<?php

namespace App\Controller\My\Tools;

use App\Entity\Account;
use App\Repository\SportRepository;
use App\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\AnovaDataQuery;
use Runalyze\Bundle\CoreBundle\Component\Tool\DatabaseCleanup\JobGeneral;
use Runalyze\Bundle\CoreBundle\Component\Tool\DatabaseCleanup\JobLoop;
use Runalyze\Bundle\CoreBundle\Component\Tool\TrendAnalysis\TrendAnalysisDataQuery;
use Runalyze\Bundle\CoreBundle\Component\Tool\VO2maxAnalysis\VO2maxAnalysis;
use Runalyze\Bundle\CoreBundle\Form\Tools\Anova\AnovaData;
use Runalyze\Bundle\CoreBundle\Form\Tools\Anova\AnovaType;
use Runalyze\Bundle\CoreBundle\Form\Tools\DatabaseCleanupType;
use Runalyze\Bundle\CoreBundle\Form\Tools\PosterType;
use Runalyze\Bundle\CoreBundle\Form\Tools\TrendAnalysis\TrendAnalysisData;
use Runalyze\Bundle\CoreBundle\Form\Tools\TrendAnalysis\TrendAnalysisType;
use Runalyze\Metrics\Common\JavaScriptFormatter;
use Runalyze\Sports\Running\Prognosis\VO2max;
use Runalyze\Sports\Running\VO2max\VO2maxVelocity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\Availability;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\FileHandler;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ToolsController extends AbstractController
{
    protected ConfigurationManager $configurationManager;
    protected SportRepository $sportRepository;
    protected TrainingRepository $trainingRepository;
    protected int $posterStoragePeriod;
    protected string $databasePrefix;

    public function __construct(
        ConfigurationManager $configurationManager,
        SportRepository $sportRepository,
        TrainingRepository $trainingRepository,
        int $posterStoragePeriod,
        string $databasePrefix,
    ) {
        $this->configurationManager = $configurationManager;
        $this->sportRepository = $sportRepository;
        $this->trainingRepository = $trainingRepository;
        $this->posterStoragePeriod = $posterStoragePeriod;
        $this->databasePrefix = $databasePrefix;
    }

    /**
     * @Route("/my/tools/cleanup", name="tools-cleanup")
     * @Security("is_granted('ROLE_USER')")
     */
    public function cleanupAction(
        Request $request,
        Account $account,
        TokenStorageInterface $tokenStorage,
        ParameterBagInterface $parameterBag,
    ): Response
    {
        $defaultData = array();
        $form = $this->createForm(DatabaseCleanupType::class, $defaultData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && null !== $form->getData()['mode']) {
            $Frontend = new \Frontend($parameterBag, true, $tokenStorage);

            if ('general' === $form->getData()['mode']) {
                $job = new JobGeneral($form->getData(), \DB::getInstance(), $account->getId(), $this->databasePrefix);
            } else {
                $job = new JobLoop($form->getData(), \DB::getInstance(), $account->getId(), $this->databasePrefix);
            }

            $job->run();

            return $this->render('tools/database_cleanup/results.html.twig', [
                'messages' => $job->messages()
            ]);
        }

        return $this->render('tools/database_cleanup/form.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/my/tools/tables/vo2max-pace", name="tools-tables-vo2max-pace")
     * @Security("is_granted('ROLE_USER')")
     */
    public function tableVo2maxPaceAction(): Response
    {
        $config = $this->configurationManager->getList();
        $running = $this->sportRepository->find($config->getGeneral()->getRunningSport());

        return $this->render('tools/tables/vo2max_paces.html.twig', [
            'currentVo2max' => $config->getCurrentVO2maxShape(),
            'vo2maxVelocity' => new VO2maxVelocity(),
            'paceUnit' => $config->getUnitSystem()->getPaceUnit($running)
        ]);
    }

    /**
     * @Route("/my/tools/tables/general-pace", name="tools-tables-general-pace")
     * @Security("is_granted('ROLE_USER')")
     */
    public function tableGeneralPaceAction(): Response
    {
        return $this->render('tools/tables/general_paces.html.twig');
    }

    /**
     * @Route("/my/tools/tables/vo2max", name="tools-tables-vo2max")
     * @Route("/my/tools/tables", name="tools-tables")
     * @Security("is_granted('ROLE_USER')")
     */
    public function tableVo2maxRaceResultAction(): Response
    {
        return $this->render('tools/tables/vo2max.html.twig', [
            'currentVo2max' => $this->configurationManager->getList()->getCurrentVO2maxShape(),
            'prognosis' => new VO2max(),
            'distances' => [1.0, 3.0, 5.0, 10.0, 21.1, 42.2, 50],
            'vo2maxValues' => range(30.0, 80.0)
        ]);
    }

    /**
     * @Route("/my/tools/vo2max-analysis", name="tools-vo2max-analysis")
     * @Security("is_granted('ROLE_USER')")
     */
    public function vo2maxAnalysisAction(
        Account $account, 
        TokenStorageInterface $tokenStorage,
        ParameterBagInterface $parameterBag,
    ): Response
    {
        $Frontend = new \Frontend($parameterBag, true, $tokenStorage);

        $configuration = $this->configurationManager->getList();
        $correctionFactor = $configuration->getVO2maxCorrectionFactor();

        $analysisTable = new VO2maxAnalysis($configuration->getVO2max()->getLegacyCategory());
        $races = $analysisTable->getAnalysisForAllRaces(
            $correctionFactor,
            $configuration->getGeneral()->getRunningSport(),
            $account->getId()
        );

        return $this->render('tools/vo2max_analysis.html.twig', [
            'races' => $races,
            'vo2maxFactor' => $correctionFactor
        ]);
    }

    /**
     * @Route("/my/tools/anova", name="tools-anova")
     * @Security("is_granted('ROLE_USER')")
     */
    public function anovaAction(Request $request, Account $account): Response
    {
        $data = AnovaData::getDefault($this->sportRepository->findAllFor($account), []);

        $form = $this->createForm(AnovaType::class, $data, [
            'action' => $this->generateUrl('tools-anova')
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                return new JsonResponse(['status' => 'There was a problem.']);
            }

            $unitSystem = $this->configurationManager->getList($account)->getUnitSystem();
            $query = new AnovaDataQuery($data);
            $query->loadAllGroups($this->getDoctrine()->getManager(), $account);

            return new JsonResponse([
                'tickFormatter' => JavaScriptFormatter::getFormatter($query->getValueUnit($unitSystem)),
                'groups' => $query->getResults(
                    $this->trainingRepository,
                    $account, $unitSystem
                )
            ]);
        }

        return $this->render('tools/anova/base.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/my/tools/trend-analysis", name="tools-trend-analysis")
     * @Security("is_granted('ROLE_USER')")
     */
    public function trendAnalysisAction(Request $request, Account $account): Response
    {
        $data = TrendAnalysisData::getDefault($this->sportRepository->findAllFor($account), []);

        $form = $this->createForm(TrendAnalysisType::class, $data, [
            'action' => $this->generateUrl('tools-trend-analysis')
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                return new JsonResponse(['status' => 'There was a problem.']);
            }

            $unitSystem = $this->configurationManager->getList($account)->getUnitSystem();
            $query = new TrendAnalysisDataQuery($data);

            return new JsonResponse([
                'tickFormatter' => JavaScriptFormatter::getFormatter($query->getValueUnit($unitSystem)),
                'values' => $query->getResults(
                    $this->trainingRepository,
                    $account, $unitSystem
                )
            ]);
        }

        return $this->render('tools/trend-analysis/base.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/my/tools/poster", name="poster")
     * @Security("is_granted('ROLE_USER')")
     * @todo Fix poster generation by migrating to symfony/messenger.
     */
    public function posterAction(
        Request $request,
        Account $account,
        TranslatorInterface $translator,
        FileHandler $fileHandler,
    ): Response
    {
        throw $this->createNotFoundException('Poster generation is temporarily disabled.');
        // $form = $this->createForm(PosterType::class, [
        //     'postertype' => ['heatmap'],
        //     'year' => (int)date('Y') - 1,
        //     'title' => ' '
        // ]);
        // $form->handleRequest($request);

        // if ($form->isSubmitted() && $form->isValid()) {
        //     $formdata = $request->request->get($form->getName());

        //     $numberOfActivities = $this->trainingRepository->getNumberOfActivitiesFor($account, (int)$formdata['year'], (int)$formdata['sport']);
        //     if ($numberOfActivities <= 1) {
        //         $this->addFlash('error', $translator->trans('There are not enough activities to generate a poster. Please change your selection.'));
        //     } else {
        //         $message = new PlainMessage('posterGenerator', array(
        //             'accountid' => $account->getId(),
        //             'year' => $formdata['year'],
        //             'types' => $formdata['postertype'],
        //             'sportid' => $formdata['sport'],
        //             'title' => $formdata['title'],
        //             'size' => $formdata['size'],
        //             'backgroundColor' => $formdata['backgroundColor'],
        //             'trackColor' => $formdata['trackColor'],
        //             'textColor' => $formdata['textColor'],
        //             'raceColor' => $formdata['raceColor'],
        //         ));
        //         $producer->produce($message);

        //         return $this->render('tools/poster_success.html.twig', [
        //             'posterStoragePeriod' => $this->posterStoragePeriod,
        //             'listing' => $fileHandler->getFileList($account)
        //         ]);
        //     }
        // }

        // return $this->render('tools/poster.html.twig', [
        //     'form' => $form->createView(),
        //     'posterStoragePeriod' => $this->posterStoragePeriod,
        //     'listing' => $fileHandler->getFileList($account)
        // ]);
    }

    /**
     * @Route("/my/tools/poster/{name}", name="poster-download", requirements={"name": ".+"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function posterDownloadAction(Account $account, $name, FileHandler $fileHandler): Response
    {
        return $fileHandler->getPosterDownloadResponse($account, $name);
    }

    /**
     * @Route("/my/tools", name="tools")
     * @Security("is_granted('ROLE_USER')")
     */
    public function overviewAction(Availability $availability): Response
    {
        return $this->render('tools/tools_list.html.twig', [
            'posterAvailable' => $availability->isAvailable()
        ]);
    }
}
