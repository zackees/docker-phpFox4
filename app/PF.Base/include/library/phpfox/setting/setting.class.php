<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 * Global Site Settings
 * Class is used to load and retrieve global settings, which are
 * stored in the database table "setting". Admins can easily modify
 * these settings direct from the AdminCP. The most common interaction
 * with this class is to get a setting value and to do this we use our
 * core static class.
 *
 * Example:
 * <code>
 * Phpfox::getParam('foo.bar');
 * </code>
 *
 * @copyright         [PHPFOX_COPYRIGHT]
 * @author            phpFox
 * @package           Phpfox
 * @version           $Id: setting.class.php 7095 2014-02-06 16:19:52Z Fern $
 */
class Phpfox_Setting
{
	/**
	 * List of all the settings.
	 *
	 * @var array
	 */
	private $_aParams = [];

	/**
	 * Default settings we load and their values. We only
	 * use this when installing the script the first time
	 * since the database has not been installed yet.
	 *
	 * @var array
	 */
	private $_aDefaults
		= [
			'core.cache_skip' => false,
			'core.cache_suffix' => '.php',
			'core.salt' => '',
			'core.cache_add_salt' => false,
			'core.session_prefix' => 'core',
			'core.title_delim' => '&raquo;',
			'core.site_title' => 'phpFox',
			'core.branding' => false,
			'core.default_lang_id' => 'en',
			'core.default_style_name' => 'konsort',
			'core.default_theme_name' => 'default',
			'language.lang_pack_helper' => false,
			'core.cookie_path' => '/',
			'core.cookie_domain' => '',
			'core.url_rewrite' => '2',
			'core.is_installed' => false,
			'user.profile_use_id' => false,
			'user.disable_username_on_sign_up' => 'both',
			'core.site_copyright' => '',
			'db' => [
				'prefix' => 'phpfox_',
				'host' => 'localhost',
				'user' => '',
				'pass' => '',
				'name' => '',
				'driver' => 'mysql',
				'slave' => false,
			],
			'balancer' => [
				'enabled' => false,
			],
			'user.min_length_for_username' => '5',
			'user.max_length_for_username' => '25',
			'user.require_basic_field' => false,
			'core.identify_dst' => '1',
			'core.global_site_title' => 'phpFox',
			'core.phpfox_is_hosted' => false,
			'core.use_jquery_datepicker' => false,
			'core.date_field_order' => 'MDY',
			'core.cache_storage' => 'file',
			'core.allow_cdn' => false,
			'core.is_auto_hosted' => false,
			'core.store_only_users_in_session' => false,
			'core.ip_check' => 1,
			'profile.profile_caches_user' => false,
			'rate.cache_rate_profiles' => false,
			'core.defer_loading_js' => false,
			'core.use_custom_cookie_names' => false,
			'core.include_site_title_all_pages' => true,
			'core.defer_loading_user_images' => false,
			'core.auth_user_via_session' => false,
			'video.convert_servers_enable' => false,
			'ad.multi_ad' => true,
			'friend.cache_mutual_friends' => true
		];

	public $override = [
		'photo.enabled_watermark_on_photos' => false,
	];

	private $_aPasswordSettings = [
		'core.mail_smtp_password',
		'core.ftp_password'
	];


	/**
	 * @var array this field hold all configuration from file
	 */
	protected $serverConfigFile = [];

