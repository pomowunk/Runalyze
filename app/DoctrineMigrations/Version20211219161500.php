<?php

namespace Runalyze\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version20211219161500 extends AbstractMigration implements ContainerAwareInterface
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

        $this->addSql('ALTER TABLE `'.$prefix.'training`
                                ADD `fit_self_evaluation_feeling` TINYINT(3) UNSIGNED DEFAULT NULL AFTER `fit_total_descent`,
                                ADD `fit_self_evaluation_perceived_effort` TINYINT(3) UNSIGNED DEFAULT NULL AFTER `fit_self_evaluation_feeling`,
                                ADD `avg_respiration_rate` TINYINT(2) UNSIGNED DEFAULT NULL AFTER `fit_self_evaluation_perceived_effort`,
                                ADD `max_respiration_rate` TINYINT(2) UNSIGNED DEFAULT NULL AFTER `avg_respiration_rate`;');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $prefix = $this->container->getParameter('database_prefix');
        $this->addSql('ALTER TABLE `'.$prefix.'training`
                                DROP `fit_self_evaluation_feeling`,
                                DROP `fit_self_evaluation_perceived_effort`,
                                DROP `avg_respiration_rate`,
                                DROP `max_respiration_rate`;');
    }
}
