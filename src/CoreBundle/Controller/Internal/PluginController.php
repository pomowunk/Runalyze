<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Internal;

use Runalyze\Bundle\CoreBundle\Controller\AbstractPluginsAwareController;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Repository\PluginRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @Route("/_internal/plugin")
 */
class PluginController extends AbstractPluginsAwareController
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var PluginRepository */
    protected $pluginRepository;

    public function __construct(TokenStorageInterface $tokenStorage, PluginRepository $pluginRepository)
    {
        $this->tokenStorage = $tokenStorage;
        $this->pluginRepository = $pluginRepository;
    }

    /**
     * @Route("/toggle/{id}", name="internal-plugin-toggle")
     * @ParamConverter("plugin", class="CoreBundle:Plugin")
     * @Security("has_role('ROLE_USER')")
     */
    public function togglePanelAction(Plugin $plugin, Account $account)
    {
        if ($plugin->getAccount()->getId() != $account->getId()) {
            return $this->createAccessDeniedException();
        }

        // Only as long as PluginFactory's cache is in use
        $Frontend = new \Frontend(true, $this->tokenStorage);
        \PluginFactory::clearCache();

        $this->pluginRepository->toggleHidden($plugin);

        return new JsonResponse();
    }

    /**
     * @Route("/move/{id}/up", name="internal-plugin-move-up")
     * @ParamConverter("plugin", class="CoreBundle:Plugin")
     * @Security("has_role('ROLE_USER')")
     */
    public function movePanelUpAction(Plugin $plugin, Account $account)
    {
        if ($plugin->getAccount()->getId() != $account->getId()) {
            return $this->createAccessDeniedException();
        }

        // Only as long as PluginFactory's cache is in use
        $Frontend = new \Frontend(true, $this->tokenStorage);
        \PluginFactory::clearCache();

        $this->pluginRepository->movePanelUp($plugin);

        return new JsonResponse();
    }

    /**
     * @Route("/move/{id}/down", name="internal-plugin-move-down")
     * @ParamConverter("plugin", class="CoreBundle:Plugin")
     * @Security("has_role('ROLE_USER')")
     */
    public function movePanelDownAction(Plugin $plugin, Account $account)
    {
        if ($plugin->getAccount()->getId() != $account->getId()) {
            return $this->createAccessDeniedException();
        }

        // Only as long as PluginFactory's cache is in use
        $Frontend = new \Frontend(true, $this->tokenStorage);
        \PluginFactory::clearCache();

        $this->pluginRepository->movePanelDown($plugin);

        return new JsonResponse();
    }

    /**
     * @Route("/all-panels", name="internal-plugin-all-panels")
     * @Security("has_role('ROLE_USER')")
     */
    public function contentPanelsAction(Request $request, Account $account)
    {
        $Frontend = new \Frontend(false, $this->tokenStorage);

        return $this->getResponseForAllEnabledPanels($request, $account);
    }
}
