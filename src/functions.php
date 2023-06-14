<?php
/**
 * Additional helper functions
 *
 * This file is included by composer's autoload mechanism.
 * Requiring global functions is considered bad practice
 * but this will stay until all old code smells are removed.
 */

if (!function_exists('__')) {
    /**
     * Returns the translation for a textstring
     * @param string $text
     * @return string
     */
    function __($text)
    {
        return gettext($text);
    }
}

if (!function_exists('_e')) {
    /**
     * Echo the translation for a textstring
     * @param string $text
     */
    function _e($text)
    {
        echo gettext($text);
    }
}

if (!function_exists('_n')) {
    /**
     * Return singular/plural translation for a textstring
     * @param string $msg1
     * @param string $msg2
     * @param int $n
     * @return string
     */
    function _n($msg1, $msg2, $n)
    {
        return ngettext($msg1, $msg2, $n);
    }
}
