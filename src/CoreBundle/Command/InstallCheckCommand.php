<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class InstallCheckCommand extends Command
{
    /** @var string */
    const REQUIRED_PHP_VERSION = '8.0.0';

    /** @var int bit flag for return code */
    const OKAY = 0x00;

    /** @var int bit flag for return code */
    const WARNING = 0x01;

    /** @var int bit flag for return code */
    const ERROR = 0x10;

    protected static $defaultName = 'runalyze:install:check';

    protected int $ReturnCode = 0x00;

    /** @var array[] */
    protected array $Checks = [
        [
            'method' => 'checkPhpVersion',
            'message' => 'Check PHP version'
        ],
        [
            'method' => 'prefixIsNotUsed',
            'message' => 'Check that database prefix is not used',
            'hint' => [
                'There must not exist any tables with the chosen prefix.',
                'Maybe RUNALYZE is already installed.'
            ]
        ],
        [
            'method' => 'directoriesAreWritable',
            'message' => 'Check that directories for cache, log and import are writable',
            'hint' => [
                'Respective directories in data/ and var/ must be writable.'
            ]
        ]
    ];

    /** @var String[] */
    protected array $DirectoriesThatMustBeWritable = [
        '/var/',
    ];
    protected Connection $connection;
    protected string $projectDirectory;
    protected string $databasePrefix;

    public function __construct(
        Connection $connection, 
        string $projectDirectory, 
        string $databasePrefix)
    {
        $this->connection = $connection;
        $this->projectDirectory = $projectDirectory;
        $this->databasePrefix = $databasePrefix;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Check requirements for installing RUNALYZE.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Check requirements...</info>');
        $output->writeln('');

        foreach ($this->Checks as $check) {
            $returnCode = $this->{$check['method']}();
            $this->ReturnCode |= $returnCode;

            $output->writeln(sprintf('  * %s ... %s', $check['message'], $this->styleReturnCode($returnCode)));

            if ($returnCode != self::OKAY && isset($check['hint'])) {
                $check['hint'] = !is_array($check['hint']) ? [$check['hint']] : $check['hint'];

                foreach ($check['hint'] as $hint) {
                    $output->writeln('    <comment>'.$hint.'</comment>');
                }
            }

            $output->writeln('');
        }

        $output->writeln('  '.$this->getFinalMessage());

        return $this->ReturnCode & self::ERROR ? 1 : 0;
    }

    protected function getFinalMessage(): string
    {
        if ($this->ReturnCode & self::ERROR) {
            return '<error>Not all requirements are fulfilled, installation not possible.</error>';
        } elseif ($this->ReturnCode & self::WARNING) {
            return '<comment>There were some warnings, installation may still be possible.</comment>';
        }

        return '<info>All requirements are fulfilled.</info>';
    }

    protected function styleReturnCode(int $returnCode, string $message = null): string
    {
        switch ($returnCode) {
            case self::WARNING:
                $tag = 'comment';
                $message = $message ? $message : 'warning';
                break;
            case self::ERROR:
                $tag = 'error';
                break;
            case self::OKAY:
            default:
                $tag = 'info';
                $message = $message ? $message : 'ok';
        }

        return '<'.$tag.'>'.($message ? $message : $tag).'</'.$tag.'>';
    }

    protected function checkPhpVersion(): int
    {
        if (version_compare(self::REQUIRED_PHP_VERSION, PHP_VERSION) == 1) {
            return self::ERROR;
        }

        return self::OKAY;
    }

    protected function prefixIsNotUsed(): int
    {
        $prefix = $this->databasePrefix;

        if (0 !== $this->connection->executeQuery('SHOW TABLES LIKE "'.$prefix.'%"')->rowCount()) {
            return self::ERROR;
        }

        return self::OKAY;
    }

    protected function directoriesAreWritable(): int
    {
        foreach ($this->DirectoriesThatMustBeWritable as $directory) {
            if (!is_writable($this->projectDirectory.$directory)) {
                return self::WARNING;
            }
        }

        return self::OKAY;
    }
}
