<?php
return function (Phpfox_Installer $Installer) {
    $aHiddenSettings = [
        [
            'var_name' => 'controllers_to_load_delayed',
            'module_id' => 'core'
        ],
        [
            'var_name' => 'site_wide_ajax_browsing',
            'module_id' => 'core'
        ],
        [
            'var_name' => 'use_gzip',
            'module_id' => 'core'
        ],
        [
            'var_name' => 'cache_js_css',
            'module_id' => 'core'
        ],
        [
            'var_name' => 'disable_hash_bang_support',
            'module_id' => 'core'
        ],
        [
            'var_name' => 'build_format',
            'module_id' => 'core'
        ],
        [
            'var_name' => 'build_file_dir',
            'module_id' => 'core'
        ],
        [
            'var_name' => 'csrf_protection_level',
            'module_id' => 'core'
        ],
        [
            'var_name' => 'log_site_activity',
            'module_id' => 'core'
        ],
        [
            'var_name' => 'auth_user_via_session',
            'module_id' => 'core'
        ],
        [
            'var_name' => 'watermark_image',
            'module_id' => 'core'
        ],
        [
            'var_name' => 'watermark_option',
            'module_id' => 'core'
        ],
        [
            'var_name' => 'watermark_opacity',
            'module_id' => 'core'
        ],
        [
            'var_name' => 'watermark_image_position',
            'module_id' => 'core'
        ],
        [
            'var_name' => 'image_text',
            'module_id' => 'core'
        ],
        [
            'var_name' => 'image_text_hex',
            'module_id' => 'core'
        ],
        [
            'var_name' => 'profile_time_stamps',
            'module_id' => 'core'
        ],
        [
            'var_name' => 'can_move_on_a_y_and_x_axis',
            'module_id' => 'core'
        ],
        [
            'var_name' => 'store_only_users_in_session',
            'module_id' => 'core'
        ],
        [
            'var_name' => 'auto_detect_language_on_ip',
            'module_id' => 'language'
        ],
        [
            'var_name' => 'no_string_restriction',
            'module_id' => 'language'
        ],
        [
            'var_name' => 'user_pic_sizes',
            'module_id' => 'user'
        ],
        [
            'var_name' => 'how_many_featured_members',
            'module_id' => 'user'
        ],
        [
            'var_name' => 'min_count_for_top_rating',
            'module_id' => 'user'
        ],
        [
            'var_name' => 'cache_featured_users',
            'module_id' => 'user'
        ],
        [
            'var_name' => 'cache_user_inner_joins',
            'module_id' => 'user'
        ],
        [
            'var_name' => 'twitter_share_via',
            'module_id' => 'feed'
        ],
        [
            'var_name' => 'cache_each_feed_entry',
            'module_id' => 'feed'
        ],
        [
            'var_name' => 'total_likes_to_display',
            'module_id' => 'feed'
        ],
        [
            'var_name' => 'show_user_photos',
            'module_id' => 'like'
        ],
        [
            'var_name' => 'load_friends_online_ajax',
            'module_id' => 'friend'
        ],
        [
            'var_name' => 'update_message_notification_preview',
            'module_id' => 'mail'
        ],
        [
            'var_name' => 'enable_mail_box_warning',
            'module_id' => 'mail'
        ],
        [
            'var_name' => 'cron_delete_messages_delay',
            'module_id' => 'mail'
        ],
        [
            'var_name' => 'comment_time_stamp',
            'module_id' => 'comment'
        ],
        [
            'var_name' => 'hide_denied_requests_from_pending_list',
            'module_id' => 'friend'
        ],
        [
            'var_name' => 'trending_topics_timestamp',
            'module_id' => 'tag'
        ]
    ];

    foreach ($aHiddenSettings as $aHideSetting) {
        $Installer->db->update(':setting', ['is_hidden' => 1], [
            'var_name' => $aHideSetting['var_name'],
            'module_id' => $aHideSetting['module_id']
        ]);
    }

    $aBringBackSettings = [
        [
            'var_name' => 'include_site_title_all_pages',
            'module_id' => 'core'
        ],
        [
            'var_name' => 'crop_seo_url',
            'module_id' => 'core'
        ],
        [
            'var_name' => 'shorten_parsed_url_links',
            'module_id' => 'core'
        ],
        [
            'var_name' => 'words_remove_in_keywords',
            'module_id' => 'core'
        ],
        [
            'var_name' => 'no_follow_on_external_links',
            'module_id' => 'core'
        ],
    ];

    foreach ($aBringBackSettings as $aBringBackSetting) {
        $Installer->db->update(':setting', ['is_hidden' => 0], [
            'var_name' => $aBringBackSetting['var_name'],
            'module_id' => $aBringBackSetting['module_id']
        ]);
    }

    $Installer->db->insert(':setting', [
        'group_id' => 'mail',
        'module_id' => 'core',
        'product_id' => 'phpfox',
        'is_hidden' => '0',
        'version_id' => '4.6.1',
        'type_id' => 'boolean',
        'var_name' => 'check_certificate_smtp_host_name',
        'phrase_var_name' => 'setting_check_certificate_smtp_host_name',
        'value_actual' => '1',
        'value_default' => '1',
        'ordering' => '5',
    ]);

    $Installer->_db()->query("ALTER TABLE `" . Phpfox::getT('link') . "` CHANGE `image` `image` VARCHAR(1023) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;");

    //Update addthis module_id
    $Installer->_db()->update(':setting', [
        'module_id' => 'share'
    ], 'var_name = "show_addthis_section"');
    $Installer->_db()->update(':setting', [
        'module_id' => 'share'
    ], 'var_name = "addthis_pub_id"');
    $Installer->_db()->update(':setting', [
        'module_id' => 'share'
    ], 'var_name = "addthis_share_button"');

    //Hide supper cache
    $Installer->_db()->update(':setting', [
        'is_hidden' => 1
    ], 'var_name = "super_cache_system"');

    //Hide  cache is friend
    $Installer->_db()->update(':setting', [
        'is_hidden' => 1
    ], 'var_name = "cache_is_friend"');
    $Installer->_db()->update(':setting', [
        'is_hidden' => 1
    ], 'var_name = "cache_friend_list"');

    // update default value
    $Installer->_db()->update(':setting', [
        'value_default' => 600
    ], [
        'var_name' => 'attachment_max_medium',
        'module_id' => 'attachment'
    ]);

    // add missing column is_read in case upgrade from 4.6 beta to official
    if (!$Installer->db->isField(Phpfox::getT('notification'), 'is_read')) {
        $Installer->db->addField([
            'table' => Phpfox::getT('notification'),
            'field' => 'is_read',
            'type' => 'TINT:1',
            'default' => '0'
        ]);
        $Installer->db->update(':notification', ['is_read' => 1], '1');
    }


    //Hide user group setting can_comment_on_own_profile
    $Installer->db->update(':user_group_setting', ['is_hidden' => 1],
        'name = "can_comment_on_own_profile" AND module_id="comment"');
    $Installer->db->update(':user_group_setting', ['is_hidden' => 1],
        'name = "can_delete_comments_posted_on_own_profile" AND module_id="comment"');


    $Installer->db->insert(':component', [
        'component' => 'news-slide',
        'm_connection' => '',
        'module_id' => 'core',
        'product_id' => 'phpfox',
        'is_controller' => 0,
        'is_block' => 1,
        'is_active' => 1,
    ]);

    $Installer->db->insert(':block', [
        'title' => 'News Slide',
        'type_id' => 0,
        'm_connection' => 'admincp.index',
        'module_id' => 'core',
        'product_id' => 'phpfox',
        'component' => 'news-slide',
        'location' => 6,
        'is_active' => 1,
        'ordering' => 3,
    ]);

    // update auto responder subject phrase
    $aAutoResponderSubjectSetting = $Installer->db->select('*')->from(':setting')->where(['var_name' => 'auto_responder_subject'])->executeRow();
    if ($aAutoResponderSubjectSetting['value_actual'] != $aAutoResponderSubjectSetting['value_default']) {
        $Installer->db->update(':language_phrase', [
            'text' => $aAutoResponderSubjectSetting['value_actual'],
            'text_default' => $aAutoResponderSubjectSetting['value_actual']
        ], [
            'var_name' => 'auto_responder_subject'
        ]);
    }
    // update auto responder message phrase
    $aAutoResponderMessageSetting = $Installer->db->select('*')->from(':setting')->where(['var_name' => 'auto_responder_message'])->executeRow();
    if ($aAutoResponderMessageSetting['value_actual'] != $aAutoResponderMessageSetting['value_default']) {
        $Installer->db->update(':language_phrase', [
            'text' => $aAutoResponderMessageSetting['value_actual'],
            'text_default' => $aAutoResponderMessageSetting['value_actual']
        ], [
            'var_name' => 'auto_responder_message'
        ]);
    }

    // admincp dashboard: update block note location
    $Installer->db->update(':block', [
        'location' => 3
    ], [
        'm_connection' => 'admincp.index',
        'component' => 'note'
    ]);

    $aDeleteMenus = [
        [
            'm_connection' => 'user.setting'
        ],
        [
            'm_connection' => 'user.privacy'
        ],
        [
            'm_connection' => 'invite'
        ],
        [
            'm_connection' => 'subscribe'
        ]
    ];

    foreach ($aDeleteMenus as $aDeleteMenu) {
        $Installer->db->delete(':menu', $aDeleteMenu);
    }

    $aRemovePhrases = [
        ['var_name' => 'we_are_unable_to_find_a_branding_removal_assigned_to_this_license_dot'],
        ['var_name' => 'you_have_already_rated_this_user'],
        ['var_name' => 'you_cannot_rate_yourself'],
        ['var_name' => 'you_cannot_rate_your_own_profile'],
        ['var_name' => 'top_rated_members'],
        ['var_name' => 'rate_your_profile'],
        ['var_name' => 'you_cannot_rate_your_own_album'],
        ['var_name' => 'i_have_read_and_agree_to_the_terms_of_use_and_privacy_policy'],
        ['var_name' => 'you_have_rated_all_the_available_images'],
        ['var_name' => 'rate_photos'],
        ['var_name' => 'top_rated'],
        ['var_name' => 'rate_this_image'],
        ['var_name' => 'no_available_images_to_rate'],
    ];

    foreach ($aRemovePhrases as $aRemovePhrase) {
        $Installer->db->delete(':language_phrase', $aRemovePhrase);
    }
};
