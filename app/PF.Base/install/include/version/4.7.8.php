<?php
return function (Phpfox_Installer $Installer) {
    $installerDb = $Installer->db;

    // add mew components
    $installerDb->insert(':component', [
        'component' => 'gmap-block',
        'm_connection' => '',
        'module_id' => 'core',
        'product_id' => 'phpfox',
        'is_controller' => 0,
        'is_block' => 1,
        'is_active' => 1,
    ]);

    $aNewSettings = [
        [
            'group_id' => '',
            'module_id' => 'friend',
            'product_id' => 'phpfox',
            'is_hidden' => '0',
            'version_id' => '4.7.8',
            'type_id' => 'drop',
            'var_name' => 'friendship_direction',
            'phrase_var_name' => 'setting_friendship_direction',
            'value_actual' => 'a:2:{s:7:"default";s:19:"two_way_friendships";s:6:"values";a:2:{i:0;s:19:"two_way_friendships";i:1;s:19:"one_way_friendships";}}',
            'value_default' => 'a:2:{s:7:"default";s:19:"two_way_friendships";s:6:"values";a:2:{i:0;s:19:"two_way_friendships";i:1;s:19:"one_way_friendships";}}',
            'ordering' => '1'
        ],
        [
            'group_id' => '',
            'module_id' => 'friend',
            'product_id' => 'phpfox',
            'is_hidden' => '0',
            'version_id' => '4.7.8',
            'type_id' => 'boolean',
            'var_name' => 'friend_allow_posting_on_main_feed',
            'phrase_var_name' => 'setting_friend_allow_posting_on_main_feed',
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

    $aRemovePhrases = [
        ['var_name' => 'user_setting_can_sponsor_song'],
        ['var_name' => 'user_setting_can_sponsor_album'],
        ['var_name' => 'user_setting_can_purchase_sponsor_album'],
        ['var_name' => 'user_setting_photo_sponsor_price'],
    ];

    foreach ($aRemovePhrases as $aRemovePhrase) {
        $installerDb->delete(':language_phrase', $aRemovePhrase);
    }
};

