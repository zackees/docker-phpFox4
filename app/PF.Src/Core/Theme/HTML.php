<?php

namespace Core\Theme;

class HTML extends \Core\Model
{
    private $_theme;

    public function __construct(\Core\Theme\Objects $Theme)
    {
        parent::__construct();

        $this->_theme = $Theme;
    }

    public function set($content)
    {

        $dir = $this->_theme->getPath() . 'html/';
        if (!is_dir($dir)) {
            mkdir($dir);
        }

        $path = $this->_theme->getPath() . 'html/layout.html';
        file_put_contents($path, $content);

        $twig = PHPFOX_DIR_FILE . 'cache/twig/';
        if (is_dir($twig)) {
            \Phpfox_File::instance()->delete_directory($twig);
        }
        return true;
    }

    public function get()
    {

        $html = file_get_contents($this->getFile());

        return $html;
    }

    public function getFile()
    {
        $html = $this->_theme->getPath() . 'html/layout.html';
        if (!file_exists($html)) {
            $html = $this->_theme->basePath() . 'html/layout.html';
        }

        return $html;
    }
}