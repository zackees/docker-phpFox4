<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 *
 *
 * @copyright        [PHPFOX_COPYRIGHT]
 * @author           phpFox LLC
 * @package          Module_Admincp
 * @version          $Id: process.class.php 7129 2014-02-19 13:27:09Z Fern $
 */
class Admincp_Service_Setting_Process extends Phpfox_Service
{
    private $_aPasswordSettings = [
        'mail_smtp_password',
        'ftp_password'
    ];

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('setting');
    }

    private function _clearCaches($params = [])
    {
        $this->cache()->remove('setting');
        $this->cache()->removeGroup('settings');
        $this->cache()->remove('app_settings');
        if (!empty($params['search_user_group_settings'])) {
            $this->cache()->remove('global_setting_all');
        }
    }

    /**
     * @param \Core\App\App $App
     */
    public function importFromApp($App)
    {
        $aSettingPhrases = [];

        $sAlias = $App->alias;
        if (empty($sAlias)) {
            $sAlias = $App->id;
        }
        // convert to array

        $aSettingItems = json_decode(json_encode($App->settings), 1);

        // get all settings from database and remove if not included in Install.php
        if ($App->isRemoveOldSettings()) {
            $aOldSettings = Phpfox::getLib('database')->select('var_name')->from(':setting')->where([
                'module_id' => [
                    'in' => "'$sAlias', '$App->id'"
                ]
            ])->executeRows();

            foreach (array_column($aOldSettings, 'var_name') as $sVarName) {
                if (!in_array($sVarName, array_keys($aSettingItems))) {
                    Phpfox::getLib('database')->update(':setting', ['is_hidden' => 1], ['var_name' => $sVarName]);
                }
            }
        }

        foreach ($aSettingItems as $key => $aSettingItem) {
            ($sPlugin = \Phpfox_Plugin::get('admincp.include_service_setting_importfromapp_start')) ? eval($sPlugin) : false;
            //Check the setting is exist
            $iCnt = Phpfox::getLib('database')->select('COUNT(*)')
                ->from(':setting')
                ->where([
                    'var_name'  => $key,
                    'module_id' => [
                        'in' => "'$sAlias', '$App->id'"
                    ]
                ])
                ->executeField();

            $sDefaultValue = null;
            $sActualValue = null;
            $sGroupId = isset($aSettingItem['group_id']) ? $aSettingItem['group_id'] : '';
            $sType = isset($aSettingItem['type']) ? $aSettingItem['type'] : 'input:text';
            $bIsHidden = isset($aSettingItem['is_hidden']) ? $aSettingItem['is_hidden'] : '0';
            if (!isset($aSettingItem['options'])) {
                $aSettingItem['options'] = [];
            }
            if ($sType == 'input:radio' and
                $aSettingItem['options'] == ["yes" => "Yes", "no" => "No"]) {
                $sType = 'boolean';
            }


            switch ($sType) {
                case 'select':
                case 'drop_with_key':
                case 'input:radio':
                    $sActualValue = $aSettingItem['value'];
                    $sDefaultValue = serialize([
                        'default' => $aSettingItem['value'],
                        'values'  => $aSettingItem['options'],
                    ]);
                    break;
                case 'drop':
                    $sActualValue = serialize([
                        'default' => $aSettingItem['value'],
                        'values'  => $aSettingItem['options'],
                    ]);
                    $sDefaultValue = $aSettingItem['value'];
                    break;
                case 'array':
                case 'multi_text':
                case 'currency':
                    $sActualValue = empty($aSettingItem['value']) ? serialize([]) : (is_array($aSettingItem['value']) ? serialize($aSettingItem['value']) : $aSettingItem['value']);
                    $sDefaultValue = $sActualValue;
                    break;
                case 'multi_checkbox':
                    $sActualValue = empty($aSettingItem['value']) ? serialize([]) : (is_array($aSettingItem['value']) ? serialize($aSettingItem['value']) : $aSettingItem['value']);
                    $sDefaultValue = serialize([
                        'default' => $aSettingItem['value'],
                        'values'  => $aSettingItem['options'],
                    ]);
                    break;
                default:
                    $sActualValue = isset($aSettingItem['value']) ? $aSettingItem['value'] : '';
                    $sDefaultValue = $sActualValue;
            }

            $sPhraseVarName = "setting_{$sAlias}_$key";
            if ($iCnt) {
                //Update setting value
                Phpfox::getLib('database')->update(':setting', [
                    'module_id'       => $sAlias,
                    'product_id'      => $App->id,
                    'is_hidden'       => $bIsHidden,
                    'group_id'        => $sGroupId,
                    'version_id'      => $App->version,
                    'type_id'         => $sType,
                    'phrase_var_name' => $sPhraseVarName,
                    'value_default'   => $sDefaultValue,
                ], [
                    'var_name'  => $key,
                    'module_id' => [
                        'in' => "'$sAlias', '$App->id'"
                    ],
                ]);
            } else {
                Phpfox::getLib('database')->insert(':setting', [
                    'module_id'       => $sAlias,
                    'product_id'      => $App->id,
                    'is_hidden'       => $bIsHidden,
                    'group_id'        => $sGroupId,
                    'version_id'      => $App->version,
                    'type_id'         => $sType,
                    'var_name'        => $key,
                    'phrase_var_name' => $sPhraseVarName,
                    'value_actual'    => $sActualValue,
                    'value_default'   => $sDefaultValue,
                ]);
            }

            if (isset($aSettingItem['description'])) {
                $aSettingPhrases[$sPhraseVarName] = [
                    'en' => '<title>' . $aSettingItem['info'] . '</title><info>' . $aSettingItem['description'] . '</info>',
                    'legacy_phrase' => $key
                ];
            } else {
                $aSettingPhrases[$sPhraseVarName] = [
                    'en' => $aSettingItem['info'],
                    'legacy_phrase' => $key
                ];
            }

            ($sPlugin = \Phpfox_Plugin::get('admincp.include_service_setting_importfromapp_end')) ? eval($sPlugin) : false;

        }

        \Core\Lib::phrase()->addPhrase($aSettingPhrases, null, false);
    }

    /**
     * @param array $aVals
     *
     * @return string
     */
    public function add($aVals)
    {
        switch ($aVals['type']) {
            case 'array':
                // Make sure its an array
                if (preg_match("/^array\((.*)\);$/i", $aVals['value'])) {
                    $aVals['value'] = serialize($aVals['value']);
                } else {
                    return Phpfox_Error::set(_p('not_valid_array'));
                }
                break;
            case 'integer':
                if (!is_numeric($aVals['value'])) {
                    return Phpfox_Error::set(_p('value_must_be_numeric'));
                }
                break;
            case 'drop':
                $aDropDowns = explode(',', $aVals['value']);

                $aVals['value'] = serialize([
                        'default' => $aDropDowns[0],
                        'values'  => $aDropDowns,
                    ]
                );
                break;
            default:

                break;
        }

        $iGroupId = $aVals['group_id'];
        $iModule = $aVals['module_id'];
        $iProductId = $aVals['product_id'];
        $aVals['var_name'] = preg_replace('/ +/', '_',
            preg_replace('/[^0-9a-zA-Z_ ]+/', '', trim($aVals['var_name'])));
        $aVals['var_name'] = strtolower($aVals['var_name']);

        $sPhrase = 'setting_' . Phpfox::getService('language.phrase.process')
                ->prepare($aVals['var_name']);

        $iLastOrder = $this->database()->select('ordering')
            ->from($this->_sTable)
            ->where("group_id = '{$iGroupId}' AND module_id = '{$iModule}' AND product_id = '{$iProductId}'")
            ->order('ordering DESC')
            ->execute('getSlaveField');

        $iId = $this->database()->insert($this->_sTable, [
                'group_id'        => (empty($iGroupId) ? null : $iGroupId),
                'module_id'       => (empty($iModule) ? null : $iModule),
                'product_id'      => $iProductId,
                'version_id'      => Phpfox::getId(),
                'type_id'         => $aVals['type'],
                'var_name'        => $aVals['var_name'],
                'phrase_var_name' => $sPhrase,
                'value_actual'    => $aVals['value'],
                'value_default'   => $aVals['value'],
                'ordering'        => ((int)$iLastOrder + 1),
            ]
        );

        $sPhrase = Phpfox::getService('language.phrase.process')->add([
                'var_name' => 'setting_' . $aVals['var_name'],
                'text'     => [
                    'en' => '<title>' . $aVals['title'] . '</title><info>'
                        . $aVals['info'] . '</info>',
                ],
            ]
        );

        // Clear the setting cache
        $this->_clearCaches();

        return (empty($iModule) ? '' : $iModule . '.') . $aVals['var_name'];
    }

    /**
     * @param array $aVals
     *
     * @return bool
     */
    public function update($aVals)
    {
        if (isset($aVals['order'])) {
            foreach ($aVals['order'] as $sVar => $iOrder) {
                $this->database()->update($this->_sTable, ['ordering' => (int)$iOrder], "var_name = '" . $this->database()->escape($sVar) . "'");
            }
        }

        if (isset($aVals['value']['admin_debug_mode'])
            && file_exists(PHPFOX_DIR . 'file' . PHPFOX_DS . 'log' . PHPFOX_DS
                . 'debug.php')
        ) {
            Phpfox_File::instance()->unlink(PHPFOX_DIR . 'file' . PHPFOX_DS
                . 'log' . PHPFOX_DS . 'debug.php');
        }

        $aValues = [];
        $bCanClearGlobalSettingsSearch = false;

        if ((!empty($aVals['order']))) {
            $bCanClearGlobalSettingsSearch = array_key_exists('search_group_settings', $aVals['order']) || array_key_exists('reenter_email_on_signup', $aVals['order']);
        } elseif (!empty($aVals['value'])) {
            $bCanClearGlobalSettingsSearch = array_key_exists('search_group_settings', $aVals['value']) || array_key_exists('reenter_email_on_signup', $aVals['value']);
        }

        if (!isset($aVals['value'])) {
            // when logged out because of inactivity and refresh and resubmit it would throw an undefined index error
            $this->_clearCaches([
                'search_user_group_settings' => $bCanClearGlobalSettingsSearch,
            ]);
            return true;
        }

        foreach ($aVals['value'] as $sKey => $mValue) {
            if (is_array($mValue)) {
                if ($sKey == 'activity_points_conversion_rate') {
                    $aValues['value_actual'] = json_encode($mValue);
                } else if ($sKey == 'captcha_font') {
                    $aValues['value_actual'] = $mValue['value'];
                } else {
                    if (isset($mValue['real']) && isset($mValue['value'])) {
                        $mValue['value'] = trim($mValue['value']);
                        $aSub = [];
                        $aSub[] = $mValue['value'];
                        $aParts = explode(',', $mValue['real']);
                        foreach ($aParts as $sPart) {
                            $sPart = trim($sPart);
                            if ($sPart == $mValue['value']) {
                                continue;
                            }
                            $aSub[] = $sPart;
                        }

                        if ($sKey == 'admin_debug_mode'
                            && $mValue['value'] != 'level_0'
                        ) {
                            if ($hFile = @fopen(PHPFOX_DIR . 'file' . PHPFOX_DS
                                . 'log' . PHPFOX_DS . 'debug.php', 'w+')
                            ) {
                                fwrite($hFile,
                                    '<?php define(\'PHPFOX_DEBUG\', true); define(\'PHPFOX_DEBUG_LEVEL\', '
                                    . trim(preg_replace('/Level(_)/i', '',
                                        $mValue['value'])) . '); ?>');
                                fclose($hFile);
                            }
                        }

                        // Prepare the array for the database
                        $aValues['value_actual'] = serialize([
                                'default' => $mValue['value'],
                                'values'  => $aSub,
                            ]
                        );
                    } else {
                        $aCached = [];
                        foreach ($mValue as $iValueKey => $sValueKey) {
                            // Make sure we don't have any duplicate values and multi_checkbox_off
                            if ($sValueKey == 'core_multi_checkbox_off' || isset($aCached[$sValueKey])) {
                                // Remove the duplicate value
                                unset($mValue[$iValueKey]);
                            }
                            // Cache for duplicate value check
                            $aCached[$sValueKey] = $sValueKey;
                        }

                        // Prepare the array for the database
                        $aValues['value_actual'] = serialize(str_replace('array ', 'array', var_export($mValue, true)) . ';');
                    }
                }
            } else {
                if ($sKey == 'title_delim') {
                    $mValue = Phpfox::getLib('parse.input')->convert($mValue);
                }

                if ($sKey == 'session_prefix') {
                    $mValue = substr(preg_replace("/[^A-Za-z0-9]/", "", $mValue), 0, 15);
                }

                // check password setting and encode
                if ($mValue != '' && in_array($sKey, $this->_aPasswordSettings)) {
                    $mValue = base64_encode(base64_encode($mValue . md5(Phpfox::getParam('core.salt'))));
                }

                // clear the cache from the featured users
                if ($sKey == 'how_many_featured_members') {
                    $this->cache()->remove('featured_users');
                }
                $aValues['value_actual'] = $mValue;

                if ($sKey == 'admin_cp' && $mValue != 'admincp') {
                    if (empty($mValue)
                        || !Phpfox::getLib('parse.input')->allowTitle($mValue,
                            _p('admincp_name_not_allowed') . ': '
                            . strip_tags($mValue))
                    ) {
                        return false;
                    }
                }

                if ($sKey == 'profile_use_id' && $mValue == '1') {
                    $aUsers = $this->database()->select('user_id')
                        ->from(Phpfox::getT('user'))
                        ->where('profile_page_id = 0')
                        ->execute('getSlaveRows');
                    foreach ($aUsers as $aUser) {
                        $this->database()->update(Phpfox::getT('user'),
                            ['user_name' => 'profile-' . $aUser['user_id']],
                            'user_id = ' . $aUser['user_id']);
                    }
                }

                if ($sKey == 'cron_delete_messages_delay') {
                    $this->database()->update(Phpfox::getT('cron'), ['every' => $mValue],
                        "module_id = 'mail'");
                }

                if ($sKey == 'enable_register_with_phone_number' && !$mValue) {
                    $iCntExisted = $this->database()->select('COUNT(*)')
                        ->from(':user')
                        ->where('full_phone_number IS NOT NULL AND email = \'\' AND profile_page_id = 0')
                        ->executeField();
                    if ($iCntExisted) {
                        return Phpfox_Error::set(_p('disable_registration_using_phone_number_warning'));
                    }
                }
            }

            $this->database()->update($this->_sTable, $aValues, "var_name = '" . $this->database()->escape($sKey) . "'");
        }

        // Clear the setting cache
        $this->_clearCaches([
            'search_user_group_settings' => $bCanClearGlobalSettingsSearch,
        ]);

        return true;
    }

    /**
     * @param array $aVals
     * @param bool  $bMissingOnly
     *
     * @return bool|int
     */
    public function import($aVals, $bMissingOnly = false)
    {
        if (!isset($aVals['product'])) {
            return Phpfox_Error::set(_p('unable_import_settings'));
        }

        if (!isset($aVals['setting'])) {
            return Phpfox_Error::set(_p('unable_import_settings'));
        }

        $iProductId = Phpfox::getService('admincp.product')
            ->getId($aVals['product']);
        if (!$iProductId) {
            $iProductId = 1;
        }

        if ($bMissingOnly) {
            $aCache = [];
            $aRows = $this->database()->select('var_name')
                ->from($this->_sTable)
                ->execute('getRows', [
                    'free_result' => true,
                ]);
            foreach ($aRows as $aRow) {
                $aCache[$aRow['var_name']] = $aRow['var_name'];
            }

            $aSql = [];
            foreach ($aVals['setting'] as $aSetting) {
                if (!in_array($aSetting['var_name'], $aCache)) {
                    $iModuleId = Phpfox_Module::instance()
                        ->getModuleId($aSetting['module']);
                    $aSql[] = [
                        (empty($aSetting['group']) ? null : $aSetting['group']),
                        $iModuleId,
                        $iProductId,
                        '',
                        $aSetting['version_id'],
                        $aSetting['type'],
                        $aSetting['var_name'],
                        $aSetting['phrase_var_name'],
                        $aSetting['value'],
                        $aSetting['value'],
                        $aSetting['ordering'],
                    ];
                }
            }

            if ($aSql) {
                $this->database()->multiInsert($this->_sTable, [
                    'group_id',
                    'module_id',
                    'product_id',
                    'is_hidden',
                    'version_id',
                    'type',
                    'var_name',
                    'phrase_var_name',
                    'value_actual',
                    'value_default',
                    'ordering',
                ], $aSql);
            }
        } else {
            $aSql = [];
            foreach ($aVals['setting'] as $aValue) {
                $iModuleId = (int)Phpfox_Module::instance()
                    ->getModuleId($aValue['module']);
                $aSql[] = [
                    (empty($aSetting['group']) ? null : $aSetting['group']),
                    $iModuleId,
                    $iProductId,
                    $aValue['is_hidden'],
                    $aValue['version_id'],
                    $aValue['type'],
                    $aValue['var_name'],
                    $aValue['phrase_var_name'],
                    $aValue['value'],
                    $aValue['value'],
                    $aValue['ordering'],

                ];
            }

            $this->database()->multiInsert($this->_sTable, [
                'group_id',
                'module_id',
                'product_id',
                'is_hidden',
                'version_id',
                'type',
                'var_name',
                'phrase_var_name',
                'value_actual',
                'value_default',
                'ordering',
            ], $aSql);
        }

        return count($aSql);
    }

    /**
     * @param string $sSetting
     * @param string $sValue
     *
     * @return bool
     */
    public function removeSettingFromArray($sSetting, $sValue)
    {
        $aSetting = $this->database()->select('setting_id, value_actual')
            ->from(Phpfox::getT('setting'))
            ->where('var_name = \'' . $this->database()->escape($sSetting)
                . '\'')
            ->execute('getSlaveRow');

        if (!isset($aSetting['setting_id'])) {
            return Phpfox_Error::set(_p('unable_to_find_the_setting_you_are_trying_to_edit_dot'));
        }

        $aCache = [];
        if (!empty($aSetting['value_actual'])) {
            $sValues = unserialize($aSetting['value_actual']);

            $aValues = null;
            eval('$aValues = ' . $sValues . '');

            foreach ($aValues as $mValue) {
                if (empty($mValue)) {
                    continue;
                }

                if ($mValue == $sValue) {
                    continue;
                }

                $aCache[] = $mValue;
            }
        }

        $this->database()->update(Phpfox::getT('setting'), [
            'value_actual' => (count($aCache) ? ''
                . serialize(str_replace('array ', 'array',
                        var_export($aCache, true)) . ';') : ''),
        ], 'setting_id = ' . $aSetting['setting_id']);

        // Clear the setting cache
        $this->_clearCaches();

        return true;
    }

    /**
     * @param array $aXml
     *
     * @return int
     */
    public function findMissingSettings($aXml)
    {
        $iCnt = 0;
        foreach ($aXml as $sModule => $aSettings) {
            if (empty($aSettings) || !isset($aSettings['setting'])) {
                continue;
            }
            $aRows = (isset($aSettings['setting'][1]) ? $aSettings['setting']
                : [$aSettings['setting']]);

            foreach ($aRows as $aSetting) {
                $iMissing = $this->database()->select('COUNT(*)')
                    ->from(Phpfox::getT('setting'))
                    ->where('module_id = \'' . $sModule . '\' AND var_name = \''
                        . $aSetting['var_name'] . '\'')
                    ->execute('getSlaveField');

                if (!$iMissing) {
                    $iCnt++;
                    $this->database()->insert(Phpfox::getT('setting'), [
                            'group_id'        => (empty($aSetting['group'])
                                ? null : $aSetting['group']),
                            'module_id'       => $sModule,
                            'product_id'      => 'phpfox',
                            'is_hidden'       => $aSetting['is_hidden'],
                            'version_id'      => $aSetting['version_id'],
                            'type_id'         => $aSetting['type'],
                            'var_name'        => $aSetting['var_name'],
                            'phrase_var_name' => $aSetting['phrase_var_name'],
                            'value_actual'    => $aSetting['value'],
                            'value_default'   => $aSetting['value'],
                            'ordering'        => $aSetting['ordering'],
                        ]
                    );
                }
            }
        }

        return $iCnt;
    }

    public function updateDefaultNotificationSettings($aVals, $sType)
    {
        if (empty($sType) && isset($aVals['notification_type'])) {
            $sType = $aVals['notification_type'];
        }
        $aNotifications = Phpfox::getService('admincp.setting')->getDefaultNotificationValues($sType);
        foreach ($aVals['notification'] as $sVar => $iVal) {
            $aQuery = [
                'user_id' => 0,
                'user_notification' => $sVar,
                'notification_type' => $sType,
                'is_admin_default' => 1
            ];
            if (!$iVal) {
                $this->database()->delete(Phpfox::getT('user_notification'), $aQuery);
            }
            elseif(!isset($aNotifications[$sVar])) {
                $this->database()->insert(Phpfox::getT('user_notification'), $aQuery);
            }
        }
        $this->cache()->remove("admincp_default_{$sType}_notification_settings");
        return true;
    }
    /**
     * If a call is made to an unknown method attempt to connect
     * it to a specific plug-in with the same name thus allowing
     * plug-in developers the ability to extend classes.
     *
     * @param string $sMethod    is the name of the method
     * @param array  $aArguments is the array of arguments of being passed
     *
     * @return  null
     */
    public function __call($sMethod, $aArguments)
    {
        /**
         * Check if such a plug-in exists and if it does call it.
         */
        if ($sPlugin
            = Phpfox_Plugin::get('admincp.service_setting_process__call')
        ) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::'
            . $sMethod . '()', E_USER_ERROR);
    }
}