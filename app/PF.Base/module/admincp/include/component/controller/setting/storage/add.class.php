<?php

/**
 * Class Admincp_Component_Controller_Setting_Storage_Add
 * @since 4.8.0
 * @author phpfox
 */
class Admincp_Component_Controller_Setting_Storage_Add extends Phpfox_Component
{

	public function process()
	{
		$manager = Phpfox::getLib('storage.admincp');

		$aItems = $manager
			->getStorageServices();

		$sServiceId = $aItems[0]['service_id'];

		if ($this->request()->method() == 'POST') {
			$sServiceId = $this->request()->get('service_id');
			$row = $manager->getStorageService($sServiceId);

			if ($row && $row['edit_link']) {
				$this->url()->send($row['edit_link']);
			}
		}

		$this->template()
			->clearBreadCrumb()
			->setBreadCrumb(_p('storage_system'), $this->url()->makeUrl('admincp.setting.storage.manage'))
			->setBreadCrumb(_p('add_storage'))
			->setTitle(_p('add_storage'))
			->setActiveMenu('admincp.setting.storage')
			->assign([
				'aItems' => $aItems,
				'sServiceId' => $sServiceId,
			]);
	}
}