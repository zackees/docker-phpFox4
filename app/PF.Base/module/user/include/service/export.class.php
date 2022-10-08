<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * @copyright [PHPFOX_COPYRIGHT]
 * @author phpFox LLC
 * Class User_Service_Export
 */
class User_Service_Export extends Phpfox_Service
{
    public function getUserInfoStatus()
    {
        $aCopyUserInfoStatus = Phpfox::massCallback('getCopyUserInfoStatus');
        $aReturn = [];
        foreach ($aCopyUserInfoStatus as $moduleId => $aInfoData) {
            foreach ($aInfoData as $infoKey => $aInfo) {
                if (Phpfox::hasCallback($moduleId, 'processCopyUserInfo_' . $infoKey)) {
                    $aReturn[$moduleId . '.' . $infoKey] = $aInfo;
                }
            }
        }
        return $aReturn;
    }

    public function getUserDataStatus()
    {
        $aCopyUserDataStatus = Phpfox::massCallback('getCopyUserDataStatus');
        $aReturn = [];
        foreach ($aCopyUserDataStatus as $moduleId => $aInfoData) {
            foreach ($aInfoData as $infoKey => $aInfo) {
                if (Phpfox::hasCallback($moduleId, 'processCopyUserData_' . $infoKey)) {
                    $aReturn[$moduleId . '.' . $infoKey] = $aInfo;
                }
            }
        }
        return $aReturn;
    }

    public function doExport($aVals)
    {
        $hash = md5(Phpfox::getUserId() . time());
        $sExportDir = PHPFOX_DIR_CACHE . $hash;
        @mkdir($sExportDir, 0777);
        if (isset($aVals['information_about_you']) && is_array($aVals['information_about_you'])) {
            foreach ($aVals['information_about_you'] as $value) {
                list($sModuleId, $actionName) = explode('.', $value);
                $data = Phpfox::callback($sModuleId . '.processCopyUserInfo_' . $actionName);
                $sessionDir = $sExportDir . PHPFOX_DS . $sModuleId . PHPFOX_DS . $actionName . PHPFOX_DS;
                @mkdir($sessionDir, 0777, true);
                file_put_contents($sessionDir . PHPFOX_DS . 'data.json', $data['data']);
                if (count($data['files'])) {
                    $this->copyFiles($sessionDir . PHPFOX_DS . 'files' . PHPFOX_DS, $data['files']);
                }
            }
        }
        if (isset($aVals['your_information']) && is_array($aVals['your_information'])) {
            foreach ($aVals['your_information'] as $value) {
                list($sModuleId, $actionName) = explode('.', $value);
                $data = Phpfox::callback($sModuleId . '.processCopyUserData_' . $actionName);
                $sessionDir = $sExportDir . PHPFOX_DS . $sModuleId . PHPFOX_DS . $actionName . PHPFOX_DS;
                @mkdir($sessionDir, 0777, true);
                file_put_contents($sessionDir . PHPFOX_DS . 'data.json', $data['data']);
                if (count($data['files'])) {
                    $this->copyFiles($sessionDir . PHPFOX_DS . 'files' . PHPFOX_DS, $data['files']);
                }
            }
        }

        return $hash;
    }

    private function copyFiles($to, $files)
    {
        @mkdir($to, 0777);
        foreach ($files as $file) {
            @copy($file, $to . basename($file));
        }
    }

}