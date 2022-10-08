<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Storage;

use Google\Cloud\Storage\StorageClient;
use Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter as Adapter;

class GoogleStorageAdapter extends Adapter implements StorageAdapterInterface
{
	/**
	 * GoogleStorageAdapter constructor.
	 * @param $params
	 */
	public function __construct($params)
	{
		$params = array_merge([
			'project_id' => 'my-project-id',
			'bucket' => 'bucket-name',
			'storage_api_url' => null,
			'base_path' => '',
		], $params);
		$storageClient = new StorageClient([
			'projectId' => $params['project_id'],
		]);

		if (!$params['storage_api_url']) {
			$params['storage_api_url'] = null;
		}
		if (!$params['base_path']) {
			$params['base_path'] = null;
		}

		$bucket = $storageClient->bucket($params['bucket']);

		parent::__construct($storageClient, $bucket, $params['base_path'], $params['storage_api_url']);
	}

	public function isValid()
	{
		return $this->listContents('/', false) !== false;
	}
}