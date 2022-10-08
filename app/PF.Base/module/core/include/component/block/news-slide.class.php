<?php

defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Core_Component_Block_News_Slide
 */
class Core_Component_Block_News_Slide extends Phpfox_Component
{
    public function process()
    {
        if (!Phpfox::getUserParam('core.can_view_news_updates')) {
            return false;
        }
        return 'block';
    }

    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('core.component_block_news_slide_clean')) ? eval($sPlugin) : false);
    }
}