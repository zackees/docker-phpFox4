<?php
return function (Phpfox_Installer $Installer) {
    $installerDb = $Installer->db;

    // add new settings
    $aNewSettings = [
        [
            'group_id' => 'general',
            'module_id' => 'core',
            'product_id' => 'phpfox',
            'is_hidden' => '1',
            'version_id' => '3.0.0Beta1',
            'type_id' => 'boolean',
            'var_name' => 'section_privacy_item_browsing',
            'phrase_var_name' => 'setting_section_privacy_item_browsing',
            'value_actual' => '1',
            'value_default' => '1',
            'ordering' => '9'
        ],
        [
            'group_id' => '',
            'module_id' => 'core',
            'product_id' => 'phpfox',
            'is_hidden' => '1',
            'version_id' => '2.0.0rc1',
            'type_id' => 'integer',
            'var_name' => 'banned_user_group_id',
            'phrase_var_name' => 'setting_banned_user_group_id',
            'value_actual' => '0',
            'value_default' => '0',
            'ordering' => '1'
        ],
        [
            'group_id' => '',
            'module_id' => 'core',
            'product_id' => 'phpfox',
            'is_hidden' => '1',
            'version_id' => '2.0.0alpha1',
            'type_id' => 'string',
            'var_name' => 'default_lang_id',
            'phrase_var_name' => 'setting_default_lang_id',
            'value_actual' => 'en',
            'value_default' => 'en',
            'ordering' => '0'
        ],
        [
            'group_id' => '',
            'module_id' => 'core',
            'product_id' => 'phpfox',
            'is_hidden' => '1',
            'version_id' => '2.0.0rc1',
            'type_id' => 'large_string',
            'var_name' => 'global_admincp_note',
            'phrase_var_name' => 'setting_global_admincp_note',
            'value_actual' => '',
            'value_default' => '',
            'ordering' => '1'
        ],
        [
            'group_id' => '',
            'module_id' => 'core',
            'product_id' => 'phpfox',
            'is_hidden' => '1',
            'version_id' => '2.0',
            'type_id' => 'integer',
            'var_name' => 'css_edit_id',
            'phrase_var_name' => 'setting_css_edit_id',
            'value_actual' => '1',
            'value_default' => '1',
            'ordering' => '1'
        ],
        [
            'group_id' => '',
            'module_id' => 'comment',
            'product_id' => 'phpfox',
            'is_hidden' => '1',
            'version_id' => '4.6.0',
            'type_id' => 'boolean',
            'var_name' => 'newest_comment_on_top',
            'phrase_var_name' => 'setting_newest_comment_on_top',
            'value_actual' => '0',
            'value_default' => '0',
            'ordering' => '98'
        ]
    ];

    foreach ($aNewSettings as $aNewSetting) {
        $checkSetting = $installerDb->select('setting_id')->from(':setting')->where(['var_name' => $aNewSetting['var_name'], 'module_id' => $aNewSetting['module_id']])->executeRow();
        if(!$checkSetting) {
            $installerDb->insert(':setting', $aNewSetting);
        }
    }

    // add new user group settings
    $aNewUserGroupSettings = [
        'can_add_custom_gender' => [
            'module' => 'user',
            'name' => 'can_add_custom_gender',
            'user_group' => [
                '1' => 1,
                '2' => 1,
                '3' => 1,
                '4' => 1,
                '5' => 1
            ],
            'options' => [
                "yes" => "Yes",
                "no"  => "No",
            ],
            'product_id' => 'phpfox',
            'type' => 'boolean',
            'text' => 'Can add custom gender?'
        ],
    ];

    $aLanguages = Phpfox::getService('language')->getAll();
    foreach($aNewUserGroupSettings as $aSetting)
    {
        $aText = [];
        foreach ($aLanguages as $aLanguage) {
            $aText[$aLanguage['language_code']] = $aSetting['text'];
        }
        $aSetting['text'] = $aText;
        Phpfox::getService('user.group.setting.process')->addSetting($aSetting);
    }
};
