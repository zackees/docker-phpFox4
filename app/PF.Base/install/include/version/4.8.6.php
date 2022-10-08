<?php

return function (Phpfox_Installer $Installer) {
    $installerDb = $Installer->db;
    if (!$installerDb->isField(Phpfox::getT('language'), 'is_active')) {
        $installerDb->addField([
            'table' => Phpfox::getT('language'),
            'field' => 'is_active',
            'type' => 'TINT:1',
            'null' => true,
            'default' => 1,
            'after' => 'is_master'
        ]);
    }

    $newSettings = [
        [
            'group_id' => 'registration',
            'module_id' => 'user',
            'product_id' => 'phpfox',
            'is_hidden' => 0,
            'version_id' => '4.8.6',
            'type_id' => 'integer',
            'var_name' => 'days_for_delete_pending_user_verification',
            'value_actual' => '0',
            'value_default' => '0',
            'phrase_var_name' => 'setting_days_for_delete_pending_user_verification',
            'ordering' => 3,
        ],
        [
            'group_id' => 'regex',
            'module_id' => 'core',
            'product_id' => 'phpfox',
            'is_hidden' => 0,
            'version_id' => '4.8.6',
            'type_id' => 'string',
            'var_name' => 'username_regex_rule',
            'phrase_var_name' => 'setting_username_regex_rule',
            'value_actual' => '/^[a-zA-Z0-9_\-\x7f-\xff]{min,max}$/',
            'value_default' => '/^[a-zA-Z0-9_\-\x7f-\xff]{min,max}$/',
            'ordering' => 0,
        ],
        [
            'group_id' => 'regex',
            'module_id' => 'core',
            'product_id' => 'phpfox',
            'is_hidden' => 0,
            'version_id' => '4.8.6',
            'type_id' => 'string',
            'var_name' => 'fullname_regex_rule',
            'phrase_var_name' => 'setting_fullname_regex_rule',
            'value_actual' => '/^[^!@#$%^&*(),.?":{}|<>]{1,max}$/',
            'value_default' => '/^[^!@#$%^&*(),.?":{}|<>]{1,max}$/',
            'ordering' => 1,
        ],
        [
            'group_id' => 'regex',
            'module_id' => 'core',
            'product_id' => 'phpfox',
            'is_hidden' => 0,
            'version_id' => '4.8.6',
            'type_id' => 'string',
            'var_name' => 'special_characters_regex_rule',
            'phrase_var_name' => 'setting_special_characters_regex_rule',
            'value_actual' => '/[!@#$%^&*(),.?":{}|<>]/',
            'value_default' => '/[!@#$%^&*(),.?":{}|<>]/',
            'ordering' => 2,
        ],
        [
            'group_id' => 'regex',
            'module_id' => 'core',
            'product_id' => 'phpfox',
            'is_hidden' => 0,
            'version_id' => '4.8.6',
            'type_id' => 'string',
            'var_name' => 'html_regex_rule',
            'phrase_var_name' => 'setting_html_regex_rule',
            'value_actual' => '/<(.*?)>/',
            'value_default' => '/<(.*?)>/',
            'ordering' => 3,
        ],
        [
            'group_id' => 'regex',
            'module_id' => 'core',
            'product_id' => 'phpfox',
            'is_hidden' => 0,
            'version_id' => '4.8.6',
            'type_id' => 'string',
            'var_name' => 'url_regex_rule',
            'phrase_var_name' => 'setting_url_regex_rule',
            'value_actual' =>
                '~(?>[a-z+]{2,}://|www\.)(?:[a-z0-9]+(?:\.[a-z0-9]+)?@)?(?:(?:[a-z](?:[a-z0-9]|(?<!-)-)*[a-z0-9])(?:\.[a-z](?:[a-z0-9]|(?<!-)-)*[a-z0-9])+|(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?))(?:/[^\\/:?*"<>|\n]*[a-z0-9])*/?(?:\?[a-z0-9_.%]+(?:=[a-z0-9_.%:/+-]*)?(?:&[a-z0-9_.%]+(?:=[a-z0-9_.%:/+-]*)?)*)?(?:#[a-z0-9_%.]+)?~is',
            'value_default' =>
                '~(?>[a-z+]{2,}://|www\.)(?:[a-z0-9]+(?:\.[a-z0-9]+)?@)?(?:(?:[a-z](?:[a-z0-9]|(?<!-)-)*[a-z0-9])(?:\.[a-z](?:[a-z0-9]|(?<!-)-)*[a-z0-9])+|(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?))(?:/[^\\/:?*"<>|\n]*[a-z0-9])*/?(?:\?[a-z0-9_.%]+(?:=[a-z0-9_.%:/+-]*)?(?:&[a-z0-9_.%]+(?:=[a-z0-9_.%:/+-]*)?)*)?(?:#[a-z0-9_%.]+)?~is',
            'ordering' => 4,
        ],
        [
            'group_id' => 'regex',
            'module_id' => 'core',
            'product_id' => 'phpfox',
            'is_hidden' => 0,
            'version_id' => '4.8.6',
            'type_id' => 'string',
            'var_name' => 'currency_id_regex_rule',
            'phrase_var_name' => 'setting_currency_id_regex_rule',
            'value_actual' => '/^[A-Z]{3,3}$/',
            'value_default' => '/^[A-Z]{3,3}$/',
            'ordering' => 5,
        ],
    ];

    foreach ($newSettings as $newSetting) {
        $checkSetting = $installerDb->select('setting_id')
            ->from(':setting')
            ->where([
                'var_name' => $newSetting['var_name'],
                'module_id' => $newSetting['module_id']
            ])->executeField(false);
        if (!$checkSetting) {
            $installerDb->insert(':setting', $newSetting);
        }
    }
    $aUpdatePhrases = [
        "menu_font_awesome" => "Can't find the right icon? Click here to find more from Font Awesome and input the icon you want.",
        "phpfox_version" => "Core Version",
        "php_safe_mode" => "PHP Safe Mode",
        "php_open_basedir" => "PHP Open Basedir",
        "setting_login_type" => "<title>User Login Method<\/title><info>Select the method you would like your users to use when logging into the site.\r\n\r\n<b>User Name<\/b>\r\nMust use their user name.\r\n\r\n<b>Email<\/b>\r\nMust use their email.\r\n\r\n<b>Both<\/b>\r\nCan use either email or user name.<\/info>",
        "setting_user_browse_default_result" => "<title>Browsing Users Default Order<\/title><info>Select <b>Full Name<\/b> to order members based on their full name in ascending order. Select <b>Last Login<\/b> to order members based on their last activity, where the latest person to be active on the site is first.<\/info>",
        "setting_default_privacy_brithdate" => "<title>Default Birthday Privacy Setting<\/title><info>Users can control their default privacy settings when it comes to how they want their birthdays to be displayed on their profile. When users sign up and have not chosen a privacy setting you can define a default setting for the site.\r\n\r\nHere is a key of what the values stand for...\r\n<b>Full birthday<\/b> = <i>Show full birthday<\/i>\r\n<b>Month & Day<\/b> = <i>Show month & day<\/i>\r\n<b>Show age only<\/b> = <i>Only show the users age<\/i>\r\n<b>Hide<\/b> = <i>Hide users age\/birthday<\/i><\/info>",
        "full_name_liked_your_comment_message_mini" => "{full_name} liked your comment \"{content}\" that you posted.\r\nTo view this comment follow the link below:\r\n<a href=\"{link}\">{link}</a>"
    ];
    Phpfox::getService('language.phrase.process')->updatePhrases($aUpdatePhrases);
};