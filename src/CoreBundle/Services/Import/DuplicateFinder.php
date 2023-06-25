<?php

namespace Runalyze\Bundle\CoreBundle\Services\Import;

use App\Entity\Training;
use App\Repository\TrainingRepository;

class DuplicateFinder
{
    /** @var TrainingRepository */
    protected $TrainingRepository;

    public function __construct(TrainingRepository $repository)
    {
        $this->TrainingRepository = $repository;
    }

    /**
     * @param Training $activity
     * @return bool
     */
    public function isPossibleDuplicate(Training $activity)
    {
        $activity->getAdapter()->setActivityIdIfEmpty();

        return $this->TrainingRepository->isPossibleDuplicate($activity);
    }
}
