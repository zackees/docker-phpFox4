<?php

namespace Core\Theme;

class Design extends \Core\Model
{
    private $_theme;
    private $_service;

    public function __construct(\Core\Theme\Objects $Theme)
    {
        parent::__construct();

        $this->_theme = $Theme;
        $this->_service = new Service($this->_theme);
    }
}