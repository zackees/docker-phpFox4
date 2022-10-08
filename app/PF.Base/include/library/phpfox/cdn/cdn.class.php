<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Phpfox_Cdn
{
	/**
	 * @return Phpfox_Cdn
	 */
	public static function instance()
	{
		return Phpfox::getLib('cdn');
	}

	public function &getInstance()
	{
		return $this;
	}

	public function factory()
	{
		return $this;
	}

	/**
	 * @param string $sSrc
	 * @param string $iServerId
	 * @return string
	 */
	public function getUrl($sSrc, $iServerId = '0')
	{
		return Phpfox::getLib('storage')->get($iServerId)->getUrl($sSrc);
	}

	/**
	 * @param string $file
	 * @param string $name
	 * @return bool
	 */
	public function put($file, $name = '')
	{
		return Phpfox::getLib('storage')->get()->putFile($file, $name);
	}

	/**
	 * @return string
	 */
	public function getServerId()
	{
		return Phpfox::getLib('storage')->getStorageId();
	}

	/**
	 * @param string $serverId
	 * @deprecated
	 */
	public function setServerId($serverId)
	{

	}

	/**
	 * @param string $file
	 * @return bool
	 */
    public function remove($file, $serverId = null)
    {
        $key = str_replace("\\", '/', str_replace(PHPFOX_DIR, '', $file));
        return Phpfox::getLib('storage')->get($serverId)->remove($key);
    }
}