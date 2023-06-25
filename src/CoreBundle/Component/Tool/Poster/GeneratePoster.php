<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Poster;

use App\Entity\Account;
use App\Entity\Sport;
use App\Repository\TrainingRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class GeneratePoster
{
    protected array $Parameter = [];
    protected string $projectDir;
    protected string $posterSvgDirectory;
    protected string $Python3Path;
    protected TrainingRepository $TrainingRepository;
    protected string $Filename;
    protected string $StdErr = '';

    public function __construct(
        string $projectDirectory,
        string $posterSvgDirectory,
        string $python3Path,
        TrainingRepository $trainingRepository,
    ) {
        $this->projectDir = $projectDirectory;
        $this->posterSvgDirectory = $posterSvgDirectory;
        $this->Python3Path = $python3Path;
        $this->TrainingRepository = $trainingRepository;
    }

    /**
     * @return string
     */
    protected function pathToRepository()
    {
        return $this->projectDir.'/vendor/runalyze/gpxtrackposter/';
    }

    /**
     * @param string $athlete
     * @param string $year
     */
    protected function generateRandomFileName($athlete, $year)
    {
        $this->Filename = md5($athlete.$year.strtotime("now")).'.svg';
    }

    /**
     * @return string path to generated file
     */
    public function generate()
    {
        if (!is_dir($this->posterSvgDirectory)) {
            mkdir($this->posterSvgDirectory, 0777, true);
        }

        $cmd = $this->Python3Path.' create_poster.py '.implode(' ', $this->Parameter);
        $builder = new Process($cmd);
        $builder->setWorkingDirectory(realpath($this->pathToRepository()));
        $builder->run();
        $this->StdErr = $builder->getErrorOutput();

        return $this->posterSvgDirectory.$this->Filename;
    }

    /**
     * @param string $type
     * @param string $jsonDir
     * @param int $year
     * @param Account $account
     * @param Sport $sport
     * @param null|string $title
     * 
     * @todo Move athlete.svg and runalyze.svg to assets/poster/, pass the path to poster generator python script!
     */
    public function buildCommand($type, $jsonDir, $year, Account $account, Sport $sport, $title, $backgroundColor, $trackColor, $textColor, $raceColor)
    {
        $this->Parameter = [];
        
        $this->generateRandomFileName($account->getUsername(), (string)$year);

        $this->Parameter[] = '--json-dir '.escapeshellarg($jsonDir);
        $this->Parameter[] = '--athlete '.escapeshellarg($account->getUsername());
        $this->Parameter[] = '--year '.(string)(int)$year;
        $this->Parameter[] = '--output '.escapeshellarg($this->posterSvgDirectory.$this->Filename);
        $this->Parameter[] = '--type '.$type;
        $this->Parameter[] = '--title '.escapeshellarg($title);
        $this->Parameter[] = '--background-color '.escapeshellarg($backgroundColor);
        $this->Parameter[] = '--track-color '.escapeshellarg($trackColor);
        $this->Parameter[] = '--text-color '.escapeshellarg($textColor);
        $this->Parameter[] = '--special-color '.escapeshellarg($raceColor);

        $this->addStatsParameter($account, $sport, $year);

        if ((new Filesystem())->exists($jsonDir.'/special.params')) {
            foreach(json_decode(file_get_contents($jsonDir.'/special.params')) as $special) {
                $this->Parameter[] = '--special '.$special;
            }
        }
    }

    /**
     * @param Account $account
     * @param Sport $sport
     * @param int $year
     */
    private function addStatsParameter(Account $account, Sport $sport, $year)
    {
        $stats = $this->TrainingRepository->getStatsForPoster($account, $sport, $year)->getArrayResult();
        $data = $stats[0];

        $this->Parameter[] = '--stat-num '.(int)$data['num'];
        $this->Parameter[] = '--stat-total '.(float)$data['total_distance'];
        $this->Parameter[] = '--stat-min '.(float)$data['min_distance'];
        $this->Parameter[] = '--stat-max '.(float)$data['max_distance'];
    }

    /**
     * @return array
     */
    public function availablePosterTypes()
    {
        return ['grid', 'calendar', 'circular', 'heatmap'];
    }

    public function deleteSvg()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->posterSvgDirectory.$this->Filename);
    }
    
    /**
     * @return string
     */
    public function getErrorOutput() {
        return $this->StdErr;
    }
}