	/**
	 * Class constructor. We run checks here to make sure the server setting file
	 * is in place and this is where we can judge if the script has been installed
	 * or not.
	 *
	 */
	public function __construct()
	{
		$_CONF = [];
		$sMessage = 'Oops! phpFox is not installed. Please run the install script to get your community setup.';

		if (defined('PHPFOX_IS_UPGRADE')) {
			$old = PHPFOX_DIR . '../include/setting/server.sett.php';
			if (file_exists($old)) {
				if (is_dir(PHPFOX_DIR_SETTINGS)) {
					copy($old, PHPFOX_DIR_SETTINGS . 'server.sett.php');
				} else {
					$_CONF = [];
					require($old);
				}
			}
		}

		if (file_exists(PHPFOX_DIR_SETTINGS . 'server.sett.php') || count($_CONF)) {
			if (!count($_CONF)) {
				$_CONF = [];

				require(PHPFOX_DIR_SETTINGS . 'server.sett.php');
			}

			if (!defined('PHPFOX_INSTALLER')) {
				if (!isset($_CONF['core.is_installed'])) {
					Phpfox::getLib('phpfox.api')->message($sMessage);
				}

				if (!$_CONF['core.is_installed']) {
					Phpfox::getLib('phpfox.api')->message($sMessage);
				}
			}

			if ($_CONF['core.db_table_installed'] === false && !defined('PHPFOX_SCRIPT_CONFIG')) {
				define('PHPFOX_SCRIPT_CONFIG', true);
			}
		} else {
			define('PHPFOX_SCRIPT_CONFIG', true);
		}
		$this->serverConfigFile = $_CONF;

		// check and force captcha settings
		if (isset($this->serverConfigFile['captcha.recaptcha_public_key']) && isset($this->serverConfigFile['captcha.recaptcha_private_key'])) {
			$this->serverConfigFile['captcha.captcha_type'] = 'recaptcha';
			$this->serverConfigFile['user.captcha_on_signup'] = 1;
		}

		// cache settings
		if (isset($this->serverConfigFile['cache.driver'])) {
			$cacheDriver = $this->serverConfigFile['cache.driver'];
			$this->serverConfigFile['PHPfox_Core.pf_core_cache_driver'] = $cacheDriver;

			if (isset($this->serverConfigFile['cache.host'])) {
				$cacheHost = $this->serverConfigFile['cache.host'];
				if ($cacheDriver == 'redis') {
					$this->serverConfigFile['PHPfox_Core.pf_core_cache_redis_host'] = $cacheHost;
				} elseif ($cacheDriver == 'memcached') {
					$this->serverConfigFile['PHPfox_Core.pf_core_cache_memcached_host'] = $cacheHost;
				}
			}
			if (isset($this->serverConfigFile['cache.port'])) {
				$cachePort = $this->serverConfigFile['cache.port'];
				if ($cacheDriver == 'redis') {
					$this->serverConfigFile['PHPfox_Core.pf_core_cache_redis_port'] = $cachePort;
				} elseif ($cacheDriver == 'memcached') {
					$this->serverConfigFile['PHPfox_Core.pf_core_cache_memcached_port'] = $cachePort;
				}
			}
			if ($cacheDriver == 'redis') {
				if (isset($this->serverConfigFile['cache.auth_pass'])) {
					$this->serverConfigFile['PHPfox_Core.pf_core_cache_redis_password'] = $this->serverConfigFile['cache.auth_pass'];
				}
				if (isset($this->serverConfigFile['cache.redis_database'])) {
					$this->serverConfigFile['PHPfox_Core.pf_core_cache_redis_database'] = $this->serverConfigFile['cache.redis_database'];
				} else {
					$this->serverConfigFile['PHPfox_Core.pf_core_cache_redis_database'] = 0;
				}
			}
		}

		if ((!isset($_CONF['core.host'])) || (isset($_CONF['core.host']) && $_CONF['core.host'] == 'HOST_NAME')) {
			$_CONF['core.host'] = $_SERVER['HTTP_HOST'];
		}

		if ((!isset($_CONF['core.folder']))
			|| (isset($_CONF['core.folder'])
				&& $_CONF['core.folder'] == 'SUB_FOLDER')
		) {
			$_CONF['core.folder'] = '/';
		}

		if (!defined('PHPFOX_INSTALLER') && $_CONF['core.url_rewrite'] == '2') {
			$_CONF['core.folder'] = $_CONF['core.folder'] . 'index.php/';
		}

		require_once(PHPFOX_DIR_SETTING . 'common.sett.php');

		if (defined('PHPFOX_INSTALLER')) {
			$_CONF['core.path'] = '../';
			$_CONF['core.url_file'] = '../file/';
		}

		if (file_exists(PHPFOX_DIR_SETTING . 'security.sett.php')) {
			require_once(PHPFOX_DIR_SETTING . 'security.sett.php');
		} else {
			require_once(PHPFOX_DIR_SETTING . 'security.sett.php.new');
		}

		$this->_aParams =& $_CONF;

		if (defined('PHPFOX_INSTALLER')) {
			$this->_aParams['core.url_rewrite'] = '2';
			// http://www.php.net/manual/en/intro.mysql.php
			if (isset($this->_aParams['db']) && ($this->_aParams['db']['driver'] == 'mysqli')
				&& !function_exists('mysqli_connect')
			) {
				$this->_aParams['db']['driver'] = 'mysql';
			}
		}
	}

