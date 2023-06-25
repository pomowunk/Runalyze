<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StaticController extends AbstractController
{
    protected string $runalyzeVersion;

    public function __construct(string $runalyzeVersion)
    {
        $this->runalyzeVersion = $runalyzeVersion;
    }

    /**
     * @Route("/dashboard/help", name="help")
     * @Security("is_granted('ROLE_USER')")
     */
    public function dashboardHelpAction(): Response
    {
        return $this->render('static/help.html.twig', [
            'version' => $this->runalyzeVersion
        ]);
    }

    /**
     * @Route("/dashboard/help-calculations", name="help-calculations")
     * @Security("is_granted('ROLE_USER')")
     */
    public function dashboardHelpCalculationsAction(): Response
    {
        return $this->render('static/help_calculations.html.twig');
    }
}
