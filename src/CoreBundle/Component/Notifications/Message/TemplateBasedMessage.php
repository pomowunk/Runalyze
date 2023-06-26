<?php

namespace Runalyze\Bundle\CoreBundle\Component\Notifications\Message;

use Runalyze\Profile\Notifications\MessageTypeProfile;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Yaml\Yaml;

class TemplateBasedMessage implements MessageInterface
{
    protected string $templatePath;
    protected ?int $LifetimeInDays = null;
    protected ?array $TemplateContent = null;

    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(string $templatePath, int $lifetimeInDays = null)
    {
        if (!file_exists($templatePath)) {
            throw new \InvalidArgumentException(sprintf('Given template "%s" cannot be found.', $templatePath));
        }

        $this->templatePath = $templatePath;
        $this->LifetimeInDays = $lifetimeInDays;
    }

    public function getMessageType()
    {
        return MessageTypeProfile::TEMPLATE_BASED_MESSAGE;
    }

    public function getData()
    {
        return $this->templatePath;
    }

    public function getLifetime()
    {
        return $this->LifetimeInDays;
    }

    public function getText(TranslatorInterface $translator = null)
    {
        $this->loadTemplateIfNotDoneYet();

        return isset($this->TemplateContent['text']) ? $this->TemplateContent['text'] : '';
    }

    public function hasLink()
    {
        $this->loadTemplateIfNotDoneYet();

        return isset($this->TemplateContent['link']) && !empty($this->TemplateContent['link']);
    }

    public function getLink(RouterInterface $router = null)
    {
        $this->loadTemplateIfNotDoneYet();

        return isset($this->TemplateContent['link']) ? $this->TemplateContent['link'] : '';
    }

    protected function loadTemplateIfNotDoneYet()
    {
        if (null === $this->TemplateContent) {
            $this->TemplateContent = Yaml::parse(file_get_contents($this->templatePath));
        }
    }

    public function isLinkInternal()
    {
        $this->loadTemplateIfNotDoneYet();

        return isset($this->TemplateContent['internal']) || (isset($this->TemplateContent['link']) && 'http' != substr($this->TemplateContent['link'], 0, 4));
    }

    public function getWindowSizeForInternalLink()
    {
        $this->loadTemplateIfNotDoneYet();

        return isset($this->TemplateContent['window_size']) ? $this->TemplateContent['window_size'] : '';
    }
}
