<?php

if (version_compare(phpversion(), '5.6', '<') === true) {
	exit('phpFox 4 requires PHP 5.6 or newer.');
}

ob_start();

if (!defined('PHPFOX_NO_SESSION')) {
	if (function_exists('ini_set')) {
		ini_set('session.cookie_httponly', true);
	}
}
if (!defined('PHPFOX')) {
	define('PHPFOX', true);
	define('PHPFOX_DS', DIRECTORY_SEPARATOR);
	define('PHPFOX_DIR', dirname(__FILE__) . PHPFOX_DS);
	define('PHPFOX_START_TIME', array_sum(explode(' ', microtime())));
}

defined('PHPFOX_UNIT_TEST') or define('PHPFOX_UNIT_TEST', false);

defined('PHPFOX_PARENT_DIR') or define('PHPFOX_PARENT_DIR', realpath(__DIR__ . '/../') . PHPFOX_DS);

if (!file_exists(__DIR__ . PHPFOX_DS . 'vendor' . PHPFOX_DS . 'autoload.php')) {
	exit('Dependencies for phpFox missing. Make sure to run composer first.');
}

if (isset($_SERVER['REQUEST_METHOD'])) {
	if ($_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'DELETE') {
		parse_str(file_get_contents('php://input'), $_REQUEST);
	}
}

require(dirname(dirname(__FILE__)) . PHPFOX_DS . 'PF.Src' . PHPFOX_DS . 'Core' . PHPFOX_DS . 'Engine.php');
\Core\Engine::ini()->initLoadClass(dirname(dirname(__FILE__)) . PHPFOX_DS . "PF.Base");

/**
 * @param string $element
 *
 * @return \Core\jQuery
 */
function j($element)
{
	return new Core\jQuery($element);
}

/**
 * @param string $key
 *
 * @return mixed
 */
function param($key)
{
	return Phpfox::getParam($key);
}

/**
 * @param null|string $key
 * @param null|string $default
 *
 * @return \Core\Setting|mixed|null
 */
function setting($key = null, $default = null, $acceptEnv = true)
{
	if ($key === null) {
		return Core\Lib::setting();
	}

	$Setting = Core\Lib::setting();

	return $Setting->get($key, $default, $acceptEnv);
}

/**
 * @param string $key
 *
 * @return mixed
 */
function user_group_setting($key)
{
	return Phpfox::getUserParam($key);
}

/**
 * @param null $key
 * @param null $default
 * @param null $userGroupId
 * @param bool $bRedirect
 *
 * @return \Api\User\Objects|\Api\User\Objects[]|mixed
 * @throws \Exception
 */
function user($key = null, $default = null, $userGroupId = null, $bRedirect = false)
{
	if ($key === null) {
		return Core\Lib::apiUser()->get(\Phpfox::getService('user.auth')->getUserSession());
	}

	$Setting = Core\Lib::userSetting();

	return $Setting->get($key, $default, $userGroupId, $bRedirect);
}

/**
 * @return mixed
 */
function phrase()
{
	$Reflect = (new ReflectionClass('Phpfox_Locale'))->newInstanceWithoutConstructor();

	return call_user_func_array([$Reflect, 'phrase'], func_get_args());
}

/**
 * @param string $sVarName
 * @param array $aParam
 * @param string $sLanguageId
 * @param bool $bSkipDebugPhrase
 *
 * @return string
 */
function _p($sVarName = '', $aParam = [], $sLanguageId = '', $bSkipDebugPhrase = false)
{
	return Core\Lib::phrase()->get($sVarName, $aParam, $sLanguageId, $bSkipDebugPhrase);
}

function error()
{
	$Reflect = (new ReflectionClass('Core\Exception'))->newInstanceWithoutConstructor();

	return call_user_func_array([$Reflect, 'toss'], func_get_args());
}

/**
 * @return \Core\Text
 */
function text()
{
	return Core\Lib::text();
}

/**
 * @param string $name
 * @param Closure $callback
 *
 * @return \Core\Route\Group
 */
function group($name, Closure $callback)
{
	return new Core\Route\Group($name, $callback);
}

function register_api($adapter, $array)
{
	\Core\Route::registerApi($adapter, $array);
}

/**
 * @param string $route
 * @param Closure|string $callback
 *
 * @return \Core\Route
 */
function route($route, $callback)
{
	return new Core\Route($route, $callback, true);
}

/**
 * @param string|array $asset
 *
 * @return \Core\Asset
 */
function asset($asset)
{
	return new Core\Asset($asset);
}

/**
 * @param string $str
 *
 * @return \Core\Text\Parse
 */
function parse($str)
{
	return text()->parse($str);
}

