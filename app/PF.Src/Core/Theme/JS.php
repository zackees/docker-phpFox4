<?php

namespace Core\Theme;

class JS extends \Core\Model
{
    private $_theme;

    public function __construct(\Core\Theme\Objects $Theme)
    {
        parent::__construct();

        $this->_theme = $Theme;
    }

    public function set($content)
    {

        $path = $this->_theme->getPath() . 'assets/';
        if (!is_dir($path)) {
            mkdir($path);
        }
        file_put_contents($this->_theme->getPath() . 'assets/autoload.js', $content);

        return true;
    }

    public function get()
    {
        $path = $this->_theme->getPath() . 'assets/autoload.js';
        if (!file_exists($path)) {
            $path = $this->_theme->basePath() . 'assets/autoload.js';
        }
        $content = file_get_contents($path);

        return $content;
    }
}