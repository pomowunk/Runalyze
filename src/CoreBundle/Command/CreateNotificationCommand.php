<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Doctrine\DBAL\Connection;
use Runalyze\Bundle\CoreBundle\Component\Notifications\Message\TemplateBasedMessage;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\AccountRepository;
use Runalyze\Bundle\CoreBundle\Entity\Notification;
use Runalyze\Bundle\CoreBundle\Repository\NotificationRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CreateNotificationCommand extends Command
{
    protected static $defaultName = 'runalyze:notifications:create';

    protected AccountRepository $accountRepository;
    protected Connection $connection;
    protected NotificationRepository $notificationRepository;
    protected string $databasePrefix;

    public function __construct(
        AccountRepository $accountRepository,
        Connection $connection,
        NotificationRepository $notificationRepository,
        string $databasePrefix)
    {
        $this->accountRepository = $accountRepository;
        $this->connection = $connection;
        $this->notificationRepository = $notificationRepository;
        $this->databasePrefix = $databasePrefix;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Create global notifications')
            ->addArgument('template', InputArgument::REQUIRED, 'Template file')
            ->addOption('lang', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Languages to select accounts')
            ->addOption('exclude-lang', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Excluded languages to select accounts')
            ->addOption('account', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Account ids')
            ->addOption('lifetime', null, InputOption::VALUE_REQUIRED, 'Lifetime [days]')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force creation of notifications without prompt')
            ->addOption('last-action-before', null, InputOption::VALUE_OPTIONAL, 'Last action before x (timestamp)')
            ->addOption('last-action-after', null, InputOption::VALUE_OPTIONAL, 'Last action after x (timestamp)')
            ->addOption('registration-before', null, InputOption::VALUE_OPTIONAL, 'Registration before x (timestamp)')
            ->addOption('registration-after', null, InputOption::VALUE_OPTIONAL, 'Registration after x (timestamp)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->validateInput($input, $output)) {
            return 1;
        }

        if (!($input->getOption('force'))  ) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Continue with this action? (y/n)', false);

            if (!$helper->ask($input, $output, $question)) {
                return 0;
            }
        }

        $notification = $this->createNotification($input->getArgument('template'), $input->getOption('lifetime'));

        if (!empty($input->getOption('account'))) {
            $num = $this->insertSingleNotifications($notification, $input->getOption('account'));
        } else {
            $num = $this->insertNotificationsWithSubquery(
                $notification,
                $input->getOption('lang'),
                $input->getOption('exclude-lang'),
                $input->getOption('last-action-before'),
                $input->getOption('last-action-after'),
                $input->getOption('registration-before'),
                $input->getOption('registration-after')
            );
        }

        $output->writeln(sprintf('<info>%u notifications have been created.</info>', $num));
        $output->writeln('');

        return 0;
    }

    protected function validateInput(InputInterface $input, OutputInterface $output): bool
    {
        return (
            $this->checkValidation($this->validateTemplate($input->getArgument('template')), $output, 'Template not found.') &&
            $this->checkValidation($this->validateLanguage($input->getOption('lang')), $output, 'Language keys must be alphabetic strings.') &&
            $this->checkValidation($this->validateLanguage($input->getOption('exclude-lang')), $output, 'Language keys to exclude must be alphabetic strings.') &&
            $this->checkValidation($this->validateAccountIds($input->getOption('account')), $output, 'Account IDs must be integers.') &&
            $this->checkValidation($this->validateLifetime($input->getOption('lifetime')), $output, 'Lifetime must be an integer.')
        );
    }

    protected function checkValidation(bool $success, OutputInterface $output, string $messageOnError): bool
    {
        if (!$success) {
            $output->writeln(sprintf('<error>Invalid input: %s</error>', $messageOnError));
            $output->writeln('');

            return false;
        }

        return true;
    }

    protected function validateTemplate(string $templatePath): bool
    {
        try {
            new TemplateBasedMessage($templatePath);
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        return true;
    }

    protected function validateLanguage(array $lang): bool
    {
        return array_reduce($lang,
            function ($state, $value) {
                return $state && ctype_alpha($value);
            }, true
        );
    }

    protected function validateAccountIds(array $accountIds): bool
    {
        return array_reduce($accountIds,
            function ($state, $value) {
                return $state && ctype_digit($value);
            }, true
        );
    }

    protected function validateLifetime(string $lifetime): bool
    {
        return (null === $lifetime || ctype_digit($lifetime));
    }

    protected function createNotification(string $template, int $lifetime = null): Notification
    {
        if (null !== $lifetime) {
            $lifetime = (int)$lifetime;
        }

        return Notification::createFromMessage(
            new TemplateBasedMessage($template, $lifetime),
            new Account()
        );
    }

    protected function insertSingleNotifications(Notification $notification, array $accountIds): int
    {
        $num = 0;

        foreach ($accountIds as $id) {
            $account = $this->accountRepository->find($id);

            if (null !== $account) {
                $accountsNotification = clone $notification;
                $accountsNotification->setAccount($account);

                $this->notificationRepository->save($accountsNotification);
                $num++;
            }
        }

        return $num;
    }

    protected function insertNotificationsWithSubquery(
        Notification $notification,
        array $lang,
        array $excludeLang,
        mixed $lastActionBefore,
        mixed $lastActionAfter,
        mixed $registrationBefore,
        mixed $registrationAfter
    ): int
    {
        $accountWhere = $this->getWhereToFindRelevantAccounts($lang, $excludeLang, $lastActionBefore, $lastActionAfter, $registrationBefore, $registrationAfter);

        $statement = $this->connection->prepare(
            'INSERT INTO `'.$this->databasePrefix.'notification` (`messageType`, `createdAt`, `expirationAt`, `data`, `account_id`) '.
            'SELECT ?, ?, ?, ?, `a`.`id` FROM `'.$this->databasePrefix.'account` AS `a` WHERE '.$accountWhere
        );

        return $statement->executeStatement([
            $notification->getMessageType(),
            $notification->getCreatedAt(),
            $notification->getExpirationAt(),
            $notification->getData()
        ]);
    }

    protected function getWhereToFindRelevantAccounts(
        array $lang,
        array $excludeLang,
        mixed $lastActionBefore,
        mixed $lastActionAfter,
        mixed $registrationBefore,
        mixed $registrationAfter
    ): string
    {
        $whereCondition = [];
        $exclude = false;

        if ($lastActionAfter) {
            $whereCondition[] = '`a`.`lastaction` > '.(int)$lastActionAfter;
        }

        if ($lastActionBefore) {
            $whereCondition[] = '`a`.`lastaction` < '.(int)$lastActionBefore;
        }

        if ($registrationAfter) {
            $whereCondition[] = '`a`.`registerdate` > '.(int)$registrationAfter;
        }

        if ($registrationBefore) {
            $whereCondition[] = '`a`.`registerdate` < '.(int)$registrationBefore;

        }

        if (!empty($excludeLang)) {
            $exclude = true;
            $lang = $excludeLang;
        }

        if (!empty($lang)) {
            $whereCondition[] = '`a`.`language` '.($exclude ? 'NOT' : '').' IN ("'.implode('", "', $lang).'")';
        }

        if (!empty($whereCondition)) {
            return implode(" AND ", $whereCondition);
        }

        return '1';
    }
}
