<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use Runalyze\Activity\Distance;
use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Form\FeedbackType;
use Runalyze\Bundle\CoreBundle\Form\RegistrationType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\AccountRepository;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\AccountMailer;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Selection\SportSelectionFactory;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Class DefaultController
 * @package Runalyze\Bundle\CoreBundle\Controller
 */
class DefaultController extends AbstractPluginsAwareController
{
    /** @var string */
    protected $projectDirectory;

    /** @var bool */
    protected $userCanRegister;

    /** @var bool */
    protected $userDisableAccountActivation;

    /** @var string */
    protected $feedbackMail;

    public function __construct(
        SportRepository $sportRepository,
        ConfigurationManager $configurationManager,
        SportSelectionFactory $sportSelectionFactory,
        TrainingRepository $trainingRepository,
        string $projectDirectory,
        bool $userCanRegister,
        bool $userDisableAccountActivation,
        string $feedbackMail)
    {
        $this->projectDirectory = $projectDirectory;
        $this->userCanRegister = $userCanRegister;
        $this->userDisableAccountActivation = $userDisableAccountActivation;
        $this->feedbackMail = $feedbackMail;

        parent::__construct($sportRepository, $configurationManager, $sportSelectionFactory, $trainingRepository);
    }

    /**
     * @Route("/dashboard", name="dashboard")
     * @Security("has_role('ROLE_USER')")
     */
    public function dashboardAction(Request $request, Account $account, TokenStorageInterface $tokenStorage)
    {
        $Frontend = new \Frontend(true, $tokenStorage);

        $panelsContent = $this->getResponseForAllEnabledPanels($request, $account)->getContent();

        include $this->projectDirectory.'/dashboard.php';

        return $this->render('legacy_end.html.twig');
    }

    /**
     * @Route("/", name="base_url")
     */
    public function indexAction(
        Request $request,
        AuthorizationCheckerInterface $authorizationChecker)
    {
        if ($authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') || $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        return $this->forward('CoreBundle:Default:register', $request->attributes->all());
    }

    /**
     * @Route("/{_locale}/register", name="register")
     */
    public function registerAction(
        Request $request,
        EncoderFactoryInterface $encoderFactory,
        AccountMailer $accountMailer,
        AccountRepository $accountRepository,
        TrainingRepository $trainingRepository)
    {
        if (!$this->userCanRegister) {
            return $this->render('register/disabled.html.twig');
        }

        $account = new Account();
        $form = $this->createForm(RegistrationType::class, $account);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $registration = new Registration($this->getDoctrine()->getManager(), $account);
            $formdata = $request->request->get($form->getName());

            $registration->setLocale($request->getLocale());
            $registration->setTimezoneByName($formdata['textTimezone']);

            if (!$this->userDisableAccountActivation) {
                $registration->requireAccountActivation();
            }

            $registration->setPassword($account->getPlainPassword(), $encoderFactory);
            $account = $registration->registerAccount();

            if ($this->userDisableAccountActivation) {
                return $this->render('account/activate/success.html.twig');
            }

            $accountMailer->sendActivationLinkTo($account);

            return $this->render('register/mail_delivered.html.twig');
        }

        return $this->render('register/form.html.twig', [
            'form' => $form->createView(),
            'num' => $this->collectStatistics($accountRepository, $trainingRepository)
        ]);
    }

    /**
     * @Route("/{_locale}/login", name="login")
     */
    public function loginAction(
        Request $request,
        AuthorizationCheckerInterface $authorizationChecker,
        AuthenticationUtils $authenticationUtils,
        AccountRepository $accountRepository,
        TrainingRepository $trainingRepository)
    {
    	if ($authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
    	    return $this->redirect($this->generateUrl('dashboard'));
    	}

        if ($request->isXmlHttpRequest()) {
            return $this->render('login/ajax_not_logged_in.html.twig');
        }

    	$error = $authenticationUtils->getLastAuthenticationError();

        return $this->render('login/form.html.twig', [
   	        'error' => $error,
            'num' => $this->collectStatistics($accountRepository, $trainingRepository)
        ]);
    }

    /**
     * @Route("/{_locale}/login_check", name="login_check")
     */
    public function loginCheckAction()
    {
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logoutAction()
    {
        return $this->redirectToRoute('login');
    }

    /**
     * @return array ['user' => (int)..., 'distance' => (string)...]
     */
    protected function collectStatistics(
        AccountRepository $accountRepository,
        TrainingRepository $trainingRepository)
    {
        $numUser =  $accountRepository->getAmountOfActivatedUsers();
        $numDistance =  $trainingRepository->getAmountOfLoggedKilometers();

        return ['user' => (int)$numUser, 'distance' => Distance::format($numDistance)];
    }

    /**
     * @Route("/plugin/{plugin}/{file}")
     */
    public function pluginAction(
        $plugin,
        $file,
        TokenStorageInterface $tokenStorage)
    {
        $Frontend = new \Frontend(false, $tokenStorage);
        
        include '../plugin/'.$plugin.'/'.$file;

        return $this->render('legacy_end.html.twig');
    }

    /**
     * @Route("/index.php")
     */
    public function indexPhpAction(
        Request $request,
        Account $account,
        TokenStorageInterface $tokenStorage)
    {
        if ($request->isXmlHttpRequest()) {
            $Frontend = new \Frontend(true, $tokenStorage);

            $panelsContent = $this->getResponseForAllEnabledPanels($request, $account)->getContent();

            include $this->projectDirectory.'/dashboard.php';

            return new Response();
        }

        return $this->redirectToRoute('base_url');
    }

    /**
     * @Route("/login.php")
     */
    public function loginPhpAction()
    {
        return $this->redirectToRoute('dashboard');
    }

    /**
     * @Route("/unsubscribe/{mail}/{hash}", name="unsubscribe-mail")
     */
    public function unsubscribeMailAction(
        $mail,
        $hash,
        AccountRepository $accountRepository)
    {
        $account = $accountRepository->findOneBy(array('mail' => $mail));

        if (null !== $account && $hash == md5($account->getUsername())) {
            return $this->render('account/unsubscribe_info.html.twig', array('mail' => $mail, 'hash' => $hash));
        }

        return $this->render('account/unsubscribe_failure.html.twig');
    }

    /**
     * @Route("/unsubscribe/{mail}/{hash}/confirm", name="unsubscribe-mail-confirm")
     */
    public function unsubscribeMailConfirmAction(
        $mail,
        $hash,
        AccountRepository $accountRepository)
    {
        $account = $accountRepository->findOneBy(array('mail' => $mail));

        if (null !== $account && $hash == md5($account->getUsername())) {
            $account->setAllowMails(false);
            $accountRepository->save($account);

            return $this->render('account/unsubscribe_success.html.twig');
        }

        return $this->render('account/unsubscribe_failure.html.twig');
    }

    /**
     * @Route("/feedback", name="feedback")
     * @Security("has_role('ROLE_USER')")
     */
    public function feedbackAction(
        Request $request,
        Account $account,
        AccountMailer $accountMailer)
    {
        if (!empty($this->feedbackMail)) {
            $form = $this->createForm(FeedbackType::class, null, [
                'action' => $this->generateUrl('feedback'),
            ]);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $accountMailer->sendCustomFeedbackToSystem($account, $this->feedbackMail, $form->getData()['message']);
                return $this->render('feedback.html.twig', [
                    'form' => $form->createView()
                ]);
            }

            return $this->render('feedback.html.twig', array(
                'form' => $form->createView()
            ));
        } else {
            throw $this->createNotFoundException();
        }
    }
}
