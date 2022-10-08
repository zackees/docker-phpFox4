<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 *
 *
 * @copyright        [PHPFOX_COPYRIGHT]
 * @author        phpFox LLC
 * @package        Module_Admincp
 * @version        $Id: setting.class.php 6545 2013-08-30 08:41:44Z phpFox $
 */
class Admincp_Service_Setting_Setting extends Phpfox_Service
{
    private $_aPasswordSettings = [
        'core.mail_smtp_password',
        'core.ftp_password'
    ];

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('setting');
    }

    /**
     * Get modules for management
     * @return array
     */
    public function getModules()
    {
        $aModules = db()->select('DISTINCT m.module_id, m.phrase_var_name')
            ->from(Phpfox::getT('module'), 'm')
            ->join(Phpfox::getT('setting'), 's', 's.module_id = m.module_id AND s.is_hidden = 0')
            ->where(['m.is_active' => 1])
            ->order('m.module_id ASC')
            ->executeRows();

        foreach ($aModules as $index => $aModule) {
            if ($aModule['phrase_var_name'] == 'module_apps') {
                $aModules[$index]['title'] = _p('module_' . $aModule['module_id']);
            } else {
                $aModules[$index]['title'] = Phpfox::getLib('locale')->translate($aModule['module_id'], 'module');
            }
        }

        return $aModules;
    }

    /**
     * @param array $aSkipModules
     *
     * @return array
     */
    public function getForSearch($aSkipModules = [], $bSkipSpecialSettings = false)
    {
        $oUrl = Phpfox::getLib('url');
        $aNotAllowedToEdit = [];
        $aReturn = [];
        $aIgnoredSpecialSettings = [];

        if ($bSkipSpecialSettings) {
            if (PHPFOX_USE_DATE_TIME) {
                $aIgnoredSpecialSettings[] = 'identify_dst';
            }
        }

        $aRows = $this->database()
            ->select('s.*, lp.text AS language_var_name')
            ->from($this->_sTable, 's')
            ->group('setting_id', true)
            ->where('s.is_hidden = 0' . (count($aIgnoredSpecialSettings) ? ' AND s.var_name NOT IN ("' . implode('","', $aIgnoredSpecialSettings) . '")' : ''))
            ->leftJoin(Phpfox::getT('language_phrase'), 'lp', [
                "lp.language_id = '" . Phpfox_Locale::instance()
                    ->getLangId() . "'",
                "AND lp.var_name = s.phrase_var_name"
            ])
            ->execute('getSlaveRows');

        foreach (Phpfox_Setting::instance()->override as $key => $value) {
            $aNotAllowedToEdit[] = $key;
        }

        $phrases = [];
        $locale = Phpfox::getLib('locale');
        $f = function ($p, $i) use (&$phrases, $locale) {
            if (isset($phrases[$p])) return $phrases[$p];
            if ($i) return ($phrases[$p] = _p($p));
            if (Phpfox::isAppAlias($p)) {
                $sRealAppId = Phpfox::getAppId($p);
                $App = \Core\Lib::appInit($sRealAppId);
            } elseif (Phpfox::isApps($p)) {
                $sRealAppId = $p;
                $App = \Core\Lib::appInit($sRealAppId);
            }
            return (empty($App) || empty($App->name)) ? $locale->translate($p, 'module') : $App->name;
        };
        $glue = ' &raquo; ';

        foreach ($aRows as $iKey => $aRow) {
            if (!empty($aRow['language_var_name'])) {
                if ($aSkipModules && in_array($aRow['module_id'], $aSkipModules)) {
                    continue;
                }

                if (in_array($aRow['module_id'] . '.' . $aRow['var_name'], $aNotAllowedToEdit)) {
                    continue;
                }

                if (!empty($aRow['group_id'])) {
                    $sLink = $oUrl->makeUrl('admincp.setting.edit', ['group-id' => $aRow['group_id']]) . '#' . $aRow['var_name'];
                    $category = $f('settings', 1) . $glue . $f('setting_group_' . $aRow['group_id'], 1);
                } else {
                    $sLink = $oUrl->makeUrl('admincp.setting.edit', ['module-id' => $aRow['module_id']]) . '#' . $aRow['var_name'];
                    $category = $f('apps', 1) . $glue . $f($aRow['module_id'], 0) . $glue . $f('settings', 1);
                }

                $aParts = explode('</title><info>', $aRow['language_var_name']);
                $sTitle = strip_tags(htmlspecialchars_decode($aParts[0]));

                if ($aRow['var_name'] == 'reenter_email_on_signup' && Phpfox::getParam('core.enable_register_with_phone_number')) {
                    $sTitle = _p('force_users_to_reenter_email_or_phone_number');
                }

                $aReturn[] = [
                    'module_id' => $aRow['module_id'],
                    'link' => $sLink,
                    'type' => 'setting',
                    'title' => $sTitle,
                    'category' => $category,
                ];
            }
        }

        return $aReturn;
    }

    /**
     * @param $iId
     *
     * @return array|bool
     */
    public function getForEdit($iId)
    {
        if (!PHPFOX_DEBUG) {
            return false;
        }

        $aSetting = $this->database()->select('s.*, lp.text AS language_var_name')
            ->from($this->_sTable, 's')
            ->leftJoin(Phpfox::getT('language_phrase'), 'lp', [
                    "lp.language_id = '" . Phpfox_Locale::instance()->getLangId() . "'",
                    "AND lp.var_name = s.phrase_var_name"
                ]
            )
            ->where('s.setting_id = ' . (int)$iId)
            ->execute('getSlaveRow');

        if (!$aSetting['setting_id']) {
            return false;
        }

        $aSetting['value'] = $aSetting['value_actual'];
        $aSetting['type'] = $aSetting['type_id'];

        if (!empty($aSetting['language_var_name'])) {
            $aParts = explode('</title><info>', $aSetting['language_var_name']);
            $aSetting['title'] = str_replace('<title>', '', $aParts[0]);
            $aSetting['info'] = str_replace(["\n", '</info>'], ["<br />", ''], $aParts[1]);
        }

        return $aSetting;
    }

    /**
     * @param string $sVarName
     *
     * @return bool
     */
    public function isSetting($sVarName)
    {
        $sCacheId = $this->cache()->set('admincp_setting_' . md5($sVarName));

        if (false === ($aRow = $this->cache()->get($sCacheId))) {
            $aRow = $this->database()
                ->select('setting.var_name')
                ->from($this->_sTable, 'setting')
                ->where("setting.var_name = '" . $this->database()
                        ->escape($sVarName) . "'")
                ->execute('getSlaveRow');

            if (!isset($aRow['var_name'])) {
                return false;
            }
            $this->cache()->save($sCacheId, $aRow);
            Phpfox::getLib('cache')->group('admincp', $sCacheId);
        }
        return $aRow['var_name'];
    }

    /**
     * @param array $aCond
     * @param bool $bGroupByModule
     * @return array
     * @throws Exception
     * @since 4.6.0 remove cache entry
     */
    public function get($aCond = [], $bGroupByModule = false)
    {
        $this->database()
            ->select("setting.*, '' AS title")
            ->from($this->_sTable, 'setting')
            ->where($aCond)
            ->group('setting.setting_id', true)
            ->order("setting.ordering ASC");
        if ($bGroupByModule) {
            $this->database()->select(', a.apps_name, m.phrase_var_name as module_var_name')
                ->leftJoin(Phpfox::getT('apps'), 'a', 'a.apps_alias = setting.module_id')
                ->leftJoin(Phpfox::getT('module'), 'm', 'm.module_id = setting.module_id');
        }
        $aRows = $this->database()->execute('getSlaveRows');

        // Load all fonts used for CAPTCHA
        $sFontDir = Phpfox::getParam('core.dir_static') . 'image' . PHPFOX_DS . 'font' . PHPFOX_DS;
        $aFonts = [];
        $hDir = opendir($sFontDir);
        while ($sFile = readdir($hDir)) {
            if (!preg_match("/ttf/i", substr($sFile, -3))) {
                continue;
            }
            $aFonts[] = $sFile;
        }
        closedir($hDir);

        // Load all the editors that are valid

        $aNotAllowedToEdit = [];
        foreach (Phpfox_Setting::instance()->override as $key => $value) {
            $aNotAllowedToEdit[] = $key;
        }

        $aCacheSetting = [];
        foreach ($aRows as $iKey => $aRow) {
            if (isset($aCacheSetting[$aRow['var_name']])) {
                unset($aRows[$iKey]);

                continue;
            }

            if (in_array($aRow['module_id'] . '.' . $aRow['var_name'], $aNotAllowedToEdit)) {
                unset($aRows[$iKey]);

                continue;
            }

            if (in_array($aRow['module_id'] . '.' . $aRow['var_name'], $this->_aPasswordSettings)) {
                $aRows[$iKey]['type_id'] = 'password';
                // decode password
                $password = substr_replace(base64_decode(base64_decode($aRow['value_actual'])), '', -32);
                if ($password) {
                    $aRows[$iKey]['value_actual'] = $password;
                }
            }
            $aCacheSetting[$aRow['var_name']] = true;

            if (!empty($aRow['language_var_name'])) {
                $aRow['language_var_name'] = htmlspecialchars_decode($aRow['language_var_name']);
                $aParts = explode('</title><info>', $aRow['language_var_name']);
                $aRows[$iKey]['group_title'] = str_replace('<title>', '', $aParts[0]);
            }

            switch ($aRow['type_id']) {
                case 'drop':
                    $aArray = unserialize($aRow['value_actual']);
                    $aRows[$iKey]['values'] = $aArray;
                    $aRows[$iKey]['value_actual'] = implode(',', $aRows[$iKey]['values']['values']);
                    break;
                case 'drop_with_key':
                case 'select':
                case 'input:radio':
                case 'radio':
                    $aArray = unserialize($aRow['value_default']);
                    $aRows[$iKey]['values'] = $aArray['values'];
                    if ($aRows[$iKey]['value_actual'] == '') {
                        $aRows[$iKey]['value_actual'] = $aArray['values']['default'];
                    }
                    break;
                case 'multi_text':
                case 'currency':
                    if (!empty($aRow['value_actual'])) {
                        $aRow['value_actual'] = preg_replace_callback("/s:([0-9]+):\"(.*?)\";/is", function ($matches) {
                            return "s:" . strlen($matches[2]) . ":\"{$matches[2]}\";";
                        }, $aRow['value_actual']);

                        if (@unserialize($aRow['value_actual']) != false) {
                            if (is_array(unserialize($aRow['value_actual']))) {
                                $aRows[$iKey]['values'] = unserialize($aRow['value_actual']);
                            } else {
                                eval("\$aRows[\$iKey]['values'] = " . unserialize($aRow['value_actual']) . "");
                            }
                        }
                    }
                    break;
                case 'array':
                    if (!empty($aRow['value_actual'])) {
                        $aRow['value_actual'] = preg_replace_callback("/s:(.*):\"(.*?)\";/is", function ($matches) {
                            return "s:" . strlen($matches[2]) . ":\"{$matches[2]}\";";
                        }, $aRow['value_actual']);

                        if (@unserialize($aRow['value_actual']) != false) {
                            if (is_array(unserialize($aRow['value_actual']))) {
                                $aRows[$iKey]['values'] = unserialize($aRow['value_actual']);
                            } else {
                                eval("\$aRows[\$iKey]['values'] = " . unserialize($aRow['value_actual']) . "");
                            }
                        }
                    }
                    break;
                case 'multi_checkbox':
                    $aArray = unserialize($aRow['value_default']);
                    $aRows[$iKey]['values'] = $aArray['values'];
                    if ($aRows[$iKey]['value_actual'] == '') {
                        $aRows[$iKey]['value_actual'] = $aArray['default'];
                    } elseif (@unserialize($aRow['value_actual']) != false) {
                        if (is_array(unserialize($aRow['value_actual']))) {
                            $aRows[$iKey]['value_actual'] = unserialize($aRow['value_actual']);
                        } else {
                            eval("\$aRows[\$iKey]['value_actual'] = " . unserialize($aRow['value_actual']) . "");
                        }
                    }
                    break;
                default:
            }

            if (!empty($aRow['title'])) {
                $aRow['title'] = htmlspecialchars_decode($aRow['title']);
                $aParts = explode('</title><info>', $aRow['title']);
            } else {
                if (!empty($aRow['phrase_var_name'])) {
                    $aParts = explode('</title><info>', _p($aRow['phrase_var_name']));
                }
            }

            if (isset($aParts[0])) {
                $aRows[$iKey]['setting_title'] = (isset($aParts[0]) ? str_replace('<title>', '', $aParts[0]) : '');
                if (isset($aParts[1])) {
                    $aParts[1] = Phpfox::getLib('parse.bbcode')
                        ->preParse($aParts[1]);
                    $aParts[1] = Phpfox::getLib('parse.bbcode')
                        ->parse($aParts[1]);
                }
                $aRows[$iKey]['setting_info'] = (isset($aParts[1]) ? str_replace([
                    "\n",
                    '</info>'
                ], [
                    "<br />",
                    ''
                ], $aParts[1]) : '');
                if ($aRows[$iKey]['setting_info']) {
                    $aRows[$iKey]['setting_info'] = preg_replace_callback("/<setting>([a-z\._]+)<\/setting>/i", function($name){
                        $aSettingTitle = explode('.', $name[1]);
                        $sSettingTitle = is_array($aSettingTitle) && count($aSettingTitle) ? array_pop($aSettingTitle) : $name[1];
                        $aSetting = $this->get(['var_name' => $sSettingTitle]);
                        if (!empty($aSetting)) {
                            $sSettingTitle = $aSetting[0]['setting_title'];
                        }
                        return "<a href=\"" . Phpfox_Url::instance()->makeUrl('admincp', [
                                'setting',
                                'search',
                                'var' => $name[1]
                            ]) . "\">$sSettingTitle</a>";
                        }, $aRows[$iKey]['setting_info']);
                    $aRows[$iKey]['setting_info'] = preg_replace("/\{url link\='(.*?)'\}/is", "" . Phpfox_Url::instance()
                            ->makeUrl('$1') . "", $aRows[$iKey]['setting_info']);
                }
            }

            unset($aRows[$iKey]['title']);
            switch ($aRow['var_name']) {
                case 'on_signup_new_friend':
                case 'admin_in_charge_of_page_claims':
                    $aUserArray = [];
                    $userGroups = [ADMIN_USER_ID];
                    if ($aRow['var_name'] == 'on_signup_new_friend') {
                        $userGroups = [ADMIN_USER_ID, STAFF_USER_ID];
                    }
                    $aUsers = $this->database()
                        ->select('user_id, full_name')
                        ->from(Phpfox::getT('user'))
                        ->where('user_group_id IN (' . implode(',', $userGroups) . ')')
                        ->execute('getSlaveRows');
                    $aUserArray[0] = _p('none');
                    foreach ($aUsers as $aUser) {
                        $aUserArray[$aUser['user_id']] = Phpfox::getLib('parse.output')->clean($aUser['full_name']);
                    }
                    $aRows[$iKey]['type_id'] = 'drop_with_key';
                    $aRows[$iKey]['values'] = $aUserArray;
                    break;
                case 'captcha_font':
                    $aRows[$iKey]['type_id'] = 'drop';
                    $aRows[$iKey]['values'] = [
                        'default' => $aRow['value_actual'],
                        'values' => $aFonts
                    ];
                    $aRows[$iKey]['value_actual'] = implode(',', $aFonts);
                    break;
                case 'default_time_zone_offset':
                    $aTimezones = Phpfox::getService('core')->getTimeZones();
                    $aRows[$iKey]['type_id'] = 'drop_with_key';
                    $aRows[$iKey]['values'] = $aTimezones;
                    break;
                case 'ip_check':
                    $aIpCheck = [
                        '0' => '255.255.255.255',
                        '1' => '255.255.255.0',
                        '2' => '255.255.0.0'
                    ];
                    $aRows[$iKey]['type_id'] = 'drop_with_key';
                    $aRows[$iKey]['values'] = $aIpCheck;
                    break;
                case 'activity_points_conversion_rate':
                    $aValueActuals = [];
                    if (!empty($aRow['value_actual'])) {
                        $aValueActuals = json_decode($aRow['value_actual'], true);
                    }
                    $aCurrencies = Phpfox::getService('core.currency')->get();
                    $aDisplayValues = [];
                    foreach ($aCurrencies as $sCurrencyKey => $aCurrencyValue) {
                        $aDisplayValues[$sCurrencyKey] = (isset($aValueActuals[$sCurrencyKey]) ? $aValueActuals[$sCurrencyKey] : '');
                    }

                    $aRows[$iKey]['type_id'] = 'multi_text';
                    $aRows[$iKey]['values'] = $aDisplayValues;
                    break;
                case 'on_register_user_group':
                    $aUserGroups = Phpfox::getService('user.group')->getAll();
                    $aDisplayGroups = [];
                    foreach ($aUserGroups as $aUserGroup) {
                        $aDisplayGroups[$aUserGroup['user_group_id']] = $aUserGroup['title'];
                    }
                    $aRows[$iKey]['type_id'] = 'drop_with_key';
                    $aRows[$iKey]['values'] = $aDisplayGroups;
                    break;
                case 'site_offline_static_page':
                    $aPages = Phpfox::getService('page')->get();
                    $aDisplayPages = [
                        '0' => _p('select_a_page')
                    ];
                    foreach ($aPages as $aPage) {
                        $aDisplayPages[$aPage['page_id']] = $aPage['title'];
                    }
                    $aRows[$iKey]['type_id'] = 'drop_with_key';
                    $aRows[$iKey]['values'] = $aDisplayPages;
                    break;
                case 'enable_register_with_phone_number':
                    if ($aRows[$iKey]['value_actual']) {
                        $iCntExisted = $this->database()->select('COUNT(*)')
                            ->from(':user')
                            ->where('full_phone_number IS NOT NULL AND email = \'\' AND profile_page_id = 0')
                            ->executeField();
                        if ($iCntExisted) {
                            $aRows[$iKey]['read_only'] = true;
                            $aRows[$iKey]['setting_warning_info'] = 'disable_registration_using_phone_number_warning_shorten';
                        }
                    }
                    break;
            }
            if ($bGroupByModule) {
                $aRows[$iKey]['apps_name'] = !empty($aRow['apps_name']) ? _p($aRow['apps_name']) : _p(($aRow['module_var_name'] && $aRow['module_var_name'] != 'module_apps') ? $aRow['module_var_name'] : $aRow['module_id']);
                unset($aRows[$iKey]['module_var_name']);
            }
        }

        (($sPlugin = Phpfox_Plugin::get('admincp.service_setting_setting_get')) ? eval($sPlugin) : false);
        return $aRows;

    }

    /**
     * @param string $sProductId
     * @param null|string $sModuleId
     * @param bool $bCore
     *
     * @return bool
     */
    public function export($sProductId, $sModuleId = null, $bCore = false)
    {
        $aWhere = [];
        $aWhere[] = "setting.product_id = '" . $sProductId . "'";
        if ($sModuleId !== null) {
            $aWhere[] = " AND setting.module_id = '" . $sModuleId . "'";
        }

        $aRows = $this->database()->select('setting.*, product.title AS product_name, m.module_id AS module_name, setting_group.group_id AS group_name')
            ->from($this->_sTable, 'setting')
            ->leftJoin(Phpfox::getT('product'), 'product', 'product.product_id = setting.product_id')
            ->leftJoin(Phpfox::getT('module'), 'm', 'm.module_id = setting.module_id')
            ->leftJoin(Phpfox::getT('setting_group'), 'setting_group', 'setting_group.group_id = setting.group_id')
            ->where($aWhere)
            ->execute('getSlaveRows');

        if (!isset($aRows[0]['product_name'])) {
            return Phpfox_Error::set(_p('product_does_not_have_any_settings'));
        }

        if (!count($aRows)) {
            return false;
        }

        $oXmlBuilder = Phpfox::getLib('xml.builder');
        $oXmlBuilder->addGroup('settings');

        $aCache = [];
        foreach ($aRows as $aSetting) {
            if (isset($aCache[$aSetting['var_name']])) {
                continue;
            }
            $aCache[$aSetting['var_name']] = $aSetting['var_name'];

            $aSetting[($bCore ? 'value_default' : 'value_actual')] = str_replace("\r\n", "\n", $aSetting[($bCore ? 'value_default' : 'value_actual')]);
            $oXmlBuilder->addTag('setting', $aSetting[($bCore ? 'value_default' : 'value_actual')], [
                    'group' => $aSetting['group_name'],
                    'module_id' => $aSetting['module_name'],
                    'is_hidden' => $aSetting['is_hidden'],
                    'type' => $aSetting['type_id'],
                    'var_name' => $aSetting['var_name'],
                    'phrase_var_name' => $aSetting['phrase_var_name'],
                    'ordering' => $aSetting['ordering'],
                    'version_id' => $aSetting['version_id']
                ]
            );
        }
        $oXmlBuilder->closeGroup();

        return true;
    }

    /**
     * @param string $sProductId
     * @param null|string $sModuleId
     *
     * @return bool
     */
    public function exportGroup($sProductId, $sModuleId = null)
    {
        $aSql = [];
        if ($sModuleId !== null) {
            $aSql[] = "setting_group.module_id = '" . $sModuleId . "' AND";
        }
        $aSql[] = "setting_group.product_id = '" . $sProductId . "'";

        $aRows = $this->database()->select('setting_group.*, product.title AS product_name')
            ->from(Phpfox::getT('setting_group'), 'setting_group')
            ->leftJoin(Phpfox::getT('product'), 'product', 'product.product_id = setting_group.product_id')
            ->where($aSql)
            ->execute('getSlaveRows');

        if (!isset($aRows[0]['product_name'])) {
            return Phpfox_Error::set(_p('product_does_not_have_any_settings'));
        }

        if (!count($aRows)) {
            return false;
        }

        $oXmlBuilder = Phpfox::getLib('xml.builder');
        $oXmlBuilder->addGroup('setting_groups');

        $aCache = [];
        foreach ($aRows as $aSetting) {
            if (isset($aCache[$aSetting['var_name']])) {
                continue;
            }
            $aCache[$aSetting['var_name']] = $aSetting['var_name'];
            $oXmlBuilder->addTag('name', $aSetting['group_id'], [
                    'module_id' => $aSetting['module_id'],
                    'version_id' => $aSetting['version_id'],
                    'var_name' => $aSetting['var_name']
                ]
            );
        }
        $oXmlBuilder->closeGroup();

        return true;
    }

    /**
     * @param string $module
     *
     * @return bool
     */
    public function moduleHasSettings($module)
    {
        $total = $this->database()->select('COUNT(*)')
            ->from(Phpfox::getT('setting'))
            ->where(['module_id' => $module])
            ->execute('getSlaveField');

        return ($total ? true : false);
    }

    /**
     * @param array $aCond
     *
     * @return array
     */
    public function search($aCond = [])
    {
        $sCacheId = $this->cache()->set('admincp_setting_' . md5(serialize($aCond)));

        if (false === ($aRows = $this->cache()->get($sCacheId))) {
            (($sPlugin = Phpfox_Plugin::get('admincp.service_setting_setting_search')) ? eval($sPlugin) : false);

            $aRows = $this->database()
                ->select('setting.*')
                ->from($this->_sTable, 'setting')
                ->where($aCond)
                ->execute('getSlaveRows');
            $this->cache()->save($sCacheId, $aRows);
            Phpfox::getLib('cache')->group('admincp', $sCacheId);
        }
        return $aRows;
    }

    /**
     * @return array
     */
    public function getDangerSettings()
    {
        $aDangerSettings = [
            'core.force_https_secure_pages',
            'core.protect_admincp_with_ips',
            'core.custom_cookie_names_hash',
            'core.session_prefix',
            'core.cookie_path',
            'core.cookie_domain',
            'core.use_custom_cookie_names',
            'core.check_certificate_smtp_host_name',
            'core.username_regex_rule',
            'core.fullname_regex_rule',
            'core.special_characters_regex_rule',
            'core.html_regex_rule',
            'core.url_regex_rule',
            'core.currency_id_regex_rule',
        ];

        (($sPlugin = Phpfox_Plugin::get('admincp.service_setting_danger_settings')) ? eval($sPlugin) : false);

        return $aDangerSettings;
    }

    public function getDefaultNotificationSettings($sType = 'email', $getVarName = false, $getDisabled = false)
    {
        switch ($sType) {
            case 'sms':
                //SMS notifications
                $aEnableNotification = [
                    'core' => [
                        'core.enable_notifications' => [
                            'phrase' => _p('enable_sms_notifications'),
                            'default' => 1
                        ]
                    ]
                ];
                $aNotificationSettings = $aEnableNotification + Phpfox::massCallback('getNotificationSettings');
                break;
            default:
                //Email notifications
                $aEnableNotification = [
                    'core' => [
                        'core.enable_notifications' => [
                            'phrase' => _p('enable_email_notifications'),
                            'default' => 1
                        ]
                    ]
                ];
                $aNotificationSettings = $aEnableNotification + Phpfox::massCallback('getNotificationSettings');
                break;
        }
        $aNotifications = $this->getDefaultNotificationValues($sType);
        $aVarNames = [];
        if (is_array($aNotifications)) {
            foreach ($aNotificationSettings as $sModule => $aModules) {
                if (!is_array($aModules)) {
                    continue;
                }
                foreach ($aModules as $sKey => $aNotification) {
                    if (isset($aNotifications[$sKey])) {
                        $aNotificationSettings[$sModule][$sKey]['default'] = 0;
                        if ($getDisabled) {
                            $aVarNames[$sKey] = 0;
                        }
                    }
                    if ($getVarName && !$getDisabled) {
                        $aVarNames[$sKey] = $aNotificationSettings[$sModule][$sKey]['default'];
                    }
                }
            }
        }
        if ($getVarName) {
            return $aVarNames;
        }
        return $aNotificationSettings;
    }

    public function getDefaultNotificationValues($sType)
    {
        $cacheId = $this->cache()->set("admincp_default_{$sType}_notification_settings");
        if (false === ($aNotifications = $this->cache()->get($cacheId))) {
            $aNotifications = [];
            $aRows = $this->database()->select('user_notification')
                ->from(Phpfox::getT('user_notification'))
                ->where([
                    'notification_type' => $sType,
                    'is_admin_default' => 1
                ])
                ->execute('getSlaveRows');

            foreach ($aRows as $aRow) {
                $aNotifications[$aRow['user_notification']] = true;
            }
            $this->cache()->save($cacheId, $aNotifications);
        }
        return $aNotifications;
    }
    /**
     * If a call is made to an unknown method attempt to connect
     * it to a specific plug-in with the same name thus allowing
     * plug-in developers the ability to extend classes.
     *
     * @param string $sMethod is the name of the method
     * @param array $aArguments is the array of arguments of being passed
     *
     * @return null
     */
    public function __call($sMethod, $aArguments)
    {
        /**
         * Check if such a plug-in exists and if it does call it.
         */
        if ($sPlugin = Phpfox_Plugin::get('admincp.service_setting_setting___call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}