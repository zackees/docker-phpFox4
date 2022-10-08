<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Admincp_Component_Controller_Cron_Manager extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $cronItems = Phpfox::getService('admincp.cron')->getAll();
        $this->template()->setTitle(_p('menu_cron_manager'))
            ->setBreadCrumb(_p('menu_cron_manager'), $this->url()->makeUrl('admincp.cron.manager'))
            ->setActiveMenu('admincp.maintain.cron')
            ->assign([
                'cronItems' => $cronItems,
            ]);
    }
}