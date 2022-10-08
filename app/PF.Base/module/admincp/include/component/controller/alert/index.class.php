<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Admincp_Component_Controller_Alert_Index extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $aItems = Phpfox::getService('admincp.alert')->getItems();
        $this->template()
            ->setTitle(_p('alerts'))
            ->setActiveMenu('admincp.alert')
            ->assign(['aItems' => $aItems])
            ->setBreadCrumb(_p('alerts'), '');
    }
}