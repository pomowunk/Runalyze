<?php
namespace Runalyze\Bundle\CoreBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class HtmlExtension extends AbstractExtension
{
    /**
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getName()
    {
        return 'runalyze.html_extension';
    }

    /**
     * @return TwigFilter[]
     *
     * @codeCoverageIgnore
     */
    public function getFilters()
    {
        return array(
            new TwigFilter('info', array($this, 'info'), array('is_safe' => array('html'))),
            new TwigFilter('error', array($this, 'error'), array('is_safe' => array('html'))),
            new TwigFilter('warning', array($this, 'warning'), array('is_safe' => array('html'))),
            new TwigFilter('okay', array($this, 'okay'), array('is_safe' => array('html'))),
            new TwigFilter('nbsp', array($this, 'nbsp'), array('is_safe' => array('html'))),
            new TwigFilter('nonbsp', array($this, 'nonbsp'), array('is_safe' => array('html'))),
        );
    }

    /**
     * @param string $string
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function info($string)
    {
        return '<p class="info">'.$string.'</p>';
    }

    /**
     * @param string $string
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function error($string)
    {
        return '<p class="error">'.$string.'</p>';
    }

    /**
     * @param string $string
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function warning($string)
    {
        return '<p class="warning">'.$string.'</p>';
    }

    /**
     * @param string $string
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function okay($string)
    {
        return '<p class="okay">'.$string.'</p>';
    }

    /**
     * @param string $string
     * @return string
     */
    public function nbsp($string)
    {
        return str_replace(' ', '&nbsp;', $string);
    }

    /**
     * @param string $string
     * @return string
     */
    public function nonbsp($string)
    {
        return str_replace('&nbsp;', ' ', $string);
    }
}