	public function getActualValue($type_id, $actual_value, $extra = null)
	{
		switch ($type_id) {
			case 'boolean':
				return ($actual_value == 'false' || !$actual_value) ? false : true;
			case 'integer':
				return (int)$actual_value;
			case 'array':
			case 'currency':
			case 'multi_text':
			case 'multi_checkbox':
				if (is_array($actual_value)) {
					return $actual_value;
				} elseif ($actual_value) {
					// Fix un-serialize string length depending on the database driver
					$actual_value = preg_replace_callback("/s:([0-9]+):\"(.*?)\";/is", function ($matches) {
						return "s:" . strlen($matches[2]) . ":\"{$matches[2]}\";";
					}, $actual_value);

					if (is_array(unserialize($actual_value))) {
						return unserialize($actual_value);
					} else {
						$sValue = @unserialize($actual_value);
						if ($sValue === false) {
							$sValue = $actual_value;
						}
						@eval("\$actual_value=$sValue;");
						return $actual_value;
					}
				}
				return [];
			case 'drop':
				$aCacheArray = unserialize($actual_value);
				return $aCacheArray['default'];
			default:
				return $actual_value;

		}
	}

	/**
	 * @return Phpfox_Setting
	 */
	public static function instance()
	{
		return Phpfox::getLib('setting');
	}

	/**
	 * Creates a new setting.
	 *
	 * @param array|string $mParam ARRAY of settings and values.
	 * @param string $mValue Value of setting if the 1st argument is a string.
	 */
	public function setParam($mParam, $mValue = null)
	{
		if (is_string($mParam)) {
			$this->_aParams[$mParam] = $mValue;
		} else {
			foreach ($mParam as $mKey => $mValue) {
				$this->_aParams[$mKey] = $mValue;
			}
		}
	}

