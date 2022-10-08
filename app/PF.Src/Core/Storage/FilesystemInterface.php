<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Storage;

use League\Flysystem\FilesystemInterface as Basic;

interface FilesystemInterface extends Basic
{
	/**
	 * @param string $file
	 * @param string $path
	 * @param array $config
	 * @return bool
	 */
	public function putFile($file, $path = null, $config = null);

	/**
	 * @param $path
	 * @return bool
	 */
	public function remove($path);

	/**
	 * @return string
	 */
	public function getServerId();

	/**
	 * @param string $path
	 * @return string
	 */
	public function getUrl($path);

	/**
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public function isValid();

}