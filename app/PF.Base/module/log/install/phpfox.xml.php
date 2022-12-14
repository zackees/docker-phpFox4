<module>
	<data>
		<module_id>log</module_id>
		<product_id>phpfox</product_id>
		<is_core>1</is_core>
		<is_active>1</is_active>
		<is_menu>0</is_menu>
		<menu />
		<phrase_var_name>module_log</phrase_var_name>
		<writable />
	</data>
	<settings>
		<setting group="server_settings" module_id="log" is_hidden="0" type="integer" var_name="active_session" phrase_var_name="setting_active_session" ordering="2" version_id="2.0.0alpha1">15</setting>
	</settings>
	<hooks>
		<hook module_id="log" hook_type="service" module="log" call_name="log.service_session___call" added="1231838390" version_id="2.0.0alpha1" />
		<hook module_id="log" hook_type="service" module="log" call_name="log.service_staff___call" added="1231838390" version_id="2.0.0alpha1" />
		<hook module_id="log" hook_type="component" module="log" call_name="log.component_block_login_clean" added="1258389334" version_id="2.0.0rc8" />
		<hook module_id="log" hook_type="service" module="log" call_name="log.service_session___verifyToken_start" added="1258389334" version_id="2.0.0rc8" />
		<hook module_id="log" hook_type="service" module="log" call_name="log.service_process__call" added="1258389334" version_id="2.0.0rc8" />
		<hook module_id="log" hook_type="service" module="log" call_name="log.service_log__call" added="1258389334" version_id="2.0.0rc8" />
		<hook module_id="log" hook_type="service" module="log" call_name="log.service_block__call" added="1258389334" version_id="2.0.0rc8" />
		<hook module_id="log" hook_type="service" module="log" call_name="log.service_callback__call" added="1258389334" version_id="2.0.0rc8" />
		<hook module_id="log" hook_type="service" module="log" call_name="log.service_log_getrecentloggedinusers_2" added="1384775177" version_id="3.7.3" />
	</hooks>
	<components>
		<component module_id="log" component="login" m_connection="" module="log" is_controller="0" is_block="1" is_active="1" />
	</components>
	<crons>
		<cron module_id="log" type_id="2" every="1"><![CDATA[Phpfox::getService('log.process')->removeOldUserSessions();]]></cron>
	</crons>
	<tables><![CDATA[a:4:{s:14:"phpfox_session";a:3:{s:7:"COLUMNS";a:4:{s:10:"session_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:7:"user_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:13:"last_activity";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:7:"id_hash";a:4:{i:0;s:7:"CHAR:32";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}}s:11:"PRIMARY_KEY";s:10:"session_id";s:4:"KEYS";a:2:{s:7:"user_id";a:2:{i:0;s:6:"UNIQUE";i:1;s:7:"user_id";}s:9:"user_id_2";a:2:{i:0;s:5:"INDEX";i:1;a:2:{i:0;s:7:"user_id";i:1;s:13:"last_activity";}}}}s:18:"phpfox_log_session";a:3:{s:7:"COLUMNS";a:13:{s:10:"session_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:12:"session_hash";a:4:{i:0;s:7:"CHAR:32";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:7:"id_hash";a:4:{i:0;s:7:"CHAR:32";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:12:"captcha_hash";a:4:{i:0;s:7:"CHAR:32";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:7:"user_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:13:"last_activity";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:8:"location";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:8:"is_forum";a:4:{i:0;s:6:"TINT:1";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:8:"forum_id";a:4:{i:0;s:5:"USINT";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:9:"im_status";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:7:"im_hide";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:10:"ip_address";a:4:{i:0;s:8:"VCHAR:50";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:10:"user_agent";a:4:{i:0;s:9:"VCHAR:100";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}}s:11:"PRIMARY_KEY";s:10:"session_id";s:4:"KEYS";a:3:{s:12:"session_hash";a:2:{i:0;s:5:"INDEX";i:1;s:12:"session_hash";}s:13:"last_activity";a:2:{i:0;s:5:"INDEX";i:1;s:13:"last_activity";}s:9:"user_id_2";a:2:{i:0;s:5:"INDEX";i:1;s:7:"user_id";}}}s:16:"phpfox_log_staff";a:2:{s:7:"COLUMNS";a:7:{s:6:"log_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:7:"user_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:7:"type_id";a:4:{i:0;s:6:"TINT:3";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:9:"call_name";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:5:"extra";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:10:"time_stamp";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:10:"ip_address";a:4:{i:0;s:8:"VCHAR:50";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}}s:11:"PRIMARY_KEY";s:6:"log_id";}s:15:"phpfox_log_view";a:2:{s:7:"COLUMNS";a:6:{s:7:"view_id";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:7:"user_id";a:4:{i:0;s:7:"UINT:10";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:10:"ip_address";a:4:{i:0;s:8:"VCHAR:50";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:8:"protocal";a:4:{i:0;s:7:"VCHAR:4";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:10:"cache_data";a:4:{i:0;s:5:"MTEXT";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:10:"time_stamp";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}}s:11:"PRIMARY_KEY";s:7:"view_id";}}]]></tables>
</module>