<?php
defined('PHPFOX') or exit('NO DICE!');
define('PHPFOX_DONT_SAVE_PAGE', true);

/**
 * Class User_Component_Controller_Passcode
 *
 * This controller receives the link for verifying a member's email address
 */
class User_Component_Controller_Passcode extends Phpfox_Component
{
    /**
     * Class process method which is used to execute this component.
     */
    public function process()
    {
        if (!Phpfox::isUser()) {
            Phpfox_Url::instance()->send('/');
        }
        Phpfox::getUserParam('user.can_use_2step_verification', true);
        $this->template()
            ->setTitle(_p('how_to_get_passcode'))
            ->setBreadCrumb(_p('how_to_get_passcode'));

        $sQRCodeUrl = '';
        $oService = Phpfox::getService('user.googleauth');
        $iPassValidate = Phpfox::getCookie('account_setting_pass_tsv');
        $aVals = $this->request()->getArray('val');
        $aUser=  Phpfox::getService('user')->get(Phpfox::getUserId());
        if (!$aUser['two_step_verification']) {
            Phpfox_Error::display(_p('you_must_enable_two_step_verification_in_your_account_setting_first'));
        }
        $sEmail =  !empty($aUser['email']) ? $aUser['email'] : '';
        $sPhone = !empty($aUser['full_phone_number']) ? $aUser['full_phone_number'] : '';
        $sUserString = trim(implode(',', [$sEmail, $sPhone]), ',');
        if (!empty($aVals['password']) && Phpfox::getService('user.auth')->checkPassword($aUser, $aVals['password'])) {
            Phpfox::setCookie('account_setting_pass_tsv', '1', PHPFOX_TIME + 900, !!Phpfox::getParam('core.force_https_secure_pages'));
            $this->url()->send('user.passcode');
        }

        if($iPassValidate && (!empty($sEmail) || !empty($sPhone))) {
            if (!empty($this->request()->get('change_code'))) {
                if ($oService->deleteUser($sUserString)) {
                    $this->url()->send('user.passcode', [], _p('revoked_old_two_step_qr_code_successfully'));
                }
            }
            $oService->setUser($sUserString);

            $sTargetUrl = $oService->createUrl($sUserString);

            $sQRCodeUrl = 'https://chart.googleapis.com/chart?' . http_build_query([
                    'cht' => 'qr',
                    'chl' => $sTargetUrl,
                    'chs' => '300x300',
                    'choe' => 'UTF-8',
                ]);
        }

        $this->template()
            ->setHeader([
                'clipboard.min.js' => 'static_script',
            ])
            ->assign([
                'sEmail' => $sEmail,
                'sPhone' => $sPhone,
                'iPassValidate' => $iPassValidate,
                'sQRCodeUrl' => $sQRCodeUrl,
                'sHexKey' => $oService->getHexkey($sUserString),
                'bRequiredChangePassword' => !empty(storage()->get('fb_new_users_' . Phpfox::getUserId())),
                'sAccountSettingLink' => $this->url()->makeUrl('user.setting')
            ]);

    }
}