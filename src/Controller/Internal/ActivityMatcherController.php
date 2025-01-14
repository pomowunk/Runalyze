<?php

namespace App\Controller\Internal;

use App\Entity\Training;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Import\DuplicateFinder;
use Runalyze\Util\LocalTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ActivityMatcherController extends AbstractController
{
    /**
     * @Route("/_internal/activity/matcher", name="internal-activity-matcher")
     * @Security("is_granted('ROLE_USER')")
     */
    public function ajaxActivityMatcher(DuplicateFinder $duplicateFinder, ConfigurationManager $configurationManager): Response
    {
        $ids = [];
        $matches = [];
        $input = explode('&', urldecode(file_get_contents('php://input')));

        foreach ($input as $line) {
            if (substr($line,0,12) == 'externalIds=') {
                $ids[] = substr($line,12);
            }
        }

        $ignoredActivityIds = array_map(function($v) {
            try {
                return (int)floor($this->parserStrtotime($v) / 60.0) * 60.0;
            } catch (\Exception $e) {
                return 0;
            }
        }, $configurationManager->getList()->getActivityForm()->getIgnoredActivityIds());

        foreach ($ids as $id) {
            try {
                $possibleDuplicate = $duplicateFinder->isPossibleDuplicate(
                    (new Training())->setTime($this->parserStrtotime($id))
                );
            } catch (\Exception $e) {
                $possibleDuplicate = false;
            }

            $matches[$id] = ['match' => $possibleDuplicate || in_array($id, $ignoredActivityIds)];
        }

        return new JsonResponse([
            'matches' => $matches
        ]);
    }

    /**
     * Adjusted strtotime
     * Timestamps are given in UTC but local timezone offset has to be considered!
     */
    private function parserStrtotime(string $string): int
    {
        if (substr($string, -1) == 'Z') {
            return LocalTime::fromServerTime((int)strtotime(substr($string, 0, -1).' UTC'))->getTimestamp();
        }

        return LocalTime::fromString($string)->getTimestamp();
    }
}
