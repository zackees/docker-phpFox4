<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Controller_Password_Request
 */
class User_Component_Controller_Password_Request extends Phpfox_Component
{
    const VERIFY_TYPE_ID = 'request_password';
    /**
     * Process the controller
     *
     */
    public function process()
    {
        if (Phpfox::isUser()) {
            $this->url()->send('');
        }
        if (Phpfox::getParam('core.enable_register_with_phone_number')) {
            $aValidation['email'] = [
                'def'   => 'email',
                'subdef' => 'phone:required',
                'title' => _p('provide_a_valid_email_address_or_phone_number')
            ];
        } else {
            $aValidation['email'] = _p('enter_your_email');
        }

        $aVals = $this->request()->getArray('val');

        if (Phpfox::isAppActive('Core_Captcha') && empty($aVals['resend_passcode'])) {
            $aValidation['image_verification'] = _p('complete_captcha_challenge');
        }
        $oValid = Phpfox_Validator::instance()->set(array(
            'sFormName' => 'js_request_password_form',
            'aParams'   => $aValidation
        ));
        $sResult = false;
        $bInputInvalidCode = false;
        if ($aVals) {
            if ($oValid->isValid($aVals)) {
                if (!empty($aVals['verify_code'])) {
                    $sToken = $aVals['verify_sms_token'];
                    if (Phpfox::getService('user.verify.process')->verify($sToken, true, true, null, self::VERIFY_TYPE_ID)) {
                        $this->url()->send('user.password.verify', [
                            'id' => isset($aVals['request_hash']) ? $aVals['request_hash'] : '',
                            'is_phone' => !empty($aVals['is_phone_verify']) ? $aVals['is_phone_verify'] : '',
                        ]);
                    } else {
                        $bInputInvalidCode = true;
                        $sResult = $aVals['request_hash'];
                        $this->template()->assign(['isPhoneVerify' => !empty($aVals['is_phone_verify'])]);
                        Phpfox_Error::set(_p('invalid_verification_token'));
                    }
                } elseif ($sResult = Phpfox::getService('user.password')->requestPassword($aVals)) {
                    if ($sResult === true) {
                        $this->url()->send('user.login', null,
                            _p('password_request_successfully_sent_check_your_email_to_verify_your_request'));
                    } elseif ($sResult !== false) {
                        $this->template()->assign(['isPhoneVerify' => true]);
                        Phpfox::addMessage(_p(!empty($aVals['resend_passcode']) ? 'new_passcode_successfully_sent_to_your_phone_number' : 'passcode_successfully_sent_to_your_phone_number'));
                    }
                }
            } else {
                $bInputInvalidCode = !empty($aVals['request_hash']);
                $sResult = $aVals['request_hash'];
                $this->template()->assign(['isPhoneVerify' => !empty($aVals['is_phone_verify'])]);
            }
        }

        $this->template()
            ->setTitle(_p('password_request'))
            ->setHeader([
                'jquery/plugin/intlTelInput.js' => 'static_script'
            ])
            ->assign([
                'aForms' => $aVals,
                'sHash' => $sResult,
                'bPassCodeForm' => $sResult && $sResult !== true || !empty($aVals['resend_passcode']) || $bInputInvalidCode
            ])
            ->setBreadCrumb(_p('password_request'));
    }
}
