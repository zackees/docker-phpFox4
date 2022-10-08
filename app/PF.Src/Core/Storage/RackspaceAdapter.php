<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Storage;

use League\Flysystem\Rackspace\RackspaceAdapter as Adapter;
use OpenCloud\Rackspace;

class RackspaceAdapter extends Adapter implements StorageAdapterInterface
{
	public function __construct($params)
	{
		$params = array_merge([
			'username' => ':username',
			'api_key' => ':password',
			'container' => ':my-container',
			'region' => 'LOL'
		], $params);

		$client = new Rackspace(Rackspace::UK_IDENTITY_ENDPOINT, array(
			'username' => $params['username'],
			'apiKey' => $params['api_key'],
		));

		$store = $client->objectStoreService('cloudFiles', $params['region'], 'publicURL');
		$container = $store->getContainer($params['container']);
		parent::__construct($container);
	}

	public function getUrl($path)
	{
		return $this->container->getUrl($path);
	}

	public function isValid()
	{
		return $this->listContents('/', false) !== false;
	}

}