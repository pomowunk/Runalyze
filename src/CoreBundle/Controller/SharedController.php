<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContext;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\AccountRepository;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Activity\ActivityContextFactory;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\PrivacyGuard;
use Runalyze\Export\Share\Facebook;
use Runalyze\View\Activity\Context;
use Runalyze\View\Activity\Feed;
use Runalyze\View\Activity\Linker;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SharedController extends Controller
{
    /** @var AccountRepository */
    private $accountRepository;

    /** @var TrainingRepository */
    private $trainingRepository;

    /** @var ConfigurationManager */
    private $configurationManager;

    public function __construct(
        AccountRepository $accountRepository,
        TrainingRepository $trainingRepository)
    {
        $this->accountRepository = $accountRepository;
        $this->trainingRepository = $trainingRepository;
    }

    /**
     * @Route("/shared/{activityHash}&{foo}&{bar}&{baz}", requirements={"activityHash": "[a-zA-Z0-9]+"})
     * @Route("/shared/{activityHash}&{foo}&{bar}", requirements={"activityHash": "[a-zA-Z0-9]+"})
     * @Route("/shared/{activityHash}&{foo}", requirements={"activityHash": "[a-zA-Z0-9]+"})
     * @Route("/shared/{activityHash}", requirements={"activityHash": "[a-zA-Z0-9]+"}, name="shared-activity")
     */
    public function sharedTrainingAction($activityHash, Request $request, ActivityContextFactory $activityContextFactory, PrivacyGuard $privacyGuard)
    {
        /** @var null|Training $activity */
        $activity = $this->trainingRepository->find((int)base_convert((string)$activityHash, 35, 10));

        if (null === $activity || !$activity->isPublic()) {
            return $this->render('shared/invalid_activity.html.twig');
        }

        $_GET['user'] = $activity->getAccount()->getUsername();
        $account = $this->accountRepository->findByUsername($activity->getAccount()->getUsername());
        $publicList = $this->configurationManager->getList($account)->getPrivacy()->isListPublic();

        $Frontend = new \FrontendShared(true);
        $activityContext = $activityContextFactory->getContext($activity);
        $activityContextLegacy = new Context($activity->getId(), $activity->getAccount()->getId());

        $hasRoute = $activityContext->canShowMap() && $privacyGuard->isMapVisible($activity, $activityContext->getRaceResult());

        if ('iframe' == $request->query->get('mode')) {
            return $this->render('shared/widget/iframe/base.html.twig', [
                'username' => $activity->getAccount()->getUsername(),
                'hasPublicList' => $publicList,
                'context' => $activityContext,
                'route' => $hasRoute ? new \Runalyze\View\Leaflet\Activity(
                    'route-'.$activity->getId(),
                    $activityContextLegacy->route(),
                    $activityContextLegacy->trackdata()
                ) : false
            ]);
        }

        return $this->render('shared/activity/base.html.twig', [
            'username' => $activity->getAccount()->getUsername(),
            'activity' => $activity,
            'hasPublicList' => $publicList,
            'activityUrl' => (new Linker($activityContextLegacy->activity()))->publicUrl(),
            'activityHasRoute' => $hasRoute,
            'metaTitle' => (new Facebook($activityContextLegacy))->metaTitle(),
            'view' => new \TrainingView($activityContextLegacy)
        ]);
    }

    /**
     * @Route("/shared/{username}/")
     */
    public function oldSharedUserAction($username)
    {
        return $this->redirect($this->generateUrl('shared-athlete', array('username' => $username)), 301);
    }

    /**
     * @Route("/athlete/{username}", name="shared-athlete")
     */
    public function sharedUserAction($username, Request $request) {
        /** @var null|Account $account */
        $account = $this->accountRepository->findByUsername($username);
        $privacy = $this->configurationManager->getList($account)->getPrivacy();

        if (null === $account || !$privacy->isListPublic()) {
            return $this->render('shared/invalid_athlete.html.twig');
        }

        $_GET['user'] = $username;

        $Frontend = new \FrontendSharedList();

        if (isset($_GET['type'])) {
            return $this->render('shared/athlete/base_plot_sum_data.html.twig', [
                'username' => $username,
                'plot' => $this->getPlotSumData()
            ]);
        }

        if ($privacy->isListWithStatistics()) {
            $accountStatistics = $this->trainingRepository->getAccountStatistics($account);
            $legacyStatistics = new \FrontendSharedStatistics();
        } else {
            $accountStatistics = null;
            $legacyStatistics = null;
        }


        return $this->render('shared/athlete/base.html.twig', [
            'account' => $account,
            'accountStatistics' => $accountStatistics,
            'legacyStatistics' => $legacyStatistics,
            'dataBrowser' => new \DataBrowserShared()
        ]);
    }

    /**
     * @Route("/athlete/{username}/feed", name="shared-athlete-feed")
     */
    public function publicUserFeedAction($username, Feed $feed) {
        /** @var null|Account $account */
        $account = $this->accountRepository->findByUsername($username);
        $privacy = $this->configurationManager->getList($account)->getPrivacy();

        $feed->setFeedTitle('RUNALYZE athlete '.$username)
                ->setFeedUrl($this->generateUrl('shared-athlete-feed', ['username' => $username], UrlGeneratorInterface::ABSOLUTE_URL))
                ->setSiteUrl($this->generateUrl('shared-athlete', ['username' => $username], UrlGeneratorInterface::ABSOLUTE_URL))
                ->setFeedAuthor($username);

        if (null === $account || !$privacy->isListPublic()) {
            return new Response(
                $feed->buildFeed(),
                Response::HTTP_OK,
                ['content-type' => 'text/xml']
            );
        }

        $feed->setActivities($this->trainingRepository->getLatestActivities($account, 20, !$privacy->isListShowingAllActivities()));

        return new Response(
            $feed->buildFeed(),
            Response::HTTP_OK,
            ['content-type' => 'text/xml']
        );
    }

    /**
     * @return \PlotSumData
     */
    protected function getPlotSumData()
    {
        $Request = Request::createFromGlobals();

        if (is_null($Request->query->get('y'))) {
            $_GET['y'] = \PlotSumData::LAST_12_MONTHS;
        }

        return 'week' == $Request->query->get('type', 'month') ? new \PlotWeekSumData() : new \PlotMonthSumData();
    }
}
