<?php

namespace Core\Log;

use InvalidArgumentException;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Phpfox;

class Admincp
{
    /**
     * @return string[]
     */
    public function getSupportedServicesForDelete()
    {
        return array_keys($this->getSupportedServicesForView());
    }

    /**
     * @param $service
     * @param $channel
     * @return boolean
     */
    public function deleteChannel($service, $channel)
    {
        if (empty($service) || empty($channel) || !in_array($service, $this->getSupportedServicesForDelete())) {
            return false;
        }

        switch ($service) {
            case 'database':
                $success = db()->delete(':core_log_data', [
                    'channel' => $channel,
                ]);
                break;
            default:
                try {
                    $logDir = PHPFOX_DIR_FILE . 'log' . PHPFOX_DS;
                    if (is_dir($logDir) && file_exists($logDir . $channel)) {
                        $success = unlink($logDir . $channel);
                    } else {
                        $success = false;
                    }
                } catch (\Exception $exception) {
                    $success = \Phpfox_Error::set($exception->getMessage());
                }
                break;
        }

        return $success;
    }

	/**
	 * @param bool $activeOnly
	 * @return array
	 */
	public function getLogServices($activeOnly = false)
	{
		$services = Phpfox::getParam('core.log_handling');

		if (!empty($services)) {
			$items = [
				'local' => [
					'is_active' => 1,
					'is_share' => 0,
					'service_id' => 'local',
					'service_phrase_name' => 'local',
				]
			];
			foreach ($services as $driver => $service) {
				$items[$driver] = [
					'is_active' => 1,
					'is_share' => 1,
					'service_id' => $driver,
					'service_phrase_name' => $driver,
				];
			}

			return $items;
		}

		$cacheObject = Phpfox::getLib('cache');
		$cacheNameParams = ['core_logs'];
		if ($activeOnly) {
			$cacheNameParams[] = 'active';
		}
		$cacheId = $cacheObject->set(implode('_', $cacheNameParams));

		if (($items = $cacheObject->getLocalFirst($cacheId)) === false) {
			$query = Phpfox::getLib('database')
				->select('d.*')
				->from(':core_log_service', 'd');

			if ($activeOnly) {
				$query->where(['d.is_active' => 1]);
			}

			$items = $query->execute('getSlaveRows');

			$cacheObject->saveBoth($cacheId, $items);
			$cacheObject->group('core_log_group', $cacheId);
		}

		return $items;
	}

	/**
	 * @param string|int $level
	 * @return string
	 */
	public function getLevelName($level)
	{
		switch ($level) {
			case "100":
				return "DEBUG";
			case "200":
				return "INFO";
			case "250":
				return "NOTICE";
			case "300":
				return "WARNING";
			case "400":
				return "ERROR";
			case "500":
				return "CRITICAL";
			case "550":
				return "ALERT";
			case "600":
				return "EMERGENCY";
			default:
				return "DEBUG";
		}
	}

	/**
	 * @param string $service_id
	 * @return array|null
	 */
	private function getServiceById($service_id)
	{
		return Phpfox::getLib('database')
			->select('d.*')
			->from(':core_log_service', 'd')
			->where(['d.service_id' => (string)$service_id])
			->execute('getSlaveRow');
	}


	/**
	 * @param string $service_id
	 * @param array $config
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	public function verifyServiceConfig($service_id, $config)
	{

		$channel = 'verify.log';

		$row = $this->getServiceById($service_id);

		if (!$row || !$row ['service_class']) {
			throw new InvalidArgumentException("Could not found driver name {$service_id}");
		}

		$adapter_class = $row['service_class'];

		if (!class_exists($adapter_class)) {
			throw new InvalidArgumentException("Could not found {$adapter_class}");
		}

		/**
		 * @var HandlerInterface
		 */
		$handler = new $adapter_class($config);

		$logger = new Logger($channel, [$handler]);

