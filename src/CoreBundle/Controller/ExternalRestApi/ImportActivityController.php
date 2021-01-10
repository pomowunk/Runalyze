<?php

namespace Runalyze\Bundle\CoreBundle\Controller\ExternalRestApi;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

//use Symfony\Component\Console\Application;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use Runalyze\Bundle\CoreBundle\Entity\Account;

/**
 * Uploads the files with this REST API and call the "runalyze:activity:bulk-import" to import the files.
 * The POST request must be a "multipart/form-data" to support multiple times in one request.
 * 
 * If no problems occur and all uploaded files are imported successfully a HTTP state 200 is returned otherwise (duplicates or failed) a state 202 is returned.
 * If there are technical trouble (while creating the tmp-folder, moving files ...) a HTTP state 500 is returned.
 * Additional infos and the output of the "runalyze:activity:bulk-import" are set in the response content as "Content-Type: text/plain"
 * 
 * example with 2 files:
 * curl -k -u "user:pwd" -H "Accept-Language: de,en" -X POST https://<domain>/api/import/activity -F 'file1=@afile.fit' -F 'file2=@another.fit' -w 'HttpCode: %{http_code}\n'
 * 
 * #TSC
 */
class ImportActivityController extends Controller
{
    const IMPORT_CMD = 'runalyze:activity:bulk-import';
    const SUCCESS_MSG = 'successfully imported';

    /**
     * import as REST api.
     * 
     * basic steps:
     * - create a tmp folder under /tmp/<username_<timestamp>
     * - move uploaded files to the created tmp-folder
     * - call the existing command "runalyze:activity:bulk-import"
     * - cleanup: remove uploaded files and tmp-folder
     * 
     * @Route("/api/import/activity")
     * @Security("has_role('ROLE_USER')")
     * @Method("POST")
     */
    public function postImportActivity(Request $request, Account $account) {
        $result = '';

        $resultCode = Response::HTTP_OK; // 200

        $username = $account->getUsername();

        /*
            $request->files->all() are a array of UploadedFile-Objects

            [data1] => Symfony\Component\HttpFoundation\File\UploadedFile Object
                (
                    [test:Symfony\Component\HttpFoundation\File\UploadedFile:private] => 
                    [originalName:Symfony\Component\HttpFoundation\File\UploadedFile:private] => 2020-10-09-15-26-00.fit
                    [mimeType:Symfony\Component\HttpFoundation\File\UploadedFile:private] => application/octet-stream
                    [size:Symfony\Component\HttpFoundation\File\UploadedFile:private] => 162129
                    [error:Symfony\Component\HttpFoundation\File\UploadedFile:private] => 0
                    [pathName:SplFileInfo:private] => /tmp/phpOZLwMG
                    [fileName:SplFileInfo:private] => phpOZLwMG
                )
        */
        $allfiles = $request->files->all();
        $allfilesCount = count($allfiles);

        $uploadDir = $this->createUploadDir($username);
        if($uploadDir != null) {
            $result = $result . 'Storing ' . $allfilesCount . ' uploaded files to temp folder ' . $uploadDir . '.' . PHP_EOL;

            try {
                // process all uploaded files
                $processFilesResult = $this->processFiles($username, $uploadDir, $allfiles);
                $result = $result . $processFilesResult;

                // if not all files processed "successfully" document it and send HTTP state 202
                $notSuccessful = $allfilesCount - substr_count($processFilesResult, self::SUCCESS_MSG);
                if($notSuccessful != 0) {
                    $result = $result . 'WARNING: ' . $notSuccessful .' of ' . $allfilesCount . ' files are NOT imported successfully - please check the above protocol.' . PHP_EOL;
                    $resultCode = Response::HTTP_ACCEPTED; // 202
                }
            } catch (\Exception $e) {
                $result = $result . 'ERROR: Error while importing ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
                $resultCode = Response::HTTP_INTERNAL_SERVER_ERROR; // 500
            }
        } else {
            $result = $result . 'ERROR: Error while creating temp folder!' . PHP_EOL;
            $resultCode = Response::HTTP_INTERNAL_SERVER_ERROR; // 500
        }

        return new Response($result, $resultCode, [ 'Content-Type' => 'text/plain' ]);
    }

    private function createUploadDir($username) {
        $tmpDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $username . '_' . microtime(true);
        if(mkdir($tmpDir)) {
            return $tmpDir;
        } else {
            return null;
        }
    }

    private function processFiles($username, $uploadDir, $allfiles) {
        $result = '';

        // move from tmp-name (/tmp/phpOZLwMG) to the tmp-upload-folder (in error case an exception is thrown)
        foreach($allfiles as $k => $v) {
            $v->move($uploadDir, $v->getClientOriginalName());
        }

        // process import as command call
        $result = $result . '-------------------' . PHP_EOL;
        $consoleResult = $this->processImport($username, $uploadDir);
        $result = $result . $consoleResult . PHP_EOL;
        $result = $result . '-------------------' . PHP_EOL;

        // cleanup: remove uploaded files
        $result = $result . 'Cleanup temp folder and uploaded files.' . PHP_EOL;
        foreach($allfiles as $k => $v) {
            $f = $uploadDir . DIRECTORY_SEPARATOR .$v->getClientOriginalName();
            if(!unlink($f)) {
                $result = $result . 'WARNING: Can not remove file ' . $f . PHP_EOL;
            }
        }
        
        // cleanup: remove temp created folder
        if(!rmdir($uploadDir)) {
            $result = $result . 'WARNING: Can not remove upload-folder ' . $uploadDir . PHP_EOL;
        }

        $result = $result . PHP_EOL;

        return $result;
    }

    private function processImport($username, $uploadDir) {
        $kernel = $this->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => self::IMPORT_CMD,
//            '--env' => 'prod',
            'username' => $username,
            'path' => $uploadDir
        ]);

        // You can use NullOutput() if you don't need the output
        $output = new BufferedOutput();
        $application->run($input, $output);

        // return the output, don't use if you used NullOutput()
        return $output->fetch();
    }
}
