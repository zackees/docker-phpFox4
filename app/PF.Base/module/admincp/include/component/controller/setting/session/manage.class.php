<?php

/**
 * Class Admincp_Component_Controller_Setting_Session_Manage
 * @since 4.8.0
 * @author phpfox
 */
class Admincp_Component_Controller_Setting_Session_Manage extends Phpfox_Component
{
	public function process()
	{
		$aItems = Phpfox::getLib('session.admincp')
			->getSessionServices();

		$this->template()->clearBreadCrumb()
			->setTitle(_p('session_handling'))
			->setBreadCrumb(_p('session_handling'))
			->setActiveMenu('admincp.setting.session')
			->assign([
				'useEnvFile'=> Phpfox::hasEnvParam('core.session_handling'),
				'aItems' => $aItems,
			]);
	}
}