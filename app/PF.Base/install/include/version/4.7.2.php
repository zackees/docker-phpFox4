<?php
return function (Phpfox_Installer $Installer) {
    $installerDb = $Installer->db;

    $tableName = Phpfox::getT('country');
    if (!$installerDb->isField($tableName, 'is_active')) {
	    $installerDb->addField([
	        'table' => $tableName,
	        'field' => 'is_active',
	        'type'  => 'BOOL',
	        'default' => '1',
	        'null' => true
	    ]);
    }

    $tableName = Phpfox::getT('user_import');
    if(!$installerDb->tableExists($tableName)) {
        $installerDb->createTable($tableName, [
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
    }
};

