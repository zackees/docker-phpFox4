<?php
/**
 * @author phpfox
 * @license phpfox.com
 */


namespace Core\Assets;

use Core\Storage\Filesystem;
use Core\Storage\LocalAdapter;
use Phpfox;
use Phpfox_Plugin;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Manager
{
	/**
	 * @var string
	 */
	private $version;

	/**
	 * @var string
	 */
	private $baseUrl;

	/**
	 * @var string
	 */
	private $baseCdnUrl;

	/**
	 * @var bool
	 */
	private $enableCdn;

	/**
	 * @var string
	 */
	private $assetStorageId;

    /**
     * @return string
     */
    public function getAssetStorageId()
    {
        return $this->assetStorageId;
    }

	/**
	 * Manager constructor.
	 */
	public function __construct()
	{
		$cndUrl = setting('pf_assets_cdn_url');
		$cdnEnabled = !!setting('pf_assets_cdn_enable');
        $storageId = $this->getDefaultStorageId();

		$this->setAssetStorageId($storageId);
		$this->setBaseCdnUrl($cndUrl);
		$this->setEnableCdn($cdnEnabled);
		$this->version = substr(Phpfox::getLib('template')->getStaticVersion(), -6);
	}

    /**
     * Get default storage
     * @return \Core\Setting|mixed|null
     */
    public function getDefaultStorageId()
    {
        $configuredStorageId = Phpfox::getLib('setting')->getFromServerConfigFile('pf_assets_storage_id', 0);
        if (!is_numeric($configuredStorageId)) {
            $configuredStorageId = 0;
        }

        $storageId = setting('pf_assets_storage_id', $configuredStorageId, false);
        if ($storageId != 0 && !Phpfox::getLib('storage.admincp')->isActive($storageId)) {
            $storageId = $configuredStorageId;
        }

        return $storageId;
    }

    /**
     * Put asset files belong to target folder
     * @param $rootFolder
     * @return array|false
     */
    public function putAssetFilesForParentFolder($rootFolder)
    {
        if (!isset($this->assetStorageId)
            || $this->assetStorageId == 0
            || empty($rootFolder)
            || !is_dir($rootFolder)
            || empty($assetFiles = $this->scanAssetFiles([$rootFolder]))) {
            return false;
        }

        $storageObject = Phpfox::getLib('storage')->get($this->assetStorageId);
        $storageObject->setExtraConfig([
            'keep_files_in_server' => true,
        ]);
        $result = [];

        foreach ($assetFiles as $assetFile) {
            $result[$assetFile] = $storageObject->putFile($assetFile, str_replace(PHPFOX_PARENT_DIR, '', $assetFile));
        }

        return $result;
    }

	/**
	 * @return string
	 */
	public function getVersion()
	{
		return $this->version;
	}

	/**
	 * @param string $version
	 */
	public function setVersion($version)
	{
		$this->version = $version;
	}


	/**
	 * @param string $baseCdnUrl
	 */
	public function setBaseCdnUrl($baseCdnUrl)
	{
		$this->baseCdnUrl = $baseCdnUrl ? rtrim($baseCdnUrl, '/') . '/' : null;;
	}

	/**
	 * @param bool $enableCdn
	 */
	public function setEnableCdn($enableCdn)
	{
		$this->enableCdn = $enableCdn;
	}

	/**
	 * @param string $assetStorageId
	 */
	public function setAssetStorageId($assetStorageId)
	{
		$manager = Phpfox::getLib('storage');
		if (!$assetStorageId) {
			$assetStorageId = 'local_assets';
			$manager->addStorage('local_assets', new Filesystem(new LocalAdapter([
				'base_url' => Phpfox::getParam('core.path_actual'),
				'base_path' => PHPFOX_ROOT,
			]), ['server_id' => '0', 'keep_files_in_server' => true]));
		}
		$this->assetStorageId = $assetStorageId;
		$baseUrl = Phpfox::getLib('storage')->get($this->assetStorageId)->getUrl('/');
		$this->baseUrl = rtrim($baseUrl, '/') . '/';
	}

	/**
	 * @param string[] $dirs
	 * @return string[]
	 */
	public function scanAssetFiles($dirs)
	{
		$files = [];
		$skip = "#\.(php|log|txt|less|sass|json|md|checksum|htaccess|html|gitignore|lock|phpfox|xml)$#i";

		$skip_extra = "#(v3\.phpfox|checksum|license)$#i";
		$skip_path = "#(p-chatplus\/clients|vendor|\.git|\.idea)#i";

		foreach ($dirs as $dir) {
			$directory = new RecursiveDirectoryIterator($dir);
			$iterator = new RecursiveIteratorIterator($directory);

			foreach ($iterator as $info) {
				$filename = $info->getFilename();
				$pathname = $info->getPathname();
				if (!$info->isFile()
					|| substr($filename, 0, 1) === '.'
					|| preg_match($skip, $filename)
					|| preg_match($skip_path, $pathname)
					|| preg_match($skip_extra, $filename)) {
					continue;
				}
				$files[] = $pathname;
			}
		}
		return $files;
	}

	/**
	 * @return string[]
	 */
	public function getSiteAssetFiles()
	{
		$dirs = [
			PHPFOX_DIR_SITE . 'flavors',
			PHPFOX_DIR . 'theme',
			PHPFOX_DIR . 'static',
			PHPFOX_DIR . 'less',
			PHPFOX_DIR_MODULE,
			PHPFOX_DIR_SITE . 'Apps',
		];

		return $this->scanAssetFiles($dirs);
	}

	/**
	 * @param string[] $paths
	 * @param string $storageId
	 * @return bool
	 */
	public function transferAssetFiles($paths, $storageId)
	{
		$filesystem = Phpfox::getLib('storage')->get($storageId);
		// skip to handle blog.
		if ($filesystem->getServerId() == '0') {
			return true;
		}

		$transferCache = storage()->get('core_transfer_asset_uniq');
		$totalTransferFile = !empty($transferCache) ? (array)$transferCache->value : null;
		$isDebug = defined('PHPFOX_DEBUG') && PHPFOX_DEBUG;
        $filesystem->setExtraConfig([
            'keep_files_in_server' => true,
        ]);

        foreach ($paths as $path) {
			$stream = fopen(PHPFOX_PARENT_DIR . $path, 'r');
			if (!$stream)
				continue;

			$success = $filesystem->putStream($path, $stream, ['visibility' => 'public']);
			if ($success) {
				if (isset($totalTransferFile)) {
					$totalTransferFile['transfered']++;
					if ($isDebug) {
						Phpfox::getLog('transfer-file.log')->info('total success: ' . $totalTransferFile['transfered']);
					}
				}
			} elseif (isset($totalTransferFile)) {
				$totalTransferFile['failed']++;
				if ($isDebug) {
					Phpfox::getLog('transfer-file.log')->info('total failed: ' . $totalTransferFile['failed']);
				}
			}
		}

		if (isset($totalTransferFile)) {
			if ($isDebug) {
				Phpfox::getLog('transfer-file.log')->info('update transfer data', $totalTransferFile);
			}
			storage()->updateById($transferCache->id, $totalTransferFile);
		}

		return true;
	}

	/**
	 * Delete all jobs when stop transfering files
	 */
	public function deleteTransferFileData()
	{
		$jobName = 'core_transfer_asset_files';
		db()->delete(':cron_job', 'data LIKE \'%"job":"' . $jobName . '"%\'');
	}

	/**
	 * @param string $path
	 * @param string $contents
	 * @return bool
	 */
	public function putAssetContents($path, $contents = '')
	{

		$filesystem = Phpfox::getLib('storage')->get($this->assetStorageId);

		if ($filesystem->getServerId() == '0') {
			return true;
		}

		return $filesystem->put($path, $contents, ['visibility' => 'public']);
	}

	/**
	 * @param string $path
	 * @param string|bool $version
	 * @param bool $use_cdn
	 * @return string
	 */
	public function getAssetUrl($path, $version = true, $use_cdn = true)
	{
		if (preg_match('/^(http:|https:)?\/\//', $path)) {
			return $path;
		}
		$version = $version === false ? '' : '?v=' . ($version !== true ? $version : $this->version);
		$path = str_replace('\\', '/', $path);
		if ($use_cdn && $this->enableCdn && $this->baseCdnUrl) {
			return $this->baseCdnUrl . $path . $version;
		}

		return $this->baseUrl . $path . $version;
	}

	public function getAssetUrlWithFilename($filename, $version = true, $use_cdn = true)
	{
		return $this->getAssetUrl(str_replace(PHPFOX_PARENT_DIR, '', $filename), $version, $use_cdn);
	}

	/**
	 * @param bool $use_cdn
	 * @return string|null
	 * @return string
	 */
	public function getAssetBaseUrl($use_cdn = true)
	{
		if ($use_cdn && $this->enableCdn && $this->baseCdnUrl) {
			return $this->baseCdnUrl;
		}

		return $this->baseUrl;
	}


	/**
	 * Update bundle files
	 * @param string $path
	 * @return bool
	 * @since 4.8.0
	 */
	public function bundleCssFile($path)
	{
		if (!$path) {
			$path = PHPFOX_DIR_FILE . 'static' . PHPFOX_DS . 'autoload' . Phpfox::getFullVersion() . '.css';
		}

		$aBundleFiles = Phpfox::getLib('template')->loadBundleFiles();
		$aBundleFiles = array_filter($aBundleFiles, function ($file) {
			return strpos($file, '.css');
		});

		Phpfox::getLib('file.minimize')->css($path, $aBundleFiles);

		if (file_exists($path)) {
			$this->putAssetContents(str_replace(PHPFOX_PARENT_DIR, '', $path), file_get_contents($path));
		}

		(($sPlugin = Phpfox_Plugin::get('core.assets_bundle_css')) ? eval($sPlugin) : false);
		return true;
	}

	public function bundleJsFile($path)
	{
		$aBundleFiles = Phpfox::getLib('template')->loadBundleFiles();

		$aBundleFiles = array_filter($aBundleFiles, function ($file) {
			return strpos($file, '.js') > 0;
		});

		Phpfox::getLib('file.minimize')->js($path, $aBundleFiles);

		// @since 4.8.0 update asset url.
		if (file_exists($path)) {
			$this->putAssetContents(str_replace(PHPFOX_PARENT_DIR, '', $path), file_get_contents($path));
		}
		(($sPlugin = Phpfox_Plugin::get('core.assets_bundle_js')) ? eval($sPlugin) : false);
	}

	public function bundleJsCss(){
		$this->bundleCssFile(PHPFOX_DIR_FILE . 'static' . PHPFOX_DS . 'autoload-' . Phpfox::getFullVersion() . '.css');
		$this->bundleJsFile(PHPFOX_DIR_FILE . 'static' . PHPFOX_DS . 'autoload-' . Phpfox::getFullVersion() . '.js');

		(($sPlugin = Phpfox_Plugin::get('core.assets_bundle_jscss')) ? eval($sPlugin) : false);
	}
}