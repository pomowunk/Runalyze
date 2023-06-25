<?php

namespace App\Controller\Activity;

use App\Entity\Account;
use App\Entity\Common\AccountRelatedEntityInterface;
use App\Entity\Raceresult;
use App\Entity\Training;
use App\Repository\RaceresultRepository;
use App\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityDecorator;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityPreview;
use Runalyze\Bundle\CoreBundle\Form\ActivityType;
use Runalyze\Bundle\CoreBundle\Services\Activity\ActivityContextFactory;
use Runalyze\Bundle\CoreBundle\Services\Activity\DataSeriesRemover;
use Runalyze\Bundle\CoreBundle\Services\AutomaticReloadFlagSetter;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Import\ElevationCorrection;
use Runalyze\Bundle\CoreBundle\Services\LegacyCache;
use Runalyze\Service\ElevationCorrection\Strategy\Geonames;
use Runalyze\Service\ElevationCorrection\Strategy\GeoTiff;
use Runalyze\Service\ElevationCorrection\Strategy\GoogleMaps;
use Runalyze\Service\ElevationCorrection\Strategy\StrategyInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

class EditController extends AbstractController
{
    protected AutomaticReloadFlagSetter $automaticReloadFlagSetter;
    protected ConfigurationManager $configurationManager;
    protected LegacyCache $legacyCache;
    protected TrainingRepository $trainingRepository;
    protected TranslatorInterface $translator;

    public function __construct(AutomaticReloadFlagSetter $automaticReloadFlagSetter, ConfigurationManager $configurationManager, LegacyCache $legacyCache, TrainingRepository $trainingRepository, TranslatorInterface $translator)
    {
        $this->automaticReloadFlagSetter = $automaticReloadFlagSetter;
        $this->configurationManager = $configurationManager;
        $this->legacyCache = $legacyCache;
        $this->trainingRepository = $trainingRepository;
        $this->translator = $translator;
    }

    protected function checkThatEntityBelongsToActivity(AccountRelatedEntityInterface $entity, Account $account): void
    {
        if ($entity->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException();
        }
    }

    /**
     * @Route("/activity/{id}/edit", name="activity-edit", requirements={"id" = "\d+"})
     * @Security("is_granted('ROLE_USER')")
     * @ParamConverter("activity", class="App\Entity\Training")
     */
    public function activityEditAction(Request $request, Training $activity, Account $account, DataSeriesRemover $dataSeriesRemover, RaceresultRepository $raceresultRepository, ActivityContextFactory $activityContextFactory): Response
    {
        $this->checkThatEntityBelongsToActivity($activity, $account);

        $form = $this->createForm(ActivityType::class, $activity, [
            'action' => $this->generateUrl('activity-edit', ['id' => $activity->getId()])
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->has('data_series_remover')) {
                $dataSeriesRemover->handleRequest($form->get('data_series_remover')->getData(), $activity);
            }

            if ($form->get('is_race')->getData() && !$activity->hasRaceresult()) {
                $raceResult = (new Raceresult())->fillFromActivity($activity);
                $activity->setRaceresult($raceResult);
            } elseif (!$form->get('is_race')->getData() && $activity->hasRaceresult()) {
                $raceresultRepository->delete($activity->getRaceresult());
                $activity->setRaceresult(null);
            }

            $this->trainingRepository->save($activity);
            $this->legacyCache->clearActivityCache($activity);

            $this->addFlash('success', $this->translator->trans('Changes have been saved.'));
            $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_ALL);

            $nextId = $form->get('next-multi-editor')->getData();

            if (is_numeric($nextId)) {
                return $this->redirectToRoute('activity-edit', ['id' => (int)$nextId, 'multi' => '1']);
            }
        }

        $context = $activityContextFactory->getContext($activity);

