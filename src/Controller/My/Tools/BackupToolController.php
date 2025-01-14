<?php

namespace App\Controller\My\Tools;

use App\Entity\Account;
use App\Repository\RouteRepository;
use App\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\FilenameHandler;
use Runalyze\Bundle\CoreBundle\Form\Tools\BackupExportType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/my/tools/backup")
 */
class BackupToolController extends AbstractController
{
    protected string $backupExportDirectory;

    public function __construct(string $backupExportDirectory)
    {
        $this->backupExportDirectory = $backupExportDirectory;
    }

    /**
     * @Route("/download/{filename}", name="tools-backup-download", requirements={"filename": ".+"})
     * @Security("is_granted('ROLE_USER')")
     *
     * @param string $filename
     * @param Account $account
     * @return BinaryFileResponse
     */
    public function downloadBackupAction($filename, Account $account): Response
    {
        $fileSystem = new Filesystem();
        $fileHandler = new FilenameHandler($account->getId());
        $filePath = $this->backupExportDirectory;
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
     * @Security("is_granted('ROLE_USER')")
     * @todo Fix backup by migrating to symfony/messenger.
     */
    public function backupAction(
        Account $account,
        Request $request,
        RouteRepository $routeRepository,
        TrainingRepository $trainingRepository,
        FlashBagInterface $flashBag,
    ): Response
    {
        throw $this->createNotFoundException("Backup is temporarily disabled!");
    //     $lockedRoutes = $routeRepository->accountHasLockedRoutes($account);
    //     $hasLockedTrainings = $trainingRepository->accountHasLockedTrainings($account);

    //     $form = $this->createForm(BackupExportType::class);
    //     $form->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $formdata = $request->request->get($form->getName());
    //         $producer->produce(new PlainMessage('userBackup', [
    //             'accountid' => $account->getId(),
    //             'export-type' => $formdata['fileFormat']
    //         ]));
    //         $flashBag->set('runalyze.backupjob.created', 'true');
    //     }

    //     $fileHandler = new FilenameHandler($account->getId());
    //     $finder = new Finder();
    //     $finder
    //         ->files()
    //         ->in($this->backupExportDirectory)
    //         ->filter(function(\SplFileInfo $file) use ($fileHandler) {
    //             return $fileHandler->validateInternalFilename($file->getFilename());
    //         })
    //     ->sort(function (\SplFileInfo $a, \SplFileInfo $b) {
    //         return ($b->getMTime() - $a->getMTime());
    //     });

    //     return $this->render('tools/backup/export.html.twig', [
    //         'backupjobWasCreated' => $flashBag->get('runalyze.backupjob.created'),
    //         'hasFiles' => $finder->count() > 0,
    //         'files' => $finder->getIterator(),
    //         'hasLocks' => ($lockedRoutes || $hasLockedTrainings),
    //         'form' => $form->createView()
    //     ]);
    }
}
