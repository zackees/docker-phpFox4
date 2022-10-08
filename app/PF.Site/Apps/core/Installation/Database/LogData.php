<?php
namespace Apps\PHPfox_Core\Installation\Database;

use Core\App\Install\Database\Table;
use Core\App\Install\Database\Field;

class LogData extends Table
{
    public function setTableName()
    {
        $this->_table_name = 'core_log_data';
    }

    public function setFieldParams()
    {
        $this->_aFieldParams = [
            'id' => [
                Field::FIELD_PARAM_PRIMARY_KEY => true,
                Field::FIELD_PARAM_AUTO_INCREMENT => true,
                Field::FIELD_PARAM_TYPE => Field::TYPE_INT,
                Field::FIELD_PARAM_TYPE_VALUE => 11,
                Field::FIELD_PARAM_OTHER => 'UNSIGNED NOT NULL'
            ],
            'message' => [
                Field::FIELD_PARAM_TYPE => Field::TYPE_TEXT,
                Field::FIELD_PARAM_OTHER => 'NOT NULL'
            ],
            'context' => [
                Field::FIELD_PARAM_TYPE => Field::TYPE_TEXT,
                Field::FIELD_PARAM_OTHER => 'NOT NULL'
            ],
            'level' => [
                Field::FIELD_PARAM_TYPE => Field::TYPE_INT,
                Field::FIELD_PARAM_TYPE_VALUE => 10,
                Field::FIELD_PARAM_OTHER => 'UNSIGNED NOT NULL'
            ],
            'level_name' => [
                Field::FIELD_PARAM_TYPE => Field::TYPE_VARCHAR,
                Field::FIELD_PARAM_TYPE_VALUE => 100,
                Field::FIELD_PARAM_OTHER => 'NOT NULL'
            ],
            'channel' => [
                Field::FIELD_PARAM_TYPE => Field::TYPE_TINYTEXT,
                Field::FIELD_PARAM_OTHER => 'NOT NULL'
            ],
            'datetime' => [
                Field::FIELD_PARAM_TYPE => Field::TYPE_DATETIME,
                Field::FIELD_PARAM_OTHER => 'NOT NULL'
            ],
            'extra' => [
                Field::FIELD_PARAM_TYPE => Field::TYPE_TEXT,
                Field::FIELD_PARAM_OTHER => 'NOT NULL'
            ],
        ];
    }

    public function setKeys()
    {
        $this->_key = [];
    }
}