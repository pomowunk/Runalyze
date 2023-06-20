<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Settings;

use Runalyze\Bundle\CoreBundle\Repository\AccountRepository;
use Runalyze\Bundle\CoreBundle\Entity\Dataset;
use Runalyze\Bundle\CoreBundle\Form\Settings\ChangeMailType;
use Runalyze\Bundle\CoreBundle\Form\Settings\ChangePasswordType;
use Runalyze\Bundle\CoreBundle\Form\Settings\DatasetCollectionType;
use Runalyze\Bundle\CoreBundle\Services\AutomaticReloadFlagSetter;
use Runalyze\Dataset\DefaultConfiguration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\DatasetRepository;
use Runalyze\Bundle\CoreBundle\Repository\EquipmentRepository;
use Runalyze\Bundle\CoreBundle\Repository\TagRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Runalyze\Bundle\CoreBundle\Form\Settings\AccountType;
use Runalyze\Bundle\CoreBundle\Services\AccountMailer;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Configuration;
use Runalyze\Language;
use Runalyze\Dataset as RunalyzeDataset;
use Runalyze\Dataset\Keys;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Translation\TranslatorInterface;

class SettingsController extends AbstractController
{
    /**
     * @Route("/settings/account", name="settings-account")
     * @Security("has_role('ROLE_USER')")
     */
    public function settingsAccountAction(
        Request $request,
        Account $account,
        TokenStorageInterface $tokenStorage,
        TranslatorInterface $translator,
        AutomaticReloadFlagSetter $automaticReloadFlagSetter,
        AccountRepository $accountRepository,
        SessionInterface $session,
        ParameterBagInterface $parameterBag,
    ) {
        $Frontend = new \Frontend($parameterBag, true, $tokenStorage);

        $currentLanguage = $account->getLanguage();
        $form = $this->createForm(AccountType::class, $account, array(
            'action' => $this->generateUrl('settings-account')
        ));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formdata = $request->request->get($form->getName());

            if (isset($formdata['reset_configuration'])) {
                Configuration::resetConfiguration($account->getId());
                $this->addFlash('success', $translator->trans('Default configuration has been restored!'));

                $automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_ALL);
            }

