<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Internal;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Runalyze\Util\LocalTime;

/**
 * @Route("/_internal/panel")
 */
class PanelController extends Controller
{

    /**
     * @Route("/sport", name="internal-sport-panel")
     * @Security("has_role('ROLE_USER')")
     */
    public function sportStatAction(Request $request, Account $account, SportRepository $sportRepository)
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
