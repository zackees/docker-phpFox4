<?php
defined('PHPFOX') or exit('NO DICE!');


class User_Job_ImportUser extends \Core\Queue\JobAbstract
{
    public function perform()
    {
        if (function_exists('ini_set')) {
            ini_set('memory_limit', '-1');
            ini_set('max_execution_time', 0);
        }
        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }

        $aParams = $this->getParams();
        $iImportId = (int)$aParams['import_id'];
        $iStart = (int)$aParams['start'];
        $iTotal = (int)$aParams['total'];
        $bIsCompleted = $aParams['is_completed'];
        $aInitCheckingData = $aParams['init_data'];
        $sFilePath = $aParams['file_path'];
        $aFieldKey = $aInitCheckingData['field_key'];
        $bGetDiff = $aInitCheckingData['get_diff'];
        $bIsIncludeUserGroup = $aInitCheckingData['include_user_group_field'];

        $hFile = @fopen($sFilePath, 'r');
        $aRows = Phpfox::getService('user.import')->parseTextToArray($hFile, $iStart, $iTotal);
        fclose($hFile);


        $aRowErrors = [];
        $iTotalSuccess = 0;
        foreach ($aRows as $iKey => $aRow) {
            $aTempRow = [];
            if ($bGetDiff) {
                foreach ($aFieldKey as $field_key) {
                    unset($aRow[$field_key]);
                }
                $aTempRow = array_values($aRow);
            } else {
                foreach ($aFieldKey as $field_key) {
                    $aTempRow[] = $aRow[$field_key];
                }
            }

            $aRow = array_replace($aInitCheckingData['init_value'], $aTempRow);
            list($aMerge, $aRowError) = Phpfox::getService('user.import')->checkRowData($aRow, $aInitCheckingData);
            if (empty($aRow['user_group_id'])) {
                $aMerge['user_group_id'] = !empty($aInitCheckingData['selected_group']) ? $aInitCheckingData['selected_group'] : $aMerge['user_group_id'];
            }
            $aFinalError = Phpfox::getService('user.import')->importUser($aMerge, $aRowError);
            if (!empty($aFinalError)) {
                if ($bIsIncludeUserGroup) {
                    $sUserGroupTemp = $aMerge['user_group_id'];
                    unset($aMerge['user_group_id']);
                    $aMerge['user_group_id'] = $sUserGroupTemp;
                } else {
                    unset($aMerge['user_group_id']);
                }

                $aRowErrors['row_' . ($iStart + $iKey)] = array_merge($aMerge, $aFinalError);
            } else {
                $iTotalSuccess++;
            }
        }
        $aImport = Phpfox::getService('user.import')->getImport($iImportId);
        if (!empty($aImport)) {
            $aErrorLog = unserialize($aImport['error_log']);
            $aErrorLog = !empty($aErrorLog) && is_array($aErrorLog) ? (!empty($aRowErrors) ? array_merge($aErrorLog, $aRowErrors) : $aErrorLog) : $aRowErrors;
            $aUpdate = [
                'total_imported' => (int)$aImport['total_imported'] + (int)$iTotalSuccess,
                'error_log' => !empty($aErrorLog) ? serialize($aErrorLog) : null
            ];
            if ($bIsCompleted) {
                $aUpdate['status'] = 'completed';
                $aUpdate['processing_job_id'] = '';
                @unlink($sFilePath);
                (Phpfox::isModule('notification') ? Phpfox::getService('notification.process')->add('user_import_user', $aImport['import_id'], $aImport['user_id']) : null);
            }
            Phpfox::getService('user.import')->updateUserImport($iImportId, $aUpdate);
        }
        $this->delete();
    }
}