            if (isset($formdata['language'])) {
                $session->set('_locale', $formdata['language']);
                Language::setLanguage($formdata['language']);

                if ($account->getLanguage() != $currentLanguage) {
                    $automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_PAGE);
                }
            }

            $accountRepository->save($account);

            $this->addFlash('success', $translator->trans('Your changes have been saved!'));
        }

        return $this->render('settings/account.html.twig', [
            'form' => $form->createView(),
            'language' => $account->getLanguage()
        ]);
    }

    /**
     * @Route("/settings/password", name="settings-password")
     * @Security("has_role('ROLE_USER')")
     */
    public function settingsPasswordAction(
        Request $request,
        Account $account,
        EncoderFactoryInterface $encoderFactory,
        AccountRepository $accountRepository,
        TranslatorInterface $translator)
    {
        $form = $this->createForm(ChangePasswordType::class, $account, array(
            'action' => $this->generateUrl('settings-password')
        ));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formdata = $request->request->get($form->getName());
            $account->setPlainPassword($formdata['plainPassword']['first']);
            $this->encodePassword($account, $encoderFactory);
            $accountRepository->save($account);

            $this->addFlash('success', $translator->trans('Your new password has been saved!'));
        }

        return $this->render('settings/account-password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/settings/mail", name="settings-mail")
     * @Security("has_role('ROLE_USER')")
     */
    public function settingsMailAction(
        Request $request,
        Account $account,
        AccountRepository $accountRepository,
        TranslatorInterface $translator)
    {
        $form = $this->createForm(ChangeMailType::class, $account, array(
            'action' => $this->generateUrl('settings-mail')
        ));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $accountRepository->save($account);
            $this->addFlash('success', $translator->trans('Your mail address has been changed!'));
        }

        return $this->render('settings/account-mail.html.twig', [
            'form' => $form->createView()
        ]);
    }

    protected function encodePassword(
        Account $account,
        EncoderFactoryInterface $encoderFactory)
    {
        $encoder = $encoderFactory->getEncoder($account);

        $account->setNewSalt();
        $account->setPassword($encoder->encodePassword($account->getPlainPassword(), $account->getSalt()));
    }

    /**
     * @Route("/settings/account/delete", name="settings-account-delete")
     * @Security("has_role('ROLE_USER')")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function windowDeleteAction(
        Account $account,
        AccountRepository $accountRepository,
        AccountMailer $accountMailer)
    {
        $account->setNewDeletionHash();
        $accountRepository->save($account);

        $accountMailer->sendDeleteLinkTo($account);

        return $this->render('settings/account-delete.html.twig');
    }

    /**
     * @Route("/settings/dataset", name="settings-dataset-update", methods={"POST"})
     * @Security("has_role('ROLE_USER')")
     */
    public function datasetPostAction(
        Account $account,
        Request $request,
        DatasetRepository $datasetRepository,
        AutomaticReloadFlagSetter $automaticReloadFlagSetter)
    {
        $em = $this->getDoctrine()->getManager();

        $dataset = $datasetRepository->findAllFor($account);

        $form = $this->createForm(DatasetCollectionType::class, ['datasets' => $dataset]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            foreach ($form->get('datasets')->getData() as $datasetObject) {
                /** @var Dataset $datasetObject */
                $datasetObject->setAccount($account);
                $em->persist($datasetObject);
            }

            $em->flush();
            $automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_DATA_BROWSER);
        }

        return $this->redirectToRoute('settings-dataset');
    }

    /**
     * @Route("/settings/dataset", name="settings-dataset")
     * @Security("has_role('ROLE_USER')")
     */
    public function datasetAction(
        Account $account,
        Request $request,
        TokenStorageInterface $tokenStorage,
        DatasetRepository $datasetRepository,
        ConfigurationManager $configurationManager,
        TagRepository $tagRepository,
        EquipmentRepository $equipmentRepository,
        ParameterBagInterface $parameterBag,
    ) {
        $Frontend = new \Frontend($parameterBag, true, $tokenStorage);

        $dataset = $datasetRepository->findAllFor($account);
        $missingKeyObjects = array_flip(RunalyzeDataset\Keys::getEnum());
        $numberOfExistingKeys = count($dataset);

        foreach ($dataset as $datasetObject) {
            unset($missingKeyObjects[$datasetObject->getKeyId()]);
        }

        foreach ($missingKeyObjects as $key => $missingDataset) {
            $dataset[] = (new Dataset())->setActive(false)->setKeyId($key)->setAccount($account);
        }

        $form = $this->createForm(DatasetCollectionType::class, ['datasets' => $dataset], array(
            'action' => $this->generateUrl('settings-dataset'),
        ));
        $form->handleRequest($request);

        return $this->render('settings/dataset.html.twig', [
            'form' => $form->createView(),
            'datasetKeys' => new RunalyzeDataset\Keys(),
            'missingKeys' => $missingKeyObjects,
            'defaultConfiguration' => (new DefaultConfiguration)->data(),
            'numberOfExistingKeys' => $numberOfExistingKeys,
            'context' => new RunalyzeDataset\Context($this->getExampleTraining($account, $configurationManager, $tagRepository, $equipmentRepository), $account->getId())
        ]);
    }

    /**
     * @return array
     */
    protected function getExampleTraining(
        Account $account,
        ConfigurationManager $configurationManager,
        TagRepository $tagRepository,
        EquipmentRepository $equipmentRepository)
    {
        $configuration = $configurationManager->getList();

        return array(
            'id' => 0,
            'sportid' => $configuration->getGeneral()->getRunningSport(),
            'typeid' => __('race'),
            'time' => time(),
            'created' => time(),
            'edited' => time(),
            'is_public' => 1,
            'is_track' => 1,
            'distance' => 10,
            's' => 51 * 60 + 27,
            'elevation' => 57,
            'climb_score' => 1.3,
            'percentage_hilly' => 0.68,
            'kcal' => 691,
            'pulse_avg' => 186,
            'pulse_max' => 193,
            'vo2max_with_elevation' => $configuration->getData()->getCurrentVO2maxShape() + 1,
            'vo2max' => $configuration->getData()->getCurrentVO2maxShape() + 2,
            'use_vo2max' => 0,
            'fit_vo2max_estimate' => round($configuration->getData()->getCurrentVO2maxShape()),
            'fit_recovery_time' => 800,
            'fit_hrv_analysis' => 800,
            'fit_training_effect' => 3.1,
            'fit_performance_condition' => 101,
            'fit_performance_condition_end' => 96,
            'jd_intensity' => 27,
            'rpe' => 13,
            'trimp' => 121,
            'cadence' => 90,
            'stride_length' => 108,
            'groundcontact' => 220,
            'vertical_oscillation' => 76,
            'power' => 520,
            'temperature' => 17,
            'wind_speed' => 27,
            'wind_deg' => 219,
            'pressure' => 1025,
            'humidity' => 63,
            'weatherid' => 5,
            'splits' => '5|26:51-5|24:36',
            'comment' => str_replace(' ', '&nbsp;', __('Test activity')),
            'partner' => 'Peter',
            'notes' => str_replace(' ', '&nbsp;', __('Great run!')),
            'accountid' => $account->getId(),
            'creator' => '',
            'creator_details' => '',
            'activity_id' => '',
            'elevation_corrected' => 1,
            'swolf' => 29,
            'total_strokes' => 1250,
            'vertical_ratio' => 79,
            'groundcontact_balance' => 4980,
            'avg_impact_gs_left' => 10.3,
            'avg_impact_gs_right' => 10.7,
            'avg_braking_gs_left' => 9.8,
            'avg_braking_gs_right' => 9.6,
            'avg_footstrike_type_left' => 12,
            'avg_footstrike_type_right' => 13,
            'avg_pronation_excursion_left' => -9.4,
            'avg_pronation_excursion_right' => -15.0,
            Keys\Tags::CONCAT_TAGIDS_KEY => $this->exampleTagID($account, $tagRepository),
            Keys\CompleteEquipment::CONCAT_EQUIPMENT_KEY => $this->exampleEquipmentIDs($account, $equipmentRepository)
        );
    }

    /**
     * @return string
     */
    protected function exampleTagID(
        Account $account,
        TagRepository $tagRepository)
    {
        $tag = $tagRepository->findBy(['account' => $account->getId()], null, 1);

        if ($tag) {
            return (string)$tag[0]->getId();
        }

        return '';
    }

    /**
     * @return string
     */
    protected function exampleEquipmentIDs(
        Account $account,
        EquipmentRepository $equipmentRepository)
    {
        $ids = [];
        $equipment = $equipmentRepository->findBy(['account' => $account->getId()], null, 2);

        if (is_array($equipment)) {
            foreach ($equipment as $element) {
                $ids[] = $element->getId();
            }
        }

        return implode(',', $ids);
    }
}
