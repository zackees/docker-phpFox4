<module>
    <data>
        <module_id>user</module_id>
        <product_id>phpfox</product_id>
        <is_core>1</is_core>
        <is_active>1</is_active>
        <is_menu>1</is_menu>
        <menu><![CDATA[a:1:{s:60:"user.admin_menu_phrase_var_user_anti_spam_security_questions";a:1:{s:3:"url";a:2:{i:0;s:4:"user";i:1;s:4:"spam";}}}]]></menu>
        <phrase_var_name>module_user</phrase_var_name>
        <writable><![CDATA[a:2:{i:0;s:14:"file/pic/user/";i:1;s:28:"file/pic/user/spam_question/";}]]></writable>
    </data>
    <menus>
        <menu module_id="user" parent_var_name="" m_connection="main" var_name="menu_browse" ordering="3" url_value="user.browse" version_id="2.0.0alpha1" disallow_access="" module="user" mobile_icon="users" />
        <menu module_id="user" parent_var_name="" m_connection="main_right" var_name="menu_settings" ordering="5" url_value="user.setting" version_id="2.0.0alpha1" disallow_access="a:1:{i:0;s:1:&quot;3&quot;;}" module="user" />
        <menu module_id="user" parent_var_name="" m_connection="profile.my" var_name="menu_edit_profile_picture" ordering="3" url_value="user.photo" version_id="2.0.0alpha1" disallow_access="" module="user" />
        <menu module_id="user" parent_var_name="" m_connection="profile.my" var_name="menu_edit_profile" ordering="2" url_value="user.profile" version_id="2.0.0alpha3" disallow_access="" module="user" />
        <menu module_id="user" parent_var_name="" m_connection="mobile" var_name="menu_user_members_532c28d5412dd75bf975fb951c740a30" ordering="126" url_value="user.browse" version_id="3.1.0rc1" disallow_access="" module="user" mobile_icon="small_groups.png" />
        <menu module_id="user" parent_var_name="menu_settings" m_connection="" var_name="menu_user_logout_4ee1a589029a67e7f1a00990a1786f46" ordering="109" url_value="user.logout" version_id="3.0.0Beta1" disallow_access="a:1:{i:0;s:1:&quot;3&quot;;}" module="user" />
        <menu module_id="user" parent_var_name="menu_settings" m_connection="" var_name="menu_user_account_settings_73c8da87d666df89aabd61620c81c24c" ordering="107" url_value="user.setting" version_id="3.0.0beta4" disallow_access="" module="user" />
        <menu module_id="user" parent_var_name="menu_settings" m_connection="" var_name="menu_user_privacy_settings_73c8da87d666df89aabd61620c81c24c" ordering="108" url_value="user.privacy" version_id="3.0.0beta4" disallow_access="" module="user" />
    </menus>
    <settings>
        <setting group="registration" module_id="user" is_hidden="0" type="array" var_name="global_genders" phrase_var_name="setting_global_genders" ordering="1" version_id="2.0.5dev2"><![CDATA[s:112:"array(
  0 => '1|core.his|profile.male|core.himself',
  1 => '2|core.her|profile.female|core.herself|female',
);";]]></setting>
        <setting group="" module_id="user" is_hidden="0" type="string" var_name="redirect_after_login" phrase_var_name="setting_redirect_after_login" ordering="2" version_id="2.0.0alpha1" />
        <setting group="" module_id="user" is_hidden="0" type="select" var_name="login_type" phrase_var_name="setting_login_type" ordering="1" version_id="2.0.0alpha1"><![CDATA[a:2:{s:7:"default";s:5:"email";s:6:"values";a:3:{s:5:"email";s:21:"email_or_phone_number";s:9:"user_name";s:9:"user_name";s:4:"both";s:4:"both";}}]]></setting>
        <setting group="" module_id="user" is_hidden="0" type="boolean" var_name="profile_use_id" phrase_var_name="setting_profile_use_id" ordering="1" version_id="2.0.0alpha1">0</setting>
        <setting group="registration" module_id="user" is_hidden="0" type="boolean" var_name="captcha_on_signup" phrase_var_name="setting_captcha_on_signup" ordering="9" version_id="2.0.0alpha1">0</setting>
        <setting group="" module_id="user" is_hidden="0" type="integer" var_name="user_profile_private_age" phrase_var_name="setting_user_profile_private_age" ordering="1" version_id="2.0.0beta4">0</setting>
        <setting group="spam" module_id="user" is_hidden="0" type="boolean" var_name="validate_full_name" phrase_var_name="setting_validate_full_name" ordering="12" version_id="2.0.0beta4">1</setting>
        <setting group="registration" module_id="user" is_hidden="0" type="boolean" var_name="verify_email_at_signup" phrase_var_name="setting_verify_email_at_signup" ordering="3" version_id="2.0.0beta5">0</setting>
        <setting group="" module_id="user" is_hidden="0" type="integer" var_name="verify_email_timeout" phrase_var_name="setting_verify_email_timeout" ordering="1" version_id="2.0.0beta5">60</setting>
        <setting group="" module_id="user" is_hidden="0" type="boolean" var_name="logout_after_change_email_if_verify" phrase_var_name="setting_logout_after_change_email_if_verify" ordering="1" version_id="2.0.0beta5">1</setting>
        <setting group="" module_id="user" is_hidden="0" type="boolean" var_name="display_user_online_status" phrase_var_name="setting_display_user_online_status" ordering="1" version_id="2.0.0rc1">0</setting>
        <setting group="registration" module_id="user" is_hidden="0" type="integer" var_name="min_length_for_username" phrase_var_name="setting_min_length_for_username" ordering="4" version_id="2.0.0rc2">5</setting>
        <setting group="registration" module_id="user" is_hidden="0" type="integer" var_name="max_length_for_username" phrase_var_name="setting_max_length_for_username" ordering="5" version_id="2.0.0rc2">25</setting>
        <setting group="registration" module_id="user" is_hidden="0" type="integer" var_name="on_signup_new_friend" phrase_var_name="setting_on_signup_new_friend" ordering="10" version_id="2.0.0rc4">0</setting>
        <setting group="spam" module_id="user" is_hidden="0" type="integer" var_name="check_status_updates" phrase_var_name="setting_check_status_updates" ordering="13" version_id="2.0.0rc5">1</setting>
        <setting group="registration" module_id="user" is_hidden="0" type="string" var_name="redirect_after_signup" phrase_var_name="setting_redirect_after_signup" ordering="12" version_id="2.0.0rc10" />
        <setting group="" module_id="user" is_hidden="0" type="integer" var_name="date_of_birth_start" phrase_var_name="setting_date_of_birth_start" ordering="1" version_id="2.0.0rc11">1900</setting>
        <setting group="" module_id="user" is_hidden="0" type="integer" var_name="date_of_birth_end" phrase_var_name="setting_date_of_birth_end" ordering="1" version_id="2.0.0rc11">2017</setting>
        <setting group="" module_id="user" is_hidden="0" type="drop" var_name="user_browse_default_result" phrase_var_name="setting_user_browse_default_result" ordering="1" version_id="2.0.0rc12"><![CDATA[a:2:{s:7:"default";s:9:"full_name";s:6:"values";a:2:{i:0;s:9:"full_name";i:1;s:10:"last_login";}}]]></setting>
        <setting group="registration" module_id="user" is_hidden="0" type="drop" var_name="on_register_privacy_setting" phrase_var_name="setting_on_register_privacy_setting" ordering="11" version_id="2.0.0rc12"><![CDATA[a:2:{s:7:"default";s:6:"anyone";s:6:"values";a:4:{i:0;s:6:"anyone";i:1;s:7:"network";i:2;s:12:"friends_only";i:3;s:6:"no_one";}}]]></setting>
        <setting group="registration" module_id="user" is_hidden="0" type="boolean" var_name="approve_users" phrase_var_name="setting_approve_users" ordering="13" version_id="2.0.5">0</setting>
        <setting group="" module_id="user" is_hidden="0" type="drop" var_name="display_or_full_name" phrase_var_name="setting_display_or_full_name" ordering="1" version_id="2.0.5"><![CDATA[a:2:{s:7:"default";s:9:"full_name";s:6:"values";a:2:{i:0;s:9:"full_name";i:1;s:12:"display_name";}}]]></setting>
        <setting group="registration" module_id="user" is_hidden="0" type="drop" var_name="disable_username_on_sign_up" phrase_var_name="setting_disable_username_on_sign_up" ordering="14" version_id="2.0.5dev1"><![CDATA[a:2:{s:7:"default";s:4:"both";s:6:"values";a:3:{i:0;s:9:"full_name";i:1;s:8:"username";i:2;s:4:"both";}}]]></setting>
        <setting group="" module_id="user" is_hidden="0" type="boolean" var_name="check_promotion_system" phrase_var_name="setting_check_promotion_system" ordering="1" version_id="2.0.5dev2">0</setting>
        <setting group="registration" module_id="user" is_hidden="0" type="boolean" var_name="allow_user_registration" phrase_var_name="setting_allow_user_registration" ordering="0" version_id="2.0.7">1</setting>
        <setting group="" module_id="user" is_hidden="0" type="boolean" var_name="enable_user_tooltip" phrase_var_name="setting_enable_user_tooltip" ordering="1" version_id="2.1.0Beta1">1</setting>
        <setting group="" module_id="user" is_hidden="0" type="integer" var_name="brute_force_attempts_count" phrase_var_name="setting_brute_force_attempts_count" ordering="1" version_id="2.0.8">5</setting>
        <setting group="" module_id="user" is_hidden="0" type="integer" var_name="brute_force_time_check" phrase_var_name="setting_brute_force_time_check" ordering="1" version_id="2.0.8">0</setting>
        <setting group="" module_id="user" is_hidden="0" type="integer" var_name="brute_force_cool_down" phrase_var_name="setting_brute_force_cool_down" ordering="1" version_id="2.0.8">15</setting>
        <setting group="registration" module_id="user" is_hidden="0" type="boolean" var_name="force_user_to_upload_on_sign_up" phrase_var_name="setting_force_user_to_upload_on_sign_up" ordering="15" version_id="2.1.0rc1">0</setting>
        <setting group="registration" module_id="user" is_hidden="0" type="boolean" var_name="invite_only_community" phrase_var_name="setting_invite_only_community" ordering="17" version_id="3.0.0beta1">0</setting>
        <setting group="" module_id="user" is_hidden="0" type="boolean" var_name="enable_relationship_status" phrase_var_name="setting_enable_relationship_status" ordering="1" version_id="3.0.0beta4">1</setting>
        <setting group="time_stamps" module_id="user" is_hidden="0" type="string" var_name="user_dob_month_day_year" phrase_var_name="setting_user_dob_month_day_year" ordering="1" version_id="3.0.0">F j, Y</setting>
        <setting group="time_stamps" module_id="user" is_hidden="0" type="string" var_name="user_dob_month_day" phrase_var_name="setting_user_dob_month_day" ordering="2" version_id="3.0.0">F j</setting>
        <setting group="" module_id="user" is_hidden="0" type="drop" var_name="default_privacy_brithdate" phrase_var_name="setting_default_privacy_brithdate" ordering="1" version_id="3.1.0beta1"><![CDATA[a:2:{s:7:"default";s:13:"full_birthday";s:6:"values";a:4:{i:0;s:13:"full_birthday";i:1;s:9:"month_day";i:2;s:8:"show_age";i:3;s:4:"hide";}}]]></setting>
        <setting group="" module_id="user" is_hidden="0" type="boolean" var_name="shorter_password_reset_routine" phrase_var_name="setting_shorter_password_reset_routine" ordering="1" version_id="3.1.0rc1">0</setting>
        <setting group="" module_id="user" is_hidden="0" type="integer" var_name="maximum_length_for_full_name" phrase_var_name="setting_maximum_length_for_full_name" ordering="1" version_id="3.3.0beta1">25</setting>
        <setting group="" module_id="user" is_hidden="0" type="boolean" var_name="split_full_name" phrase_var_name="setting_split_full_name" ordering="1" version_id="3.4.0beta1">0</setting>
        <setting group="registration" module_id="user" is_hidden="0" type="boolean" var_name="reenter_email_on_signup" phrase_var_name="setting_reenter_email_on_signup" ordering="19" version_id="3.4.0beta1">0</setting>
        <setting group="" module_id="user" is_hidden="0" type="boolean" var_name="prevent_profile_photo_cache" phrase_var_name="setting_prevent_profile_photo_cache" ordering="1" version_id="3.4.0beta2">0</setting>
        <setting group="registration" module_id="user" is_hidden="0" type="boolean" var_name="require_all_spam_questions_on_signup" phrase_var_name="setting_require_all_spam_questions_on_signup" ordering="20" version_id="3.5.0beta1">0</setting>
        <setting group="" module_id="user" is_hidden="0" type="integer" var_name="cache_recent_logged_in" phrase_var_name="setting_cache_recent_logged_in" ordering="3" version_id="3.6.0rc1">0</setting>
        <setting group="" module_id="user" is_hidden="0" type="boolean" var_name="disable_store_last_user" phrase_var_name="setting_disable_store_last_user" ordering="4" version_id="3.6.0rc1">0</setting>
        <setting group="registration" module_id="user" is_hidden="0" type="boolean" var_name="hide_main_menu" phrase_var_name="setting_hide_main_menu" ordering="16" version_id="3.0.0beta1">0</setting>
        <setting group="registration" module_id="user" is_hidden="0" type="boolean" var_name="new_user_terms_confirmation" phrase_var_name="setting_new_user_terms_confirmation" ordering="18" version_id="3.0.0beta3">0</setting>
        <setting group="" module_id="user" is_hidden="0" type="boolean" var_name="require_basic_field" phrase_var_name="setting_require_basic_field" ordering="20" version_id="4.2.0">0</setting>
        <setting group="registration" module_id="user" is_hidden="0" type="boolean" var_name="multi_step_registration_form" phrase_var_name="setting_multi_step_registration_form" ordering="1" version_id="2.0.0alpha2">0</setting>
        <setting group="general" module_id="user" is_hidden="0" type="boolean" var_name="hide_recommended_user_block" phrase_var_name="setting_hide_recommended_user_block" ordering="15" version_id="4.4.0">0</setting>
        <setting group="" module_id="user" is_hidden="0" type="boolean" var_name="captcha_on_login" phrase_var_name="setting_captcha_on_login" ordering="16" version_id="4.4.0">0</setting>
        <setting group="registration" module_id="user" is_hidden="0" type="boolean" var_name="signup_repeat_password" phrase_var_name="setting_signup_repeat_password" ordering="6" version_id="4.6.0">0</setting>
        <setting group="" module_id="user" is_hidden="0" type="integer" var_name="resend_verification_email_delay_time" phrase_var_name="setting_resend_verification_email_delay_time" ordering="21" version_id="4.7.3">15</setting>
        <setting group="" module_id="user" is_hidden="0" type="boolean" var_name="logout_after_change_phone_number" phrase_var_name="setting_logout_after_change_phone_number" ordering="1" version_id="4.8.2">1</setting>
        <!-- new setting in 4.8.3 -->
        <setting group="registration" module_id="user" is_hidden="0" type="integer" var_name="on_register_user_group" phrase_var_name="setting_on_register_user_group" ordering="22" version_id="4.8.3">2</setting>
        <!-- new setting in 4.8.4 -->
        <setting group="" module_id="user" is_hidden="0" type="string" var_name="redirect_after_logout" phrase_var_name="setting_redirect_after_logout" ordering="3" version_id="4.8.4" />
        <setting group="" module_id="user" is_hidden="0" type="boolean" var_name="enable_feed_user_update_profile" phrase_var_name="setting_enable_feed_user_update_profile" ordering="4" version_id="4.8.4">1</setting>
        <setting group="" module_id="user" is_hidden="0" type="boolean" var_name="enable_feed_user_update_relationship" phrase_var_name="setting_enable_feed_user_update_relationship" ordering="5" version_id="4.8.4">1</setting>

        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_subject_pending_approval_user" phrase_var_name="setting_user_setting_subject_pending_approval_user" ordering="23" version_id="4.8.4"><![CDATA[{_p var="pending_approval"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_content_pending_approval_user" phrase_var_name="setting_user_setting_content_pending_approval_user" ordering="24" version_id="4.8.4"><![CDATA[{_p var="we_are_reviewing_your_account_and_pending_approval"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_subject_approve_user" phrase_var_name="setting_user_setting_subject_approve_user" ordering="25" version_id="4.8.4"><![CDATA[{_p var="account_approved"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_content_approve_user" phrase_var_name="setting_user_setting_content_approve_user" ordering="26" version_id="4.8.4"><![CDATA[{_p var="your_account_has_been_approved_on_site_title"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_subject_deny_user" phrase_var_name="setting_user_setting_subject_deny_user" ordering="27" version_id="4.8.4"><![CDATA[{_p var="deny_mail_subject"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_content_deny_user" phrase_var_name="setting_user_setting_content_deny_user" ordering="28" version_id="4.8.4"><![CDATA[{_p var="deny_mail_message"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_subject_inactive_user" phrase_var_name="setting_user_setting_subject_inactive_user" ordering="29" version_id="4.8.4"><![CDATA[{_p var="inactive_member_email_subject"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_content_inactive_user" phrase_var_name="setting_user_setting_content_inactive_user" ordering="30" version_id="4.8.4"><![CDATA[{_p var="inactive_member_email_body"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_subject_like_status" phrase_var_name="setting_user_setting_subject_like_status" ordering="31" version_id="4.8.4"><![CDATA[{_p var="full_name_liked_your_status_update_content"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_content_like_status" phrase_var_name="setting_user_setting_content_like_status" ordering="32" version_id="4.8.4"><![CDATA[{_p var="full_name_liked_your_status_update_message"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_subject_like_another_status" phrase_var_name="setting_user_setting_subject_like_another_status" ordering="33" version_id="4.8.4"><![CDATA[{_p var="full_name_liked_full_name2_status_update"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_content_like_another_status" phrase_var_name="setting_user_setting_content_like_another_status" ordering="34" version_id="4.8.4"><![CDATA[{_p var="full_name_liked_full_name2_status_update_message"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_subject_comment_on_other_user_status" phrase_var_name="setting_user_setting_subject_comment_on_other_user_status" ordering="35" version_id="4.8.4"><![CDATA[{_p var="full_name_commented_on_one_of_other_full_name_s_status_updates"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_content_comment_on_other_user_status" phrase_var_name="setting_user_setting_content_comment_on_other_user_status" ordering="36" version_id="4.8.4"><![CDATA[{_p var="full_name_commented_on_other_full_name_s_status_update_a_href_link_title_a_to_see_the_comment_thread_follow_the_link_below_a_href_link_link_a"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_subject_imported_user" phrase_var_name="setting_user_setting_subject_imported_user" ordering="37" version_id="4.8.4"><![CDATA[{_p var="import_user_send_mail_title"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_content_imported_user" phrase_var_name="setting_user_setting_content_imported_user" ordering="38" version_id="4.8.4"><![CDATA[{_p var="import_user_send_mail_content"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_subject_welcome_user" phrase_var_name="setting_user_setting_subject_welcome_user" ordering="39" version_id="4.8.4"><![CDATA[{_p var="welcome_email_subject"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_content_welcome_user" phrase_var_name="setting_user_setting_content_welcome_user" ordering="40" version_id="4.8.4"><![CDATA[{_p var="welcome_email_content"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_subject_tagged_on_status" phrase_var_name="setting_user_setting_subject_tagged_on_status" ordering="41" version_id="4.8.4"><![CDATA[{_p var="user_name_tagged_you_in_a_status_update"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_content_tagged_on_status" phrase_var_name="setting_user_setting_content_tagged_on_status" ordering="42" version_id="4.8.4"><![CDATA[{_p var="user_name_tagged_you_in_a_status_update_check_it_out"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_subject_comment_on_owner_status" phrase_var_name="setting_user_setting_subject_comment_on_owner_status" ordering="43" version_id="4.8.4"><![CDATA[{_p var="full_name_commented_on_your_status_update"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_content_comment_on_owner_status" phrase_var_name="setting_user_setting_content_comment_on_owner_status" ordering="44" version_id="4.8.4"><![CDATA[{_p var="full_name_commented_on_your_status_update_title_to_see_the_comment_thread_follow_the_link_below_a_href_link_link_a"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_subject_comment_on_their_own_status" phrase_var_name="setting_user_setting_subject_comment_on_their_own_status" ordering="45" version_id="4.8.4"><![CDATA[{_p var="full_name_commented_on_one_of_gender_status_updates"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_content_comment_on_their_own_status" phrase_var_name="setting_user_setting_content_comment_on_their_own_status" ordering="46" version_id="4.8.4"><![CDATA[{_p var="full_name_commented_on_gender_status_update_a_href_link_title_a_to_see_the_comment_thread_follow_the_link_below_a_href_link_link_a"}]]></setting>

        <!--new settings in 4.8.6-->
        <setting group="registration" module_id="user" is_hidden="0" type="integer" var_name="days_for_delete_pending_user_verification" phrase_var_name="setting_days_for_delete_pending_user_verification" ordering="3" version_id="4.8.6">0</setting>

        <!-- new setting in 4.8.7 -->
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_subject_verify_email" phrase_var_name="setting_user_setting_subject_verify_email" ordering="47" version_id="4.8.7"><![CDATA[{_p var="please_verify_your_email_for_site_title"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_content_verify_email" phrase_var_name="setting_user_setting_content_verify_email" ordering="48" version_id="4.8.7"><![CDATA[{_p var="you_registered_an_account_on_site_title_before_being_able_to_use_your_account_you_need_to_verify_that_this_is_your_email_address_by_clicking_here_a_href_link_link_a"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_subject_resend_verify_email" phrase_var_name="setting_user_setting_subject_resend_verify_email" ordering="49" version_id="4.8.7"><![CDATA[{_p var="email_verification_on_site_title"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_content_resend_verify_email" phrase_var_name="setting_user_setting_content_resend_verify_email" ordering="50" version_id="4.8.7"><![CDATA[{_p var="resend_email_on_site_title_before"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_subject_verify_email_after_changed" phrase_var_name="setting_user_setting_subject_verify_email_after_changed" ordering="51" version_id="4.8.7"><![CDATA[{_p var="email_verification_on_site_title"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_content_verify_email_after_changed" phrase_var_name="setting_user_setting_content_verify_email_after_changed" ordering="52" version_id="4.8.7"><![CDATA[{_p var="you_changed_email_on_site_title_before"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_subject_confirm_forgot_password" phrase_var_name="setting_user_setting_subject_confirm_forgot_password" ordering="53" version_id="4.8.7"><![CDATA[{_p var="password_request_for_site_title"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_content_confirm_forgot_password" phrase_var_name="setting_user_setting_content_confirm_forgot_password" ordering="54" version_id="4.8.7"><![CDATA[{_p var="you_have_requested_for_us_to_send_you_a_new_password_for_site_title"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_subject_new_password" phrase_var_name="setting_user_setting_subject_new_password" ordering="55" version_id="4.8.7"><![CDATA[{_p var="new_password_for_site_title"}]]></setting>
        <setting group="email" module_id="user" is_hidden="0" type="" var_name="user_setting_content_new_password" phrase_var_name="setting_user_setting_content_new_password" ordering="56" version_id="4.8.7"><![CDATA[{_p var="you_have_requested_for_us_to_send_you_a_new_password_for_site_title_with_password"}]]></setting>

        <!-- new setting in 4.8.8 -->
        <setting group="" module_id="user" is_hidden="0" type="integer" var_name="delay_time_for_next_promotion" phrase_var_name="setting_delay_time_for_next_promotion" ordering="57" version_id="4.8.8">1</setting>
        <!-- new setting in 4.8.9 -->
        <setting group="registration" module_id="user" is_hidden="0" type="boolean" var_name="required_strong_password" phrase_var_name="setting_required_strong_password" ordering="58" version_id="4.8.9">0</setting>
        <setting group="registration" module_id="user" is_hidden="0" type="integer" var_name="min_length_for_password" phrase_var_name="setting_min_length_for_password" ordering="59" version_id="4.8.9">6</setting>
        <setting group="registration" module_id="user" is_hidden="0" type="integer" var_name="max_length_for_password" phrase_var_name="setting_max_length_for_password" ordering="60" version_id="4.8.9">32</setting>
    </settings>
    <blocks>
        <block type_id="0" m_connection="user.browse" module_id="user" component="featured" location="1" is_active="1" ordering="2" disallow_access="" can_move="0">
            <title>Featured Members</title>
            <source_code />
            <source_parsed />
        </block>
        <block type_id="0" m_connection="core.index-visitor" module_id="user" component="register" location="2" is_active="1" ordering="1" disallow_access="" can_move="0">
            <title>User SignUp for Guests</title>
            <source_code />
            <source_parsed />
        </block>
        <block type_id="0" m_connection="core.index-visitor" module_id="user" component="featured" location="1" is_active="1" ordering="3" disallow_access="" can_move="0">
            <title>Featured Users for Guests</title>
            <source_code />
            <source_parsed />
        </block>
        <block type_id="0" m_connection="core.index-visitor" module_id="user" component="welcome" location="2" is_active="1" ordering="1" disallow_access="" can_move="0">
            <title>Welcome</title>
            <source_code />
            <source_parsed />
        </block>
        <block type_id="0" m_connection="user.browse" module_id="user" component="users-you-may-know" location="2" is_active="1" ordering="1" disallow_access="" can_move="0">
            <title>Users You May Know</title>
            <source_code />
            <source_parsed />
        </block>
        <block type_id="0" m_connection="user.browse" module_id="user" component="recently-active" location="2" is_active="1" ordering="2" disallow_access="" can_move="0">
            <title>Recently Active</title>
            <source_code />
            <source_parsed />
        </block>
    </blocks>
    <hooks>
        <hook module_id="user" hook_type="component" module="user" call_name="user.component_block_login_ajax_clean" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_admincp_group_setting_process" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_admincp_group_setting_clean" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_setting_clean" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_register_process_validation" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_register_clean" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_browse__call" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_validate__call" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_auth___construct" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_auth__call" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_group_setting_setting__call" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_group_setting_process__call" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_group_group__call" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_user_getuser_start" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_user_getuser_end" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_user_get_start" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_user_get_end" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_user_isuser" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_user_gender" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_user_getinlinesearch" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_user__call" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_field_process__call" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_space___construct" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_space_update" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_space__call" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_activity_update" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_activity__call" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_process_add_start" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_process_add_extra" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_process_add_end" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_process__call" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_controller_login_end" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_controller_register" added="1231838390" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_setting_process_validation" added="1231934944" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_password__call" added="1231934944" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_controller_setting" added="1231934944" version_id="2.0.0alpha1" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_index_clean" added="1240687633" version_id="2.0.0beta1" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_profile_clean" added="1240687633" version_id="2.0.0beta1" />
        <hook module_id="user" hook_type="component" module="user" call_name="user.component_block_setting_clean" added="1240687633" version_id="2.0.0beta1" />
        <hook module_id="user" hook_type="component" module="user" call_name="user.component_block_new_clean" added="1240687633" version_id="2.0.0beta1" />
        <hook module_id="user" hook_type="component" module="user" call_name="user.component_block_filter_clean" added="1240687633" version_id="2.0.0beta1" />
        <hook module_id="user" hook_type="component" module="user" call_name="user.component_block_browse_clean" added="1240687633" version_id="2.0.0beta1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_privacy_privacy__call" added="1240687633" version_id="2.0.0beta1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_privacy_process__call" added="1240687633" version_id="2.0.0beta1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_register__call" added="1240687633" version_id="2.0.0beta1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_group_process__call" added="1240687633" version_id="2.0.0beta1" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_controller_profile_form" added="1240687633" version_id="2.0.0beta1" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_block_setting_form" added="1240692039" version_id="2.0.0beta1" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_admincp_group_delete_clean" added="1242299564" version_id="2.0.0beta2" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_admincp_add_clean" added="1244973584" version_id="2.0.0beta4" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_admincp_browse_clean" added="1244973584" version_id="2.0.0beta4" />
        <hook module_id="user" hook_type="component" module="user" call_name="user.component_block_photo_clean" added="1244973584" version_id="2.0.0beta4" />
        <hook module_id="user" hook_type="component" module="user" call_name="user.component_block_signup_error_clean" added="1244973584" version_id="2.0.0beta4" />
        <hook module_id="user" hook_type="component" module="user" call_name="user.component_block_custom_clean" added="1244973584" version_id="2.0.0beta4" />
        <hook module_id="user" hook_type="component" module="user" call_name="user.component_block_block_clean" added="1244973584" version_id="2.0.0beta4" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_auth___construct_start" added="1244973584" version_id="2.0.0beta4" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_auth___construct_query" added="1244973584" version_id="2.0.0beta4" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_auth___construct_end" added="1244973584" version_id="2.0.0beta4" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_block_process__call" added="1244973584" version_id="2.0.0beta4" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_block_block__call" added="1244973584" version_id="2.0.0beta4" />
        <hook module_id="user" hook_type="component" module="user" call_name="user.component_block_admincp_setting_clean" added="1258389334" version_id="2.0.0rc8" />
        <hook module_id="user" hook_type="component" module="user" call_name="user.component_block_featured_clean" added="1258389334" version_id="2.0.0rc8" />
        <hook module_id="user" hook_type="component" module="user" call_name="user.component_block_password_clean" added="1258389334" version_id="2.0.0rc8" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_featured__call" added="1258389334" version_id="2.0.0rc8" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_featured_feature_start" added="1258389334" version_id="2.0.0rc8" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_featured_feature_end" added="1258389334" version_id="2.0.0rc8" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.controller_browse_filter" added="1259160644" version_id="2.0.0rc9" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_browse_genders" added="1259173633" version_id="2.0.0rc9" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_browse_filter" added="1259173633" version_id="2.0.0rc9" />
        <hook module_id="user" hook_type="component" module="user" call_name="user.block_login-block_process__start" added="1260366442" version_id="2.0.0rc11" />
        <hook module_id="user" hook_type="component" module="user" call_name="user.block_login-block_process__end" added="1260366442" version_id="2.0.0rc11" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_auth_login__start" added="1261572640" version_id="2.0.0" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_auth_login__no_user_name" added="1261572640" version_id="2.0.0" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_auth_login__password" added="1261572640" version_id="2.0.0" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_auth_login__cookie_start" added="1261572640" version_id="2.0.0" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_auth_login__cookie_end" added="1261572640" version_id="2.0.0" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_auth_login__end" added="1261572640" version_id="2.0.0" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_auth_logout__start" added="1261572640" version_id="2.0.0" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_auth_logout__end" added="1261572640" version_id="2.0.0" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_browse_genders_top_users" added="1261572640" version_id="2.0.0" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_controller_login_block__start" added="1261572640" version_id="2.0.0" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_controller_login_block__end" added="1261572640" version_id="2.0.0" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_process_add_updatestatus" added="1266260139" version_id="2.0.4" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_setting_process_check" added="1266260139" version_id="2.0.4" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.controller_login_login_failed" added="1266260139" version_id="2.0.4" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_logout-mobile_clean" added="1267629983" version_id="2.0.4" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_pending_clean" added="1271160844" version_id="2.0.5" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_verify_process_verify_pass" added="1276177474" version_id="2.0.5" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_promotion_process__call" added="1276177474" version_id="2.0.5" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_promotion_promotion__call" added="1276177474" version_id="2.0.5" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_auth_login_skip_email_verification" added="1276177474" version_id="2.0.5" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_auth_handlestatus" added="1276177474" version_id="2.0.5" />
        <hook module_id="user" hook_type="component" module="user" call_name="user.component_ajax_updatestatus" added="1276177474" version_id="2.0.5" />
        <hook module_id="user" hook_type="component" module="user" call_name="user.component_ajax_getregistrationstep_pass" added="1276177474" version_id="2.0.5" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_promotion_clean" added="1276177474" version_id="2.0.5" />
        <hook module_id="user" hook_type="component" module="user" call_name="user.component_verify_process_redirection" added="1276177474" version_id="2.0.5" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_admincp_promotion_add_clean" added="1276177474" version_id="2.0.5" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_admincp_promotion_index_clean" added="1276177474" version_id="2.0.5" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_process_add_check_1" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_process_update_start" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_process_update_1" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_process_update_end" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_process_updatesimple" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_process_updateusergroup" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_process_uploadimage" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_process_updateadvanced_start" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_process_updateadvanced_end" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_process_updatepassword" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_process_banuser" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_password_verifyrequest_start" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_password_verifyrequest_check_1" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_password_verifyrequest_end" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_cancellations_process_cancelaccount_invalid_password" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_callback_getnewsfeedstatus_start" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_callback_getnewsfeedphoto_start" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_callback_getnewsfeedjoined_start" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_user_getuserfields" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_photo_1" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_photo_2" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_photo_3" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_index_process" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_controller_photo_1" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_controller_photo_2" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_controller_photo_3" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_controller_photo_4" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_controller_photo_5" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_controller_photo_6" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_controller_photo_7" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_controller_photo_8" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_controller_photo_9" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_controller_photo_10" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_default_block_register_step2_1" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_default_block_register_step2_2" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_default_block_register_step2_3" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_default_block_register_step2_4" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_default_block_register_step2_5" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_default_block_register_step2_6" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_default_block_register_step2_7" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_default_block_register_step2_8" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_default_block_register_step1_1" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_default_block_register_step1_2" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_default_block_register_step1_3" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_default_block_register_step1_4" added="1286546859" version_id="2.0.7" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_admincp_add" added="1288281378" version_id="2.0.7" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_process_add_feed" added="1290072896" version_id="2.0.7" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_user_getforedit" added="1290072896" version_id="2.0.7" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_admincp_ban_clean" added="1298455495" version_id="2.0.8" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_browse_get__start" added="1298902308" version_id="2.0.8" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_browse_get__cnt" added="1298902308" version_id="2.0.8" />
        <hook module_id="user" hook_type="component" module="user" call_name="user.component_ajax_addviastatusupdate" added="1319729453" version_id="3.0.0rc1" />
        <hook module_id="user" hook_type="component" module="user" call_name="user.component_block_images_clean" added="1319729453" version_id="3.0.0rc1" />
        <hook module_id="user" hook_type="component" module="user" call_name="user.component_block_login_header_clean" added="1319729453" version_id="3.0.0rc1" />
        <hook module_id="user" hook_type="component" module="user" call_name="user.component_block_register_top_clean" added="1319729453" version_id="3.0.0rc1" />
        <hook module_id="user" hook_type="component" module="user" call_name="user.component_block_tooltip_clean" added="1319729453" version_id="3.0.0rc1" />
        <hook module_id="user" hook_type="component" module="user" call_name="user.component_block_welcome_clean" added="1319729453" version_id="3.0.0rc1" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_register_1" added="1319729453" version_id="3.0.0rc1" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_register_2" added="1319729453" version_id="3.0.0rc1" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_register_3" added="1319729453" version_id="3.0.0rc1" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_register_4" added="1319729453" version_id="3.0.0rc1" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_register_5" added="1319729453" version_id="3.0.0rc1" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_register_6" added="1319729453" version_id="3.0.0rc1" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_register_7" added="1319729453" version_id="3.0.0rc1" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_register_8" added="1319729453" version_id="3.0.0rc1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_api__call" added="1319729453" version_id="3.0.0rc1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_password_verifyrequest_2" added="1319729453" version_id="3.0.0rc1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_password_verifyrequest_3" added="1319729453" version_id="3.0.0rc1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_password_verifyrequest_4" added="1319729453" version_id="3.0.0rc1" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template.login_header_set_var" added="1319729453" version_id="3.0.0rc1" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template.login_header_custom" added="1319729453" version_id="3.0.0rc1" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_default_block_register_step1_5" added="1319729453" version_id="3.0.0rc1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_browse_get__start_no_return" added="1320054335" version_id="3.0.0rc1" />
        <hook module_id="user" hook_type="component" module="user" call_name="user.component_block_tooltip_1" added="1323345487" version_id="3.0.0" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_controller_profile_form_onsubmit" added="1323345487" version_id="3.0.0" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_block_tooltip_1" added="1323345637" version_id="3.0.0" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_block_tooltip_3" added="1323345637" version_id="3.0.0" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_block_tooltip_5" added="1323345637" version_id="3.0.0" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_block_tooltip_2" added="1323345637" version_id="3.0.0" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_browse_filter_process" added="1327938973" version_id="3.0.1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_browse_get__end" added="1327938973" version_id="3.0.1" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_setting_settitle" added="1335951260" version_id="3.2.0" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_process_add_1" added="1335951260" version_id="3.2.0" />
        <hook module_id="user" hook_type="template" module="user" call_name="user.template_controller_register_pre_captcha" added="1358258443" version_id="3.5.0beta1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_process_add_updatestatus_end" added="1358258443" version_id="3.5.0beta1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_user_getcurrency__1" added="1361532353" version_id="3.5.0" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_profile__1" added="1363075699" version_id="3.5.0" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_profile__2" added="1363075699" version_id="3.5.0" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_profile__3" added="1363075699" version_id="3.5.0" />
        <hook module_id="user" hook_type="controller" module="user" call_name="user.component_controller_browse__1" added="1363075699" version_id="3.5.0" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_activity_update_1" added="1372931660" version_id="3.6.0" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_browse_get_1" added="1378372973" version_id="3.7.0rc1" />
        <hook module_id="user" hook_type="service" module="user" call_name="user.service_featured_get_1" added="1378374384" version_id="3.7.0rc1" />
        <hook module_id="user" hook_type="component" module="user" call_name="user.component_block_login_header" added="1378803594" version_id="3.7.0rc1" />
    </hooks>
    <components>
        <component module_id="user" component="ajax" m_connection="" module="user" is_controller="0" is_block="0" is_active="1" />
        <component module_id="user" component="login-block" m_connection="" module="user" is_controller="0" is_block="1" is_active="1" />
        <component module_id="user" component="login" m_connection="user.login" module="user" is_controller="1" is_block="0" is_active="1" />
        <component module_id="user" component="admincp.group.index" m_connection="" module="user" is_controller="0" is_block="0" is_active="1" />
        <component module_id="user" component="admincp.group.add" m_connection="" module="user" is_controller="0" is_block="0" is_active="1" />
        <component module_id="user" component="logout" m_connection="user.logout" module="user" is_controller="1" is_block="0" is_active="1" />
        <component module_id="user" component="register" m_connection="user.register" module="user" is_controller="1" is_block="0" is_active="1" />
        <component module_id="user" component="admincp.group.setting" m_connection="" module="user" is_controller="0" is_block="0" is_active="1" />
        <component module_id="user" component="login-ajax" m_connection="" module="user" is_controller="0" is_block="1" is_active="1" />
        <component module_id="user" component="browse" m_connection="user.browse" module="user" is_controller="1" is_block="0" is_active="1" />
        <component module_id="user" component="setting" m_connection="user.setting" module="user" is_controller="1" is_block="0" is_active="1" />
        <component module_id="user" component="lost-password" m_connection="user.lost-password" module="user" is_controller="1" is_block="0" is_active="1" />
        <component module_id="user" component="photo" m_connection="user.photo" module="user" is_controller="1" is_block="0" is_active="1" />
        <component module_id="user" component="password.request" m_connection="user.password.request" module="user" is_controller="1" is_block="0" is_active="1" />
        <component module_id="user" component="password.verify" m_connection="user.password.verify" module="user" is_controller="1" is_block="0" is_active="1" />
        <component module_id="user" component="status" m_connection="" module="user" is_controller="0" is_block="1" is_active="1" />
        <component module_id="user" component="register" m_connection="" module="user" is_controller="0" is_block="1" is_active="1" />
        <component module_id="user" component="filter" m_connection="" module="user" is_controller="0" is_block="1" is_active="1" />
        <component module_id="user" component="privacy" m_connection="user.privacy" module="user" is_controller="1" is_block="0" is_active="1" />
        <component module_id="user" component="featured" m_connection="" module="user" is_controller="0" is_block="1" is_active="1" />
        <component module_id="user" component="register-top" m_connection="" module="user" is_controller="0" is_block="1" is_active="1" />
        <component module_id="user" component="cf_about_me" m_connection="" module="user" is_controller="0" is_block="1" is_active="1" />
        <component module_id="user" component="cf_who_i_d_like_to_meet" m_connection="" module="user" is_controller="0" is_block="1" is_active="1" />
        <component module_id="user" component="cf_interests" m_connection="" module="user" is_controller="0" is_block="1" is_active="1" />
        <component module_id="user" component="cf_music" m_connection="" module="user" is_controller="0" is_block="1" is_active="1" />
        <component module_id="user" component="cf_movies" m_connection="" module="user" is_controller="0" is_block="1" is_active="1" />
        <component module_id="user" component="cf_smoker" m_connection="" module="user" is_controller="0" is_block="1" is_active="1" />
        <component module_id="user" component="cf_drinker" m_connection="" module="user" is_controller="0" is_block="1" is_active="1" />
        <component module_id="user" component="welcome" m_connection="" module="user" is_controller="0" is_block="1" is_active="1" />
        <component module_id="user" component="users-you-may-know" m_connection="" module="user" is_controller="0" is_block="1" is_active="1" />
        <component module_id="user" component="recently-active" m_connection="" module="user" is_controller="0" is_block="1" is_active="1" />
    </components>
    <crons>
        <cron module_id="user" type_id="2" every="1" name="Delete Pending Verification Users"><![CDATA[Phpfox::getService('user.verify.process')->deletePendingVerifications();]]></cron>
    </crons>
    <stats>
        <stat module_id="user" phrase_var="user.stat_title_1" stat_link="user.browse" stat_image="user.png" is_active="1"><![CDATA[$this->database()
->select('COUNT(u.user_id)')
->from(Phpfox::getT('user'), 'u')
->join(Phpfox::getT('user_field'), 'uf', 'uf.user_id = u.user_id')
->where('u.status_id = 0 AND u.view_id = 0')
->execute('getSlaveField');]]></stat>
    </stats>
    <custom_field>
        <field group_name="user.custom_group_about_me" field_name="about_me" module_id="user" type_id="user_main" phrase_var_name="user.custom_about_me" type_name="MEDIUMTEXT" var_type="textarea" is_active="1" is_required="0" ordering="1" is_search="1"/>
    </custom_field>
    <custom_group>
        <group module_id="user" type_id="user_profile" phrase_var_name="user.custom_group_about_me" is_active="1" ordering="1" />
    </custom_group>
    <user_group_settings>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="1" user="0" guest="0" staff="0" module="user" ordering="0">can_add_user_group_setting</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="1" user="1" guest="0" staff="1" module="user" ordering="0">can_control_profile_privacy</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="1" user="1" guest="0" staff="1" module="user" ordering="0">can_control_notification_privacy</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="1" user="0" guest="0" staff="1" module="user" ordering="0">can_override_user_privacy</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="0" user="0" guest="0" staff="0" module="user" ordering="0">require_profile_image</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="1" user="1" guest="0" staff="1" module="user" ordering="0">can_edit_gender_setting</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="1" user="1" guest="1" staff="1" module="user" ordering="0">can_add_custom_gender</setting>
        <setting is_admin_setting="0" module_id="user" type="string" admin="" user="" guest="" staff="" module="user" ordering="0">custom_name_field</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="1" user="1" guest="0" staff="1" module="user" ordering="0">can_edit_dob</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="1" user="0" guest="0" staff="1" module="user" ordering="0">can_edit_users</setting>
        <setting is_admin_setting="1" module_id="user" type="boolean" admin="1" user="1" guest="0" staff="1" module="user" ordering="0">can_stay_logged_in</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="1" user="0" guest="0" staff="1" module="user" ordering="0">can_change_other_user_picture</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="1" user="0" guest="0" staff="1" module="user" ordering="0">can_edit_other_user_privacy</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="1" user="0" guest="0" staff="0" module="user" ordering="0">can_change_own_user_name</setting>
        <setting is_admin_setting="0" module_id="user" type="integer" admin="3" user="3" guest="3" staff="3" module="user" ordering="0">total_times_can_change_user_name</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="1" user="1" guest="0" staff="1" module="user" ordering="0">can_block_other_members</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="0" user="1" guest="1" staff="0" module="user" ordering="0">can_be_blocked_by_others</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="true" user="false" guest="false" staff="false" module="user" ordering="0">can_feature</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="true" user="true" guest="false" staff="true" module="user" ordering="0">can_change_email</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="true" user="false" guest="false" staff="true" module="user" ordering="0">can_verify_others_emails</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="false" user="true" guest="false" staff="false" module="user" ordering="0">can_delete_own_account</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="true" user="true" guest="false" staff="true" module="user" ordering="0">can_change_own_full_name</setting>
        <setting is_admin_setting="0" module_id="user" type="integer" admin="0" user="3" guest="1" staff="0" module="user" ordering="0">total_times_can_change_own_full_name</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="true" user="false" guest="false" staff="false" module="user" ordering="0">can_delete_others_account</setting>
        <setting is_admin_setting="0" module_id="user" type="integer" admin="0" user="0" guest="0" staff="0" module="user" ordering="0">total_upload_space</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="0" user="0" guest="0" staff="0" module="user" ordering="0">force_cropping_tool_for_photos</setting>
        <setting is_admin_setting="0" module_id="user" type="integer" admin="8192" user="8192" guest="8192" staff="8192" module="user" ordering="0">max_upload_size_profile_photo</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="1" user="1" guest="1" staff="1" module="user" ordering="0">can_search_user_gender</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="1" user="1" guest="1" staff="1" module="user" ordering="0">can_search_user_age</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="1" user="1" guest="1" staff="1" module="user" ordering="0">can_browse_users_in_public</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="1" user="0" guest="0" staff="0" module="user" ordering="0">can_edit_user_group_membership</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="1" user="0" guest="0" staff="1" module="user" ordering="0">can_view_if_a_user_is_invisible</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="1" user="1" guest="0" staff="1" module="user" ordering="0">can_edit_currency</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="1" user="0" guest="0" staff="0" module="user" ordering="0">can_manage_user_group_settings</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="1" user="0" guest="0" staff="0" module="user" ordering="0">can_edit_user_group</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="1" user="0" guest="0" staff="0" module="user" ordering="0">can_delete_user_group</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="false" user="false" guest="false" staff="false" module="user" ordering="0">can_member_snoop</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="false" user="false" guest="false" staff="false" module="user" ordering="0">hide_from_browse</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="true" user="true" guest="true" staff="true" module="user" ordering="0">can_search_by_zip</setting>
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="true" user="true" guest="false" staff="true" module="user" ordering="0">can_change_phone</setting>
        <!--new setting 4.8.9 -->
        <setting is_admin_setting="0" module_id="user" type="boolean" admin="true" user="true" guest="false" staff="true" module="user" ordering="0">can_use_2step_verification</setting>
    </user_group_settings>
    <tables><![CDATA[a:35:{s:11:"phpfox_user";a:3:{s:7:"COLUMNS";a:37:{s:7:"user_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:15:"profile_page_id";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:9:"server_id";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:13:"user_group_id";a:4:{i:0;s:5:"USINT";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:9:"status_id";a:4:{i:0;s:6:"TINT:2";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:7:"view_id";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:9:"user_name";a:4:{i:0;s:9:"VCHAR:100";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:9:"full_name";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:8:"password";a:4:{i:0;s:9:"VCHAR:150";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:13:"password_salt";a:4:{i:0;s:6:"CHAR:3";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:5:"email";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:12:"phone_number";a:4:{i:0;s:8:"VCHAR:50";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:17:"full_phone_number";a:4:{i:0;s:8:"VCHAR:50";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:6:"gender";a:4:{i:0;s:6:"TINT:3";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:8:"birthday";a:4:{i:0;s:7:"CHAR:10";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:15:"birthday_search";a:4:{i:0;s:4:"BINT";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:11:"country_iso";a:4:{i:0;s:6:"CHAR:2";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:11:"language_id";a:4:{i:0;s:8:"VCHAR:12";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:8:"style_id";a:4:{i:0;s:5:"USINT";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:9:"time_zone";a:4:{i:0;s:6:"CHAR:4";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:9:"dst_check";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:6:"joined";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:10:"last_login";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:13:"last_activity";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:10:"user_image";a:4:{i:0;s:8:"VCHAR:75";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:8:"hide_tip";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:6:"status";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:10:"footer_bar";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:14:"invite_user_id";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:7:"im_beep";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:7:"im_hide";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:12:"is_invisible";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:10:"total_spam";a:4:{i:0;s:5:"USINT";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:15:"last_ip_address";a:4:{i:0;s:8:"VCHAR:50";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:9:"feed_sort";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:13:"custom_gender";a:4:{i:0;s:5:"MTEXT";i:1;s:0:"";i:2;s:0:"";i:3;s:3:"YES";}s:21:"two_step_verification";a:4:{i:0;s:6:"TINT:1";i:1;i:0;i:2;s:0:"";i:3;s:2:"NO";}}s:11:"PRIMARY_KEY";s:7:"user_id";s:4:"KEYS";a:11:{s:9:"user_name";a:2:{i:0;s:5:"INDEX";i:1;s:9:"user_name";}s:5:"email";a:2:{i:0;s:5:"INDEX";i:1;s:5:"email";}s:10:"user_image";a:2:{i:0;s:5:"INDEX";i:1;s:10:"user_image";}s:13:"user_group_id";a:2:{i:0;s:5:"INDEX";i:1;s:13:"user_group_id";}s:11:"user_status";a:2:{i:0;s:5:"INDEX";i:1;s:9:"status_id";}s:10:"total_spam";a:2:{i:0;s:5:"INDEX";i:1;s:10:"total_spam";}s:9:"status_id";a:2:{i:0;s:5:"INDEX";i:1;a:2:{i:0;s:9:"status_id";i:1;s:7:"view_id";}}s:11:"public_feed";a:2:{i:0;s:5:"INDEX";i:1;a:3:{i:0;s:9:"status_id";i:1;s:7:"view_id";i:2;s:13:"last_activity";}}s:11:"status_id_2";a:2:{i:0;s:5:"INDEX";i:1;a:3:{i:0;s:9:"status_id";i:1;s:7:"view_id";i:2;s:9:"full_name";}}s:7:"page_id";a:2:{i:0;s:5:"INDEX";i:1;s:15:"profile_page_id";}s:7:"user_id";a:2:{i:0;s:5:"INDEX";i:1;a:2:{i:0;s:7:"user_id";i:1;s:9:"status_id";}}}}s:20:"phpfox_user_activity";a:2:{s:7:"COLUMNS";a:18:{s:7:"user_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:13:"activity_blog";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:19:"activity_attachment";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:16:"activity_comment";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:14:"activity_photo";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:17:"activity_bulletin";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:13:"activity_poll";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:15:"activity_invite";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:14:"activity_forum";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:14:"activity_video";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:14:"activity_total";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:15:"activity_points";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:13:"activity_quiz";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:19:"activity_music_song";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:20:"activity_marketplace";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:14:"activity_event";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:14:"activity_pages";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:22:"activity_points_gifted";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}}s:4:"KEYS";a:1:{s:7:"user_id";a:2:{i:0;s:6:"UNIQUE";i:1;s:7:"user_id";}}}s:19:"phpfox_user_blocked";a:3:{s:7:"COLUMNS";a:5:{s:8:"block_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:7:"user_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:13:"block_user_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:10:"time_stamp";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:10:"ip_address";a:4:{i:0;s:8:"VCHAR:50";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}}s:11:"PRIMARY_KEY";s:8:"block_id";s:4:"KEYS";a:2:{s:7:"user_id";a:2:{i:0;s:5:"INDEX";i:1;a:2:{i:0;s:7:"user_id";i:1;s:13:"block_user_id";}}s:9:"user_id_2";a:2:{i:0;s:5:"INDEX";i:1;s:7:"user_id";}}}s:17:"phpfox_user_count";a:2:{s:7:"COLUMNS";a:7:{s:7:"user_id";a:4:{i:0;s:7:"UINT:11";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:8:"mail_new";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:15:"comment_pending";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:14:"friend_request";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:12:"group_invite";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:12:"event_invite";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:18:"marketplace_invite";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}}s:4:"KEYS";a:1:{s:7:"user_id";a:2:{i:0;s:6:"UNIQUE";i:1;s:7:"user_id";}}}s:15:"phpfox_user_css";a:3:{s:7:"COLUMNS";a:6:{s:6:"css_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:7:"user_id";a:4:{i:0;s:7:"UINT:11";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:12:"css_selector";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:12:"css_property";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:9:"css_value";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:8:"ordering";a:4:{i:0;s:5:"USINT";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}}s:11:"PRIMARY_KEY";s:6:"css_id";s:4:"KEYS";a:1:{s:7:"user_id";a:2:{i:0;s:5:"INDEX";i:1;s:7:"user_id";}}}s:20:"phpfox_user_css_code";a:2:{s:7:"COLUMNS";a:2:{s:7:"user_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:8:"css_code";a:4:{i:0;s:5:"MTEXT";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}}s:4:"KEYS";a:1:{s:7:"user_id";a:2:{i:0;s:6:"UNIQUE";i:1;s:7:"user_id";}}}s:18:"phpfox_user_custom";a:2:{s:7:"COLUMNS";a:6:{s:7:"user_id";a:4:{i:0;s:7:"UINT:11";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:11:"cf_about_me";a:4:{i:0;s:5:"MTEXT";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:20:"cf_record_label_name";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:20:"cf_record_label_type";a:4:{i:0;s:9:"VCHAR:150";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:22:"cf_relationship_status";a:4:{i:0;s:9:"VCHAR:150";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:23:"cf_which_best_describes";a:4:{i:0;s:9:"VCHAR:150";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}}s:4:"KEYS";a:1:{s:7:"user_id";a:2:{i:0;s:6:"UNIQUE";i:1;s:7:"user_id";}}}s:24:"phpfox_user_custom_value";a:2:{s:7:"COLUMNS";a:6:{s:7:"user_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:11:"cf_about_me";a:4:{i:0;s:5:"MTEXT";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:20:"cf_record_label_name";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:20:"cf_record_label_type";a:4:{i:0;s:9:"VCHAR:150";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:22:"cf_relationship_status";a:4:{i:0;s:5:"USINT";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:23:"cf_which_best_describes";a:4:{i:0;s:9:"VCHAR:150";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}}s:4:"KEYS";a:1:{s:7:"user_id";a:2:{i:0;s:6:"UNIQUE";i:1;s:7:"user_id";}}}s:21:"phpfox_user_dashboard";a:3:{s:7:"COLUMNS";a:6:{s:12:"dashboard_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:7:"user_id";a:4:{i:0;s:7:"UINT:11";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:8:"cache_id";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:8:"block_id";a:4:{i:0;s:8:"VCHAR:75";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:9:"is_hidden";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:8:"ordering";a:4:{i:0;s:5:"USINT";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}}s:11:"PRIMARY_KEY";s:12:"dashboard_id";s:4:"KEYS";a:1:{s:7:"user_id";a:2:{i:0;s:5:"INDEX";i:1;a:2:{i:0;s:7:"user_id";i:1;s:9:"is_hidden";}}}}s:18:"phpfox_user_delete";a:3:{s:7:"COLUMNS";a:6:{s:9:"delete_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:10:"phrase_var";a:4:{i:0;s:9:"VCHAR:250";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:9:"module_id";a:4:{i:0;s:8:"VCHAR:75";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:10:"product_id";a:4:{i:0;s:8:"VCHAR:25";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:8:"ordering";a:4:{i:0;s:4:"UINT";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:9:"is_active";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"1";i:2;s:0:"";i:3;s:2:"NO";}}s:11:"PRIMARY_KEY";s:9:"delete_id";s:4:"KEYS";a:1:{s:9:"is_active";a:2:{i:0;s:5:"INDEX";i:1;s:9:"is_active";}}}s:27:"phpfox_user_delete_feedback";a:3:{s:7:"COLUMNS";a:8:{s:11:"feedback_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:10:"user_email";a:4:{i:0;s:9:"VCHAR:100";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:9:"full_name";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:13:"user_group_id";a:4:{i:0;s:5:"USINT";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:13:"feedback_text";a:4:{i:0;s:4:"TEXT";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:13:"reasons_given";a:4:{i:0;s:4:"TEXT";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:10:"time_stamp";a:4:{i:0;s:7:"UINT:11";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:10:"user_phone";a:4:{i:0;s:8:"VCHAR:50";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}}s:11:"PRIMARY_KEY";s:11:"feedback_id";s:4:"KEYS";a:1:{s:10:"user_email";a:2:{i:0;s:5:"INDEX";i:1;s:10:"user_email";}}}s:24:"phpfox_user_design_order";a:3:{s:7:"COLUMNS";a:6:{s:15:"design_order_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:7:"user_id";a:4:{i:0;s:7:"UINT:11";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:8:"cache_id";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:8:"block_id";a:4:{i:0;s:8:"VCHAR:75";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:9:"is_hidden";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:8:"ordering";a:4:{i:0;s:5:"USINT";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}}s:11:"PRIMARY_KEY";s:15:"design_order_id";s:4:"KEYS";a:1:{s:7:"user_id";a:2:{i:0;s:5:"INDEX";i:1;s:7:"user_id";}}}s:20:"phpfox_user_featured";a:2:{s:7:"COLUMNS";a:2:{s:7:"user_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:8:"ordering";a:4:{i:0;s:6:"TINT:2";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}}s:11:"PRIMARY_KEY";s:7:"user_id";}s:17:"phpfox_user_field";a:2:{s:7:"COLUMNS";a:43:{s:7:"user_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:10:"first_name";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:9:"last_name";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:9:"signature";a:4:{i:0;s:5:"MTEXT";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:15:"signature_clean";a:4:{i:0;s:5:"MTEXT";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:17:"designer_style_id";a:4:{i:0;s:5:"USINT";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:13:"total_comment";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:10:"total_view";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:12:"total_friend";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:10:"total_post";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:18:"total_profile_song";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:11:"total_score";a:4:{i:0;s:9:"DECIMAL:4";i:1;s:4:"0.00";i:2;s:0:"";i:3;s:2:"NO";}s:12:"total_rating";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:17:"total_user_change";a:4:{i:0;s:6:"TINT:3";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:22:"total_full_name_change";a:4:{i:0;s:6:"TINT:3";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:16:"country_child_id";a:4:{i:0;s:4:"UINT";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:13:"city_location";a:4:{i:0;s:9:"VCHAR:100";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:11:"postal_code";a:4:{i:0;s:8:"VCHAR:20";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:12:"subscribe_id";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:11:"dob_setting";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:14:"birthday_range";a:4:{i:0;s:6:"CHAR:4";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:9:"rss_count";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:8:"css_hash";a:4:{i:0;s:7:"CHAR:32";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:16:"newsletter_state";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:10:"in_admincp";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:16:"default_currency";a:4:{i:0;s:7:"VCHAR:3";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:10:"total_blog";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:11:"total_video";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:10:"total_poll";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:10:"total_quiz";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:11:"total_event";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:10:"total_song";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:13:"total_listing";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:11:"total_photo";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:11:"total_pages";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:21:"brute_force_locked_at";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:16:"relation_data_id";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:13:"relation_with";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:11:"cover_photo";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:15:"cover_photo_top";a:4:{i:0;s:7:"VCHAR:5";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:12:"use_timeline";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:12:"landing_page";a:4:{i:0;s:8:"VCHAR:50";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:15:"location_latlng";a:4:{i:0;s:9:"VCHAR:100";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}}s:4:"KEYS";a:2:{s:7:"user_id";a:2:{i:0;s:6:"UNIQUE";i:1;s:7:"user_id";}s:17:"designer_style_id";a:2:{i:0;s:5:"INDEX";i:1;s:17:"designer_style_id";}}}s:19:"phpfox_user_gateway";a:3:{s:7:"COLUMNS";a:3:{s:7:"user_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:10:"gateway_id";a:4:{i:0;s:8:"VCHAR:75";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:14:"gateway_detail";a:4:{i:0;s:5:"MTEXT";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}}s:11:"PRIMARY_KEY";a:2:{i:0;s:7:"user_id";i:1;s:10:"gateway_id";}s:4:"KEYS";a:1:{s:7:"user_id";a:2:{i:0;s:5:"INDEX";i:1;s:7:"user_id";}}}s:17:"phpfox_user_group";a:3:{s:7:"COLUMNS";a:7:{s:13:"user_group_id";a:4:{i:0;s:6:"TINT:3";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:10:"inherit_id";a:4:{i:0;s:6:"TINT:3";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:5:"title";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:10:"is_special";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:6:"prefix";a:4:{i:0;s:8:"VCHAR:75";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:6:"suffix";a:4:{i:0;s:8:"VCHAR:75";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:8:"icon_ext";a:4:{i:0;s:7:"VCHAR:6";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}}s:11:"PRIMARY_KEY";s:13:"user_group_id";s:4:"KEYS";a:2:{s:13:"user_group_id";a:2:{i:0;s:5:"INDEX";i:1;s:13:"user_group_id";}s:10:"is_special";a:2:{i:0;s:5:"INDEX";i:1;s:10:"is_special";}}}s:24:"phpfox_user_group_custom";a:3:{s:7:"COLUMNS";a:5:{s:10:"setting_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:13:"user_group_id";a:4:{i:0;s:6:"TINT:4";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:9:"module_id";a:4:{i:0;s:8:"VCHAR:75";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:4:"name";a:4:{i:0;s:9:"VCHAR:250";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:13:"default_value";a:4:{i:0;s:4:"TEXT";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}}s:11:"PRIMARY_KEY";s:10:"setting_id";s:4:"KEYS";a:1:{s:13:"user_group_id";a:2:{i:0;s:5:"INDEX";i:1;a:3:{i:0;s:13:"user_group_id";i:1;s:9:"module_id";i:2;s:4:"name";}}}}s:25:"phpfox_user_group_setting";a:3:{s:7:"COLUMNS";a:14:{s:10:"setting_id";a:4:{i:0;s:4:"UINT";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:16:"is_admin_setting";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:9:"is_hidden";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:9:"module_id";a:4:{i:0;s:8:"VCHAR:75";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:10:"product_id";a:4:{i:0;s:8:"VCHAR:64";i:1;s:6:"phpfox";i:2;s:0:"";i:3;s:2:"NO";}s:4:"name";a:4:{i:0;s:9:"VCHAR:250";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:7:"type_id";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:13:"default_admin";a:4:{i:0;s:4:"TEXT";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:12:"default_user";a:4:{i:0;s:4:"TEXT";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:13:"default_guest";a:4:{i:0;s:4:"TEXT";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:13:"default_staff";a:4:{i:0;s:4:"TEXT";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:8:"ordering";a:4:{i:0;s:6:"TINT:3";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:13:"option_values";a:4:{i:0;s:5:"MTEXT";i:1;s:1:"0";i:2;s:0:"";i:3;s:3:"YES";}s:15:"disallow_access";a:4:{i:0;s:4:"TEXT";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}}s:11:"PRIMARY_KEY";s:10:"setting_id";s:4:"KEYS";a:2:{s:10:"product_id";a:2:{i:0;s:5:"INDEX";i:1;s:10:"product_id";}s:9:"module_id";a:2:{i:0;s:5:"INDEX";i:1;s:9:"module_id";}}}s:20:"phpfox_user_inactive";a:2:{s:7:"COLUMNS";a:7:{s:6:"job_id";a:4:{i:0;s:7:"UINT:11";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:13:"days_inactive";a:4:{i:0;s:7:"UINT:11";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:10:"batch_size";a:4:{i:0;s:7:"UINT:11";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:11:"page_number";a:4:{i:0;s:7:"UINT:11";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:12:"date_started";a:4:{i:0;s:7:"UINT:11";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:11:"total_users";a:4:{i:0;s:7:"UINT:11";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:7:"user_id";a:4:{i:0;s:7:"UINT:11";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}}s:11:"PRIMARY_KEY";s:6:"job_id";}s:14:"phpfox_user_ip";a:3:{s:7:"COLUMNS";a:5:{s:6:"ip_log";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:7:"user_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:7:"type_id";a:4:{i:0;s:8:"VCHAR:20";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:10:"ip_address";a:4:{i:0;s:8:"VCHAR:50";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:10:"time_stamp";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}}s:11:"PRIMARY_KEY";s:6:"ip_log";s:4:"KEYS";a:4:{s:7:"user_id";a:2:{i:0;s:5:"INDEX";i:1;s:7:"user_id";}s:10:"ip_address";a:2:{i:0;s:5:"INDEX";i:1;s:10:"ip_address";}s:9:"user_id_2";a:2:{i:0;s:5:"INDEX";i:1;a:2:{i:0;s:7:"user_id";i:1;s:10:"ip_address";}}s:9:"user_id_3";a:2:{i:0;s:5:"INDEX";i:1;a:2:{i:0;s:7:"user_id";i:1;s:7:"type_id";}}}}s:24:"phpfox_user_notification";a:3:{s:7:"COLUMNS";a:5:{s:15:"notification_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:7:"user_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:17:"user_notification";a:4:{i:0;s:9:"VCHAR:100";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:17:"notification_type";a:4:{i:0;s:8:"VCHAR:50";i:1;s:5:"email";i:2;s:0:"";i:3;s:3:"YES";}s:16:"is_admin_default";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:3:"YES";}}s:11:"PRIMARY_KEY";s:15:"notification_id";s:4:"KEYS";a:2:{s:7:"user_id";a:2:{i:0;s:5:"INDEX";i:1;a:2:{i:0;s:7:"user_id";i:1;s:17:"user_notification";}}s:9:"user_id_2";a:2:{i:0;s:5:"INDEX";i:1;s:7:"user_id";}}}s:19:"phpfox_user_privacy";a:3:{s:7:"COLUMNS";a:3:{s:7:"user_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:12:"user_privacy";a:4:{i:0;s:9:"VCHAR:100";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:10:"user_value";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}}s:11:"PRIMARY_KEY";a:2:{i:0;s:7:"user_id";i:1;s:12:"user_privacy";}s:4:"KEYS";a:1:{s:9:"user_id_2";a:2:{i:0;s:5:"INDEX";i:1;s:7:"user_id";}}}s:21:"phpfox_user_promotion";a:3:{s:7:"COLUMNS";a:7:{s:12:"promotion_id";a:4:{i:0;s:5:"USINT";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:13:"user_group_id";a:4:{i:0;s:6:"TINT:3";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:21:"upgrade_user_group_id";a:4:{i:0;s:6:"TINT:3";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:14:"total_activity";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:9:"total_day";a:4:{i:0;s:7:"VCHAR:5";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:4:"rule";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:10:"time_stamp";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}}s:11:"PRIMARY_KEY";s:12:"promotion_id";s:4:"KEYS";a:1:{s:13:"user_group_id";a:2:{i:0;s:5:"INDEX";i:1;s:13:"user_group_id";}}}s:18:"phpfox_user_rating";a:3:{s:7:"COLUMNS";a:5:{s:7:"rate_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:7:"item_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:7:"user_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:6:"rating";a:4:{i:0;s:9:"DECIMAL:4";i:1;s:4:"0.00";i:2;s:0:"";i:3;s:2:"NO";}s:10:"time_stamp";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}}s:11:"PRIMARY_KEY";s:7:"rate_id";s:4:"KEYS";a:2:{s:7:"item_id";a:2:{i:0;s:5:"INDEX";i:1;a:2:{i:0;s:7:"item_id";i:1;s:7:"user_id";}}s:9:"item_id_2";a:2:{i:0;s:5:"INDEX";i:1;s:7:"item_id";}}}s:19:"phpfox_user_setting";a:2:{s:7:"COLUMNS";a:3:{s:13:"user_group_id";a:4:{i:0;s:6:"TINT:3";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:10:"setting_id";a:4:{i:0;s:4:"UINT";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:12:"value_actual";a:4:{i:0;s:4:"TEXT";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}}s:11:"PRIMARY_KEY";a:2:{i:0;s:13:"user_group_id";i:1;s:10:"setting_id";}}s:17:"phpfox_user_snoop";a:3:{s:7:"COLUMNS";a:4:{s:8:"snoop_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:10:"time_stamp";a:4:{i:0;s:7:"UINT:11";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:7:"user_id";a:4:{i:0;s:7:"UINT:11";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:13:"logging_in_as";a:4:{i:0;s:7:"UINT:11";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}}s:11:"PRIMARY_KEY";s:8:"snoop_id";s:4:"KEYS";a:1:{s:7:"user_id";a:2:{i:0;s:5:"INDEX";i:1;s:7:"user_id";}}}s:17:"phpfox_user_space";a:2:{s:7:"COLUMNS";a:13:{s:7:"user_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:16:"space_attachment";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:11:"space_photo";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:10:"space_poll";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:10:"space_quiz";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:17:"space_marketplace";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:11:"space_event";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:11:"space_group";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:11:"space_music";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:17:"space_music_image";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:11:"space_video";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:11:"space_pages";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:11:"space_total";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}}s:4:"KEYS";a:1:{s:7:"user_id";a:2:{i:0;s:6:"UNIQUE";i:1;s:7:"user_id";}}}s:18:"phpfox_user_verify";a:3:{s:7:"COLUMNS";a:6:{s:9:"verify_id";a:4:{i:0;s:7:"UINT:11";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:7:"user_id";a:4:{i:0;s:7:"UINT:11";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:9:"hash_code";a:4:{i:0;s:8:"VCHAR:52";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:10:"time_stamp";a:4:{i:0;s:6:"UINT:9";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:5:"email";a:4:{i:0;s:9:"VCHAR:100";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:7:"type_id";a:4:{i:0;s:8:"VCHAR:50";i:1;s:14:"verify_account";i:2;s:0:"";i:3;s:3:"YES";}}s:11:"PRIMARY_KEY";s:9:"verify_id";s:4:"KEYS";a:1:{s:7:"user_id";a:2:{i:0;s:5:"INDEX";i:1;s:7:"user_id";}}}s:24:"phpfox_user_verify_error";a:2:{s:7:"COLUMNS";a:5:{s:8:"error_id";a:4:{i:0;s:7:"UINT:11";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:9:"hash_code";a:4:{i:0;s:7:"CHAR:50";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:5:"email";a:4:{i:0;s:9:"VCHAR:100";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:10:"ip_address";a:4:{i:0;s:8:"VCHAR:50";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:10:"time_stamp";a:4:{i:0;s:6:"UINT:9";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}}s:11:"PRIMARY_KEY";s:8:"error_id";}s:18:"phpfox_user_status";a:3:{s:7:"COLUMNS";a:10:{s:9:"status_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:7:"user_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:7:"privacy";a:4:{i:0;s:6:"TINT:3";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:15:"privacy_comment";a:4:{i:0;s:6:"TINT:3";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:7:"content";a:4:{i:0;s:5:"MTEXT";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:10:"time_stamp";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:13:"total_comment";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:10:"total_like";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:15:"location_latlng";a:4:{i:0;s:9:"VCHAR:100";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:13:"location_name";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}}s:11:"PRIMARY_KEY";s:9:"status_id";s:4:"KEYS";a:1:{s:7:"user_id";a:2:{i:0;s:5:"INDEX";i:1;s:7:"user_id";}}}s:19:"phpfox_upload_track";a:3:{s:7:"COLUMNS";a:6:{s:8:"track_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:7:"user_id";a:4:{i:0;s:7:"UINT:11";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:4:"hash";a:4:{i:0;s:8:"VCHAR:50";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:9:"user_hash";a:4:{i:0;s:8:"VCHAR:50";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:10:"ip_address";a:4:{i:0;s:8:"VCHAR:50";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:10:"time_stamp";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}}s:11:"PRIMARY_KEY";s:8:"track_id";s:4:"KEYS";a:1:{s:7:"user_id";a:2:{i:0;s:5:"INDEX";i:1;s:7:"user_id";}}}s:33:"phpfox_user_custom_multiple_value";a:3:{s:7:"COLUMNS";a:3:{s:7:"user_id";a:4:{i:0;s:7:"UINT:11";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:8:"field_id";a:4:{i:0;s:5:"USINT";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:9:"option_id";a:4:{i:0;s:5:"USINT";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}}s:11:"PRIMARY_KEY";a:3:{i:0;s:7:"user_id";i:1;s:8:"field_id";i:2;s:9:"option_id";}s:4:"KEYS";a:1:{s:7:"user_id";a:2:{i:0;s:5:"INDEX";i:1;a:2:{i:0;s:7:"user_id";i:1;s:8:"field_id";}}}}s:23:"phpfox_user_custom_data";a:2:{s:7:"COLUMNS";a:4:{s:8:"field_id";a:4:{i:0;s:7:"UINT:11";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:7:"user_id";a:4:{i:0;s:7:"UINT:11";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:10:"total_like";a:4:{i:0;s:7:"UINT:11";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:13:"total_comment";a:4:{i:0;s:7:"UINT:11";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}}s:11:"PRIMARY_KEY";s:7:"user_id";}s:21:"phpfox_point_purchase";a:2:{s:7:"COLUMNS";a:7:{s:11:"purchase_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:7:"user_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:11:"currency_id";a:4:{i:0;s:6:"CHAR:3";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:5:"price";a:4:{i:0;s:10:"DECIMAL:14";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:6:"status";a:4:{i:0;s:8:"VCHAR:20";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:10:"time_stamp";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:11:"total_point";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}}s:11:"PRIMARY_KEY";s:11:"purchase_id";}s:16:"phpfox_user_spam";a:2:{s:7:"COLUMNS";a:8:{s:11:"question_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:15:"question_phrase";a:4:{i:0;s:9:"VCHAR:250";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:15:"answers_phrases";a:4:{i:0;s:4:"TEXT";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:10:"image_path";a:4:{i:0;s:9:"VCHAR:250";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:9:"server_id";a:4:{i:0;s:6:"TINT:3";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:14:"case_sensitive";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"1";i:2;s:0:"";i:3;s:2:"NO";}s:9:"is_active";a:4:{i:0;s:6:"TINT:1";i:1;i:1;i:2;s:0:"";i:3;s:3:"YES";}s:10:"time_stamp";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}}s:11:"PRIMARY_KEY";s:11:"question_id";}}]]></tables>
    <install><![CDATA[
		$aRows = array(
			array(
				'user_group_id' => '1',
				'title' => 'Administrator',
				'is_special' => '1'
			),
			array(
				'user_group_id' => '2',
				'title' => 'Registered User',
				'is_special' => '1'
			),
			array(
				'user_group_id' => '3',
				'title' => 'Guest',
				'is_special' => '1'
			),
			array(
				'user_group_id' => '4',
				'title' => 'Staff',
				'is_special' => '1'
			)
		);
		foreach ($aRows as $aRow)
		{
			$aInsert = array();
			foreach ($aRow as $sKey => $sValue)
			{
				$aInsert[$sKey] = $sValue;
			}
			$this->database()->insert(Phpfox::getT('user_group'), $aInsert);
		}		
		
		$iUserGroupId = Phpfox::getService('user.group.process')->add(array(
				'title' => 'Banned',
				'inherit_id' => 2
			)
		);	
		
		$this->database()->update(Phpfox::getT('setting'), array('value_actual' => $iUserGroupId), 'module_id = \'core\' AND var_name = \'banned_user_group_id\'');
		$this->database()->update(Phpfox::getT('user_group_custom'), array('default_value' => '1'), 'user_group_id = ' . (int) $iUserGroupId . ' AND module_id = \'core\' AND name = \'user_is_banned\'');

		$sTable = Phpfox::getT('user_twofactor_token');
        $this->database()->createTable($sTable, [['name' => 'email', 'type' => 'varchar(150)', 'extra' => 'NOT NULL DEFAULT \'\'', 'primary_key' => true], ['name' => 'token_data', 'type' => 'text']], true);

        $this->database()->createTable(Phpfox::getT('user_import'), [
            [
                'name' => 'import_id',
                'type' => 'INT:10',
                'extra' => 'unsigned not null',
                'primary_key' => true,
                'auto_increment' => true
            ],
            [
                'name' => 'user_id',
                'type' => 'INT:10',
                'extra' => 'unsigned not null',
                'primary_key' => true,
            ],
            [
                'name' => 'time_stamp',
                'type' => 'INT:10',
                'extra' => 'unsigned not null',
            ],
            [
                'name' => 'file_name',
                'type' => 'VCHAR',
            ],
            [
                'name' => 'status',
                'type' => 'enum',
                'extra' => '("completed","processing","stopped") NOT NULL'
            ],
            [
                'name' => 'total_user',
                'type' => 'INT:10',
                'extra' => 'unsigned not null',
            ],
            [
                'name' => 'total_imported',
                'type' => 'INT:10',
                'extra' => 'unsigned not null default 0',
            ],
            [
                'name' => 'error_log',
                'type' => 'MTEXT',
                'extra' => 'default null'
            ],
            [
                'name' => 'processing_job_id',
                'type' => 'MTEXT',
                'extra' => 'default null'
            ],
            [
                'name' => 'import_field',
                'type' => 'MTEXT',
                'extra' => 'default null'
            ],
        ]);
	]]></install>
</module>