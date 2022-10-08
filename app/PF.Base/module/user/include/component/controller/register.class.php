<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Controller_Register
 */
class User_Component_Controller_Register extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        if (!Phpfox::getParam('user.allow_user_registration')) {
            $this->url()->send('');
        }

        define('PHPFOX_DONT_SAVE_PAGE', true);

        if (Phpfox::isUser()) {
            $this->url()->send('profile');
        }
        $aValidateParams = Phpfox::getService('user.register')->getValidation();
        $oValid = Phpfox_Validator::instance()->set([
            'sFormName' => 'js_form',
            'aParams' => $aValidateParams
        ]);
        if ($aVals = $this->request()->getArray('val')) {
            if (Phpfox::isModule('invite') && Phpfox::getService('invite')->isInviteOnly()) {
                if (Phpfox::getService('invite')->isValidInvite($aVals['invite_email'])) {
                    $iExpire = (Phpfox::getParam('invite.invite_expire') > 0 ? (Phpfox::getParam('invite.invite_expire') * 60 * 60 * 24) : (7 * 60 * 60 * 24));
                    Phpfox::setCookie('invite_only_pass', $aVals['invite_email'], PHPFOX_TIME + $iExpire);
                    $this->url()->send('user.register');
                }
            } else {
                (($sPlugin = Phpfox_Plugin::get('user.component_controller_register_1')) ? eval($sPlugin) : false);
                $bPhoneEnabled = Phpfox::getParam('core.enable_register_with_phone_number');
                if ($bPhoneEnabled && !filter_var($aVals['email'], FILTER_VALIDATE_EMAIL)) {
                    Phpfox::getService('user.validate')->phone($aVals['email'], false, true, null, true);
                } else {
                    Phpfox::getService('user.validate')->email($aVals['email']);
                }
                if (Phpfox::getParam('user.split_full_name')
                    && Phpfox::getParam('user.disable_username_on_sign_up') != 'username') {
                    if (empty($aVals['first_name']) || empty($aVals['last_name'])) {
                        unset($aValidateParams['full_name']);
                        //Update validator
                        $oValid = Phpfox_Validator::instance()->set([
                            'sFormName' => 'js_form',
                            'aParams' => $aValidateParams
                        ]);
                    } else {
                        $aVals['full_name'] = $aVals['first_name'] . ' ' . $aVals['last_name'];
                    }
                }


                (($sPlugin = Phpfox_Plugin::get('user.component_controller_register_2')) ? eval($sPlugin) : false);

                if (Phpfox_Error::isPassed() && $oValid->isValid($aVals)) {
                    if ($iId = Phpfox::getService('user.process')->add($aVals, null, false, false, true)) {
                        Phpfox::setCookie('invite_only_pass', null, '-1');
                        if (defined('PHPFOX_FORCE_VERIFY_PHONE_NUMBER') && PHPFOX_FORCE_VERIFY_PHONE_NUMBER) {
                            $this->url()->send('user.sms.send', ['sent' => 1]);
                        }
                        if (!defined('PHPFOX_INSTALLER') && Phpfox::getParam('core.registration_sms_enable')) {
                            $this->url()->send('user.sms.send');
                        }
                        if (Phpfox::getService('user.auth')->login($aVals['email'], $aVals['password'])) {
                            if (is_array($iId)) {
                                (($sPlugin = Phpfox_Plugin::get('user.component_controller_register_3')) ? eval($sPlugin) : false);
                                $this->url()->forward($iId[0]);
                            } else {
                                $sRedirect = Phpfox::getParam('user.redirect_after_signup');

                                if (!empty($sRedirect)) {
                                    (($sPlugin = Phpfox_Plugin::get('user.component_controller_register_4')) ? eval($sPlugin) : false);
                                    $this->url()->send($sRedirect);
                                }

                                (($sPlugin = Phpfox_Plugin::get('user.component_controller_register_6')) ? eval($sPlugin) : false);
                                if (Phpfox::getLib('session')->get('appinstall') != '') {
                                    $this->url()->send('apps.install.' . Phpfox::getLib('session')->get('appinstall'));
                                } else {
                                    $this->url()->send('');
                                }
                            }
                        }
                    } else {
                        if (Phpfox::getParam('user.multi_step_registration_form')) {
                            $this->template()->assign('bIsPosted', true);
                            (($sPlugin = Phpfox_Plugin::get('user.component_controller_register_7')) ? eval($sPlugin) : false);
                        }
                    }
                } else {
                    if (!empty($aVals['gender']) && !empty($aVals['custom_gender']) && $aVals['gender'] == 'custom') {
                        $this->template()->setHeader('cache', [
                            '<script>aUserGenderCustom = ' . json_encode($aVals['custom_gender']) . '; bIsCustomGender = true;</script>'
                        ]);
                    }

                    $this->template()->assign(array(
                            'sUsername' => ((!Phpfox::getParam('user.profile_use_id') && (Phpfox::getParam('user.disable_username_on_sign_up') != 'full_name')) ? $aVals['user_name'] : ''),
                            'iTimeZonePosted' => (isset($aVals['time_zone']) ? $aVals['time_zone'] : 0)
                        )
                    );

                    if (Phpfox::getParam('user.multi_step_registration_form')) {
                        $this->template()->assign('bIsPosted', true);
                    }

                    $this->setParam(array(
                            'country_child_value' => (isset($aVals['country_iso']) ? $aVals['country_iso'] : 0),
                            'country_child_id' => (isset($aVals['country_child_id']) ? $aVals['country_child_id'] : 0)
                        )
                    );
                }
            }
        } else {
            if (($sSentCookie = Phpfox::getCookie('invited_by_email_form'))) {
                $this->template()->assign('message', _p(Phpfox::getParam('core.enable_register_with_phone_number') ? 'you_can_register_with_invited_email_or_phone_number' : 'you_can_register_with_invited_email'));
            }
        }

        $sTitle = _p('sign_and_start_using_site', array('site' => Phpfox::getParam('core.site_title')));

        (($sPlugin = Phpfox_Plugin::get('user.component_controller_register_8')) ? eval($sPlugin) : false);

        if ($iPackageId = $this->request()->get('selected_package_id')) {
            $this->template()->assign('aForms', [
                'package_id' => $iPackageId
            ]);
        }

        $this->template()->setTitle($sTitle)
            ->setBreadCrumb(_p('sign_up_title'))
            ->setPhrase(array(
                    'continue'
                )
            )
            ->setHeader('cache', array(
                    'register.css' => 'module_user',
                    'country.js' => 'module_core',
                    'jquery/plugin/intlTelInput.js' => 'static_script',
                )
            )
            ->assign(array(
                    'sCreateJs' => $oValid->createJS(),
                    'sGetJsForm' => $oValid->getJsForm(),
                    'sSiteUrl' => Phpfox::getParam('core.path'),
                    'aTimeZones' => Phpfox::getService('core')->getTimeZones(),
                    'aPackages' => (Phpfox::isAppActive('Core_Subscriptions') ? Phpfox::getService('subscribe')->getPackages(true) : null),
                    'aSettings' => Phpfox::getService('custom')->getForEdit(array(
                        'user_main',
                        'user_panel',
                        'profile_panel'
                    ), null, null, true),
                    'sDobStart' => Phpfox::getParam('user.date_of_birth_start'),
                    'sDobEnd' => Phpfox::getParam('user.date_of_birth_end'),
                    'sUserEmailCookie' => Phpfox::getCookie('invited_by_email_form'),
                    'sSiteTitle' => Phpfox::getParam('core.site_title'),
                    'sEmailClass' => 'email' . PHPFOX_TIME,
                    'sConfirmEmailClass' => 'confirm_email' . PHPFOX_TIME,
                    'sPasswordDescription' => _p(Phpfox::getParam('user.required_strong_password') ? 'strong_password_form_description' : 'normal_password_form_description',
                        ['min' => Phpfox::getParam('user.min_length_for_password'), 'max' => Phpfox::getParam('user.max_length_for_password')])
                )
            );
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('user.component_controller_register_clean')) ? eval($sPlugin) : false);
    }
}
