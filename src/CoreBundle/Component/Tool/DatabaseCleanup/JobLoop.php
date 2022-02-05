<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\DatabaseCleanup;

use Runalyze\Calculation\Activity\Calculator;
use Runalyze\Calculation\Power\CyclingPowerCalculator;
use Runalyze\Calculation\Power\RunningPowerCalculator;
use Runalyze\Model\Activity;
use Runalyze\Model\Trackdata;
use Runalyze\Model\Route;
use Runalyze\Profile\Sport\SportProfile;

class JobLoop extends Job
{
    /** @var string */
    const ELEVATION = 'activityElevation';

    /** @var string */
    const ELEVATION_OVERWRITE = 'activityElevationOverwrite';

    /** @var string */
    const VO2MAX = 'activityVO2max';

    /** @var string */
    const TRIMP = 'activityTrimp';

    /** @var string */
    const POWER = 'activityPower';

    /** @var array */
    protected $ElevationResults = [];

    public function run()
    {
        set_time_limit(120);
        
        if ($this->isRequested(self::ELEVATION)) {
            $this->runRouteLoop();
        }

        if (count($this->updateSet())) {
            $this->runActivityLoop();

            // This may be removed if single activities are not cached anymore.
            \Cache::clean();
        }
    }

    protected function runRouteLoop()
    {
        require_once __DIR__.'/ElevationsRecalculator.php';

        $Recalculator = new ElevationsRecalculator($this->PDO, $this->AccountId, $this->DatabasePrefix);
        $Recalculator->run();

        $this->ElevationResults = $Recalculator->results();

        $this->addMessage(sprintf(__('Elevations have been recalculated for %d routes.'), count($this->ElevationResults)));
    }

    protected function runActivityLoop()
    {
        $i = 0;
        $Query = $this->getQuery();
        $Update = $this->prepareUpdate();

        while ($Data = $Query->fetch()) {
            try {
                $Calculator = $this->calculatorFor($Data);
                $calculateVO2max = ($Data['sportid'] == $this->RunningSportId);

                if ($this->isRequested(self::ELEVATION) && $this->isRequested(self::ELEVATION_OVERWRITE)) {
                    $Update->bindValue(':elevation', $this->elevationsFor($Data)[0], \PDO::PARAM_INT);
                }

                if ($this->isRequested(self::VO2MAX)) {
                    $Update->bindValue(':vo2max', $calculateVO2max ? $Calculator->estimateVO2maxByHeartRate() : null, \PDO::PARAM_STR);
                    $Update->bindValue(':vo2max_by_time', $calculateVO2max ? $Calculator->estimateVO2maxByTime() : null, \PDO::PARAM_STR);
                    $Update->bindValue(':vo2max_with_elevation', $calculateVO2max ? $Calculator->estimateVO2maxByHeartRateWithElevation() : null, \PDO::PARAM_STR);
                }

                if ($this->isRequested(self::TRIMP)) {
                    $Update->bindValue(':trimp', $Calculator->calculateTrimp(), \PDO::PARAM_INT);
                }

                if ($this->isRequested(self::POWER)) {
                    $avg_power = $this->recalculatePower($Data);
                    $Update->bindValue(':power', $avg_power, \PDO::PARAM_INT);
                    $Update->bindValue(':is_power_calculated', $avg_power ? 1 : null, \PDO::PARAM_INT);
                }

                $Update->bindValue(':id', $Data['id']);
                $Update->execute();
                $i++;
            } catch (\RuntimeException $e) {
                $this->addMessage(sprintf(__('There was a problem with activity %d.<br>Error message: %s'), $Data['id'], $e->getMessage()));
            }
        }

        $this->addMessage(sprintf(__('%d activities have been updated.'), $i));
    }

    /**
     * @param array $data
     * @return \Runalyze\Calculation\Activity\Calculator
     */
    protected function calculatorFor(array $data)
    {
        $elevations = $this->elevationsFor($data);

        return new Calculator(
            new Activity\Entity($data),
            new Trackdata\Entity(array(
                Trackdata\Entity::TIME => $data['trackdata_time'],
                Trackdata\Entity::HEARTRATE => $data['trackdata_heartrate']
            )),
            new Route\Entity(array(
                Route\Entity::ELEVATION => $elevations[0],
                Route\Entity::ELEVATION_UP => $elevations[1],
                Route\Entity::ELEVATION_DOWN => $elevations[2]
            ))
        );
    }

    /**
     * @param array $data activity data
     * @return array ('total', 'up', 'down')
     */
    protected function elevationsFor(array $data)
    {
        if (isset($this->ElevationResults[$data['routeid']])) {
            return $this->ElevationResults[$data['routeid']];
        }

        if (isset($data['elevation']) && isset($data['elevation_up']) && isset($data['elevation_down'])) {
            return array($data['elevation'], $data['elevation_up'], $data['elevation_down']);
        }

        return array($data['training_elevation'], $data['training_elevation'], $data['training_elevation']);
    }

