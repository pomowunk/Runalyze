<?php
namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\DatabaseCleanup;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Tool\DatabaseCleanup\JobLoop;
use Runalyze\Configuration;
use Runalyze\Profile\Sport\SportProfile;

/**
 * @group dependsOn
 * @group dependsOnOldDatabase
 */

class JobLoopTest extends TestCase
{
	/** @var \PDO */
	protected $PDO;

	protected function setUp(): void
    {
		$this->PDO = \DB::getInstance();
		$this->PDO->exec('TRUNCATE TABLE `'.PREFIX.'route`');
		$this->PDO->exec('TRUNCATE TABLE `'.PREFIX.'trackdata`');
		$this->PDO->exec('DELETE FROM `'.PREFIX.'training`');
		$this->PDO->exec('DELETE FROM `'.PREFIX.'sport`');
	}

	protected function tearDown(): void
    {
		$this->PDO->exec('TRUNCATE TABLE `'.PREFIX.'route`');
		$this->PDO->exec('TRUNCATE TABLE `'.PREFIX.'trackdata`');
		$this->PDO->exec('DELETE FROM `'.PREFIX.'training`');
		$this->PDO->exec('DELETE FROM `'.PREFIX.'sport`');
	}

	public function testNoLoopForSingleActivity()
    {
		$this->PDO->exec(
			'INSERT INTO `'.PREFIX.'training` (`id`, `distance`, `s`, `pulse_avg`, `sportid`, `accountid`, `time`) '.
			'VALUES (1, 10, 3600, 150, '.Configuration::General()->runningSport().', 0, 1477839475)'
		);

		$Loop = new JobLoop([], $this->PDO, 0, PREFIX);
		$Loop->run();

		$data = $this->PDO->query('SELECT * FROM `'.PREFIX.'training` WHERE `id`=1 LIMIT 1')->fetch();
		$this->assertEquals(0, $data['elevation']);
		$this->assertEquals(0, $data['vo2max']);
		$this->assertEquals(0, $data['vo2max_by_time']);
		$this->assertEquals(0, $data['vo2max_with_elevation']);
		$this->assertEquals(0, $data['trimp']);
	}

	public function testCompleteLoopForSingleActivity()
    {
		$this->PDO->exec(
			'INSERT INTO `'.PREFIX.'training` (`id`, `distance`, `s`, `pulse_avg`, `sportid`, `accountid`, `time`, `routeid`) '.
			'VALUES (1, 10, 3600, 150, '.Configuration::General()->runningSport().', 0, 1477839906, 1)'
		);
		$this->PDO->exec('INSERT INTO `'.PREFIX.'route` (`id`, `elevation`, `elevation_up`, `elevation_down`, `elevations_corrected`, `accountid`) VALUES (1, 123, 123, 123, "0|123|0", 0)');
		$this->PDO->exec('INSERT INTO `'.PREFIX.'trackdata` (`activityid`, `time`, `distance`, `accountid`) VALUES (1, "0|1800|3600", "0|5|10", 0)');
		$this->PDO->exec('INSERT INTO `'.PREFIX.'sport` (`id`, `internal_sport_id`, `name`, `power`, `accountid`) VALUES ('.Configuration::General()->runningSport().', '.SportProfile::RUNNING.', "tempsport", 1, 0)');

		$Loop = new JobLoop([
            JobLoop::ELEVATION => true,
            JobLoop::ELEVATION_OVERWRITE => true,
            JobLoop::VO2MAX => true,
            JobLoop::TRIMP => true,
			JobLoop::POWER => true
        ], $this->PDO, 0, PREFIX);
		$Loop->run();

		$data = $this->PDO->query('SELECT * FROM `'.PREFIX.'training` WHERE `id`=1 LIMIT 1')->fetch();
		$this->assertNotEquals(0, $data['vo2max']);
		$this->assertNotEquals(0, $data['vo2max_by_time']);
		$this->assertNotEquals(0, $data['vo2max_with_elevation']);
		$this->assertNotEquals(0, $data['trimp']);
		$this->assertNotEquals(0, $data['power']);
		
		$trackdata = $this->PDO->query('SELECT `power` FROM `'.PREFIX.'trackdata` WHERE `activityid`=1 AND `accountid`=0 LIMIT 1')->fetch();
		$this->assertNotEmpty($trackdata['power']);
	}

	public function testOverwriteElevation()
	{
		$this->PDO->exec('INSERT INTO `'.PREFIX.'training` (`id`, `distance`, `s`, `elevation`, `routeid`, `accountid`, `sportid`, `time`) VALUES (1, 10, 3600, 42, 1, 0, '.Configuration::General()->runningSport().', 1477839906)');
		$this->PDO->exec('INSERT INTO `'.PREFIX.'route` (`id`, `elevation`, `elevation_up`, `elevation_down`, `accountid`) VALUES (1, 123, 123, 123, 0)');

		$Loop = new JobLoop([
			JobLoop::ELEVATION => true,
			JobLoop::ELEVATION_OVERWRITE => true
		], $this->PDO, 0, PREFIX);
		$Loop->run();

		$this->assertEquals(123, $this->PDO->query(
			'SELECT `elevation` FROM `'.PREFIX.'training` WHERE `id`=1 LIMIT 1'
		)->fetchColumn());
	}

