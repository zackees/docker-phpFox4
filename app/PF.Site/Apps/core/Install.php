<?php

namespace Apps\PHPfox_Core;

use Core\App;
use Core\App\Install\Setting;
use Phpfox;

/**
 * Class Install
 * @author  phpFox LLC
 * @version 4.5.0
 * @package Apps\PHPfox_Core
 */
class Install extends App\App
{

    /**
     * @var array
     */
    private $_app_phrases = [];

    /**
     *
     */
    protected function setId()
    {
        $this->id = 'PHPfox_Core';
    }

    /**
     * Set start and end support version of your App.
     * @example   $this->start_support_version = 4.2.0
     * @example   $this->end_support_version = 4.5.0
     * @see       list of our verson at PF.Base/install/include/installer.class.php ($_aVersions)
     * @important You DO NOT ALLOW to set current version of phpFox for start_support_version and end_support_version. We will reject of app if you use current version of phpFox for these variable. These variables help clients know their sites is work with your app or not.
     */
    protected function setSupportVersion()
    {
        $this->start_support_version = Phpfox::getVersion();
        $this->end_support_version = Phpfox::getVersion();
    }


    /**
     *
     */
    protected function setAlias()
    {
    }

    /**
     *
     */
    protected function setName()
    {
        $this->name = 'phpFox Core';
    }

    /**
     *
     */
    protected function setVersion()
    {
        $this->version = Phpfox::getVersion();
    }

    /**
     *
     */
    protected function setSettings()
    {
    	$cronTaskToken = uniqid();
    	if(getenv('PFE_CRON_TASK_TOKEN')){
			$cronTaskToken = getenv('PFE_CRON_TASK_TOKEN');
		}

        $this->settings = [
            "phpfox_version"               => [
                "group_id"    => "general",
                "var_name"    => "phpfox_version",
                "info"        => "phpfox_version",
                "description" => "phpfox_version",
                "is_hidden"   => 1,
                "type"        => "string",
                "value"       => Phpfox::getCurrentVersion(),
            ],
            "pf_core_cache_driver"         => [
                "var_name"    => "pf_core_cache_driver",
                "info"        => "Cache Driver",
                "description" => "pf_core_cache_driver_description",
                "type"        => "select",
                "value"       => "file",
                "options"     => [
                    "file"      => "File System",
                    "redis"     => "Redis",
                    "memcached" => "Memcache"
                ],
                "group_class" => "core_cache_driver"
            ],
            "pf_core_cache_redis_host"     => [
                "var_name"     => "pf_core_cache_redis_host",
                "info"         => "Redis Host",
                "group_class"  => "core_cache_driver",
                "option_class" => "pf_core_cache_driver=redis"
            ],
            "pf_core_cache_redis_port"     => [
                "var_name"     => "pf_core_cache_redis_port",
                "info"         => "Redis Port",
                "group_class"  => "core_cache_driver",
                "option_class" => "pf_core_cache_driver=redis"
            ],
            "pf_core_cache_redis_password" => [
                "var_name"     => "pf_core_cache_redis_password",
                "info"         => "Redis Password",
                "group_class"  => "core_cache_driver",
                "option_class" => "pf_core_cache_driver=redis"
            ],
            "pf_core_cache_redis_database" => [
                "var_name"     => "pf_core_cache_redis_database",
                "info"         => "Redis Database",
                "group_class"  => "core_cache_driver",
                "option_class" => "pf_core_cache_driver=redis"
            ],
            "pf_core_cache_memcached_host" => [
                "var_name"     => "pf_core_cache_memcached_host",
                "info"         => "Memcache Host",
                "group_class"  => "core_cache_driver",
                "option_class" => "pf_core_cache_driver=memcached"
            ],
            "pf_core_cache_memcached_port" => [
                "var_name"     => "pf_core_cache_memcached_port",
                "info"         => "Memcache Port",
                "group_class"  => "core_cache_driver",
                "option_class" => "pf_core_cache_driver=memcached"
            ],
            "pf_core_bundle_js_css"        => [
                "var_name"    => "pf_core_bundle_js_css",
                "info"        => "Bundle JavaScript & CSS",
                "type"        => Setting\Site::TYPE_RADIO,
                "value"       => 0,
                "group_class" => "core_bundle_js_css"
            ],
            "pf_cron_task_token"           => [
                "var_name"    => "pf_cron_task_token",
                "info"        => "Cron Job Token",
                "type"        => 'string',
                "value"       => $cronTaskToken,
                "group_class" => 'cron_job'
            ],
            "pf_cron_task_url"             => [
                "var_name"    => "pf_cron_task_url",
                "info"        => "Cron Job URL",
                "description" => "Copy the URL then follow the instruction at <a target=\"_blank\" href=\"https://docs.phpfox.com/display/FOX4MAN/Setup+Cron\">here</a> to set up cron jobs for your phpFox site.",
                "type"        => 'readonly',
                "value"       => '',
                "group_class" => 'cron_job'
            ],
            "pf_assets_cdn_enable"         => [
                "group_id"    => 'assets',
                "var_name"    => "pf_assets_cdn_enable",
                "info"        => "enable_cdn",
                "type"        => 'integer',
                "is_hidden"   => 1,
                "value"       => '0',
                "group_class" => 'assets'
            ],
            "pf_assets_cdn_url"            => [
                "group_id"    => 'assets',
                "var_name"    => "pf_assets_cdn_url",
                "info"        => "cdn_url",
                "type"        => 'string',
                "is_hidden"   => 1,
                "value"       => '',
                "group_class" => 'assets'
            ],
            "pf_assets_storage_id"         => [
                "group_id"    => 'assets',
                "var_name"    => "pf_assets_storage_id",
                "info"        => "storage",
                "type"        => 'string',
                "is_hidden"   => 1,
                "value"       => '0',
                "group_class" => 'assets'
            ],
        ];
    }

    /**
     *
     */
    protected function setUserGroupSettings()
    {
    }

    /**
     *
     */
    protected function setComponent()
    {
    }

    /**
     *
     */
    protected function setComponentBlock()
    {
    }

    /**
     *
     */
    protected function setPhrase()
    {
        $this->phrase = $this->_app_phrases;
    }

    /**
     *
     */
    protected function setOthers()
    {
        $this->_publisher = 'phpFox';
        $this->_publisher_url = 'https://store.phpfox.com/';
        $this->_apps_dir = 'core';
        $this->database = [
            'Currency',
            'Feed_Hide',
            'Feed_Tag_Data',
            'Feed_Tag_Remove',
            'Tag',
            'Temp_File',
            'Timezone_Setting',
            'LogService',
            'LogData',
            'SqsService',
            'SqsQueue',
            'SessionService',
            'SessionData',
            'StorageService',
            'Storage',
            'SearchWordLog',
            'Schedule'
        ];
    }
}