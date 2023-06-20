<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Activity;

use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContext;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityPreview;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Entity\Route as EntityRoute;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Form\ActivityType;
use Runalyze\Bundle\CoreBundle\Form\MultiImporterType;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\AutomaticReloadFlagSetter;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityCache;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityContextAdapterFactory;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityDataContainerFilter;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityDataContainerToActivityContextConverter;
use Runalyze\Bundle\CoreBundle\Services\Import\DuplicateFinder;
use Runalyze\Bundle\CoreBundle\Services\Import\FileImporter;
use Runalyze\Bundle\CoreBundle\Services\Import\FileImportResultCollection;
use Runalyze\Util\LocalTime;
use Runalyze\Util\ServerParams;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

class CreateController extends AbstractController
{
    /** @var ActivityDataContainerToActivityContextConverter */
    protected $activityConverter;

    /** @var ActivityCache */
    protected $activityCache;

    /** @var ActivityContextAdapterFactory */
    protected $activityContextAdapterFactory;

    /** @var AutomaticReloadFlagSetter */
    protected $automaticReloadFlagSetter;

    /** @var ConfigurationManager */
    protected $configurationManager;

    /** @var DuplicateFinder */
    protected $duplicateFinder;

    /** @var SportRepository */
    protected $sportRepository;

    /** @var TrainingRepository */
    protected $trainingRepository;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var string */
    protected $activityImportDirectory;

    /** @var string */
    protected $garminApiKey;

    public function __construct(
        ActivityDataContainerToActivityContextConverter $converter,
        ActivityCache $cache,
        ActivityContextAdapterFactory $contextAdapterFactory,
        AutomaticReloadFlagSetter $automaticReloadFlagSetter,
        ConfigurationManager $configurationManager,
        DuplicateFinder $duplicateFinder,
        SportRepository $sportRepository,
        TrainingRepository $trainingRepository,
        TranslatorInterface $translator,
        string $activityImportDirectory,
        string $garminApiKey)
    {
        $this->activityConverter = $converter;
        $this->activityContextAdapterFactory = $contextAdapterFactory;
        $this->activityCache = $cache;
        $this->automaticReloadFlagSetter = $automaticReloadFlagSetter;
        $this->configurationManager = $configurationManager;
        $this->duplicateFinder = $duplicateFinder;
        $this->sportRepository = $sportRepository;
        $this->trainingRepository = $trainingRepository;
        $this->translator = $translator;
        $this->activityImportDirectory = $activityImportDirectory;
        $this->garminApiKey = $garminApiKey;
    }

    /**
     * @Route("/activity/add", name="activity-add")
     * @Security("has_role('ROLE_USER')")
     */
    public function createAction()
    {
        $defaultUploadMode = $this->configurationManager->getList()->getActivityForm()->get('TRAINING_CREATE_MODE');

        if ('garmin' == $defaultUploadMode) {
            return $this->forward('CoreBundle:Activity\Create:communicator');
        } elseif ('form' == $defaultUploadMode) {
            return $this->forward('CoreBundle:Activity\Create:new');
        }

        return $this->getUploadFormResponse();
    }

    /**
     * @Route("/activity/communicator", name="activity-communicator")
     * @Security("has_role('ROLE_USER')")
     */
    public function communicatorAction()
    {
        return $this->render('activity/import_garmin_communicator.html.twig');
    }

    /**
     * @Route("/activity/communicator/iframe", name="activity-communicator-iframe")
     * @Security("has_role('ROLE_USER')")
     */
    public function communicatorIFrameAction()
    {
        return $this->render('import/garmin_communicator.html.twig', [
            'garminAPIKey' => $this->garminApiKey,
        ]);
    }

