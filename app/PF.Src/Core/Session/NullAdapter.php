<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Session;


use SessionHandlerInterface;

/**
 * Class NullAdapter
 *
 * @package Core\Session
 */
class NullAdapter implements SaveHandlerInterface, SessionHandlerInterface
{
	public function registerSaveHandler()
	{
		// set object as the save handler
		session_set_save_handler(
			array($this, 'open'),
			array($this, 'close'),
			array($this, 'read'),
			array($this, 'write'),
			array($this, 'destroy'),
			array($this, 'gc')
		);
	}

	public function isValid()
	{
		return true;
	}

	public function close()
	{
		return true;
	}

	public function destroy($session_id)
	{
		return true;
	}

	public function gc($maxlifetime)
	{
		return true;
	}

	public function open($save_path, $name)
	{
		return true;
	}

	public function read($session_id)
	{
		return '';
	}

	public function write($session_id, $session_data)
	{
		return true;
	}
}