<?php

/**
 * Class Admincp_Component_Controller_Setting_Queue_Transfer
 * @since 4.8.0
 * @author phpfox
 */
class Admincp_Component_Controller_Setting_Queue_Transfer extends Phpfox_Component
{
	public function process()
	{
		$manager =  Phpfox::getLib('job.admincp');
		$aItems = $manager
			->getQueueServices();

		$sServiceId = $aItems[0]['service_id'];


		if ($this->request()->method() === 'POST') {
			$sServiceId =  $this->request()->get('service_id');
			$sQueueId = $this->request()->get('queue_id');

			$aService =  $manager->getQueueService($sServiceId);

			$this->url()->send($aService['edit_link'],['queue_id'=>$sQueueId]);
		}

		$this->template()->clearBreadCrumb()
			->setTitle(_p('message_queue'))
			->setBreadCrumb(_p('message_queue'))
			->setActiveMenu('admincp.setting.queue')
			->assign([
				'aItems' => $aItems,
				'sServiceId' => $sServiceId,
			]);
	}
}