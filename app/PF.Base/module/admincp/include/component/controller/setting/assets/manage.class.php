<?php

/**
 * Class Admincp_Component_Controller_Setting_Assets_Manage
 * @since 4.8.0
 * @author phpfox
 */
class Admincp_Component_Controller_Setting_Assets_Manage extends Phpfox_Component
{
    public function process()
    {
        $keys = [
            'pf_assets_cdn_enable',
            'pf_assets_cdn_url',
            'pf_assets_storage_id',
            'pf_core_bundle_js_css',
        ];

        $storageItems = Phpfox::getLib('storage.admincp')->getAllStorage(true);

        if ($this->request()->method() === 'POST') {
            $aVals = $this->request()->get('val');
            $update = [];
            foreach ($keys as $key) {
                $update[$key] = $aVals[$key];
            }

            Phpfox::getService('admincp.setting.process')->update(['value' => $update]);
            Phpfox::addMessage(_p('Your changes have been saved!'));
            Phpfox::getLib('cache')->remove();

            if ($aVals['pf_core_bundle_js_css']) {
                $oAssets = Phpfox::getLib('assets');
                $oAssets->setAssetStorageId($aVals['pf_assets_storage_id']);
                $oAssets->setBaseCdnUrl($aVals['pf_assets_cdn_url']);
                $oAssets->setEnableCdn($aVals['pf_assets_cdn_enable']);

                $oAssets->bundleCssFile(PHPFOX_DIR_FILE . 'static' . PHPFOX_DS . 'autoload-' . Phpfox::getFullVersion() . '.css');
                $oAssets->bundleJsFile(PHPFOX_DIR_FILE . 'static' . PHPFOX_DS . 'autoload-' . Phpfox::getFullVersion() . '.js');
            }

        } else {
            $aVals = [];
            foreach ($keys as $key) {
                if ($key == 'pf_assets_storage_id') {
                    $aVals[$key] = Phpfox::getLib('assets')->getDefaultStorageId();
                } else {
                    $aVals[$key] = setting($key, setting($key, in_array($key, ['pf_assets_cdn_enable', 'pf_core_bundle_js_css']) ? 0 : ''), false);
                }
            }
        }

        $this->template()->setActionMenu([
            _p('transfer_files') => [
                'icon' => 'ico-upload-cloud',
                'url' => $this->url()->makeUrl('admincp.setting.assets.transfer')
            ]
        ]);

        $this->template()->clearBreadCrumb()
            ->setBreadCrumb(_p('assets_handling'), $this->url()->makeUrl('admincp.setting.assets.manage'))
            ->setBreadCrumb(_p('manage'))
            ->setActiveMenu('admincp.setting.assets')
            ->assign([
                'aItems' => $storageItems,
                'aForms' => $aVals,
            ]);
    }
}