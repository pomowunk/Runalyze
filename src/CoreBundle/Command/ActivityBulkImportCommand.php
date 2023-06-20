<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContext;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\AccountRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityContextAdapterFactory;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityDataContainerFilter;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityDataContainerToActivityContextConverter;
use Runalyze\Bundle\CoreBundle\Services\Import\FileImporter;
use Runalyze\Bundle\CoreBundle\Services\Import\FileImportResult;
use Runalyze\Parser\Activity\Common\Data\ActivityDataContainer;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class ActivityBulkImportCommand extends ContainerAwareCommand
{
    /** @var array */
    protected $FailedImports = array();

    /** @var AccountRepository */
    protected $accountRepository;

    /** @var ActivityContextAdapterFactory */
    protected $activityContextAdapterFactory;

    /** @var ActivityDataContainerFilter */
    protected $activityDataContainerFilter;

    /** @var ActivityDataContainerToActivityContextConverter */
    protected $converter;

    /** @var ConfigurationManager */
    protected $configurationManager;

    /** @var FileImporter */
    protected $fileImporter;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var TrainingRepository */
    protected $trainingRepository;

    /** @var string */
    protected $activityImportDirectory;

    public function __construct(
        AccountRepository $accountRepository,
        ActivityContextAdapterFactory $activityContextAdapterFactory,
        ActivityDataContainerFilter $activityDataContainerFilter,
        ActivityDataContainerToActivityContextConverter $converter,
        ConfigurationManager $configurationManager,
        FileImporter $fileImporter,
        TokenStorageInterface $tokenStorage,
        TrainingRepository $trainingRepository,
        string $activityImportDirectory)
    {
        $this->accountRepository = $accountRepository;
        $this->activityContextAdapterFactory = $activityContextAdapterFactory;
        $this->activityDataContainerFilter = $activityDataContainerFilter;
        $this->converter = $converter;
        $this->configurationManager = $configurationManager;
        $this->fileImporter = $fileImporter;
        $this->tokenStorage = $tokenStorage;
        $this->trainingRepository = $trainingRepository;
        $this->activityImportDirectory = $activityImportDirectory;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('runalyze:activity:bulk-import')
            ->setDescription('Bulk import of activity files')
            ->addArgument('username', InputArgument::REQUIRED, 'username')
            ->addArgument('path', InputArgument::REQUIRED, 'Path to files');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return null|int null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user = $this->accountRepository->loadUserByUsername($input->getArgument('username'));

        if (null === $user) {
            $output->writeln('<fg=red>Unknown User</>');

            return 1;
        }

        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);

        $path = $input->getArgument('path');
        $it = new \FilesystemIterator($path);
        $fs = new Filesystem();

        $files = [];

        if (!is_dir($this->activityImportDirectory)) {
            mkdir($this->activityImportDirectory, 0777, true);
        }

        foreach ($it as $fileinfo) {
            $file = $fileinfo->getFilename();

            if (!is_file($path.'/'.$file)) {
                continue;
            }

            $filename = 'bulk-import'.uniqid().'_'.$file;
            $fs->copy($path.'/'.$file, $this->activityImportDirectory.$filename);
            $files[] = $this->activityImportDirectory.$filename;
        }

        $importResult = $this->fileImporter->importFiles($files);
        $importResult->completeAndFilterResults($this->activityDataContainerFilter);
        $defaultLocation = $this->configurationManager->getList()->getActivityForm()->getDefaultLocationForWeatherForecast();

        foreach ($importResult as $result) {
            /** @var FileImportResult $result */
            foreach ($result->getContainer() as $container) {
                $activity = $this->containerToActivity($container, $user);
                $context = new ActivityContext($activity, null, null, $activity->getRoute());
                $contextAdapter = $this->activityContextAdapterFactory->getAdapterFor($context);
                $output->writeln('<info>'.$result->getOriginalFileName().'</info>');

                if ($contextAdapter->isPossibleDuplicate()) {
                    $output->writeln('<fg=yellow> ... is a duplicate</>');
                    break;
                }

                $contextAdapter->guessWeatherConditions($defaultLocation);
                $this->trainingRepository->save($activity);
                $output->writeln('<fg=green> ... successfully imported</>');
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
     * @param ActivityDataContainer $container
     * @param Account $account
     * @return \Runalyze\Bundle\CoreBundle\Entity\Training
     */
    protected function containerToActivity(ActivityDataContainer $container, Account $account)
    {
        return $this->converter->getActivityFor($container, $account);
    }

    private function addFailedFile($fileName, $error)
    {
        $this->FailedImports[$fileName] = $error;
    }
}
