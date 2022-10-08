<?php

defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Controller_Admincp_Exportusers
 */
class User_Component_Controller_Admincp_Exportusers extends Phpfox_Component
{
    public function process()
    {

        if ($sDownload = $this->request()->get('download')) {
            $sFilePath = base64_decode($sDownload);
            if (file_exists($sFilePath)) {
                // Make sure there's not anything else left
                ob_clean();
                // Start sending headers
                header("Pragma: public"); // required
                header("Expires: 0");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Cache-Control: private", false); // required for certain browsers
                header("Content-Transfer-Encoding: binary");
                header("Content-Type: application/force-download");
                header("Content-Length: " . filesize($sFilePath));
                header("Content-Disposition: attachment; filename=\"" . basename($sFilePath) . "\";");

                // Send data
                echo @fox_get_contents($sFilePath);
            }
        } else {
            $aVals = $this->request()->get('val');
            $aFilter = json_decode(base64_decode($this->request()->get('filter_condition')), true);

            if (empty($aVals) || empty($aFilter)) {
                echo json_encode([
                    'status' => false,
                    'message' => _p('export_users_invalid_data')
                ]);
                exit;
            }

            $aFields = isset($aVals['field']) ? $aVals['field'] : [];
            $aCustomFields = isset($aVals['custom_field']) ? $aVals['custom_field'] : [];

            if (isset($aFilter['aConditions']['ip'])) {
                Phpfox::getService('user.browse')->ip($aFilter['aConditions']['ip']);
                unset($aFilter['aConditions']['ip']);
            }

            $aHeader = array_merge($aFields, $aCustomFields);

            list(, $aUsers) = Phpfox::getService('user.browse')->conditions($aFilter['aConditions'])
                ->sort($aFilter['sSort'])
                ->online($aFilter['bIsOnline'])
                ->extend(true)
                ->featured($aFilter['mFeatured'])
                ->pending(false)
                ->custom($aFilter['aCustomSearch'])
                ->gender($aFilter['bIsGender'])
                ->limit(0)
                ->get();

            if (empty($aUsers)) {
                echo json_encode([
                    'status' => true,
                    'message' => _p('export_users_not_found')
                ]);
                exit;
            }

            $aError = [];

            $sFilePath = PHPFOX_DIR_FILE . 'static' . PHPFOX_DS . 'export_user' . PHPFOX_DS . 'Export_users_' . Phpfox::getTime('d-m-Y H-i-s') . '.csv';
            if (@file_exists($sFilePath)) {
                @unlink($sFilePath);
            }

            if (!is_dir(PHPFOX_DIR_FILE . 'static' . PHPFOX_DS . 'export_user')) {
                if (!mkdir(PHPFOX_DIR_FILE . 'static' . PHPFOX_DS . 'export_user', 0777)) {
                    echo json_encode([
                        'status' => false,
                        'message' => _p('export_user_cannot_create_folder')
                    ]);
                    exit;
                }
                chmod(PHPFOX_DIR_FILE . 'static' . PHPFOX_DS . 'export_user', 0777);
            }

            if ($hFile = @fopen($sFilePath, 'w')) {
                fwrite($hFile, implode(',', $aHeader) . PHP_EOL);

                $aUserCustoms = Phpfox::getService('custom')->getCustomFieldValueByUserIds(array_column($aUsers, 'user_id'), $aCustomFields);

                $aUserCustomParsed = [];
                foreach ($aUserCustoms as $aUserCustom) {
                    $aUserCustomParsed[$aUserCustom['user_id']] = $aUserCustom;
                }


                $aCountries = Phpfox::getService('core.country')->get();

                foreach ($aCountries as $sIso => $sCountry) {
                    if (Phpfox::isPhrase('translate_country_iso_' . strtolower($sIso))) {
                        $aCountries[$sIso] = _p('translate_country_iso_' . strtolower($sIso));
                    }
                }


                foreach ($aUsers as $aUser) {
                    $aData = [];

                    foreach ($aFields as $sField) {
                        if (isset($aUser[$sField])) {
                            if ($sField == 'birthday_search') {
                                if (empty($aUser[$sField])) {
                                    $aData[] = null;
                                } else {
                                    $iUserYear = date('Y', $aUser[$sField]);
                                    $aData[] = (int)date('Y') - (int)$iUserYear;
                                }
                            } elseif ($sField == 'user_group_id') {
                                $aData[] = $aUser[$sField];
                            } elseif ($sField == 'last_activity') {
                                $aData[] = Phpfox::getTime('F j, Y H:i:s', $aUser[$sField]);
                            } elseif ($sField == 'gender') {
                                if ((int)$aUser[$sField] == 0) {
                                    $aData[] = null;
                                } elseif ((int)$aUser[$sField] == 127) {
                                    $aCustomGenders = Phpfox::getService('user')->getCustomGenders($aUser);
                                    foreach ($aCustomGenders as $key => $sCustomGender) {
                                        $aCustomGenders[$key] = html_entity_decode($sCustomGender, ENT_QUOTES, 'UTF-8');
                                    }
                                    $aData[] = is_array($aCustomGenders) && count($aCustomGenders) ? implode('_', $aCustomGenders) : null;
                                } else {
                                    $aData[] = $aUser[$sField];
                                }
                            } elseif ($sField == 'country_iso') {
                                $aData[] = !empty($aCountries[$aUser[$sField]]) ? $aUser[$sField] : null;
                            } elseif ($sField == 'country_child_id') {
                                $aState = Phpfox::getService('core.country')->getChildren($aUser['country_iso']);
                                $aData[] = !empty($aState[$aUser[$sField]]) ? $aUser[$sField] : null;
                            } else {
                                $aData[] = html_entity_decode($aUser[$sField], ENT_QUOTES, 'UTF-8');
                            }
                        } else {
                            $aData[] = null;
                        }
                    }

                    foreach ($aCustomFields as $sCustomField) {
                        $aData[] = is_array($aUserCustomParsed[$aUser['user_id']][$sCustomField]) ? implode('_', $aUserCustomParsed[$aUser['user_id']][$sCustomField]) : $aUserCustomParsed[$aUser['user_id']][$sCustomField];
                    }


                    if (count($aHeader) === count($aData)) {
                        $sUserRowContent = '';
                        foreach ($aData as $sContentRow) {
                            $sUserRowContent .= strpos($sContentRow, ',') != -1 ? '"' . (strpos($sContentRow, '"') != -1 ? str_replace('"', '""', $sContentRow) : $sContentRow) . '",' : (!empty($sContentRow) ? $sContentRow . ',' : 'NULL,');
                        }
                        $sUserRowContent = trim($sUserRowContent, ',') . PHP_EOL;
                        fwrite($hFile, $sUserRowContent);
                    } else {
                        $aError[] = _p('invalid_user_to_export', ['full_name' => $aUser['full_name']]);
                    }
                }

                fclose($hFile);

                echo json_encode([
                    'status' => true,
                    'message' => _p('export_users_completed'),
                    'is_completed' => true,
                    'download' => base64_encode($sFilePath),
                    'error' => $aError
                ]);
            } else {
                echo json_encode([
                    'status' => false,
                    'message' => _p('cannot_create_file_for_export_user')
                ]);
                exit;
            }
        }
        exit;
    }
}