	/**
	 * Build the setting cache by getting all the settings from the database
	 * and then caching it. This way we only load it from the database
	 * the one time.
	 *
	 */
	public function set()
	{
		if (defined('PHPFOX_INSTALLER') && defined('PHPFOX_SCRIPT_CONFIG') && !defined('PHPFOX_IS_UPGRADE')) {
			return;
		}

		$oCache = null;
		$iId = '';
		if (!defined('PHPFOX_INSTALLER')) {
			$oCache = Phpfox::getLib('cache');
			$iId = $oCache->set('setting');
		}

		if (defined('PHPFOX_INSTALLER') || !($aRows = $oCache->getLocalFirst($iId)) || !is_array($aRows)) {
			$aRows = Phpfox_Database::instance()
				->select('s.type_id, s.var_name, s.value_actual, m.module_id AS module_name')
				->from(Phpfox::getT('setting'), 's')
				->join(Phpfox::getT('module'), 'm', 'm.module_id = s.module_id AND m.is_active = 1')
				->execute('getRows');

			foreach ($aRows as $iKey => $aRow) {
				// Remove un-active module settings
				if (!empty($aRow['module_name']) && !Phpfox::isModule($aRow['module_name'])) {
					unset($aRows[$iKey]);
					continue;
				}

				if ($aRow['var_name'] == 'allowed_html') {
					$aHtmlTags = [];
					$sAllowedTags = $aRow['value_actual'];
					preg_match_all("/<(.*?)>/i", $sAllowedTags, $aMatches);
					if (isset($aMatches[1])) {
						foreach ($aMatches[1] as $sHtmlTag) {
							$aHtmlParts = explode(' ', $sHtmlTag);
							$sHtmlTag = trim($aHtmlParts[0]);
							$aHtmlTags[$sHtmlTag] = true;
						}
					}
					$aRows[$iKey]['value_actual'] = $aHtmlTags;
				}

				if ($aRow['var_name'] == 'session_prefix') {
					$aRows[$iKey]['value_actual'] = $aRow['value_actual'] . substr($this->_aParams['core.salt'], 0, 2)
						. substr($this->_aParams['core.salt'], -2);
				}

				if ($aRow['var_name'] == 'description' || $aRow['var_name'] == 'keywords') {
					$aRows[$iKey]['value_actual'] = strip_tags($aRow['value_actual']);
					$aRows[$iKey]['value_actual'] = str_replace(["\n", "\r"], "", $aRows[$iKey]['value_actual']);
				}

				// Lets set the correct type
				switch ($aRow['type_id']) {
					case 'boolean':
						if (strtolower($aRows[$iKey]['value_actual']) == 'true'
							|| strtolower($aRows[$iKey]['value_actual']) == 'false'
						) {
							$aRows[$iKey]['value_actual'] = (strtolower($aRows[$iKey]['value_actual']) == 'true' ? '1'
								: '0');
						}
						settype($aRows[$iKey]['value_actual'], 'boolean');
						break;
					case 'integer':
						settype($aRows[$iKey]['value_actual'], 'integer');
						break;
					case 'array':
					case 'currency':
					case 'multi_text':
					case 'multi_checkbox':
						if (!empty($aRow['value_actual'])) {
							// Fix unserialize sting length depending on the database driver
							$aRow['value_actual'] = preg_replace_callback("/s:(.*):\"(.*?)\";/is", function ($matches) {
								return "s:" . strlen($matches[2]) . ":\"{$matches[2]}\";";

							}, $aRow['value_actual']);

							if (@unserialize($aRow['value_actual']) != false) {
								if (is_array(unserialize($aRow['value_actual']))) {
									$aRows[$iKey]['value_actual'] = unserialize($aRow['value_actual']);
								} else {
									eval("\$aRows[\$iKey]['value_actual'] = " . unserialize($aRow['value_actual']) . "");
								}
							}
						}

						if ($aRow['var_name'] == 'global_genders') {
							$aTempGenderCache = $aRows[$iKey]['value_actual'];
							$aRows[$iKey]['value_actual'] = [];
							foreach ($aTempGenderCache as $aGender) {
								$aGenderExplode = explode('|', $aGender);
								if (count($aGenderExplode)
									>= 3
								) //To add a new gender you need to populate it with 3 values separated by a pipe "|" (without quotes).
								{
									$aRows[$iKey]['value_actual'][$aGenderExplode[0]] = [
										$aGenderExplode[1],
										$aGenderExplode[2],
										(isset($aGenderExplode[3]) ? $aGenderExplode[3] : null),
										(isset($aGenderExplode[4]) ? $aGenderExplode[4] : null),
									];
								}
							}
						}
						break;
					case 'drop':
						// Get the default value from a drop-down setting
						$aCacheArray = unserialize($aRow['value_actual']);
						$aRows[$iKey]['value_actual'] = $aCacheArray['default'];
						unset($aCacheArray);
						break;
					case 'large_string':
						break;
				}
			}

			if (!defined('PHPFOX_INSTALLER')) {
				$oCache->saveBoth($iId, $aRows);
				Phpfox::getLib('cache')->group('setting', $iId);
			}
		}
		foreach ($aRows as $aRow) {
			$this->_aParams[$aRow['module_name'] . '.' . $aRow['var_name']] = $aRow['value_actual'];
		}

		// Make sure we set the correct cookie domain in case the admin did not
		if ($this->_aParams['core.url_rewrite'] == '3' && empty($this->_aParams['core.cookie_domain'])) {
			$this->_aParams['core.cookie_domain'] = preg_replace("/(.*?)\.(.*?)$/i", ".$2", $_SERVER['HTTP_HOST']);
		}
	}

	
	public function hasEnvParam($key){
		if(!is_string($key)){
			return false;
		}
		if(isset($this->serverConfigFile[$key])){
			return  $this->serverConfigFile[$key] !== null;
		}elseif(($pos = strpos($key, '.')) > 0){
			$key =  substr($key, $pos + 1);
			return isset($this->serverConfigFile[$key]) && $this->serverConfigFile[$key] !== null;
		}
		return false;
	}

