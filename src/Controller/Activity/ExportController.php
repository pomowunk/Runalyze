<?php

namespace App\Controller\Activity;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Export\File;
use Runalyze\Export\File\AbstractFileExporter;
use Runalyze\Export\Share;
use Runalyze\Export\Share\AbstractSnippetSharer;
use Runalyze\View\Activity\Context;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ExportController extends AbstractController
{
    protected TokenStorageInterface $tokenStorageInterface;
    protected ParameterBagInterface $parameterBag;

    public function __construct(
        TokenStorageInterface $tokenStorageInterface,
        ParameterBagInterface $parameterBag,
    ) {
        $this->tokenStorageInterface = $tokenStorageInterface;
        $this->parameterBag = $parameterBag;
    }

    /**
     * @Route("/activity/{id}/export/social/{typeid}", requirements={"id" = "\d+"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function exporterExportAction(
        int $id,
        int $typeid,
        Account $account,
    ): Response
    {
        $Frontend = new \Frontend($this->parameterBag, true, $this->tokenStorageInterface);

        if (Share\Types::isValidValue((int)$typeid)) {
            $context = new Context((int)$id, $account->getId());
            $exporter = Share\Types::get((int)$typeid, $context);

            if ($exporter instanceof AbstractSnippetSharer) {
                $exporter->display();
            }
        }

        return new Response();
    }

    /**
     * @Route("/activity/{id}/export/file/{typeid}", requirements={"id" = "\d+"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function fileExportAction(
        int $id,
        int $typeid,
        Account $account,
    ): Response
    {
        $Frontend = new \Frontend($this->parameterBag, true, $this->tokenStorageInterface);

        if (File\Types::isValidValue((int)$typeid)) {
            $context = new Context((int)$id, $account->getId());
            $exporter = File\Types::get((int)$typeid, $context);

            if ($exporter instanceof AbstractFileExporter) {
                $exporter->downloadFile();
                exit;
            }
        }

        return new Response();
    }
}
