<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Activity;

use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityDecorator;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityPreview;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Repository\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

class EditController extends AbstractController
{
    /** @var AutomaticReloadFlagSetter */
    protected $automaticReloadFlagSetter;
    
    /** @var ConfigurationManager */
    protected $configurationManager;
    
    /** @var LegacyCache */
    protected $legacyCache;
    
    /** @var TrainingRepository */
    protected $trainingRepository;
    
    /** @var TranslatorInterface */
    protected $translator;
    
    public function __construct(AutomaticReloadFlagSetter $automaticReloadFlagSetter, ConfigurationManager $configurationManager, LegacyCache $legacyCache, TrainingRepository $trainingRepository, TranslatorInterface $translator)
    {
        $this->automaticReloadFlagSetter = $automaticReloadFlagSetter;
        $this->configurationManager = $configurationManager;
        $this->legacyCache = $legacyCache;
        $this->trainingRepository = $trainingRepository;
        $this->translator = $translator;
    }

    protected function checkThatEntityBelongsToActivity(AccountRelatedEntityInterface $entity, Account $account)
    {
        if ($entity->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException();
        }
    }

    /**
     * @Route("/activity/{id}/edit", name="activity-edit", requirements={"id" = "\d+"})
     * @Security("is_granted('ROLE_USER')")
     * @ParamConverter("activity", class="Runalyze\Bundle\CoreBundle\Entity\Training")
     */
    public function activityEditAction(Request $request, Training $activity, Account $account, DataSeriesRemover $dataSeriesRemover, RaceresultRepository $raceresultRepository, ActivityContextFactory $activityContextFactory)
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
    public function multiEditorAction(Request $request, Account $account)
    {
        $ids = array_filter(explode(',', $request->get('ids', '')));

        return $this->getResponseForMultiEditor($ids, $account);
    }

    /**
     * @param array $activityIds
     * @param Account $account
     * @return Response
     */
    protected function getResponseForMultiEditor(array $activityIds, Account $account)
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
    * @ParamConverter("activity", class="Runalyze\Bundle\CoreBundle\Entity\Training")
    */
   public function deleteAction(Training $activity, Account $account)
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
     * @ParamConverter("activity", class="Runalyze\Bundle\CoreBundle\Entity\Training")
     */
    public function elevationCorrectionAction(Request $request, Training $activity, Account $account, ElevationCorrection $elevationCorrection, GeoTiff $geotiff, Geonames $geonames, GoogleMaps $googleMaps)
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

    protected function adjustAndSaveRouteAndActivityForElevationCorrection(Training $activity)
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

    /**
     * @param $string
     * @return null|\Runalyze\Service\ElevationCorrection\Strategy\StrategyInterface
     */
    protected function getElevationCorrectionStrategyFromRequest($string, GeoTiff $geoTiff, Geonames $geoNames, GoogleMaps $googleMaps)
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
