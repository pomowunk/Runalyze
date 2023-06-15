<?php

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Configuration;
use Runalyze\Timezone;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Frontend class for setting up everything
 *
 * The frontend initializes everything for Runalyze.
 * It sets the autoloader, constants and mysql-connection.
 * By default, constructing a new frontend will print a html-header.
 *
 * @author Hannes Christiansen
 * @package Runalyze\Frontend
 */
class Frontend {
	protected ?TokenStorageInterface $symfonyToken = null;
	protected ParameterBagInterface $parameterBag;

	protected $HideFooter = false;

	/**
	 * Constructing a new Frontend includes all files and sets the correct header.
	 * Runalyze is not usable without setting up the environment with this class.
	 */
	public function __construct(
		ParameterBagInterface $parameterBag,
		bool $hideHeaderAndFooter = false,
		?TokenStorageInterface $symfonyToken = null,
	)
	{
		$this->parameterBag = $parameterBag;
		$this->symfonyToken = $symfonyToken;

		$this->initSystem();
		$this->defineConsts();

		if (!$hideHeaderAndFooter)
			$this->displayHeader();
		else
		    $this->HideFooter = true;
	}

	/**
	 * Destructor
	 */
	public function __destruct() {
	    if (!$this->HideFooter)
    		$this->displayFooter();
	}

	/**
	 * Init system
	 */
	private function initSystem() {
		define('RUNALYZE', true);
		define('FRONTEND_PATH', dirname(__FILE__).'/');

		$this->setAutoloader();
		$this->initCache();
		$this->initConfig();
		$this->initDatabase();
		$this->initSessionAccountHandler();
		$this->initTimezone();
		$this->forwardAccountIDtoDatabaseWrapper();
	}

	/**
	 * Set up Autloader
	 */
	private function setAutoloader() {
		require_once FRONTEND_PATH.'../vendor/autoload.php';
	}

	/**
	 * Setup config
	 */
	private function initConfig() {
	    define('NOKIA_HERE_APPID', $this->parameterBag->get('app.nokia_here_appid'));
	    define('NOKIA_HERE_TOKEN', $this->parameterBag->get('app.nokia_here_token'));
	    define('THUNDERFOREST_API_KEY', $this->parameterBag->get('app.thunderforest_api_key'));
        define('MAPBOX_API_KEY', $this->parameterBag->get('app.mapbox_api_key'));
	    define('PERL_PATH', $this->parameterBag->get('app.perl_path'));
	    define('TTBIN_PATH', $this->parameterBag->get('app.ttbin_path'));
	    define('GEONAMES_USERNAME', $this->parameterBag->get('app.geonames_username'));
	    define('USER_DISABLE_ACCOUNT_ACTIVATION', $this->parameterBag->get('app.user_disable_account_activation'));
	    define('SQLITE_MOD_SPATIALITE', $this->parameterBag->get('app.sqlite_mod_spatialite'));
        define('RUNALYZE_VERSION', $this->parameterBag->get('app.version'));
        define('DATA_DIRECTORY', $this->parameterBag->get('app.data_directory'));
	}

	/**
	 * Setup timezone
	 */
	private function initTimezone() {
		Timezone::setPHPTimezone(SessionAccountHandler::getTimezone());
		Timezone::setMysql();
	}

        /**
	 * Setup Language
	 */
	private function initCache() {
		require_once FRONTEND_PATH.'/system/class.Cache.php';

		try {
			new Cache();
		} catch (Exception $E) {
			die('Cache directory "./'.Cache::PATH.'/cache/" must be writable.');
		}
	}

	/**
	 * Init constants
	 */
	private function defineConsts() {
		require_once FRONTEND_PATH.'system/define.consts.php';

		Configuration::loadAll();

		\Runalyze\Calculation\JD\LegacyEffectiveVO2maxCorrector::setGlobalFactor( Configuration::Data()->vo2maxCorrectionFactor() );

		require_once FRONTEND_PATH.'class.Helper.php';
	}

	/**
	 * Connect to database
	 */
	private function initDatabase() {
		define('PREFIX', $this->parameterBag->get('app.database_prefix'));

		DB::connect(
			$this->parameterBag->get('app.database_host'),
			$this->parameterBag->get('app.database_port'),
			$this->parameterBag->get('app.database_user'),
			$this->parameterBag->get('app.database_password'),
			$this->parameterBag->get('app.database_name')
		);
	}

	/**
	 * Init SessionAccountHandler
	 */
	protected function initSessionAccountHandler() {
		new SessionAccountHandler();

		if (!is_null($this->symfonyToken) && $this->symfonyToken->getToken()->getUser() != 'anon.') {
			/** @var Account $user */
		    $user = $this->symfonyToken->getToken()->getUser();

		    SessionAccountHandler::setAccount(array(
			    'id' => $user->getId(),
			    'username' => $user->getUsername(),
			    'language' => $user->getLanguage(),
			    'timezone' => $user->getTimezone(),
			    'mail' => $user->getMail(),
				'gender' => $user->getGender(),
				'birthyear' => $user->getBirthyear()
		    ));
		}
	}

	/**
	 * Forward accountid to database wraper
	 */
	protected function forwardAccountIDtoDatabaseWrapper() {
		DB::getInstance()->setAccountID( SessionAccountHandler::getId() );
	}

	/**
	 * Display the HTML-Header
	 */
	public function displayHeader() {

		if (!Request::isAjax() && !isset($_GET['hideHtmlHeader']))
			include 'tpl/tpl.Frontend.header.php';
	}

	/**
	 * Display the HTML-Footer
	 */
	public function displayFooter() {
		if (!Request::isAjax() && !isset($_GET['hideHtmlHeader'])) {
			include 'tpl/tpl.Frontend.footer.php';
		}
	}
}
