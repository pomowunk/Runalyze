<?php

namespace Runalyze\Bundle\CoreBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class UtilityExtension extends AbstractExtension
{
    /**
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getName()
    {
        return 'runalyze.utility_extension';
    }

    /**
     * @return TwigFilter[]
     *
     * @codeCoverageIgnore
     */
    public function getFilters()
    {
        return array(
            new TwigFilter('duration', array($this, 'duration')),
            new TwigFilter('filesize', array($this, 'filesizeAsString')),
            new TwigFilter('md5', array($this, 'md5'))
        );
    }

    /**
     * @param float $seconds
     * @param int $decimals
     * @param string $decimalPoint
     * @return string
     */
    public function duration($seconds, $decimals = 0, $decimalPoint = ',')
    {
        $fraction = (round($seconds) != round($seconds, $decimals) && $decimals > 0) ? $decimalPoint.'u' : '';

        if ($seconds>= 86400) {
            return $this->formatDuration($seconds, 'z\d H:i:s');
        } elseif ($seconds >= 3600) {
            return $this->formatDuration($seconds, 'G:i:s');
        } elseif ($seconds < 60) {
            return '0:'.$this->formatDuration($seconds, 's'.$fraction, $decimals);
        }

        return ltrim($this->formatDuration($seconds, 'i:s'.$fraction, $decimals), '0');
    }

    /**
     * @param float $seconds
     * @param string $format
     * @param int $decimals
     * @return string
     */
    private function formatDuration($seconds, $format, $decimals = 0)
    {
        if (substr($format, -1) == 'u') {
            $time = \DateTime::createFromFormat('!U', (string)floor($seconds), new \DateTimeZone('UTC'));
            $fraction = str_pad((string)round(fmod($seconds, 1) * pow(10, $decimals)), $decimals, '0', STR_PAD_LEFT);

            return $time->format(substr($format, 0, -1)).$fraction;
        }

        $time = \DateTime::createFromFormat('!U', (string)round($seconds), new \DateTimeZone('UTC'));

        return $time->format($format);
    }

    /**
     * @param int $bytes
     * @return string
     */
    public function filesizeAsString($bytes)
    {
        if ($bytes == 0) {
            return '0 B';
        }

        $FS = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

        return number_format($bytes / pow(1024, $I = (int)floor(log($bytes, 1024))), ($I >= 1) ? 2 : 0, '.', '').' '.$FS[$I];
    }

    /**
     * @param $value
     * @return string
     */
    public function md5($value) {
        return md5($value);
    }

}
