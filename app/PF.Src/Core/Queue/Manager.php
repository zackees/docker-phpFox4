<?php

namespace Core\Queue;

use Phpfox;
use Phpfox_Plugin;

/**
 * Class Manager
 *
 * @package Core\Queue
 */
class Manager
{
    /**
     * Maximum job fetch per request
     */
    const DEFAULT_LIMIT = 5;

    /**
     * Default life time
     */
    const DEFAULT_LIFETIME = 600;

    /**
     * Default cache id for deleting jobs
     */
    const DEFAULT_DELETED_JOB_CACHE_ID = 'pf_queue_deleted_{service_id}_{job_id}';

    /**
     * Default queue name
     */
    const DEFAULT_QUEUE_NAME = 'default';

    /**
     * @var Manager
     */
    private static $singleton;

    /**
     * @var array
     */
    private $handlerNames = [];

    /**
     * @var array
     */
    private $byQueueNames = [];

    private $envConfigs;

    private $serviceIdByQueueNames = [];

    /**
     * Manager constructor
     */
    public function __construct()
    {
        if (!self::$singleton) {
            self::$singleton = $this;
        }
    }

    public function factory()
    {
        return self::$singleton;
    }

    /**
     * @return Manager
     */
    public static function instance()
    {
        if (null == self::$singleton) {
            self::$singleton = new self();
        }

        return self::$singleton;
    }

    private function loadHandlers()
    {
        (($sPlugin = Phpfox_Plugin::get('job_queue_init')) ? eval($sPlugin) : false);

        if (!$this->handlerNames) {
            $this->handlerNames = ['empty'];
        }
    }

    /**
     * @param string $name
     * @param string $class
     *
     * @return Manager
     */
    public function addHandler($name, $class)
    {
        $this->handlerNames[$name] = $class;

        return $this;
    }

    private function getAvailableQueueHandlers()
    {
        $cache = Phpfox::getLib('cache');

        $sCacheId = $cache->set('pf_core_get_available_queuehandlers');

        $data = $cache->getLocalFirst($sCacheId);

        if (!$data) {
            foreach (Phpfox::getLib('database')
                         ->select('s.*')
                         ->from(':core_sqs_service', 's')
                         ->execute('getSlaveRows') as $row) {
                $data[$row['service_id']] = [
                    'service_class' => $row['service_class'],
                ];
            }

            $data['sqs'] = $data['amazon-sqs'];

            $cache->saveBoth($sCacheId, $data);
        }

        return $data;
    }

    private function getConfigFromEnv()
    {
        if ($this->envConfigs) {
            return $this->envConfigs;
        }

        $handlers =  Phpfox::getParam('core.message_queue_handling');
        $result = [];

        $availableSqsHandlers = $this->getAvailableQueueHandlers();
        foreach ($handlers as $driver=>$config) {
            if (!array_key_exists($driver, $availableSqsHandlers)) {
                continue;
            }

            $row = $availableSqsHandlers[$driver];
            $channel = isset($config['channel']) ? $config['channel'] : 'default';

            $result[$channel] = [
                'service_class' => $row['service_class'],
                'channel' => $channel,
                'config' => $config,
            ];
        }

        return $this->envConfigs = $result;
    }

    /**
     * @param $queue_name
     * @return string
     */
    private function getProviderByQueueNameFromEnv($queue_name)
    {
        $handler = Phpfox::getParam('core.message_queue_handling');
        if (!$handler) {
            return null;
        }

        if (!$this->envConfigs) {
            $this->getConfigFromEnv();
        }

        if (isset($this->envConfigs[$queue_name])) {
            return $this->envConfigs[$queue_name];
        }

        if (isset($this->envConfigs['default'])) {
            return $this->envConfigs['default'];
        }
    }

