<?php

namespace Core\Queue;

interface JobRepositoryInterface
{
	/**
	 *
	 * @param string $data
	 * @param string $queue_name
	 * @param int $expire_time
	 * @param int $waiting_time
	 * @param int $priority
	 *
	 * @return string|int Return Job ID
	 */
	public function addJob($data, $queue_name, $expire_time, $waiting_time, $priority);

	/**
	 * @param string $queue_name
	 *
	 * @return array|bool
	 */
	public function getJob($queue_name);

	/**
	 * @param null $queue_name
	 * @param int $limit
	 *
	 * @return array
	 */
	public function getJobs($queue_name, $limit);

	/**
	 * Delete job from queue
	 *
	 * @param string $queue_name
	 * @param $reversationId
	 */
	public function deleteJob($queue_name, $reversationId);

	/**
	 * @param string $queue_name
	 * @return mixed
	 */
	public function createQueue($queue_name);

	/**
	 * @return array
	 */
	public function listQueues();
}