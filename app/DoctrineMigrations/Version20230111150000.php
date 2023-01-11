<?php

namespace Runalyze\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * TSC: Migrate the existing RPE (6-20) to the simpler RPE (1-10). This fits better to the Garmin FIT SelfEvaluationPerceivedEffort.
 */
class Version20230111150000 extends AbstractMigration implements ContainerAwareInterface {
    /** @var ContainerInterface|null */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void {
        $prefix = $this->container->getParameter('database_prefix');

        // migrate existing rpe (6-20) to 1-10
        $this->addSql('update `'.$prefix.'training` t set t.rpe = round((t.rpe / 1.5) - 3.5) where t.rpe is not null;');

        // use the fit_self_evaluation_perceived_effort as RPE 
        $this->addSql('update `'.$prefix.'training` t set t.rpe = t.fit_self_evaluation_perceived_effort '.
            'where t.rpe is null and t.fit_self_evaluation_perceived_effort is not null and t.fit_self_evaluation_perceived_effort between 1 and 10 '.
            // ignore RG and TR runs (here are the RPE values used for the run-type)
            'and (t.fit_self_evaluation_perceived_effort <> 1 and t.fit_self_evaluation_feeling <> 5 and '.
                 't.fit_self_evaluation_perceived_effort <> 10 and t.fit_self_evaluation_feeling <> 1);');

        // fix wrong values
        $this->addSql('update `'.$prefix.'training` t set t.rpe = 1 where t.rpe is not null and t.rpe < 1;');
        $this->addSql('update `'.$prefix.'training` t set t.rpe = 10 where t.rpe is not null and t.rpe > 10;');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void {
        $prefix = $this->container->getParameter('database_prefix');

        $this->addSql('update `'.$prefix.'training` t set t.rpe = round((t.rpe * 1.5) + 4.5) where t.rpe is not null;');

        // fix wrong values
        $this->addSql('update `'.$prefix.'training` t set t.rpe = 6 where t.rpe is not null and t.rpe < 6;');
        $this->addSql('update `'.$prefix.'training` t set t.rpe = 20 where t.rpe is not null and t.rpe > 20;');
    }
}