    /**
     * @return \PDOStatement
     */
    protected function prepareUpdate()
    {
        $Set = $this->updateSet();

        foreach ($Set as $i => $key) {
            $Set[$i] = "`$key`=IFNULL(:$key, `$key`)";
        }

        $Query = 'UPDATE `'.$this->DatabasePrefix.'training` SET '.implode(',', $Set).' WHERE `id`=:id LIMIT 1';

        return $this->PDO->prepare($Query);
    }

    /**
     * @return array
     */
    protected function updateSet()
    {
        $Set = array();

        if ($this->isRequested(self::ELEVATION) && $this->isRequested(self::ELEVATION_OVERWRITE)) {
            $Set[] = 'elevation';
        }

        if ($this->isRequested(self::VO2MAX)) {
            $Set[] = 'vo2max';
            $Set[] = 'vo2max_by_time';
            $Set[] = 'vo2max_with_elevation';
        }

        if ($this->isRequested(self::TRIMP)) {
            $Set[] = 'trimp';
        }

        if ($this->isRequested(self::POWER)) {
            $Set[] = 'power';
            $Set[] = 'is_power_calculated';
        }

        return $Set;
    }

    /**
     * @return \PDOStatement
     */
    protected function getQuery()
    {
        return $this->PDO->query(
            'SELECT
				`'.$this->DatabasePrefix.'training`.`id`,
				`'.$this->DatabasePrefix.'training`.`routeid`,
				`'.$this->DatabasePrefix.'training`.`sportid`,
				`'.$this->DatabasePrefix.'training`.`typeid`,
				`'.$this->DatabasePrefix.'training`.`distance`,
				`'.$this->DatabasePrefix.'training`.`s`,
				`'.$this->DatabasePrefix.'training`.`pulse_avg`,
				`'.$this->DatabasePrefix.'training`.`elevation` as `training_elevation`,
				`'.$this->DatabasePrefix.'training`.`power`,
				`'.$this->DatabasePrefix.'training`.`is_power_calculated`,
				`'.$this->DatabasePrefix.'route`.`elevation`,
				`'.$this->DatabasePrefix.'route`.`elevation_up`,
				`'.$this->DatabasePrefix.'route`.`elevation_down`,
				`'.$this->DatabasePrefix.'trackdata`.`time` as `trackdata_time`,
				`'.$this->DatabasePrefix.'trackdata`.`heartrate` as `trackdata_heartrate`
			'.(!$this->isRequested(self::POWER) ? '' : '
				,
				`'.$this->DatabasePrefix.'trackdata`.`distance` as `trackdata_distance`,
				IFNULL(
					`'.$this->DatabasePrefix.'route`.`elevations_corrected`,
					`'.$this->DatabasePrefix.'route`.`elevations_original`
				) as `route_elevation`,
				`'.$this->DatabasePrefix.'sport`.`internal_sport_id`
			').'
			FROM `'.$this->DatabasePrefix.'training`
			LEFT JOIN `'.$this->DatabasePrefix.'trackdata`
				ON `'.$this->DatabasePrefix.'trackdata`.`activityid` = `'.$this->DatabasePrefix.'training`.`id`
			LEFT JOIN `'.$this->DatabasePrefix.'route`
				ON `'.$this->DatabasePrefix.'route`.`id` = `'.$this->DatabasePrefix.'training`.`routeid`
			LEFT JOIN `'.$this->DatabasePrefix.'sport`
				ON `'.$this->DatabasePrefix.'sport`.`accountid` = '.$this->AccountId.'
				AND `'.$this->DatabasePrefix.'sport`.`id` = `'.$this->DatabasePrefix.'training`.`sportid`
			WHERE `'.$this->DatabasePrefix.'training`.`accountid` = '.$this->AccountId
        );
    }
    
    /**
     * @param array $data
     * @return int|null average power
     */
    protected function recalculatePower(array $data)
    {
        if (!empty($data['power']) && empty($data['is_power_calculated'])){
            return;
        }

        if($data['internal_sport_id'] == SportProfile::CYCLING || $data['internal_sport_id'] == SportProfile::RUNNING) {
            $trackdata = new Trackdata\Entity([
                Trackdata\Entity::TIME => $data['trackdata_time'],
                Trackdata\Entity::DISTANCE => $data['trackdata_distance']
            ]);
            if (empty($data['route_elevation'])){
                $route = new Route\Entity([]);
            } else {
                $route = new Route\Entity([
                    Route\Entity::ELEVATIONS_CORRECTED => array_map('intval', explode('|', $data['route_elevation']))
                ]);
            }

            if ($data['internal_sport_id'] == SportProfile::CYCLING) {
                $calculator = new CyclingPowerCalculator($trackdata, $route);
            } else {
                $calculator = new RunningPowerCalculator($trackdata, $route);
            }

            $calculator->calculate();

            $update_trackpower = $this->PDO->prepare('UPDATE `'.$this->DatabasePrefix.'trackdata` SET `power`=:power WHERE `activityid`=:id LIMIT 1');
            $update_trackpower->bindValue(':power', implode('|', $calculator->powerData()), \PDO::PARAM_STR);
            $update_trackpower->bindValue(':id', $data['id'], \PDO::PARAM_INT);
            $update_trackpower->execute();

            return $calculator->average();
        }
    }
}