		return $logger->emergency('Administrator verify log configuration');
	}

	/**
	 * @param string $service_id
	 * @param bool $active
	 * @param array $config
	 *
	 * @return bool
	 */
	public function updateServiceConfig($service_id, $active, $config)
	{
		$success = Phpfox::getLib('database')
			->update(':core_log_service', [
				'is_active' => $active ? 1 : 0,
				'level_name' => $this->getLevelName($config['level']),
				'config' => json_encode($config),
			], "service_id='{$service_id}'");

		if ($success) {
			Phpfox::getLib('cache')->removeGroup('core_log_group');
			$this->_clearGlobalCache();
		}

		return $success;
	}

	/**
	 * @param string $service_id
	 * @return array
	 * @throws InvalidArgumentException
	 */
	public function getServiceConfig($service_id)
	{
		$row = $this->getServiceById($service_id);

		if (!$row) {
			throw new InvalidArgumentException("Invalid log name '$service_id'");
		}

		$config = json_decode($row['config'], true);

		$config['is_active'] = !!$row['is_active'];
		$config['level_name'] = $row['level_name'];

		return $config;
	}

	/**
	 * Support local and database
	 * @return array
	 */
	public function getSupportedServicesForView()
	{
		return [
			'local' => [
				'title' => _p('local_filesystem'),
				'value' => 'local',
			],
			'database' => [
				'title' => _p('database'),
				'value' => 'database',
			],
		];
	}

	/**
	 * @param string $serviceId
	 * @return array
	 */
	public function getSupportedChannelsByService($serviceId = 'local')
	{
		switch ($serviceId) {
			case 'database':
				$where = [' AND (channel NOT REGEXP "(.php|.jpg|.png|.gif|.mp4)$") AND (channel NOT REGEXP "^installer_.*_phpfox.log$")'];
				$ignoredFiles = $this->getIgnoredLogViewerFiles(false);
				if (!empty($ignoredFiles)) {
					$where[] = ' AND channel NOT IN ("' . implode('","', $ignoredFiles) . '")';
				}
				$channels = Phpfox::getLib('database')->select('channel')
					->from(':core_log_data')
					->where($where)
					->group('channel')
					->order('field(channel, "main.log") DESC, channel ASC')
					->executeRows();
				$channels = array_column($channels, 'channel');
				break;
			default:
				$logDir = Phpfox::getParam('core.log_dir',PHPFOX_DIR_FILE . 'log');
				$ffs = scandir($logDir);
				$channels = [];
				$ignoredExtensions = ['php', 'jpg', 'png', 'gif', 'mp4'];

				foreach ($ffs as $ff) {
					if (!is_dir($logDir . PHPFOX_DS . $ff) && !in_array($ff, $this->getIgnoredLogViewerFiles()) && !preg_match('/^installer_(.*)_phpfox.log$/', $ff)) {
						$fileExt = pathinfo($logDir . PHPFOX_DS . $ff, PATHINFO_EXTENSION);
						if (in_array($fileExt, $ignoredExtensions)) {
							continue;
						}
						if ($ff == 'main.log') {
							$channels = array_merge(['main.log'], $channels);
						} else {
							$channels[] = $ff;
						}
					}
				}
				break;
		}

		return $channels;
	}

	/**
	 * @param $serviceId
	 * @param $channel
	 * @param int $page
	 * @param int $limit
	 * @return array|bool
	 */
	public function getLogs($serviceId, $channel, $page = 1, $limit = 10)
	{
		if (empty($serviceId) || empty($channel)) {
			return false;
		}

		$limitPage = 50;
		$limitCount = $limit * $limitPage;
		$getCountOnly = $page > $limitPage;
		$rows = [];

		switch ($serviceId) {
			case 'database':
				$databaseObject = Phpfox::getLib('database');
				if ($getCountOnly) {
					$count = $databaseObject->select('COUNT(id)')
						->from(':core_log_data')
						->where([
							'channel' => $channel,
						])
						->executeField();
				} else {
					$rows = $databaseObject->select('level_name AS level, message, datetime')
						->from(':core_log_data')
						->where([
							'channel' => $channel,
						])
						->order('id DESC')
						->limit($page, $limit)
						->forCount()
						->executeRows();
					$count = $databaseObject->forCount()->getCount();
				}
				break;
			default:
				$filePath = Phpfox::getParam('core.log_dir',PHPFOX_DIR_FILE . 'log') . PHPFOX_DS . $channel;
				$fileLines = file($filePath);
				$count = count($fileLines);
				if ($count > $limitCount) {
					$count = $limitCount;
				}

				if (!empty($count) && empty($getCountOnly)) {
					$rows = array_reverse(array_slice($fileLines, ($page * $limit) * (-1), $limit));
					if (!empty($rows)) {
						$parsedRows = [];
						foreach ($rows as $row) {
							$data = [
								'message' => $row,
							];

							if (preg_match('/^\[([\d\-\s\:]+)\]\s+([\w\.]+)\.([\w]+):\s+?(.*)(\[.*?\]|\{.*\})\s+\[\](.*)?$/', $row, $match)) {
								if (isset($match[4]) && ($realMessage = trim($match[4])) != '') {
									$data['message'] = $realMessage;
								}
								if (!empty($match[1])) {
									$data['datetime'] = $match[1];
								}
								if (!empty($match[3])) {
									$data['level'] = $match[3];
								}
							}
							$parsedRows[] = $data;
						}
						$rows = $parsedRows;
					}
				}

				break;
		}

		return [$count, $rows];
	}

	/**
	 * Get ignored files in folder log for viewing
	 * @param bool $local
	 * @return array
	 */
	private function getIgnoredLogViewerFiles($local = true)
	{
		$fileNames = [
			'.htaccess',
			'installation.log',
			'installation_message.log',
			'upgrade_app_state.log',
			'upgrade_app_ftp.log',
			'upgrade_app_options.log',
			'installer_modules.php',
		];
		if ($local) {
			$fileNames = array_merge(['.', '..'], $fileNames);
		}

		return $fileNames;
	}

    private function _clearGlobalCache()
    {
        $cacheObject = Phpfox::getLib('cache');
        $cacheObject->remove('pf_core_getloghandlerconfigfromdatabase');
    }
}