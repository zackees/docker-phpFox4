<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Storage;

use League\Flysystem\Adapter\Ftp as Adapter;

/**
 * Class FTPAdapter
 * @package Core\Storage
 */
class FTPAdapter extends Adapter implements StorageAdapterInterface
{
	/**
	 * @var string
	 */
	private $baseUrl;

	/**
	 * FTPAdapter constructor.
	 * @param array $params
	 */
	public function __construct($params)
	{

		$params = array_merge([
			'host' => 'localhost',
			'port' => 21,
			'base_path' => '',
			'base_url' => '',
			'username' => 'username',
			'password' => 'password',
			'timeout' => 30,
			'ssl' => false,
			'passive' => true,
			'ignore_passive_address' => false,
		], $params);



		$this->baseUrl = rtrim($params['base_url'], '/') . '/';
		$base_path = rtrim($params['base_path'], '/') . '/';

		parent::__construct([
			'host' => $params['host'],
			'username' => $params['username'],
			'password' => $params['password'],
			'port' => $params['port'],
			'root' => $base_path,
			'timeout' => $params['timeout'],
			'passive' => $params['passive'],
			'ssl' => $params['ssl'],
            'permPublic' => 0644,
            'directoryPerm' => 0755,
			'ignorePassiveAddress' => $params['ignore_passive_address']
		]);
	}

	/**
	 * @inheritDoc
	 */
	public function getUrl($path)
	{
		return $this->baseUrl . $path;
	}

	public function isValid()
	{
		return $this->listDirectoryContents('/', false);
	}
}