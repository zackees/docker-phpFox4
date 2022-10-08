<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Phpfox_Cache_Storage_Driver extends Phpfox_Cache_Abstract
{
    const FEEDS = 'feeds';
    const CACHE_VERSION = "cache_version";
    const CACHE_REMOVED = "cache_removed";

    /**
     * Array of all the cache files we have saved.
     *
     * @var array
     */
    private $_aName = [];

    /**
     * If redis or memcache is enabled. Local cache is used and auto select between Files, Apcu or Xcache
     * base on your system. Recommend install Apcu extension for best performance
     * @var self
     */
    private $localCache;

    private $defaultExpireTime = 43200; // 1 month


    /**
     * By default we always close a cache call automatically, however at times
     * you may need to close it at a later time and setting this to true will
     * skip closing the closing of the cache reference.
     *
     * @var bool
     *
     */
    private $_bSkipClose = false;

    /**
     * @var \phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface
     */
    private $_driver;

    /**
     * Enable/Disable cache
     * @var bool|mixed
     */
    private $skipCache;

    /**
     * @var bool Is remote share cache or local cache
     */
    private $isShareCache;

    /**
     * Get Cache driver for access more function of cache
     *
     * @return mixed|\phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface
     */
    public function getDrive()
    {
        return $this->_driver;
    }

    public function getAdapterName()
    {
        if ($this->_driver) {
            return $this->_driver->getDriverName();
        }
        return null;
    }

    public function setup($params = [])
    {
        if (isset($params['driver'])) {
            $this->_driver = $params['driver'];
        }
    }

    public function __construct($params = [], $object = null)
    {
        parent::__construct($params);

        if (isset($params['driver'])) {
            $this->_driver = $params['driver'];
        }
        if (isset($params['expire_time'])) {
            $this->defaultExpireTime = (int)$params['expire_time'];
        }

        $this->skipCache = (isset($params['skip_cache']) ? $params['skip_cache'] : false);
        $this->isShareCache = (isset($params['is_share_cache']) ? $params['is_share_cache'] : true);

        // Reset local cache
        if ($this->isShareCache == true && $this->getLocalCache()) {
            $masterVersion = $this->get($this->set(self::CACHE_VERSION));
            $localVersion = $this->getLocalCache()->get($this->set(self::CACHE_VERSION));
            if ($masterVersion && $masterVersion != $localVersion) {
                $this->getLocalCache()->remove();
                $this->getLocalCache()->save($this->set(self::CACHE_VERSION), $masterVersion);
            }

            $cacheSync = $this->get($this->set(self::CACHE_REMOVED));
            if (!empty($cacheSync)) {
                $this->getLocalCache()->remove($cacheSync);
            }
        }
    }

    /**
     * Sets the name of the cache.
     *
     * @param string|array $sName Unique fill name of the cache.
     * @param string $sGroup Optional param to identify what group the cache file belongs to
     * @return string Returns the unique ID of the file name
     */
    public function set($sName, $sGroup = '')
    {
        if (is_array($sName)) {
            $sName = str_replace(['/', PHPFOX_DS], '_', $sName[0]) . '_' . $sName[1];
        }
        $sId = $sName;
        $this->_aName[$sId] = $sName;

        return $sId;
    }

    /**
     * By default we always close a cache call automatically, however at times
     * you may need to close it at a later time and setting this to true will
     * skip closing the closing of the cache reference.
     *
     * @param bool $bClose True to skip the closing of the connection
     * @return object Returns the classes object.
     * @deprecated from 4.7.0
     */
    public function skipClose($bClose)
    {
        $this->_bSkipClose = $bClose;

        return $this;
    }

    /**
     * Optimize: This param help reduce cache call in same context
     * @var array
     */
    private $contextCacheData = [];

    /**
     * Get cache data. if cache not saved on local
     * This method support remote and local cache
     * @param $sId
     * @param int $iLocalTimeOut
     * @return bool|mixed
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getLocalFirst($sId, $iLocalTimeOut = 0)
    {
        if ($this->skipCache) {
            return false;
        }

        $cacheData = false;
        if ($this->getLocalCache()) {
            $cacheData = $this->getLocalCache()->get($sId, $iLocalTimeOut);
        }

        if ($cacheData === false) {
            $cacheData = $this->get($sId, $iLocalTimeOut);

            // Remote cached but local has not. Should cache it
            if ($cacheData !== false && $this->getLocalCache()) {
                $this->getLocalCache()->save($sId, $cacheData, $iLocalTimeOut);
            }
        }

        return $cacheData;
    }

    /**
     *
     * Save cache on both local and remote cache
     *
     * @param $sId
     * @param $mContent
     * @param int $iLocalTimeOut
     * @param int $iRemoteTimeOut
     * @return bool
     */
    public function saveBoth($sId, $mContent, $iLocalTimeOut = 0, $iRemoteTimeOut = 0)
    {
        $this->save($sId, $mContent, $iRemoteTimeOut);
        if ($this->getLocalCache()) {
            $this->getLocalCache()->save($sId, $mContent, $iLocalTimeOut);
        }
        return true;
    }

    /**
     * Get cached data
     *
     * @param string $sName Unique ID of the file we need to get. This is what is returned from when you use the set() method.
     * @param int $iTime By default this is left blank, however you can identify how long a file should be cached before it needs to be updated in minutes.
     * @return mixed If the file is cached we return the data. If the file is cached but empty we return a true (bool). if the file has not been cached we return false (bool).
     * @throws \Psr\Cache\InvalidArgumentException
     *@see self::set()
     */
    public function get($sName, $iTime = 0)
    {
        // We don't allow caching during an install or upgrade.
        if ($this->skipCache) {
            return false;
        }
        try {
            $cacheKey = $this->_getName($sName);
            if (isset($this->contextCacheData[$cacheKey])) {
                return $this->contextCacheData[$cacheKey];
            }

            // Optimize: If is memcache or redis, We can skip encode and decode object
            $cacheObj = $this->_driver->getItem($cacheKey);

            if ($cacheObj->isHit() === false) {
                // Return false because many place of phpfox use false to check is cached or not
                return false;
            }

            if ($iTime && (PHPFOX_TIME - $iTime * 60) > ($cacheObj->getExpirationDate()->getTimestamp() - $this->defaultExpireTime)) {
                $this->_driver->deleteItem($cacheKey);
                return false;
            }

            $aContent = $cacheObj->get();
            $this->contextCacheData[$cacheKey] = $aContent;

            return $aContent;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Save data to the cache.
     * @param string $sName Unique ID connecting to the cache file based by the method set()
     * @param string|array $aValue Content you plan on saving to cache. Can be bools, strings, ints, objects, arrays etc...
     * @param int $iTimeOut Save with specific timeout in minute
     * @return bool
     *@see self::set()
     */
    public function save($sName, $aValue, $iTimeOut = 0)
    {
        if ($this->skipCache) {
            return false;
        }
        $cacheKey = $this->_getName($sName);
        try {
            $cacheItem = $this->_driver->getItem($cacheKey);
            if ($iTimeOut === 0) {
                $cacheItem->set($aValue)->expiresAfter($this->defaultExpireTime); // cache 1 month
            } else {
                $cacheItem->set($aValue)->expiresAfter($iTimeOut * 60);
            }

            $this->_driver->save($cacheItem);
            return true;
        } catch (Exception $e) {
            return false;
        }

    }

    /**
     * Close the cache connection.
     *
     * @param string $sId ID of the cache file we plan on closing the connection with.
     * @deprecated from v4.7.0
     */
    public function close($sId)
    {
        if (is_string($sId)) {
            unset($this->_aName[$sId]);
        }
    }

    /**
     * Removes cache file(s).
     *
     * @param string $sName Name of the cache file we need to remove.
     * @param string $sType Pass an optional command to execute a specific routine.
     * @return bool Returns true if we were able to remove the cache file and false if the system was locked.
     * @throws \Psr\Cache\InvalidArgumentException
     */

    public function remove($sName = null, $sType = '')
    {
        if ($this->skipCache && $sType != 'force-remove') {
            return true;
        }

        // Clean all cache and rebuild system
        if ($sName === null) {
            // Clear all cache
            if ($this->getLocalCache() && $this->getLocalCache()->getDrive()) {
                $this->getLocalCache()->getDrive()->clear();
            }

            // Clear remote cache
            $this->_driver->clear();

            \Core\Engine::flushCache(\Phpfox_Cache::unitCacheDomain());

            $this->contextCacheData = [];

            $this->save($this->set(self::CACHE_VERSION), uniqid());
        } else {
            $cacheKey = $this->_getName($sName);
            // Optimize: If is memcache or redis, We can skip encode and decode object
            $this->_driver->deleteItem($cacheKey);

            if (isset($this->contextCacheData[$cacheKey])) {
                unset($this->contextCacheData[$cacheKey]);
            }

            // add key to let other web server remove key
            if ($this->isShareCache && $this->getLocalCache()) {
                $cacheSync = $this->set(self::CACHE_REMOVED);
                $this->save($cacheSync, $sName, 1);

                $this->getLocalCache()->remove($sName);
            }
        }

        return true;
    }

    /**
     * Checks if a file is cached or not.
     *
     * @param string $sId Unique ID of the cache file.
     * @return bool Returns true if the file is cached and false if the file hasn't been cached already.
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isCached($sId)
    {
        if ($this->skipCache) {
            return false;
        }

        if ($this->_driver->hasItem($this->_getName($sId))) {
            return true;
        }

        return false;
    }

    public function getCachedFiles()
    {

        $s = $this->_driver->getStats();

        $rows = explode(",", $s->getData());

        $this->_aStats = [
            'total' => count($rows),
            'size' => $s->getSize(),
            'last' => null,
            'info' => $s->getInfo(),
            'driver' => $this->_driver->getDriverName()
        ];

        return [count($rows), $rows];
    }

    /**
     * Returns the full path to the cache file.
     *
     * @param string $sFile File name of the cache
     * @return string Full path to the cache file.
     */
    private function _getName($sFile)
    {
        if (is_array($sFile)) {
            $sFile = str_replace(['/', PHPFOX_DS], '_', $sFile[0]) . '_' . $sFile[1];
        }
        return Phpfox_Cache::unitCacheDomain() . str_replace(["/", PHPFOX_DS], "_", $sFile);
    }

    /**
     * Set skip cache in some cases like installing plugin...
     * @param bool $skipCache
     */
    public function setSkipCache($skipCache = true)
    {
        $this->skipCache = $skipCache;
    }

    /**
     * @param Phpfox_Cache_Storage_Driver $localCache
     */
    public function setLocalCache($localCache)
    {
        $this->localCache = $localCache;
    }

    /**
     * @return Phpfox_Cache_Storage_Driver
     */
    public function getLocalCache()
    {
        return $this->localCache;
    }
}