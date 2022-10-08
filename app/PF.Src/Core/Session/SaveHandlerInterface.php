<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Session;

/**
 * Interface SaveHandlerInterface
 * @package Core\Session
 */
interface SaveHandlerInterface
{
	/**
	 * register save handler
	 */
	public function registerSaveHandler();

	/**
	 * @return mixed
	 * @throw \InvalidArgumentException
	 */
	public function isValid();
}