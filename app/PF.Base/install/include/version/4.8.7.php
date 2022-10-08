<?php
return function (Phpfox_Installer $Installer) {
    $installerDb = $Installer->db;
    $blockExists = $installerDb->select('block_id')
        ->from(':block')
        ->where([
            'component'    => 'cf_about_me',
            'module_id'    => 'custom',
            'm_connection' => 'profile.index',
        ])->executeField(false);
    if ($blockExists) {
        $installerDb->update(':block', [
            'm_connection' => 'profile.info',
            'location'     => 2,
        ], ['block_id' => $blockExists]);
    }

    $aNewSettings = [
        [
            'group_id' => 'email',
            'module_id' => 'user',
            'product_id' => 'phpfox',
            'is_hidden' => 0,
            'version_id' => '4.8.7',
            'type_id' => '',
            'var_name' => 'user_setting_subject_verify_email',
            'value_actual' => '{_p var="please_verify_your_email_for_site_title"}',
            'value_default' => '{_p var="please_verify_your_email_for_site_title"}',
            'phrase_var_name' => 'setting_user_setting_subject_verify_email',
            'ordering' => 28,
        ],
        [
            'group_id' => 'email',
            'module_id' => 'user',
            'product_id' => 'phpfox',
            'is_hidden' => 0,
            'version_id' => '4.8.7',
            'type_id' => '',
            'var_name' => 'user_setting_content_verify_email',
            'value_actual' => '{_p var="you_registered_an_account_on_site_title_before_being_able_to_use_your_account_you_need_to_verify_that_this_is_your_email_address_by_clicking_here_a_href_link_link_a"}',
            'value_default' => '{_p var="you_registered_an_account_on_site_title_before_being_able_to_use_your_account_you_need_to_verify_that_this_is_your_email_address_by_clicking_here_a_href_link_link_a"}',
            'phrase_var_name' => 'setting_user_setting_content_verify_email',
            'ordering' => 29,
        ],
        [
            'group_id' => 'email',
            'module_id' => 'user',
            'product_id' => 'phpfox',
            'is_hidden' => 0,
            'version_id' => '4.8.7',
            'type_id' => '',
            'var_name' => 'user_setting_subject_resend_verify_email',
            'value_actual' => '{_p var="email_verification_on_site_title"}',
            'value_default' => '{_p var="email_verification_on_site_title"}',
            'phrase_var_name' => 'setting_user_setting_subject_resend_verify_email',
            'ordering' => 30,
        ],
        [
            'group_id' => 'email',
            'module_id' => 'user',
            'product_id' => 'phpfox',
            'is_hidden' => 0,
            'version_id' => '4.8.7',
            'type_id' => '',
            'var_name' => 'user_setting_content_resend_verify_email',
            'value_actual' => '{_p var="resend_email_on_site_title_before"}',
            'value_default' => '{_p var="resend_email_on_site_title_before"}',
            'phrase_var_name' => 'setting_user_setting_content_resend_verify_email',
            'ordering' => 31,
        ],
        [
            'group_id' => 'email',
            'module_id' => 'user',
            'product_id' => 'phpfox',
            'is_hidden' => 0,
            'version_id' => '4.8.7',
            'type_id' => '',
            'var_name' => 'user_setting_subject_verify_email_after_changed',
            'value_actual' => '{_p var="email_verification_on_site_title"}',
            'value_default' => '{_p var="email_verification_on_site_title"}',
            'phrase_var_name' => 'setting_user_setting_subject_verify_email_after_changed',
            'ordering' => 32,
        ],
        [
            'group_id' => 'email',
            'module_id' => 'user',
            'product_id' => 'phpfox',
            'is_hidden' => 0,
            'version_id' => '4.8.7',
            'type_id' => '',
            'var_name' => 'user_setting_content_verify_email_after_changed',
            'value_actual' => '{_p var="you_changed_email_on_site_title_before"}',
            'value_default' => '{_p var="you_changed_email_on_site_title_before"}',
            'phrase_var_name' => 'setting_user_setting_content_verify_email_after_changed',
            'ordering' => 33,
        ],
        [
            'group_id' => 'email',
            'module_id' => 'user',
            'product_id' => 'phpfox',
            'is_hidden' => 0,
            'version_id' => '4.8.7',
            'type_id' => '',
            'var_name' => 'user_setting_subject_confirm_forgot_password',
            'value_actual' => '{_p var="password_request_for_site_title"}',
            'value_default' => '{_p var="password_request_for_site_title"}',
            'phrase_var_name' => 'setting_user_setting_subject_confirm_forgot_password',
            'ordering' => 34,
        ],
        [
            'group_id' => 'email',
            'module_id' => 'user',
            'product_id' => 'phpfox',
            'is_hidden' => 0,
            'version_id' => '4.8.7',
            'type_id' => '',
            'var_name' => 'user_setting_content_confirm_forgot_password',
            'value_actual' => '{_p var="you_have_requested_for_us_to_send_you_a_new_password_for_site_title"}',
            'value_default' => '{_p var="you_have_requested_for_us_to_send_you_a_new_password_for_site_title"}',
            'phrase_var_name' => 'setting_user_setting_content_confirm_forgot_password',
            'ordering' => 35,
        ],
        [
            'group_id' => 'email',
            'module_id' => 'user',
            'product_id' => 'phpfox',
            'is_hidden' => 0,
            'version_id' => '4.8.7',
            'type_id' => '',
            'var_name' => 'user_setting_subject_new_password',
            'value_actual' => '{_p var="new_password_for_site_title"}',
            'value_default' => '{_p var="new_password_for_site_title"}',
            'phrase_var_name' => 'setting_user_setting_subject_new_password',
            'ordering' => 36,
        ],
        [
            'group_id' => 'email',
            'module_id' => 'user',
            'product_id' => 'phpfox',
            'is_hidden' => 0,
            'version_id' => '4.8.7',
            'type_id' => '',
            'var_name' => 'user_setting_content_new_password',
            'value_actual' => '{_p var="you_have_requested_for_us_to_send_you_a_new_password_for_site_title_with_password"}',
            'value_default' => '{_p var="you_have_requested_for_us_to_send_you_a_new_password_for_site_title_with_password"}',
            'phrase_var_name' => 'setting_user_setting_content_new_password',
            'ordering' => 37,
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