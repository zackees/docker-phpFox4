<?php

/**
 * Class Admincp_Component_Controller_Queue_Index
 * @since 4.8.0
 * @author phpfox
 */
class Admincp_Component_Controller_Setting_Queue_Manage extends Phpfox_Component
{
	public function process()
	{
		$aItems = Phpfox::getLib('job.admincp')
			->getAllQueues();

		$this->template()->clearBreadCrumb()
			->setTitle(_p('message_queue'))
			->setBreadCrumb(_p('message_queue'))
			->setActiveMenu('admincp.setting.queue')
			->assign([
				'useEnvFile'=> Phpfox::hasEnvParam('core.message_queue_handling'),
				'aItems' => $aItems,
			]);
	}
}