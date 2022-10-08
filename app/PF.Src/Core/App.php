<?php

namespace Core;

use Core\App\Objects;
use Core\Route\Controller as RouteController;
use Phpfox_Module;

/**
 * Class App
 *
 * @package Core
 */
class App
{
    static private $refreshed = false;
    /**
     * @var array
     */
    public static $routes = [];

    /**
     * @var array
     */
    public static $activeApps = [];

    /**
     * @var App\App[]
     */
    private static $_apps = [];

    /**
     *
     * @var \Core\App\Objects[]
     */
    private static $_appObjects = [];

    /**
     *
     * @var boolean
     */
    private static $_isLoadingApps = false;

    /**
     * List of core apps/modules. It doesn't display anywhere on backend.
     *
     * @var array
     */
    private $_aCoreApps
        = [
            'like',
            'admincp',
            'api',
            'ban',
            'core',
            'custom',
            'error',
            'language',
            'link',
            'log',
            'page',
            'privacy',
            'profile',
            'report',
            'request',
            'search',
            'theme',
            'user',
        ];

    private $_NotAllowDisable
        = [
            'photo',
            'Core_Photos',
        ];

    public function getAllPsr4Namespace($aRows)
    {
        $allNamespaces = [];
        foreach ($aRows as $row) {
            $sAppId = $row['apps_id'];
            $sAppDir = $row['apps_dir'];

            if (!$sAppDir) {
                $sAppDir = $sAppId;
            }
            $namespace = 'Apps\\' . $sAppId . '\\';
            $allNamespaces[$namespace] = 'PF.Site' . PHPFOX_DS . 'Apps'
                . PHPFOX_DS . $sAppDir;
        }

        return $allNamespaces;
    }

    /**
     * @param $aAppNamespaces
     *
     */
    public function initAutoload($aAppNamespaces)
    {
        $autoloader = include PHPFOX_DIR . 'vendor' . PHPFOX_DS . 'autoload.php';

        if (isset($_REQUEST['rename_on_upgrade']) && !empty($_REQUEST['apps_dir']) && !empty($_REQUEST['apps_id'])) {
            $aAppNamespaces[sprintf('Apps\\%s\\', $_REQUEST['apps_id'])] = 'PF.Site' . PHPFOX_DS . 'Apps' . PHPFOX_DS . $_REQUEST['apps_dir'];
        }

        foreach ($aAppNamespaces as $namespace => $path) {
            $autoloader->addPsr4($namespace, PHPFOX_PARENT_DIR . $path);
        }
    }

