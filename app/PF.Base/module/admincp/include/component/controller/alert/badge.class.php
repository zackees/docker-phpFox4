<?php
defined('PHPFOX') or exit('NO DICE!');

class Admincp_Component_Controller_Alert_Badge extends Phpfox_Component
{
    public function process()
    {
        $badge = Phpfox::getService('admincp.alert')->getAdminMenuBadgeNumber();
        if ($badge > 99) {
            $badge = '99+';
        }

        echo $this->template()
            ->assign(array(
                'badge' => $badge,
            ))
            ->getTemplate('admincp.controller.alert.badge', true);
        exit;
    }
}