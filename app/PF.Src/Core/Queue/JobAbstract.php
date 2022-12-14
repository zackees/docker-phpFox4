<?php

namespace Core\Queue;

/**
 * Class JobAbstract
 *
 * @package Core\Queue
 */
abstract class JobAbstract implements JobInterface
{
	/**
	 * @var string
	 */
	private $job_name;

	/**
	 * @var string
	 */
	private $queue_name;

	/**
	 * @var int
	 */
	private $reversation_id;

	/**
	 * @var int
	 */
	private $job_id;

	/**
	 * @var array
	 */
	private $params = [];

	/**
	 * JobAbstract constructor.
	 * @param string $queue_name
	 * @param string $reversation_id
	 * @param string $job_id
	 * @param string $job_name
	 * @param array $data
	 */
	public function __construct($queue_name, $reversation_id, $job_id, $job_name, $data)
	{
		$this->setQueueName($queue_name);
		$this->setReversationId($reversation_id);
		$this->setJobId($job_id);
		$this->setParams($data);
		$this->setJobName($job_name);
	}

	/**
	 * @return string
	 */
	public function getQueueName()
	{
		return $this->queue_name;
	}

	/**
	 * @param string $queue_name
	 */
	public function setQueueName($queue_name)
	{
		$this->queue_name = $queue_name;
	}

	/**
	 * @return mixed
	 */
	public function getReversationId()
	{
		return $this->reversation_id;
	}

	/**
	 * @param mixed $reversation_id
	 */
	public function setReversationId($reversation_id)
	{
		$this->reversation_id = $reversation_id;
	}

	/**
	 * @return mixed
	 */
	public function getJobId()
	{
		return $this->job_id;
	}

	/**
	 * @param mixed $job_id
	 */
	public function setJobId($job_id)
	{
		$this->job_id = $job_id;
	}

	/**
	 * @return array
	 */
	public function getParams()
	{
		return $this->params;
	}

	/**
	 * @param array $params
	 */
	public function setParams($params)
	{
		$this->params = $params;
	}

	/**
	 * @return string
	 */
	public function getJobName()
	{
		return $this->job_name;
	}

	/**
	 * @param string $job_name
	 */
	public function setJobName($job_name)
	{
		$this->job_name = $job_name;
	}

	/**
	 * Delete job
	 */
	public function delete()
	{
		if ($this->reversation_id) {
			Manager::instance()->deleteJob($this->reversation_id, $this->queue_name);
		}
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->getJobName() . ':' . $this->reversation_id;
	}
}