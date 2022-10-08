<?php
return function (Phpfox_Installer $Installer) {
    $installerDb = $Installer->db;
    // add new settings
    $aNewSettings = [
        [
            'group_id' => 'general',
            'module_id' => 'core',
            'product_id' => 'phpfox',
            'is_hidden' => '0',
            'version_id' => '4.7.0',
            'type_id' => 'drop',
            'var_name' => 'start_of_week',
            'phrase_var_name' => 'setting_start_of_week',
            'value_actual' => 'a:2:{s:7:"default";s:3:"mon";s:6:"values";a:7:{i:0;s:3:"sun";i:1;s:3:"mon";i:2;s:3:"tue";i:3;s:3:"wed";i:4;s:3:"thu";i:5;s:3:"fri";i:6;s:3:"sat";}}',
            'value_default' => 'a:2:{s:7:"default";s:3:"mon";s:6:"values";a:7:{i:0;s:3:"sun";i:1;s:3:"mon";i:2;s:3:"tue";i:3;s:3:"wed";i:4;s:3:"thu";i:5;s:3:"fri";i:6;s:3:"sat";}}',
            'ordering' => '18',
        ],
        [
            'group_id' => 'general',
            'module_id' => 'core',
            'product_id' => 'phpfox',
            'is_hidden' => '0',
            'version_id' => '4.7.0',
            'type_id' => 'integer',
            'var_name' => 'min_character_to_search',
            'phrase_var_name' => 'setting_min_character_to_search',
            'value_actual' => '2',
            'value_default' => '2',
            'ordering' => '19',
        ],
        [
            'group_id' => 'general',
            'module_id' => 'core',
            'product_id' => 'phpfox',
            'is_hidden' => '0',
            'version_id' => '4.7.0',
            'type_id' => 'integer',
            'var_name' => 'no_pages_for_scroll_down',
            'phrase_var_name' => 'setting_no_pages_for_scroll_down',
            'value_actual' => '2',
            'value_default' => '2',
            'ordering' => '2',
        ]
    ];

    foreach ($aNewSettings as $aNewSetting) {
        $installerDb->insert(':setting', $aNewSetting);
    }

    // update type for password field
    $installerDb->update(':setting', [
        'type_id' => 'password'
    ], [
        'var_name' => 'ftp_password',
        'module_id' => 'core'
    ]);

    // remove settings
    $aRemoveSettings = [
        [
            'var_name' => 'no_show_activity_points',
            'module_id' => 'user'
        ],
        [
            'var_name' => 'can_purchase_activity_points',
            'module_id' => 'user'
        ],
        [
            'var_name' => 'points_conversion_rate',
            'module_id' => 'user'
        ],
        [
            'var_name' => 'can_purchase_with_points',
            'module_id' => 'user'
        ],
    ];

    foreach ($aRemoveSettings as $aRemoveSetting) {
        $installerDb->delete(':setting', [
            'var_name' => $aRemoveSetting['var_name'],
            'module_id' => $aRemoveSetting['module_id']
        ]);
    }

    // remove user group settings
    $aDeleteUserGroupSettings = [
        [
            'module_id' => 'core',
            'name' => 'can_gift_points'
        ],
    ];

    foreach ($aDeleteUserGroupSettings as $aDeleteUserGroupSetting) {
        $installerDb->delete(':user_group_setting', [
            'module_id' => $aDeleteUserGroupSetting['module_id'],
            'name' => $aDeleteUserGroupSetting['name']
        ]);
    }

    $aRemovePhrases = [
        ['var_name' => 'large_text_area'],
        ['var_name' => 'small_text_area_255_characters_max'],
        ['var_name' => 'manage_activity_points'],
    ];

    foreach ($aRemovePhrases as $aRemovePhrase) {
        $installerDb->delete(':language_phrase', $aRemovePhrase);
    }

    // add new cron to remove user_ip
    $iCnt = db()->select('COUNT(*)')
        ->from(':cron')
        ->where('module_id = "user" AND php_code = "Phpfox::getService(\'user.auth\')->clearUserIp();"')
        ->execute('getField');
    if (!$iCnt) {
        db()->insert(':cron',[
            'module_id' => 'user',
            'product_id' => 'phpfox',
            'type_id' => 2,
            'every' => 1,
            'is_active' => 1,
            'php_code' => 'Phpfox::getService(\'user.auth\')->clearUserIp();'
        ]);
    }

    $tableName = Phpfox::getT('report');
    if(!$installerDb->isField($tableName, 'ordering')) {
        $installerDb->query("ALTER TABLE `" . $tableName . "` ADD `ordering` INT(11) NOT NULL DEFAULT '99';");
    }

    $tableName = Phpfox::getT('admincp_dashboard');
    if($installerDb->tableExists($tableName)) {
        $installerDb->dropTable($tableName);
    }

    $tableName = Phpfox::getT('feed');
    if($installerDb->tableExists($tableName) && !$installerDb->isIndex($tableName, 'parent_module_id_parent_feed_id')) {
        $installerDb->addIndex($tableName, '`parent_module_id`, `parent_feed_id`', 'parent_module_id_parent_feed_id');
    }

    $tableName = Phpfox::getT('announcement_hide');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->updatePrimaryKeys($tableName, ['announcement_id', 'user_id']);
        if($installerDb->isIndex($tableName, 'announcement_id')) {
            $installerDb->dropIndex($tableName, 'announcement_id');
        }
    }

    $tableName = Phpfox::getT('api_gateway');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->addPrimaryKey($tableName, 'gateway_id');
        if($installerDb->isIndex($tableName, 'gateway_id')) {
            $installerDb->dropIndex($tableName, 'gateway_id');
        }
    }

    $tableName = Phpfox::getT('attachment_type');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->addPrimaryKey($tableName, 'extension');
        if($installerDb->isIndex($tableName, 'extension')) {
            $installerDb->dropIndex($tableName, 'extension');
        }
    }

    $tableName = Phpfox::getT('block_source');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->addPrimaryKey($tableName, 'block_id');
        if($installerDb->isIndex($tableName, 'block_id')) {
            $installerDb->dropIndex($tableName, 'block_id');
        }
    }

    $tableName = Phpfox::getT('blog_category_data');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->updatePrimaryKeys($tableName, ['blog_id', 'category_id']);
    }

    $tableName = Phpfox::getT('comment_hash');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->updatePrimaryKeys($tableName, ['user_id', 'item_hash', 'time_stamp']);
        if($installerDb->isIndex($tableName, 'user_id')) {
            $installerDb->dropIndex($tableName, 'user_id');
        }
    }

    $tableName = Phpfox::getT('comment_rating');
    if($installerDb->tableExists($tableName) && !$installerDb->isField($tableName, 'rating_id')) {
        $installerDb->query("ALTER TABLE `" . $tableName . "` ADD `rating_id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;");
    }

    $tableName = Phpfox::getT('comment_text');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->addPrimaryKey($tableName, 'comment_id');
        if($installerDb->isIndex($tableName, 'comment_id')) {
            $installerDb->dropIndex($tableName, 'comment_id');
        }
    }

    $tableName = Phpfox::getT('component_setting');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->updatePrimaryKeys($tableName, ['user_id', 'var_name']);
        if($installerDb->isIndex($tableName, 'user_id_2')) {
            $installerDb->dropIndex($tableName, 'user_id_2');
        }
    }

    $tableName = Phpfox::getT('version');
    if($installerDb->tableExists($tableName) && !$installerDb->isField($tableName, 'version_key')) {
        $installerDb->query("ALTER TABLE `" . $tableName . "` ADD `version_key` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;");
        $installerDb->query("DELETE t1 FROM `" . $tableName . "` t1
            INNER JOIN `" . $tableName . "` t2 
            WHERE t1.version_key > t2.version_key AND t1.version_id = t2.version_id;");
    }

    $tableName = Phpfox::getT('event_category_data');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->updatePrimaryKeys($tableName, ['event_id', 'category_id']);
    }

    $tableName = Phpfox::getT('event_text');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->addPrimaryKey($tableName, 'event_id');
        if($installerDb->isIndex($tableName, 'event_id')) {
            $installerDb->dropIndex($tableName, 'event_id');
        }
    }

    $tableName = Phpfox::getT('forum_moderator_access');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->updatePrimaryKeys($tableName, ['moderator_id', 'var_name']);
    }

    $tableName = Phpfox::getT('forum_post_text');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->addPrimaryKey($tableName, 'post_id');
        if($installerDb->isIndex($tableName, 'post_id')) {
            $installerDb->dropIndex($tableName, 'post_id');
        }
    }

    $tableName = Phpfox::getT('like_cache');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->query("ALTER TABLE `" . $tableName . "` ADD `cache_id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;");
    }

    $tableName = Phpfox::getT('log_session');
    if($installerDb->tableExists($tableName) && !$installerDb->isField($tableName, 'session_id')) {
        $installerDb->query("ALTER TABLE `" . $tableName . "` ADD `session_id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;");
    }

    $tableName = Phpfox::getT('session');
    if($installerDb->tableExists($tableName) && !$installerDb->isField($tableName, 'session_id')) {
        $installerDb->query("ALTER TABLE `" . $tableName . "` ADD `session_id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;");
    }

    $tableName = Phpfox::getT('mail_thread_user');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->updatePrimaryKeys($tableName, ['thread_id', 'user_id']);
        if($installerDb->isIndex($tableName, 'thread_id')) {
            $installerDb->dropIndex($tableName, 'thread_id');
        }
    }

    $tableName = Phpfox::getT('marketplace_category_data');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->updatePrimaryKeys($tableName, ['listing_id', 'category_id']);
    }

    $tableName = Phpfox::getT('marketplace_text');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->addPrimaryKey($tableName, 'listing_id');
        if($installerDb->isIndex($tableName, 'listing_id')) {
            $installerDb->dropIndex($tableName, 'listing_id');
        }
    }

    $tableName = Phpfox::getT('photo_album_info');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->addPrimaryKey($tableName, 'album_id');
    }

    $tableName = Phpfox::getT('photo_category_data');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->updatePrimaryKeys($tableName, ['photo_id', 'category_id']);
    }

    $tableName = Phpfox::getT('photo_feed');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->updatePrimaryKeys($tableName, ['feed_id', 'photo_id']);
    }

    $tableName = Phpfox::getT('photo_info');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->addPrimaryKey($tableName, 'photo_id');
    }

    $tableName = Phpfox::getT('video_category_data');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->updatePrimaryKeys($tableName, ['video_id', 'category_id']);
    }

    $tableName = Phpfox::getT('video_embed');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->addPrimaryKey($tableName, 'video_id');
        if($installerDb->isIndex($tableName, 'video_id')) {
            $installerDb->dropIndex($tableName, 'video_id');
        }
    }

    $tableName = Phpfox::getT('video_text');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->addPrimaryKey($tableName, 'video_id');
    }

    $tableName = Phpfox::getT('module');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->addPrimaryKey($tableName, 'module_id');
        if($installerDb->isIndex($tableName, 'module_id')) {
            $installerDb->dropIndex($tableName, 'module_id');
        }
    }

    $tableName = Phpfox::getT('music_album_text');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->addPrimaryKey($tableName, 'album_id');
        if($installerDb->isIndex($tableName, 'album_id')) {
            $installerDb->dropIndex($tableName, 'album_id');
        }
    }

    $tableName = Phpfox::getT('music_genre_data');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->updatePrimaryKeys($tableName, ['song_id', 'genre_id']);
    }

    $tableName = Phpfox::getT('music_feed');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->updatePrimaryKeys($tableName, ['feed_id', 'song_id']);
    }

    $tableName = Phpfox::getT('music_genre_user');
    if($installerDb->tableExists($tableName)) {
        $installerDb->dropTable($tableName);
    }

    $tableName = Phpfox::getT('page_log');
    if($installerDb->tableExists($tableName) && !$installerDb->isField($tableName, 'log_id')) {
        $installerDb->query("ALTER TABLE `" . $tableName . "` ADD `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;");
    }

    $tableName = Phpfox::getT('pages_text');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->addPrimaryKey($tableName, 'page_id');
        if($installerDb->isIndex($tableName, 'page_id')) {
            $installerDb->dropIndex($tableName, 'page_id');
        }
    }

    $tableName = Phpfox::getT('pages_widget_text');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->addPrimaryKey($tableName, 'widget_id');
        if($installerDb->isIndex($tableName, 'widget_id')) {
            $installerDb->dropIndex($tableName, 'widget_id');
        }
    }

    $tableName = Phpfox::getT('pages_admin');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->updatePrimaryKeys($tableName, ['page_id', 'user_id']);
        if($installerDb->isIndex($tableName, 'page_id_2')) {
            $installerDb->dropIndex($tableName, 'page_id_2');
        }
    }

    $tableName = Phpfox::getT('pages_perm');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->updatePrimaryKeys($tableName, ['page_id', 'var_name']);
    }

    $tableName = Phpfox::getT('pages_url');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->addPrimaryKey($tableName, 'page_id');
        if($installerDb->isIndex($tableName, 'page_id')) {
            $installerDb->dropIndex($tableName, 'page_id');
        }
    }

    $tableName = Phpfox::getT('page_text');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->addPrimaryKey($tableName, 'page_id');
        if($installerDb->isIndex($tableName, 'page_id')) {
            $installerDb->dropIndex($tableName, 'page_id');
        }
    }

    $tableName = Phpfox::getT('user_css');
    if($installerDb->tableExists($tableName) && !$installerDb->isField($tableName, 'css_id')) {
        $installerDb->query("ALTER TABLE `" . $tableName . "` ADD `css_id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;");
    }

    $tableName = Phpfox::getT('user_snoop');
    if($installerDb->tableExists($tableName) && !$installerDb->isField($tableName, 'snoop_id')) {
        $installerDb->query("ALTER TABLE `" . $tableName . "` ADD `snoop_id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;");
    }

    $tableName = Phpfox::getT('user_notification');
    if($installerDb->tableExists($tableName) && !$installerDb->isField($tableName, 'notification_id')) {
        $installerDb->query("ALTER TABLE `" . $tableName . "` ADD `notification_id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;");
    }

    $tableName = Phpfox::getT('user_dashboard');
    if($installerDb->tableExists($tableName) && !$installerDb->isField($tableName, 'dashboard_id')) {
        $installerDb->query("ALTER TABLE `" . $tableName . "` ADD `dashboard_id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;");
    }

    $tableName = Phpfox::getT('user_design_order');
    if($installerDb->tableExists($tableName) && !$installerDb->isField($tableName, 'design_order_id')) {
        $installerDb->query("ALTER TABLE `" . $tableName . "` ADD `design_order_id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;");
    }

    $tableName = Phpfox::getT('user_custom_multiple_value');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->updatePrimaryKeys($tableName, ['user_id', 'field_id', 'option_id']);
    }

    $tableName = Phpfox::getT('user_setting');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->updatePrimaryKeys($tableName, ['user_group_id', 'setting_id']);
        if($installerDb->isIndex($tableName, 'user_group_id')) {
            $installerDb->dropIndex($tableName, 'user_group_id');
        }
    }

    $tableName = Phpfox::getT('user_privacy');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->updatePrimaryKeys($tableName, ['user_id', 'user_privacy']);
        if($installerDb->isIndex($tableName, 'user_id')) {
            $installerDb->dropIndex($tableName, 'user_id');
        }
    }

    $tableName = Phpfox::getT('user_gateway');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->updatePrimaryKeys($tableName, ['user_id', 'gateway_id']);
    }

    $tableName = Phpfox::getT('upload_track');
    if($installerDb->tableExists($tableName) && !$installerDb->isField($tableName, 'track_id')) {
        $installerDb->query("ALTER TABLE `" . $tableName . "` ADD `track_id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;");
    }

    $tableName = Phpfox::getT('setting_group');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->addPrimaryKey($tableName, 'group_id');
        if($installerDb->isIndex($tableName, 'group_id')) {
            $installerDb->dropIndex($tableName, 'group_id');
        }
    }

    $tableName = Phpfox::getT('quiz_result');
    if($installerDb->tableExists($tableName) && !$installerDb->isField($tableName, 'result_id')) {
        $installerDb->query("ALTER TABLE `" . $tableName . "` ADD `result_id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;");
    }

    $tableName = Phpfox::getT('poll_result');
    if($installerDb->tableExists($tableName) && !$installerDb->isField($tableName, 'result_id')) {
        $installerDb->query("ALTER TABLE `" . $tableName . "` ADD `result_id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;");
    }

    $tableName = Phpfox::getT('product');
    if($installerDb->tableExists($tableName) && empty($installerDb->getPrimaryKeyColumns($tableName))) {
        $installerDb->addPrimaryKey($tableName, 'product_id');
        if($installerDb->isIndex($tableName, 'product_id')) {
            $installerDb->dropIndex($tableName, 'product_id');
        }
    }

    $tableName = Phpfox::getT('password_request');
    if($installerDb->tableExists($tableName) && !$installerDb->isField($tableName, 'password_request_id')) {
        $installerDb->query("ALTER TABLE `" . $tableName . "` ADD `password_request_id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;");
    }

    $tableName = Phpfox::getT('oauth_public_keys');
    if($installerDb->tableExists($tableName) && !$installerDb->isField($tableName, 'keys_id')) {
        $installerDb->query("ALTER TABLE `" . $tableName . "` ADD `keys_id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;");
    }

    $tableName = Phpfox::getT('oauth_jti');
    if($installerDb->tableExists($tableName) && !$installerDb->isField($tableName, 'jti_id')) {
        $installerDb->query("ALTER TABLE `" . $tableName . "` ADD `jti_id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;");
    }

    $tableName = Phpfox::getT('oauth_jwt');
    if($installerDb->tableExists($tableName) && !$installerDb->isField($tableName, 'jwt_id')) {
        $installerDb->query("ALTER TABLE `" . $tableName . "` ADD `jwt_id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;");
    }
};
