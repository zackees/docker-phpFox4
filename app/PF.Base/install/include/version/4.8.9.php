<?php
return function (Phpfox_Installer $Installer) {
    // Remove settings
    $installerDb = $Installer->db;

    $tableName = Phpfox::getT('user_verify');
    if ($installerDb->tableExists($tableName) && !$installerDb->isField($tableName, 'verify_id')) {
        $installerDb->query("ALTER TABLE `" . $tableName . "` DROP PRIMARY KEY;");
        $installerDb->query("ALTER TABLE `" . $tableName . "` ADD `verify_id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;");
        $installerDb->query("ALTER TABLE `" . $tableName . "` ADD `type_id` varchar(50) NULL DEFAULT 'verify_account';");
    }

    if (!$installerDb->isField(Phpfox::getT('user_group_setting'), 'disallow_access')) {
        $installerDb->addField([
            'table' => Phpfox::getT('user_group_setting'),
            'field' => 'disallow_access',
            'type' => 'TEXT',
            'null' => true
        ]);
    }

    if (!$installerDb->isField(Phpfox::getT('invite'), 'hash_code')) {
        $installerDb->addField([
            'table' => Phpfox::getT('invite'),
            'field' => 'hash_code',
            'type' => 'VCHAR:52',
            'null' => true
        ]);
    }

    //hide setting genders in module core
    $installerDb->update(':setting', ['is_hidden' => 1], [
        'var_name' => 'global_genders',
        'module_id' => 'core',
    ]);

    //update phrases
    $aUpdatePhrases = [
        "setting_shorter_password_reset_routine" => "<title>Shorter Password Reset Routine</title><info>If this is enabled when a user clicks on Forgot your password he will receive an email with a link, or if user request password will be sent to phone number (This only works when setting \"Enable Registration using Phone Number\" is enabled), then he will receive a passcode via phone number, when clicking on the link or inputting the passcode he will be shown an input where to change the password. The site will not assign a new password to that user and the previous password will work until it has been reset.</info>",
        "user_setting_can_member_snoop" => "Can members of this user group log in as another user without entering a password?\r\n\r\n<b>Notice:</b> Requires the ability to log into the AdminCP.",
        "user_setting_can_manage_user_group_settings" => "Can manage user group settings?\r\n\r\n<b>Notice:</b> Requires the ability to log into the AdminCP.",
        "user_setting_can_edit_user_group" => "Can edit user groups?\r\n\r\n<b>Notice:</b> Requires the ability to log into the AdminCP.",
        "user_setting_can_delete_user_group" => "Can delete user group?\r\n\r\n<b>Notice:</b> Requires the ability to log into the AdminCP.",
        "user_setting_can_edit_other_user_privacy" => "Can edit privacy settings for other users?\r\n\r\n<b>Notice:</b> Requires the ability to log into the AdminCP.",
        "user_setting_can_edit_user_group_membership" => "Can modify a users \"user group\" status?\r\n\r\n<b>Notice:</b> Requires the ability to log into the AdminCP.",
        "maximum_length_for_password_is_number" => "Maximum length for password is {number}.",
        "minimum_length_for_password_is_number" => "Minimum length for password is {number}."
    ];
    Phpfox::getService('language.phrase.process')->updatePhrases($aUpdatePhrases);
};