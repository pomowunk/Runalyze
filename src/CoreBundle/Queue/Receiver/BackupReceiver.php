<?php

namespace Runalyze\Bundle\CoreBundle\Queue\Receiver;

use App\Entity\Notification;
use App\Repository\AccountRepository;
use App\Repository\NotificationRepository;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\FilenameHandler;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\JsonBackup;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\SqlBackup;
use Runalyze\Bundle\CoreBundle\Component\Notifications\Message\BackupReadyMessage;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BackupReceiver
{
    protected AccountRepository $accountRepository;
    protected NotificationRepository $notificationRepository;
    protected string $backupExportDirectory;
    protected string $databasePrefix;
    protected string $runalyzeVersion;
    protected ParameterBagInterface $parameterBag;
    
    public function __construct(
        AccountRepository $accountRepository,
        NotificationRepository $notificationRepository,
        string $backupExportDirectory,
        string $databasePrefix,
        string $runalyzeVersion,
        ParameterBagInterface $parameterBag,
    )
    {
        $this->accountRepository = $accountRepository;
        $this->notificationRepository = $notificationRepository;
        $this->backupExportDirectory = $backupExportDirectory;
        $this->databasePrefix = $databasePrefix;
        $this->runalyzeVersion = $runalyzeVersion;
        $this->parameterBag = $parameterBag;
    }

    public function userBackup($message = null)
    {
        $Frontend = new \FrontendShared($this->parameterBag, true);

        $fileHandler = new FilenameHandler($message->get('accountid'));
        $fileHandler->setRunalyzeVersion($this->runalyzeVersion);

        $account = $this->accountRepository->find($message->get('accountid'));

        if (!is_dir($this->backupExportDirectory)) {
            mkdir($this->backupExportDirectory, 0777, true);
        }

        if ('json' == $message->get('export-type')) {
            $Backup = new JsonBackup(
                $this->backupExportDirectory.$fileHandler->generateInternalFilename(FilenameHandler::JSON_FORMAT),
                $message->get('accountid'),
                \DB::getInstance(),
                $this->databasePrefix,
                $this->runalyzeVersion
            );
            $Backup->run();
        } else {
            $Backup = new SqlBackup(
                $this->backupExportDirectory.$fileHandler->generateInternalFilename(FilenameHandler::SQL_FORMAT),
                $message->get('accountid'),
                \DB::getInstance(),
                $this->databasePrefix,
                $this->runalyzeVersion
            );
            $Backup->run();
        }

        $this->notificationRepository->save(
            Notification::createFromMessage(new BackupReadyMessage(), $account)
        );
        gc_collect_cycles();
    }
}
