<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Storage;

use InvalidArgumentException;
use Phpfox;
use Phpfox_Error;
use Phpfox_Plugin;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Class StorageManager
 * @package Core\Storage
 */
final class Admincp
{
    /**
     * @param $getIds
     * @return array
     */
    public function getActiveItems($getIds = true)
    {
        $cacheObject = Phpfox::getLib('cache');
        $cacheId = $cacheObject->set('pf_core_storage_configs_active');

        if (($items = $cacheObject->get($cacheId)) === false) {
            $items = db()->select('*')
                ->from(':core_storage')
                ->where([
                    'is_active' => 1
                ])->executeRows();
            $cacheObject->save($cacheId, $items);
            $cacheObject->group('core_storage', $cacheId);
        }

        if ($getIds && $items) {
            $items = array_column($items, 'storage_id');
        }

        return $items;
    }

    /**
     * @param $storageId
     * @return bool
     */
    public function isActive($storageId)
    {
        if (!$storageId) {
            return false;
        }

        list(, $configuredItems) = $this->getConfiguredItems();

        if (!empty($configuredItems = array_column($configuredItems, 'storage_id'))
            && in_array($storageId, $configuredItems)) {
            return true;
        }

        return in_array($storageId, $this->getActiveItems());
    }

    /**
     * @return boolean|integer
     */
    public function getLastStorageIdFromConfig()
    {
        list(, $items) = $this->getConfiguredItems();

        if (!$items || empty($storageIds = array_column($items, 'storage_id'))) {
            return false;
        }

        if (count($storageIds) > 1) {
            rsort($storageIds);
        }

        return array_shift($storageIds);
    }

    /**
     * @param $getDefault
     * @return string|int
     */
    public function getDefaultStorageId($getDefault = false)
    {
        $defaultStorage = storage()->get('default_storage_item');

        $default = is_object($defaultStorage) && isset($defaultStorage->value) ? (is_array($defaultStorage->value) ? array_shift($defaultStorage->value) : $defaultStorage->value) : null;

        if (is_null($default) && $getDefault) {
            list($configuredDefault, $configuredItems) = $this->getConfiguredItems();

            if ($configuredItems) {
                $default = $configuredDefault;
            } else {
                $default = db()->select('storage_id')
                    ->from(':core_storage')
                    ->where([
                        'is_default' => 1
                    ])->executeField();
            }

            if (!$default) {
                $default = 0;
            }
        }

        return $default;
    }

    public function getConfiguredItems()
    {
        static $configuredItems = null;
        static $configuredDefault = null;

        if (!isset($configuredItems) || !isset($configuredDefault)) {
            $configuredItems =  Phpfox::getParam('core.storage_handling', []);
            $configuredDefault = Phpfox::getParam('core.storage_default', 0);

            if ($configuredItems) {
                $availableStorageServices = $this->loadAvailableStorageServices();
                $parsedItems = [];

                foreach ($configuredItems as $serverId => $configuredItem) {
                    if (!$configuredItem
                        || empty($configuredItem['driver'])
                        || empty($availableStorageServices[$configuredItem['driver']])) {
                        continue;
                    }

                    $parsedItems[] = [
                        'storage_id' => $serverId,
                        'service_class' => $availableStorageServices[$configuredItem['driver']],
                        'service_name'=> $configuredItem['driver'],
                        'service_phrase_name'=> $configuredItem['driver'],
                        'is_active' => 1,
                        'config' => $configuredItem,
                        'is_configured' => true,
                        'storage_name' => isset($configuredItem['storage_name']) ? $configuredItem['storage_name'] : $configuredItem['driver'],
                    ];
                }

                $configuredItems = $parsedItems;
            }
        }

        return [$configuredDefault, $configuredItems];
    }

    /**
     * @return array
     */
    public function getStorageServices()
    {
        return Phpfox::getLib('database')
            ->select('s.*')
            ->from(':core_storage_service', 's')
            ->order('s.sort_order')
            ->execute('getSlaveRows');
    }

