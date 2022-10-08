<?php

/**
 * Class Admincp_Component_Controller_Setting_Session_Local
 * @since 4.8.0
 * @author phpfox
 */
class Admincp_Component_Controller_Setting_Session_Local extends Phpfox_Component
{
	const SERVICE_ID = 'local';

	public function process()
	{
		$sError = null;
		$manager = Phpfox::getLib('session.admincp');

		if ($this->request()->method() === 'POST') {
			$aVals = $this->request()->get('val');
			$bIsDefault = !!$aVals['is_default'];

			$bIsValid = true;
			$config = [];

			if ($bIsDefault) {
				try {
					$bIsValid = $manager->verifyServiceConfig(self::SERVICE_ID, $config);
					if (!$bIsValid) {
						$sError = _p('invalid_configuration');
					}

				} catch (Exception $exception) {
					$bIsValid = false;
					$sError = $exception->getMessage();
				}
			}

			if ($bIsValid) {
				$manager->updateServiceConfig(self::SERVICE_ID, $bIsDefault, $config);
				Phpfox::addMessage(_p('Your changes have been saved!'));
			}
		} else {
			$aVals = $manager->getAdapterConfig(self::SERVICE_ID);
		}

		$sSavePath = session_save_path();
		if (!$sSavePath) {
			$sSavePath = 'handle by php.ini';
		}

		$this->template()->clearBreadCrumb()
			->setTitle(_p('session_handling'))
			->setBreadCrumb(_p('session_handling'))
			->setActiveMenu('admincp.setting.session')
			->assign([
				'aForms' => $aVals,
				'sError' => $sError,
				'sSavePath' => $sSavePath,
			]);
	}
}