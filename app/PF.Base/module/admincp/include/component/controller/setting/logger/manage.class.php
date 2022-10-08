<?php

/**
 * Class Admincp_Component_Controller_Setting_Logger_Manage
 * @since 4.8.0
 * @author phpfox
 */
class Admincp_Component_Controller_Setting_Logger_Manage extends Phpfox_Component
{
    public function process()
    {
        $aItems  = Phpfox::getLib('log.admincp')->getLogServices();

		$this->template()->clearBreadCrumb()
			->setTitle(_p('log_handling'))
			->setBreadCrumb(_p('log_handling'))
			->setActiveMenu('admincp.setting.logger')
			->assign([
				'useEnvFile'=> Phpfox::hasEnvParam('core.log_handling'),
				'aItems' => $aItems,
                'supportedViewer' => array_keys(Phpfox::getLib('log.admincp')->getSupportedServicesForView())
			]);
	}
}