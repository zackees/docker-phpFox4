<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Queue;

use InvalidArgumentException;
use Islambey\RSMQ\Exception;
use Islambey\RSMQ\RSMQ;
use Redis;


class RedisAdapter implements JobRepositoryInterface
{
	/**
	 * @var RSMQ;
	 */
	private $rsmqClient;

	/**
	 * RedisAdapter constructor.
	 * @param array $params
	 */
	public function __construct($params)
	{

		$params = array_merge([
			'password'=> '',
			'host' => '127.0.0.1',
			'port' => 6379,
			'namespace' => 'rsmq',
			'retry_interval' => 0.0,
			'read_timeout' => 0.0,
			'timeout' => 0.0,
			'reserved' => null,
			'realtime' => false,
			'database' => '3',
		], $params);

		$redis = new Redis();

		if (!$redis->connect($params['host'],
			$params['port'],
			$params['timeout'],
			$params['reserved'],
			$params['retry_interval'],
			$params['read_timeout'])) {
			// todo throw invalid exception
			throw new InvalidArgumentException($redis->getLastError());
		}

		if($params['password']){
			$redis->auth($params['password']);
		}

		$redis->select($params['database']);

		$this->rsmqClient = new RSMQ($redis, $params['namespace'], $params['realtime']);
	}

	public function addJob($data, $queue_name, $expire_time, $waiting_time, $priority)
	{
		$jobId = null;
		try {
			$jobId = $this->rsmqClient->sendMessage($queue_name, $data, ['delay' => $waiting_time]);
		} catch (Exception $exception) {
			if (!in_array($queue_name, $this->listQueues())) {
				$this->rsmqClient->createQueue($queue_name);
				$jobId = $this->rsmqClient->sendMessage($queue_name, $data, ['delay' => $waiting_time]);
			} else {
				throw new InvalidArgumentException($exception->getMessage());
			}
		}
		return $jobId;
	}

	public function getJob($queue_name)
	{
		$result = $this->rsmqClient->receiveMessage($queue_name);

		if (!$result || !$result['id'])
			return null;

		$id = $result['id'];
		return [
			'queue_name' => $queue_name,
			'job_id' => $id,
			'reversation_id' => $id,
			'data' => $result['message']
		];
	}

	public function getJobs($queue_name, $limit)
	{
		$result = [];
		for ($i = 0; $i < $limit; ++$i) {
			$job = $this->getJob($queue_name);
			if (!$job) {
				return $result;
			} else {
				$result[] = $job;
			}
		}
		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function deleteJob($queue_name, $reversationId)
	{
		return $this->rsmqClient->deleteMessage($queue_name, $reversationId) !== false;
	}

	public function createQueue($queue_name)
	{
		return $this->rsmqClient->createQueue($queue_name);
	}

	public function listQueues()
	{
		return $this->rsmqClient->listQueues();
	}
}