    /**
     * @param $queue_name
     * @return array|null
     */
    private function getProviderByQueueNameFromDatabase($queue_name)
    {
        $row = db()->select('d.*, s.service_class, s.edit_link')
            ->from(':core_sqs_queue', 'd')
            ->join(':core_sqs_service', 's', 's.service_id=d.service_id')
            ->where(['d.is_active' => 1, 'd.queue_name' => (string)$queue_name])
            ->execute('getSlaveRow');

        if ($row) {
            return [
                'service_id' => $row['service_id'],
                'service_class' => $row['service_class'],
                'config' => json_decode($row['config'], true),
            ];
        }
    }


    /**
     * @param string $queue_name
     *
     * @return JobRepositoryInterface || null
     */
    private function getProviderByQueueName($queue_name)
    {
        if (!$queue_name) {
            $queue_name = self::DEFAULT_QUEUE_NAME;
        }

        if (!empty($this->byQueueNames[$queue_name])) {
            return $this->byQueueNames[$queue_name];
        }

        $data = $this->getProviderByQueueNameFromEnv($queue_name);

        if (!$data) {
            $cache = Phpfox::getLib('cache');
            $sCacheId = $cache->set("pf_core_getproviderbyqueuename_" . $queue_name);
            if (($data = $cache->get($sCacheId)) === false) {
                $data = $this->getProviderByQueueNameFromDatabase($queue_name);
                $cache->saveBoth($sCacheId, $data);
                $cache->group('core_queue', $sCacheId);
            }
        }

        if (!$data) {
            $data = [
                'service_id' => 'database',
                'service_class' => JobRepositoryDatabase::class,
                'config' => [],
            ];
        }

        $service_class = $data ? $data['service_class'] : null;

        if ($service_class && class_exists($service_class)) {
            if (isset($data['service_id'])) {
                $this->serviceIdByQueueNames[$queue_name] = $data['service_id'];
            }
            return $this->byQueueNames[$queue_name] = new $service_class($data['config']);
        }

        return null;
    }

    /**
     * <example>
     * addJob('notify_liked', [user_id: 1, item_id: 4], 0, 600, 0)
     * </example>
     *
     * @param string $name
     * @param mixed $params
     * @param string $queue_name default  "default'
     * @param int $expire_time
     * @param int $waiting_time
     * @param int $priority
     *
     * @return mixed
     */
    public function addJob($name, $params, $queue_name = null, $expire_time = 0, $waiting_time = 0, $priority = 10)
    {
        $provider = $this->getProviderByQueueName($queue_name);

        if (!$provider) {
            return null;
        }

        if (empty($this->handlerNames)) {
            $this->loadHandlers();
        }

        if (empty($this->handlerNames[$name]) && (!defined('PHPFOX_INSTALLER') || !PHPFOX_INSTALLER)) {
            return 0;
        }

        if (null == $queue_name) {
            $queue_name = self::DEFAULT_QUEUE_NAME;
        }

        if (null == $expire_time) {
            $expire_time = self::DEFAULT_LIFETIME;
        }

        $data = json_encode([
            'job' => $name,
            'params' => $params,
        ]);

        return $provider->addJob($data, $queue_name, $expire_time, $waiting_time, $priority);
    }

    /**
     * @param string $queue_name
     *
     * @return JobInterface|null
     * @deprecated 4.8.0
     */
    public function getJob($queue_name = null)
    {
        if (null == $queue_name) {
            $queue_name = self::DEFAULT_QUEUE_NAME;
        }

        $provider = $this->getProviderByQueueName($queue_name);

        if (!$provider) {
            return null;
        }

        $item = $provider->getJob($queue_name);

        if ($item) {
            return $this->makeJob($queue_name, $item);
        }

        return null;
    }

    /**
     * @param string $queue_name
     * @param array $item
     *
     * @return JobInterface
     */
    private function makeJob($queue_name, $item)
    {
        $data = json_decode($item['data'], true);

        $params = $data['params'];

        if (!empty($this->handlerNames[$data['job']])) {
            $class = $this->handlerNames[$data['job']];

            if (class_exists($class)) {
                return new $class($queue_name, $item['reversation_id'], $item['job_id'], $data['job'], $params);
            }

        }

        Phpfox::getLog('cron.log')->debug('job not found', ['job' => $data['job']]);
        Phpfox::getLog('cron.log')->debug('handlers', $this->handlerNames);

        // fallback handlers
        $this->deleteJob($item['reversation_id'], $item['queue_name']);

        return null;
    }