    /**
     * @Route("/activity/upload", name="activity-upload")
     * @Security("has_role('ROLE_USER')")
     */
    public function uploadAction(Request $request, Account $account, FileImporter $fileImporter, ActivityDataContainerFilter $filter)
    {
        $importResult = null;

        if ($request->query->has('file')) {
            $importResult = $fileImporter->importSingleFile($this->activityImportDirectory.$request->query->get('file'));
        } elseif ($request->query->has('files')) {
            $importResult = $fileImporter->importFiles(
                array_map(function ($file) {
                    return $this->activityImportDirectory.$file;
                }, explode(';', $request->query->get('files')))
            );
        }

        if (null !== $importResult) {
            return $this->getResponseForImportResults($importResult, $account, $request, $filter);
        }

        return $this->getUploadFormResponse();
    }

    /**
     * @param FileImportResultCollection $results
     * @param Account $account
     * @param Request $request
     * @return Response
     */
    protected function getResponseForImportResults(FileImportResultCollection $results, Account $account, Request $request, ActivityDataContainerFilter $filter)
    {
        $results->completeAndFilterResults($filter);

        foreach ($results as $result) {
            if ($result->isFailed()) {
                $this->addFlash('error', sprintf('%s: %s', pathinfo($result->getOriginalFileName(), PATHINFO_BASENAME), $result->getException()->getMessage()));
            }
        }

        $numActivities = $results->getTotalNumberOfActivities();

        if (1 == $numActivities) {
            return $this->getResponseForNewSingleActivity(
                $this->activityConverter->getActivityFor($results[0]->getContainer()[0], $account),
                $request
            );
        } elseif (1 < $numActivities) {
            return $this->getResponseForMultipleNewActivities($results, $request, $account);
        }

        return $this->getUploadFormResponse();
    }

    /**
     * @return Response
     */
    protected function getUploadFormResponse()
    {
        $serverParams = new ServerParams();
        $maxFileSize = min($serverParams->getPostMaxSizeInBytes(), $serverParams->getUploadMaxFilesize());

        return $this->render('activity/import_upload.html.twig', [
            'maxFileSize' => $maxFileSize < PHP_INT_MAX ? $maxFileSize : false
        ]);
    }

    protected function getResponseForMultipleNewActivities(FileImportResultCollection $results, Request $request, Account $account)
    {
        $activityHashes = [];
        $errors = [];
        $previews = [];

        foreach ($results as $result) {
            if ($result->isFailed()) {
                $errors[] = sprintf('%s: %s', $result->getOriginalFileName(), $result->getException()->getMessage());
            } else {
                foreach ($result->getContainer() as $container) {
                    $activity = $this->activityConverter->getActivityFor($container, $account);
                    $previews[] = new ActivityPreview($activity, $this->duplicateFinder->isPossibleDuplicate($activity));
                    $activityHashes[] = $this->activityCache->save($activity);
                }
            }
        }

        $form = $this->createForm(MultiImporterType::class, $activityHashes, [
            'action' => $this->generateUrl('activity-multi-importer')
        ]);
        $form->handleRequest($request);

        return $this->render('activity/multi_importer.html.twig', [
            'form' => $form->createView(),
            'errors' => $errors,
            'previews' => $previews
        ]);
    }

    /**
     * @Route("/activity/multi-import", name="activity-multi-importer")
     * @Security("has_role('ROLE_USER')")
     */
    public function multiImporterAction(Request $request, Account $account)
    {
        $form = $this->createForm(MultiImporterType::class, [], [
            'action' => $this->generateUrl('activity-multi-importer')
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $hashes = $form->get('activity')->getData();

            if (!is_array($hashes)) {
                return $this->redirectToRoute('activity-upload');
            }

            $defaultLocation = $this->configurationManager->getList()->getActivityForm()->getDefaultLocationForWeatherForecast();
            $activityIds = [];

            foreach ($hashes as $hash) {
                $activity = $this->activityCache->get($hash, null, true);
                $activity->setAccount($account);
                $activity->getAdapter()->setAccountToRelatedEntities();

                $context = new ActivityContext($activity, null, null, $activity->getRoute());
                $this->activityContextAdapterFactory->getAdapterFor($context)->guessWeatherConditions($defaultLocation);

                $activityIds[] = $this->trainingRepository->save($activity, true);
            }

            $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_ALL);

            if ($form->get('show_multi_editor')->getData()) {
                return $this->redirectToRoute('multi-editor', ['ids' => implode(',', $activityIds)]);
            }

            $this->addFlash('success', $this->translator->trans('The activities have been successfully imported.'));

            return $this->render('util/close_overlay.html.twig');
        }

        return $this->redirectToRoute('activity-upload');
    }

