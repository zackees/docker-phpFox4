<?php
return function (Phpfox_Installer $Installer) {
    $installerDb = $Installer->db;

    $aNewSettings = [
        [
            'group_id' => 'registration',
            'module_id' => 'core',
            'product_id' => 'phpfox',
            'is_hidden' => 0,
            'version_id' => '4.8.2',
            'type_id' => 'boolean',
            'var_name' => 'enable_register_with_phone_number',
            'phrase_var_name' => 'setting_enable_register_with_phone_number',
            'value_actual' => '0',
            'value_default' => '0',
            'ordering' => '1'
        ],
        [
            'group_id' => '',
            'module_id' => 'user',
            'product_id' => 'phpfox',
            'is_hidden' => 0,
            'version_id' => '4.8.2',
            'type_id' => 'boolean',
            'var_name' => 'logout_after_change_phone_number',
            'phrase_var_name' => 'setting_logout_after_change_phone_number',
            'value_actual' => '1',
            'value_default' => '1',
            'ordering' => '1'
        ],
        [
            'group_id'        => null,
            'module_id'       => 'link',
            'product_id'      => 'phpfox',
            'is_hidden'       => '0',
            'version_id'      => '4.8.2',
            'type_id'         => 'string',
            'var_name'        => 'facebook_app_id',
            'phrase_var_name' => 'setting_link_facebook_app_id',
            'value_actual'    => '',
            'value_default'   => '',
            'ordering'        => '99',
        ],
        [
            'group_id'        => null,
            'module_id'       => 'link',
            'product_id'      => 'phpfox',
            'is_hidden'       => '0',
            'version_id'      => '4.8.2',
            'type_id'         => 'password',
            'var_name'        => 'facebook_app_secret',
            'phrase_var_name' => 'setting_link_facebook_app_secret',
            'value_actual'    => '',
            'value_default'   => '',
            'ordering'        => '100',
        ]
    ];
    foreach ($aNewSettings as $aNewSetting) {
        $checkSetting = $installerDb->select('setting_id')->from(':setting')->where(['var_name' => $aNewSetting['var_name'], 'module_id' => $aNewSetting['module_id']])->executeRow();
        if (!$checkSetting) {
            $installerDb->insert(':setting', $aNewSetting);
        }
    }

    if(!$installerDb->isField(Phpfox::getT('user'), 'phone_number')) {
        $installerDb->addField([
            'table' => Phpfox::getT('user'),
            'field' => 'phone_number',
            'type' => 'VCHAR:50',
            'null' => true,
            'after' => 'email'
        ]);
    }
    if(!$installerDb->isField(Phpfox::getT('user'), 'full_phone_number')) {
        $installerDb->addField([
            'table' => Phpfox::getT('user'),
            'field' => 'full_phone_number',
            'type' => 'VCHAR:50',
            'null' => true,
            'after' => 'email'
        ]);
    }
    if(!$installerDb->isField(Phpfox::getT('password_request'), 'request_type')) {
        $installerDb->addField([
            'table' => Phpfox::getT('password_request'),
            'field' => 'request_type',
            'type' => 'VCHAR:100',
            'null' => true,
            'default' => '\'email\'',
            'after' => 'request_id'
        ]);
    }
    if(!$installerDb->isField(Phpfox::getT('user_notification'), 'notification_type')) {
        $installerDb->addField([
            'table' => Phpfox::getT('user_notification'),
            'field' => 'notification_type',
            'type' => 'VCHAR:50',
            'null' => true,
            'default' => '\'email\'',
            'after' => 'user_notification'
        ]);
    }
};

