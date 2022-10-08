<?php
return function (Phpfox_Installer $Installer) {
    $installerDb = $Installer->db;
    if (!$installerDb->isIndex(Phpfox::getT('user_verify'), 'user_id')) {
        $installerDb->query('ALTER TABLE ' . Phpfox::getT('user_verify') . ' ADD INDEX `user_id` (`user_id`)');
    }
    $tableName = Phpfox::getT('core_session_data');
    if ($installerDb->tableExists($tableName) && !$installerDb->isField($tableName, 'id')) {
        $installerDb->query("ALTER TABLE `" . $tableName . "` DROP PRIMARY KEY;");
        $installerDb->query("ALTER TABLE `" . $tableName . "` ADD `id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;");
    }
    $installerDb->update(':custom_field', ['is_search' => 1], 'phrase_var_name = "user.custom_about_me"');

    //update cron
    $installerDb->update(':cron',[
        "php_code" => "Phpfox::getService('log.process')->removeOldUserSessions();"
    ], 'module_id = "log" AND php_code LIKE "%Phpfox::getLib(\'phpfox.database\')->delete(Phpfox::getT(\'log_session\')%"');
    //update phrases
    $aUpdatePhrases = [
        'manage_schedule_items'       => 'Manage Scheduled Posts',
        'this_scheduled_item_not_exist' => 'This scheduled post is not exist',
        'setting_username_regex_rule' => '</title>Username Regex Rule</title><info>This is the regular expressions configuration which validate for username. "min" and "max" are 2 variables define the length of username which directly relate to 2 settings "Minimum Length for Username" and "Maximum Length for Username" respectively. For more information, see <a target="_blank" href="https://www.php.net/manual/en/reference.pcre.pattern.syntax.php">here</a>.<br/><a role="button" onclick="$Core.editMeta(\'provide_a_valid_user_name\', true)">Click here</a> to edit default error message which related to this setting when user input an invalid data.</info>',
        'setting_fullname_regex_rule' => '</title>Full Name Regex Rule</title><info>This is the regular expressions configuration which validate for full name. "max" is the variable define the maximum length of full name which directly relate to setting "Maximum Length for Full Name". For more information, see <a target="_blank" href="https://www.php.net/manual/en/reference.pcre.pattern.syntax.php">here</a>.<br/><a role="button" onclick="$Core.editMeta(\'provide_a_valid_full_name\', true)">Click here</a> to edit default error message which related to this setting when user input an invalid data.</info>',
        'provide_a_valid_user_name'   => 'Not a valid user name. User name can only contain alphanumeric characters and _ or - and must be between {min} and {max} characters long.',
        'provide_a_valid_full_name'   => 'Full name is invalid. Full name can\'t contain special characters (e.g. !@#$%^&*(),.?\":{}|<>) and it\'s only {max} maximum characters long'
    ];
    Phpfox::getService('language.phrase.process')->updatePhrases($aUpdatePhrases);
};