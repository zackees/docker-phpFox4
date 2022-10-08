<module>
	<data>
		<module_id>share</module_id>
		<product_id>phpfox</product_id>
		<is_core>0</is_core>
		<is_active>1</is_active>
		<is_menu>1</is_menu>
		<menu></menu>
		<phrase_var_name>module_share</phrase_var_name>
		<writable />
	</data>
	<hooks>
		<hook module_id="share" hook_type="component" module="share" call_name="share.component_block_frame_clean" added="1240687633" version_id="2.0.0beta1" />
		<hook module_id="share" hook_type="component" module="share" call_name="share.component_block_link_clean" added="1240687633" version_id="2.0.0beta1" />
		<hook module_id="share" hook_type="service" module="share" call_name="share.service_site__call" added="1240687633" version_id="2.0.0beta1" />
		<hook module_id="share" hook_type="service" module="share" call_name="share.service_process__call" added="1240687633" version_id="2.0.0beta1" />
		<hook module_id="share" hook_type="service" module="share" call_name="share.service_bookmark__call" added="1240687633" version_id="2.0.0beta1" />
		<hook module_id="share" hook_type="service" module="share" call_name="share.service_callback__call" added="1240687633" version_id="2.0.0beta1" />
		<hook module_id="share" hook_type="service" module="share" call_name="share.service_share__call" added="1240687633" version_id="2.0.0beta1" />
		<hook module_id="share" hook_type="component" module="share" call_name="share.component_block_friend_clean" added="1258389334" version_id="2.0.0rc8" />
		<hook module_id="share" hook_type="service" module="share" call_name="share.service_process_sendemails_1" added="1339076699" version_id="3.3.0beta1" />
		<hook module_id="share" hook_type="service" module="share" call_name="share.service_process_sendemails_7" added="1339076699" version_id="3.3.0beta1" />
		<hook module_id="share" hook_type="service" module="share" call_name="share.service_process_sendemails_2" added="1339076699" version_id="3.3.0beta1" />
		<hook module_id="share" hook_type="service" module="share" call_name="share.service_process_sendemails_3" added="1339076699" version_id="3.3.0beta1" />
		<hook module_id="share" hook_type="service" module="share" call_name="share.service_process_sendemails_6" added="1339076699" version_id="3.3.0beta1" />
		<hook module_id="share" hook_type="service" module="share" call_name="share.service_process_sendemails_4" added="1339076699" version_id="3.3.0beta1" />
		<hook module_id="share" hook_type="service" module="share" call_name="share.service_process_sendemails_5" added="1339076699" version_id="3.3.0beta1" />
	</hooks>
    <settings>
        <setting group="general" module_id="share" is_hidden="0" type="boolean" var_name="show_addthis_section" phrase_var_name="setting_show_addthis_section" ordering="1" version_id="4.6.0">1</setting>
        <setting group="" module_id="core" is_hidden="0" type="string" var_name="addthis_pub_id" phrase_var_name="setting_addthis_pub_id" ordering="1" version_id="4.5.2" />
        <setting group="" module_id="core" is_hidden="0" type="large_string" var_name="addthis_share_button" phrase_var_name="setting_addthis_share_button" ordering="1" version_id="4.5.2" />
    </settings>
	<user_group_settings>
        <setting is_admin_setting="0" module_id="share" type="boolean" admin="1" user="1" guest="0" staff="1" module="share" ordering="0">can_share_items</setting>
	</user_group_settings>
</module>