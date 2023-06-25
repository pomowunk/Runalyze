<?php

namespace App\Controller\Internal;

use App\Entity\Account;
use App\Repository\SportRepository;
use Runalyze\Util\LocalTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/_internal/panel")
 */
class PanelController extends AbstractController
{

    /**
     * @Route("/sport", name="internal-sport-panel")
     * @Security("is_granted('ROLE_USER')")
     */
    public function sportStatAction(Account $account, SportRepository $sportRepository): Response
    {
        $today = (new LocalTime())->setTime(0, 0, 0);

        return new JsonResponse( [
            'weekStatistics' => $sportRepository->getSportStatisticsSince($today->weekstart(), $account, true),
            'monthStatistics' => $sportRepository->getSportStatisticsSince($today->setDate((int)$today->format('Y'), (int)$today->format('m'), 1)->getTimestamp(), $account, true),
            'yearStatistics' => $sportRepository->getSportStatisticsSince($today->setDate((int)$today->format('Y'), 1, 1)->getTimestamp(), $account, true),
            'totalStatistics' => $sportRepository->getSportStatisticsSince(null, $account, true)
        ]);
    }
}
