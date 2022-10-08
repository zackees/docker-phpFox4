<?php

/**
 * Class Admincp_Component_Controller_Setting_Assets_Transfer
 * @since 4.8.0
 * @author phpfox
 */
class Admincp_Component_Controller_Setting_Assets_Transfer extends Phpfox_Component
{
    CONST STORAGE_KEY = 'core_transfer_asset_uniq';

    public function process()
    {
        $oAssets = Phpfox::getLib('assets');
        $storageItems = Phpfox::getLib('storage.admincp')
            ->getAllStorage(true);

        $cache = storage()->get(self::STORAGE_KEY);
        $transferProgress = null;

        if ($cache) {
            $bIsTransferred = true;
            $transferProgress = (array)$cache->value;
        }

        unset($storageItems['0']);

        $aAssetFiles = array_map(function ($str) {
            return str_replace(PHPFOX_PARENT_DIR, '', $str);
        }, $oAssets->getSiteAssetFiles());


        $sTransferStorageId = $this->request()->get('transfer_storage_id', !empty($transferProgress['storage_id']) ? $transferProgress['storage_id'] : null);
        $defaultStorageId = count($storageItems) > 0 ? array_values($storageItems)[0]['storage_id'] : null;

        if (!$sTransferStorageId && isset($defaultStorageId)) {
            $sTransferStorageId = $defaultStorageId;
        }

        if($this->request()->get('transfer_direct_done')){
            $this->url()->send($this->url()->makeUrl('admincp.setting.assets.transfer'),null,_p('transfer_successfully'));
        }

        if ($this->request()->method() === 'POST') {
            $bIsStop = $this->request()->get('stop');
            $bIsTransferred = $this->request()->get('transfer');
            $bIsTransferredDirectly = $this->request()->get('transfer_files_directly');


            if ($bIsStop) {
                if ($cache) {
                    storage()->delById($cache->id);
                }
                $oAssets->deleteTransferFileData();
                $bIsTransferred = false;
                $sTransferStorageId = $defaultStorageId;
            } else if ($bIsTransferredDirectly) {
                $sTransferStorageId = $this->request()->get('transfer_storage_id');
                $aChunkFiles = array_map(function ($files) use ($sTransferStorageId) {
                    return [
                        'files' => $files,
                        'storage_id' => $sTransferStorageId];
                }, array_chunk($aAssetFiles, 5));
                $this->template()
                    ->assign([
                        'totalTransferedFile'=> 0,
                        'bIsTransferredDirectly'=> true,
                        'aTransferFileData' => json_encode($aChunkFiles),
                    ]);

            } else if ($bIsTransferred) {
                $sTransferStorageId = $this->request()->get('transfer_storage_id');
                $uniqid = uniqid();
                $iRemainFile = count($aAssetFiles);
                $transferProgress = [
                    'uniqid' => $uniqid,
                    'total' => $iRemainFile,
                    'transfered' => 0,
                    'failed' => 0,
                    'storage_id' => $sTransferStorageId,
                ];

                if ($cache) {
                    storage()->updateById($cache->id, $transferProgress);
                } else {
                    storage()->set(self::STORAGE_KEY, $transferProgress);
                }

                $aChunkFiles = array_chunk($aAssetFiles, 25);
                foreach ($aChunkFiles as $files) {
                    $iRemainFile -= count($files);
                    Phpfox::getLib('job.manager')->addJob('core_transfer_asset_files', [
                        'uniqid' => $uniqid,
                        'remain_file' => $iRemainFile,
                        'storage_id' => $sTransferStorageId,
                        'files' => $files,
                    ]);
                }
            }
        }

        if ($bIsTransferred && !empty($transferProgress['total']) && $transferProgress['total'] > 0) {
            $totalTransferedFile = $transferProgress['transfered'] + $transferProgress['failed'];
            $transferProgress['percentage'] = (int)(($totalTransferedFile / $transferProgress['total']) * 100);
            $this->template()->assign([
                'transferedProgress' => $transferProgress,
                'totalTransferedFile' => $totalTransferedFile,
            ]);
        }

        $this->template()->clearBreadCrumb()
            ->setBreadCrumb(_p('assets_handling'), $this->url()->makeUrl('admincp.setting.assets.manage'))
            ->setBreadCrumb(_p('transfer_asset_files'))
            ->setActiveMenu('admincp.setting.assets')
            ->setHeader('cache', [
                'asset.js' => 'module_admincp',
            ])
            ->assign([
                'aItems' => $storageItems,
                'aAssetFiles' => $aAssetFiles,
                'iTotalFile' => count($aAssetFiles),
                'sTransferStorageId' => $sTransferStorageId,
                'bIsTransferred' => $bIsTransferred,
                'messageQueueLink' => '<a href="' . $this->url()->makeUrl('admincp.setting.queue.manage') . '" target="_blank">' . _p('message_queue') . '</a>',
                'cronJobLink' => '<a href="' . $this->url()->makeUrl('admincp.app.settings', ['id' => 'PHPfox_Core', 'group' => 'cron_job']) . '" target="_blank">' . _p('Cron Job') . '</a>',
            ]);
    }
}