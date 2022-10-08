<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Storage;

use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter as Adapter;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;

class AzureBlobStorageAdapter extends Adapter implements StorageAdapterInterface
{
	/**
	 * @var string
	 */
	protected $baseUrl;

	public function __construct($params)
	{
		$params = array_merge([
			'connection_string' => '',
			'container' => '',
			'prefix' => null,
			'base_url' => '',
		], $params);
		$restOptions = [];
		$client = BlobRestProxy::createBlobService($params['connection_string'], $restOptions);
		$this->baseUrl = rtrim($params['base_url'], '/') . '/';
		parent::__construct($client, $params['container'], $params['prefix']);
	}

	public function getUrl($path)
	{
		return $this->baseUrl . '/' . $path;

	}

	public function isValid()
	{
		return $this->listContents('/', false) !== false;
	}
}