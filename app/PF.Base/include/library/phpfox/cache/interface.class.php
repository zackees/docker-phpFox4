<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Interface Phpfox_Cache_Interface
 */
interface Phpfox_Cache_Interface
{
	/**
	 * Sets the name of the cache.
	 *
	 * @param string|array $sName Unique fill name of the cache.
	 * @param string $sGroup Optional param to identify what group the cache file belongs to
	 * @return string Returns the unique ID of the file name
	 */
	public function set($sName, $sGroup = '');

	/**
	 * Get cache by key
	 *
	 * @param string $sName Unique fill name of the cache.
     * @param integer $iTime expire time in minutes
	 * @return array|bool Returns cached value or false
	 */
	public function get($sName, $iTime = 0);

    /**
     * Get cache data. if cache not saved on local
     * This method support remote and local cache
     * @param $sId
     * @param int $iLocalTimeOut
     * @return bool|mixed
     */
    public function getLocalFirst($sId, $iLocalTimeOut = 0);

	/**
	 * Save value to cache
	 *
	 * @param string $sName cache ID
	 * @param array $aValue value to save cache
	 * @return bool
	 */
	public function save($sName, $aValue);

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
    public function saveBoth($sId, $mContent, $iLocalTimeOut = 0, $iRemoteTimeOut = 0);

    /**
     * Remove cache by ID
     *
     * @param string $sName cache ID
     * @return bool
     */
	public function remove($sName = '');
}
