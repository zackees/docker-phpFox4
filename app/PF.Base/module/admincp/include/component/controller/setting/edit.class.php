<?php

use Core\Lib;

defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Admincp_Component_Controller_Setting_Edit
 */
class Admincp_Component_Controller_Setting_Edit extends Phpfox_Component
{
    private function scanValidation($sModuleId, $sGroupId, &$aSettings)
    {
        $aScanPluginNames = [];
        $aSortedValidation = [];

        if ($sModuleId) {
            $aScanPluginNames[$sModuleId] = 'validator.admincp_settings_' . $sModuleId;
        }

        if ($sGroupId) {
            array_map(function ($row) use (&$aScanPluginNames) {
                $aScanPluginNames[$row['module_id']] = 'validator.admincp_settings_' . $row['module_id'];
            }, Phpfox::getLib('database')
                ->select('distinct(module_id)')
                ->from(':setting')
                ->where("group_id='{$sGroupId}'")
                ->execute('getSlaveRows'));
        }
        foreach ($aScanPluginNames as $sScanModuleId => $sScanPluginName) {
            $aValidation = [];
            (($sPlugin = Phpfox_Plugin::get($sScanPluginName)) ? eval($sPlugin) : false);
            $aSortedValidation [$sScanModuleId] = $aValidation;
        }

        // reset validation array
        $aValidation = [];
        $aExists = [];

        foreach ($aSettings as $aSetting) {
            $tempModuleId = $aSetting['module_id'];
            $tempVarName = $aSetting['var_name'];
            $aExists[$tempVarName] = 1;
            if (isset($aSortedValidation[$tempModuleId]) and isset($aSortedValidation[$tempModuleId][$tempVarName])) {
                $aValidation[$tempVarName] = $aSortedValidation[$tempModuleId][$tempVarName];
            }
        }

        $sPluginName = 'validator.admincp_settings' . ($sModuleId ? '_' . $sModuleId : '') . ($sGroupId ? '_group_' . $sGroupId : '');

        (($sPlugin = Phpfox_Plugin::get($sPluginName)) ? eval($sPlugin) : false);

        $aValidation = array_intersect_key($aValidation, $aExists);

        return $aValidation;
    }

