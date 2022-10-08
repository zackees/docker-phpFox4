<?php

namespace Apps\PHPfox_Core\Installation\Database;

use Core\App\Install\Database\Table;
use Core\App\Install\Database\Field;

class SearchWordLog extends Table
{
    public function setTableName()
    {
        $this->_table_name = 'search_word_log';
    }

    public function setFieldParams()
    {
        $this->_aFieldParams = [
            'search_word_id' => [
                Field::FIELD_PARAM_PRIMARY_KEY    => true,
                Field::FIELD_PARAM_AUTO_INCREMENT => true,
                Field::FIELD_PARAM_TYPE           => Field::TYPE_INT,
                Field::FIELD_PARAM_TYPE_VALUE     => 11,
                Field::FIELD_PARAM_OTHER          => 'UNSIGNED NOT NULL'
            ],
            'search_word'    => [
                Field::FIELD_PARAM_TYPE       => Field::TYPE_VARCHAR,
                Field::FIELD_PARAM_TYPE_VALUE => 50,
                Field::FIELD_PARAM_OTHER      => 'NOT NULL'
            ],
            'total'          => [
                Field::FIELD_PARAM_TYPE       => Field::TYPE_INT,
                Field::FIELD_PARAM_TYPE_VALUE => 10,
                Field::FIELD_PARAM_OTHER      => 'NOT NULL DEFAULT 1'
            ],
            'time_stamp'      => [
                Field::FIELD_PARAM_TYPE       => Field::TYPE_INT,
                Field::FIELD_PARAM_TYPE_VALUE => 10,
                Field::FIELD_PARAM_OTHER      => 'UNSIGNED NOT NULL DEFAULT 0'
            ],
        ];
    }

    public function setKeys()
    {
        $this->_key = [];
    }
}