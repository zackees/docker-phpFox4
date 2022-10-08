<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Core_Service_Api
 */
class Core_Service_Api extends \Core\Api\ApiServiceBase
{
    /**
     * @description: Upload temp file
     *
     * @return array|bool
     */
    public function postTempFile()
    {
        $sType = $this->request()->get('type', '');

        if (empty($sType)) {
            return $this->error(_p('missing_param_name', ['name' => 'type']));
        }

        if (!Phpfox::hasCallback($sType, 'getUploadParams')) {
            return $this->error(_p('missing_callback_name_for_type_name', ['name' => 'getUploadParams']));
        }

        $aParams = [
            'list_type' => [],
            'max_size' => null,
            'upload_dir' => Phpfox::getParam('core.dir_pic'),
            'thumbnail_sizes' => [],
            'user_id' => Phpfox::getUserId(),
            'type' => $sType,
            'param_name' => 'file',
            'field_name' => 'temp_file'

        ];

        $aParams = array_merge($aParams, Phpfox::callback($sType . '.getUploadParams', ['id' => $this->request()->get('id')]));

        $aParams['update_space'] = false;

        if (isset($aParams['type'])) {
            $sType = $aParams['type'];
        }
        
        $aFile = Phpfox::getService('user.file')->upload($aParams['param_name'], $aParams);
        $sErrorMessage = null;
        $aResponse = [];
        
        if (!$aFile) {
            $sErrorMessage = _p('upload_fail_please_try_again_later');
        } elseif (!empty($aFile['error'])) {
            $sErrorMessage = $aFile['error'];
        } else {
            $iId = phpFox::getService('core.temp-file')->add([
                'type' => $sType,
                'size' => $aFile['size'],
                'path' => $aFile['name'],
                'server_id' => Phpfox_Request::instance()->getServer('PHPFOX_SERVER_ID')
            ]);

            $aResponse = [
                'file' => $iId,
                'type' => $sType,
                'field_name' => $aParams['field_name']
            ];
        }

        if ($sErrorMessage) {
            return $this->error($sErrorMessage);
        } else {
            return $this->success($aResponse);
        }
    }
}