<?php
return function (Phpfox_Installer $Installer) {
    // Remove settings
    $installerDb = $Installer->db;

    if (!$installerDb->isField(Phpfox::getT('user'), 'two_step_verification')) {
        $installerDb->addField([
            'table' => Phpfox::getT('user'),
            'field' => 'two_step_verification',
            'type' => 'TINT:1',
            'null' => true,
            'default' => 0
        ]);
        $installerDb->query('UPDATE `'. Phpfox::getT('user') .'` SET `two_step_verification` = (SELECT `value_actual` FROM `' . Phpfox::getT('setting') . '` WHERE `var_name` = \'enable_2step_verification\' AND `module_id` = \'user\')');
    }

    $installerDb->query('ALTER TABLE ' . Phpfox::getT('user_twofactor_token') . ' CHANGE email email VARCHAR(150) NOT NULL DEFAULT \'\'');

    if (!$installerDb->isField(Phpfox::getT('user_delete_feedback'), 'user_phone')) {
        $installerDb->addField([
            'table' => Phpfox::getT('user_delete_feedback'),
            'field' => 'user_phone',
            'type' => 'VCHAR:50',
            'null' => true,
            'after' => 'user_email'
        ]);
    }

    if (!$installerDb->isField(Phpfox::getT('link'), 'server_id')) {
        $installerDb->addField([
            'table' => Phpfox::getT('link'),
            'field' => 'server_id',
            'type' => 'TINT:1',
            'null' => true,
            'default' => '0',
            'after' => 'image'
        ]);
    }

    $aRemoveSettings = [
        [
            'var_name'  => 'vimeo_client_id',
            'module_id' => 'link'
        ],
        [
            'var_name'  => 'vimeo_client_secret',
            'module_id' => 'link'
        ],
        [
            'var_name'  => 'vimeo_access_token',
            'module_id' => 'link'
        ],
        [
            'var_name'  => 'enable_2step_verification',
            'module_id' => 'user'
        ]
    ];

    foreach ($aRemoveSettings as $aRemoveSetting) {
        $installerDb->delete(':setting', [
            'var_name'  => $aRemoveSetting['var_name'],
            'module_id' => $aRemoveSetting['module_id']
        ]);
    }

    //Update tables
    $installerDb->changeField(Phpfox::getT('menu'), 'disallow_access', [
        'type' => 'TEXT',
        'null' => true
    ]);
    $installerDb->changeField(Phpfox::getT('block'), 'disallow_access', [
        'type' => 'TEXT',
        'null' => true
    ]);
    $installerDb->changeField(Phpfox::getT('page'), 'disallow_access', [
        'type' => 'TEXT',
        'null' => true
    ]);

    //Update setting login method
    $aSetting = $installerDb->select('value_actual, setting_id')->from(':setting')->where([
        'var_name' => 'login_type',
        'module_id' => 'user',
        'type_id' => 'drop'
    ])->executeRow();
    if (!empty($aSetting)) {
        $aValue = !empty($aSetting['value_actual']) ? unserialize($aSetting['value_actual']) : [];
        $installerDb->update(':setting', [
            'type_id'       => 'select',
            'value_actual'  => isset($aValue['default']) ? $aValue['default'] : 'email',
            'value_default' => 'a:2:{s:7:"default";s:5:"email";s:6:"values";a:3:{s:5:"email";s:21:"email_or_phone_number";s:9:"user_name";s:9:"user_name";s:4:"both";s:4:"both";}}'
        ], 'setting_id = ' . $aSetting['setting_id']);
    }

    //update user email settings ordering
    $iMaxOrdering = (int)db()->select('MAX(ordering)')
        ->from(':setting')
        ->where('module_id = "user" and group_id != "email"')
        ->executeField(false);

    $aUserEmailSettings = db()->select('setting_id')
        ->from(':setting')
        ->where([
            'module_id' => 'user',
            'group_id' => 'email'
        ])
        ->order('setting_id ASC')
        ->executeRows(false);

    foreach ($aUserEmailSettings as $iUserEmailSettingId) {
        $installerDb->update(':setting', ['ordering' => ++$iMaxOrdering], ['setting_id' => $iUserEmailSettingId]);
    }

    //update phrases
    $aUpdatePhrases = [
        "name_commented_on_your_profile_update_a_href_link_content_a" => "{name} commented on your profile update \"<a href=\"{link}\">{content}</a>\".\r\nTo see the comment thread, follow the link below:<a href=\"{link}\">{link}</a>",
        "setting_min_length_for_username" => "<title>Minimum Length for Username</title><info>Minimum Length for Username must be greater than or equal to 1</info>",
        "setting_max_length_for_username" => "<title>Maximum Length for Username</title><info>Maximum Length for Username must be greater than or equal to 1</info>",
        "invalid_verification_token" => "Your passcode might have been expired or invalid verification token. Please try again.",
        "setting_login_type" => "<title>User Login Method</title><info>Select the method you would like your users to use when logging into the site.\r\n\r\n<b>User Name</b>\r\nMust use their user name.\r\n\r\n<b>Email or phone number</b>\r\nMust use their email or phone number.\r\n\r\n<b>Both</b>\r\nCan use either email, user name or phone number.\r\n\r\nNote: Login with Phone Number only available if setting \"Enable Registration using Phone Number\" is enabled</info>",
        "inactive_member_email_body" => "<p>We have missed you at <a href=\"{site_url}\">{site_name}<\/a>. <br/>\r\nWhy not come and pay a visit to your friends, there's lots of catching up to do.</p>",
        "google_2step_verify_description" => "<p><b>Authenticator app</b> generates 2-step verification codes on your phone.</p>\r\n<p>Enable 2-step verification to protect your account from hijacking by adding another layer of security. With 2-step verification signing in will require a code generated by <b>Authenticator app</b> in addition to your account password.\r\n<\/p>",
        "use_google_authencator_app_to_scan_this_qr_code" => "Use <b>Authenticator app</b> (Such as: Google Authenticator, Microsoft Authenticator, Twilio Authy, 2FA Authenticator...) to scan this QR Code to get passcode",
        "get_new_google_authencator_barcode_when_you_change_email" => "If you change your email, please scan new Authenticator code here",
    ];
    Phpfox::getService('language.phrase.process')->updatePhrases($aUpdatePhrases);

    $installerDb->update(':user_group_setting', ['is_hidden' => 1], [
        'name' => 'can_post_comment_on_feed',
        'module_id' => 'feed',
    ]);

    $aNewSettings = [
        [
            'group_id' => null,
            'module_id' => 'user',
            'product_id' => 'phpfox',
            'is_hidden' => 0,
            'version_id' => '4.8.8',
            'type_id' => 'integer',
            'var_name' => 'delay_time_for_next_promotion',
            'value_actual' => '1',
            'value_default' => '1',
            'phrase_var_name' => 'setting_delay_time_for_next_promotion',
            'ordering' => 57,
        ],
    ];

    foreach ($aNewSettings as $aNewSetting) {
        $checkSetting = $installerDb->select('setting_id')
            ->from(':setting')
            ->where([
                'var_name' => $aNewSetting['var_name'],
                'module_id' => $aNewSetting['module_id']
            ])->executeRow();
        if (!$checkSetting) {
            $installerDb->insert(':setting', $aNewSetting);
        }
    }
};