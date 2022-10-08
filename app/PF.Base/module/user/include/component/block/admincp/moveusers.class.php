<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Block_Admincp_Setting
 */
class User_Component_Block_Admincp_Moveusers extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $iIds = $this->getParam('user_ids');
        if (empty($iIds)) {
            return false;
        }
        $this->template()->assign([
            'aGroups' => Phpfox::getService('user.group')->get(),
            'aUserIds' => $iIds
        ]);
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('user.component_block_admincp_moveusers_clean')) ? eval($sPlugin) : false);
    }
}
