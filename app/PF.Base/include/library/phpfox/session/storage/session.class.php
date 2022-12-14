<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 * Session Storage
 * Store information about the users using PHP sessions.
 *
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author			phpFox LLC
 * @package 		Phpfox
 * @version 		$Id: session.class.php 2767 2011-07-29 12:05:18Z phpFox LLC $
 */
class Phpfox_Session_Storage_Session
{
	/**
	 * Prefix of the session name.
	 *
	 * @var string
	 */
	private $_sPrefix;

	/**
	 * Class constructor. Gets the new prefix from the global settings.
	 *
	 */
	public function __construct()
	{
		Phpfox::getLib('session.manager')->registerSaveHandler();

		$this->_sPrefix = Phpfox::getParam('core.session_prefix');
		if (session_status() == PHP_SESSION_NONE
			&& !defined('PHPFOX_UNITEST')
			&& !defined('PHPFOX_NO_SESSION')
			&& PHP_SAPI !== "cli") {
			session_start();
		}
	}

	/**
	 * Sets a session.
	 *
	 * @see Phpfox::setCookie()
	 * @param string $sName Name of the session.
	 * @param string $sValue Value of the session.
	 */
	public function set($sName, $sValue)
	{
		$_SESSION[$this->_sPrefix][$sName] = $sValue;
	}

	/**
	 * Gets a session.
	 *
	 * @param string $sName Name of the session.
	 * @return mixed Session exists we return its value, otherwise we return FALSE.
	 */
	public function get($sName)
	{
		if (isset($_SESSION[$this->_sPrefix][$sName]))
		{
			return (empty($_SESSION[$this->_sPrefix][$sName]) ? true : $_SESSION[$this->_sPrefix][$sName]);
		}

		return false;
	}

	/**
	 * Removes a session.
	 *
	 * @param mixed $mName STRING name of session, ARRAY of sessions.
	 */
	public function remove($mName)
	{
		if (!is_array($mName))
		{
			$mName = array($mName);
		}
		(($sPlugin = Phpfox_Plugin::get('session_remove__start')) ? eval($sPlugin) : false);
		foreach ($mName as $sName)
		{
			unset($_SESSION[$this->_sPrefix][$sName]);
		}
	}

	/**
	 * Set an ARRAY session.
	 *
	 * @param string $sName Name of session.
	 * @param string $sValue Group of session.
	 * @param string $sActualValue Value of the session.
	 */
	public function setArray($sName, $sValue, $sActualValue)
	{
		$_SESSION[$this->_sPrefix][$sName]['value_' . $sValue] = $sActualValue;
	}

	/**
	 * Get a session ARRAY.
	 *
	 * @param string $sName Name of the session.
	 * @param string $sValue Name of the group session.
	 * @return mixed Session exists we return its value, otherwise we return FALSE.
	 */
	public function getArray($sName, $sValue)
	{
		if (isset($_SESSION[$this->_sPrefix][$sName]['value_' . $sValue]))
		{
			return $_SESSION[$this->_sPrefix][$sName]['value_' . $sValue];
		}

		return false;
	}
}