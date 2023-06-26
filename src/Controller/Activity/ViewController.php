<?php

namespace App\Controller\Activity;

use App\Entity\Account;
use App\Entity\Common\AccountRelatedEntityInterface;
use App\Entity\Training;
use App\Repository\TrackdataRepository;
use App\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityDecorator;
use Runalyze\Bundle\CoreBundle\Component\Activity\Tool\BestSubSegmentsStatistics;
use Runalyze\Bundle\CoreBundle\Component\Activity\Tool\TimeSeriesStatistics;
use Runalyze\Bundle\CoreBundle\Component\Activity\VO2maxCalculationDetailsDecorator;
use Runalyze\Bundle\CoreBundle\Services\Activity\ActivityContextFactory;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\LegacyCache;
use Runalyze\Metrics\Velocity\Unit\PaceEnum;
use Runalyze\Service\ElevationCorrection\StepwiseElevationProfileFixer;
use Runalyze\View\Activity\Context;
use Runalyze\View\Window\Laps\Window;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ViewController extends AbstractController
{
    protected TrainingRepository $trainingRepository;
    protected TrackdataRepository $trackdataRepository;
    protected TokenStorageInterface $tokenStorage;
    protected ParameterBagInterface $parameterBag;

    public function __construct(
        TrainingRepository $trainingRepository,
        TrackdataRepository $trackdataRepository,
        TokenStorageInterface $tokenStorage,
        ParameterBagInterface $parameterBag,
    ) {
        $this->trainingRepository = $trainingRepository;
        $this->trackdataRepository = $trackdataRepository;
        $this->tokenStorage = $tokenStorage;
        $this->parameterBag = $parameterBag;
    }

    protected function checkThatEntityBelongsToActivity(AccountRelatedEntityInterface $entity, Account $account)
    {
        if ($entity->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException();
        }
    }

    /**
     * @Route("/activity/{id}", name="ActivityShow", requirements={"id" = "\d+"})
     * @Security("is_granted('ROLE_USER')")
     * @ParamConverter("activity", class="App\Entity\Training")
     */
    public function displayAction(
        Request $request,
        Training $activity,
        Account $account,
        LegacyCache $legacyCache,
    ): Response
    {
        $this->checkThatEntityBelongsToActivity($activity, $account);

        switch ($request->query->get('action')) {
            case 'changePrivacy':
                $this->trainingRepository->save($activity->togglePrivacy());
                $legacyCache->clearActivityCache($activity);
                break;
            case 'delete':
                $this->trainingRepository->remove($activity);

                return $this->render('activity/activity_has_been_removed.html.twig');
        }

        if (!$request->query->get('silent')) {
            $frontend = new \Frontend($this->parameterBag, true, $this->tokenStorage);
            $context = new Context($activity->getId(), $account->getId());

            $view = new \TrainingView($context);
            $view->display();
        }

        return new Response();
    }

    /**
     * @Route("/activity/{id}/vo2max-info")
     * @ParamConverter("activity", class="App\Entity\Training")
     * @Security("is_granted('ROLE_USER')")
     */
    public function vo2maxInfoAction(
        Training $activity,
        Account $account,
        ConfigurationManager $configManager,
        ActivityContextFactory $activityContextFactory,
    ): Response
    {
        $this->checkThatEntityBelongsToActivity($activity, $account);

        $configList = $configManager->getList();
        $activityContext = $activityContextFactory->getContext($activity);

        return $this->render('activity/vo2max_info.html.twig', [
            'context' => $activityContext,
            'details' => new VO2maxCalculationDetailsDecorator($activityContext, $configList)
        ]);
    }

    /**
     * @Route("/activity/{id}/splits-info", requirements={"id" = "\d+"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function splitsInfoAction(int $id, Account $account): Response
    {
        
        $Frontend = new \Frontend($this->parameterBag, false, $this->tokenStorage);

        $context = new Context($id, $account->getId());

        if (!$context->hasTrackdata()) {
            return $this->render('activity/tool/not_possible.html.twig');
        }

        $window = new Window($context);
        $window->display();

        return new Response();
    }

    /**
     * @Route("/activity/{id}/elevation-info", requirements={"id" = "\d+"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function elevationInfoAction(int $id, Request $request, Account $account): Response
    {
        if ($request->get('use-calculated-value') == 'true') {
            /** @var Training $activity */
            $activity = $this->trainingRepository->find($id);

            $this->checkThatEntityBelongsToActivity($activity, $account);

            $activity->getAdapter()->useElevationFromRoute();

            $this->trainingRepository->save($activity);
        }

        $Frontend = new \Frontend($this->parameterBag, false, $this->tokenStorage);

        $context = new Context($id, $account->getId());

        if (!$context->hasRoute()) {
            return $this->render('activity/tool/not_possible.html.twig');
        }

        $elevationInfo = new \ElevationInfo($context);
        $elevationInfo->display();

        return new Response();
    }

    /**
     * @Route("/activity/{id}/time-series-info", requirements={"id" = "\d+"}, name="activity-tool-time-series-info")
     * @Security("is_granted('ROLE_USER')")
     */
    public function timeSeriesInfoAction(int $id, Account $account): Response
    {
        // TODO: fix whatever this is

        $trackdata = $this->trackdataRepository->findByActivity($id, $account);

        if (null === $trackdata) {
            return $this->render('activity/tool/not_possible.html.twig');
        }

        $trackdataModel = $trackdata->getLegacyModel();

        $paceUnit = PaceEnum::get(
            $this->trainingRepository->getSpeedUnitFor($id, $account->getId())
        );

        $statistics = new TimeSeriesStatistics($trackdataModel);
        $statistics->calculateStatistics([0.1, 0.9]);

        return $this->render('activity/tool/time_series_statistics.html.twig', [
            'statistics' => $statistics,
            'paceAverage' => $trackdataModel->totalPace(),
            'paceUnit' => $paceUnit
        ]);
    }

    /**
     * @Route("/activity/{id}/sub-segments-info", requirements={"id" = "\d+"}, name="activity-tool-sub-segments-info")
     * @ParamConverter("activity", class="App\Entity\Training")
     * @Security("is_granted('ROLE_USER')")
     */
    public function subSegmentInfoAction(
        int $id,
        Training $activity,
        Account $account,
        ConfigurationManager $confManager,
    ): Response
    {
        // TODO: fix whatever this is

        $trackdata = $this->trackdataRepository->findByActivity($id, $account);

        if (!$activity->hasTrackdata()) {
            return $this->render('activity/tool/not_possible.html.twig');
        }

        $trackdataModel = $activity->getTrackdata()->getLegacyModel();

        $paceUnit = PaceEnum::get(
            $this->trainingRepository->getSpeedUnitFor($id, $account->getId())
        );

        $statistics = new BestSubSegmentsStatistics($trackdataModel);
        $statistics->setDistancesToAnalyze([0.2, 1.0, 1.609, 3.0, 5.0, 10.0, 16.09, 21.1, 42.2, 50, 100]);
        $statistics->setTimesToAnalyze([30, 60, 120, 300, 600, 720, 1800, 3600, 7200]);
        $statistics->findSegments();

        $mapRoute = false;
        $segments = [];

        if ($activity->hasRoute() && $activity->getRoute()->hasGeohashes()) {
            $Frontend = new \Frontend($this->parameterBag, false, $this->tokenStorage);
            $routeModel = $activity->getRoute()->getLegacyModel();
            $mapRoute = new \Runalyze\View\Leaflet\Activity(
                'route-'.$activity->getId(),
                $routeModel,
                $trackdataModel
            );

            $precision = (int)$confManager->getList()->getActivityView()->get('GMAP_PATH_PRECISION');
            $distanceSegments = $statistics->getDistanceSegmentPaths($routeModel, $precision);
            $timeSegments = $statistics->getTimeSegmentPaths($routeModel, $precision);
            $segments = [
                'time' => $timeSegments,
                'distance' => $distanceSegments
            ];
        }

        return $this->render('activity/tool/best_sub_segments.html.twig', [
            'account' => $account,
            'activityId' => $activity->getId(),
            'statistics' => $statistics,
            'distanceArray' => $trackdataModel->distance(),
            'paceUnit' => $paceUnit,
            'segments' => $segments,
            'map' => $mapRoute,
        ]);
    }

    /**
     * @Route("/activity/{id}/climb-score", requirements={"id" = "\d+"}, name="activity-tool-climb-score")
     * @ParamConverter("activity", class="App\Entity\Training")
     */
    public function climbScoreAction(
        Training $activity,
        Account $account = null,
        ActivityContextFactory $activityContextFactory,
    ): Response
    {
        $activityContext = $activityContextFactory->getContext($activity);

        if (!$activity->isPublic() && $account === null) {
            throw $this->createNotFoundException('No activity found.');
        }

        if (!$activityContext->hasTrackdata() || !$activityContext->hasRoute()) {
            return $this->render('activity/tool/not_possible.html.twig');
        }

        if (
            $activity->hasRoute() && null !== $activity->getRoute()->getElevationsCorrected() &&
            $activity->hasTrackdata() && null !== $activity->getTrackdata()->getDistance()
        ) {
            $numDistance = count($activity->getTrackdata()->getDistance());
            $numElevations = count($activity->getRoute()->getElevationsCorrected());

            if ($numElevations > $numDistance) {
                $activity->getRoute()->setElevationsCorrected(array_slice($activity->getRoute()->getElevationsCorrected(), 0, $numDistance));
            }
        }

        if (null !== $activity->getRoute()->getElevationsCorrected() && null !== $activity->getTrackdata()->getDistance()) {
            $activity->getRoute()->setElevationsCorrected((new StepwiseElevationProfileFixer(
                5, StepwiseElevationProfileFixer::METHOD_VARIABLE_GROUP_SIZE
            ))->fixStepwiseElevations(
                $activity->getRoute()->getElevationsCorrected(),
                $activity->getTrackdata()->getDistance()
            ));
        }

        return $this->render('activity/tool/climb_score.html.twig', [
            'context' => $activityContext,
            'decorator' => new ActivityDecorator($activityContext),
            'paceUnit' => $activity->getSport()->getSpeedUnit()
        ]);
    }
}
