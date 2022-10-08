<?php
return function (Phpfox_Installer $Installer) {
    $installerDb = $Installer->db;

    $aNewSettings = [
        'resend_verification_email_delay_time' => [
            'group_id' => '',
            'module_id' => 'user',
            'product_id' => 'phpfox',
            'is_hidden' => '0',
            'version_id' => '4.7.3',
            'type_id' => 'integer',
            'var_name' => 'resend_verification_email_delay_time',
            'phrase_var_name' => 'setting_resend_verification_email_delay_time',
            'value_actual' => '15',
            'value_default' => '15',
            'ordering' => '21',
        ]
    ];

    foreach ($aNewSettings as $aNewSetting) {
        $installerDb->insert(':setting', $aNewSetting);
    }
};
