<?php

namespace Runalyze\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * unique key on dataset
 */
class Version20170424090001 extends AbstractMigration implements ContainerAwareInterface
{
    /** @var ContainerInterface|null */
    private $container;

    /**
     * @param ContainerInterface|null $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $prefix = $this->container->getParameter('app.database_prefix');
        $this->addSql('ALTER table `'.$prefix.'dataset` DROP INDEX `unique_key`');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // not possible
    }
}