    /**
     * Controller
     */
    public function process()
    {
        list($aGroups, $aModules, $aProductGroups) = Phpfox::getService('admincp.setting.group')->get();

        $aCond = [];
        $sSettingTitle = '';
        $bTestEmail = false;
        $aInvalid = [];
        $sModuleId = $this->request()->get('module-id');
        $sGroupId = $this->request()->get('group-id');
        $sGroupClass = $this->request()->get('group');
        $oDb = Phpfox::getLib('database');

        if ($this->request()->get('setting-id')) {
            $this->url()->send('admincp');
        }

        $sRealAppId = null;
        if (Phpfox::isAppAlias($sModuleId)) {
            $sRealAppId = Phpfox::getAppId($sModuleId);
            $App = Lib::appInit($sRealAppId);
            Phpfox::getService('admincp.setting.process')->importFromApp($App);
        } else if (Phpfox::isApps($sModuleId)) {
            $sRealAppId = $sModuleId;
            $App = Lib::appInit($sModuleId);
            Phpfox::getService('admincp.setting.process')->importFromApp($App);
        }
        if ($sRealAppId) {
            if (!Phpfox::isAppActive($sRealAppId)) {
                $this->url()->send('admincp.apps', [], _p('this_app_is_invalid'));
            }
        } elseif ($sModuleId && !Phpfox::isModule($sModuleId)) {
            $this->url()->send('admincp.apps', [], _p('this_app_is_invalid'));
        }

        if (!$sModuleId and !$sGroupId) {
            $this->url()->send('admincp');
        }

        if (($sSettingId = $this->request()->get('setting-id'))) {
            $aCond[] = " AND setting.setting_id = " . (int)$sSettingId;
        }

        if ($sGroupId) {
            $aCond[] = " AND setting.group_id = '" . $oDb->escape($sGroupId) . "' AND setting.is_hidden = 0 ";
            foreach ($aGroups as $aGroup) {
                if ($aGroup['group_id'] == $sGroupId) {
                    $sSettingTitle = $aGroup['var_name'];
                    break;
                }
            }
        }

        if (($iModuleId = $this->request()->get('module-id'))) {
            $aCond[] = " AND setting.module_id = '" . $oDb->escape($iModuleId) . "' AND setting.is_hidden = 0 ";
            foreach ($aModules as $aModule) {
                if ($aModule['module_id'] == $iModuleId) {
                    $sSettingTitle = $aModule['module_id'];
                    break;
                }
            }
        }

        if (($sProductId = $this->request()->get('product-id'))) {
            $aCond[] = " AND setting.product_id = '" . $oDb->escape($sProductId) . "' AND setting.is_hidden = 0 ";
            foreach ($aProductGroups as $aProduct) {
                if ($aProduct['product_id'] == $sProductId) {
                    $sSettingTitle = $aProduct['var_name'];
                    break;
                }
            }
        }

        $isValid = true;
        $oValidator = Phpfox::getLib('validator');
        $bGroupByApp = in_array($sGroupId,['seo', 'email']);

        (($sPlugin = Phpfox_Plugin::get('admincp.component_controller_setting_edit_start')) ? eval($sPlugin) : false);

        $aSettings = Phpfox::getService('admincp.setting')->get($aCond, $bGroupByApp);
        $aValidation = $this->scanValidation($sModuleId, $sGroupId, $aSettings);
        if (Phpfox::getLib('setting')->hasEnvParam('core.mail_from_name')) {
            unset($aValidation['mail_from_name']);
        }
        if (Phpfox::getLib('setting')->hasEnvParam('core.email_from_email')) {
            unset($aValidation['email_from_email']);
        }

        if ($aValidation) {
            $oValidator = $oValidator->set(['sFormName' => 'js_form', 'aParams' => $aValidation]);
        }
        $aVals = $this->request()->getArray('val');
        if ($sGroupId == 'mail' && $this->request()->get('test')) {
            $bTestEmail = true;
            if (isset($aVals['email_send_test']) && (!$aValidation || (!empty($aVals['value']) && $oValidator->isValid($aVals['value'])))) {
                if (filter_var($aVals['email_send_test'], FILTER_VALIDATE_EMAIL)) {
                    define('PHPFOX_MAIL_DEBUG', true);
                    //Save coolie test email
                    Phpfox::setCookie('email_send_test', $aVals['email_send_test']);
                    $oMail = Phpfox::getLib('mail')
                        ->to($aVals['email_send_test'])
                        ->fromEmail(Phpfox::getParam('core.email_from_email'))
                        ->fromName(Phpfox::getParam('core.mail_from_name'))
                        ->subject("Test setup email")
                        ->message("Congratulations, your configuration worked");

                    if (!Phpfox::getLib('setting')->hasEnvParam('core.method')) {
                        $oMail->test($aVals['value']);
                    }

                    if ($oMail->send(false, true)) {
                        $aVals = $this->request()->getArray('val');
                        if (!empty($aValidation)) {
                            $aIntValidate = array_filter($aValidation, function ($aValidate) {
                                return in_array($aValidate['def'], ['int', 'int:required']);
                            });
                            if (count($aIntValidate)) {
                                foreach ($aIntValidate as $sSetting => $aValidate) {
                                    $aVals['value'][$sSetting] = (int)$aVals['value'][$sSetting];
                                }
                            }
                        }
                        Phpfox::getService('admincp.setting.process')->update($aVals);
                        Phpfox::addMessage(_p("Email sent."));
                    } else {
                        Phpfox::addMessage(_p("Email can't send."));
                    }
                    $this->url()->send('admincp.setting.edit', ['group-id' => 'mail']);

                } else {
                    Phpfox_Error::set(_p("Not a valid test email address"));
                }
            }
        }

        if (!$bTestEmail && $aVals) {
            if ($aValidation && !empty($aVals['value']) && !$oValidator->isValid($aVals['value'])) {
                $aInvalid = $oValidator->getInvalidate();
                $isValid = false;

            } else {
                $oValidator->parseIntValues($aValidation, $aVals['value']);
                if (Phpfox::getService('admincp.setting.process')->update($aVals)) {
                    Phpfox::addMessage(_p('Your changes have been saved!'));
                    if ($sGroupClass == 'cron_job') {
                        $this->url()->send('admincp.app.settings', ['id' => 'PHPfox_Core', 'group' => 'cron_job']);
                    } else {
                        $bRedirect = false;
                        if (!empty($aVals['order'])) {
                            $bRedirect = array_key_exists('search_group_settings', $aVals['order']) || array_key_exists('reenter_email_on_signup', $aVals['order']);
                        }
                        if (!$bRedirect && !empty($aVals['value'])) {
                            $bRedirect = array_key_exists('search_group_settings', $aVals['value']) || array_key_exists('reenter_email_on_signup', $aVals['value']);
                        }
                        if ($bRedirect) {
                            $this->url()->send('current');
                        }
                    }
                }
            }
        }

        $aSettings = Phpfox::getService('admincp.setting')->get($aCond, $bGroupByApp);
        if ($sRealAppId) {
            $oApp = Core\Lib::app()->get($sRealAppId);
            $sSettingTitle = ($oApp && $oApp->name) ? $oApp->name : Phpfox_Locale::instance()->translate($sSettingTitle, 'module');
        }
        if (empty($sSettingTitle) && Phpfox::isModule($sSettingTitle)) {
            $oApp = Core\Lib::app()->get('__module_' . $sSettingTitle);
            $sSettingTitle = ($oApp && $oApp->name) ? $oApp->name : Phpfox_Locale::instance()->translate($sSettingTitle, 'module');
        }
        if (empty($sSettingTitle) && Phpfox::isApps($iModuleId)) {
            $oApp = Core\Lib::app()->get($iModuleId);
            $sSettingTitle = ($oApp && $oApp->name) ? $oApp->name : Phpfox_Locale::instance()->translate($sSettingTitle, 'module');
        }
        if ($sGroupClass) {
            foreach ($aSettings as $iKey => $aSetting) {
                $aGroupOptions = [
                    'pf_core_cache_driver'         => [
                        'group_class'  => 'core_cache_driver',
                        'option_class' => ''
                    ],
                    'pf_core_cache_redis_host'     => [
                        'group_class'  => 'core_cache_driver',
                        'option_class' => 'pf_core_cache_driver=redis'
                    ],
                    'pf_core_cache_redis_port'     => [
                        'group_class'  => 'core_cache_driver',
                        'option_class' => 'pf_core_cache_driver=redis'
                    ],
                    'pf_core_cache_redis_password' => [
                        'group_class'  => 'core_cache_driver',
                        'option_class' => 'pf_core_cache_driver=redis'
                    ],
                    'pf_core_cache_redis_database' => [
                        'group_class'  => 'core_cache_driver',
                        'option_class' => 'pf_core_cache_driver=redis'
                    ],
                    'pf_core_cache_memcached_host' => [
                        'group_class'  => 'core_cache_driver',
                        'option_class' => 'pf_core_cache_driver=memcached'
                    ],
                    'pf_core_cache_memcached_port' => [
                        'group_class'  => 'core_cache_driver',
                        'option_class' => 'pf_core_cache_driver=memcached'
                    ],
                    'pf_core_bundle_js_css'        => [
                        'group_class'  => 'core_bundle_js_css',
                        'option_class' => ''
                    ],
                    'pf_cron_task_token'           => [
                        'group_class'  => 'cron_job',
                        'option_class' => ''
                    ],
                    'pf_cron_task_url'             => [
                        'group_class'  => 'cron_job',
                        'option_class' => ''
                    ]
                ];
                if (array_key_exists($aSetting['var_name'], $aGroupOptions)) {
                    $aSettings[$iKey]['group_class'] = $aGroupOptions[$aSetting['var_name']]['group_class'];
                    $aSettings[$iKey]['option_class'] = $aGroupOptions[$aSetting['var_name']]['option_class'];
                }
                //plugin for 3rd would like to use this feature
                (($sPlugin = Phpfox_Plugin::get('admincp.component_controller_setting_group_class')) ? eval($sPlugin) : false);
            }
        }

        if (!$bTestEmail && isset($App) && $aVals && $isValid) {
            try {
                $settings = $aVals['value'];
                Core\Event::trigger('app_settings', $settings);
            } catch (\Exception $e) {
                return [
                    'error' => $e->getMessage()
                ];
            }

            Phpfox::addMessage(_p('Your changes have been saved!'));
        }
        $aDangerSettings = Phpfox::getService('admincp.setting')->getDangerSettings();
        $aSettingDependencies = !empty($sModuleId) && Phpfox::isModule($sModuleId) && Phpfox::hasCallback($sModuleId, 'getSettingDependencies') ? Phpfox::callback($sModuleId . '.getSettingDependencies') : [];

        foreach ($aSettings as $index => $aSetting) {
            if (isset($aInvalid[$aSetting['var_name']])) {
                $aSettings[$index]['error'] = $aInvalid[$aSetting['var_name']];
            }
            if (in_array($aSetting['module_id'] . '.' . $aSetting['var_name'], $aDangerSettings)) {
                $aSettings[$index]['is_danger'] = true;
            }
            $aSettings[$index]['is_file_config'] = Phpfox::hasEnvParam($aSetting['module_id'] . '.' . $aSetting['var_name']);
            if ($aSetting['var_name'] == 'pf_cron_task_url') {
                $aSettings[$index]['value_actual'] = str_replace("index.php/", "", Phpfox::getParam('core.path')) . 'cron.php?token=' . setting('pf_cron_task_token');
            }
            if (PHPFOX_USE_DATE_TIME && $aSetting['var_name'] == 'identify_dst') {
                unset($aSettings[$index]);
            }
            if ($aSetting['var_name'] == 'reenter_email_on_signup' && Phpfox::getParam('core.enable_register_with_phone_number')) {
                $aSettings[$index] = array_merge($aSettings[$index], [
                    'setting_title' => _p('force_users_to_reenter_email_or_phone_number'),
                    'setting_info' => _p('force_users_to_reenter_email_or_phone_number_description'),
                ]);
            }
            if (!empty($aSettingDependencies[$aSetting['var_name']])) {
                $aSettings[$index]['dependency'] = $aSettingDependencies[$aSetting['var_name']];
            }
        }

        $aAppGroupSettings = [];
        if ($bGroupByApp) {
            $aSearchSettings = [];
            foreach ($aSettings as $aSetting) {
                $aAppGroupSettings[$aSetting['module_id']]['settings'][] = $aSetting;
                $aAppGroupSettings[$aSetting['module_id']]['app_name'] = $aSetting['apps_name'];
                $aAppGroupSettings[$aSetting['module_id']]['product_id'] = $aSetting['product_id'];
                $aAppGroupSettings[$aSetting['module_id']]['app_active'] = $aSetting['product_id'] != 'phpfox' ? Phpfox::isAppActive($aSetting['product_id']) : Phpfox::isModule($aSetting['module_id']);
                $aSearchSettings[] = [
                    'module_id' => $aSetting['module_id'],
                    'title' => $aSetting['setting_title'],
                    'var_name' => $aSetting['var_name']
                ];
            }
            $this->template()->setHeader('<script>var admincpAppGroupSettings = ' . json_encode($aSearchSettings) . ';</script>');
            ksort($aAppGroupSettings);
        }

        if ($sGroupId) {
            $this->template()
                ->setActiveMenu('admincp.settings.' . $sGroupId);
        } else if ($sGroupClass) {
            $this->template()
                ->setActiveMenu('admincp.settings.' . $sGroupClass);
        } else if (!empty($sModuleId) && $sModuleId == 'user') {
            $this->template()
                ->setActiveMenu('admincp.member.settings');
        }

        $this->template()->setSectionTitle($sSettingTitle)
            ->setBreadCrumb(_p('settings'), '#')
            ->setTitle(_p('settings'))
            ->assign([
                'aGroups'           => $aGroups,
                'aModules'          => $aModules,
                'aProductGroups'    => $aProductGroups,
                'aSettings'         => $aSettings,
                'aAppGroupSettings' => $aAppGroupSettings,
                'sSettingTitle'     => $sSettingTitle,
                'sGroupId'          => $sGroupId,
                'sGroupClass'       => $sGroupClass,
                'bGroupByApp'       => $bGroupByApp,
                'admincp_help'      => isset($App) && !empty($App->admincp_help) ? $App->admincp_help : null
            ]);

        if ($sGroupId) {
            $n = _p('setting_group_label_' . $sGroupId);
            $this->template()->clearBreadCrumb()->setBreadCrumb($n);
        } else if ($sGroupClass) {
            $n = _p('setting_group_label_' . $sGroupClass);
            $this->template()->clearBreadCrumb()->setBreadCrumb($n);
        } else if ($sModuleId) {
            $sAppName = (!empty($App) && !empty($App->name)) ? $App->name : Phpfox::getLib('locale')->translate($sModuleId, 'module');
            $sAppId = (!empty($App) && !empty($App->id)) ? $App->id : '__module_' . $sModuleId;
            if (in_array($sAppId, ['__module_core', '__module_link', '__module_api', '__module_ban', '__module_error', '__module_log', '__module_profile', '__module_request', '__module_search'])) {
                $appUrl = $this->url()->current();
            } else {
                $appUrl = $this->url()->makeUrl('admincp.app', ['id' => $sAppId]);
            }
            $this->template()
                ->clearBreadCrumb()
                ->setBreadCrumb(_p('Apps'), $this->url()->makeUrl('admincp.apps'))
                ->setBreadCrumb($sAppName, $appUrl)
                ->setBreadCrumb(_p('Settings'));
        }
        (($sPlugin = Phpfox_Plugin::get('admincp.component_controller_setting_edit_process')) ? eval($sPlugin) : false);

        return null;
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('admincp.component_controller_setting_edit_clean')) ? eval($sPlugin) : false);
    }
}