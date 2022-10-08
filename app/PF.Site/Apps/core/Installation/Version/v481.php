<?php
namespace Apps\PHPfox_Core\Installation\Version;

use Phpfox;

class v481
{
    public function process()
    {
        $this->_initAssetHandling();
    }

    private function _initAssetHandling()
    {
        $initAssetHandling = storage()->get('core_init_asset_handling');
        if ((empty($initAssetHandling) || empty($initAssetHandling->value)) && defined('PHPFOX_IS_UPGRADE')) {
            Phpfox::getCoreApp();
            if (Phpfox::isAppActive('PHPfox_CDN_Service')) {
                $settingTable = Phpfox::getT('setting');
                $moduleId = 'PHPfox_Core';
                $success = false;
                if (!setting('pf_assets_cdn_enable') && setting('pf_cdn_service_enabled')) {
                    $success = db()->update($settingTable, ['value_actual' => 1], ['var_name' => 'pf_assets_cdn_enable', 'module_id' => $moduleId]);
                }
                if (!setting('pf_assets_cdn_url') && setting('pf_cdn_service_url')) {
                    $success = db()->update($settingTable, ['value_actual' => setting('pf_cdn_service_url')], ['var_name' => 'pf_assets_cdn_url', 'module_id' => $moduleId]);
                }
                if ($success) {
                    $cacheObject = Phpfox::getLib('cache');
                    $cacheObject->remove('setting');
                    $cacheObject->removeGroup('settings');
                    $cacheObject->remove('app_settings');
                }
            }
            storage()->set('core_init_asset_handling', 1);
        }
    }
}