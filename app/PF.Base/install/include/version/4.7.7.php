<?php
return function (Phpfox_Installer $Installer) {
    $installerDb = $Installer->db;

    // add new settings
    $aNewSettings = [
        [
            'group_id' => '',
            'module_id' => 'feed',
            'product_id' => 'phpfox',
            'is_hidden' => '0',
            'version_id' => '4.7.7',
            'type_id' => 'boolean',
            'var_name' => 'enable_hide_feed',
            'phrase_var_name' => 'setting_enable_hide_feed',
            'value_actual' => '1',
            'value_default' => '1',
            'ordering' => '1'
        ]
    ];

    foreach ($aNewSettings as $aNewSetting) {
        $checkSetting = $installerDb->select('setting_id')->from(':setting')->where(['var_name' => $aNewSetting['var_name'], 'module_id' => $aNewSetting['module_id']])->executeRow();
        if (!$checkSetting) {
            $installerDb->insert(':setting', $aNewSetting);
        }
    }
};

