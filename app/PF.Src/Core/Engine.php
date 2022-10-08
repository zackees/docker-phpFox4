<?php


namespace Core;
use josegonzalez\Dotenv\Loader as EnvLoader;


/**
 * Class Engine
 * @package Core
 *
 * This cash use to init load and monitoring system when running
 *
 */
class Engine
{
    private static $self = null;

    private $autoloadFiles;

    private $apcuPrefix;

    private $doneAutoload = false;

    private $manageApiRoutes = [];

    private $reloadInit = false;


    public function __construct()
    {
        register_shutdown_function([$this, 'transactionClose']);
    }


    public function apiRouteAppend($routeName, $routeUrl)
    {
        $this->manageApiRoutes[$routeName] = $routeUrl;
    }

    public static function ini()
    {
        if (!self::$self) {
            self::$self = new self();
        }

        return self::$self;
    }

    /**
     * Support autoload multiple composer vendor in Appended Order
     * @param $baseDir
     */
    public function initLoadClass($baseDir)
    {
        try {
            if (!defined('PHPFOX_IS_UPGRADE')
                && !defined('PHPFOX_INSTALLER')
                && defined('PHPFOX_CACHE_AUTOLOAD')
                && PHPFOX_CACHE_AUTOLOAD) {
                $this->apcuPrefix = (Engine::apcuEnabled() ? Engine::getSecureCacheKey() : null);
            }

            if (null !== $this->apcuPrefix) {
                $files = apcu_fetch($this->apcuPrefix . 'autoload', $hit);
                if ($hit) {
                    $this->autoloadFiles = $files;
                }
            }

            if ($this->autoloadFiles === null) {
                require($baseDir . PHPFOX_DS . "vendor" . PHPFOX_DS . "autoload.php");
            } else {
                foreach ($this->autoloadFiles as $file) {
                    require $file;
                }
                require($baseDir . PHPFOX_DS . "vendor" . PHPFOX_DS . "autoload.php");

                $this->doneAutoload = true;
            }
            require($baseDir . PHPFOX_DS . "include" . PHPFOX_DS . "init.inc.php");
        }
        catch (\Exception $e) {
            // Try to reload
            if ($this->reloadInit == false) {
                $this->autoloadFiles = null;
                Engine::flushApcu(Engine::getSecureCacheKey());
                $this->apcuPrefix = null;
                $this->reloadInit =  true;
                $this->initLoadClass($baseDir);
            }
            else {
                exit('Cannot load Dependencies for the platform. Make sure to run composer first. Error message: ' . $e->getMessage());
            }
        }
    }

    public function addAutoload($file)
    {
        $this->autoloadFiles[] = $file;
    }

    /**
     * Monitor shutdown function and cache core objects
     *
     */
    public function transactionClose()
    {
        // Saving autoload cache
        if (null !== $this->apcuPrefix && !(apcu_exists($this->apcuPrefix. 'autoload'))) {
            apcu_add($this->apcuPrefix . 'autoload', $this->autoloadFiles);
        }

        if (!empty($this->manageApiRoutes)) {
            /** @var \Phpfox_Cache_Storage_Driver $cache */
            $cache = \Phpfox::getLib("cache");
            $cache->saveBoth($cache->set('manage_api_routes'), $this->manageApiRoutes);
        }

    }

    /**
     * Check is finish cache autoload files
     * @return bool
     */
    public function isDoneAutoload()
    {
        return $this->doneAutoload;
    }

    public function getRouteMatch($uri, &$matched, $prefix = '')
    {
        /** @var \Phpfox_Cache_Storage_Driver $cache */
        $cache = \Phpfox::getLib("cache");
        $cacheId = $cache->set('core_route_'.$prefix . md5($uri));
        $routeName = $cache->getLocalFirst($cacheId, 3600) ;
        if ($routeName === false || $routeName === null) {
            $matched = false;
            return null;
        }

        $matched = true;
        return $routeName;
    }

    public function setRouteMatch($uri, $routeName, $prefix = '')
    {
        /** @var \Phpfox_Cache_Storage_Driver $cache */
        $cache = \Phpfox::getLib("cache");
        $cacheId = $cache->set('core_route_'.$prefix . md5($uri));
        $cache->saveBoth($cacheId, $routeName,3600) ;

    }

    public static function flushCache($prefix = null)
    {
        if (Engine::apcuEnabled()) {
            Engine::flushApcu($prefix);
        }
        if (Engine::opcacheEnabled()) {
            @opcache_reset();
        }
    }

    public static function flushApcu($prefix = null)
    {
        if (Engine::apcuEnabled()) {
            if (class_exists('\APCUIterator') && $prefix != null) {
                $listCache = new \APCUIterator('/^' . $prefix. '/');
                foreach($listCache as $apcu_cache) {
                    apcu_delete($apcu_cache['key']);
                }
            }
            else {
                @apcu_clear_cache();
            }
        }
    }

    public static function flushOpcache()
    {
        if (Engine::opcacheEnabled()) {
            @opcache_reset();
        }
    }

    public static function apcuEnabled()
    {
        return (function_exists('apcu_clear_cache') && ini_get('apc.enabled'));
    }

    public static function opcacheEnabled()
    {
        return (ini_get('opcache.enable') && function_exists("opcache_reset"));
    }

    /**
     * @var string manage cache key prefix
     */
    private static $secureCacheKey;

    /**
     * @return string
     */
    public static function getSecureCacheKey()
    {
        if (!self::$secureCacheKey) {
            self::$secureCacheKey = substr(md5(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "phpfox"), 0, 4) . "_";
        }
        return self::$secureCacheKey;
    }
}