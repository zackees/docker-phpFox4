<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Queue;

use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Collection;

class MongoDbAdapter implements JobRepositoryInterface
{
	/**
	 * @var Collection
	 */
	private $client;

	/**
	 * @var Collection
	 */
	private $collection;

	/**
	 * MongoDbAdapter constructor.
	 * @param array $params
	 */
	public function __construct($params)
	{
		$params = array_merge([
			'connection_string' => 'mongodb://127.0.0.1:27017',
			'database' => 'local',
			'collection' => 'message_queue',
		], $params);

		$this->client = new Client($params['connection_string'], [], []);

		$this->collection = $this->client->selectCollection($params['database'], $params['collection']);

	}

	/**
	 * @param string $data
	 * @param string $queue_name
	 * @param int $expire_time
	 * @param int $waiting_time
	 * @param int $priority
	 * @return int|string|void
	 */
	public function addJob($data, $queue_name, $expire_time, $waiting_time, $priority)
	{
		$result = $this->collection->insertOne([
			'queue_name' => $queue_name,
			'sent' => 0,
			'data' => $data,
			'priority' => $priority || 10,
			'created' => new UTCDateTime((int)(microtime(true) * 1000)),
		]);

		return $result->getInsertedId();
	}

	public function getJob($queue_name)
	{

		$result = $this->collection->findOneAndUpdate([
			'queue_name' => $queue_name,
			'sent' => 0,
		], ['$set' => ['sent' => 1]]);

		if ($result && $result['_id']) {
			return [
				'queue_name'=>$queue_name,
				'job_id' => $result['_id'],
				'reversation_id' => $result['_id'],
				'data' => $result['data']
			];
		}
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function getJobs($queue_name, $limit)
	{
		$result = [];
		do {
			$job = $this->getJob($queue_name);
			if (!$job) {
				return $result;
			} else {
				$result[] = $job;
			}
		} while (count($result) < $limit);
		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function deleteJob($queue_name,$reversationId)
	{
		$this->collection->deleteOne(['_id' => $reversationId]);
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