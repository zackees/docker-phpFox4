<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Session;


class BuiltInAdapter implements SaveHandlerInterface
{
	public function registerSaveHandler()
	{
		session_module_name('files');
	}

	public function isValid()
	{
		return true;
	}
}