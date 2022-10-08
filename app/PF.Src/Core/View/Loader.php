<?php

namespace Core\View;

class Loader extends \Twig_Loader_Filesystem
{
    /**
     * @var string override layout file
     */
    public $layout = null;

    /**
     * Custom find layout template
     * @param $name
     * @return bool|string
     */
    protected function findTemplate($name)
    {
        if ($name == '@Theme/macro/form.html' && request()->segment(1) == 'admincp') {
            $file = PHPFOX_DIR . 'theme/default/html/macro/form.html';

            return $file;
        }

        if ($name == '@Theme/layout.html') {
            \Core\Event::trigger('Core\View\Loader::getSource', $this);
            if ($this->layout !== null) {
                return $this->layout;
            }

            $Theme = \Phpfox_Template::instance()->theme()->get();
            $Service = new \Core\Theme\Service($Theme);
            return $Service->html()->getFile();

        } else {
            if (substr($name, 0, 7) == '@Theme/') {
                \Core\Event::trigger('Core\View\Loader::getCustomSource', $this, substr($name, 7));
                if ($this->layout !== null) {
                    return $this->layout;
                }

                $Theme = \Phpfox_Template::instance()->theme()->get();
                $name = str_replace('@Theme/', '', $name);
                $file = $Theme->getPath() . 'html/' . $name;

                if (!file_exists($file)) {
                    $file = PHPFOX_DIR . 'theme/default/html/' . $name;
                }

                return $file;
            }
        }

        return parent::findTemplate($name);
    }
}