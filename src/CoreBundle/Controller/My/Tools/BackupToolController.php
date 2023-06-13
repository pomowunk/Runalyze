<?php

namespace Runalyze\Bundle\CoreBundle\Controller\My\Tools;

use Bernard\Message\PlainMessage;
use Bernard\Producer;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\FilenameHandler;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\RouteRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Form\Tools\BackupExportType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/my/tools/backup")
 */
class BackupToolController extends Controller
{
    /** @var string */
    protected $backupPath;

    public function __construct(string $dataDirectory)
    {
        $this->backupPath = $dataDirectory.'/backup-tool/backup/';
    }

    /**
     * @Route("/download/{filename}", name="tools-backup-download", requirements={"filename": ".+"})
     * @Security("has_role('ROLE_USER')")
     *
     * @param string $filename
     * @param Account $account
     * @return BinaryFileResponse
     */
    public function downloadBackupAction($filename, Account $account)
    {
        $fileSystem = new Filesystem();
        $fileHandler = new FilenameHandler($account->getId());
        $filePath = $this->backupPath;
        $internalFilename = $fileHandler->transformPublicToInternalFilename($filename);

        if (!$fileSystem->exists($filePath.$internalFilename)) {
            throw $this->createNotFoundException();
        }

        if (!$fileHandler->validateInternalFilename($internalFilename)) {
            throw $this->createAccessDeniedException();
        }

        $response = new BinaryFileResponse($filePath.$internalFilename);
        $response->trustXSendfileTypeHeader();
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename,
            iconv('UTF-8', 'ASCII//TRANSLIT', $filename)
        );

        return $response;
    }

    /**
     * @Route("", name="tools-backup")
     * @Security("has_role('ROLE_USER')")
     */
    public function backupAction(
        Account $account,
        Request $request,
        RouteRepository $routeRepository,
        TrainingRepository $trainingRepository,
        Producer $producer,
        FlashBagInterface $flashBag)
    {
        $lockedRoutes = $routeRepository->accountHasLockedRoutes($account);
        $hasLockedTrainings = $trainingRepository->accountHasLockedTrainings($account);

        $form = $this->createForm(BackupExportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formdata = $request->request->get($form->getName());
            $producer->produce(new PlainMessage('userBackup', [
                'accountid' => $account->getId(),
                'export-type' => $formdata['fileFormat']
            ]));
            $flashBag->set('runalyze.backupjob.created', 'true');
        }

        $fileHandler = new FilenameHandler($account->getId());
        $finder = new Finder();
        $finder
            ->files()
            ->in($this->backupPath)
            ->filter(function(\SplFileInfo $file) use ($fileHandler) {
                return $fileHandler->validateInternalFilename($file->getFilename());
            })
        ->sort(function (\SplFileInfo $a, \SplFileInfo $b) {
            return ($b->getMTime() - $a->getMTime());
        });

        return $this->render('tools/backup/export.html.twig', [
            'backupjobWasCreated' => $flashBag->get('runalyze.backupjob.created'),
            'hasFiles' => $finder->count() > 0,
            'files' => $finder->getIterator(),
            'hasLocks' => ($lockedRoutes || $hasLockedTrainings),
            'form' => $form->createView()
        ]);
    }
}
