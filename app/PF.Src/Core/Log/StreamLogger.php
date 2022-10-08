<?php

namespace Core\Log;

use Monolog\Handler\StreamHandler;
use Phpfox;

class StreamLogger extends StreamHandler
{
	/**
	 * StreamLogger constructor.
	 * @param array $params
	 * @throws \Exception
	 */
	public function __construct($params)
	{
		$params = array_merge([
			'_channel' => 'main.log',
			'directory' => Phpfox::getParam('core.log_dir',PHPFOX_DIR_FILE . 'log') ,
			'level' => Phpfox::getParam('core.log_level',100),
			'use_locking' => false,
			'file_permission' => 0777,
			'bubble' => true,
			'size_limit' => 5242880,
		], $params);

		// force level debug
		if (defined('PHPFOX_DEBUG') && PHPFOX_DEBUG) {
			$params['level'] = 100;
		}

		$directory = $params['directory'];

		if (!is_dir($directory)) {
			@mkdir($directory, 0777, true);
		}


		$stream = $directory . PHPFOX_DS . $params['_channel'];

		if (file_exists($stream)
			and filesize($stream) > $params['size_limit']
			and rename($stream, $stream . '.' . time())
		) {
			rename($stream, $stream . '.' . time());
		}

		parent::__construct($stream, (int)$params['level'], $params['bubble'], $params['file_permission'], $params['use_locking']);
	}
}