<module>
	<data>
		<module_id>link</module_id>
		<product_id>phpfox</product_id>
		<is_core>0</is_core>
		<is_active>1</is_active>
		<is_menu>0</is_menu>
		<menu />
		<phrase_var_name>module_link</phrase_var_name>
		<writable />
	</data>
	<hooks>
		<hook module_id="link" hook_type="component" module="link" call_name="link.component_ajax_addviastatusupdate" added="1319729453" version_id="3.0.0rc1" />
		<hook module_id="link" hook_type="component" module="link" call_name="link.component_block_attach_clean" added="1319729453" version_id="3.0.0rc1" />
		<hook module_id="link" hook_type="component" module="link" call_name="link.component_block_display_clean" added="1319729453" version_id="3.0.0rc1" />
		<hook module_id="link" hook_type="component" module="link" call_name="link.component_block_preview_clean" added="1319729453" version_id="3.0.0rc1" />
		<hook module_id="link" hook_type="controller" module="link" call_name="link.component_controller_index_clean" added="1319729453" version_id="3.0.0rc1" />
		<hook module_id="link" hook_type="service" module="link" call_name="link.service_callback__call" added="1319729453" version_id="3.0.0rc1" />
		<hook module_id="link" hook_type="service" module="link" call_name="link.service_link__call" added="1319729453" version_id="3.0.0rc1" />
		<hook module_id="link" hook_type="service" module="link" call_name="link.service_process__call" added="1319729453" version_id="3.0.0rc1" />
		<hook module_id="link" hook_type="service" module="link" call_name="link.component_service_callback_getactivityfeed__1" added="1335951260" version_id="3.2.0" />
		<hook module_id="link" hook_type="service" module="link" call_name="link.service_callback_checkfeedsharelink" added="1358258443" version_id="3.5.0beta1" />
	</hooks>
    <settings>
        <setting group="" module_id="link" is_hidden="0" type="string" var_name="youtube_data_api_key" phrase_var_name="setting_youtube_data_api_key" ordering="96" version_id="4.6.0"/>
        <setting group="" module_id="link" is_hidden="0" type="string" var_name="twitter_data_api_key" phrase_var_name="setting_twitter_data_api_key" ordering="97" version_id="4.8.0"/>
        <setting group="" module_id="link" is_hidden="0" type="password" var_name="twitter_data_secret_key" phrase_var_name="setting_twitter_data_secret_key" ordering="98" version_id="4.8.0"/>
        <setting group="" module_id="link" is_hidden="0" type="string" var_name="facebook_app_id" phrase_var_name="setting_link_facebook_app_id" ordering="99" version_id="4.8.2"/>
        <setting group="" module_id="link" is_hidden="0" type="password" var_name="facebook_app_secret" phrase_var_name="setting_link_facebook_app_secret" ordering="100" version_id="4.8.2"/>
        <!-- new setting in 4.8.4 -->
        <setting group="email" module_id="link" is_hidden="0" type="" var_name="link_setting_subject_like_user_link" phrase_var_name="setting_link_setting_subject_like_user_link" ordering="1" version_id="4.8.4"><![CDATA[{_p var="full_name_liked_your_link_title"}]]></setting>
        <setting group="email" module_id="link" is_hidden="0" type="" var_name="link_setting_content_like_user_link" phrase_var_name="setting_link_setting_content_like_user_link" ordering="2" version_id="4.8.4"><![CDATA[{_p var="full_name_liked_your_link_title_message"}]]></setting>
        <setting group="email" module_id="link" is_hidden="0" type="" var_name="link_setting_subject_comment_user_link" phrase_var_name="setting_link_setting_subject_comment_user_link" ordering="3" version_id="4.8.4"><![CDATA[{_p var="full_name_commented_on_your_link_title"}]]></setting>
        <setting group="email" module_id="link" is_hidden="0" type="" var_name="link_setting_content_comment_user_link" phrase_var_name="setting_link_setting_content_comment_user_link" ordering="4" version_id="4.8.4"><![CDATA[{_p var="full_name_commented_on_your_link_a_href_link_title_a"}]]></setting>
        <setting group="email" module_id="link" is_hidden="0" type="" var_name="link_setting_subject_comment_their_own_link" phrase_var_name="setting_link_setting_subject_comment_their_own_link" ordering="5" version_id="4.8.4"><![CDATA[{_p var="full_name_commented_on_gender_link"}]]></setting>
        <setting group="email" module_id="link" is_hidden="0" type="" var_name="link_setting_content_comment_their_own_link" phrase_var_name="setting_link_setting_content_comment_their_own_link" ordering="6" version_id="4.8.4"><![CDATA[{_p var="full_name_commented_on_gender_link_a_href_link_title_a"}]]></setting>
        <setting group="email" module_id="link" is_hidden="0" type="" var_name="link_setting_subject_comment_other_link" phrase_var_name="setting_link_setting_subject_comment_other_link" ordering="7" version_id="4.8.4"><![CDATA[{_p var="full_name_commented_on_row_full_name_s_link"}]]></setting>
        <setting group="email" module_id="link" is_hidden="0" type="" var_name="link_setting_content_comment_other_link" phrase_var_name="setting_link_setting_content_comment_other_link" ordering="8" version_id="4.8.4"><![CDATA[{_p var="full_name_commented_on_row_full_name_s_link_a_href_link_title_a_message"}]]></setting>
        <setting group="email" module_id="link" is_hidden="0" type="" var_name="link_setting_subject_add_link_on_user_wall" phrase_var_name="setting_link_setting_subject_add_link_on_user_wall" ordering="9" version_id="4.8.4"><![CDATA[{_p var="full_name_posted_a_link_on_your_wall"}]]></setting>
        <setting group="email" module_id="link" is_hidden="0" type="" var_name="link_setting_content_add_link_on_user_wall" phrase_var_name="setting_link_setting_content_add_link_on_user_wall" ordering="10" version_id="4.8.4"><![CDATA[{_p var="full_name_posted_a_link_on_your_wall_message"}]]></setting>
        <setting group="email" module_id="link" is_hidden="0" type="" var_name="link_setting_subject_tagged_on_link" phrase_var_name="setting_link_setting_subject_tagged_on_link" ordering="11" version_id="4.8.4"><![CDATA[{_p var="full_name_tagged_you_in_a_link"}]]></setting>
        <setting group="email" module_id="link" is_hidden="0" type="" var_name="link_setting_content_tagged_on_link" phrase_var_name="setting_link_setting_content_tagged_on_link" ordering="12" version_id="4.8.4"><![CDATA[{_p var="full_name_tagged_you_in_a_link_you_can_view_here"}]]></setting>
    </settings>
	<tables><![CDATA[a:2:{s:11:"phpfox_link";a:3:{s:7:"COLUMNS";a:21:{s:7:"link_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:7:"user_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:9:"module_id";a:4:{i:0;s:8:"VCHAR:75";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:7:"item_id";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:14:"parent_user_id";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:9:"is_custom";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:4:"link";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:5:"image";a:4:{i:0;s:10:"VCHAR:1023";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:5:"title";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:11:"description";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:11:"status_info";a:4:{i:0;s:5:"MTEXT";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:7:"privacy";a:4:{i:0;s:6:"TINT:1";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:15:"privacy_comment";a:4:{i:0;s:6:"TINT:1";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:10:"time_stamp";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:9:"has_embed";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:13:"total_comment";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:10:"total_like";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:13:"total_dislike";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:15:"location_latlng";a:4:{i:0;s:9:"VCHAR:100";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:13:"location_name";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:9:"server_id";a:4:{i:0;s:6:"TINT:1";i:1;i:0;i:2;s:0:"";i:3;s:3:"YES";}}s:11:"PRIMARY_KEY";s:7:"link_id";s:4:"KEYS";a:1:{s:14:"parent_user_id";a:2:{i:0;s:5:"INDEX";i:1;s:14:"parent_user_id";}}}s:17:"phpfox_link_embed";a:2:{s:7:"COLUMNS";a:2:{s:7:"link_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:10:"embed_code";a:4:{i:0;s:5:"MTEXT";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}}s:4:"KEYS";a:1:{s:7:"link_id";a:2:{i:0;s:6:"UNIQUE";i:1;s:7:"link_id";}}}}]]></tables>
</module>