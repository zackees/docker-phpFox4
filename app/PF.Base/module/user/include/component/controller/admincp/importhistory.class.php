<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Controller_Admincp_Importhistory
 */
class User_Component_Controller_Admincp_Importhistory extends Phpfox_Component
{
    public function process()
    {
        $bIsImportHistoryPage = true;
        $aAssign = [];
        $iPage = 0;
        $iSize = 10;
        $iCnt = 0;

        if ($iImportId = $this->request()->get('import_id')) {
            $bIsImportHistoryPage = false;
            $bIsValidImport = false;
            if ($aImport = Phpfox::getService('user.import')->getImport($iImportId, true)) {
                $aFieldTexts = array_merge([
                    'user_name' => _p('display_name'),
                    'full_name' => _p('full_name'),
                    'email' => _p('email_address'),
                    'full_phone_number' => _p('phone_number'),
                    'user_group_id' => _p('user_group'),
                    'gender' => _p('gender'),
                    'country_iso' => _p('location'),
                    'city_location' => _p('city'),
                    'postal_code' => _p('zip_postal_code'),
                    'country_child_id' => _p('state_province'),
                ], Phpfox::getService('user.import')->createCustomFieldText(true));
                $aTempFields = $aFields = unserialize($aImport['import_field']);
                foreach ($aFields as $key => $value) {
                    $aFields[$value] = isset($aFieldTexts[$value]) ? $aFieldTexts[$value] : '';
                    unset($aFields[$key]);
                }

                $bIsValidImport = true;
                $aErrorLog = unserialize($aImport['error_log']);

                $iPage = !empty($this->request()->get('page')) ? $this->request()->get('page') : 1;
                $iOffset = empty($iPage) ? 0 : ((int)$iPage - 1) * $iSize;
                $iCnt = is_array($aErrorLog) ? count($aErrorLog) : 0;
                $aTemp = is_array($aErrorLog) ? array_slice($aErrorLog, $iOffset, $iSize) : [];
                $iTotalPhoneValues = 0;
                $aFilters = [];
                foreach ($aTemp as $key => $row) {
                    $row_key = explode('_', $key);
                    if (!empty($row_key) && count($row_key) == 2) {
                        $aCombine = array_combine($aTempFields, array_fill(0, count($aTempFields), null));
                        $aMergeTemp = array_merge($aCombine, $row);
                        foreach ($aMergeTemp as $merge_key => $merge_value) {
                            if ($merge_key == 'full_phone_number') {
                                $iTotalPhoneValues++;
                            }
                            if (!empty($merge_value['error']) && is_array($merge_value['error'])) {
                                $tempError = [];
                                foreach ($merge_value['error'] as $var_name => $param) {
                                    $tempError[] = _p($var_name, $param);
                                }
                                $aMergeTemp[$merge_key] = [
                                    'error_code' => base64_encode(json_encode($tempError))
                                ];
                            } else {
                                if (empty($merge_value)) {
                                    $aMergeTemp[$merge_key] = _p('n_a');
                                } else {
                                    $aMergeTemp[$merge_key] = is_array($merge_value) ? implode('_', $merge_value) : $merge_value;
                                }
                            }
                        }
                        $aFilters[$row_key[1]] = $aMergeTemp;

                    }
                }

                $aAssign['aErrorLogs'] = $aFilters;
                $aAssign['aImport'] = $aImport;
                $aAssign['aFields'] = $aFields;
                $aAssign['aRequiredFields'] = ['full_name', 'user_name'];
                if (!Phpfox::getParam('core.enable_register_with_phone_number')) {
                    $aAssign['aRequiredFields'][] = 'email';
                    if (!$iTotalPhoneValues) {
                        unset($aAssign['aRequiredFields']['full_phone_number']);
                    }
                }
            }
            $aAssign['bIsValidImport'] = $bIsValidImport;
        } else {
            $aStatus = [
                'processing' => _p('processing'),
                'stopped' => _p('stopped'),
                'completed' => _p('done')
            ];

            $aSearch = $this->request()->get('search');
            if (!empty($aSearch['owner'])) {
                $this->search()->setCondition('AND (u.full_name LIKE "%' . $aSearch['owner'] . '%" OR u.user_name LIKE "%' . $aSearch['owner'] . '%")');
            }
            if (!empty($aSearch['status'])) {
                $this->search()->setCondition('AND (ui.status = "' . $aSearch['status'] . '")');
            }
            if (!empty($aSearch['from_month']) && !empty($aSearch['to_month'])) {
                $iFromDate = Phpfox::getLib('date')->mktime(0, 0, 0, $aSearch['from_month'], $aSearch['from_day'], $aSearch['from_year']);
                $iToDate = Phpfox::getLib('date')->mktime(23, 59, 59, $aSearch['to_month'], $aSearch['to_day'], $aSearch['to_year']);
                $this->search()->setCondition('AND (ui.time_stamp BETWEEN ' . $iFromDate . ' AND ' . $iToDate . ')');
            }

            $iPage = $this->request()->get('page');
            list($aImports, $iCnt) = Phpfox::getService('user.import')->getImportsByConditions($this->search()->getConditions(), $iPage, $iSize);

            $sCurrentDate = '';
            switch (Phpfox::getParam("core.date_field_order")) {
                case "DMY":
                {
                    $sCurrentDate = Phpfox::getTime('j') . '/' . Phpfox::getTime('n') . '/' . Phpfox::getTime('Y');
                    break;
                }
                case "MDY":
                {
                    $sCurrentDate = Phpfox::getTime('n') . '/' . Phpfox::getTime('j') . '/' . Phpfox::getTime('Y');
                    break;
                }
                case "YMD":
                {
                    $sCurrentDate = Phpfox::getTime('Y') . '/' . Phpfox::getTime('n') . '/' . Phpfox::getTime('j');
                    break;
                }
            }

            $aAssign = [
                'aStatus' => $aStatus,
                'aForms' => !empty($aSearch) ? $aSearch : [
                    'from_day' => Phpfox::getTime('j'),
                    'from_month' => Phpfox::getTime('n'),
                    'from_year' => Phpfox::getTime('Y'),
                    'to_day' => Phpfox::getTime('j'),
                    'to_month' => Phpfox::getTime('n'),
                    'to_year' => Phpfox::getTime('Y'),
                ],
                'aImports' => $aImports,
                'sCurrentDate' => $sCurrentDate,
                'sDefaultDateFormat' => Phpfox::getTime('j') . '/' . Phpfox::getTime('n') . '/' . Phpfox::getTime('Y')
            ];
        }

        Phpfox_Pager::instance()->set(['page' => $iPage, 'size' => $iSize, 'count' => $iCnt]);

        $this->template()
            ->setTitle(_p('import_user_history'))
            ->setBreadCrumb(_p('import_user_history'))
            ->assign(array_merge($aAssign, ['bIsImportHistoryPage' => $bIsImportHistoryPage]));
    }
}