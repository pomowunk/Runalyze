<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContext;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Import\FileImportResult;
use Runalyze\Parser\Activity\Common\Data\ActivityDataContainer;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class ActivityBulkImportCommand extends ContainerAwareCommand
{
    // move files after import to "this"-folder
    private $moveFolder;

    /** @var array */
    protected $FailedImports = array();

    protected function configure()
    {
        $this
            ->setName('runalyze:activity:bulk-import')
            ->setDescription('Bulk import of activity files')
            ->addArgument('username', InputArgument::REQUIRED, 'username')
            ->addArgument('path', InputArgument::REQUIRED, 'Path to files')
            // #TSC: new optional to set the sports profile if the imported file has no sport (f.e. GPX files) -> usage "--sport=mountaineering"
            ->addOption('sport', null, InputOption::VALUE_OPTIONAL, 'Override sport profile')
            // #TSC: new optional to move files to other folder under the path-argument -> usage "--move=foldername"
            ->addOption('move', null, InputOption::VALUE_OPTIONAL, 'Move imported files to specified folder');
    }

    /**
     * @return TrainingRepository
     */
    protected function getTrainingRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('CoreBundle:Training');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return null|int null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repository = $this->getContainer()->get('doctrine')->getRepository('CoreBundle:Account');
        $user = $repository->loadUserByUsername($input->getArgument('username'));

        if (null === $user) {
            $output->writeln('<fg=red>Unknown User</>');

            return 1;
        }

        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->getContainer()->get('security.token_storage')->setToken($token);

        $importer = $this->getContainer()->get('app.file_importer');
        $dataDirectory = $this->getContainer()->getParameter('data_directory');
        $path = $input->getArgument('path');

        // #TSC - new arguments/options
        $sport = $input->getOption('sport');
        $this->moveFolder = $input->getOption('move');

        if(!empty($sport)) {
            $output->writeln('<info>using sport=' . $sport . '</info>');
        }
        if(!empty($this->moveFolder)) {
            $output->writeln('<info>move imported files to ' . $this->moveFolder . '</info>');
        }

        $it = new \FilesystemIterator($path);
        $fs = new Filesystem();

        $files = [];

        foreach ($it as $fileinfo) {
            $file = $fileinfo->getFilename();

            if (!is_file($path.'/'.$file)) {
                continue;
            }

            // TSC: do not use "'bulk-import'.uniqid()" for bulk uploads, because the filename is set in the activity title
            // $filename = 'bulk-import'.uniqid().$file;
            $filename = $file;
            $fs->copy($path.'/'.$file, $dataDirectory.'/import/'.$filename);
            $files[] = $dataDirectory.'/import/'.$filename;
        }

        $importResult = $importer->importFiles($files);
        $importResult->completeAndFilterResults($this->getContainer()->get('app.activity_data_container.filter'));
        $contextAdapterFactory = $this->getContainer()->get('app.activity_context_adapter_factory');
        $defaultLocation = $this->getContainer()->get('app.configuration_manager')->getList()->getActivityForm()->getDefaultLocationForWeatherForecast();

        foreach ($importResult as $result) {
            /** @var $result FileImportResult */
            foreach ($result->getContainer() as $container) {

                // #TSC override the sportname to set the sport profile
                if(!empty($sport)) {
                    $container->Metadata->setSportName($sport);
                }

                $activity = $this->containerToActivity($container, $user);
                $context = new ActivityContext($activity, null, null, $activity->getRoute());
                $contextAdapter = $contextAdapterFactory->getAdapterFor($context);
                $output->writeln('<info>'.$result->getOriginalFileName().'</info>');

                if ($contextAdapter->isPossibleDuplicate()) {
                    $output->writeln('<fg=yellow> ... is a duplicate</>');

                    $this->moveFile($fs, $path, $result->getOriginalFileName(), $output);
                    break;
                }

                $contextAdapter->guessWeatherConditions($defaultLocation);
                $this->getTrainingRepository()->save($activity);
                $output->writeln('<fg=green> ... successfully imported</>');
                $this->moveFile($fs, $path, $result->getOriginalFileName(), $output);
            }
        }

        if (!empty($this->FailedImports)) {
            $output->writeln('');
            $output->writeln('<fg=red>Failed imports:</>');

            foreach ($this->FailedImports as $fileName => $message) {
                $output->writeln('<fg=red> - '.$fileName.': '.$message.'</>');
            }
        }

        $output->writeln('');
        $output->writeln('Done.');
    }

    /**
     * TSC: move the imported file to a specified folder under path-argument.
    */ 
    private function moveFile(Filesystem $fs, string $path, string $importedFilename, OutputInterface $output)
    {
        if(!empty($this->moveFolder)) {
            $path_parts = pathinfo($importedFilename);
            $source = $path . '/' . $path_parts['basename'];
            $moveTo = $path . '/' . $this->moveFolder. '/' . $path_parts['basename'];
            echo 'moveTo:'.$moveTo;

            if(!rename($source, $moveTo)) {
                $output->writeln('<fg=red>Cant move to ' . $moveTo . '</>');
            }
        }
    }

    /**
     * @param ActivityDataContainer $container
     * @param Account $account
     * @return \Runalyze\Bundle\CoreBundle\Entity\Training
     */
    protected function containerToActivity(ActivityDataContainer $container, Account $account)
    {
        return $this->getContainer()->get('app.activity_data_container.converter')->getActivityFor($container, $account);
    }

    private function addFailedFile($fileName, $error)
    {
        $this->FailedImports[$fileName] = $error;
    }
}
