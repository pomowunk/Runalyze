<?php

namespace App\Controller\My;

use App\Entity\Account;
use App\Entity\User;
use App\Repository\UserRepository;
use Runalyze\Bundle\CoreBundle\Form\BodyValuesType;
use Runalyze\Bundle\CoreBundle\Services\AutomaticReloadFlagSetter;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Metrics\HeartRate\Unit\BeatsPerMinute;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/my/body-values")
 * @Security("is_granted('ROLE_USER')")
 */
class BodyValuesController extends AbstractController
{
    /**
     * @Route("/add", name="body-values-add")
     */
    public function addAction(
        Request $request,
        Account $account,
        UserRepository $userRepository,
        AutomaticReloadFlagSetter $automaticReloadFlagSetter,
    ): Response
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
     * @ParamConverter("user", class="App\Entity\User")
     */
    public function editAction(
        Request $request,
        User $user,
        Account $account,
        UserRepository $userRepository,
        AutomaticReloadFlagSetter $automaticReloadFlagSetter,
    ): Response
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
     * @ParamConverter("user", class="App\Entity\User")
     */
    public function deleteAction(
        User $user,
        Account $account,
        UserRepository $userRepository,
        AutomaticReloadFlagSetter $automaticReloadFlagSetter,
    ): Response
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
     */
    public function tableAction(
        Account $account,
        UserRepository $userRepository,
        ConfigurationManager $configurationManager
    ): Response
    {
        return $this->render('my/body-values/table.html.twig', [
            'values' => $userRepository->findAllFor($account),
            'unitWeight' => $configurationManager->getList()->getUnitSystem()->getWeightUnit(),
            'unitHeartRate' => new BeatsPerMinute()
        ]);
    }
}