    /**
     * @param $service_id
     * @return array
     */
    public function getStorageService($service_id)
    {
        return Phpfox::getLib('database')
            ->select('s.*')
            ->from(':core_storage_service', 's')
            ->where(['s.service_id' => (string)$service_id])
            ->execute('getSlaveRow');
    }

    public function getAllStorage($activeOnly = false, $includeLocal = true)
    {
        $items = [];

        if ($includeLocal) {
            $items[] = $this->getLocalStorageItem();
        }

        list($configuredDefault, $configuredItems) = $this->getConfiguredItems();

        if ($configuredItems) {
            $items = array_merge($items, $configuredItems);
        }

        $createdItems = db()
            ->select('d.*, s.edit_link, s.service_class, s.service_phrase_name')
            ->join(':core_storage_service', 's', 's.service_id=d.service_id')
            ->from(':core_storage', 'd');

        if ($activeOnly) {
            $createdItems->where(['d.is_active' => 1]);
        }

        $createdItems = $createdItems->execute('getSlaveRows');

        foreach ($createdItems as $row) {
            $items[] = $row;
        }

        $defaultStorageId = $this->getDefaultStorageId();

        if (!isset($defaultStorageId)) {
            if (isset($configuredDefault) && $configuredDefault != '') {
                $defaultStorageId = $configuredDefault;
            } else {
                $defaultStorageId = 0;

                foreach ($createdItems as $createdItem) {
                    if (!empty($createdItem['is_default'])) {
                        $defaultStorageId = $createdItem['storage_id'];
                        break;
                    }
                }
            }
        }

        foreach ($items as $key => $item) {
            $items[$key]['is_default'] = $item['storage_id'] == $defaultStorageId;
        }

        return $items;
    }

    private function getLocalStorageItem()
    {
        $isDefault = Phpfox::getLib('database')
            ->select('d.*')
            ->from(':core_storage', 'd')
            ->where('d.is_default=1')
            ->execute('getSlaveRow');

        return [
            'storage_id' => '0',
            'service_id' => 'local',
            'service_phrase_name' => 'local_storage',
            'storage_name' => _p('local_storage'),
            'is_active' => 1,
            'is_default' => !$isDefault,
            'is_editable' => 0,
            'edit_link' => 'admincp.setting.storage.local'
        ];
    }

    /**
     * @param string $storageId
     * @return array|null
     */
    public function getStorageById($storageId)
    {
        // fallback to default server id.
        if ((string)$storageId == 0) {
            return $this->getLocalStorageItem();
        }

        if (!$storageId)
            return null;

        return Phpfox::getLib('database')
            ->select('d.*, s.edit_link, s.service_class, s.service_phrase_name')
            ->from(':core_storage', 'd')
            ->join(':core_storage_service', 's', 's.service_id=d.service_id')
            ->where("d.storage_id='{$storageId}'")
            ->execute('getSlaveRow');
    }

    /**
     * @param string $storageId
     * @return array
     */
    public function getStorageConfig($storageId)
    {
        $row = $this->getStorageById($storageId);
        if (!$row) {
            throw new InvalidArgumentException("Could not found storage '$storageId'");
        }
        if (!empty($row['config'])) {
            $config = json_decode($row['config'], true);
        } else {
            $config = [];
        }

        return array_merge($row, $config);
    }