/**
 * @param string $route
 * @param int $id
 * @param string $title
 *
 * @return string
 */
function permalink($route, $id, $title)
{
	return \Phpfox_Url::instance()->permalink($route, $id, $title);
}

/**
 * @return \Core\Redis
 */
function redis()
{
	return Core\Lib::redis();
}

/**
 * @param int $location
 * @param Closure $callback
 * @param null|string $controller
 *
 * @return bool|\Core\Block
 */
function block($location, $callback, $controller = null)
{
	if ($controller !== null && is_callable($controller)) {
		return new Core\Block($callback, $location, $controller);
	}

	if (!is_numeric($location)) {
		Core\Block\Group::$blocks[$location] = $callback;
		return true;
	}

	return new Core\Block(null, $location, $callback);
}

/**
 * @param string $name
 * @param Closure $callback
 *
 * @return \Core\Event
 */
function event($name, $callback)
{
	return new Core\Event($name, $callback);
}

/**
 * @return \Core\Storage
 */
function storage()
{
	return Core\Lib::storage();
}

/**
 * @param string $name
 * @param array $params
 *
 * @return \Core\View
 */
function render($name, $params = [])
{
	return Core\Controller::$__view->render($name, $params);
}

/**
 * @param string $name
 * @param array $params
 *
 * @return \Core\View|string
 */
function view($name, $params = [])
{
	return Core\Controller::$__view->view($name, $params);
}

/**
 * @param null|int $id
 *
 * @return \Core\App|\Core\App\Objects
 */
function app($id = null)
{
	$app = Core\Lib::app();

	if ($id != null) {
		return $app->get($id);
	}

	return $app;
}

/**
 * @param null|int $app_id
 *
 * @return string
 */
function home($app_id = null)
{
	if ($app_id !== null) {
		$path = str_replace(PHPFOX_DIR_SITE, home() . 'PF.Site/', app($app_id)->path);
		return $path;
	}

	return setting('core.path_actual');
}

/**
 * @return \Core\Is
 */
function is()
{
	return Core\Lib::is();
}

/**
 * @param null|int $seconds
 *
 * @return \Core\Moment|string
 */
function moment($seconds = null)
{
	$object = Core\Lib::moment();
	if ($seconds !== null) {
		return $object->toString($seconds);
	}

	return $object;
}

/**
 * @return Phpfox_Database_Driver_Mysql
 */
function db()
{
	return \Phpfox_Database::instance();
}

/**
 * @return \Core\Request
 */
function request()
{
	return Core\Lib::request();
}

/**
 * @param string $app_id
 * @param string $key_name
 * @param int $feed_id
 * @param int $user_id
 * @param bool $force
 *
 * @return bool
 */
function notify($app_id, $key_name, $feed_id, $user_id, $force = true)
{
	return Core\Lib::apiNotification()->post($app_id . '/' . $key_name, $feed_id, $user_id, $force);
}

/**
 * @param string $name
 * @param string $url
 *
 * @return \Core\Controller
 */
function section($name, $url)
{
	return Core\Controller::$__self->section($name, $url);
}

/**
 * @param string $title
 * @param string $url
 * @param string $extra
 *
 * @return \Core\Controller
 */
function sectionMenu($title, $url, $extra = '')
{
	return Core\Controller::$__self->sectionMenu($title, $url, $extra);
}

/**
 * @param string $section
 * @param array $menu
 *
 * @return \Core\Controller
 */
function subMenu($section, $menu)
{
	return Core\Controller::$__self->subMenu($section, $menu);
}

/**
 * @param string $title
 * @param string $url
 * @param string $extra
 *
 * @return \Core\Controller
 * @see sectionMenu()
 *
 */
function button($title, $url, $extra = '')
{
	return sectionMenu($title, $url, $extra);
}

/**
 * @param string $section
 * @param array $menu
 *
 * @return \Core\Controller
 * @see subMenu()
 *
 */
function menu($section, $menu)
{
	return subMenu($section, $menu);
}


function flavor()
{
	// Optimize: Cache object this function is called very much
	return Core\Flavor\Flavor::instance();
}

;

/**
 * @param null|string $name
 *
 * @return \Core\Cache
 */
function cache($name = null)
{
	return new Core\Cache($name);
}

/**
 * @param null $route
 * @param array $params
 *
 * @return \Core\Url
 */
function url($route = null, $params = [])
{
	$object = new \Core\Url();
	if ($route !== null && $object != null) {
		return $object->make($route, $params);
	}

	return $object;
}

/**
 * @param string $name
 * @param string $url
 *
 * @return \Core\Controller
 */
function h1($name, $url)
{
	return Core\Controller::$__self->h1($name, $url);
}

