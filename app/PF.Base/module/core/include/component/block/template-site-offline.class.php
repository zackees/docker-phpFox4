<?php
defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Block_Template_Site_Offline extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        (($sPlugin = Phpfox_Plugin::get('core.component_block_template_site_offline_process')) ? eval($sPlugin) : false);
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('core.component_block_template_site_offline_clean')) ? eval($sPlugin) : false);
    }
}