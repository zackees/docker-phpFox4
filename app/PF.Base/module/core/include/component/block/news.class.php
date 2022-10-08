<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Core_Component_Block_News
 */
class Core_Component_Block_News extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        if (!Phpfox::getUserParam('core.can_view_news_updates')) {
            return false;
        }
        return 'block';
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('core.component_block_news_clean')) ? eval($sPlugin) : false);
    }
}
