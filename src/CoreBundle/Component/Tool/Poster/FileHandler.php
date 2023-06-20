<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Poster;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Queue\Receiver\PosterReceiver;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class FileHandler
{
    protected string $posterExportDirectory;

    public function __construct(string $posterExportDirectory)
    {
        $this->posterExportDirectory = $posterExportDirectory;
    }

    /**
     * @param Account $account
     * @return array
     */
    public function getFileList(Account $account)
    {
        $finder = new Finder();
        $finder->files()->name($account->getId().'-*')
            ->sort(function (\SplFileInfo $a, \SplFileInfo $b) {
                return ($b->getMTime() - $a->getMTime());
            })
            ->in($this->posterExportDirectory);

        $list = [];

        foreach ($finder as $file) {
            $list[substr($file->getBasename(), strlen((string)$account->getId()) + 1)] = $file->getSize();
        }

        return $list;
    }

    /**
     * @param Account $account
     * @param string $filename
     * @return Response
     *
     * @throws ResourceNotFoundException
     */
    public function getPosterDownloadResponse(Account $account, $filename)
    {
        $fs = new Filesystem();
        $filename = $account->getId().'-'.$filename;
        $path = $this->posterExportDirectory.$filename;

        if ($fs->exists($path)) {
            $response = new Response();
            $response->headers->set('Cache-Control', 'private');
            $response->headers->set('Content-type', 'image/png');
            $response->headers->set('Content-Disposition', 'attachment; filename="'.basename($filename).'";');
            $response->headers->set('Content-length', (string)filesize($path));
            $response->setContent(file_get_contents($path));

            return $response;
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param Account $account
     * @param Sport $sport
     * @param string|int $year
     * @param string $type
     * @param string|int $size
     * @return string
     */
    public function buildFinalFileName(Account $account, Sport $sport, $year, $type, $size)
    {
        return sprintf('%s-%s-%s-%s-%s-%s.%s',
            $account->getId(),
            $this->filesystemFriendlyName($sport->getName()),
            $year,
            $type,
            $size,
            date('Ymd-Hi'),
            'png'
        );
    }

    /**
     * @param string $string
     * @return string
     */
    protected function filesystemFriendlyName($string)
    {
        return preg_replace('~[^a-zA-Z0-9]+~', '', $string);
    }
}
