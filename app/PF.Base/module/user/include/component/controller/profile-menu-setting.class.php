<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Controller_Profile_Menu_Setting
 */
class User_Component_Controller_Profile_Menu_Setting extends Phpfox_Component
{
    public function process()
    {
        Phpfox::isUser(true);

        if ($aVals = $this->request()->get('val')) {
            if (Phpfox::getService('user.privacy.process')->update($aVals)) {
                $this->url()->send('current', [], _p('profile_menu_settings_update_successfully'));
            }
        }

        $mUser = Phpfox::getUserBy('user_name');
        $aUser = Phpfox::getService('user')->get($mUser, false);
        $aProfileLinks = Phpfox::getService('profile')->getProfileMenu($aUser);
        $aUserPrivacy = Phpfox::getService('user.privacy')->getUserPrivacy(Phpfox::getUserId());
        $aProfileMenuPrivacies = [];

        ($plugin = Phpfox_Plugin::get('user.controller_profilemenusetting_start')) && eval($plugin);

        foreach ($aProfileLinks as $aProfileLink) {
            if ($aProfileLink['actual_url'] == "profile_activity-statistics" || $aProfileLink['actual_url'] == "profile_attachment") {
                continue;
            }
            $aModules = explode('_', $aProfileLink['actual_url']);
            $sModule = $aModules[1];
            if (!$sModule) {
                continue;
            }
            $aMenu = [
                'phrase' => $aProfileLink['phrase'],
                'default' => !empty($aUserPrivacy[$sModule . '.' . $aProfileLink['actual_url'] . '_menu']) ? $aUserPrivacy[$sModule . '.' . $aProfileLink['actual_url'] . '_menu'] : 0
            ];
            $aProfileMenuPrivacies[$sModule . '.' . $aProfileLink['actual_url'] . '_menu'] = $aMenu;
        }
        $this->template()->setTitle(_p('profile_menu_settings'))
            ->setBreadCrumb(_p('profile_menu_settings'))
            ->assign([
                'aProfileMenuPrivacies' => $aProfileMenuPrivacies,
            ])->buildSectionMenu('user', [
                _p('account_settings') => 'user.setting',
                _p('privacy_settings') => 'user.privacy',
                _p('profile_menu_settings') => 'user.profile-menu-setting'

            ]);
    }
}