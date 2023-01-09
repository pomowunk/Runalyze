<?php

namespace Runalyze\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * TSC: Add new table for equipment to sports assigment: equipment_spor (the t is missing because its not the type assignment);
 * its the assignment to the equipment and i don't want to change the existing equipment_sport to runalyze_equipment_sport_type)
 */
class Version20230101190000 extends AbstractMigration implements ContainerAwareInterface
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


        $this->addSql('CREATE TABLE IF NOT EXISTS `'.$prefix.'equipment_spor` (
                `sportid` int(10) unsigned NOT NULL,
                `equipment_id` int(10) unsigned NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');


        $this->addSql('ALTER TABLE `'.$prefix.'equipment_spor`
            ADD PRIMARY KEY (`sportid`,`equipment_id`), ADD KEY `equipment_id` (`equipment_id`)');

        $this->addSql('ALTER TABLE `'.$prefix.'equipment_spor`
            ADD CONSTRAINT `runalyze_equipment_spor_ibfk_1` FOREIGN KEY (`sportid`) REFERENCES `runalyze_sport` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `runalyze_equipment_spor_ibfk_2` FOREIGN KEY (`equipment_id`) REFERENCES `runalyze_equipment` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $prefix = $this->container->getParameter('database_prefix');
        $this->addSql('DROP TABLE `'.$prefix.'equipment_spor`');
    }
}