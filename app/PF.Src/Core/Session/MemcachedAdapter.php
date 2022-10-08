<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Session;

/**
 * Class MemcachedAdapter
 * @package Core\Session
 */
class MemcachedAdapter implements SaveHandlerInterface
{
	/**
	 * @var string
	 */
	private $savePath;

	public function __construct($params)
	{
		$this->savePath = $params['save_path'];
	}

	/**
	 * @link https://www.php.net/manual/en/memcached.sessions.php
	 */
	public function registerSaveHandler()
	{
		session_module_name('memcached');
		session_save_path($this->savePath);
	}

	public function isValid()
	{
		return true;
	}
}