/**
 * @param string $title
 *
 * @return \Core\Controller
 */
function title($title)
{
	return Core\Controller::$__self->title($title);
}

/**
 * @return \Core\Auth\User
 */
function auth()
{
	return Core\Lib::authUser();
}

/**
 * @return \Core\Validator
 */
function validator()
{
	return Core\Lib::validator();
}

function resolve_path($file)
{
	return strtr($file, ['\\' => PHPFOX_DS, '//' => PHPFOX_DS]);
}

/**
 * @return \Core\Form
 */
function form()
{
	return Core\Lib::form();
}

/**
 * @return \Core\Search
 */
function search()
{
	return Core\Lib::search();
}

/**
 * @param string|array $name
 * @param \Closure $callback
 * @param int $lifetime default is "0"
 *
 * @return mixed
 */
function get_from_cache($name, \Closure $callback, $lifetime = 0)
{
	$cache = Phpfox_Cache::instance();

	$key = $cache->set(is_array($name) ? implode('_', $name) : $name);

	$data = $cache->getLocalFirst($key, $lifetime);

	if ($data !== false or !$callback) {
		return $data;
	}

	$data = $callback();

	$cache->saveBoth($key, $data);

	return $data;
}

/**
 * @param string $sPath Url or path to content
 * @param boolean $bExit
 *
 * @return string
 */

function fox_get_contents($sPath, $bExit = true)
{
	if (filter_var($sPath, FILTER_VALIDATE_URL) === false) {
		return file_get_contents($sPath);
	} else {
		//use CURL to get
		$ch = curl_init($sPath);

		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 3,
			CURLOPT_TIMEOUT => 30,
		]);

		$content = curl_exec($ch);

		if ($error = curl_errno($ch) && $bExit) {
			exit(curl_error($ch));
		}
		curl_close($ch);
		return $content;
	}
}

/**
 * Replicate file_exists function featuring caching method
 *
 * @staticvar array $cached_list
 * @staticvar boolean $shouldUpdateCache
 * @param string $file_name
 * @param boolean $saveOnExistsOnly - Only save cache when file exists, useful for Template Caching.
 * @param boolean $emptyCache - Set to true to clear all static cached list.
 * @return boolean
 */
function cached_file_exists($file_name = NULL, $saveOnExistsOnly = false, $emptyCache = false)
{
	static $cached_list = [];
	static $shouldUpdateCache = false;

	$sFileExistsCacheId = Phpfox::getLib('cache')->set('file_exists_caching');

	if ($emptyCache) {
		$cached_list = [];
		return;
	}

	// Update cache when calling function without passing a param
	if (empty($file_name)) {
		// Update cache
		if ($shouldUpdateCache) {
			Phpfox::getLib('cache')->saveBoth($sFileExistsCacheId, $cached_list);
		}
		return;
	}
	if ((empty($cached_list) && !($cached_list = Phpfox::getLib('cache')->getLocalFirst($sFileExistsCacheId))) || !isset($cached_list[$file_name])) {
		if (file_exists($file_name)) {
			$cached_list[$file_name] = true;
		} else {
			$cached_list[$file_name] = false;
		}
		if ($saveOnExistsOnly && $cached_list[$file_name] == false) {
			unset($cached_list[$file_name]);
			return false;
		}
		$shouldUpdateCache = true;
	}
	return $cached_list[$file_name];
}

;

register_shutdown_function(function () {
	cached_file_exists();
});

/**
 * This method is for debug only
 * <code>
 * _dump($var1, $var2, ...)
 * </code>
 */
function _dump()
{
	echo '<pre>', var_export(func_get_args(), 1), '</pre>';
	exit;
}

if (!defined('PHPFOX_NO_RUN')) {
	try {
		Core\Lib::app();
		Phpfox::run();
	} catch (\Exception $e) {

		if (\Core\Route\Controller::$isApi) {
			http_response_code(400);
			$content = [
				'error' => [
					'message' => $e->getMessage(),
				],
			];
			header('Content-type: application/json');
			echo json_encode($content, JSON_PRETTY_PRINT);
			exit;
		}

		if (PHPFOX_IS_AJAX_PAGE || Phpfox_Request::instance()->get('is_ajax_post')) {
			header('Content-type: application/json');

			$msg = $e->getMessage();
			if (Phpfox_Request::instance()->get('is_ajax_post')) {
				$msg = '<div class="error_message">' . $msg . '</div>';
			}

			ob_clean();
			echo json_encode([
				'error' => $msg,
			]);
			exit;
		}
		header('Content-type: text/html');

		if (!PHPFOX_DEBUG) {
			new Core\Route('*', function (Core\Controller $controller) {
				http_response_code(400);

				return $controller->render('@Base/layout.html', [
					'content' => '<div class="error_message">Something went wrong here. We have notified the village elders about the issue.</div>',
				]);
			});

			if (($content = (new Core\Route\Controller())->get())) {
				echo $content;
			}

			exit;
		}

		throw new Exception($e->getMessage(), $e->getCode(), $e);
	}
}

