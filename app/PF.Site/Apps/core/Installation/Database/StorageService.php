<?php
namespace Apps\PHPfox_Core\Installation\Database;

use Core\App\Install\Database\Table;
use Core\App\Install\Database\Field;

class StorageService extends Table
{
    public function setTableName()
    {
        $this->_table_name = 'core_storage_service';
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
            'service_class' => [
                Field::FIELD_PARAM_TYPE => Field::TYPE_VARCHAR,
                Field::FIELD_PARAM_TYPE_VALUE => 200,
                Field::FIELD_PARAM_OTHER => 'NOT NULL'
            ],
            'service_phrase_name' => [
                Field::FIELD_PARAM_TYPE => Field::TYPE_VARCHAR,
                Field::FIELD_PARAM_TYPE_VALUE => 200,
                Field::FIELD_PARAM_OTHER => 'NOT NULL'
            ],
            'module_id' => [
                Field::FIELD_PARAM_TYPE => Field::TYPE_VARCHAR,
                Field::FIELD_PARAM_TYPE_VALUE => 75,
                Field::FIELD_PARAM_OTHER => 'NOT NULL'
            ],
            'edit_link' => [
                Field::FIELD_PARAM_TYPE => Field::TYPE_VARCHAR,
                Field::FIELD_PARAM_TYPE_VALUE => 200,
                Field::FIELD_PARAM_OTHER => 'NOT NULL'
            ],
            'sort_order' => [
                Field::FIELD_PARAM_TYPE => Field::TYPE_INT,
                Field::FIELD_PARAM_TYPE_VALUE => 11,
                Field::FIELD_PARAM_OTHER => 'UNSIGNED NOT NULL'
            ],
        ];
    }

    public function setKeys()
    {
        $this->_key = [];
    }
}