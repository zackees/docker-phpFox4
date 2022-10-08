<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

use phpFastCache\CacheManager;

/**
 * Class is used to cache any sort of data that is passed via PHP.
 * Currently there is support for file based caching and memcached.
 * It is perfect to store output from SQL queries that do not need to be executed
 * each time a user visits a specific page.
 *
 * Example of using the cache system:
 * <code>
 * $oCache = Phpfox::getLib('cache');
 * // Create a name for your cache file
 * $sCacheId = $oCache->set('cache_file_name');
 * // Check if the file is already cached
 * if (!($aData = $oCache->get($sCacheId)))
 * {
 *        // Run SQL query here...
 *        $aData = array(1, 2, 3, 4);
 *        // Store data in the the cache file (eg. strings, arrays, bool, objects etc...)
 *        $oCache->save($sCacheId, $aData);
 * }
 * print_r($aData);
 * </code>
 *
 * If you want to remove a cache file:
 * <code>
 * Phpfox::getLib('cache')->remove('cache_file_name');
 * </code>
 *
 * If you want to get all the files that have been cached:
 * <code>
 * // Array of files.
 * $aFiles = Phpfox::getLib('cache')->getCachedFiles();
 * </code>
 *
 * @copyright        [PHPFOX_COPYRIGHT]
 * @author            phpFox LLC
 * @package        Phpfox
 * @version        $Id: cache.class.php 1666 2010-07-07 08:17:00Z phpFox LLC $
 */
class Phpfox_Cache
{
    /**
     * Object of the storage class.
     *
     * @var object|Phpfox_Cache_Storage_Driver
     */
    private static $_oObject = null;

    /**
     * If redis or memcache is enabled. Local cache is used and auto select between Files, Apcu or Xcache
     * base on your system. Recommend install Apcu extension
     *
     * @var null|Phpfox_Cache_Storage_Driver
     */
    private static $_oLocalCache = null;

    /**
     * Drive name (file/redis/memcached)
     * @var null|string
     */
    private static $_driver = null;

    private static $_cacheDomain = null;

    public static function unitCacheDomain()
    {
        if (self::$_cacheDomain === null) {
            self::$_cacheDomain = substr(md5(Phpfox::getParam('core.host') ? (Phpfox::getParam('core.host') . Phpfox::getParam('core.path')) : "phpfox"), 0, 4) . "_";
        }
        return self::$_cacheDomain;

    }

