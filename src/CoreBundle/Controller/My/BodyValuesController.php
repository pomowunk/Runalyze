<?php

namespace Runalyze\Bundle\CoreBundle\Controller\My;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\User;
use Runalyze\Bundle\CoreBundle\Repository\UserRepository;
use Runalyze\Bundle\CoreBundle\Form\BodyValuesType;
use Runalyze\Bundle\CoreBundle\Services\AutomaticReloadFlagSetter;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Metrics\HeartRate\Unit\BeatsPerMinute;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/my/body-values")
 * @Security("is_granted('ROLE_USER')")
 */
class BodyValuesController extends AbstractController
{
    /**
     * @Route("/add", name="body-values-add")
     * @param Request $request
     * @param Account $account
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addAction(
        Request $request,
        Account $account,
        UserRepository $userRepository,
        AutomaticReloadFlagSetter $automaticReloadFlagSetter)
    {
        $oldUser = $userRepository->getLatestEntryFor($account);
        $user = $oldUser ? $oldUser->cloneObjectForForm() : (new User())->setAccount($account)->setCurrentTimestamp();

        $form = $this->createForm(BodyValuesType::class, $user,[
            'action' => $this->generateUrl('body-values-add')
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository->save($user, $account);
            $automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_ALL);
            return $this->redirectToRoute('body-values-table');
        }

        return $this->render('my/body-values/form.html.twig', [
            'form' => $form->createView(),
            'isNew' => true
        ]);
    }

    /**
     * @Route("/{id}/edit", name="body-values-edit")
     * @ParamConverter("user", class="CoreBundle:User")
     * @param Request $request
     * @param User $user
     * @param Account $account
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(
        Request $request,
        User $user,
        Account $account,
        UserRepository $userRepository,
        AutomaticReloadFlagSetter $automaticReloadFlagSetter)
    {
        if ($user->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(BodyValuesType::class, $user, [
            'action' => $this->generateUrl('body-values-edit', ['id' => $user->getId()])
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository->save($user, $account);
            $automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_ALL);
            return $this->redirectToRoute('body-values-table');
        }

        return $this->render('my/body-values/form.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/{id}/delete", name="body-values-delete")
     * @ParamConverter("user", class="CoreBundle:User")
     * @param User $user
     * @param Account $account
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(
        User $user,
        Account $account,
        UserRepository $userRepository,
        AutomaticReloadFlagSetter $automaticReloadFlagSetter)
    {
        if ($user->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException('No user entry found.');
        }

        $userRepository->remove($user);
        $automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_ALL);

        return $this->redirectToRoute('body-values-table');
    }

    /**
     * @Route("/table", name="body-values-table")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function tableAction(
        Account $account,
        UserRepository $userRepository,
        ConfigurationManager $configurationManager)
    {
        return $this->render('my/body-values/table.html.twig', [
            'values' => $userRepository->findAllFor($account),
            'unitWeight' => $configurationManager->getList()->getUnitSystem()->getWeightUnit(),
            'unitHeartRate' => new BeatsPerMinute()
        ]);
    }
}
