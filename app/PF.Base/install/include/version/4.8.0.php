<?php
return function (Phpfox_Installer $installer) {
	$installerDb = $installer->db;

    // remove user group settings
    $aDeleteUserGroupSettings = [
        [
            'module_id' => 'admincp',
            'name' => 'can_manage_modules'
        ],
        [
            'module_id' => 'admincp',
            'name' => 'can_add_new_modules'
        ],
    ];

    foreach ($aDeleteUserGroupSettings as $aDeleteUserGroupSetting) {
        $installerDb->delete(':user_group_setting', [
            'module_id' => $aDeleteUserGroupSetting['module_id'],
            'name' => $aDeleteUserGroupSetting['name']
        ]);
    }

    // add new settings
	$aNewSettings = [
		[
			'group_id' => 'assets',
			'module_id' => 'PHPfox_Core',
			'product_id' => 'PHPfox_Core',
			'is_hidden' => '1',
			'version_id' => '4.8.0',
			'type_id' => 'integer',
			'var_name' => 'pf_assets_cdn_enable',
			'phrase_var_name' => 'enable_cdn',
			'value_actual' => '0',
			'value_default' => '0',
			'ordering' => '1'
		],
		[
			'group_id' => 'assets',
			'module_id' => 'PHPfox_Core',
			'product_id' => 'PHPfox_Core',
			'is_hidden' => '1',
			'version_id' => '4.8.0',
			'type_id' => 'string',
			'var_name' => 'pf_assets_cdn_url',
			'phrase_var_name' => 'cdn_url',
			'value_actual' => '',
			'value_default' => '',
			'ordering' => '1'
		],
        [
            'group_id'        => null,
            'module_id'       => 'link',
            'product_id'      => 'phpfox',
            'is_hidden'       => '0',
            'version_id'      => '4.8.0',
            'type_id'         => 'string',
            'var_name'        => 'twitter_data_api_key',
            'phrase_var_name' => 'setting_twitter_data_api_key',
            'value_actual'    => '',
            'value_default'   => '',
            'ordering'        => '97',
        ],
        [
            'group_id'        => null,
            'module_id'       => 'link',
            'product_id'      => 'phpfox',
            'is_hidden'       => '0',
            'version_id'      => '4.8.0',
            'type_id'         => 'password',
            'var_name'        => 'twitter_data_secret_key',
            'phrase_var_name' => 'setting_twitter_data_secret_key',
            'value_actual'    => '',
            'value_default'   => '',
            'ordering'        => '98',
        ]
	];

	foreach ($aNewSettings as $aNewSetting) {
		$checkSetting = $installerDb->select('setting_id')
			->from(':setting')
			->where(['var_name' => $aNewSetting['var_name'], 'module_id' => $aNewSetting['module_id']])
			->executeRow();
		if (!$checkSetting) {
			$installerDb->insert(':setting', $aNewSetting);
		}
	}
};