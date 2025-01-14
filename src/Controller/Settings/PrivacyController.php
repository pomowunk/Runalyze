<?php

namespace App\Controller\Settings;

use App\Entity\Account;
use Runalyze\Bundle\CoreBundle\Form\Settings\PrivacyData;
use Runalyze\Bundle\CoreBundle\Form\Settings\PrivacyType;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationUpdater;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class PrivacyController extends AbstractController
{
    /**
     * @Route("/settings/privacy", name="settings-privacy")
     * @Security("is_granted('ROLE_USER')")
     */
    public function settingsAccountAction(
        Request $request,
        Account $account,
        ConfigurationManager $configurationManager,
        ConfigurationUpdater $configurationUpdater,
        TranslatorInterface $translator,
    ): Response
    {
        $privacyConfig = $configurationManager->getList()->getPrivacy();

        $privacy = new PrivacyData();
        $privacy->setDataFrom($privacyConfig);

        $form = $this->createForm(PrivacyType::class, $privacy, array(
            'action' => $this->generateUrl('settings-privacy')
        ));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $configurationUpdater->updatePrivacyDetails($account, $privacy->getDataForConfiguration());

            $this->addFlash('success', $translator->trans('Your changes have been saved!'));
        }

        return $this->render('settings/privacy.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
