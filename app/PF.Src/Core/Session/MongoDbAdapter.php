<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Session;

use MongoDB\Client;
use MongoDB\Collection;
use SessionHandlerInterface;

class MongoDbAdapter implements SaveHandlerInterface, SessionHandlerInterface
{

	private $lifetime;

	/**
	 * @var Collection
	 */
	private $collection;

	/**
	 * @var array
	 */
	private $_session = [];

	/**
	 * MongoDbHandler constructor.
	 * @param $params
	 */
	public function __construct($params)
	{

		$params = array_merge([
				'connection_string' => 'mongodb://127.0.0.1',
				'database' => 'local',
				'collection' => 'session']
			, $params);

		$client = new Client($params['connection_string'], [], []);

		$this->collection = $client->selectCollection($params['database'], $params['collection']);
	}


	public function registerSaveHandler()
	{
		// set object as the save handler
		session_set_save_handler(
			array($this, 'open'),
			array($this, 'close'),
			array($this, 'read'),
			array($this, 'write'),
			array($this, 'destroy'),
			array($this, 'gc')
		);
	}

	public function close()
	{
		return true;
	}

	public function destroy($session_id)
	{
		$this->collection->deleteOne(['session_id' => $session_id]);
	}

	public function gc($maxlifetime)
	{
		$query = array('expiry' => array('$lt' => time()));

		$this->collection->deleteMany($query);

		return true;
	}

	public function open($save_path, $name)
	{
		return true;
	}

	public function read($session_id)
	{
		$result = $this->collection->findOne([
			'session_id' => $session_id,
			'expiry' => array('$gte' => time()),
		]);

		if (isset($result['data'])) {
			$this->_session = $result;
			return $result['data'];
		}

		return '';
	}

	public function write($session_id, $session_data)
	{
        if (!empty($session_data)) {
            $expiry = time() + $this->lifetime;

            $this->collection->updateOne([
                'session_id' => $session_id,
            ], [
                '$set' => [
                    'data'   => $session_data,
                    'expiry' => $expiry
                ]
            ], [
                'upsert' => true,
                'safe'   => true,
                'fsync'  => false
            ]);
        }
		return true;
	}

	public function isValid()
	{
		return $this->write('test-session-id', '');
	}
}