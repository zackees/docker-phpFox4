<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Storage;

use League\Flysystem\Adapter\Local as Adapter;
use Phpfox;

/**
 * Class LocalAdapter
 * @package Core\Storage
 */
class LocalAdapter extends Adapter implements StorageAdapterInterface
{

	/**
	 * @var string
	 */
	private $baseUrl;

	/**
	 * @param array $params
	 */
	public function __construct($params)
	{
		$params = array_merge([
			'base_url' => Phpfox::getParam('core.path_actual') . 'PF.Base',
			'base_path' => PHPFOX_DIR,
		], $params);

		$writeFlags = LOCK_EX;
		$linkHandling = self::DISALLOW_LINKS;
		$permissions = [];

		$this->baseUrl = rtrim($params['base_url'], '/') . '/';
		$root = rtrim($params['base_path'], '/') . '/';

		parent::__construct($root, $writeFlags, $linkHandling, $permissions);
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public function getUrl($path)
	{
		return $this->baseUrl . $path;
	}

	public function isValid()
	{
		return true;
	}
}