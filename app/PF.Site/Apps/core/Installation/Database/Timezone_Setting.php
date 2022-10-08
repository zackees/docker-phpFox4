<?php
namespace Apps\PHPfox_Core\Installation\Database;

use \Core\App\Install\Database\Table as Table;

class Timezone_Setting extends Table
{
    /**
     *
     */
    protected function setTableName()
    {
        $this->_table_name = 'timezone_setting';
    }

    /**
     *
     */
    protected function setFieldParams()
    {
        $this->_aFieldParams = [
            'setting_id' => [
                'type' => 'int',
                'type_value' => '10',
                'other' => 'UNSIGNED NOT NULL',
                'primary_key' => true,
                'auto_increment' => true,
            ],
            'timezone_key' => [
                'type' => 'varchar',
                'type_value' => '4',
                'other' => 'NOT NULL',
            ],
            'disable' => [
                'type' => 'tinyint',
                'type_value' => '1',
                'other' => 'NOT NULL DEFAULT \'1\'',
            ],
        ];
    }
}
