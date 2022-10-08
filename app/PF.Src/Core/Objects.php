<?php

namespace Core;

class Objects
{
    public function __construct($objects)
    {
        foreach ($objects as $key => $value) {
            $this->$key = $value;
        }
    }

    public function __get($param)
    {
        if (!PHPFOX_DEBUG) {
            return '';
        }

        throw error(_p('missing_param_s'), $param);
    }
}