<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Admincp_Component_Controller_Demo extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        // check authorization
        Phpfox::isUser(true);
        Phpfox::getUserParam('admincp.has_admin_access', true);

        $this->template()->setTitle('AdminCP Demo Mode');
    }
}