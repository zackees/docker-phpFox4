<?php

/**
 * Class Admincp_Component_Controller_Setting_Storage_Local
 * @since 4.8.0
 * @author phpfox
 */
class Admincp_Component_Controller_Setting_Storage_Local extends Phpfox_Component
{
    const SERVICE_ID = 'local';

    public function process()
    {
        $sError = null;
        $manager = Phpfox::getLib('storage.admincp');
        $storage_id = $this->request()->get('storage_id');

        $aVals = $this->request()->get('val');
        if (!empty($aVals)) {
            $bIsDefault = !!$aVals['is_default'];
            $bIsActive = $bIsDefault || !!$aVals['is_active'];
            $sStorageName = isset($aVals['storage_name']) ? $aVals['storage_name'] : '';

            $manager->updateStorageConfig($storage_id, self::SERVICE_ID, $sStorageName, $bIsDefault, $bIsActive, []);
            Phpfox::addMessage(_p('Your changes have been saved!'));
        } else {
            $aVals = $manager->getStorageConfig($storage_id);
        }

        $this->template()->clearBreadCrumb()
            ->setBreadCrumb(_p('storage_system'), $this->url()->makeUrl('admincp.setting.storage.manage'))
            ->setTitle(_p('local_filesystem_storage'))
            ->setBreadCrumb(_p('local_filesystem_storage'))
            ->setActiveMenu('admincp.setting.storage')
            ->assign([
                'aForms' => $aVals,
                'sError' => $sError,
                'base_path' => PHPFOX_DIR_FILE,
                'base_url' => Phpfox::getParam('core.path_actual'),
            ]);
    }
}