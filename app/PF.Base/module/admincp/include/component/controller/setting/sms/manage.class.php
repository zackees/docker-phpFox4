<?php

/**
 * Class Admincp_Component_Controller_Setting_Sms_Manage
 * @since 4.8.0
 * @author phpfox
 */
class Admincp_Component_Controller_Setting_Sms_Manage extends Phpfox_Component
{
	public function process()
	{
		$aItems = Phpfox::getLib('sms')
			->getAllStorage(false);

		$this->template()
			->clearBreadCrumb()
			->setBreadCrumb(_p('storage_system'), $this->url()->makeUrl('admincp.setting.storage.manage'))
			->setBreadCrumb(_p('manage'))
			->setActiveMenu('admincp.setting.storage')
			->setActionMenu([
				_p('add_storage') => [
					'icon' => 'ico ico-cloud',
					'url' => $this->url()->makeUrl('admincp.setting.storage.add')
				]
			])
			->assign([
				'useEnvFile'=> Phpfox::hasEnvParam('core.sms_handling'),
				'aItems' => $aItems,
			]);
	}
}