<?php

/**
 * Class Admincp_Component_Controller_Setting_Queue_Beanstalk
 * @since 4.8.0
 * @author phpfox
 */
class Admincp_Component_Controller_Setting_Queue_Beanstalk extends Phpfox_Component
{
	const SERVICE_ID = 'beanstalk';
	const DEFAULT_PORT = 11300;

	public function process()
	{
		$sError = null;
		$manager = Phpfox::getLib('job.admincp');
		$queue_id = $this->request()->get('queue_id');

		if ($this->request()->method() === 'POST') {
			$aVals = $this->request()->get('val');
			$bIsActive = !!$aVals['is_active'];

			$bIsValid = true;
			$config = [
			    'host' => $aVals['host'],
                'port' => isset($aVals['port']) && $aVals['port'] >= 0 ? $aVals['port'] : self::DEFAULT_PORT,
            ];

			if ($bIsActive) {
				try {
					$bIsValid = $manager->verifyQueueConfig(self::SERVICE_ID, $config);
					if (!$bIsValid) {
						$sError = _p('invalid_configuration');
					}

				} catch (Exception $exception) {
					$bIsValid = false;
					$sError = $exception->getMessage();
				}
			}

			if ($bIsValid) {
				$manager->updateQueueConfig($queue_id, self::SERVICE_ID, $bIsActive, $config);
				Phpfox::addMessage(_p('Your changes have been saved!'));
			}
		} else {
			$aVals = $manager->getQueueConfig($queue_id, self::SERVICE_ID);
			if (!isset($aVals['port']) || $aVals['port'] == '') {
			    $aVals['port'] = self::DEFAULT_PORT;
            }
		}


		$this->template()->clearBreadCrumb()
			->setTitle(_p('message_queue'))
			->setBreadCrumb(_p('message_queue'))
			->setActiveMenu('admincp.setting.queue')
			->assign([
				'aForms' => $aVals,
				'sError' => $sError,
				'sSessionTable' => Phpfox::getT('cron_job'),
			]);
	}
}