<?php
return function (Phpfox_Installer $Installer) {
    $installerDb = $Installer->db;

    if(!$installerDb->isField(Phpfox::getT('user_notification'), 'is_admin_default')) {
        $installerDb->addField([
            'table' => Phpfox::getT('user_notification'),
            'field' => 'is_admin_default',
            'type' => 'TINT:1',
            'null' => true,
            'default' => 0,
            'after' => 'notification_type'
        ]);
    }

    if(!$installerDb->isField(Phpfox::getT('user_spam'), 'is_active')) {
        $installerDb->addField([
            'table' => Phpfox::getT('user_spam'),
            'field' => 'is_active',
            'type' => 'TINT:1',
            'null' => true,
            'default' => 1,
            'after' => 'case_sensitive'
        ]);
    }

    $aNewSettings = [
        [
            'group_id' => 'registration',
            'module_id' => 'user',
            'product_id' => 'phpfox',
            'is_hidden' => 0,
            'version_id' => '4.8.3',
            'type_id' => 'integer',
            'var_name' => 'on_register_user_group',
            'phrase_var_name' => 'setting_on_register_user_group',
            'value_actual' => NORMAL_USER_ID,
            'value_default' => NORMAL_USER_ID,
            'ordering' => 1
        ],
        [
            'group_id' => 'site_offline_online',
            'module_id' => 'core',
            'product_id' => 'phpfox',
            'is_hidden' => 0,
            'version_id' => '4.8.3',
            'type_id' => 'integer',
            'var_name' => 'site_offline_static_page',
            'phrase_var_name' => 'setting_site_offline_static_page',
            'value_actual' => 0,
            'value_default' => 0,
            'ordering' => 3
        ],
        [
            'group_id' => 'general',
            'module_id' => 'core',
            'product_id' => 'phpfox',
            'is_hidden' => 0,
            'version_id' => '4.8.3',
            'type_id' => 'boolean',
            'var_name' => 'use_popup_on_signup_login_button',
            'phrase_var_name' => 'setting_use_popup_on_signup_login_button',
            'value_actual' => 1,
            'value_default' => 1,
            'ordering' => 16
        ],
        [
            'group_id' => 'time_stamps',
            'module_id' => 'core',
            'product_id' => 'phpfox',
            'is_hidden' => 0,
            'version_id' => '4.8.3',
            'type_id' => 'select',
            'var_name' => 'pf_time_format',
            'phrase_var_name' => 'setting_core_pf_time_format',
            'value_actual' => 2,
            'value_default' => serialize([
                'default' => 2,
                'values'  => [
                    '1' => '12-hour format',
                    '2' => '24-hour format',
                ],
            ]),
            'ordering' => '3'
        ],
    ];
    foreach ($aNewSettings as $aNewSetting) {
        $checkSetting = $installerDb->select('setting_id')->from(':setting')->where(['var_name' => $aNewSetting['var_name'], 'module_id' => $aNewSetting['module_id']])->executeRow();
        if (!$checkSetting) {
            $installerDb->insert(':setting', $aNewSetting);
        }
    }
    //Update setting description
    $oLanguageProcess = Phpfox::getService('language.phrase.process');

    $oLanguageProcess->update('setting_facebook_app_id', "<title>Facebook Application ID</title><info>Provide the Facebook Application ID for your Facebook application.<br/>This setting and Facebook Application Secret use to get information when user share a Facebook link or video. Please make sure <b>oEmbed</b> and <b>Instagram Graph API</b> are enabled in your app.</info>");
    $oLanguageProcess->update('setting_link_facebook_app_id', "<title>Facebook App ID</title><info>Provide the Facebook App ID for your Facebook application.<br/>This setting and Facebook App Secret use to get information when user share a Facebook link or video. Please make sure <b>oEmbed</b> and <b>Instagram Graph API</b> are enabled in your app.</info>");
};