    /**
     * @param null $queue_name
     * @param int $limit
     *
     * @return JobInterface[]
     */
    public function getJobs($queue_name = null, $limit = null)
    {
        if (!$queue_name) {
            $queue_name = self::DEFAULT_QUEUE_NAME;
        }
        $provider = $this->getProviderByQueueName($queue_name);

        if (!$provider) {
            return [];
        }

        if (empty($this->handlerNames)) {
            $this->loadHandlers();
        }

        if (null == $queue_name) {
            $queue_name = self::DEFAULT_QUEUE_NAME;
        }

        if (null == $limit) {
            $limit = self::DEFAULT_LIMIT;
        }

        $items = $provider->getJobs($queue_name, $limit);

        $jobs = [];

        foreach ($items as $item) {
            if (false != ($job = $this->makeJob($queue_name, $item))) {
                if ($this->_checkIfDeleted($queue_name, isset($item['job_id']) ? $item['job_id'] : null)) {
                    $provider->deleteJob($queue_name, $item['reversation_id']);
                    continue;
                }
                $jobs[] = $job;
            }
        }

        return $jobs;
    }

    private function _checkIfDeleted($queue_name, $jobId)
    {
        $canDeleted = false;

        if (isset($this->serviceIdByQueueNames[$queue_name]) && $this->serviceIdByQueueNames[$queue_name] != 'database' && isset($jobId)) {
            $cacheId = str_replace(['{service_id}', '{job_id}'], [$this->serviceIdByQueueNames[$queue_name], $jobId], self::DEFAULT_DELETED_JOB_CACHE_ID);
            $cacheObject = storage()->get($cacheId);
            if (is_object($cacheObject) && !empty($cacheObject->value)) {
                $canDeleted = true;
            } else {
                $exists = db()->select('cache_id')
                    ->from(':cache')
                    ->where([
                        'AND LOCATE(file_name, "' . $cacheId . '") > 0',
                        'cache_data' => '1',
                    ])->executeField(false);
                if ($exists) {
                    $canDeleted = true;
                }
            }

            if ($canDeleted) {
                storage()->del($cacheId);
            }
        }

        return $canDeleted;
    }

    /**
     * Delete job from queue
     *
     * @param string $queue_name
     * @param string $reversationId
     */
    public function deleteJob($reversationId, $queue_name = null, $useJobId = false)
    {
        if (!$queue_name) {
            $queue_name = self::DEFAULT_QUEUE_NAME;
        }

        $provider = $this->getProviderByQueueName($queue_name);
        if ($provider) {
            $isDatabase = $provider instanceof JobRepositoryDatabase;
            if ($useJobId && !$isDatabase) {
                if (!isset($this->serviceIdByQueueNames[$queue_name])) {
                    return false;
                }
                $cacheId = str_replace(['{service_id}', '{job_id}'], [$this->serviceIdByQueueNames[$queue_name], $reversationId], self::DEFAULT_DELETED_JOB_CACHE_ID);
                storage()->del($cacheId);
                storage()->set($cacheId, 1);
            } else {
                $provider->deleteJob($queue_name, $reversationId);
            }
        }
    }

    public function work()
    {
        $this->loadHandlers();

        $check_time = time();
        while ($check_time + 295 > time()) { // message scheduler run 4 minutes 55 seconds
            // do check connect again
            $db = \Phpfox::getLib('database');
            $db->reconnect();
            $jobs = $this->getJobs();
            if ($jobs) {
                foreach ($jobs as $job) {
                    $job->perform();
                }
                sleep(2);
            } else {
                sleep(3);
            }
        }
        return false;
    }
}