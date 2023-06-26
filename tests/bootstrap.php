<?php
/**
 * Bootstrap for PHPUnit
 * @author Hannes Christiansen
 * @package Runalyze\PHPUnit
 */

use App\Kernel;

//ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.dirname(__FILE__).'/../../../php/PEAR');

error_reporting(E_ALL);

if (!defined('RUNALYZE_TEST'))
	define('RUNALYZE_TEST', true);

if (!defined('FRONTEND_PATH'))
	define('FRONTEND_PATH', dirname(__FILE__).'/../inc/');

define('TESTS_ROOT', __DIR__);

require_once FRONTEND_PATH.'../config/bootstrap.php';

require_once FRONTEND_PATH.'system/define.consts.php';

date_default_timezone_set('Europe/Berlin');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

if (!defined('PREFIX'))
	define('PREFIX', $container->getParameter('app.database_prefix'));

if (!defined('PERL_PATH'))
	define('PERL_PATH', $container->getParameter('app.perl_path'));

if (!defined('TTBIN_PATH'))
	define('TTBIN_PATH', FRONTEND_PATH.$container->getParameter('app.ttbin_path'));

if (!defined('DATA_DIRECTORY'))
    define('DATA_DIRECTORY', $container->getParameter('app.data_directory'));

if (!defined('SQLITE_MOD_SPATIALITE'))
	define('SQLITE_MOD_SPATIALITE', $container->getParameter('app.sqlite_mod_spatialite'));

$_SERVER['REQUEST_URI'] = '/runalyze/index.php';
$_SERVER['SCRIPT_NAME'] = '/runalyze/index.php';

// Load and clean database
DB::connect(
	$database_host = $container->getParameter('app.database_host'),
	$database_port = $container->getParameter('app.database_port'),
	$database_user = $container->getParameter('app.database_user'),
	$database_password = $container->getParameter('app.database_password'),
	$database_name = 'runalyze_test_old' //$container->getParameter('app.database_name')
);
DB::getInstance()->exec('SET GLOBAL sql_mode="TRADITIONAL"');
DB::getInstance()->exec('SET FOREIGN_KEY_CHECKS=0');
DB::getInstance()->exec('DELETE FROM `'.PREFIX.'account`');
DB::getInstance()->exec('SET FOREIGN_KEY_CHECKS=1');
DB::getInstance()->exec('INSERT INTO `'.PREFIX.'account` (`id`,`username`,`mail`,`password`,`salt`) VALUES(1, "test", "test@test.com","","")');
DB::getInstance()->exec('INSERT INTO `'.PREFIX.'account` (`username`,`mail`,`password`,`salt`) VALUES("zero", "zero@test.com","","")');
DB::getInstance()->exec('UPDATE `'.PREFIX.'account` SET `id`=0 WHERE `username`="zero"');

// Login
SessionAccountHandler::setAccount(array(
	'id' => 0,
	'username' => 'runalyze',
	'language' => 'de',
	'timezone' => '43',
	'mail' => 'noreply@runalyze.com',
));

$runalyze_test_tz_lookup = true;
try {
    $lookup = new \Runalyze\Bundle\CoreBundle\Services\Import\TimezoneLookup(DATA_DIRECTORY.'/timezone.sqlite', SQLITE_MOD_SPATIALITE);
    $lookup->getTimezoneForCoordinate(13.41, 52.52);
} catch (\Runalyze\Bundle\CoreBundle\Services\Import\TimezoneLookupException $e) {
	$runalyze_test_tz_lookup = false;
}
// define('RUNALYZE_TEST_TZ_LOOKUP', $runalyze_test_tz_lookup);
define('RUNALYZE_TEST_TZ_LOOKUP', false);
// TODO: The Tests depending on this fail because ActivityListener::guessTimezoneBasedOnCoordinates is only called on prePersist, which doesn't happen here.

// Language functions
if (!function_exists('__')) {
	function __($text, $domain = 'runalyze') {
		return $text;
	}
}

if (!function_exists('_e')) {
	function _e($text, $domain = 'runalyze') {
		echo $text;
	}
}

if (!function_exists('_n')) {
	function _n($msg1, $msg2, $n, $domain = 'runalyze') {
		if ($n == 1)
			return $msg1;

		return $msg2;
	}
}

if (!function_exists('_ne')) {
	function _ne($msg1, $msg2, $n, $domain = 'runalyze') {
		if ($n == 1)
			echo $msg1;

		echo $msg2;
	}
}

// Clear cache
require_once FRONTEND_PATH.'system/class.Cache.php';
new Cache();
Cache::clean();

// Load helper class
Helper::Unknown('');

// Load test helper
require_once FRONTEND_PATH.'../tests/fake/FakeContext.php';

// Add doctrine types (required for test cases that do not use the kernel)
try {
	\Doctrine\DBAL\Types\Type::addType(\Runalyze\Bundle\CoreBundle\Doctrine\Types\TinyIntType::TINYINT, \Runalyze\Bundle\CoreBundle\Doctrine\Types\TinyIntType::class);
} catch (\Throwable $th) {
	error_log("'TinyIntType' doctrine type was already registered.");
}
try {
\Doctrine\DBAL\Types\Type::addType(\Runalyze\Bundle\CoreBundle\Doctrine\Types\PipeDelimitedArray::PIPE_ARRAY, \Runalyze\Bundle\CoreBundle\Doctrine\Types\PipeDelimitedArray::class);
} catch (\Throwable $th) {
	error_log("'PipeDelimitedArray' doctrine type was already registered.");
}
try {
\Doctrine\DBAL\Types\Type::addType(\Runalyze\Bundle\CoreBundle\Doctrine\Types\GeohashArray::GEOHASH_ARRAY, \Runalyze\Bundle\CoreBundle\Doctrine\Types\GeohashArray::class);
} catch (\Throwable $th) {
	error_log("'GeohashArray' doctrine type was already registered.");
}
try {
\Doctrine\DBAL\Types\Type::addType(\Runalyze\Bundle\CoreBundle\Doctrine\Types\RunalyzePauseArray::RUNALYZE_PAUSE_ARRAY, \Runalyze\Bundle\CoreBundle\Doctrine\Types\RunalyzePauseArray::class);
} catch (\Throwable $th) {
	error_log("'RunalyzePauseArray' doctrine type was already registered.");
}
try {
\Doctrine\DBAL\Types\Type::addType(\Runalyze\Bundle\CoreBundle\Doctrine\Types\RunalyzeRoundArray::RUNALYZE_ROUND_ARRAY, \Runalyze\Bundle\CoreBundle\Doctrine\Types\RunalyzeRoundArray::class);
} catch (\Throwable $th) {
	error_log("'RunalyzeRoundArray' doctrine type was already registered.");
}

$kernel->shutdown();