        return $this->render('activity/form.html.twig', [
            'form' => $form->createView(),
            'isNew' => false,
            'isMulti' => (bool)$request->get('multi', false),
            'decorator' => new ActivityDecorator($context),
            'activity_id' => $activity->getId(),
            'prev_activity_id' => $this->trainingRepository->getIdOfPreviousActivity($activity),
            'next_activity_id' => $this->trainingRepository->getIdOfNextActivity($activity),
            'showElevationCorrectionLink' => $activity->hasRoute() && $activity->getRoute()->hasGeohashes() && !$activity->getRoute()->hasCorrectedElevations(),
            'isPowerLocked' => null !== $activity->isPowerCalculated()
        ]);
    }

    /**
     * @Route("/activity/multi-editor", name="multi-editor")
     * @Security("is_granted('ROLE_USER')")
     */
    public function multiEditorAction(Request $request, Account $account): Response
    {
        $ids = array_filter(explode(',', $request->get('ids', '')));

        return $this->getResponseForMultiEditor($ids, $account);
    }

    protected function getResponseForMultiEditor(array $activityIds, Account $account): Response
    {
        $previews = array_map(function (Training $activity) {
            return new ActivityPreview($activity);
        }, $this->trainingRepository->getPartialEntitiesForPreview($activityIds, $account, 20));

        return $this->render('activity/multi_editor_navigation.html.twig', [
            'previews' => $previews
        ]);
    }

   /**
    * @Route("/activity/{id}/delete", name="activity-delete", requirements={"id" = "\d+"})
    * @Security("is_granted('ROLE_USER')")
    * @ParamConverter("activity", class="App\Entity\Training")
    */
   public function deleteAction(Training $activity, Account $account): Response
   {
       $activityId = $activity->getId();

       $this->checkThatEntityBelongsToActivity($activity, $account);

       $this->trainingRepository->remove($activity);

        return $this->render('activity/activity_has_been_removed.html.twig', [
            'multiEditorId' => (int)$activityId
        ]);
   }

    /**
     * @Route("/activity/{id}/elevation-correction", name="activity-elevation-correction", requirements={"id" = "\d+"})
     * @Security("is_granted('ROLE_USER')")
     * @ParamConverter("activity", class="App\Entity\Training")
     */
    public function elevationCorrectionAction(Request $request, Training $activity, Account $account, ElevationCorrection $elevationCorrection, GeoTiff $geotiff, Geonames $geonames, GoogleMaps $googleMaps): Response
    {
        $this->checkThatEntityBelongsToActivity($activity, $account);

        $success = false;
        if ($activity->hasRoute()) {
            $routeAdapter = $activity->getRoute()->getAdapter();
            $strategy = null;

            if ('none' == $request->query->get('strategy')) {
                $routeAdapter->removeElevation();
                $this->addFlash('notice', $this->translator->trans('Corrected elevation data has been removed.'));
                $success = true;
            } else {
                $strategy = $this->getElevationCorrectionStrategyFromRequest($request->query->get('strategy'), $geotiff, $geonames, $googleMaps);

                if ($routeAdapter->correctElevation($elevationCorrection, $strategy)) {
                    $this->addFlash('success', $this->translator->trans('Elevation data has been corrected.'));
                    $success = true;
                }
            }
        }

        if ($success) {
            $this->adjustAndSaveRouteAndActivityForElevationCorrection($activity);
        } else {
            $this->addFlash('error', $this->translator->trans('Elevation data could not be retrieved.'));
        }

        return $this->render('util/flashmessages_only.html.twig', [
            'reloadActivityOverlay' => $success,
            'activityId' => $activity->getId()
        ]);
    }

    protected function adjustAndSaveRouteAndActivityForElevationCorrection(Training $activity): void
    {
        $configuration = $this->configurationManager->getList($activity->getAccount())->getActivityView();

        $activity->getRoute()->getAdapter()->calculateElevation(
            $configuration->getElevationCalculationMethod(),
            $configuration->getElevationCalculationThreshold()
        );

        $activityAdapter = $activity->getAdapter();
        $activityAdapter->useElevationFromRoute();
        $activityAdapter->calculateClimbScore();

        $this->trainingRepository->save($activity);

        $this->legacyCache->clearActivityCache($activity);
        $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_TRAINING_AND_DATA_BROWSER);
    }

    protected function getElevationCorrectionStrategyFromRequest(string $string, GeoTiff $geoTiff, Geonames $geoNames, GoogleMaps $googleMaps): ?StrategyInterface
    {
        if ('GeoTIFF' == $string) {
            return $geoTiff;
        } elseif ('Geonames' == $string) {
            return $geoNames;
        } elseif ('GoogleMaps' == $string) {
            return $googleMaps;
        }

        return null;
    }
}
