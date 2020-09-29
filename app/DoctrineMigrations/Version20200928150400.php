<?php

namespace Runalyze\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version20200928150400 extends AbstractMigration implements ContainerAwareInterface
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
    public function up(Schema $schema)
    {
        $prefix = $this->container->getParameter('database_prefix');

        $this->addSql('ALTER TABLE `'.$prefix.'training`
                                ADD `fit_lactate_threshold_hr` TINYINT(3) UNSIGNED DEFAULT NULL AFTER `fit_performance_condition_end`,
                                ADD `fit_total_ascent` SMALLINT UNSIGNED DEFAULT NULL AFTER `fit_lactate_threshold_hr`,
                                ADD `fit_total_descent` SMALLINT UNSIGNED DEFAULT NULL AFTER `fit_total_ascent`;
                                ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $prefix = $this->container->getParameter('database_prefix');
        $this->addSql('ALTER TABLE `'.$prefix.'training`
                                DROP `fit_lactate_threshold_hr`,
                                DROP `fit_total_ascent`,
                                DROP `fit_total_descent`;
                                ');
    }
}
