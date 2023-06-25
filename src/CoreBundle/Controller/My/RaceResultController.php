<?php

namespace Runalyze\Bundle\CoreBundle\Controller\My;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Repository\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Metrics\LegacyUnitConverter;
use Runalyze\Sports\Running\VO2max\Estimation\DanielsGilbertFormula;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Runalyze\Bundle\CoreBundle\Form\RaceResultType;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Services\Activity\AgeGradeLookup;
use Runalyze\Bundle\CoreBundle\Services\LegacyCache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/my/raceresult")
 * @Security("is_granted('ROLE_USER')")
 */
class RaceResultController extends AbstractController
{
    /**
     * @Route("/{activityId}", name="raceresult-form", requirements={"activityId" = "\d+"})
     * @param int $activityId
     * @param Account $account
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function raceresultFormAction(
        $activityId,
        Account $account,
        Request $request,
        TrainingRepository $trainingRepository,
        RaceresultRepository $raceresultRepository,
        LegacyCache $legacyCache)
    {
        $activity = $trainingRepository->findForAccount($activityId, $account->getId());

        if (null === $activity) {
            throw $this->createAccessDeniedException();
        }

        $raceResult = $raceresultRepository->findForAccount($activityId, $account->getId());
        $isNew = false;

        if (null === $raceResult) {
            $isNew = true;
            $raceResult = new Raceresult();
            $raceResult->setAccount($account);
            $raceResult->fillFromActivity($activity);
        }

        $form = $this->createForm(RaceResultType::class, $raceResult, array(
            'action' => $this->generateUrl('raceresult-form', array('activityId' => $activityId))
        ));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $raceresultRepository->save($raceResult);
            $legacyCache->clearRaceResultCache($raceResult);
        }

        return $this->render('my/raceresult/form.html.twig', [
            'form' => $form->createView(),
            'isNew' => $isNew,
            'activity' => $activity,
            'unitConverter' => new LegacyUnitConverter()
        ]);
    }

    /**
     * @Route("/{activityId}/delete", name="raceresult-delete", requirements={"activityId" = "\d+"})
     */
    public function raceresultDeleteAction(
        $activityId,
        Request $request,
        Account $account,
        RaceresultRepository $raceresultRepository,
        LegacyCache $legacyCache)
    {
        $raceResult = $raceresultRepository->findForAccount($activityId, $account->getId());

        if ($raceResult) {
            $raceresultRepository->delete($raceResult);
            $legacyCache->clearRaceResultCache($raceResult);
        } else {
            throw $this->createAccessDeniedException();
        }

        return $this->render('my/raceresult/deleted.html.twig');
    }

    /**
     * @Route("/performance-chart", name="race-results-performance-chart")
     * @Security("is_granted('ROLE_USER')")
     */
    public function performanceChartAction(
        Account $account,
        AgeGradeLookup $ageGradeLookup,
        SportRepository $sportRepository)
    {
        $danielsGilbertFormula = new DanielsGilbertFormula();
        $ageGradeLookup = $ageGradeLookup->getLookup() ?: $ageGradeLookup->getDefaultLookup();
        $distances = [0.06, 0.1, 0.2, 0.4, 0.8, 1.0, 1.5, 3.0, 5.0, 10.0, 21.1, 42.2, 50.0];
        $distanceTicks = [0.06, 0.1, 0.2, 0.4, 0.8, 1.5, 3.0, 5.0, 10.0, 21.1, 42.2];
        $ageStandardTimes = array_map(function($kilometer) use ($ageGradeLookup) {
            return $ageGradeLookup->getAgeStandard($kilometer);
        }, $distances);
        $ageStandardVO2max = array_map(function($kilometer, $seconds) use ($danielsGilbertFormula) {
            return $danielsGilbertFormula->estimateFromRaceResult($kilometer, $seconds);
        }, $distances, $ageStandardTimes);

        return $this->render('my/raceresult/performance_chart.html.twig', [
            'runningSportId' => $sportRepository->findRunningFor($account)->getId(),
            'mainDistances' => $distances,
            'mainDistanceTicks' => $distanceTicks,
            'ageStandardTimes' => $ageStandardTimes,
            'ageStandardVO2max' => $ageStandardVO2max
        ]);
    }
}
