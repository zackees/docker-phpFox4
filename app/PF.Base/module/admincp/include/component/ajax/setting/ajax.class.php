<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 *
 *
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		phpFox LLC
 * @package  		Module_Admincp
 * @version 		$Id: ajax.class.php 225 2009-02-13 13:24:59Z phpFox LLC $
 */
class Admincp_Component_Ajax_Setting_Ajax extends Phpfox_Ajax
{
    public function deleteChannel()
    {
        Phpfox::getUserParam('admincp.has_admin_access', true);

        $service = $this->get('service');

        if (Phpfox::getLib('log.admincp')->deleteChannel($service, $this->get('channel'))) {
            Phpfox::addMessage(_p('channel_successfully_deleted'));
            $this->call('window.location.href = "' . Phpfox::getLib('url')->makeUrl('admincp.setting.logger.view', ['service' => $service]) . '";');
        } elseif (!Phpfox_Error::isPassed()) {
            $errorMessages = Phpfox_Error::get();
            $this->alert(array_shift($errorMessages));
        }
    }

    public function updateStorageActive()
    {
        Phpfox::getUserParam('admincp.has_admin_access', true);

        $manager = Phpfox::getLib('storage.admincp');
        $iStorageId = $this->get('storage_id');
        $iActive = $this->get('active');
        $manager->updateStorageActive($iStorageId, $iActive);
        if (!Phpfox_Error::isPassed()) {
            $this->call('setTimeout(function(){ window.location.reload(); }, 2000);');
            return false;
        }
        $this->call('window.location.reload();');
        return true;
    }

    public function updateStorageDefault()
    {
        Phpfox::getUserParam('admincp.has_admin_access', true);

        $manager = Phpfox::getLib('storage.admincp');
        $iStorageId = $this->get('storage_id');
        $iDefault = $this->get('active');
        $manager->updateStorageDefault($iStorageId, $iDefault);
        if (!Phpfox_Error::isPassed()) {
            $this->call('setTimeout(function(){ window.location.reload(); }, 2000);');
            return false;
        }
        $this->call('window.location.reload();');
        return true;
    }

    public function getLogChannels()
    {
        Phpfox::getUserParam('admincp.has_admin_access', true);

        $service = $this->get('service');
        $response = [];
        if (!empty($service)) {
            $channels = Phpfox::getLib('log.admincp')->getSupportedChannelsByService($service);
            if (!empty($channels)) {
                $parsedChannels = [];
                foreach ($channels as $channel) {
                    $parsedChannels[] = [
                        'text' => $channel,
                        'value' => $channel
                    ];
                }
                $response = array_merge($response, [
                    'channels' => $parsedChannels,
                    'default_channel' => $parsedChannels[0]['value'],
                ]);
            }
        }

        echo json_encode($response);
    }

    public function getTransferAssetFileProgress()
    {
        Phpfox::getUserParam('admincp.has_admin_access', true);

        $transferCache = storage()->get('core_transfer_asset_uniq');
        $response = [
            'status' => 'not_exist',
        ];

        if (!empty($transferCache->value) && !empty($transferProgress = (array)$transferCache->value) && !empty($transferProgress['total'])) {
            $totalTransfered = (int)($transferProgress['transfered'] + $transferProgress['failed']);
            $response = [
                'transfered' => $totalTransfered,
                'total' => (int)$transferProgress['total'],
                'percentage' => (int)(($totalTransfered / $transferProgress['total']) * 100),
                'status' => 'in_process',
            ];
        } elseif (!empty($totalTransfered = $this->get('total'))) {
            $response = [
                'status' => 'completed',
                'total' => $totalTransfered,
                'transfered' => $totalTransfered,
                'percentage' => 100,
            ];
        }
        echo json_encode($response);
        exit;
    }

    public function transferAssets()
    {
        Phpfox::getUserParam('admincp.has_admin_access', true);

        $files =  $this->get('files');
        $storage_id = $this->get('storage_id');
        $storage =  Phpfox::getLib('storage')->get($storage_id);
        $storage->setExtraConfig([
            'keep_files_in_server' => true,
        ]);
        $result =  [];

        foreach(explode(';', $files) as $file){
            $result[$file] =  $storage->putFile(PHPFOX_PARENT_DIR .  $file, $file);
        }
        exit(json_encode($result,JSON_PRETTY_PRINT | JSON_FORCE_OBJECT));
    }
}