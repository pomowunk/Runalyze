<?php

namespace App\Controller\Internal;

use App\Controller\AbstractPluginsAwareController;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Repository\PluginRepository;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Selection\SportSelectionFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @Route("/_internal/plugin")
 */
class PluginController extends AbstractPluginsAwareController
{
    protected TokenStorageInterface $tokenStorage;
    protected ParameterBagInterface $parameterBag;
    protected PluginRepository $pluginRepository;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        ParameterBagInterface $parameterBag,
        PluginRepository $pluginRepository,
        SportRepository $sportRepository,
        ConfigurationManager $configurationManager,
        SportSelectionFactory $sportSelectionFactory,
        TrainingRepository $trainingRepository,
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->parameterBag = $parameterBag;
        $this->pluginRepository = $pluginRepository;

        parent::__construct($sportRepository, $configurationManager, $sportSelectionFactory, $trainingRepository);
    }

    /**
     * @Route("/toggle/{id}", name="internal-plugin-toggle")
     * @ParamConverter("plugin", class="Runalyze\Bundle\CoreBundle\Entity\Plugin")
     * @Security("is_granted('ROLE_USER')")
     */
    public function togglePanelAction(Plugin $plugin, Account $account): Response
    {
        if ($plugin->getAccount()->getId() != $account->getId()) {
            return $this->createAccessDeniedException();
        }

        // Only as long as PluginFactory's cache is in use
        $Frontend = new \Frontend($this->parameterBag, true, $this->tokenStorage);
        \PluginFactory::clearCache();

        $this->pluginRepository->toggleHidden($plugin);

        return new JsonResponse();
    }

    /**
     * @Route("/move/{id}/up", name="internal-plugin-move-up")
     * @ParamConverter("plugin", class="Runalyze\Bundle\CoreBundle\Entity\Plugin")
     * @Security("is_granted('ROLE_USER')")
     */
    public function movePanelUpAction(Plugin $plugin, Account $account): Response
    {
        if ($plugin->getAccount()->getId() != $account->getId()) {
            return $this->createAccessDeniedException();
        }

        // Only as long as PluginFactory's cache is in use
        $Frontend = new \Frontend($this->parameterBag, true, $this->tokenStorage);
        \PluginFactory::clearCache();

        $this->pluginRepository->movePanelUp($plugin);

        return new JsonResponse();
    }

    /**
     * @Route("/move/{id}/down", name="internal-plugin-move-down")
     * @ParamConverter("plugin", class="Runalyze\Bundle\CoreBundle\Entity\Plugin")
     * @Security("is_granted('ROLE_USER')")
     */
    public function movePanelDownAction(Plugin $plugin, Account $account): Response
    {
        if ($plugin->getAccount()->getId() != $account->getId()) {
            return $this->createAccessDeniedException();
        }

        // Only as long as PluginFactory's cache is in use
        $Frontend = new \Frontend($this->parameterBag, true, $this->tokenStorage);
        \PluginFactory::clearCache();

        $this->pluginRepository->movePanelDown($plugin);

        return new JsonResponse();
    }

    /**
     * @Route("/all-panels", name="internal-plugin-all-panels")
     * @Security("is_granted('ROLE_USER')")
     */
    public function contentPanelsAction(Request $request, Account $account): Response
    {
        $Frontend = new \Frontend($this->parameterBag, false, $this->tokenStorage);

        return $this->getResponseForAllEnabledPanels($request, $account);
    }
}
