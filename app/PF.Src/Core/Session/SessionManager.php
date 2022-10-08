<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Session;

use Phpfox;


class SessionManager
{
	/**
	 * @var SaveHandlerInterface
	 */
	private $adapter;

	public function getDefaultSessionServiceFromEnv()
	{
		$config = Phpfox::getParam('core.session_handling');

		if (empty($config) || !$config['handler']) {
			return false;
		}

		if ($config['handler']) {
			$availableSaveHandlers = $this->getAvailableSessionSaveHandlers();
			if (array_key_exists($config['handler'], $availableSaveHandlers)) {
				return [
					'service_class' => $availableSaveHandlers[$config['handler']],
					'config' => $config,
				];
			}
		}
	}

	public function getDefaultSessionServiceFromDatabase()
	{
		$row = Phpfox::getLib('database')
			->select('d.*')
			->from(':core_session_service', 'd')
			->where('d.is_default=1')
			->execute('getSlaveRow');

		if ($row) {
			$service_class = $row['service_class'];
			if (class_exists($service_class)) {
				return [
					'service_class' => $service_class,
					'config' => json_decode($row['config'], true),
				];
			}
		}
	}

	public function registerSaveHandler()
	{
		if (defined('PHPFOX_INSTALLER')) {
			return;
		}

		$service_class = 'Core\Session\BuiltInAdapter';
		$params = [];

		$cache = Phpfox::getLib('cache');
		$sCacheId = $cache->set('pf_core_session_config');

		$data = $this->getDefaultSessionServiceFromEnv();
		if (!$data) {
            if (($data = $cache->getLocalFirst($sCacheId)) === false) {
                $data = $this->getDefaultSessionServiceFromDatabase();
                $cache->saveBoth($sCacheId, $data);
            }
		}

		if ($data) {
			$service_class = $data['service_class'];
			$params = $data['config'];
		}

		$this->adapter = $this->createSessionSaveHandler($service_class, $params);

		if ($this->adapter) {
			$this->adapter->registerSaveHandler();
		}
	}

	/**
	 * @param string $service_class
	 * @param array $params
	 *
	 * @return SaveHandlerInterface
	 */
	private function createSessionSaveHandler($service_class, $params)
	{
		return new $service_class($params);
	}

	private function getAvailableSessionSaveHandlers()
	{
		$cache = Phpfox::getLib('cache');

		$sCacheId = $cache->set('pf_core_getavailablesessionsavehandlers');

		$data = $cache->getLocalFirst($sCacheId);

		if (!$data) {
			$data = [
				'files' => 'Core\Session\BuiltInAdapter'
			];
			foreach (Phpfox::getLib('database')
						 ->select('s.*')
						 ->from(':core_session_service', 's')
						 ->execute('getSlaveRows') as $row) {
				$data[$row['service_id']] = $row['service_class'];
			}
		}
		return $data;
	}
}