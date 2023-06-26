<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Poster;

use App\Entity\Account;
use App\Entity\Sport;
use App\Repository\RaceresultRepository;
use App\Repository\TrainingRepository;
use Doctrine\ORM\Query;
use Runalyze\Model;
use Symfony\Component\Filesystem\Filesystem;

class GenerateJsonData
{
    protected TrainingRepository $TrainingRepository;
    protected RaceresultRepository $RaceresultRepository;
    protected string $posterJsonDirectory;
    protected string $Directory;

    public function __construct(TrainingRepository $trainingRepository, RaceresultRepository $raceresultRepository, $posterJsonDirectory)
    {
        $this->TrainingRepository = $trainingRepository;
        $this->RaceresultRepository = $raceresultRepository;
        $this->posterJsonDirectory = $posterJsonDirectory;
    }

    /**
     * @return string
     */
    public function getPathToJsonFiles()
    {
        return $this->posterJsonDirectory.'/'.$this->Directory;
    }

    /**
     * @param int $timestamp
     * @return string
     */
    private function generateJsonFilename($timestamp)
    {
        return date('Y-m-d-His', $timestamp).'.json';
    }

    /**
     * @param Account $account
     * @param Sport $sport
     * @param int $year
     */
    public function createJsonFilesFor(Account $account, Sport $sport, $year)
    {
        $this->Directory = md5($account->getId().strtotime("now"));

        $filesystem = new Filesystem();
        $filesystem->mkdir($this->getPathToJsonFiles(), 0777);
        $counter = 0;

        $query = $this->TrainingRepository->getQueryForJsonPosterData($account, $sport, $year);
        $result = $query->iterate(null, Query::HYDRATE_SCALAR);

        while ($data = $result->next()) {
            $data = $data[0];
            $json = [
                'start' => date('Y-m-d H:i:s', $data['time']),
                'end' => date('Y-m-d H:i:s', $data['time'] + $data['s']),
                'length' => 1000.0 * (float)$data['distance'],
                'segments' => $this->getSegmentsFor($data['geohashes'], $data['distance'])
            ];
            $filesystem->dumpFile($this->getPathToJsonFiles().'/'.$this->generateJsonFilename($data['time']), json_encode($json));
            $counter++;
        }

        $this->listSpecialFiles($account, $sport, $year);
    }

    /**
     * @param string $geohashLine
     * @param float $distance
     * @return array
     */
    protected function getSegmentsFor($geohashLine, $distance)
    {
        $segments = [];
        $segments[] = [];

        if (null === $geohashLine || $geohashLine == '') {
            return $segments;
        }

        $loop = new Model\Route\Loop(new Model\Route\Entity([Model\Route\Entity::GEOHASHES => $geohashLine]));
        $loop->setStepSize(5);
        $pauseLimit = 50 * 5 * $distance / $loop->num();
        $currentSegment = 0;

        while ($loop->nextStep()) {
            if ($loop->geohash() != '7zzzzzzzzzzz') {
                $coordinate = $loop->coordinate();
                $segments[$currentSegment][] = [
                    'lat' => (float)$coordinate->getLatitude(),
                    'lng' => (float)$coordinate->getLongitude()
                ];

                if ($loop->calculatedStepDistance() > $pauseLimit) {
                    $segments[] = [];
                    $currentSegment++;
                }
            }
        }

        return $segments;
    }

    /**
     * @param Account $account
     * @param Sport $sport
     * @param int $year
     */
    protected function listSpecialFiles(Account $account, Sport $sport, $year)
    {
        $races = $this->RaceresultRepository->findBySportAndYear($account, $sport, $year);

        if (!empty($races)) {
            $special_filenames = [];

            foreach ($races as $race) {
                $special_filenames[] = $this->generateJsonFilename($race['time']);
            }

            $filesystem = new Filesystem();
            $filesystem->dumpFile($this->getPathToJsonFiles().'/special.params', json_encode($special_filenames));
        }
    }

    public function deleteGeneratedFiles()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->getPathToJsonFiles());
    }
}
