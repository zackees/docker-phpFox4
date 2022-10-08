<?php
namespace Apps\PHPfox_Core\Installation\Database;

use Core\App\Install\Database\Table;
use Core\App\Install\Database\Field;

class SessionService extends Table
{
    public function setTableName()
    {
        $this->_table_name = 'core_session_service';
    }

    public function setFieldParams()
    {
        $this->_aFieldParams = [
            'service_id' => [
                Field::FIELD_PARAM_PRIMARY_KEY => true,
                Field::FIELD_PARAM_TYPE => Field::TYPE_VARCHAR,
                Field::FIELD_PARAM_TYPE_VALUE => 100,
                Field::FIELD_PARAM_OTHER => 'NOT NULL'
            ],
            'service_phrase_name' => [
                Field::FIELD_PARAM_TYPE => Field::TYPE_VARCHAR,
                Field::FIELD_PARAM_TYPE_VALUE => 100,
                Field::FIELD_PARAM_OTHER => 'NOT NULL'
            ],
            'service_class' => [
                Field::FIELD_PARAM_TYPE => Field::TYPE_VARCHAR,
                Field::FIELD_PARAM_TYPE_VALUE => 200,
                Field::FIELD_PARAM_OTHER => 'NOT NULL'
            ],
            'config' => [
                Field::FIELD_PARAM_TYPE => Field::TYPE_TEXT,
                Field::FIELD_PARAM_OTHER => 'NOT NULL'
            ],
            'is_default' => [
                Field::FIELD_PARAM_TYPE => Field::TYPE_TINYINT,
                Field::FIELD_PARAM_TYPE_VALUE => 1,
                Field::FIELD_PARAM_OTHER => 'UNSIGNED NOT NULL DEFAULT 0'
            ],
            'edit_link' => [
                Field::FIELD_PARAM_TYPE => Field::TYPE_VARCHAR,
                Field::FIELD_PARAM_TYPE_VALUE => 200,
                Field::FIELD_PARAM_OTHER => 'NOT NULL'
            ],
        ];
    }

    public function setKeys()
    {
        $this->_key = [];
    }
}