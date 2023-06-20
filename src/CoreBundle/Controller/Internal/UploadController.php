<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Internal;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/_internal/upload")
 */
class UploadController extends AbstractController
{
    /** @var string */
    protected $activityImportDirectory;

    public function __construct(string $activityImportDirectory)
    {
        $this->activityImportDirectory = $activityImportDirectory;
    }

    /**
     * @Route("", name="internal-activity-upload")
     * @Security("has_role('ROLE_USER')")
     */
    public function uploadAction(Request $request)
    {
        if ($request->files->has('qqfile')) {
            /** @var UploadedFile $file */
            $file = $request->files->get('qqfile');
            $newFileName = str_replace(';', '_-_', $file->getClientOriginalName());

            if (class_exists('Normalizer')) {
                $newFileName = \Normalizer::normalize($newFileName);
            }

            try {
                if (!is_dir($this->activityImportDirectory)) {
                    mkdir($this->activityImportDirectory, 0777, true);
                }

                $file->move(
                    $this->activityImportDirectory,
                    $newFileName
                );

                return new JsonResponse(['success' => true]);
            } catch (FileException $e) {
                return new JsonResponse(['error' => $e->getMessage()]);
            }
        }

        return new JsonResponse(['error' => 'No file given.']);
    }

    /**
     * @Route("/tcx", name="internal-activity-upload-tcx")
     * @Security("has_role('ROLE_USER')")
     */
    public function ajaxSaveTcxAction(Request $request)
    {
        if (!$request->request->has('activityId') || !$request->request->has('data')) {
            return new JsonResponse(['error' => 'No data given.']);
        }

        $filesystem = new Filesystem();
        $fileName = $request->request->get('activityId').'.tcx';

        if (class_exists('Normalizer')) {
            $fileName = \Normalizer::normalize($fileName);
        }

        try {
            if (!is_dir($this->activityImportDirectory)) {
                mkdir($this->activityImportDirectory, 0777, true);
            }
            $filesystem->appendToFile(
                $this->activityImportDirectory.$fileName,
                $request->request->get('data')
            );

            return new JsonResponse(['success' => true]);
        } catch (FileException $e) {
            return new JsonResponse(['error' => $e->getMessage()]);
        }
    }
}
