<?php
namespace Apps\PHPfox_Core\Installation\Database;

use Core\App\Install\Database\Table as Table;

class Feed_Tag_Remove extends Table
{
    /**
     *
     */
    protected function setTableName()
    {
        $this->_table_name = 'feed_tag_remove';
    }

    /**
     *
     */
    protected function setFieldParams()
    {
        $this->_aFieldParams = [
            'id' => [
                'type' => 'int',
                'type_value' => '10',
                'other' => 'UNSIGNED NOT NULL',
                'auto_increment' => true,
                'primary_key' => true,
            ],
            'user_id' => [
                'type' => 'int',
                'type_value' => '10',
                'other' => 'UNSIGNED NOT NULL'
            ],
            'item_id' => [
                'type' => 'int',
                'type_value' => '10',
                'other' => 'UNSIGNED NOT NULL'
            ],
            'type_id' => [
                'type' => 'varchar',
                'type_value' => '75',
                'other' => 'NOT NULL'
            ],
        ];
    }
    /**
     * Set keys of table
     */
    protected function setKeys()
    {
        $this->_key = [
            'user_id'     => ['user_id'],
            'item_id'     => ['item_id'],
            'item_id_2'     => ['item_id','type_id'],
        ];
    }
}