    public function createStorage($storageId, $serviceId, $storageName, $isDefault, $isActive, $config)
    {
        $adapter = db()
            ->select('d.*')
            ->from(':core_storage_service', 'd')
            ->where("service_id='{$serviceId}' ")
            ->execute('getSlaveRow');

        if ($storageId && $this->getStorageById($storageId)) {
            // server is exists.
            return true;
        }

        if (!$adapter) {
            throw new InvalidArgumentException("Could not found adapter '{$serviceId}'");
        }

        $data = [
            'is_active' => $isActive || $isDefault,
            'is_default' => $isDefault,
            'storage_name' => $storageName,
            'config' => json_encode($config, JSON_FORCE_OBJECT),
            'service_id' => $serviceId,
        ];

        if ($storageId) {
            $data['storage_id'] = $storageId;
        } elseif ($lastConfigId = $this->getLastStorageIdFromConfig()) {
            $total = db()->select('COUNT(*)')
                ->from(':core_storage')
                ->executeField(false);
            if (!$total) {
                $data['storage_id'] = (int)$lastConfigId + 1;
            }
        }

        if ($success = db()->insert(':core_storage', $data)) {
            if ($isDefault) {
                $storageId = (int)(!empty($data['storage_id']) ? $data['storage_id'] : $success);
                db()->update(':core_storage', ['is_default' => 0], 'storage_id <> ' . $storageId);
                $this->updateDefault($storageId);
            }
            $this->clearCacheConfig();
        }

        return $success;
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

    /**
     * @param string $serviceId
     * @param array $config
     * @return bool
     */
    public function verifyStorageConfig($serviceId, $config)
    {
        $services = $this->loadAvailableStorageServices();

        if(!isset($services[$serviceId])){
            throw new InvalidArgumentException("Service ${serviceId} does not found");
        }

        $service_class =  $services[$serviceId];

        if(!class_exists($service_class)){
            throw new InvalidArgumentException("Service ${serviceId} does not support");
        }

        return false !== (new $service_class($config))->isValid();
    }

    /**
     * @param string $storageId
     * @param $serviceId
     * @param $storageName
     * @param $isDefault
     * @param $isActive
     * @param $config
     * @return bool
     */
    public function updateStorageConfig($storageId, $serviceId, $storageName, $isDefault, $isActive, $config)
    {
        $databaseObject = db();

        // check if is local server.
        if ((string)$storageId === '0') {
            if ($isDefault) {
                $databaseObject->update(':core_storage', ['is_default' => 0], 'is_default = 1');
                $this->clearCacheConfig();
            }
            return true;
        }

        $row = $this->getStorageById($storageId);

        if (!$row) {
            $success = $this->createStorage($storageId, $serviceId, $storageName, $isDefault, $isActive, $config);
        } elseif ($success = $databaseObject->update(':core_storage', [
            'is_default' => $isDefault,
            'is_active' => $isActive || $isDefault,
            'storage_name' => $storageName,
            'config' => json_encode($config),
        ], ['storage_id' => $storageId])) {
            if (!$row['is_default'] && $isDefault) {
                $databaseObject->update(':core_storage', ['is_default' => 0], 'storage_id <> ' . (int)$storageId);
            }
            $this->clearCacheConfig();
        }

        return $success;
    }

    /**
     * Active or de-active a category. This function in adminCP only
     *
     * @param int $storageId
     * @param int $active
     */
    public function updateStorageActive($storageId, $active)
    {
        Phpfox::isUser(true);
        Phpfox::isAdmin(true);
        $storage = $this->getStorageConfig($storageId);
        if (empty($storage)) {
            return false;
        }
        $active = (int)$active;
        $bIsValid = true;
        if (!$active) {
            if (db()->update(':core_storage', ['is_default' => 0], 'storage_id = ' . (int)$storageId)) {
                if ($storageId == $this->getDefaultStorageId(true)) {
                    $this->updateDefault(Phpfox::getParam('core.storage_default', 0));
                }
            }
        } else {
            //Validate config
            try {
                if (!empty($storage['config'])) {
                    $bIsValid = $this->verifyStorageConfig($storage['service_id'], json_decode($storage['config'], true));
                } else {
                    $bIsValid = false;
                }
            } catch (\Exception $exception) {
                $bIsValid = false;
            }
        }
        if ($bIsValid) {
            db()->update(':core_storage', [
                'is_active' => ($active == 1 ? 1 : 0)
            ], 'storage_id = ' . (int)$storageId);
            $this->clearCacheConfig();
            return true;
        } else {
            Phpfox_Error::set(_p('invalid_configuration'));
        }
        return false;
    }

    /**
     * Active or de-active a category. This function in adminCP only
     *
     * @param int $storageId
     * @param int $default
     */
    public function updateStorageDefault($storageId, $default)
    {
        Phpfox::isUser(true);
        Phpfox::isAdmin(true);

        list(, $configuredItems) = $this->getConfiguredItems();
        $isFromConfig = in_array($storageId, array_column($configuredItems, 'storage_id'));

        if (!$isFromConfig) {
            $storage = $this->getStorageConfig($storageId);
            if (empty($storage)) {
                return false;
            }
        }

        $default = (int)$default;
        $result = true;

        if ($default) {
            db()->update(':core_storage', ['is_default' => 0], 'is_default = 1');
            if (!$isFromConfig && !$storage['is_active']) {
                $result = $this->updateStorageActive($storageId, 1);
            }
        }

        if ($result) {
            $newDefaultId = Phpfox::getParam('core.storage_default', 0);
            $this->updateDefault($default ? $storageId : ($newDefaultId != $storageId ? $newDefaultId : 0));
            $this->clearCacheConfig();
        }

        return true;
    }

    private function updateDefault($storageId)
    {
        storage()->del('default_storage_item');
        storage()->set('default_storage_item', (int)$storageId);
    }

    public function deleteStorage($storageId)
    {
        Phpfox::isUser(true);
        Phpfox::isAdmin(true);
        $storage = $this->getStorageConfig($storageId);
        if (empty($storage)) {
            return false;
        }
        if (db()->delete(':core_storage', ['storage_id' => (int)$storageId])) {
            if (!empty($storage['is_default'])) {
                $this->updateDefault(Phpfox::getParam('core.storage_default', 0));
            }
            $this->clearCacheConfig();
        }
        return true;
    }

    public function getSiteFiles()
    {
        $dirs = [
            PHPFOX_DIR_FILE
        ];

        $files = [];
        $skip = "#\.(htaccess|gitignore)$#i";
        $skip_extra = "#(v3\.phpfox|checksum|license)$#i";
        $allow_path = ['ad', 'attachment', 'blog', 'comment', 'egift', 'event', 'forum',
            'groups', 'pages', 'music', 'core-im-sounds', 'marketplace', 'message',
            'mobile', 'photo', 'poll', 'quiz', 'preaction', 'pstatusbg',
            'subscribe', 'video'];
        $skip_path = "#(\.git|\.idea|\/cache|\/session|\/settings|\/static|\/log)#i";

        (($sPlugin = Phpfox_Plugin::get('core.storage_admincp_get_site_files_start')) ? eval($sPlugin) : false);

        $allAllowPaths = Phpfox::massCallback('getTransferFileAllowPath');
        foreach($allAllowPaths as $key => $paths) {
            if (is_array($paths)) {
                foreach ($paths as $index => $path) {
                    $path = preg_replace('/\./','\.', $path);
                    $path = preg_replace('/\//','\/', $path);
                    $allow_path[] = $path;
                }
            } else {
                $paths = preg_replace('/\./','\.', $paths);
                $paths = preg_replace('/\//','\/', $paths);
                $allow_path[] = $paths;
            }
        }
        $allow_path = implode('|', $allow_path);
        $allow_path = "#({$allow_path})#i";
        foreach ($dirs as $dir) {
            $directory = new RecursiveDirectoryIterator($dir);
            $iterator = new RecursiveIteratorIterator($directory);

            foreach ($iterator as $info) {
                $filename = $info->getFilename();
                $pathname = $info->getPathname();
                $pathname = str_replace(PHPFOX_PARENT_DIR, '', $pathname);

                if (!$info->isFile()
                    || preg_match($skip, $filename)
                    || !preg_match($allow_path, $pathname)
                    || preg_match($skip_path, $pathname)
                    || preg_match($skip_extra, $filename)) {
                    continue;
                }
                $files[] = $pathname;
            }
        }

        (($sPlugin = Phpfox_Plugin::get('core.storage_admincp_get_site_files_end')) ? eval($sPlugin) : false);

        return $files;
    }

    /**
     * @param string[] $paths
     * @param string $storageId
     * @param $cache
     * @return bool
     */
    public function transferFiles($paths, $storageId, $cache)
    {
        $filesystem = Phpfox::getLib('storage')->get($storageId);

        if ($filesystem->getServerId() == '0') {
            return true;
        }
        $params = $cache->value;
        $removeFile = false;
        $successTransfer = $failTransfer = 0;
        if (!empty($params)) {
            $removeFile = isset($params->remove_file) ? $params->remove_file : false;
            $successTransfer = isset($params->success_file) ? $params->success_file : 0;
            $failTransfer = isset($params->fail_file) ? $params->fail_file : 0;
        }
        $deleteFileList = [];
        $failTransferList = [];
        foreach ($paths as $path) {
            $stream = fopen(PHPFOX_PARENT_DIR . $path, 'r');
            if (!$stream) {
                $failTransfer++;
                $failTransferList[] = $path;
                continue;
            }
            $result = $filesystem->putStream(str_replace('PF.Base/', '', $path), $stream, ['visibility' => 'public']);
            if ($result != false) {
                $deleteFileList[] = $path;
                $successTransfer++;
            } else {
                $failTransferList[] = $path;
                $failTransfer++;
            }
        }
        $completed = !empty($params->total_file) && ($successTransfer + $failTransfer) == $params->total_file;

        //Update cache
        storage()->updateById($cache->id, [
            'success_file' => $successTransfer,
            'fail_file' => $failTransfer,
            'update_time' => PHPFOX_TIME,
            'status' => $completed ? 'completed' : 'in_process'
        ]);

        if ($removeFile) {
            //Add job delete local file
            Phpfox::getLib('job.manager')->addJob('core_storage_transfer_files_remove_local', [
                'files' => $deleteFileList,
                'uniqId' => isset($params->uniq_id) ? $params->uniq_id : ''
            ]);
        }

        if ($failTransfer) {
            //Log list failed files
            Phpfox::getLog('storage.log')->error('Files Transfer Failed', $failTransferList);
        }

        return true;
    }

    public function transferFileUpdateDatabase($uniqId, $storageId)
    {
        $updateAppsTable = [
            'ad' => [
                [
                    'table_name' => 'ad',
                    'column_id' => 'server_id'
                ]
            ],
            'attachment' => [
                [
                    'table_name' => 'attachment',
                    'column_id' => 'server_id'
                ]
            ],
            'blog' => [
                [
                    'table_name' => 'blog',
                    'column_id' => 'server_id'
                ]
            ],
            'comment' => [
                [
                    'table_name' => 'comment',
                    'column_id' => 'server_id'
                ],
                [
                    'table_name' => 'comment_stickers',
                    'column_id' => 'server_id'
                ]
            ],
            'event' => [
                [
                    'table_name' => 'event',
                    'column_id' => 'server_id'
                ]
            ],
            'egift' => [
                [
                    'table_name' => 'egift',
                    'column_id' => 'server_id'
                ]
            ],
            'pages' => [
                [
                    'table_name' => 'pages',
                    'column_id' => 'image_server_id'
                ],
                [
                    'table_name' => 'pages_type',
                    'column_id' => 'image_server_id',
                    'extra_condition' => 'AND `image_path` NOT LIKE "%PF.Site/Apps%"'
                ],
                [
                    'table_name' => 'pages_widget',
                    'column_id' => 'image_server_id'
                ]
            ],
            'music' => [
                [
                    'table_name' => 'music_album',
                    'column_id' => 'server_id'
                ],
                [
                    'table_name' => 'music_playlist',
                    'column_id' => 'server_id'
                ],
                [
                    'table_name' => 'music_song',
                    'column_id' => 'image_server_id'
                ],
                [
                    'table_name' => 'music_song',
                    'column_id' => 'server_id'
                ],
            ],
            'photo' => [
                [
                    'table_name' => 'photo',
                    'column_id' => 'server_id'
                ]
            ],
            'im' => [
                [
                    'use_storage' => true,
                    'is_path' => true,
                    'cache_id' => 'core-im/sound',
                    'column_id' => 'custom_file',
                ]
            ],
            'marketplace' => [
                [
                    'table_name' => 'marketplace',
                    'column_id' => 'server_id'
                ],
                [
                    'table_name' => 'marketplace_image',
                    'column_id' => 'server_id'
                ]
            ],
            'mobile' => [
                [
                    'use_storage' => true,
                    'is_path' => false,
                    'cache_id' => 'mobile-api/logo',
                    'column_id' => 'server_id',
                ]
            ],
            'poll' => [
                [
                    'table_name' => 'poll',
                    'column_id' => 'server_id'
                ]
            ],
            'quiz' => [
                [
                    'table_name' => 'quiz',
                    'column_id' => 'server_id'
                ]
            ],
            'preaction' => [
                [
                    'table_name' => 'preaction_reactions',
                    'column_id' => 'server_id'
                ]
            ],
            'pstatusbg' => [
                [
                    'table_name' => 'pstatusbg_backgrounds',
                    'column_id' => 'server_id'
                ]
            ],
            'subscribe' => [
                [
                    'table_name' => 'subscribe_package',
                    'column_id' => 'server_id'
                ]
            ],
            'video' => [
                [
                    'table_name' => 'video',
                    'column_id' => 'server_id'
                ],
                [
                    'table_name' => 'video',
                    'column_id' => 'image_server_id'
                ]
            ]
        ];

        (($sPlugin = Phpfox_Plugin::get('core.storage_admincp_transfer_file_update_database_start')) ? eval($sPlugin) : false);

        //Get params update database from other apps
        $updateDatabaseCallback = Phpfox::massCallback('getTransferFileDatabaseParams');

        if (!empty($updateDatabaseCallback)) {
            $updateAppsTable = array_merge($updateAppsTable, $updateDatabaseCallback);
        }
        $sqlList = [];
        foreach ($updateAppsTable as $key => $params) {
            if (isset($params['columns_id'])) {
                $this->processTransferFileSql([$params], $storageId, $sqlList);
            } else {
                $this->processTransferFileSql($params, $storageId, $sqlList);
            }
        }
        //Log
        Phpfox::getLog('storage.log')->info('Transfer Files Update Database SQL: ', [implode(' ', $sqlList)]);

        $chunkSql = array_chunk($sqlList, 2);
        foreach ($chunkSql as $sql) {
            Phpfox::getLib('job.manager')->addJob('core_storage_transfer_files_update_db_execute', [
                'uniqId' => $uniqId,
                'sql_list' => $sql
            ]);
        }

        (($sPlugin = Phpfox_Plugin::get('core.storage_admincp_transfer_file_update_database_end')) ? eval($sPlugin) : false);

        return $sqlList;
    }

    /**
     * @param $params
     * @param $storageId
     * @param array $sqlList
     * @return bool
     */
    protected function processTransferFileSql($params, $storageId, &$sqlList)
    {
        foreach ($params as $miniKey => $param) {
            if (!empty($param['use_storage'])) {
                //Update cache, not database
                if (!isset($param['cache_id'], $param['column_id'])) {
                    continue;
                }
                $cache = storage()->get($param['cache_id']);
                if (empty($cache)) {
                    //Not value
                    continue;
                }
                $value = (array)$cache->value;
                if (isset($value[$param['column_id']])) {
                    if (!empty($param['is_path'])) {
                        $path = str_replace(PHPFOX_DIR_FILE, '', $value[$param['column_id']]);
                        $value[$param['column_id']] = Phpfox::getLib('cdn')->getUrl($path, $storageId);
                    } else {
                        $value[$param['column_id']] = $storageId;
                    }
                }
                storage()->updateById($cache->id, $value);
            } else {
                if (!isset($param['table_name'], $param['column_id'])) {
                    continue;
                }
                //Update database, generate sql
                $tableName = Phpfox::getT($param['table_name']);
                if (db()->tableExists($tableName)) {
                    //Make sure table is existed
                    $sqlList[] = 'UPDATE `' . $tableName . '` SET `' . $param['column_id'] . '` = ' . $storageId . ' WHERE `' . $param['column_id'] . '` = 0 ' . (isset($param['extra_condition']) ? $param['extra_condition'] : '') . ';';
                }
            }
        }
        return true;
    }

    private function clearCacheConfig()
    {
        Phpfox::getLib('cache')->removeGroup('core_storage');
    }
}