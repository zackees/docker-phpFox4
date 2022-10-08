<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * @copyright [PHPFOX_COPYRIGHT]
 * @author phpFox LLC
 * Class Admincp_Component_Controller_App_Settings
 */
class Admincp_Component_Controller_App_Groupsettings extends Phpfox_Component {

    public function process() {

        $sAppId = $this->request()->get('id');
        $App = \Core\Lib::appInit($sAppId);

        $sModule = isset($App->alias)? $App->alias : $App->id;
        $iGroupId = $this->request()->get('group_id', 2);

        Phpfox::getLib('request')->set('module',$sModule);
        Phpfox::getLib('request')->set('product-id', $sAppId);
        Phpfox::getLib('request')->set('setting', 1);
        Phpfox::getLib('request')->set('group_id', $iGroupId);
        $this->setParam('bInAppDetail', $sAppId);
        Phpfox::getLib('module')->setController('user.admincp.group.add');

        return true;
    }
}