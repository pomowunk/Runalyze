<?php

namespace Runalyze\Bundle\CoreBundle\Controller\My\Tools;

use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\FilenameHandler;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\JsonBackupAnalyzer;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\JsonImporter;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @Route("/my/tools/backup-import")
 */
class JsonImportToolController extends Controller
{
    /** @var string */
    protected $importPath;

    /** @var string */
    protected $runalyzeVersion;

    /** @var string */
    protected $databasePrefix;

    public function __construct(string $dataDirectory, string $runalyzeVersion, string $databasePrefix)
    {
        $this->importPath = $dataDirectory.'/backup-tool/import/';
        $this->runalyzeVersion = $runalyzeVersion;
        $this->databasePrefix = $databasePrefix;
    }

    /**
     * @Route("/upload", name="tools-backup-json-upload")
     * @Security("has_role('ROLE_USER')")
     */
    public function backupUploadAction(Request $request, FlashBagInterface $flashBag)
    {
        $backupFile = $request->files->get('qqfile');

        if (null === $backupFile) {
            return $this->json(['error' => 'File upload did not work.']);
        }

        if (!FilenameHandler::validateImportFileExtension($backupFile->getClientOriginalName())) {
            return $this->json(['error' => 'Wrong file extension.']);
        }

        try {
            $backupFile->move($this->importPath, $backupFile->getClientOriginalName());
        } catch (FileException $e) {
            return $this->json(['error' => 'Moving file did not work. Set chmod 777 for /data/backup-tool/import/']);
        }

        $flashBag->set('json-import.file', $backupFile->getClientOriginalName());

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/import", name="tools-backup-json-import")
     * @Security("has_role('ROLE_USER')")
     */
    public function backupImportAction(FlashBagInterface $flashBag)
    {
        if (!$flashBag->has('json-import.file')) {
            return $this->redirectToRoute('tools-backup-json');
        }

        $filename = $flashBag->get('json-import.file')[0];
        $fileInfo = new \SplFileInfo($this->importPath.$filename);
        $analyzer = new JsonBackupAnalyzer($this->importPath.$filename, $this->runalyzeVersion);

        if (!$analyzer->fileIsOkay()) {
            (new Filesystem())->remove($this->importPath.$filename);

            return $this->render('tools/backup/import_bad_file.html.twig', [
                'file' => $fileInfo,
                'versionIsOkay' => $analyzer->versionIsOkay(),
                'runalyzeVersion' => $this->runalyzeVersion,
                'runalyzeVersionFile' => $analyzer->fileVersion()
            ]);
        }

        $flashBag->set('json-import.file', $filename);

        return $this->render('tools/backup/import_form.html.twig', [
            'file' => $fileInfo,
            'numActivities' => $analyzer->count('runalyze_training'),
            'numBodyValues' => $analyzer->count('runalyze_user')
        ]);
    }

    /**
     * @Route("/import/do", name="tools-backup-json-import-do")
     * @Security("has_role('ROLE_USER')")
     */
    public function backupImportDoAction(
        Request $request,
        Account $account,
        TokenStorageInterface $tokenStorage,
        FlashBagInterface $flashBag)
    {
        $Frontend = new \Frontend(true, $tokenStorage);

        if (!$flashBag->has('json-import.file')) {
            return $this->redirectToRoute('tools-backup-json');
        }

        $filename = $flashBag->get('json-import.file')[0];

        $importer = new JsonImporter(
            $this->importPath.$filename,
            \DB::getInstance(),
            $account->getId(),
            $this->databasePrefix
        );

        if ($request->request->get('delete_trainings')) {
            $importer->deleteOldActivities();
        }

        if ($request->request->get('delete_user_data')) {
            $importer->deleteOldBodyValues();
        }

        $importer->enableOverwritingConfig($request->request->get('overwrite_config'));
        $importer->enableOverwritingDataset($request->request->get('overwrite_dataset'));
        $importer->enableOverwritingPlugins($request->request->get('overwrite_plugin'));
        $importer->importData();

        return $this->render('tools/backup/import_finish.html.twig', [
            'results' => $importer->resultsAsString()
        ]);
    }

    /**
     * @Route("", name="tools-backup-json")
     * @Security("has_role('ROLE_USER')")
     */
    public function uploadFormAction()
    {
        return $this->render('tools/backup/upload_form.html.twig', [
            'runalyzeVersion' => $this->runalyzeVersion
        ]);
    }
}
