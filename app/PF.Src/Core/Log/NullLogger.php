<?php

namespace Core\Log;

use Monolog\Handler\NullHandler;
use Monolog\Logger;

class NullLogger extends NullHandler
{
	/**
	 * NullLogger constructor.
	 * @param array $params
	 */
	public function __construct($params)
	{
		$level = Logger::DEBUG;
		extract($params, EXTR_OVERWRITE);
		parent::__construct($level);
	}
}