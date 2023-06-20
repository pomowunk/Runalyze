<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class InstallDatabaseCommand extends Command
{
    const DATABASE_STRUCTURE_FILE = '/inc/install/structure.sql';

    protected static $defaultName = 'runalyze:install:database';

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
            ->setDescription('Setup RUNALYZE database.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Setup RUNALYZE database...</info>');
        $output->writeln('');
        $output->writeln(sprintf('  <info>Importing %s ...</info>', self::DATABASE_STRUCTURE_FILE));
        $output->writeln('');

        $this->importDatabaseStructure();
        $output->writeln('  <info>Database has been successfully initialized.</info>');

        $this->addAllMigrationsToDatabase();
        $output->writeln('  <info>Database was migrated to current status.</info>');

        return 0;
    }

    /**
     * @throws \Exception
     */
    protected function importDatabaseStructure()
    {
        $fileName = $this->projectDirectory.self::DATABASE_STRUCTURE_FILE;
        $queries = $this->getSqlFileAsArray($fileName, $this->databasePrefix);

        $this->connection->beginTransaction();

        try {
            foreach ($queries as $query) {
                $this->connection->executeQuery($query);
            }

            try {
                $this->connection->commit();
            } catch (\Throwable $th) {
                // queries are auto-committed anyways, even when disabled. ignore...
                if ($th->getMessage() !== 'There is no active transaction') {
                    throw $th;
                }
            }
        } catch (\Exception $e) {
            try {
                $this->connection->rollBack();
            } catch(\Exception $e_ignored) {

            }

            throw $e;
        }
    }

    private function addAllMigrationsToDatabase()
    {
        $app = $this->getApplication();
        $app->setAutoExit(false);

        $input = new StringInput('doctrine:migrations:version --add --all -n -q');
        $output = new NullOutput();
        $app->run($input, $output);
    }

    public function getSqlFileAsArray(string $filename, string $databasePrefix, bool $removeDelimiter = true): array
    {
        $MRK = array('DELIMITER', 'USE', 'SET', 'LOCK', 'SHOW', 'DROP', 'GRANT', 'ALTER', 'UNLOCK', 'CREATE', 'INSERT', 'UPDATE', 'DELETE', 'REVOKE', 'REPLACE', 'RENAME', 'TRUNCATE');
        $SQL = @file($filename);
        $query  = '';
        $array = array();
        $inDelimiter = false;

        if (!is_array($SQL)) {
            $SQL = array();
        }

        foreach ($SQL as $line) {
            $line = trim($line);
            $line = str_replace('runalyze_', $databasePrefix, $line);

            if ($inDelimiter) {
                if (mb_substr($line, 0, 9) == 'DELIMITER') {
                    $inDelimiter = false;
                    $query .= $removeDelimiter ? ';' : ' '.$line;
                    $array[] = $query;
                    $query = '';
                } elseif (trim($line) != '//') {
                    $query .= ' '.$line;
                }
            } else {
                $AA = explode(' ', $line);
                if (in_array(strtoupper($AA[0]), $MRK)) {
                    if ($AA[0] == 'DELIMITER') {
                        $inDelimiter = true;
                        $query = $removeDelimiter ? '' : $line;
                    } else {
                        $query = $line;
                    }
                } elseif (strlen($query) > 1) {
                    $query .= " ".$line;
                }

                $x = strlen($query) - 1;
                if (mb_substr($query,$x) == ';') {
                    $array[] = $query;
                    $query = '';
                }
            }
        }

        return $array;
    }
}