    public function __construct($refresh = false)
    {
        if (self::$refreshed)
            $refresh = false;

        $cache = \Phpfox::getLib('cache');
        if (!self::$refreshed && $refresh == false) {
            $settings = $cache->getLocalFirst('app_settings');
            if (is_bool($settings)) {
                self::$refreshed = $refresh = true;
            }
        }

        self::getActivatedApps();

        $base = PHPFOX_DIR_SITE . 'Apps' . PHPFOX_DS;
        if (!is_dir($base)) {
            self::$_apps = [];
            return;
        }

        if (!empty(self::$_apps) && !$refresh) {
            return;
        }

        self::$_apps = [];
        self::$_isLoadingApps = true;

        $allApps = Phpfox_Module::instance()->getAllAppFromDatabase($refresh);

        // Optimize: This line override all autoload class map generate by composer
        $aAppNamespaces = $this->getAllPsr4Namespace($allApps);
        $this->initAutoload($aAppNamespaces);

        $excludeApps = Phpfox_Module::instance()->getExcludedModulesAppsByPackageId(PHPFOX_PACKAGE_ID);
        $hasDisable = false;
        foreach ($allApps as $aApp) {
            $app = $aApp['apps_id'];
            if ((!defined('PHPFOX_TRIAL_MODE') || !PHPFOX_TRIAL_MODE) && in_array($app, $excludeApps)) {
                if (!defined('PHPFOX_INSTALLER')) {
                    if ($aApp['is_active'] == 1) {
                        \Phpfox::getService('admincp.module.process')->updateActivity($app, 0, false, true);
                        $hasDisable = true;
                    }
                }
                continue;
            }

            $appInfo = Lib::appInit($app);

            if (!$appInfo) {
                continue;
            }

            if (!$appInfo->isActive()) {
                continue;
            }

            if (Engine::ini()->isDoneAutoload() === false
                && cached_file_exists($vendor_file = $appInfo->path . 'vendor/autoload.php')) {
                Engine::ini()->addAutoload($vendor_file);
                include_once($vendor_file);
            }

            RouteController::$active = $appInfo->path;
            RouteController::$activeId = $appInfo->id;
            self::$_apps[$appInfo->id] = $appInfo;

            if (file_exists($start_filename = $appInfo->path . 'start.php')) {
                $callback = require_once($start_filename);
                if (is_callable($callback)) {
                    $View = new View();
                    $viewEnv = null;
                    if (is_dir($appInfo->path . 'views/')) {
                        $View->loader()->addPath($appInfo->path . 'views/', $appInfo->id);
                        $viewEnv = $View->env();
                    }
                    call_user_func($callback, $this->get($appInfo->id),
                        $viewEnv);
                }
            }

            if (isset($appInfo->routes)) {
                foreach ((array)$appInfo->routes as $key => $route) {
                    $orig = $route;
                    $route = (array)$route;
                    $route['id'] = $appInfo->id;
                    if (is_string($orig)) {
                        $route['url'] = $orig;
                    }
                    Route::$routes = array_merge(Route::$routes,
                        [$key => $route]);
                }
            }

        }
        // clear cache
        if ($hasDisable) {
            \Phpfox::getLib('cache')->remove();
            \Phpfox::getLib('template.cache')->remove();
            \Phpfox::getLib('cache')->removeStatic();
        }

        $settings = [];
        self::$_isLoadingApps = false;

        foreach ($this->all() as $app) {
            if ($app->blocks) {
                $blocks = [];
                foreach ($app->blocks as $block) {
                    $blocks[$block->route][$block->location][]
                        = $block->callback;
                }
                \Core\Block\Group::make($blocks);
            }


            if ($refresh && $app->settings) {
                foreach (json_decode(json_encode($app->settings), true) as $key => $value) {
                    $thisValue = (isset($value['value']) ? $value['value'] : null);
                    $value = (new \Core\Db())->select('*')->from(':setting')->where(['var_name' => $key])->get();
                    if (isset($value['value_actual'])) {
                        $thisValue = \Phpfox::getLib('setting')->getActualValue($value['type_id'], $value['value_actual']);
                    }
                    $settings[$key] = $thisValue;
                }
            }
        }

        if ($refresh && $settings) {
            new Setting($settings);
            $cache->saveBoth('app_settings', $settings);
        }

        if (function_exists('flavor')) {
            $forceFlavor = \Phpfox_Request::instance()->get('force-flavor');
            if ($forceFlavor) {
                flavor()->set_active($forceFlavor);
            }
            if (flavor()->active) {
                $start = flavor()->active->path . 'start.php';
                if (file_exists($start)) {
                    require_once($start);
                }
            }
        }
    }

    public static function isAppActive($sAppId)
    {
        if (empty(self::$activeApps)) {
            self::getActivatedApps();
        }

        if (!empty(self::$activeApps)) {
            if (in_array($sAppId, self::$activeApps) && !empty(self::$_apps[$sAppId])) {
                $appObj = self::$_apps[$sAppId];
                return !empty($appObj->alias) ? \Phpfox::isModule($appObj->alias) : true; // check module active
            }
        }
        return false;
    }

    public static function getActivatedApps()
    {
        /* Cache all active apps */
        if (empty(self::$activeApps)) {
            $oCache = \Phpfox::getLib('cache');
            $iCachedId = $oCache->set('app_is_active');
            if (empty(self::$activeApps = $oCache->getLocalFirst($iCachedId))) {
                $aActiveApps = db()->select('apps_id')
                    ->from(':apps')
                    ->where('is_active=1')
                    ->execute('getSlaveRows');
                $excludeApps = Phpfox_Module::instance()->getExcludedModulesAppsByPackageId(PHPFOX_PACKAGE_ID);
                foreach ($aActiveApps as $index => $aActiveApp) {
                    if (in_array($aActiveApp['apps_id'], $excludeApps)) {
                        unset($aActiveApps[$index]);
                    }
                }

                self::$activeApps = array_map(function ($v) {
                    return $v['apps_id'];
                }, $aActiveApps);
                $oCache->saveBoth($iCachedId, self::$activeApps);
            }
        }
    }

