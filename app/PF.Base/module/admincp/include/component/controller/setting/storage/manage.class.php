<?php

/**
 * Class Admincp_Component_Controller_Setting_Storage_Manage
 * @since 4.8.0
 * @author phpfox
 */
class Admincp_Component_Controller_Setting_Storage_Manage extends Phpfox_Component
{
	public function process()
	{
        Phpfox::isAdmin(true);

        $deleteId = $this->request()->get('delete_id');
        if (!empty($deleteId)) {
            if (Phpfox::getLib('storage.admincp')->deleteStorage($deleteId)) {
                $this->url()->send('admincp.setting.storage.manage', _p('storage_deleted_successfully'));
            }
        }

        $this->template()
            ->setActionMenu([
                _p('add_storage') => [
                    'icon' => 'ico ico-cloud',
                    'url' => $this->url()->makeUrl('admincp.setting.storage.add')
                ]
            ])
            ->clearBreadCrumb()
            ->setTitle(_p('storage_system'))
            ->setBreadCrumb(_p('storage_system'), $this->url()->makeUrl('admincp.setting.storage.manage'))
            ->setActiveMenu('admincp.setting.storage')
            ->assign([
                'aItems' => Phpfox::getLib('storage.admincp')->getAllStorage(false),
            ]);
	}
}