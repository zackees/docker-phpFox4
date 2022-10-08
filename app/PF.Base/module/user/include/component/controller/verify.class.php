<?php
defined('PHPFOX') or exit('NO DICE!');
define('PHPFOX_DONT_SAVE_PAGE', true);

/**
 * Class User_Component_Controller_Verify
 *
 * This controller receives the link for verifying a member's email address
 */
class User_Component_Controller_Verify extends Phpfox_Component
{
    /**
     * Class process method which is used to execute this component.
     */
    public function process()
    {
        $sHash = $this->request()->get('link', '');
        $bRedirectLogin = false;
        $sRedirectMessage = null;

        if ($sHash != '') {
            if (Phpfox::getService('user.verify.process')->verify($sHash)) {
                if ($sPlugin = Phpfox_Plugin::get('user.component_verify_process_redirection')) {
                    eval($sPlugin);
                }
                //Remove cache user id after verified
                Phpfox::getLib('session')->remove('cache_user_id');
                if (Phpfox::isUser()) {
                    $bRedirectLogin = true;
                    $sRedirectMessage = _p('your_email_has_been_verified');
                } else {
                    $sRedirect = Phpfox::getParam('user.redirect_after_signup');
                    if (!empty($sRedirect)) {
                        Phpfox::getLib('session')->set('redirect', $sRedirect);
                    }
                    // send to the log in and say everything is ok
                    Phpfox::getLib('session')->set('verified_do_redirect', '1');
                    if (Phpfox::getParam('user.approve_users')) {
                        $this->url()->send('', null, _p('your_account_is_pending_approval'));
                    }
                    $this->url()->send('user.login', null, _p('your_email_has_been_verified_please_log_in_with_the_information_you_provided_during_sign_up'));
                }
            } else {
                if (Phpfox::isUser()) {
                    $bRedirectLogin = true;
                } else {
                    //send to the log in and say there was an error
                    Phpfox_Error::set(_p('invalid_verification_link'));
                    if (Phpfox::getService('user.verify.process')->sendMail(Phpfox::getLib('session')->get('cache_user_id'))) {
                        $iTime = Phpfox::getParam('user.verify_email_timeout');
                        if ($iTime < 60) {
                            $sTime = _p('time_minutes', ['time' => $iTime]);
                        } elseif ($iTime < (60 * 60 * 24)) // one day
                        {
                            $sTime = ($iTime == 60 ? _p('time_hour', ['time' => round($iTime / 60)]) : _p('time_hours', ['time' => round($iTime / 60)]));
                        } else {
                            $sTime = _p('time_days', ['time' => round($iTime / (60 * 60 * 24))]);
                        }
                        $this->template()->assign(['sTime' => $sTime]);
                    } else {
                        $this->template()->assign(['bVerified' => true]);
                    }
                }
            }
        } elseif (Phpfox::isUser()) {
            $bRedirectLogin = true;
        }

        //Redirect to home page if logged
        if ($bRedirectLogin) {
            $this->url()->send('', null, $sRedirectMessage);
        }

        $canResend = false;

        if ($userId = Phpfox::getLib('session')->get('cache_user_id')) {
            $delayResendVerificationEmail = Phpfox::getParam('user.resend_verification_email_delay_time', 15);
            $time = Phpfox::getService('user.verify')->getVerificationTimeByUserId($userId);
            if ((int)$time > 0 && (PHPFOX_TIME - $delayResendVerificationEmail * 60) > (int)$time) {
                $canResend = true;
            }
        } else {
            $this->url()->send('');
        }

        $this->template()->setTitle(_p('email_verification'))->setBreadCrumb(_p('email_verification'))
            ->assign([
                    'iVerifyUserId' => Phpfox::getLib('session')->get('cache_user_id'),
                    'canResend' => $canResend
                ]
            );
    }
}