	/**
	 * @param string $key
	 * @return array|bool|mixed
	 */
    public function getFromServerConfigFile($key, $default = null)
    {
        if($key === null){
            return $this->serverConfigFile;
        }
        if (is_string($key) && isset($this->serverConfigFile[$key])) {
            return $this->serverConfigFile[$key];
        }
        return isset($default) ? $default : false;
    }

	/**
	 * Get a setting and its value.
	 *
	 * @param mixed $mVar STRING name of the setting or ARRAY name of the setting.
	 * @param string $sDef Default value in case the setting cannot be found.
	 *
	 * @return mixed Returns the value of the setting, which can be a STRING, ARRAY, BOOL or INT.
	 */
	public function getParam($mVar, $sDef = null)
	{
		if ($mVar == 'core.branding') {
			if (!defined('PHPFOX_LICENSE_ID')) {
				return false;
			}
			if (PHPFOX_LICENSE_ID == 'techie') {
				return false;
			}
			return true;
		}

		if ($mVar == 'core.phpfox_is_hosted') {
			return $this->getParam('core.is_auto_hosted');
		}

		if (defined('PHPFOX_IS_HOSTED_SCRIPT')) {
			if ($mVar == 'core.setting_session_prefix') {
				return PHPFOX_IS_HOSTED_SCRIPT;
			}
		}

		if (defined('PHPFOX_INSTALLER') && $mVar == 'core.cache_js_css') {
			return false;
		}

		if (($serverConfig = $this->getFromServerConfigFile($mVar))) {
			return $serverConfig;
		}

		if (is_array($mVar)) {
			if (!isset($this->_aDefaults[$mVar[0]][$mVar[1]]) && isset($sDef)) {
				$this->_aDefaults[$mVar[0]][$mVar[1]] = $sDef;
			}
			$sParam = (isset($this->_aParams[$mVar[0]][$mVar[1]])
				? $this->_aParams[$mVar[0]][$mVar[1]]
				: (isset($this->_aDefaults[$mVar[0]][$mVar[1]]) ? $this->_aDefaults[$mVar[0]][$mVar[1]]
					: $sDef));
		} else {
			if (!isset($this->_aDefaults[$mVar]) && isset($sDef)) {
				$this->_aDefaults[$mVar] = $sDef;
			}
			$sParam = (isset($this->_aParams[$mVar]) ? $this->_aParams[$mVar]
				: (isset($this->_aDefaults[$mVar]) ? $this->_aDefaults[$mVar] : $sDef));

			if (!defined('PHPFOX_INSTALLER') && $mVar == 'core.site_copyright') {
				$sParam = Phpfox_Locale::instance()->convert($sParam);
			}
			if ($mVar == 'activitypoint.activity_points_conversion_rate') {
				$sParam = (empty($sParam) ? [] : json_decode($sParam, true));
			}
			if (in_array($mVar, $this->_aPasswordSettings)) {
				// decode password
				$password = substr_replace(base64_decode(base64_decode($sParam)), '', -32);
				if ($password) {
					$sParam = $password;
				}
			}
		}

		return $sParam;
	}

	/**
	 * Checks to see if a setting exists or not.
	 *
	 * @param string $mVar Name of the setting.
	 *
	 * @return bool TRUE it exists, FALSE if it does not.
	 */
	public function isParam($mVar)
	{
		return (isset($this->_aParams[$mVar]) ? true : false);
	}

	/**
	 * @return array
	 */
	public function all()
	{
		return $this->_aParams;
	}
}