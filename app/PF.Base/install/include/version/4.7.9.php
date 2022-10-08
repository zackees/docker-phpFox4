<?php
return function (Phpfox_Installer $Installer) {
    $installerDb = $Installer->db;

    $aNewSettings = [
        [
            'group_id' => '',
            'module_id' => 'core',
            'product_id' => 'phpfox',
            'is_hidden' => '0',
            'version_id' => '4.7.9',
            'type_id' => 'string',
            'var_name' => 'map_view_default_zoom',
            'phrase_var_name' => 'setting_map_view_default_zoom',
            'value_actual' => '15',
            'value_default' => '15',
            'ordering' => '1'
        ],
    ];

    foreach ($aNewSettings as $aNewSetting) {
        $checkSetting = $installerDb->select('setting_id')->from(':setting')->where(['var_name' => $aNewSetting['var_name'], 'module_id' => $aNewSetting['module_id']])->executeRow();
        if (!$checkSetting) {
            $installerDb->insert(':setting', $aNewSetting);
        }
    }
};

