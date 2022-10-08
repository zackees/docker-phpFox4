<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * @copyright [PHPFOX_COPYRIGHT]
 * @author phpFox LLC
 * Class Track_Component_Controller_Admincp_Index
 */
class Track_Component_Controller_Admincp_Index extends Phpfox_Component
{
    public function process()
    {
        $this->url()->send('admincp.setting.edit', ['module-id' => 'track']);
    }
}