    public function vendor()
    {

    }

    public function add($id)
    {
        if (is_string($id)) {
            $app = Lib::appInit($id);
        } elseif ($id instanceof Objects) {
            $app = $id;
        }

        if (!$app) {
            exit("Apps not found  [$id]");
        }

        return self::$_apps[$app->id] = $app;
    }

    public function make($name)
    {
        ignore_user_abort(true);

        $base = PHPFOX_DIR_SITE . 'Apps/';
        $gitFile = null;

        if (!preg_match('/^[a-zA-Z\_][a-zA-Z\_0-9]+$/', $name)) {
            throw new \Exception(_p('app_name_validation_message'));
        }


        $appBase = $base . $name . '/';
        if (is_dir($appBase)) {
            throw new \Exception('App already exists.');
        }

        $dirs = [
            'Block',
            'Controller',
            'Service',
            'assets',
            'hooks',
            'views',
        ];
        foreach ($dirs as $dir) {
            $path = $appBase . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
        }

        \Core\App\Migrate::migrate($name, true);

        file_put_contents($appBase . 'assets/autoload.js',
            "\n\$Ready(function() {\n\n});");
        file_put_contents($appBase . 'assets/autoload.css', "\n");
        file_put_contents($appBase . 'start.php', "<?php\n");

        $lockPath = $appBase . 'app.lock';
        $lock = json_encode(['installed' => PHPFOX_TIME, 'version' => 0],
            JSON_PRETTY_PRINT);
        file_put_contents($lockPath, $lock);

        (new \Core\Cache())->purge();

        $App = new App(true);

        $Object = $App->get($name);

        return $Object;
    }

    /**
     * @param null $zip
     *
     * @param bool $download
     * @param bool $isUpgrade
     *
     * @return Object
     * @throws \Exception
     */
    public function import($zip = null, $download = false, $isUpgrade = false)
    {
        if ($zip === null || empty($zip)) {
            $zip = PHPFOX_DIR_FILE . 'static' . PHPFOX_DS . 'import-' . uniqid()
                . '.zip';
            register_shutdown_function(function () use ($zip) {
                unlink($zip);
            });

            if (isset($_FILES['ajax_upload'])) {
                file_put_contents($zip,
                    file_get_contents($_FILES['ajax_upload']['tmp_name']));
            } else {
                file_put_contents($zip, file_get_contents('php://input'));
            }
        }

        if ($download) {
            $zipUrl = $zip;
            $zip = PHPFOX_DIR_FILE . 'static' . PHPFOX_DS . 'import-' . uniqid()
                . '.zip';
            register_shutdown_function(function () use ($zip) {
            });

            file_put_contents($zip, fox_get_contents($zipUrl));
        }

        $archive = new \ZipArchive();
        $archive->open($zip);
        $json = $archive->getFromName('/Install.php');

        if (!$json) {
            $json = $archive->getFromName('Install.php');
        }

        if (!$json) {
            $json = $archive->getFromName('\\Install.php');
        }

        $json = json_decode($json);
        if (!isset($json->id)) {
            throw error(_p('Not a valid App to install.'));
        }

        $base = PHPFOX_DIR_SITE . 'Apps/' . $json->id . '/';
        if (!is_dir($base)) {
            mkdir($base, 0777, true);
        }

        $archive->close();
        $appPath = $base . 'import-' . uniqid() . '.zip';
        copy($zip, $appPath);

        $newZip = new \ZipArchive();
        $newZip->open($appPath);
        $newZip->extractTo($base);
        $newZip->close();

        register_shutdown_function(function () use ($appPath) {
            unlink($appPath);
        });

        $check = $base . 'app.json';
        if (!file_exists($check)) {
            throw new \Exception('App was unable to install.');
        }

        $lockPath = $base . 'app.lock';
        if (!$isUpgrade && file_exists($lockPath)) {
            unlink($lockPath);
        }

        if (file_exists($lockPath)) {
            $lock = json_decode(file_get_contents($lockPath));
            $lock->updated = PHPFOX_TIME;
            file_put_contents($lockPath, json_encode($lock, JSON_PRETTY_PRINT));
        } else {
            $lock = json_encode([
                'installed' => PHPFOX_TIME,
                'version' => $json->version,
            ], JSON_PRETTY_PRINT);
            file_put_contents($lockPath, $lock);
        }

        $CoreApp = \Phpfox::getCoreApp(true);
        $Object = $CoreApp->get($json->id);

        return $Object;
    }