    /**
     * @Route("/activity/new", name="activity-new")
     * @Security("has_role('ROLE_USER')")
     */
    public function newAction(Request $request, Account $account)
    {
        if ($request->query->has('date')) {
            $time = LocalTime::fromString($request->query->get('date'))->getTimestamp();
        } else {
            $time = null;
        }

        return $this->getResponseForNewSingleActivity(
            $this->getDefaultNewActivity($account, $time),
            $request,
            false
        );
    }

    protected function getResponseForNewSingleActivity(Training $activity, Request $request = null, $setCache = true)
    {
        $form = $this->createForm(ActivityType::class, $activity, [
            'action' => $this->generateUrl('activity-new')
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleSubmitOfNewActivityForm($activity, $form);

            $this->addFlash('success', $this->translator->trans('The activity has been successfully created.'));
            $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_ALL);

            return $this->render('util/close_overlay.html.twig');
        } elseif (!$form->isSubmitted() && $setCache) {
            $form->get('temporaryHash')->setData(
                $this->activityCache->save($activity)
            );
        }

        return $this->render('activity/form.html.twig', [
            'form' => $form->createView(),
            'isNew' => true,
            'isDuplicate' => $this->duplicateFinder->isPossibleDuplicate($activity),
            'isPowerLocked' => null !== $activity->isPowerCalculated()
        ]);
    }

    protected function handleSubmitOfNewActivityForm(Training $newActivity, Form $form)
    {
        $activity = $this->activityCache->get($form->get('temporaryHash')->getData(), $newActivity, true);

        if ('' != $activity->getRouteName()) {
            if (!$activity->hasRoute()) {
                $activity->setRoute((new EntityRoute())->setAccount($activity->getAccount()));
            }

            $activity->getRoute()->setName($activity->getRouteName());

            if (0 != (int)$activity->getElevation() && 0 == $activity->getRoute()->getElevation()) {
                $activity->getRoute()->setElevation($activity->getElevation());
            }
        }

        if ($form->get('is_race')->getData()) {
            $raceResult = (new Raceresult())->fillFromActivity($activity);
            $activity->setRaceresult($raceResult);
        }

        $this->trainingRepository->save($activity);
    }

    /**
     * @param Account $account
     * @param int|null $time
     * @return Training
     */
    protected function getDefaultNewActivity(Account $account, $time = null)
    {
        $activity = new Training();
        $activity->setAccount($account);
        $activity->setTime($time ?: LocalTime::now());
        $activity->setSport($this->getMainSport($account));
        $activity->setPublic(!$activity->getSport()->getDefaultPrivacy());

        if (null !== $activity->getSport()) {
            $activity->setType($activity->getSport()->getDefaultType());
        }

        return $activity;
    }

    /**
     * @param Account $account
     * @return null|\Runalyze\Bundle\CoreBundle\Entity\Sport
     */
    protected function getMainSport(Account $account)
    {
        $mainSportId = $this->configurationManager->getList()->getGeneral()->getMainSport();
        /** @var SportRepository */
        $sport = $this->sportRepository->findThisOrAny($mainSportId, $account);

        if (null === $sport || $account->getId() != $sport->getAccount()->getId()) {
            return null;
        }

        return $sport;
    }
}
