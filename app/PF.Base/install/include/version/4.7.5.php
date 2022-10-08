<?php
return function (Phpfox_Installer $Installer) {
    $installerDb = $Installer->db;

    // add new settings
    $aNewSettings = [
        [
            'group_id' => '',
            'module_id' => 'core',
            'product_id' => 'phpfox',
            'is_hidden' => '1',
            'version_id' => '3.0.0Beta1',
            'type_id' => 'boolean',
            'var_name' => 'allow_cdn',
            'phrase_var_name' => 'setting_allow_cdn', // will removed in 4.8.0
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

    // replace user name from . to - (happen when import users from Wordpress)
    $installerDb->query("UPDATE `" . Phpfox::getT('user') . "` SET `user_name` = REPLACE(`user_name`, '.', '-') WHERE INSTR(`user_name`, '.') > 0;");

    // update like type id from user_photo to photo and remove duplicated like
    $installerDb->update(':like', ['type_id' => 'photo'], ['type_id' => 'user_photo']);
    $installerDb->update(':like_cache', ['type_id' => 'photo'], ['type_id' => 'user_photo']);

    // remove duplicated on like table
    $tableName = Phpfox::getT('like');
    $query = "DELETE l1 FROM `" . $tableName . "` l1 INNER JOIN `" . $tableName . "` l2 WHERE l1.like_id < l2.like_id AND l1.type_id = l2.type_id AND l1.item_id = l2.item_id AND l1.user_id = l2.user_id AND l1.feed_table = l2.feed_table;";
    $installerDb->query($query);

    // remove duplicated on like cache table
    $tableName = Phpfox::getT('like_cache');
    $query = "DELETE l1 FROM `" . $tableName . "` l1 INNER JOIN `" . $tableName . "` l2 WHERE l1.cache_id < l2.cache_id AND l1.type_id = l2.type_id AND l1.item_id = l2.item_id AND l1.user_id = l2.user_id;";
    $installerDb->query($query);
};