    public function processUpgrade($json, $base)
    {
        if (file_exists($base . 'installer.php')) {
            \Core\App\Installer::$method = 'onInstall';
            \Core\App\Installer::$basePath = $base;

            require_once($base . 'installer.php');
        }
    }

    /**
     * @param $id
     *
     * @return App\Objects|null
     */
    public function getByInternalId($id)
    {
        foreach ($this->all() as $app) {
            if ($app->internal_id == $id) {
                return $app;
            }
        }
        return null;
    }

    /**
     * @param string $id
     *
     * @return App\Objects
     * @throws \Exception
     */
    public function get($id)
    {
        if (substr($id, 0, 9) == '__module_') {
            $id = substr_replace($id, '', 0, 9);
            // $db = new \Core\Db();
            /** @var \Phpfox_Database $db */
            $db = \Phpfox::getLib("database");
            $module = $db->select('m.*')
                ->from(':module', 'm')
                ->where(['m.module_id' => $id])
                ->execute('getRow');

            if ($module['product_id'] == 'phpfox') {
                $module['version'] = \Phpfox::getVersion();
            }

            $menus = unserialize($module['menu']);
            $aDefaultRoute = ['admincp'];
            if (is_array($menus)) {
                $bHasDefault = !empty(array_column($menus, 'default'));
                foreach ($menus as $menu) {
                    if (($bHasDefault && !empty($menu['default']))
                        || (!$bHasDefault && !empty($menu['url'][1]) && $menu['url'][1] == 'setting')
                    ) {
                        $aDefaultRoute = array_merge($aDefaultRoute, $menu['url']);

                        break;
                    }
                }
            }

            $app = [
                'id' => '__module_' . $id,
                'name' => ($module['phrase_var_name'] && ($module['product_id'] != 'phpfox')) ? _p($module['phrase_var_name']) : \Phpfox_Locale::instance()->translate($id, 'module'),
                'path' => null,
                'is_active' => $module['is_active'],
                'module_id' => $id,
                'is_module' => true,
                'version' => $module['version'],
                'icon' => (!empty($module['apps_icon'])) ? $module['apps_icon'] : null,
                'vendor' => (!empty($module['vendor'])) ? $module['vendor'] : null,
                'admincp_route' => count($aDefaultRoute) > 1 ? implode('/', $aDefaultRoute) : ''
            ];
        } elseif (!isset(self::$_apps[$id])) { // _apps is apps active
            // try check is disabled app
            $app = Lib::appInit($id);
            if (!$app) {
                throw new \Exception(sprintf('App not found "%s"', $id));
            }
        } else {
            $app = self::$_apps[$id];
        }
        $oAppObject = new App\Objects($app);
        $oAppObject->allow_disable = (in_array($id, $this->_NotAllowDisable)) ? false : true;
        return $oAppObject;
    }

