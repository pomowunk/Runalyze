<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Poster\Converter;

use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;

class InkscapeConverter extends AbstractSvgToPngConverter
{
    /**
     * @param string $inkscapePath absolut path to inkscape
     */
    public function __construct($inkscapePath)
    {
        $this->Command = $inkscapePath;
    }

    public function setHeight($height)
    {
        $this->Parameter[] = '-h '.(int)$height;
    }

    public function setWidth($width)
    {
        $this->Parameter[] = '-w '.(int)$width;
    }

    public function callConverter($source, $target)
    {
        if ((new Filesystem())->exists($source)) {
            $builder = new Process($this->Command.' --export-type=png --export-filename='.$target.' '.implode(' ', $this->Parameter).' '.$source);
            $exitCode = $builder->run();
            $this->StdErr = $builder->getErrorOutput();
            return $exitCode;
        }

        return 1;
    }
}
