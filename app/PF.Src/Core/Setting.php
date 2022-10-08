<?php

namespace Core;

class Setting extends \Core\Model
{
    private static $_settings = null;

    public function __construct($settings = [])
    {
        if ($settings) {
            self::$_settings = $settings;
            return;
        }

        if (self::$_settings === null) {
            parent::__construct();

            self::$_settings = \Phpfox::getLib('cache')->getLocalFirst('app_settings');
            if (is_bool(self::$_settings)) {
                foreach ($this->_getAllAppSettings() as $settings) {
                    foreach (json_decode(json_encode($settings), true) as $key => $value) {
                        $thisValue = (isset($value['value']) ? $value['value'] : null);
                        $value = $this->db->select('*')->from(':setting')->where(['var_name' => $key])->get();
                        if (isset($value['value_actual'])) {
                            $thisValue = \Phpfox::getLib('setting')->getActualValue($value['type_id'], $value['value_actual']);
                        }
                        self::$_settings[$key] = $thisValue;
                    }
                }
            }
        }
    }

    public function get($key, $default = null, $acceptEnv = true)
    {
        if (strpos($key, '.') || ($acceptEnv && \Phpfox::hasEnvParam($key))) {
            return \Phpfox::getParam($key);
        }

        return (isset(self::$_settings[$key]) ? $this->_get(self::$_settings[$key]) : $default);
    }

    public function set($key, $value)
    {
        self::$_settings[$key] = $value;
    }

    private function _get($key)
    {
        $server_host = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
        $key = str_replace('{HTTP_HOST}', $server_host, $key);
        return $key;
    }

    private function _getAllAppSettings()
    {
        $appSettings = [];
        $allApps = \Phpfox_Module::instance()->getAllAppFromDatabase(true);
        $excludeApps = \Phpfox_Module::instance()->getExcludedModulesAppsByPackageId(PHPFOX_PACKAGE_ID);
        foreach ($allApps as $aApp) {
            $app = $aApp['apps_id'];
            if ((!defined('PHPFOX_TRIAL_MODE') || !PHPFOX_TRIAL_MODE) && in_array($app, $excludeApps)) {
                continue;
            }
            $appInfo = Lib::appInit($app);
            if (!$appInfo) {
                continue;
            }
            if (!$appInfo->isActive()) {
                continue;
            }
            if($appInfo->settings) {
                $appSettings[] = $appInfo->settings;
            }
        }
        return $appSettings;
    }
}