    /**
     * Based on what storage system is set within the global settings this is where we load the file.
     * You can also pass any params to the storage object.
     *
     * @param array $aParams Any extra params you may want to pass to the storage object.
     * @throws \phpFastCache\Exceptions\phpFastCacheDriverCheckException
     * @throws \phpFastCache\Exceptions\phpFastCacheInvalidConfigurationException
     */
    public function __construct($aParams = [])
    {
        $sCacheDirBase = Phpfox::getParam('core.dir_cache') . (PHPFOX_DEBUG == false ? "prod" : "dev") . PHPFOX_DS;
        $localCacheDir = $sCacheDirBase . 'cl' . PHPFOX_DS;
        $shareCacheDir = $sCacheDirBase . 'cs' . PHPFOX_DS;

        if (!is_dir($sCacheDirBase)) {
            mkdir($sCacheDirBase, 0777, true);
        }
        if (!is_dir($localCacheDir)) {
            mkdir($localCacheDir, 0777, true);
        }
        if (!is_dir($shareCacheDir)) {
            mkdir($shareCacheDir, 0777, true);
        }

        if (!defined('PHPFOX_DIR_CACHE')) {
            define('PHPFOX_DIR_CACHE', $sCacheDirBase);
        }

        if (!self::$_oObject) {
            $driver = 'file';
            $cache_file = null;
            $cache = null;
            if (!defined("PHPFOX_CACHE_FORCE_FILE_CACHE") || PHPFOX_CACHE_FORCE_FILE_CACHE == false) {
                if (file_exists(PHPFOX_DIR_SETTINGS . 'cache.sett.php')) {
                    $cache_file = require(PHPFOX_DIR_SETTINGS . 'cache.sett.php');
                    $driver = $cache_file['driver'];
                }

                // get overwrite config from server.sett.php
                if ($tmp_driver = Phpfox::getLib('setting')->getFromServerConfigFile('cache.driver')) {
                    $cache_file['driver'] = $driver = $tmp_driver;
                    if ($host = Phpfox::getLib('setting')->getFromServerConfigFile('cache.host')) {
                        if ($driver == 'redis') {
                            $cache_file['redis']['host'] = $host;
                        } elseif ($driver == 'memcached') {
                            $cache_file['memcached'][0][0] = $host;
                        }
                    }
                    if ($port = Phpfox::getLib('setting')->getFromServerConfigFile('cache.port')) {
                        if ($driver == 'redis') {
                            $cache_file['redis']['port'] = $port;
                        } elseif ($driver == 'memcached') {
                            $cache_file['memcached'][0][1] = $port;
                        }
                    }
                    if ($auth_user = Phpfox::getLib('setting')->getFromServerConfigFile('cache.auth_user')) {
                        if ($driver == 'redis') {
                            $cache_file['redis']['auth_user'] = $auth_user;
                        }
                    }
                    if ($auth_pass = Phpfox::getLib('setting')->getFromServerConfigFile('cache.auth_pass')) {
                        if ($driver == 'redis') {
                            $cache_file['redis']['auth_pass'] = $auth_pass;
                        }
                    }
                }

                try {
                    switch ($driver) {
                        case 'redis':
                            if (!isset($cache_file['redis'])) {
                                throw new \Exception('Redis not set.');
                            }

                            if (empty($cache_file['redis']['host']) || empty($cache_file['redis']['port'])) {
                                throw new \Exception('No host/port set.');
                            }

                            $redisConfig = $cache_file['redis'];
                            $redisConfig['securityKey'] = self::unitCacheDomain();
                            if (extension_loaded('redis')) {
                                $cache = CacheManager::getInstance('redis', $redisConfig);
                            } else {
                                $cache = CacheManager::getInstance('predis', $redisConfig);
                            }
                            break;
                        case 'memcached':
                            if (!isset($cache_file['memcached'])) {
                                throw new \Exception('Memcache not set.');
                            }

                            if (!isset($cache_file['memcached'][0])) {
                                throw new \Exception('Missing server details for Memcache');
                            }

                            foreach ($cache_file['memcached'][0] as $value) {
                                if (empty($value)) {
                                    throw new \Exception('Memcache server value not set.');
                                }
                            }

                            $cache = CacheManager::getInstance('Memcached', [
                                    'servers' => [
                                        [
                                            'host' => $cache_file['memcached'][0][0],
                                            'port' => $cache_file['memcached'][0][1]
                                        ]
                                    ],
                                    'securityKey' => self::unitCacheDomain()
                                ]
                            );

                            break;
                        default:
                            if (function_exists('apcu_fetch') && ini_get('apc.enabled')) {
                                $cache = CacheManager::getInstance('Apcu');
                            } else {
                                $cache = CacheManager::getInstance('Files', [
                                    'path' => $shareCacheDir,
                                    'default_chmod' => 0777,
                                    'securityKey' => self::unitCacheDomain()
                                ]);
                            }
                            $driver = 'file';
                    }
                } catch (\Exception $e) {
                    if (defined("PHPFOX_DEBUG") && PHPFOX_DEBUG == true) {
                        echo "Cannot connect to cache server: " . $e->getMessage();
                        exit();
                    }

                    if (function_exists('apcu_fetch') && ini_get('apc.enabled')) {
                        $cache = CacheManager::getInstance('Apcu');
                    } else {
                        $cache = CacheManager::getInstance('Files', [
                            'path' => $shareCacheDir,
                            'default_chmod' => 0777,
                            'securityKey' => self::unitCacheDomain()
                        ]);
                    }
                    $driver = 'file';
                }
            } else {
                if (function_exists('apcu_fetch') && ini_get('apc.enabled')) {
                    $cache = CacheManager::getInstance('Apcu');
                } else {
                    $cache = CacheManager::getInstance('Files', [
                        'path' => $shareCacheDir,
                        'default_chmod' => 0777,
                        'securityKey' => self::unitCacheDomain()
                    ]);
                }
            }

            // try get item to check AUTH connection
            try {
                if ($driver !== "file") {
                    $cache->getItem('check');
                }
            }
            catch (Exception $e) {
                if (function_exists('apcu_fetch') && ini_get('apc.enabled')) {
                    $cache = CacheManager::getInstance('Apcu');
                } else {
                    $cache = CacheManager::getInstance('Files', [
                        'path' => $shareCacheDir,
                        'default_chmod' => 0777,
                        'securityKey' => self::unitCacheDomain()
                    ]);
                }
                $driver = 'file';
            }

            $skipCache = (Phpfox::getParam('core.cache_skip') || defined('PHPFOX_INSTALLER'));
            $aParams['driver'] = $cache;
            $aParams['skip_cache'] = $skipCache;
            self::$_driver = $driver;
            self::$_oObject = new Phpfox_Cache_Storage_Driver($aParams);

            // Setup Local cache
            if ($driver !== "file") {
                if (\Core\Engine::apcuEnabled()) {
                    self::$_oLocalCache = new Phpfox_Cache_Storage_Driver([
                        'driver' => CacheManager::getInstance('Apcu'),
                        'skip_cache' => $skipCache,
                        'expire_time' => 3600, // 5 minutes cache for local cache
                        'is_share_cache' => false
                    ]);
                } else {
                    self::$_oLocalCache = new Phpfox_Cache_Storage_Driver([
                        'driver' => CacheManager::getInstance('Files', [
                            'path' => $localCacheDir,
                            'default_chmod' => 0777,
                            'securityKey' => self::unitCacheDomain()
                        ]),
                        'skip_cache' => $skipCache,
                        'expire_time' => 3600, // 5 minutes cache for local cache
                        'is_share_cache' => false
                    ]);
                }

            } else {
                // No local cache
                self::$_oLocalCache = new Phpfox_Cache_Storage_Driver([
                    'driver' => null,
                    'skip_cache' => true,
                    'is_share_cache' => false
                ]);
            }

            self::$_oObject->setLocalCache(self::$_oLocalCache);
        }
    }

    public function factory()
    {
        return self::$_oObject;
    }

    /**
     * Return the object of the storage object.
     *
     * @return object Object provided by the storage class we loaded earlier.
     */
    public function &getInstance()
    {
        return self::$_oObject;
    }

    /**
     * @return null|string
     */
    public static function driver()
    {
        return self::$_driver;
    }

    /**
     *
     * Support local cache layer use to reduce call to Memcached or Redis cache for better performance
     *
     * Local cache only available when using Memcached or Redis cache
     *
     * @return Phpfox_Cache_Storage_Driver
     */
    public static function instance()
    {
        if (!self::$_oObject) {
            new self();
        }

        return self::$_oObject;
    }
}