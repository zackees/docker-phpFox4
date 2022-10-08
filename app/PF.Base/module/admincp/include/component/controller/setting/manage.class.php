<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 *
 *
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		phpFox LLC
 * @package 		Phpfox_Component
 * @version 		$Id: manage.class.php 1390 2010-01-13 13:30:08Z phpFox LLC $
 */
class Admincp_Component_Controller_Setting_Manage extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $aModules = Phpfox::getService('admincp.setting')->getModules();
        $sModuleId = $this->request()->get('module-id');

        if (empty($sModuleId)) {
            if (!empty($aModules[0])) {
                $sModuleId = $aModules[0]['module_id'];
                $this->request()->set('module-id', $sModuleId);
            } else {
                return Phpfox_Error::display(_p('no_app_settings_found'));
            }
        }

        $oModuleObject = Phpfox::getLib('module');
        $oModuleObject->setController('admincp.setting.edit');
        $oModuleObject->dispatch('admincp.setting.manage');

        $this->template()
            ->clearBreadCrumb()
            ->setTitle(_p('app_settings'))
            ->setBreadCrumb(_p('app_settings'), $this->url()->makeUrl('admincp.setting.manage'))
            ->setActiveMenu('admincp.setting.manage')
            ->assign([
                'aSearchModules' => Phpfox::getService('admincp.setting')->getModules(),
                'sSelectedModuleId' => $sModuleId,
            ]);

        return 'controller';
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('admincp.component_controller_setting_manage_clean')) ? eval($sPlugin) : false);
    }
}