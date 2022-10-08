<?php
namespace Apps\PHPfox_Core\Installation\Database;

use Core\App\Install\Database\Table;
use Core\App\Install\Database\Field;

class Storage extends Table
{
    public function setTableName()
    {
        $this->_table_name = 'core_storage';
    }

    public function setFieldParams()
    {
        $this->_aFieldParams = [
            'storage_id' => [
                Field::FIELD_PARAM_PRIMARY_KEY => true,
                Field::FIELD_PARAM_AUTO_INCREMENT => true,
                Field::FIELD_PARAM_TYPE => Field::TYPE_INT,
                Field::FIELD_PARAM_TYPE_VALUE => 11,
                Field::FIELD_PARAM_OTHER => 'UNSIGNED NOT NULL'
            ],
            'service_id' => [
                Field::FIELD_PARAM_TYPE => Field::TYPE_VARCHAR,
                Field::FIELD_PARAM_TYPE_VALUE => 100,
                Field::FIELD_PARAM_OTHER => 'NOT NULL'
            ],
            'is_default' => [
                Field::FIELD_PARAM_TYPE => Field::TYPE_TINYINT,
                Field::FIELD_PARAM_TYPE_VALUE => 1,
                Field::FIELD_PARAM_OTHER => 'UNSIGNED NOT NULL DEFAULT 0'
            ],
            'is_active' => [
                Field::FIELD_PARAM_TYPE => Field::TYPE_TINYINT,
                Field::FIELD_PARAM_TYPE_VALUE => 1,
                Field::FIELD_PARAM_OTHER => 'UNSIGNED NOT NULL DEFAULT 0'
            ],
            'storage_name' => [
                Field::FIELD_PARAM_TYPE => Field::TYPE_VARCHAR,
                Field::FIELD_PARAM_TYPE_VALUE => 200,
                Field::FIELD_PARAM_OTHER => 'NOT NULL'
            ],
            'config' => [
                Field::FIELD_PARAM_TYPE => Field::TYPE_TEXT,
                Field::FIELD_PARAM_OTHER => 'NOT NULL'
            ],
        ];
    }

    public function setKeys()
    {
        $this->_key = [
            'service_id' => ['service_id']
        ];
    }
}