<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Log;


use Monolog\Formatter\ScalarFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Phpfox;

class DatabaseAdapter extends AbstractProcessingHandler
{
	const DATE_FORMAT = "Y-m-d H:i:s";

	public function __construct($params)
	{
		$params = array_merge([
			'level' => Phpfox::getParam('core.log_level',100),
			'bubble' => true,
		], $params);
		parent::__construct((int)$params['level'], $params['bubble']);
	}

	protected function write(array $record)
	{
		Phpfox::getLib('database')
			->insert(':core_log_data', $record['formatted']);
	}

	protected function getDefaultFormatter()
	{
		return new ScalarFormatter(self::DATE_FORMAT);
	}
}

