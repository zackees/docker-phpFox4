<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Session;

use InvalidArgumentException;
use Phpfox;

class Admincp
{
    public function getSessionServices()
    {
    	$config = Phpfox::getParam('core.session_handling');
    	if(!empty($config)){
			return [
				[
					'service_id'=> $config['handler'],
					'service_phrase_name'=> $config['handler'].'_session_storage',
					'is_default'=>1,
				]
			];
		}

        $cacheObject = Phpfox::getLib('cache');
        $cacheId = $cacheObject->set('core_sessions');

        if (($sessions = $cacheObject->getLocalFirst($cacheId)) === false) {
            $sessions = Phpfox::getLib('database')
                ->select('d.*')
                ->from(':core_session_service', 'd')
                ->execute('getSlaveRows');
            $cacheObject->saveBoth($cacheId, $sessions);
        }

        return $sessions;
    }

    /**
     * @param $service_id
     * @return array|null
     */
    private function getServiceById($service_id)
    {
        return Phpfox::getLib('database')
            ->select('d.*')
            ->from(':core_session_service', 'd')
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
        $row = $this->getServiceById($service_id);

        if (!$row) {
            throw new InvalidArgumentException("Could not found session ${service_id}");
        }

        $service_class = $row['service_class'];
        if (!class_exists($service_class)) {
            throw new InvalidArgumentException("Could not found class {$service_class}");
        }

        $handler = new $service_class($config);

        return $handler->isValid();
    }

    /**
     * @param string $service_id
     * @param bool $default
     * @param array $config
     */
    public function updateServiceConfig($service_id, $default, $config)
    {
        $row = $this->getServiceById($service_id);

        if (!$row) {
            throw new InvalidArgumentException("Could not found session ${service_id}");
        }

        $update = [
            'is_default' => $default ? 1 : 0,
            'config' => json_encode($config, JSON_FORCE_OBJECT)
        ];

        if (!$row['is_default'] && $default) {
            //Toggle all services to be not default
            Phpfox::getLib('database')->update(':core_session_service', [
                'is_default' => 0
            ]);
        }

        if (Phpfox::getLib('database')
            ->update(':core_session_service', $update, "service_id='{$service_id}'")) {
            Phpfox::getLib('cache')->remove('core_sessions');
            if ($default) {
                Phpfox::getLib('cache')->remove('pf_core_session_config');
            }
        }
    }

    /**
     * @param $service_name
     * @return array
     * @throws InvalidArgumentException
     */
    public function getAdapterConfig($service_name)
    {
        $row = $this->getServiceById($service_name);

        if (!$row) {
            throw new InvalidArgumentException("Could not found session ${$service_name}");
        }

        $config = json_decode($row['config'], true);
        $config['is_default'] = !!$row['is_default'];

        return $config;
    }
}