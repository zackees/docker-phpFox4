<module>
	<data>
		<module_id>theme</module_id>
		<product_id>phpfox</product_id>
		<is_core>1</is_core>
		<is_active>1</is_active>
		<is_menu>0</is_menu>
		<menu />
		<phrase_var_name>module_theme</phrase_var_name>
	</data>
	<hooks>
		<hook module_id="theme" hook_type="controller" module="theme" call_name="theme.component_controller_sample_clean" added="1231838390" version_id="2.0.0alpha1" />
	</hooks>
	<components>
		<component module_id="theme" component="ajax" m_connection="" module="theme" is_controller="0" is_block="0" is_active="1" />
		<component module_id="theme" component="sample" m_connection="theme.sample" module="theme" is_controller="1" is_block="0" is_active="1" />
		<component module_id="theme" component="admincp.index" m_connection="" module="theme" is_controller="0" is_block="0" is_active="1" />
	</components>
	<tables><![CDATA[a:2:{s:12:"phpfox_theme";a:3:{s:7:"COLUMNS";a:11:{s:8:"theme_id";a:4:{i:0;s:5:"USINT";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:9:"parent_id";a:4:{i:0;s:5:"USINT";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:4:"name";a:4:{i:0;s:8:"VCHAR:75";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:6:"folder";a:4:{i:0;s:8:"VCHAR:75";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:7:"created";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:7:"creator";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:7:"website";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:7:"version";a:4:{i:0;s:8:"VCHAR:10";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:9:"is_active";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:10:"is_default";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:12:"total_column";a:4:{i:0;s:6:"TINT:1";i:1;s:1:"2";i:2;s:0:"";i:3;s:2:"NO";}}s:11:"PRIMARY_KEY";s:8:"theme_id";s:4:"KEYS";a:3:{s:9:"is_active";a:2:{i:0;s:5:"INDEX";i:1;s:9:"is_active";}s:8:"theme_id";a:2:{i:0;s:5:"INDEX";i:1;a:2:{i:0;s:8:"theme_id";i:1;s:9:"is_active";}}s:6:"folder";a:2:{i:0;s:5:"INDEX";i:1;s:6:"folder";}}}s:18:"phpfox_theme_style";a:2:{s:7:"COLUMNS";a:15:{s:8:"style_id";a:4:{i:0;s:5:"USINT";i:1;N;i:2;s:14:"auto_increment";i:3;s:2:"NO";}s:8:"theme_id";a:4:{i:0;s:5:"USINT";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:9:"parent_id";a:4:{i:0;s:5:"USINT";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:9:"is_active";a:4:{i:0;s:6:"TINT:1";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:10:"is_default";a:4:{i:0;s:6:"TINT:1";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:4:"name";a:4:{i:0;s:8:"VCHAR:75";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:6:"folder";a:4:{i:0;s:8:"VCHAR:75";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:7:"created";a:4:{i:0;s:7:"UINT:10";i:1;N;i:2;s:0:"";i:3;s:2:"NO";}s:7:"creator";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:7:"website";a:4:{i:0;s:9:"VCHAR:255";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:7:"version";a:4:{i:0;s:8:"VCHAR:10";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:10:"logo_image";a:4:{i:0;s:8:"VCHAR:50";i:1;N;i:2;s:0:"";i:3;s:3:"YES";}s:7:"l_width";a:4:{i:0;s:5:"USINT";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:7:"c_width";a:4:{i:0;s:5:"USINT";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}s:7:"r_width";a:4:{i:0;s:5:"USINT";i:1;s:1:"0";i:2;s:0:"";i:3;s:2:"NO";}}s:4:"KEYS";a:6:{s:10:"style_id_2";a:2:{i:0;s:6:"UNIQUE";i:1;s:8:"style_id";}s:8:"style_id";a:2:{i:0;s:5:"INDEX";i:1;a:2:{i:0;s:8:"style_id";i:1;s:9:"is_active";}}s:9:"is_active";a:2:{i:0;s:5:"INDEX";i:1;s:9:"is_active";}s:9:"parent_id";a:2:{i:0;s:5:"INDEX";i:1;a:2:{i:0;s:9:"parent_id";i:1;s:9:"is_active";}}s:10:"is_default";a:2:{i:0;s:5:"INDEX";i:1;s:10:"is_default";}s:6:"folder";a:2:{i:0;s:5:"INDEX";i:1;s:6:"folder";}}}}]]></tables>
</module>