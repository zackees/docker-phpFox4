<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Storage;

use InvalidArgumentException;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem as FlyFilesystem;


class Filesystem extends FlyFilesystem implements FilesystemInterface
{
    /**
     * @var string
     */
    private $serverId;

    public function __construct(AdapterInterface $adapter, $config = [])
    {
        $this->serverId = isset($config['server_id']) ? $config['server_id'] : (isset($config['storage_id']) ? $config['storage_id'] : 0);

        parent::__construct($adapter, $config);
    }

    public function setExtraConfig($name, $value = null)
    {
        if (!is_object($this->config) || empty($name) || (!is_array($name) && (!isset($value) || $value == ''))) {
            return false;
        }

        if (!is_array($name)) {
            $name = [$name => $value];
        }

        foreach ($name as $key => $config) {
            $this->config->set($key, $config);
        }
    }

    public function putFile($file, $path = null, $config = null)
    {
        if (empty($path)) {
            $path = str_replace("\\", '/', str_replace(PHPFOX_DIR, '', $file));
        }

        if (empty($config)) {
            $config = ['visibility' => 'public'];
        }

        $resource = fopen($file, 'r');
        if (!$resource) {
            throw new InvalidArgumentException("$file does not exists!");
        }

        $adapter = $this->getAdapter();
        $isLocal = $adapter instanceof LocalAdapter;

        if ($isLocal) {
            if (realpath($adapter->getPathPrefix() . $path) === realpath($file)) {
                return true;
            }
        }

        $result = $this->putStream($path, $resource, $config);

        if ($result) {
            if (!$isLocal) {
                $originalConfig = $this->getConfig();
                if (empty($originalConfig->get('keep_files_in_server'))) {
                    register_shutdown_function(function () use ($file) {
                        @unlink($file);
                    });
                }
            }
        }

        fclose($resource);
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function remove($path)
    {
        return $this->getAdapter()->delete($path);
    }

    /**
     * @inheritDoc
     */
    public function getUrl($path)
    {
        $path = str_replace(\Phpfox::getParam('core.path_file'), '', $path);
        $path = str_replace("\\", '/', $path);

        return $this->getAdapter()->getUrl($path);
    }

    /**
     * @inheritDoc
     */
    public function getServerId()
    {
        return $this->serverId;
    }

    public function isValid()
    {
        return $this->getAdapter()->isValid();
    }
}