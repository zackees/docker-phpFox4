<?php
defined('PHPFOX') or exit('NO DICE!');

class User_Component_Controller_Admincp_Importusers extends Phpfox_Component
{
    public function process()
    {
        $sType = $this->request()->get('type');

        if (empty($sType) || ($sType == 'upload' && empty($_FILES['import_user_file']))) {
            echo json_encode([
                'status' => false,
                'message' => _p('invalid_data')
            ]);
            exit;
        }

        $aFields = [
            'full_name',
            'user_name',
            'email',
        ];

        if (Phpfox::getParam('core.enable_register_with_phone_number')) {
            $aFields[] = 'full_phone_number';
        }

        $aFields = array_merge($aFields, [
            'user_group_id',
            'gender',
            'country_iso',
            'city_location',
            'postal_code',
            'country_child_id'
        ]);

        //Note: custom field types are select, multi-select, checkbox, radio -> value which included to file is option_id or text. Multiple value will be seperated by "_"
        list($aCustomFields, $aCustomFieldText) = Phpfox::getService('user.import')->createCustomFieldText();

        $aGenders = array_keys(Phpfox::getService('core')->getGenders(true));
        $aCountries = Phpfox::getService('core.country')->get();

        $aCountryCode = array_keys($aCountries);
        $aCountryTitle = array_values($aCountries);

        foreach ($aCountries as $sIso => $sCountry) {
            if (Phpfox::isPhrase('translate_country_iso_' . strtolower($sIso))) {
                $aCountries[$sIso] = _p('translate_country_iso_' . strtolower($sIso));
            }
        }

        $aUserGroups = [];
        foreach (Phpfox::getService('user.group')->getAll() as $aGroup) {
            $aUserGroups[$aGroup['user_group_id']] = \Core\Lib::phrase()->isPhrase($aGroup['title']) ? _p($aGroup['title']) : $aGroup['title'];
        }

        $aRequiredFields = ['full_name', 'user_name'];
        $bIsPhoneEnabled = Phpfox::getParam('core.enable_register_with_phone_number');

        if (!$bIsPhoneEnabled) {
            $aRequiredFields[] = 'email';
        }

        $aInitCheckingData = [
            'gender' => $aGenders,
            'country_iso' => [
                'country_code' => $aCountryCode,
                'country_title' => $aCountryTitle
            ],
            'main_field' => $aFields,
            'custom_field' => $aCustomFields,
            'custom_field_text' => $aCustomFieldText,
            'required_field' => $aRequiredFields,
            'group' => [
                'group_id' => array_keys($aUserGroups),
                'group_title' => array_values($aUserGroups)
            ]
        ];

        $sFilePath = '';
        $aFileInfo = [];
        if ($sType == 'upload') {
            $aFileInfo = pathinfo($_FILES["import_user_file"]["name"]);
            if (!in_array($aFileInfo['extension'], ['csv'])) {
                echo json_encode([
                    'status' => false,
                    'message' => _p('file_must_be_csv_only'),
                    'next' => 'start'
                ]);
                exit;
            }
            $sFileName = md5($aFileInfo['filename'] . time() . uniqid());
            $sFilePath = PHPFOX_DIR_FILE . 'cache' . PHPFOX_DS . $sFileName . '.' . $aFileInfo['extension'];
            if (!@move_uploaded_file($_FILES['import_user_file']['tmp_name'], $sFilePath)) {
                echo json_encode([
                    'status' => false,
                    'next' => 'start',
                    'message' => _p('unable_to_upload_file_due_to_a_server_error_or_restriction')
                ]);
                exit;
            }

            if (!$hFile = @fopen($sFilePath, 'r')) {

                echo json_encode([
                    'status' => false,
                    'next' => 'start',
                    'message' => _p('unable_to_upload_file_due_to_a_server_error_or_restriction')
                ]);
                exit;
            }
        } elseif ($sType == 'import') {
            $sFilePath = $this->request()->get('file_path');
            if (!$hFile = @fopen($sFilePath, 'r')) {
                echo json_encode([
                    'status' => false,
                    'next' => 'finish',
                    'message' => _p('unable_to_upload_file_due_to_a_server_error_or_restriction')
                ]);
                exit;
            }
        }

        list($iTotalLine, $aHeader, $aRows) = Phpfox::getService('user.import')->parseTextToArray($hFile);
        fclose($hFile);

        if ($iTotalLine <= 1) {
            echo json_encode([
                'status' => false,
                'next' => 'start',
                'message' => _p('file_must_be_more_than_one_line')
            ]);
            exit;
        }
        $aHeaderError = [];

        $aCheckRequired = array_intersect($aRequiredFields, $aHeader);

        if (!empty(array_diff($aRequiredFields, $aCheckRequired))) {
            $aHeaderError[] = _p('import_user_required_fields', [
                'number' => count($aRequiredFields),
                'fields' => implode(',', $aRequiredFields)
            ]);
        } elseif ($bIsPhoneEnabled && !in_array('full_phone_number', $aHeader) && !in_array('email', $aHeader)) {
            $aHeaderError[] = _p('imported_file_must_have_email_or_phone_number');
        }

        if (!empty($aHeaderError)) {
            echo json_encode([
                'status' => false,
                'next' => $sType == 'upload' ? 'start' : 'finish',
                'error' => $aHeaderError
            ]);
            exit;
        }

        $aSampleHeader = array_values(array_merge(array_combine($aFields, $aFields), array_combine($aCustomFieldText, $aCustomFieldText)));
        $aTempHeader = array_intersect($aSampleHeader, $aHeader);
        $bGetDiff = count($aTempHeader) >= (count($aHeader) - count($aTempHeader)) ? true : false;
        $aFieldKey = [];
        if ($bGetDiff) {
            foreach ($aHeader as $header_key => $header_value) {
                if (!in_array($header_value, $aSampleHeader)) {
                    $aFieldKey[] = $header_key;
                }
            }
        } else {
            foreach ($aHeader as $header_key => $header_value) {
                if (in_array($header_value, $aSampleHeader)) {
                    $aFieldKey[] = $header_key;
                }
            }
        }

        $aInitCheckingData['header'] = $aHeader = $aTempHeader;
        $aInitCheckingData['init_value'] = array_fill(0, count($aHeader), null);
        $aInitCheckingData['field_key'] = $aFieldKey;
        $aInitCheckingData['get_diff'] = $bGetDiff;

        if ($sType == 'upload') {
            $iTotalErrorLine = 0;
            $bDropCheck = false;

            foreach ($aRows as $aRow) {
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
                $bIsValid = Phpfox::getService('user.import')->checkRowData($aRow, $aInitCheckingData);

                if ($bIsValid === false) {
                    $iTotalErrorLine++;
                    if ($iTotalErrorLine >= round(($iTotalLine - 1) * 0.1)) {
                        $bDropCheck = true;
                        break;
                    }
                }
            }
            $aResponse = [
                'status' => true,
                'next' => 'start',
                'message' => $bDropCheck ? _p('import_user_more_than_error_in_your_file', ['total' => (($iTotalLine - 1) < 10 ? 1 : round(($iTotalLine - 1) * 0.1))]) : ($iTotalErrorLine > 0 ? _p('import_user_there_are_error_records_in_your_file', ['total' => $iTotalErrorLine]) : _p('upload_file_successfully_click_to_start_import')),
                'drop_check' => $bDropCheck,
                'is_completed' => $bDropCheck ? false : ($iTotalErrorLine > 0 ? false : true),
                'file_path' => $sFilePath,
                'file_name' => $aFileInfo['filename'] . '.' . $aFileInfo['extension'],
                'import_fields' => $aHeader
            ];
            echo json_encode($aResponse);
            exit;
        }

        $aVals = $this->request()->get('val');

        if (empty($aVals)) {
            echo json_encode([
                'status' => false,
                'message' => _p('invalid_data')
            ]);
            exit;
        }

        $aSelectedFields = array_values($aVals['selected_field']);
        if (array_diff($aSelectedFields, $aHeader)) {
            echo json_encode([
                'status' => false,
                'message' => _p('invalid_data')
            ]);
            exit;
        }

        $iSelectedGroup = (int)$aVals['user_group_id'];
        $bIsIncludeUserGroup = (int)$aVals['include_user_group'];
        $sFileName = $this->request()->get('file_name');

        $aTempSelectedField = $aSelectedFields;
        if ($bIsIncludeUserGroup) {
            $aTempSelectedField[] = 'user_group_id';
        }
        $aImportInsert = [
            'user_id' => Phpfox::getUserId(),
            'time_stamp' => PHPFOX_TIME,
            'file_name' => $sFileName,
            'status' => 'processing',
            'total_user' => $iTotalLine - 1,
            'import_field' => serialize($aTempSelectedField)
        ];

        $iImportId = Phpfox::getService('user.import')->addUserImport($aImportInsert);
        $iLimitTotalLine = 1000;
        $iTotalLine = $iTotalLine - 1;
        $iPosition = 1;
        $aInitCheckingData['header'] = $aHeader;
        $aInitCheckingData['selected_group'] = $iSelectedGroup;
        $aInitCheckingData['selected_field'] = $aSelectedFields;
        $aInitCheckingData['include_user_group_field'] = $bIsIncludeUserGroup;
        $sProcessingJobIds = '';
        while ($iTotalLine > 0) {
            $iJobTotalLine = $iTotalLine >= $iLimitTotalLine ? $iLimitTotalLine : $iTotalLine;
            $iTotalLine -= $iJobTotalLine;
            $iJobId = Phpfox_Queue::instance()->addJob('user_import_user', [
                'start' => $iPosition,
                'total' => $iJobTotalLine,
                'is_completed' => $iTotalLine == 0 ? true : false,
                'import_id' => $iImportId,
                'init_data' => $aInitCheckingData,
                'file_path' => $sFilePath
            ]);
            $iPosition += $iJobTotalLine;
            $sProcessingJobIds .= $iJobId . ',';
        }
        $sProcessingJobIds = trim($sProcessingJobIds, ',');
        if (!empty($sProcessingJobIds)) {
            Phpfox::getService('user.import')->updateUserImport($iImportId, ['processing_job_id' => $sProcessingJobIds]);
        }

        $aResponse = [
            'status' => true,
            'next' => 'finish',
            'message' => _p('import_user_completed'),
        ];
        echo json_encode($aResponse);
        exit;
    }
}
