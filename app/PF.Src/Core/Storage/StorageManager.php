<?php
/**
 * @author  phpfox
 * @license phpfox.com
 */

namespace Core\Storage;

use Phpfox;

/**
 * Class StorageManager
 * @package Core\Storage
 */
final class StorageManager
{

    /**
     * @var Filesystem[]
     */
    private $sharedFilesystem = [];

    /**
     * @var array[]
     */
    private $configAdapters;

    /**
     * @var array
     */
    private $aliasMap = [];

    /**
     * @var string
     */
    private $defaultStorageId = null;


    public function __construct()
    {
        $this->loadStorageConfigs();
    }


    /**
     * @return string
     */
    public function getStorageId()
    {
        return $this->defaultStorageId;
    }

    /**
     * @param string     $id
     * @param Filesystem $service
     */
    public function addStorage($id, $service)
    {
        $this->sharedFilesystem[$id] = $service;
    }

    /**
     * @param string $storage_id
     *
     */
    public function setStorageId($storage_id = null)
    {
        if ($storage_id === null || empty($this->configAdapters[$storage_id])) {
            $storage_id = '0';
        }
        $this->defaultStorageId = $storage_id;
    }

    /**
     * @param string $storage_id
     *
     * @return Filesystem
     */
    public function get($storage_id = null)
    {
        if ($storage_id === null || $storage_id === '') {
            $storage_id = $this->defaultStorageId;
        }

        return !empty($this->sharedFilesystem[$storage_id]) ? $this->sharedFilesystem[$storage_id] : $this->make($storage_id);
    }

    /**
     * @param string $storage_id
     *
     * @return Filesystem
     */
    private function make($storage_id = null)
    {
        $origin = $storage_id;

        if ($storage_id === null || $storage_id === '') {
            $storage_id = $this->defaultStorageId;
        }

        if (!empty($this->aliasMap[$storage_id])) {
            $storage_id = $this->aliasMap[$storage_id];
        }

        if (empty($this->configAdapters[$storage_id]) && $storage_id !== $this->defaultStorageId) {
            $storage_id = $this->defaultStorageId;
        }

        if (empty($this->configAdapters[$storage_id])) {
            $storage_id = '0';
        }

        if ($origin !== null && !array_key_exists($origin, $this->aliasMap)) {
            $this->aliasMap[$origin] = $storage_id;
        }

        $config = $this->configAdapters[$storage_id];

        $service_class = $config['service_class'];
        $params = $config['config'];

        // todo where to check this params.
        $keep_files_in_server = isset($params['keep_files_in_server']) ? $params['keep_files_in_server'] : Phpfox::getParam('core.keep_files_in_server');
        $params['storage_id'] = $storage_id;

        if (class_exists($service_class)) {
            $this->sharedFilesystem[$storage_id] = new Filesystem(new $service_class($params), [
                'storage_id'           => $storage_id,
                'keep_files_in_server' => $keep_files_in_server,
            ]);
        }
        return $this->sharedFilesystem[$storage_id];

    }

    private function loadStorageConfigs()
    {
        $finalItems = [
            '0' => [
                'storage_id'    => '0',
                'service_class' => 'Core\Storage\LocalAdapter',
                'is_default'    => false,
                'config'        => [
                    'storage_id' => '0'
                ]
            ]
        ];

        $this->defaultStorageId = Phpfox::getLib('storage.admincp')->getDefaultStorageId();

        list($defaultConfigured, $configuredItems) = Phpfox::getLib('storage.admincp')->getConfiguredItems();

        if ($configuredItems) {
            $configuredItems = array_combine(array_column($configuredItems, 'storage_id'), $configuredItems);
            $finalItems = array_merge($finalItems, $configuredItems);
        }

        $cache = Phpfox::getLib('cache');
        $cacheId = $cache->set('pf_core_storage_configs');
        if (($createdItems = $cache->getLocalFirst($cacheId)) === false) {
            $createdItems = $this->loadStorageConfigsFromDatabase();
            $cache->saveBoth($cacheId, $createdItems);
            $cache->group('core_storage', $cacheId);
        }

        if ($createdItems) {
            $finalItems = array_merge($finalItems, $createdItems);
        }

        if (!isset($this->defaultStorageId)) {
            if (isset($defaultConfigured) && $defaultConfigured != '') {
                $this->defaultStorageId = $defaultConfigured;
            } else {
                $this->defaultStorageId = 0;

                foreach ($createdItems as $createdItem) {
                    if ($createdItem['is_default']) {
                        $this->defaultStorageId = $createdItem['storage_id'];
                    }
                }
            }
        }

        $finalItems = array_combine(array_column($finalItems, 'storage_id'), array_values($finalItems));

        $this->configAdapters = $finalItems;
    }

    private function loadAvailableStorageServices()
    {
        $results = [
            'local' => 'Core\Storage\LocalAdapter'
        ];

        foreach (Phpfox::getLib('database')
                     ->select('s.service_class, s.service_id')
                     ->from(':core_storage_service', 's')
                     ->execute('getSlaveRows') as $row) {
            $results[$row['service_id']] = $row['service_class'];
        }

        return $results;
    }

    private function loadStorageConfigsFromDatabase()
    {

        $configs = [];

        $rows = Phpfox::getLib('database')
            ->select('d.*, s.edit_link, s.service_class, s.service_phrase_name')
            ->from(':core_storage', 'd')
            ->join(':core_storage_service', 's', 's.service_id=d.service_id')
            ->where('d.is_active=1')
            ->execute('getSlaveRows');

        foreach ($rows as $row) {
            $storage_id = $row['storage_id'];
            $params = json_decode($row['config'], true);
            $params['storage_id'] = $storage_id;
            $configs[$storage_id] = [
                'storage_id'    => $storage_id,
                'service_class' => $row['service_class'],
                'is_default'    => !!$row['is_default'],
                'config'        => $params,
            ];
        }
        return $configs;
    }
}