<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Controller_Privacy
 */
class User_Component_Controller_Privacy extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        Phpfox::isUser(true);

        if (($aVals = $this->request()->getArray('val'))) {
            if (Phpfox::getService('user.privacy.process')->update($aVals)) {
                $this->url()->send('user.privacy', ['tab' => empty($aVals['current_tab']) ? '' : $aVals['current_tab']], _p('privacy_settings_successfully_updated'));
            }
        }

        list($aUserPrivacy, $aNotifications, $aProfiles, $aItems, $aSmsNotifications) = Phpfox::getService('user.privacy')->get();

        $aUserInfo = Phpfox::getService('user')->get(Phpfox::getUserId());
        $aMenus = [];

        (($sPlugin = Phpfox_Plugin::get('user.component_controller_privacy_process')) ? eval($sPlugin) : false);

        if (Phpfox::getUserParam('user.can_control_profile_privacy')) {
            $aMenus['profile'] = _p('profile');
        }
        $aMenus['items'] = _p('items');
        if (Phpfox::getUserParam('user.can_control_notification_privacy')) {
            if (!empty($aUserInfo['email'])) {
                $aMenus['notifications'] = _p('email_notifications');
            }
            if (!empty($aUserInfo['full_phone_number']) && Phpfox::getParam('core.enable_register_with_phone_number')) {
                $aMenus['sms_notifications'] = _p('sms_notifications');
            }
        }
        $aMenus['blocked'] = _p('blocked_users');

        if (Phpfox::getUserParam('user.hide_from_browse')) {
            $aMenus['invisible'] = _p('invisible_mode');
        }
        if (!Phpfox::isModule('privacy')) {
            unset($aMenus['items']);
        }

        $this->template()->buildPageMenu('js_privacy_block',
            $aMenus,
            [
                'no_header_border' => true,
                'link'             => $this->url()->makeUrl(Phpfox::getUserBy('user_name')),
                'phrase'           => _p('view_your_profile')
            ]
        );

        if ($this->request()->get('view') == 'blocked') {
            $this->template()->assign(['bGoToBlocked' => true]);
        }
        $this->template()->setTitle(_p('privacy_settings'))
            ->setBreadCrumb(_p('account'), $this->url()->makeUrl('profile'))
            ->setBreadCrumb(_p('privacy_settings'), $this->url()->makeUrl('user.privacy'), true)
            ->setHeader([
                    'privacy.css'          => 'module_user',
                    'search-user-block.js' => 'module_user',
                ]
            )
            ->assign([
                'aForms'                => $aUserPrivacy['privacy'],
                'aPrivacyNotifications' => $aNotifications,
                'aSmsNotifications'     => $aSmsNotifications,
                'aProfiles'             => $aProfiles,
                'aUserPrivacy'          => $aUserPrivacy,
                'aBlockedUsers'         => Phpfox::getService('user.block')->get(),
                'aUserInfo'             => $aUserInfo,
                'aItems'                => $aItems
            ])->buildSectionMenu('user', [
                _p('account_settings')      => 'user.setting',
                _p('privacy_settings')      => 'user.privacy',
                _p('profile_menu_settings') => 'user.profile-menu-setting'
            ]);

        return null;
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('user.component_controller_privacy_clean')) ? eval($sPlugin) : false);
    }
}
