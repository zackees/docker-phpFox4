<?php
return function (Phpfox_Installer $Installer) {
    $installerDb = $Installer->db;

    // remove settings
    $aRemoveSettings = [
        [
            'var_name' => 'td_feature_price',
            'module_id' => 'todo'
        ],
        [
            'var_name' => 'td_can_user_privacy',
            'module_id' => 'todo'
        ],
    ];

    foreach ($aRemoveSettings as $aRemoveSetting) {
        $installerDb->delete(':setting', [
            'var_name' => $aRemoveSetting['var_name'],
            'module_id' => $aRemoveSetting['module_id']
        ]);
    }

    // uninstall TodoList app
    $installerDb->delete(':module', 'module_id = \'todo\'');
    $installerDb->delete(':apps', 'apps_id = \'TodoList\'');
    $installerDb->delete(':menu', 'module_id = \'todo\'');

    //remove advanced search block in page user.browse
    $iBlockId = $installerDb->select('block_id')
        ->from(':block')
        ->where('m_connection="user.browse" AND module_id="user" AND component="filter"')
        ->execute('getSlaveField');
    if((int)$iBlockId > 0)
    {
        $installerDb->delete(':block', 'block_id = ' . (int)$iBlockId);
        $installerDb->delete(':block_source', 'block_id = ' . (int)$iBlockId);
    }

    //add field 'custom_gender' for table user
    if (!$installerDb->isField(Phpfox::getT('user'), 'custom_gender')) {
        $installerDb->addField([
            'table' => Phpfox::getT('user'),
            'field' => 'custom_gender',
            'type'  => 'MEDIUMTEXT',
            'null'  => true
        ]);
    }

    // Move Welcome and SignUp blocks to location 2
    $installerDb->update(':block', ['location' => 2, 'ordering' => 1], ['m_connection' => 'core.index-visitor', 'component' => 'welcome']);
    $installerDb->update(':block', ['location' => 2, 'ordering' => 2], ['m_connection' => 'core.index-visitor', 'component' => 'register']);

    // Update index length to 128

    // Core table
    $tableName = Phpfox::getT('custom_group');
    if($installerDb->tableExists($tableName) && $installerDb->isIndex($tableName, 'phrase_var_name')) {
        $installerDb->dropIndex($tableName, 'phrase_var_name');
        $installerDb->addIndex($tableName, '`phrase_var_name`(128)', 'phrase_var_name_128');
    }

    $tableName = Phpfox::getT('language_phrase');
    if($installerDb->tableExists($tableName)) {
        if($installerDb->isIndex($tableName, 'module_id')) {
            $installerDb->dropIndex($tableName, 'module_id');
            $installerDb->addIndex($tableName, '`var_name`(128),`module_id`', 'var_name_128_module_id');
        }
        if($installerDb->isIndex($tableName, 'setting_list')) {
            $installerDb->dropIndex($tableName, 'setting_list');
            $installerDb->addIndex($tableName, '`var_name`(128),`language_id`', 'var_name_128_language_id');
        }
    }

    $tableName = Phpfox::getT('language');
    if($installerDb->tableExists($tableName) && $installerDb->isIndex($tableName, 'title')) {
        $installerDb->dropIndex($tableName, 'title');
        $installerDb->addIndex($tableName, '`title`(128)', 'title_128');
    }

    $tableName = Phpfox::getT('menu');
    if($installerDb->tableExists($tableName) && $installerDb->isIndex($tableName, 'url_value')) {
        $installerDb->dropIndex($tableName, 'url_value');
        $installerDb->addIndex($tableName, '`url_value`(128),`module_id`', 'url_value_128_module_id');
    }

    $tableName = Phpfox::getT('page');
    if($installerDb->tableExists($tableName)) {
        if($installerDb->isIndex($tableName, 'url_value')) {
            $installerDb->dropIndex($tableName, 'url_value');
            $installerDb->addIndex($tableName, '`title_url`(128)', 'title_url_128');
        }
        if($installerDb->isIndex($tableName, 'is_active')) {
            $installerDb->dropIndex($tableName, 'is_active');
            $installerDb->addIndex($tableName, '`title_url`(128),`is_active`', 'title_url_128_is_active');
        }
    }

    $tableName = Phpfox::getT('plugin_hook');
    if($installerDb->tableExists($tableName) && $installerDb->isIndex($tableName, 'call_name')) {
        $installerDb->dropIndex($tableName, 'call_name');
        $installerDb->addIndex($tableName, '`call_name`(128),`is_active`', 'call_name_128_is_active');
    }

    $tableName = Phpfox::getT('setting_group');
    if($installerDb->tableExists($tableName) && $installerDb->isIndex($tableName, 'var_name')) {
        $installerDb->dropIndex($tableName, 'var_name');
        $installerDb->addIndex($tableName, '`var_name`(128)', 'var_name_128');
    }

    $tableName = Phpfox::getT('tag');
    if($installerDb->tableExists($tableName)) {
        if($installerDb->isIndex($tableName, 'user_id')) {
            $installerDb->dropIndex($tableName, 'user_id');
            $installerDb->addIndex($tableName, '`tag_text`(128),`user_id`', 'tag_text_128_user_id');
        }
        if($installerDb->isIndex($tableName, 'tag_url')) {
            $installerDb->dropIndex($tableName, 'tag_url');
            $installerDb->addIndex($tableName, '`tag_url`(128)', 'tag_url_128');
        }
        if($installerDb->isIndex($tableName, 'user_search')) {
            $installerDb->dropIndex($tableName, 'user_search');
            $installerDb->addIndex($tableName, '`tag_text`(128),`category_id`,`user_id`', 'tag_text_128_category_user');
        }
        if($installerDb->isIndex($tableName, 'item_id_3')) {
            $installerDb->dropIndex($tableName, 'item_id_3');
            $installerDb->addIndex($tableName, '`tag_url`(128),`item_id`,`category_id`', 'tag_url_128_item_category');
        }
        if($installerDb->isIndex($tableName, 'category_id_2')) {
            $installerDb->dropIndex($tableName, 'category_id_2');
            $installerDb->addIndex($tableName, '`tag_text`(128),`category_id`', 'tag_text_128_category_id');
        }
    }

    $tableName = Phpfox::getT('user');
    if($installerDb->tableExists($tableName)) {
        if($installerDb->isIndex($tableName, 'email')) {
            $installerDb->dropIndex($tableName, 'email');
            $installerDb->addIndex($tableName, '`email`(128)', 'email_128');
        }
        if($installerDb->isIndex($tableName, 'status_id_2')) {
            $installerDb->dropIndex($tableName, 'status_id_2');
            $installerDb->addIndex($tableName, '`full_name`(128),`status_id`,`view_id`', 'full_name_128_status_view');
        }
    }

    $tableName = Phpfox::getT('user_group_custom');
    if($installerDb->tableExists($tableName) && $installerDb->isIndex($tableName, 'user_group_id')) {
        $installerDb->dropIndex($tableName, 'user_group_id');
        $installerDb->addIndex($tableName, '`name`(128),`user_group_id`,`module_id`', 'name_128_group_module');
    }

    // App tables
    // -- Blog
    $tableName = Phpfox::getT('blog');
    if($installerDb->tableExists($tableName) && $installerDb->isIndex($tableName, 'title')) {
        $installerDb->dropIndex($tableName, 'title');
        $installerDb->addIndex($tableName, '`title`(128),`is_approved`,`privacy`,`post_status`', 'title_128_approved_privacy_status');
    }

    // -- Event
    $tableName = Phpfox::getT('event_category');
    if($installerDb->tableExists($tableName) && $installerDb->isIndex($tableName, 'is_active')) {
        $installerDb->dropIndex($tableName, 'is_active');
        $installerDb->addIndex($tableName, '`is_active`', 'is_active_only');
    }

    // -- Forum
    $tableName = Phpfox::getT('forum_thread');
    if($installerDb->tableExists($tableName)) {
        if($installerDb->isIndex($tableName, 'group_id')) {
            $installerDb->dropIndex($tableName, 'group_id');
            $installerDb->addIndex($tableName, '`title_url`(128),`group_id`,`view_id`', 'title_url_128_group_view');
        }
        if($installerDb->isIndex($tableName, 'group_id_3')) {
            $installerDb->dropIndex($tableName, 'group_id_3');
            $installerDb->addIndex($tableName, '`title_url`(128),`group_id`', 'title_url_128_group');
        }
        if($installerDb->isIndex($tableName, 'view_id_2')) {
            $installerDb->dropIndex($tableName, 'view_id_2');
            $installerDb->addIndex($tableName, '`title`(128),`view_id`', 'title_128_view');
        }
    }

    // -- Message
    $tableName = Phpfox::getT('mail_thread_folder');
    if($installerDb->tableExists($tableName)) {
        if($installerDb->isIndex($tableName, 'name')) {
            $installerDb->dropIndex($tableName, 'name');
            $installerDb->addIndex($tableName, '`name`(128),`user_id`', 'name_128_user_id');
        }
        if($installerDb->isIndex($tableName, 'folder_id')) {
            $installerDb->dropIndex($tableName, 'folder_id');
            $installerDb->addIndex($tableName, '`name`(128),`folder_id`', 'name_128_folder_id');
        }
    }

    // -- Marketplace
    $tableName = Phpfox::getT('marketplace_category');
    if($installerDb->tableExists($tableName) && $installerDb->isIndex($tableName, 'is_active')) {
        $installerDb->dropIndex($tableName, 'is_active');
        $installerDb->addIndex($tableName, '`is_active`', 'is_active_only');
    }

    // -- Music
    $tableName = Phpfox::getT('music_genre');
    if($installerDb->tableExists($tableName) && $installerDb->isIndex($tableName, 'name')) {
        $installerDb->dropIndex($tableName, 'name');
        $installerDb->addIndex($tableName, '`name`(128)', 'name_128');
    }

    $tableName = Phpfox::getT('music_song');
    if($installerDb->tableExists($tableName) && $installerDb->isIndex($tableName, 'view_id_5')) {
        $installerDb->dropIndex($tableName, 'view_id_5');
        $installerDb->addIndex($tableName, '`title`(128),`view_id`,`privacy`', 'title_128_view_privacy');
    }

    // -- Pages
    $tableName = Phpfox::getT('pages');
    if($installerDb->tableExists($tableName) && $installerDb->isIndex($tableName, 'view_id')) {
        $installerDb->dropIndex($tableName, 'view_id');
        $installerDb->addIndex($tableName, '`title`(128),`view_id`,`privacy`', 'title_128_view_privacy');
    }

    $tableName = Phpfox::getT('pages_url');
    if($installerDb->tableExists($tableName) && $installerDb->isIndex($tableName, 'vanity_url')) {
        $installerDb->dropIndex($tableName, 'vanity_url');
        $installerDb->addIndex($tableName, '`vanity_url`(128)', 'vanity_url_128');
    }

    // -- Photos
    $tableName = Phpfox::getT('photo');
    if($installerDb->tableExists($tableName) && $installerDb->isIndex($tableName, 'view_id_4')) {
        $installerDb->dropIndex($tableName, 'view_id_4');
        $installerDb->addIndex($tableName, '`title`(128),`view_id`,`privacy`', 'title_128_view_privacy');
    }

    $tableName = Phpfox::getT('photo_category');
    if($installerDb->tableExists($tableName) && $installerDb->isIndex($tableName, 'name_url')) {
        $installerDb->dropIndex($tableName, 'name_url');
    }

    // -- Poll
    $tableName = Phpfox::getT('poll');
    if($installerDb->tableExists($tableName) && $installerDb->isIndex($tableName, 'item_id_3')) {
        $installerDb->dropIndex($tableName, 'item_id_3');
        $installerDb->addIndex($tableName, '`question`(128),`item_id`,`view_id`,`privacy`', 'question_128_item_view_privacy');
    }

    // -- Quiz
    $tableName = Phpfox::getT('quiz');
    if($installerDb->tableExists($tableName) && $installerDb->isIndex($tableName, 'view_id_4')) {
        $installerDb->dropIndex($tableName, 'view_id_4');
        $installerDb->addIndex($tableName, '`title`(128),`view_id`,`privacy`', 'title_128_view_privacy');
    }

    // -- Video
    $tableName = Phpfox::getT('video');
    if($installerDb->tableExists($tableName)) {
        if($installerDb->isIndex($tableName, 'in_process_4')) {
            $installerDb->dropIndex($tableName, 'in_process_4');
            $installerDb->addIndex($tableName, '`title`(128),`in_process`,`view_id`,`item_id`,`privacy`', 'title_128_process_view_item_privacy');
        }
        if($installerDb->isIndex($tableName, 'in_process_6')) {
            $installerDb->dropIndex($tableName, 'in_process_6');
            $installerDb->addIndex($tableName, '`title`(128),`in_process`,`view_id`,`privacy`', 'title_128_process_view_privacy');
        }
    }

};

