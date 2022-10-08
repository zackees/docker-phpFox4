<?php
namespace Apps\PHPfox_Core\Installation\Database;

use Core\App\Install\Database\Field;
use Core\App\Install\Database\Table;

class SessionData extends Table
{
    public function setTableName()
    {
        $this->_table_name = 'core_session_data';
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
            'session_id' => [
                Field::FIELD_PARAM_TYPE => Field::TYPE_VARCHAR,
                Field::FIELD_PARAM_TYPE_VALUE => 100,
                Field::FIELD_PARAM_OTHER => 'NOT NULL'
            ],
            'session_data' => [
                Field::FIELD_PARAM_TYPE => Field::TYPE_TEXT,
                Field::FIELD_PARAM_OTHER => 'NOT NULL'
            ],
            'lifetime' => [
                Field::FIELD_PARAM_TYPE => Field::TYPE_INT,
                Field::FIELD_PARAM_TYPE_VALUE => 11,
                Field::FIELD_PARAM_OTHER => 'UNSIGNED NOT NULL'
            ],
            'expired_at' => [
                Field::FIELD_PARAM_TYPE => Field::TYPE_INT,
                Field::FIELD_PARAM_TYPE_VALUE => 11,
                Field::FIELD_PARAM_OTHER => 'UNSIGNED NOT NULL'
            ],
        ];
    }

    public function setKeys()
    {
        $this->_key = [
            'expired_at' => ['expired_at'],
        ];
    }
}