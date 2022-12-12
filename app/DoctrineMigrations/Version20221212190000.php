<?php

namespace Runalyze\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version20221212190000 extends AbstractMigration implements ContainerAwareInterface
{
    /** @var ContainerInterface|null */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $prefix = $this->container->getParameter('database_prefix');

        $this->addSql('ALTER TABLE `'.$prefix.'route`
                                ADD `altitude_barometric` tinyint(1) unsigned DEFAULT 0 AFTER `elevations_source`;
                                ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $prefix = $this->container->getParameter('database_prefix');
        $this->addSql('ALTER TABLE `'.$prefix.'route`
                                DROP `altitude_barometric`;
                                ');
    }
}
