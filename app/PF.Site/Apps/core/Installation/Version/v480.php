<?php
namespace Apps\PHPfox_Core\Installation\Version;

use Phpfox;

class v480
{
    public function process()
    {
        $this->_initDatabaseRecords();
        $this->_initStorageItems();
    }

    private function _initStorageItems()
    {
        $initActiveCdn = storage()->get('core_is_init_first_active_cdn');
        if (empty($initActiveCdn) || empty($initActiveCdn->value) && defined('PHPFOX_IS_UPGRADE')) {
            Phpfox::getCoreApp();
            if (Phpfox::isAppActive('PHPfox_CDN') && setting('pf_cdn_enabled')) {
                $r = new \ReflectionClass('Apps\PHPfox_CDN\Model\CDN');
                $cdnObject = $r->newInstance();
                if (is_object($cdnObject)) {
                    $servers = get_from_cache('pf_cdn_servers', function () {
                        $aResult = [];
                        foreach (storage()->all('pf_cdn_servers') as $iKey => $aServer) {
                            $iKey+= 2;
                            $aResult[$iKey] = (array)$aServer->value;
                        }
                        return $aResult;
                    });

                    if (!empty($servers)) {
                        $defaultServerId = $cdnObject->getServerId();
                        $multiInsert = [];

                        Phpfox::getLib('database')->update(':core_storage', ['is_default' => 0]);

                        foreach ($servers as $serverKey => $server) {
                            $serviceId = preg_match('/https:\/\//', $server['upload']) ? 'sftp' : 'ftp';
                            $parsedUrl = parse_url($server['upload']);
                            $multiInsert[] = [
                                'storage_id' => $serverKey,
                                'service_id' => $serviceId,
                                'is_default' => $serverKey == $defaultServerId ? 1 : 0,
                                'is_active' => 1,
                                'storage_name' => strtoupper($serviceId),
                                'config' => json_encode([
                                    'host' => $parsedUrl['scheme'] . '://' . $parsedUrl['host'],
                                    'port' => $serviceId == 'sftp' ? 22 : 21,
                                    'base_url' => $server['url'],
                                ]),
                            ];
                        }

                        if (!empty($multiInsert)) {
                            db()->multiInsert(Phpfox::getT('core_storage'), ['storage_id', 'service_id', 'is_default', 'is_active', 'storage_name', 'config'], $multiInsert);
                        }
                    }
                }
            }
            if (Phpfox::isAppActive('PHPfox_AmazonS3') && setting('cdn_enabled')) {
                $r = new \ReflectionClass('Apps\PHPfox_AmazonS3\Model\CDN');
                $cdnObject = $r->newInstance();
                if (is_object($cdnObject)) {
                    Phpfox::getLib('database')->update(':core_storage', ['is_default' => 0]);
                    db()->insert(':core_storage', [
                        'storage_id' => $cdnObject->getServerId(),
                        'service_id' => 's3',
                        'is_default' => 1,
                        'is_active' => 1,
                        'storage_name' => 'Amazon S3',
                        'config' => json_encode([
                            'key' => Phpfox::getParam('amazons3.cdn_amazon_id', ''),
                            'secret' => Phpfox::getParam('amazons3.cdn_amazon_secret', ''),
                            'bucket' => Phpfox::getParam('amazons3.cdn_bucket', ''),
                            'region' => Phpfox::getParam('amazons3.cdn_region', ''),
                            'cloudfront_url' => Phpfox::getParam('amazons3.amazon_cloudfront_url', ''),
                            'cloudfront_enabled' => !!Phpfox::getParam('amazons3.amazon_cloudfront_url', ''),
                            'prefix'=> '',
                        ]),
                    ]);
                }
            }
            if (Phpfox::isAppActive('Core_DO_Space') && setting('do_space_enabled')) {
                $r = new \ReflectionClass('Apps\Core_DO_Space\Model\Space');
                $cdnObject = $r->newInstance();
                if (is_object($cdnObject)) {
                    Phpfox::getLib('database')->update(':core_storage', ['is_default' => 0]);
                    db()->insert(':core_storage', [
                        'storage_id' => $cdnObject->getServerId(),
                        'service_id' => 'dospace',
                        'is_default' => 1,
                        'is_active' => 1,
                        'storage_name' => 'Digital Ocean Space',
                        'config' => json_encode([
                            'key' => Phpfox::getParam('dospace.do_api_key', ''),
                            'secret' => Phpfox::getParam('dospace.do_api_secret', ''),
                            'region' => Phpfox::getParam('dospace.do_space_region', ''),
                            'bucket' => Phpfox::getParam('dospace.do_space_name', ''),
                            'prefix' => Phpfox::getParam('dospace.do_sub_dir', ''),
                        ]),
                    ]);
                }
            }

            storage()->set('core_is_init_first_active_cdn', 1);
        }
    }

