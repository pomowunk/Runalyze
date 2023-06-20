<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Doctrine\ORM\QueryBuilder;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\AccountRepository;
use Runalyze\Bundle\CoreBundle\Services\AccountMailer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\Filesystem;

class MailingCommand extends ContainerAwareCommand
{
    /** @var AccountMailer */
    protected $accountMailer;

    /** @var AccountRepository */
    protected $accountRepository;

    public function __construct(AccountMailer $accountMailer, AccountRepository $accountRepository)
    {
        $this->accountMailer = $accountMailer;
        $this->accountRepository = $accountRepository;

        parent::__construct();
    }


    protected function configure()
    {
        $this
            ->setName('runalyze:mails:send')
            ->setDescription('Send out a mails to users with custom templates')
            ->addArgument('template', InputArgument::REQUIRED, 'Template file')
            ->addOption('subject', null, InputOption::VALUE_REQUIRED, 'Mail subject')
            ->addOption('lang', null, InputOption::VALUE_REQUIRED, 'Languages to select accounts')
            ->addOption('exclude-lang', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Excluded languages to select accounts')
            ->addOption('account', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Account ids')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force creation of notifications without prompt')
            ->addOption('last-action-before', null, InputOption::VALUE_OPTIONAL, 'Last action before x (timestamp)')
            ->addOption('last-action-after', null, InputOption::VALUE_OPTIONAL, 'Last action after x (timestamp)')
            ->addOption('registration-before', null, InputOption::VALUE_OPTIONAL, 'Registration before x (timestamp)')
            ->addOption('registration-after', null, InputOption::VALUE_OPTIONAL, 'Registration after x (timestamp)')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return null|int null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->validateInput($input, $output)) {
            return 1;
        }

        $accounts = $this->buildQuery($input);

        if (!($input->getOption('force'))  ) {
            $helper = $this->getHelper('question');
            $output->writeln(sprintf('<info>%u mail(s) will be sent.</info>', count($accounts)));
            $question = new ConfirmationQuestion('Continue with this action? (y/n)', false);

            if (!$helper->ask($input, $output, $question)) {
                return;
            }
        }
        foreach($accounts as $account) {
            /** @var Account $account */
            $this->accountMailer->sendMailTo($account, $input->getOption('subject'), $input->getArgument('template'), ['account' => $account]);
        }
        $output->writeln(sprintf('<info>%u mail(s) have been sent.</info>', count($accounts)));
        $output->writeln('');

        return null;
    }

    /**
     * @param InputInterface $input
     * @return array
     */
    private function buildQuery(InputInterface $input) {
        $query = $this->accountRepository->createQueryBuilder('a');
        $exclude = false;

        $query->andWhere('a.allowMails = 1');

        if ($input->getOption('last-action-after')) {
            $query->andWhere('a.lastaction > '.(int)$input->getOption('last-action-after'));
        }

        if ($input->getOption('last-action-before')) {
             $query->andWhere('a.lastaction < '.(int)$input->getOption('last-action-before'));
        }

        if ($input->getOption('registration-after')) {
             $query->andWhere('a.registerdate > '.(int)$input->getOption('registration-after'));
        }

        if ($input->getOption('registration-before')) {
             $query->andWhere('a.registerdate < '.(int)$input->getOption('registration-before'));
        }

        $lang = $input->getOption('lang');

        if (!empty($input->getOption('exclude-lang'))) {
            $exclude = true;
            $lang = $input->getOption('exclude-lang');
        }
        if (!empty($input->getOption('lang')) || !empty($input->getOption('exclude-lang')) ) {
            $query->andWhere('a.language '.($exclude ? 'NOT' : '').' IN (\''.implode('", "', $lang).'\')');
        }

        if (!empty($input->getOption('account'))) {
            $query->andWhere('a.id IN ('.implode(', ', $input->getOption('account')).')');
        }

        return $query->getQuery()->getResult();

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    protected function validateInput(InputInterface $input, OutputInterface $output)
    {
        return (
            $this->checkValidation($this->validateTemplate($input->getArgument('template')), $output, 'Template not found.') &&
            $this->checkValidation($this->validateLanguage($input->getOption('lang')), $output, 'Language keys must be alphabetic strings.') &&
            $this->checkValidation($this->validateLanguage($input->getOption('exclude-lang')), $output, 'Language keys to exclude must be alphabetic strings.') &&
            $this->checkValidation($this->validateAccountIds($input->getOption('account')), $output, 'Account IDs must be integers.')
        );
    }

    /**
     * @param bool $success
     * @param OutputInterface $output
     * @param string $messageOnError
     * @return bool
     */
    protected function checkValidation($success, OutputInterface $output, $messageOnError)
    {
        if (!$success) {
            $output->writeln(sprintf('<error>Invalid input: %s</error>', $messageOnError));
            $output->writeln('');

            return false;
        }

        return true;
    }

    /**
     * @param string $templatePath
     * @return bool
     */
    protected function validateTemplate($templatePath)
    {
        return (new Filesystem())->exists($templatePath);
    }

    /**
     * @param array $lang
     * @return bool
     */
    protected function validateLanguage(array $lang)
    {
        return array_reduce($lang,
            function ($state, $value) {
                return $state && ctype_alpha($value);
            }, true
        );
    }

    /**
     * @param array $accountIds
     * @return bool
     */
    protected function validateAccountIds(array $accountIds)
    {
        return array_reduce($accountIds,
            function ($state, $value) {
                return $state && ctype_digit($value);
            }, true
        );
    }

    /**
     * @param null|string $lifetime
     * @return bool
     */
    protected function validateLifetime($lifetime)
    {
        return (null === $lifetime || ctype_digit($lifetime));
    }

}
