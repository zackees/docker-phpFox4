<?php

namespace Core\Log;

use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Phpfox;

class Manager
{

	/**
	 * @var Logger
	 */
	protected $loggers = [];

	/**
	 * @var array
	 */
	protected $adapters = [];

	/**
	 * @var HandlerInterface
	 */
	protected $sharedHandlers = [];

	/**
	 * LogManager constructor.
	 */
	public function __construct()
	{
		if (defined('PHPFOX_INSTALLER')) {
			return;
		}

		$this->adapters = $this->getLogHandlerConfigFromEnv();

		if (!$this->adapters) {
			$this->adapters = $this->getLogHandlerConfigFromDatabase();
		}
	}

	/**
	 * @param string $channel
	 *
	 * @return Logger
	 */
	public function get($channel)
	{
		return isset($this->loggers[$channel])
			? $this->loggers[$channel]
			: ($this->loggers[$channel] = $this->make($channel));
	}

	private function getLogHandlerConfigFromEnv()
	{
		$handlers = Phpfox::getParam('core.log_handling');
		if (!$handlers) {
			return false;
		}

		$result['local'] = [
			'id' => 'local',
			'service_class' => StreamLogger::class,
			'config' => [],
			'channels' => '*',
			'shared' => false,
		];

		$availableLogHandlers = $this->getAvailableLogHandlers();
		foreach ($handlers as $driver=>$config) {
			if (!array_key_exists($driver, $availableLogHandlers)) {
				continue;
			}
			$row = $availableLogHandlers[$driver];
			$result[$driver] = [
				'id' => $driver,
				'service_class' => $row['service_class'],
				'config' => $config,
				'channels' => '*',
				'shared' => !!$row['is_share'],
			];
		}

		return $result;
	}

	private function getAvailableLogHandlers()
	{
		$cache = Phpfox::getLib('cache');

		$sCacheId = $cache->set('pf_core_getavailableloghandlers');

		$data = $cache->getLocalFirst($sCacheId);

		if (!$data) {
			$data = [
				'files' => 'Core\Log\StreamLogger',
			];
			foreach (Phpfox::getLib('database')
						 ->select('log.*')
						 ->from(':core_log_service', 'log')
						 ->execute('getSlaveRows') as $row) {
				$data[$row['service_id']] = [
					'service_class' => $row['service_class'],
					'is_shared' => $row['is_shared'],
				];
			}

			$cache->saveBoth($sCacheId, $data);
		}

		return $data;
	}


	private function getLogHandlerConfigFromDatabase()
	{
		$cache = Phpfox::getLib('cache');

		$sCacheId = $cache->set('pf_core_getloghandlerconfigfromdatabase');

		$data = $cache->getLocalFirst($sCacheId);

		if (!$data) {
			$data = array_map(function ($row) {
				$params = json_decode($row['config'], 1);
				$channels = array_key_exists('channels', $params) ? $params['channels'] : '';
				$channels = array_filter(array_map(function ($channel) {
					return trim($channel);
				}, explode(',', $channels)), function ($str) {
					return !!$str;
				});

				if (empty($channels)) {
					$channels = ['*'];
				}

				return [
					'id' => $row['service_id'],
					'service_class' => $row['service_class'],
					'config' => $params,
					'channels' => $channels,
					'shared' => !!$row['is_share'],
				];
			}, Phpfox::getLib('database')
				->select('log.*')
				->from(':core_log_service', 'log')
				->where("is_active=1")
				->execute('getSlaveRows'));

			$cache->saveBoth($sCacheId,$data);
		}
		return $data;
	}


	/**
	 * @param $adapter_class
	 * @param $params
	 *
	 * @return HandlerInterface
	 */
	public function createLogHandler($adapter_class, $params)
	{
		if (class_exists($adapter_class)) {
			return new $adapter_class($params);
		}
	}

	/**
	 * @param string $channel
	 *
	 * @return Logger
	 */
	public function make($channel)
	{
		$handlers = [];
		$adapters = array_filter($this->adapters, function ($adapter) use ($channel) {
			return array_search('*', $adapter['channels']) !== false
				|| array_search($channel, $adapter['channels']) !== false;
		});

		foreach ($adapters as $adapter) {
			$id = $adapter['id'];
			$shared = $adapter['shared'];
			$adapter_class = $adapter['service_class'];
			$params = $adapter['config'];
			$params['_channel'] = $channel;

			if ($shared) {
				if (!isset($this->sharedHandlers[$id])) {
					$handler = $this->createLogHandler($adapter_class, $params);
					if ($handler) {
						$handlers[] = $this->sharedHandlers[$id] = $handler;
					}
				} else {
					$handlers[] = $this->sharedHandlers[$id];
				}
			} else {
				$handler = $this->createLogHandler($adapter_class, $params);
				if ($handler) {
					$handlers[] = $handler;
				}
			}
		}

		return new Logger($channel, $handlers);
	}

	public function getLevelName($level)
	{
		switch ($level) {
			case "100":
				return "DEBUG";
			case "200":
				return "INFO";
			case "250":
				return "NOTICE";
			case "300":
				return "WARNING";
			case "400":
				return "ERROR";
			case "500":
				return "CRITICAL";
			case "550":
				return "ALERT";
			case "600":
				return "EMERGENCY";
			default:
				return "DEBUG";
		}
	}
}