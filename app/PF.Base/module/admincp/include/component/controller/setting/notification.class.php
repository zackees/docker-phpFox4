<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Admincp_Component_Controller_Setting_Edit
 */
class Admincp_Component_Controller_Setting_Notification extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        Phpfox::isAdmin(true);
        $sType = $this->request()->get('type', 'email');
        $aNotifications = Phpfox::getService('admincp.setting')->getDefaultNotificationSettings($sType);
        $this->template()
            ->clearBreadCrumb()
            ->setTitle(_p('notification_settings'))
            ->setBreadCrumb(_p('notification_settings'), $this->url()->makeUrl('admincp.setting.notification'))
            ->setActiveMenu('admincp.setting.notification')
            ->assign([
                'aSectionAppMenus' => [
                    _p('email_notifications') => [
                        'url' => $this->url()->makeUrl('admincp.setting.notification', ['type' => 'email']),
                        'is_active' => $sType == 'email'
                    ],
                    _p('sms_notifications') => [
                        'url' => $this->url()->makeUrl('admincp.setting.notification', ['type' => 'sms']),
                        'is_active' => $sType == 'sms'
                    ],
                ],
                'aNotifications' => $aNotifications,
                'sType' => $sType,
                'sDescription' => _p("default_{$sType}_notification_setting_description")
            ]);
        if ($aVals = $this->request()->getArray('val')) {
            Phpfox::getService('admincp.setting.process')->updateDefaultNotificationSettings($aVals, $sType);
            $this->url()->send('admincp.setting.notification', ['type' => $sType], _p('default_notification_settings_updated_successfully'));
        }
        (($sPlugin = Phpfox_Plugin::get('admincp.component_controller_setting_notification_process')) ? eval($sPlugin) : false);

        return null;
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('admincp.component_controller_setting_notification_clean')) ? eval($sPlugin) : false);
    }
}