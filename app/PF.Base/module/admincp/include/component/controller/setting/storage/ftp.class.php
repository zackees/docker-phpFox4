<?php

/**
 * Class Admincp_Component_Controller_Setting_Storage_Ftp
 * @since 4.8.0
 * @author phpfox
 */
class Admincp_Component_Controller_Setting_Storage_Ftp extends Phpfox_Component
{
	const SERVICE_ID = 'ftp';

	public function process()
	{
		$sError = null;
		$manager = Phpfox::getLib('storage.admincp');
		$storage_id = $this->request()->get('storage_id');
		$bIsEdit = !$storage_id;

        $aValidation = array(
            'storage_name' => array(
                'def' => 'string:required',
                'title' => _p('storage_name_is_required')
            ),
            'host' => array(
                'def' => 'string:required',
                'title' => _p('host_name_is_required')
            ),
            'port' => array(
                'def' => 'string:required',
                'title' => _p('port_is_required')
            ),
            'base_path' => array(
                'def' => 'string:required',
                'title' => _p('root_path_is_required')
            ),
            'base_url' => array(
                'def' => 'string:required',
                'title' => _p('base_url_is_required')
            ),

        );

        $oValid = Phpfox::getLib('validator')->set(array(
                'sFormName' => 'js_storage_ftp_form',
                'aParams' => $aValidation
            )
        );
        $aVals = $this->request()->get('val');
		if (!empty($aVals) && $oValid->isValid($aVals)) {
			$bIsActive = !empty($aVals['is_active']);
			$bIsDefault = !empty($aVals['is_default']);

			if ($bIsDefault) {
				$bIsActive = true;
			}

			$bIsValid = true;
			$config = [
			    'host' => $aVals['host'],
                'port' => $aVals['port'],
                'username' => isset($aVals['username']) ? $aVals['username'] : '',
                'password' =>isset($aVals['password']) ? $aVals['password'] : '',
                'base_path' => $aVals['base_path'],
                'base_url' => $aVals['base_url']
            ];

			if ($bIsActive) {
				try {
					$bIsValid = $manager->verifyStorageConfig(self::SERVICE_ID, $config);
					if (!$bIsValid) {
                        $sError = _p('invalid_configuration');
					}
				} catch (Exception $exception) {
					$bIsValid = false;
					$sError = $exception->getMessage();
				}
			}

			if ($bIsValid) {
                $storage_name = isset($aVals['storage_name']) ? $aVals['storage_name'] : '';
                if ($storage_id) {
                    $manager->updateStorageConfig($storage_id, self::SERVICE_ID, $storage_name, $bIsDefault, $bIsActive, $config);
                    Phpfox::addMessage(_p('Your changes have been saved!'));
                } else {
                    $manager->createStorage($storage_id, self::SERVICE_ID, $storage_name, $bIsDefault, $bIsActive, $config);
                    Phpfox::addMessage(_p('Your changes have been saved!'));
                    Phpfox::getLib('url')->send('admincp.setting.storage.manage');
                }
            }

		} else if ($storage_id) {
			$aVals = $manager->getStorageConfig($storage_id);
		} else {
			$aVals = [
			    'storage_name' => 'FTP'
            ];
		}

		$this->template()
			->clearBreadCrumb()
			->setBreadCrumb(_p('storage_system'), $this->url()->makeUrl('admincp.setting.storage.manage'));

		if ($bIsEdit) {
			$this->template()
				->setBreadCrumb(_p('add_storage'), $this->url()->makeUrl('admincp.setting.storage.add'));
		}

		$this->template()
			->setTitle(_p('ftp_storage'))
			->setBreadCrumb(_p('ftp_storage'))
			->setActiveMenu('admincp.setting.storage')
			->assign([
                'sCreateJs' => $oValid->createJS(),
                'sGetJsForm' => $oValid->getJsForm(),
				'aForms' => $aVals,
				'sError' => $sError
			]);
	}
}