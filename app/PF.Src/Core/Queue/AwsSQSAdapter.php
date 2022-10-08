<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Queue;


use Aws\Sqs\Exception\SqsException;
use Aws\Sqs\SqsClient;
use Exception;

class AwsSQSAdapter implements JobRepositoryInterface
{
	/**
	 * @var SqsClient
	 */
	private $sqsClient;

	/**
	 * @var array
	 */
	private $queue_urls = [];

	/**
	 * AswSQSRepository constructor.
	 * @param $params
	 */
	public function __construct($params)
	{
		$params = array_merge([], $params);

		$this->sqsClient = new SqsClient([
			'region' => $params['region'],
			'version' => 'latest',
			'credentials' => [
				'key' => $params['key'],
				'secret' => $params['secret'],
			],
		]);
	}

	/**
	 * @param string $queue_name
	 * @return string
	 * @throws Exception
	 */
	public function getQueueUrl($queue_name)
	{
		if (isset($this->queue_urls[$queue_name])) {
			return $this->queue_urls[$queue_name];
		}

		$result = null;

		try {
			$result = $this->sqsClient->getQueueUrl(['QueueName' => $queue_name]);
		} catch (SqsException $exception) {
			if (strpos($exception->getMessage(), 'NonExistentQueue')) {
				$result = $this->createQueue($queue_name);
				var_dump($result);
			} else {
				throw $exception;
			}
		}


		if ($result) {
			return $this->queue_urls[$queue_name] = $result['QueueUrl'];
		}

		return null;
	}

	public function addJob($data, $queue_name, $expire_time, $waiting_time, $priority)
	{
		$jobId = null;
		try {
			$result = $this->sqsClient->sendMessage([
				'DelaySeconds' => $waiting_time,
				'MessageBody' => $data,
				'QueueUrl' => $this->getQueueUrl($queue_name),
			]);
			if ($result && $result['MessageId'])
				$jobId = $result['MessageId'];
		} catch (\Exception $exception) {
            if(defined('PHPFOX_DEBUG') && PHPFOX_DEBUG) {
                \Phpfox_Error::set(strip_tags($exception->getMessage()));
            }
		}
		return $jobId;
	}

	/**
	 * @param string $queue_name
	 * @return array|bool|void
	 */
	public function getJob($queue_name)
	{
		$queueUrl = $this->getQueueUrl($queue_name);

		$result = $this->sqsClient->receiveMessage([
			'QueueUrl' => $queueUrl,
			'MaxNumberOfMessages' => 1,
		]);

		if (!$result || !$result['Messages'])
			return [];

		$jobs = array_map(function ($row) use ($queue_name) {
			return [
				'queue_name' => $queue_name,
				'reversation_id' => $row['ReceiptHandle'],
				'job_id' => $row['MessageId'],
				'data' => $row['Body'],
			];
		}, $result['Messages']);

		return array_pop($jobs);
	}

	public function getJobs($queue_name, $limit)
	{
		$queueUrl = $this->getQueueUrl($queue_name);

		$result = $this->sqsClient->receiveMessage([
			'QueueUrl' => $queueUrl,
			'MaxNumberOfMessages' => $limit,
		]);

		if (!$result || !$result['Messages'])
			return [];

		$jobs = array_map(function ($row) use ($queue_name) {
			return [
				'queue_name' => $queue_name,
				'reversation_id' => $row['ReceiptHandle'],
				'job_id' => $row['MessageId'],
				'data' => $row['Body'],
			];
		}, $result['Messages']);

		return $jobs;
	}

	/**
	 * @param string $queue_name
	 * @param string $reversationId
	 * @return \Aws\Result
	 */
	public function deleteJob($queue_name, $reversationId)
	{
		return $this->sqsClient->deleteMessage([
			'QueueUrl' => $this->getQueueUrl($queue_name), // REQUIRED
			'ReceiptHandle' => $reversationId,
		]);
	}

	public function createQueue($queue_name)
	{
		return $this->sqsClient->createQueue([
			'QueueName' => $queue_name,
		]);
	}

	public function listQueues()
	{
		return $this->sqsClient->listQueues([]);
	}
}