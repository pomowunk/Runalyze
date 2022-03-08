<?php

namespace Runalyze\Bundle\CoreBundle\Queue\Receiver;

use Bernard\Message\PlainMessage;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\FilenameHandler;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\JsonBackup;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\SqlBackup;
use Runalyze\Bundle\CoreBundle\Entity\Notification;
use Runalyze\Bundle\CoreBundle\Component\Notifications\Message\BackupReadyMessage;
use Runalyze\Bundle\CoreBundle\Repository\AccountRepository;
use Runalyze\Bundle\CoreBundle\Repository\NotificationRepository;

class BackupReceiver
{
    /** @var AccountRepository */
    protected $accountRepository;

    /** @var NotificationRepository */
    protected $notificationRepository;

    /** @var string */
    protected $backupPath;

    /** @var string */
    protected $databasePrefix;

    /** @var string */
    protected $runalyzeVersion;

    public function __construct(
        AccountRepository $accountRepository,
        NotificationRepository $notificationRepository,
        string $dataDirectory,
        string $databasePrefix,
        string $runalyzeVersion)
    {
        $this->accountRepository = $accountRepository;
        $this->notificationRepository = $notificationRepository;
        $this->backupPath = $dataDirectory.'/backup-tool/backup/';
        $this->databasePrefix = $databasePrefix;
        $this->runalyzeVersion = $runalyzeVersion;
    }

    public function userBackup(PlainMessage $message)
    {
        $Frontend = new \FrontendShared(true);

        $fileHandler = new FilenameHandler($message->get('accountid'));
        $fileHandler->setRunalyzeVersion($this->runalyzeVersion);

        $account = $this->accountRepository->find($message->get('accountid'));

        if ('json' == $message->get('export-type')) {
            $Backup = new JsonBackup(
                $this->backupPath.$fileHandler->generateInternalFilename(FilenameHandler::JSON_FORMAT),
                $message->get('accountid'),
                \DB::getInstance(),
                $this->databasePrefix,
                $this->runalyzeVersion
            );
            $Backup->run();
        } else {
            $Backup = new SqlBackup(
                $this->backupPath.$fileHandler->generateInternalFilename(FilenameHandler::SQL_FORMAT),
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
