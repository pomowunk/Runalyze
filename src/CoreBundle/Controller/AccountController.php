<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\AccountRepository;
use Runalyze\Bundle\CoreBundle\Form\RecoverPasswordType;
use Runalyze\Bundle\CoreBundle\Services\AccountMailer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/{_locale}/account")
 */
class AccountController extends Controller
{
    /**
     * @var AccountRepository
     */
    private $accountRepository;

    public function __construct(AccountRepository $accountRepository)
    {
        $this->accountRepository = $accountRepository;
    }

    /**
     * @Route("/delete/{hash}/confirmed", name="account_delete_confirmed", requirements={"hash": "[[:xdigit:]]{32}"})
     */
    public function deleteAccountConfirmedAction($hash, Request $request, TokenStorageInterface $tokenStorage)
    {
        if ($this->accountRepository->deleteByHash($hash)) {
            $tokenStorage->setToken(null);
            $request->getSession()->invalidate();

            return $this->render('account/delete/success.html.twig');
        }

        return $this->render('account/delete/problem.html.twig');
    }

    /**
     * @Route("/delete/{hash}", name="account_delete", requirements={"hash": "[[:xdigit:]]{32}"})
     */
    public function deleteAccountAction($hash)
    {
        /** @var Account|null $account */
        $account = $this->accountRepository->findOneBy(['deletionHash' => $hash]);

        if (null === $account) {
            return $this->render('account/delete/problem.html.twig');
        }

        return $this->render('account/delete/please_confirm.html.twig', [
            'deletionHash' => $hash,
            'username' => $account->getUsername()
        ]);
    }

    /**
     * @Route("/recover/{hash}", name="account_recover_hash", requirements={"hash": "[[:xdigit:]]{32}"})
     */
    public function recoverForHashAction(
        $hash,
        Request $request,
        EncoderFactoryInterface $encoderFactory)
    {
        /** @var Account|null $account */
        $account = $this->accountRepository->findOneBy(array('changepwHash' => $hash));

        if (null === $account) {
            return $this->render('account/recover/hash_invalid.html.twig', ['recoverHash' => $hash]);
        }

        $form = $this->createForm(RecoverPasswordType::class, $account);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $encoder = $encoderFactory->getEncoder($account);

            $account->setNewSalt();
            $account->setPassword($encoder->encodePassword($account->getPlainPassword(), $account->getSalt()));
            $account->removeChangePasswordHash();

            $this->accountRepository->save($account);

            return $this->render('account/recover/success.html.twig');
        }

        return $this->render('account/recover/form_new_password.html.twig', [
            'recoverHash' => $hash,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/recover", name="account_recover")
     */
    public function recoverAction(
        Request $request,
        TranslatorInterface $translator,
        AccountMailer $accountMailer)
    {
        $form = $this->createFormBuilder()
            ->add('username', TextType::class, [
                'required' => false,
                'attr' => [
                    'autofocus' => true
                ]
            ])->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            /** @var Account|null $account */
            $account = $this->accountRepository->loadUserByUsername(
                (string)$request->request->get($form->getName())['username']
            );

            if (null === $account) {
                $form->get('username')->addError(new FormError($translator->trans('The username is not known.')));
            } else {
                $account->setNewChangePasswordHash();
                $this->accountRepository->save($account);

                $accountMailer->sendRecoverPasswordLinkTo($account);

                return $this->render('account/recover/mail_delivered.html.twig');
            }
        }

        return $this->render('account/recover/form_send_mail.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/activate/{hash}", requirements={"hash": "[[:xdigit:]]{32}"})
     * @Route("/activate/{hash}/{username}", name="account_activate", requirements={"hash": "[[:xdigit:]]{32}"})
     */
    public function activateAccountAction($hash, $username = null)
    {
        if ($this->accountRepository->activateByHash($hash)) {
            return $this->render('account/activate/success.html.twig');
        } elseif (null !== $username && null != $this->accountRepository->loadUserByUsername($username)) {
            return $this->render('account/activate/success.html.twig', ['username' => $username, 'alreadyActivated' => true]);
        }

        return $this->render('account/activate/problem.html.twig');
    }
}
