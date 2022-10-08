<?php

namespace Core\Queue;

use InvalidArgumentException;
use Phpfox;

class Admincp
{
	/**
	 * @param $queue_id
	 * @return array|null
	 */
	private function getQueueById($queue_id)
	{
		return db()->select('d.*, s.service_class, s.service_phrase_name, s.edit_link')
			->from(':core_sqs_queue', 'd')
			->join(':core_sqs_service', 's', 's.service_id=d.service_id')
			->where(['d.queue_id' => $queue_id])
			->execute('getSlaveRow');

	}

	/**
	 * @param string $service_id
	 * @return array
	 */
	public function getQueueService($service_id)
	{
		return db()->select('s.*')
			->from(':core_sqs_service', 's')
			->where(['service_id' => (string)$service_id])
			->execute('getSlaveRow');

	}

	/**
	 * @return array
	 */
	public function getQueueServices()
	{
		$cacheObject = Phpfox::getLib('cache');
		$cacheId = $cacheObject->set('core_queue_services');

		if (($rows = $cacheObject->getLocalFirst($cacheId)) === false) {
			$rows = db()->select('s.*')
				->from(':core_sqs_service', 's')
				->execute('getSlaveRows');
			$cacheObject->saveBoth($cacheId, $rows);
			$cacheObject->group('core_queue', $cacheId);
		}

		return $rows;
	}

	/**
	 * @return array
	 */
	public function getAllQueues()
	{

		$handling = Phpfox::getParam('core.message_queue_handling');

		if (!empty($handling)) {
			$result = [];
			foreach ($handling as $driver => $config) {
				$result[] = [
					'queue_id' => '1',
					'service_id' => $driver,
					'queue_name' => isset($config['queue_name']) ? $config['queue_name'] : 'default',
					'config' => $config,
					'is_active' => 1,
					'service_phrase_name' => $driver,
				];
			}

			return $result;
		}

		$cacheObject = Phpfox::getLib('cache');
		$cacheId = $cacheObject->set('core_queues');

		if (($rows = $cacheObject->getLocalFirst($cacheId)) === false) {
			$rows = db()
				->select('d.*,s.service_id, s.service_phrase_name, s.edit_link')
				->from(':core_sqs_queue', 'd')
				->join(':core_sqs_service', 's', 's.service_id=d.service_id')
				->execute('getSlaveRows');
			$cacheObject->saveBoth($cacheId, $rows);
			$cacheObject->group('core_queue', $cacheId);
		}

		return $rows;
	}

	/**
	 * @param $queue_id
	 * @param $service_id
	 * @return array
	 */
	public function getQueueConfig($queue_id, $service_id)
	{
		$row = $this->getQueueById($queue_id);
		if (!$row) {
			throw  new InvalidArgumentException("Could not found message queue '{$service_id}'");
		}

		$config = json_decode($row['config'], true);
		$config['is_active'] = !!$row['is_active'];

		return $config;
	}

	public function verifyQueueConfig($service_id, $config)
	{
		$row = $this->getQueueService($service_id);

		if (!$row) {
			throw  new InvalidArgumentException("Could not found message queue '{$service_id}'");
		}

		$serviceClass = $row['service_class'];

		if ($serviceClass && class_exists($serviceClass)) {
			$driver = new $serviceClass($config);
			$data = json_encode(['job' => 'validate_configuration', 'params' => []]);
			$driver->addJob($data, 'default', 0, 0, 0);
			return true;
		}

		return false;
	}

	/**
	 * @param string $queue_id
	 * @param string $service_id
	 * @param bool $is_active
	 * @param array $config
	 * @return bool
	 */
	public function updateQueueConfig($queue_id, $service_id, $is_active, $config)
	{
		$row = $this->getQueueService($service_id);

		if (!$row) {
			throw  new InvalidArgumentException("Could not found message queue '{$service_id}'");
		}

		if ($success = Phpfox::getLib('database')
			->update(':core_sqs_queue', [
				'is_active' => $is_active,
				'config' => json_encode($config, JSON_FORCE_OBJECT),
				'service_id' => $service_id,
			], ['queue_id' => $queue_id])) {
			Phpfox::getLib('cache')->removeGroup('core_queue');
		}

		return $success;
	}
}