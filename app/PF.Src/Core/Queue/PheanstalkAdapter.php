<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Queue;

use Pheanstalk\Job;
use Pheanstalk\Pheanstalk;


class PheanstalkAdapter implements JobRepositoryInterface
{
	/**
	 * @var Pheanstalk
	 */
	private $pheanstalk;

	/**
	 * PheanstalkAdapter constructor.
	 * @param array $params
	 */
	public function __construct($params = [])
	{
		$params = array_merge([
			'host' => '127.0.0.1',
			'port' => 11300,
			'timeout' => null,
			'persistent' => false,
		], $params);

		$this->pheanstalk = new Pheanstalk($params['host'], $params['port'], $params['timeout'], $params['persistent']);
	}

	/**
	 * @inheritDoc
	 */
	public function addJob($data, $queue_name, $expire_time, $waiting_time, $priority)
	{
		return $this->pheanstalk
			->useTube($queue_name)
			->put($data, $priority || 1024, $waiting_time);
	}

	/**
	 * @inheritDoc
	 */
	public function getJob($queue_name)
	{
		$job = $this->pheanstalk->reserveFromTube($queue_name);

		if (!$job) {
			return null;
		}

		$job->getData();
		$job->getId();

		return [
			'queue_name' => $queue_name,
			'job_id' => $job->getId(),
			'reversation_id' => $job->getId(),
			'data' => $job->getData(),
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getJobs($queue_name, $limit)
	{
		$job = null;
		$result = [];
		for ($i = 0; $i < $limit; ++$i) {
			$job = $this->pheanstalk->reserveFromTube($queue_name);
			if (!$job || !$job->getId()) {
				return $result;
			} else {
				$result[] = [
					'queue_name' => $queue_name,
					'job_id' => $job->getId(),
					'reversation_id' => $job->getId(),
					'data' => $job->getData(),
				];
			}
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function deleteJob($queue_name, $reversationId)
	{
		$job = new Job($reversationId, '');

		return $this->pheanstalk->delete($job);
	}

	/**
	 * @inheritDoc
	 */
	public function createQueue($queue_name)
	{
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function listQueues()
	{
		return [];
	}


}