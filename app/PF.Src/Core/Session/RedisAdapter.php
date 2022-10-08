<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Session;

/**
 * Class RedisAdapter
 * @package Core\Session
 */
class RedisAdapter implements SaveHandlerInterface
{
	/**
	 * @var string
	 */
	private $savePath;

	/**
	 * RedisAdapter constructor.
	 * @param $params
	 */
	public function __construct($params)
	{
		$this->savePath = $params['save_path'];
	}

	/**
	 * @link https://github.com/phpredis/phpredis#php-session-handler
	 */
	public function registerSaveHandler()
	{
		session_module_name('redis');
		session_save_path($this->savePath);
	}

	public function isValid()
	{
		return true;
	}
}