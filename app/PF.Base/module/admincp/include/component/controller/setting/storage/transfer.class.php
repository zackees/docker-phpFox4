<?php

/**
 * Class Admincp_Component_Controller_Setting_Storage_Transfer
 * @since 4.8.0
 * @author phpfox
 */
class Admincp_Component_Controller_Setting_Storage_Transfer extends Phpfox_Component
{
    CONST STORAGE_TRANSFER_PARAMS = 'core_storage_transfer_files_params';

	public function process()
	{
        $storage = Phpfox::getLib('storage.admincp');
        $aItems = $storage->getAllStorage(true, false);

		$sStorageId = $this->request()->get('storage_id');

		$aItems = array_filter($aItems, function ($row) use ($sStorageId) {
			return $row['storage_id'] != $sStorageId;
		});

		$sTransferStorageId = null;


        $aFiles = $storage->getSiteFiles();

		if (count($aItems)) {
			$sTransferStorageId = $aItems[0]['storage_id'];
		}
        $cacheParam = storage()->get(self::STORAGE_TRANSFER_PARAMS);
        $aForms = [];
        $sStatus = '';
        if ($cacheParam) {
            $aForms = (array)$cacheParam->value;
            $sStatus = $cacheParam->value->status;
        }

		$aVals = $this->request()->getArray('val');
		if ($this->request()->method() === 'POST' && !empty($aVals)) {
			$sTransferStorageId = isset($aVals['transfer_storage_id']) ? $aVals['transfer_storage_id'] : 0;
            $bIsStop = $this->request()->get('stop');
            if ($bIsStop) {
                if ($cacheParam) {
                    storage()->update(self::STORAGE_TRANSFER_PARAMS, [
                        'status' => 'stopped'
                    ]);
                }
                $this->url()->send('admincp.setting.storage.transfer', _p('files_transfer_stopped'));
            } else {
                if (!$sTransferStorageId) {
                    Phpfox_Error::set(_p('please_select_storage_to_transfer_files'));
                } else {
                    $iRemainFile = count($aFiles);
                    $uniqId = uniqid();
                    $updateDb = !empty($aVals['remove_file']) || !empty($aVals['update_database']);
                    $params = [
                        'remove_file' => isset($aVals['remove_file']) ? !!$aVals['remove_file'] : false,
                        'update_database' => $updateDb,
                        'uniq_id' => $uniqId,
                        'total_file' => $iRemainFile,
                        'transfer_storage_id' => $sTransferStorageId,
                        'success_file' => 0,
                        'update_time' => PHPFOX_TIME,
                        'fail_file' => 0,
                        'status' => 'in_process'
                    ];
                    $sStatus = 'in_process';
                    if ($cacheParam) {
                        storage()->update(self::STORAGE_TRANSFER_PARAMS, $params);
                    } else {
                        storage()->set(self::STORAGE_TRANSFER_PARAMS, $params);
                    }
                    $aForms = $params;
                    $aChunkFiles = array_chunk($aFiles, 30);
                    foreach ($aChunkFiles as $files) {
                        $iRemainFile -= count($files);
                        Phpfox::getLib('job.manager')->addJob('core_storage_transfer_files', [
                            'uniqId' => $uniqId,
                            'remain_file' => $iRemainFile,
                            'storage_id' => $sTransferStorageId,
                            'files' => $files,
                        ]);
                    }
                    if ($updateDb) {
                        Phpfox::getLib('job.manager')->addJob('core_storage_transfer_files_update_db', [
                            'uniqId' => $uniqId,
                            'storage_id' => $sTransferStorageId,
                            'total_file' => count($aFiles)
                        ]);
                    }
                    $this->url()->send('admincp.setting.storage.transfer', _p('files_transfer_in_process'));
                }
            }
		}

		if (!empty($aForms['transfer_storage_id'])) {
		    $aForms['storage'] = Phpfox::getLib('storage.admincp')->getStorageById($aForms['transfer_storage_id']);
        }

		$this->template()
			->clearBreadCrumb()
			->setBreadCrumb(_p('storage_system'), $this->url()->makeUrl('admincp.setting.storage.manage'))
			->setBreadCrumb(_p('transfer_files'))
			->setTitle(_p('transfer_files'))
			->setActiveMenu('admincp.setting.storage')
            ->setHeader('cache', [
                'storage.js' => 'module_admincp'
            ])
			->assign([
				'aItems' => $aItems,
				'aForms' => $aForms,
				'sTransferStorageId' => $sTransferStorageId,
                'aFiles' => $aFiles,
                'iTotalFile' => count($aFiles),
                'sStatus' => $sStatus,
                'messageQueueLink' => '<a href="' . $this->url()->makeUrl('admincp.setting.queue.manage') . '" target="_blank">' . _p('message_queue') . '</a>',
                'cronJobLink' => '<a href="' . $this->url()->makeUrl('admincp.app.settings', ['id' => 'PHPfox_Core', 'group' => 'cron_job']) . '" target="_blank">' . _p('Cron Job') . '</a>',
			]);
		return 'controller';
	}
}