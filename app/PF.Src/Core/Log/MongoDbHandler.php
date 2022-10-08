<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Log;

use MongoDB\Client;
use Phpfox;

class MongoDbHandler extends \Monolog\Handler\MongoDBHandler
{
	/**
	 * MongoDbHandler constructor.
	 * @param $params
	 */
	public function __construct($params)
	{
		$params = array_merge([
			'connection_string' => 'mongodb://127.0.0.1',
			'level' => Phpfox::getParam('core.log_level',100),
			'bubble' => true,
			'database' => 'local',
			'collection' => 'logs',
		], $params);

		$client = new Client($params['connection_string'], [], []);

		parent::__construct($client, $params['database'], $params['collection'], (int)$params['level'], $params['bubble']);
	}

}