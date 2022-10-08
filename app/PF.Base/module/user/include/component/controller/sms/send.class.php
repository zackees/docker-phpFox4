<?php
defined('PHPFOX') or exit('NO DICE!');
define('PHPFOX_DONT_SAVE_PAGE', true);

/**
 * Class User_Component_Controller_Sms_Send
 */
class User_Component_Controller_Sms_Send extends Phpfox_Component
{
    /**
     * Class process method which is used to execute this component.
     */
    public function process()
    {
        $bForceVerify = $this->request()->get('force', false);
        if (Phpfox::getUserId() && !$bForceVerify) {
            Phpfox::getLib('session')->remove('sms_verify_email');
            Phpfox::getLib('session')->remove('sms_verify_phone');
            $this->url()->send('');
        }
        $iStep = 1;
        $aVals = $this->request()->get('val');
        $isSent = $this->request()->get('sent');
        $isResend = $this->request()->get('resend');
        $sSessionPhone = Phpfox::getLib('session')->get('sms_verify_phone');
        $sPhone = $sSessionPhone ? $sSessionPhone : (!empty($aVals['phone']) ? $aVals['phone'] : '');
        $sEmail = Phpfox::getLib('session')->get('sms_verify_email');

        if (!Phpfox::isUser() && empty($sEmail) && empty($sPhone)) {
            $this->url()->send('');
        }

        if (!empty($aVals['phone']) || !empty($isSent) || !empty($isResend)) {
            $iStep = 2;
        } elseif (!empty($aVals['publish'])) {
            Phpfox_Error::set(_p('provide_a_valid_phone_number'));
        }

        if (!empty($aVals['verify_sms_token'])) {
            $iStep = 3;
        } elseif ($iStep == 2 && !empty($aVals['publish_passcode'])) {
            Phpfox_Error::set(_p('provide_valid_verification_code'));
        }

        if (!empty($aVals['change_phone'])) {
            $iStep = 1;
        }

        if ($iStep == 1 && !empty($sSessionPhone)) {
            $this->url()->send('user.sms.send', ['sent' => 1], _p('you_still_need_to_verify_your_account'));
        }

        $oService = Phpfox::getLib('phpfox.verify');

        if ($iStep == 2 && (!$isSent || !empty($aVals['resend_passcode'])) && Phpfox_Error::isPassed()) {

            if (!empty($aVals['email'])) {
                $sEmail = $aVals['email'];
                Phpfox::getLib('session')->set('sms_verify_email', $sEmail);
            }

            if (!empty($sEmail) && empty($sSessionPhone) && !Phpfox::getService('user.validate')->phone($sPhone)) {
                $iStep = 1;
            } else {
                $oPhone = Phpfox::getLib('phone');
                $oPhone->setRawPhone($sPhone);
                if ($oPhone->isValidPhone()) {
                    $sPhone = $oPhone->getPhoneE164();
                }

                $sSendToken = Phpfox::getService('user.verify')->getVerifyHashByEmail(!empty($sSessionPhone) ? $sPhone : $sEmail, !empty($sSessionPhone), false, true);

                $sSendToken = substr($sSendToken, 0, 3) . ' ' . substr($sSendToken, 3);

                $sMsg = _p('sms_registration_verification_message', ['token' => $sSendToken]);

                $bResult = $oService->sendSMS($sPhone, $sMsg);

                if (!$bResult) {
                    Phpfox_Error::set(_p('invalid_phone_number_or_contact_admin', ['phone' => $sPhone]));
                    $iStep = 1;
                } elseif (!$isResend) {
                    Phpfox::addMessage(_p('new_passcode_successfully_sent_to_your_phone_number'));
                }
            }
        }

        if ($iStep == 3 && Phpfox_Error::isPassed()) {
            $sToken = $aVals['verify_sms_token'];
            if ($iUserId = Phpfox::getService('user.verify.process')->verify($sToken, true, false, !empty($sSessionPhone) ? $sPhone : $sEmail)) {
                if ($sPlugin = Phpfox_Plugin::get('user.component_verify_process_redirection')) {
                    eval($sPlugin);
                }
                if (empty($sSessionPhone) && !empty($sEmail) && !empty($sPhone)) {
                    //Update phone to user when signup by email
                    $oPhone = Phpfox::getLib('phone');
                    $oPhone->setRawPhone($sPhone);
                    $sFullPhone = $oPhone->getPhoneE164();
                    Phpfox::getService('user.process')->updateUserFields($iUserId, [
                        'full_phone_number' => $sFullPhone,
                        'phone_number' => $oPhone->getPhoneNational()
                    ]);
                    if (Phpfox::isModule('invite')) {
                        Phpfox::getService('invite.process')->registerByEmail([
                            'user_id' => $iUserId,
                            'email' => $sEmail,
                            'full_phone_number' => $sFullPhone
                        ]);
                    }
                }
                $sRedirect = Phpfox::getParam('user.redirect_after_signup');

                if (!empty($sRedirect)) {
                    Phpfox::getLib('session')->set('redirect', $sRedirect);
                }

                //remove session after success
                Phpfox::getLib('session')->remove('sms_verify_email');
                Phpfox::getLib('session')->remove('sms_verify_phone');
                // send to the log in and say everything is ok
                Phpfox::getLib('session')->set('verified_do_redirect', '1');
                if (Phpfox::getParam('user.approve_users')) {
                    $this->url()->send('', null, _p('your_account_is_pending_approval'));
                }
                $bIsUpdateAccount = Phpfox::getLib('session')->get('verified_account_changed');
                Phpfox::getLib('session')->remove('verified_account_changed');
                $this->url()->send('user.login', ['t' => PHPFOX_TIME],
                    _p($bIsUpdateAccount && Phpfox::isUser() ?
                        'your_phone_number_has_been_verified_please_go_to_account_setting_to_view_the_information_you_changed' :
                        'your_account_has_been_verified_please_log_in_with_the_information_you_provided_during_sign_up'));
            } else {
                Phpfox_Error::set(_p('invalid_verification_token'));
            }
        }

        $this->template()
            ->assign([
                'iStep' => $iStep,
                'sPhone' => $sPhone,
                'sEmail' => $sEmail,
                'bIgnoreEmail' => !empty($sSessionPhone),
                'bForceVerify' => $bForceVerify,
                'bIsSent' => $isSent
            ])
            ->setTitle(_p('account_verification'))
            ->setBreadCrumb(_p('account_verification'))
            ->setHeader('cache', array(
                    'jquery/plugin/intlTelInput.js' => 'static_script',
                )
            );
    }
}