	public function testDontOverwriteElevation()
    {
		$this->PDO->exec('INSERT INTO `'.PREFIX.'training` (`id`, `distance`, `s`, `elevation`, `routeid`, `accountid`, `sportid`, `time`) VALUES (1, 10, 3600, 42, 1, 0, '.Configuration::General()->runningSport().', 1477839906)');
		$this->PDO->exec('INSERT INTO `'.PREFIX.'route` (`id`, `elevation`, `elevation_up`, `elevation_down`, `accountid`) VALUES (1, 123, 123, 123, 0)');

		$Loop = new JobLoop([
            JobLoop::ELEVATION => true,
        ], $this->PDO, 0, PREFIX);
		$Loop->run();

		$this->assertEquals(42, $this->PDO->query(
			'SELECT `elevation` FROM `'.PREFIX.'training` WHERE `id`=1 LIMIT 1'
		)->fetchColumn());
	}

	public function testUsageOfCorrectElevation()
    {
		$this->PDO->exec(
			'INSERT INTO `'.PREFIX.'training` (`id`, `routeid`, `distance`, `s`, `pulse_avg`, `sportid`, `accountid`, `time`) '.
			'VALUES (1, 2, 10, 3600, 150, '.Configuration::General()->runningSport().', 0, 1477839906)'
		);
		$this->PDO->exec(
			'INSERT INTO `'.PREFIX.'training` (`id`, `routeid`, `distance`, `s`, `pulse_avg`, `sportid`, `accountid`, `time`) '.
			'VALUES (2, 1, 10, 3600, 150, '.Configuration::General()->runningSport().', 0, 1477839906)'
		);
		$this->PDO->exec('INSERT INTO `'.PREFIX.'route` (`id`, `elevations_corrected`, `accountid`) VALUES (1, "0|100", 0)');
		$this->PDO->exec('INSERT INTO `'.PREFIX.'route` (`id`, `elevations_corrected`, `accountid`) VALUES (2, "200|0", 0)');

		$Loop = new JobLoop([
            JobLoop::ELEVATION => true,
            JobLoop::ELEVATION_OVERWRITE => true,
            JobLoop::VO2MAX => true
        ], $this->PDO, 0, PREFIX);
		$Loop->run();

		$DataDown = $this->PDO->query('SELECT `elevation`, `vo2max`, `vo2max_with_elevation` FROM `'.PREFIX.'training` WHERE `id`=1 LIMIT 1')->fetch();
		$DataUp = $this->PDO->query('SELECT `elevation`, `vo2max`, `vo2max_with_elevation` FROM `'.PREFIX.'training` WHERE `id`=2 LIMIT 1')->fetch();

		$this->assertEquals($DataUp['vo2max'], $DataDown['vo2max']);
		$this->assertEquals(100, $DataUp['elevation']);
		$this->assertEquals(200, $DataDown['elevation']);
		$this->assertGreaterThan($DataDown['vo2max_with_elevation'], $DataUp['vo2max_with_elevation']);
	}

	/**
	 * @see https://github.com/Runalyze/Runalyze/issues/1970
	 */
	public function testUsageOfCorrectElevationForVO2max()
	{
		$this->PDO->exec(
			'INSERT INTO `'.PREFIX.'training` (`id`, `routeid`, `distance`, `s`, `pulse_avg`, `sportid`, `accountid`, `time`) '.
			'VALUES (1, 2, 10, 3600, 150, '.Configuration::General()->runningSport().', 0, 1477839906)'
		);
		$this->PDO->exec(
			'INSERT INTO `'.PREFIX.'training` (`id`, `routeid`, `distance`, `s`, `pulse_avg`, `sportid`, `accountid`, `time`) '.
			'VALUES (2, 1, 10, 3600, 150, '.Configuration::General()->runningSport().', 0, 1477839906)'
		);
		$this->PDO->exec('INSERT INTO `'.PREFIX.'route` (`id`, `elevation`, `elevation_up`, `elevation_down`, `accountid`) VALUES (1, 200, 200, 0, 0)');
		$this->PDO->exec('INSERT INTO `'.PREFIX.'route` (`id`, `elevation`, `elevation_up`, `elevation_down`, `accountid`) VALUES (2, 200, 0, 200, 0)');

		$Loop = new JobLoop([
			JobLoop::VO2MAX => true
		], $this->PDO, 0, PREFIX);
		$Loop->run();

		$vo2maxElevationDown = $this->PDO->query('SELECT `vo2max_with_elevation` FROM `'.PREFIX.'training` WHERE `id`=1 LIMIT 1')->fetchColumn();
		$vo2maxElevationUp = $this->PDO->query('SELECT `vo2max_with_elevation` FROM `'.PREFIX.'training` WHERE `id`=2 LIMIT 1')->fetchColumn();

		$this->assertGreaterThan($vo2maxElevationDown, $vo2maxElevationUp);
	}

	public function testIgnoreVO2maxForNotRunning()
    {
		$this->PDO->exec(
			'INSERT INTO `'.PREFIX.'training` (`id`, `distance`, `s`, `pulse_avg`, `sportid`, `accountid`, `time`) '.
			'VALUES (1, 10, 3600, 150, '.(Configuration::General()->runningSport() + 1).', 0, 1477839906)'
		);

		$Loop = new JobLoop([
            JobLoop::VO2MAX => true,
            JobLoop::TRIMP => true
        ], $this->PDO, 0, PREFIX);
		$Loop->run();

		$data = $this->PDO->query('SELECT * FROM `'.PREFIX.'training` WHERE `id`=1 LIMIT 1')->fetch();
		$this->assertEquals(0, $data['vo2max']);
		$this->assertEquals(0, $data['vo2max_by_time']);
		$this->assertEquals(0, $data['vo2max_with_elevation']);
		$this->assertNotEquals(0, $data['trimp']);
	}
}