    /**
     * @param bool|string $includeModules
     *
     * @return App\Objects[]
     */
    public function all($includeModules = false)
    {
        if (!empty(self::$_appObjects)) {
            return self::$_appObjects;
        }
        $apps = [];
        if ($includeModules) {
            $modules = Phpfox_Module::instance()->all();
            $skip = $this->_aCoreApps;
            foreach ($modules as $module_id) {
                if (in_array($module_id, $skip)) {
                    continue;
                }

                $coreFile = PHPFOX_DIR_MODULE . $module_id
                    . '/install/version/v3.phpfox';
                if ($includeModules == '__core') {
                    if (!file_exists($coreFile)) {
                        continue;
                    }
                } else {
                    if ($includeModules == '__not_core'
                        || $includeModules == '__remove_core'
                    ) {
                        if (file_exists($coreFile)) {
                            continue;
                        }
                    }
                }

                $aModule = \Phpfox::getService('admincp.module')
                    ->getForEdit($module_id);
                if ($aModule['phrase_var_name'] == 'module_apps') {
                    continue;
                }
                $aProduct = ($aModule && !empty($aModule['product_id']))
                    ? \Phpfox::getService('admincp.product')
                        ->getForEdit($aModule['product_id']) : [];
                $app = [
                    'id' => '__module_' . $module_id,
                    'name' => ($aProduct
                        && ($aModule['product_id'] != 'phpfox')
                        && $aProduct['title'])
                        ? $aProduct['title']
                        : \Phpfox_Locale::instance()
                            ->translate($module_id, 'module'),
                    'path' => null,
                    'is_module' => true,
                    'icon' => (!empty($aProduct['icon']))
                        ? $aProduct['icon'] : null,
                    'vendor' => (!empty($aProduct['vendor']))
                        ? $aProduct['vendor'] : null,
                ];

                $apps[] = new App\Objects($app);
            }

            if ($includeModules == '__core'
                || $includeModules == '__not_core'
            ) {
                return $apps;
            }
        }

        foreach (self::$_apps as $app) {
            $apps[] = new App\Objects($app);
        }

        if (!self::$_isLoadingApps) {
            self::$_appObjects = $apps;
        }

        return $apps;
    }

    /**
     * Whether or not system is loading settings from external apps, if so, this can be used as a flag to prevent \Core\Setting from being cached
     *
     * @return boolean
     */
    public function isLoadingApps()
    {
        return self::$_isLoadingApps;
    }

    public function processRow($app)
    {
        if ($app['type'] == 'module') {
            $oAppDetail = [
                'id' => '__module_' . $app['id'],
                'name' => _p($app['name']),
                'path' => null,
                'is_module' => true,
                'is_active' => $app['is_active'],
                'icon' => (!empty($app['icon'])) ? $app['icon'] : null,
                'vendor' => (!empty($app['vendor'])) ? $app['vendor']
                    : null,
                'publisher' => $app['publisher'],
                'version' => $app['version'],
            ];
        } else if (!empty(self::$_apps[$app['id']])) {
            $oAppDetail = self::$_apps[$app['id']];
        } else {
            $oAppDetail = Lib::appInit($app['id']);
        }

        $oAppObject = new App\Objects($oAppDetail);
        $oAppObject->version = $app['version'];
        $oAppObject->allow_disable = (in_array($app['id'], $this->_NotAllowDisable)) ? false : true;
        if (!empty($app['publisher'])) {
            $oAppObject->publisher = $app['publisher'];
        }
        $oAppObject->publisher_url = $app['vendor'];
        return $oAppObject;
    }

    /**
     * Get all modules and apps (included disabled)
     *
     * @return array
     */
    public function getForManage()
    {
        return get_from_cache('apps_for_manage', function () {
            $sCoreApps = implode("','", $this->_aCoreApps);
            $oDb = db();
            $oDb->select('apps_icon as icon, module_id AS id, version, author as publisher, vendor, phrase_var_name as name, is_active, \'module\' AS type')
                ->from(":module")
                ->where("module_id NOT IN ('" . $sCoreApps
                    . "') AND phrase_var_name!='module_apps'")
                ->union();
            $oDb->select('apps_icon as icon, apps_id as id, version, author as publisher, vendor,  apps_name as name, is_active, \'app\' AS type')
                ->from(':apps')
                ->union();

            $rows = array_map(function ($item) {
                return $this->processRow($item);
            }, $oDb->executeRows());

            uasort($rows, function ($a, $b) {
                return (int)(strtolower($a->name) > strtolower($b->name));
            });
            return $rows;
        });
    }

    public function exists($id, $bReturnId = false)
    {
        return (isset(self::$_apps[$id]) ? ($bReturnId ? $id : true) : false);
    }

    /**
     * @param $id
     * @return Objects|null
     */
    public function getApp($id)
    {
        if (isset(self::$_apps[$id])) {
            return new App\Objects(self::$_apps[$id]);
        }
        return null;
    }
}