    private function _initDatabaseRecords()
    {
        $tableName = Phpfox::getT('core_log_service');
        $count = db()->select('COUNT(service_id)')
            ->from($tableName)
            ->executeField(false);
        if (!$count) {
            $defaultLogItems = [
                [
                    'service_id' => 'local',
                    'service_phrase_name' => 'local_filesystem',
                    'service_class' => 'Core\Log\StreamLogger',
                    'config' => '{"level":"100"}',
                    'level_name' => 'DEBUG',
                    'is_active' => 1,
                    'is_share' => 0,
                    'edit_link' => 'admincp.setting.logger.local',
                ],
                [
                    'service_id' => 'database',
                    'service_phrase_name' => 'database',
                    'service_class' => 'Core\Log\DatabaseAdapter',
                    'config' => '{"level":"100"}',
                    'level_name' => 'DEBUG',
                    'is_active' => 0,
                    'is_share' => 1,
                    'edit_link' => 'admincp.setting.logger.database',
                ],
                [
                    'service_id' => 'mongodb',
                    'service_phrase_name' => 'mongodb',
                    'service_class' => 'Core\Log\MongoDbHandler',
                    'config' => '{"level":"100"}',
                    'level_name' => 'DEBUG',
                    'is_active' => 0,
                    'is_share' => 1,
                    'edit_link' => 'admincp.setting.logger.mongodb',
                ]
            ];

            db()->multiInsert($tableName, ['service_id', 'service_phrase_name', 'service_class', 'config', 'level_name', 'is_active', 'is_share', 'edit_link'], $defaultLogItems);
        }

        $tableName = Phpfox::getT('core_sqs_service');
        $count = db()->select('COUNT(service_id)')
            ->from($tableName)
            ->executeField(false);
        if (!$count) {
            $defaultQueueServiceItems = [
                [
                    'service_id' => 'database',
                    'service_class' => 'Core\Queue\JobRepositoryDatabase',
                    'service_phrase_name' => 'database',
                    'edit_link' => 'admincp.setting.queue.database',
                ],
                [
                    'service_id' => 'mongodb',
                    'service_class' => 'Core\Queue\MongoDbAdapter',
                    'service_phrase_name' => 'mongodb',
                    'edit_link' => 'admincp.setting.queue.mongodb',
                ],
                [
                    'service_id' => 'redis',
                    'service_class' => 'Core\Queue\RedisAdapter',
                    'service_phrase_name' => 'redis_rsmq',
                    'edit_link' => 'admincp.setting.queue.redis',
                ],
                [
                    'service_id' => 'beanstalk',
                    'service_class' => 'Core\Queue\PheanstalkAdapter',
                    'service_phrase_name' => 'beanstalk',
                    'edit_link' => 'admincp.setting.queue.beanstalk',
                ],
                [
                    'service_id' => 'amazon-sqs',
                    'service_class' => 'Core\Queue\AwsSQSAdapter',
                    'service_phrase_name' => 'aws_sqs',
                    'edit_link' => 'admincp.setting.queue.sqs',
                ],
            ];
            db()->multiInsert($tableName, ['service_id', 'service_class', 'service_phrase_name', 'edit_link'], $defaultQueueServiceItems);
        }

        $tableName = Phpfox::getT('core_sqs_queue');
        $count = db()->select('COUNT(queue_id)')
            ->from($tableName)
            ->executeField(false);
        if (!$count) {
            $defaultQueueItems = [
                [
                    'service_id' => 'database',
                    'queue_name' => 'default',
                    'config' => '{}',
                    'is_active' => 1,
                ],
            ];
            db()->multiInsert($tableName, ['service_id', 'queue_name', 'config', 'is_active'], $defaultQueueItems);
        }


        $tableName = Phpfox::getT('core_session_service');
        $count = db()->select('COUNT(service_id)')
            ->from($tableName)
            ->executeField(false);
        if (!$count) {
            $defaultSessionItems = [
                [
                    'service_id' => 'local',
                    'service_phrase_name' => 'local_session_storage',
                    'service_class' => 'Core\Session\BuiltInAdapter',
                    'config' => '{}',
                    'is_default' => 1,
                    'edit_link' => 'admincp.setting.session.local',
                ],
                [
                    'service_id' => 'redis',
                    'service_phrase_name' => 'redis_session_storage',
                    'service_class' => 'Core\Session\RedisAdapter',
                    'config' => '{}',
                    'is_default' => 0,
                    'edit_link' => 'admincp.setting.session.redis',
                ],
                [
                    'service_id' => 'database',
                    'service_phrase_name' => 'database_session_storage',
                    'service_class' => 'Core\Session\DatabaseAdapter',
                    'config' => '{}',
                    'is_default' => 0,
                    'edit_link' => 'admincp.setting.session.database',
                ],
                [
                    'service_id' => 'mongodb',
                    'service_phrase_name' => 'mongodb_session_storage',
                    'service_class' => 'Core\Session\MongoDbAdapter',
                    'config' => '{}',
                    'is_default' => 0,
                    'edit_link' => 'admincp.setting.session.mongodb',
                ],
                [
                    'service_id' => 'memcached',
                    'service_phrase_name' => 'memcached_session_storage',
                    'service_class' => 'Core\Session\MemcachedAdapter',
                    'config' => '{}',
                    'is_default' => 0,
                    'edit_link' => 'admincp.setting.session.memcached',
                ],
            ];
            db()->multiInsert($tableName, ['service_id', 'service_phrase_name', 'service_class', 'config', 'is_default', 'edit_link'], $defaultSessionItems);
        }

        $tableName = Phpfox::getT('core_storage_service');
        $count = db()->select('COUNT(service_id)')
            ->from($tableName)
            ->executeField(false);
        if (!$count) {
            $defaultStorageItems = [
                [
                    'service_id' => 'ftp',
                    'service_class' => 'Core\Storage\FtpAdapter',
                    'service_phrase_name' => 'FTP',
                    'module_id' => 'core',
                    'edit_link' => 'admincp.setting.storage.ftp',
                    'sort_order' => 1
                ],
                [
                    'service_id' => 'sftp',
                    'service_class' => 'Core\Storage\SFTPAdapter',
                    'service_phrase_name' => 'SFTP/SSH',
                    'module_id' => 'core',
                    'edit_link' => 'admincp.setting.storage.sftp',
                    'sort_order' => 2
                ],
                [
                    'service_id' => 's3',
                    'service_class' => 'Core\Storage\AwsS3V3Adapter',
                    'service_phrase_name' => 'Amazon S3',
                    'module_id' => 'core',
                    'edit_link' => 'admincp.setting.storage.s3',
                    'sort_order' => 3,
                ],
                [
                    'service_id' => 'dospace',
                    'service_class' => 'Core\Storage\DigitalOceanSpaceAdapter',
                    'service_phrase_name' => 'Digital Ocean Space',
                    'module_id' => 'core',
                    'edit_link' => 'admincp.setting.storage.dospace',
                    'sort_order' => 4,
                ],
				[
					'service_id' => 's3compatible',
					'service_class' => 'Core\Storage\S3CompatibleAdapter',
					'service_phrase_name' => 's3compatible',
					'module_id' => 'core',
					'edit_link' => 'admincp.setting.storage.s3compatible',
					'sort_order' => 5,
				],
            ];
            db()->multiInsert($tableName, ['service_id', 'service_class', 'service_phrase_name', 'module_id', 'edit_link', 'sort_order'], $defaultStorageItems);
        }
    }
}