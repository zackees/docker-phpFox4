<?php
defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Controller_News extends Phpfox_Component
{
    public function process()
    {
        $isSlide = $this->request()->get('slide', 0);
        $aNews = Phpfox::getService('core.admincp')->getNews($isSlide);

        if ($aNews === false) {
            return false;
        }

        echo $this->template()
            ->assign(array(
                'aPhpfoxNews' => $aNews,
                'isSlide' => $isSlide
            ))
            ->getTemplate('core.controller.news', true);
        exit;
    }
}