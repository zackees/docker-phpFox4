<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Storage;

use League\Flysystem\AdapterInterface;

/**
 * Interface StorageAdapterInterface
 * @package Core\Storage
 */
interface StorageAdapterInterface extends AdapterInterface
{
	/**
	 * StorageAdapterInterface constructor.
	 *
	 * @param array $params
	 */
	public function __construct($params);

	/**
	 * @param string $path
	 * @return string
	 */
	public function getUrl($path);

	/**
	 * Check configuration is valid.
	 *
	 * @return bool
	 * @throws \InvalidArgumentException
	 */
	public function isValid();
}