/**
 * Debug only. will be removed
 *
 * @param $content
 * @param string $file
 * @param bool $tracer
 */
function debug_log($content, $file = "", $tracer = false)
{
	if (!$_GET['d']) {
		return;
	}

	if ($file == "") {
		$file = "debug.log";
	}

	$file = Phpfox::getParam('core.dir_cache') . $file;
	if (!file_exists($file)) {
		touch($file);
	}

	file_put_contents($file, "/*" . date("Y-m-d H:i:s", PHPFOX_TIME) . " - debug --*/ \r\n\r\n" . var_export($content, true) . "\r\n /* Debug Tracer For Above item \r\n " . ($tracer ? var_export(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10), true) : "") . " \r\n*/", FILE_APPEND);

}

/**
 * PHP print_r Data so its readable
 *
 * @param mixed $mInfo Can be any sort of type that will be outputed by print_r()
 * @see print_r()
 */
function d($mInfo, $bVarDump = false)
{
	$bCliOrAjax = (PHP_SAPI == 'cli');
	(!$bCliOrAjax ? print '<pre style="text-align:left; padding-left:15px;">' : false);
	($bVarDump ? var_dump($mInfo) : print_r($mInfo));
	(!$bCliOrAjax ? print '</pre>' : false);
}

/**
 * Print DATA
 */
function p()
{
	$aArgs = func_get_args();
	$bCliOrAjax = (PHP_SAPI == 'cli');
	foreach ($aArgs as $sStr) {
		print ($bCliOrAjax ? '' : '<pre>') . "{$sStr}" . ($bCliOrAjax ? "\n" : '</pre><br />');
	}
}

function _getenv($key, $type = 'string', $default = null)
{
    static $_all;
    if (!$_all && version_compare(PHP_VERSION, '7.1', '>=')) {
        $_all = getenv();
    } elseif (getenv($key) !== false && !array_key_exists($key, $_all)) {
        $_all[$key] = getenv($key);
    }

    switch ($type) {
        case 'array':
            if (!array_key_exists($key, $_all)) {
                return $default;
            }
            return explode(',', $_all[$key]);
        case 'options':
            $envPrefix = "${key}_";
            $config = [];
            $keys = array_filter(array_keys($_all), function ($str) use ($envPrefix) {
                return strpos($str, $envPrefix) === 0;
            });
            foreach ($keys as $envId) {
                $config[strtolower(str_replace($envPrefix, '', $envId))] = $_all[$envId];
            }
            return empty($config) ? $default : $config;
        case 'multi_options':
            if (!array_key_exists($key, $_all)) {
                return $default;
            }
            $options = $options = array_filter(array_map(function ($str) {
                return trim($str);
            }, explode(',', $_all[$key])), function ($str) {
                return !empty($str);
            });
            if (empty($options)) {
                return $default;
            }
            $result = [];
            // load all keys
            foreach ($options as $id) {
                $envPrefix = "${key}_" . strtoupper($id) . '_';
                $config = [];
                $keys = array_filter(array_keys($_all), function ($str) use ($envPrefix) {
                    return strpos($str, $envPrefix) === 0;
                });
                foreach ($keys as $envId) {
                    $config[strtolower(str_replace($envPrefix, '', $envId))] = $_all[$envId];
                }
                $result[strtolower($id)] = $config;
            }
            return $result;
        case 'int':
            if (!array_key_exists($key, $_all)) {
                return $default;
            }
            return (int)$_all[$key];
        case 'boolean':
        case 'bool':
            if (!array_key_exists($key, $_all)) {
                return $default;
            }
            return !!$_all[$key];
        default:
            if (!array_key_exists($key, $_all)) {
                return $default;
            }
            return $_all[$key];
    }
}


/**
 * Prints error messages. Used with AJAX calls
 */
function e()
{
	$bCliOrAjax = ((PHP_SAPI == 'cli' || (defined('PHPFOX_IS_AJAX') && PHPFOX_IS_AJAX)));
	ob_clean();
	if (!$bCliOrAjax) {
		echo '<link rel="stylesheet" type="text/css" href="theme/adminpanel/default/style/default/css/debug.css?v=' . PHPFOX_TIME . '" />';
	}
	define('PHPFOX_MEM_END', memory_get_usage());
	echo Phpfox_Debug::getDetails();
	exit;
}