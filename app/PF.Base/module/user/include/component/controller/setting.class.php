<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Controller_Setting
 */
class User_Component_Controller_Setting extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        Phpfox::isUser(true);
        $aUser = Phpfox::getService('user')->get(Phpfox::getUserId());
        $aVals = $this->request()->getArray('val');
        $aValidation = array();

        if (!isset($aUser['user_id'])) {
            return Phpfox_Error::display(_p('unable_to_edit_this_account'));
        }

        if (Phpfox::getUserParam('user.can_change_email')) {
            $aValidation['email'] = array(
                'def' => 'email',
                'subdef' => Phpfox::getParam('core.enable_register_with_phone_number') ? 'no_required' : '',
                'title' => _p('provide_a_valid_email_address')
            );
        }

        if (Phpfox::getUserParam('user.can_change_phone')) {
            $aValidation['phone_number'] = array(
                'def' => 'phone',
                'title' => _p('provide_a_valid_phone_number')
            );
        }

        if (Phpfox::getUserParam('user.can_change_own_full_name') && Phpfox::getParam('user.validate_full_name')) {
            $aChange = ['max' => Phpfox::getParam('user.maximum_length_for_full_name')];
            $sTitle = Phpfox::getParam('user.display_or_full_name') == 'full_name' ?
                _p('provide_a_valid_full_name', $aChange) : _p('provide_a_valid_display_name', $aChange);
            $aValidation['full_name'] = array(
                'def' => 'full_name',
                'title' => $sTitle
            );
        }

        if (Phpfox::getParam('user.split_full_name')) {
            if (empty($aVals['first_name']) || empty($aVals['last_name'])) {
                unset($aValidation['full_name']);
            } else {
                $aVals['full_name'] = $aVals['first_name'] . ' ' . $aVals['last_name'];
            }
        }

        if (Phpfox::getUserParam('user.can_change_own_user_name') && !Phpfox::getParam('user.profile_use_id')) {
            $aUser['old_user_name'] = $aUser['user_name'];
            $aValidation['user_name'] = array(
                'def' => 'username',
                'title' => _p('provide_a_valid_user_name', array(
                    'min' => Phpfox::getParam('user.min_length_for_username'),
                    'max' => Phpfox::getParam('user.max_length_for_username')
                ))
            );
        }

        (($sPlugin = Phpfox_Plugin::get('user.component_controller_setting_process_validation')) ? eval($sPlugin) : false);

        $oValid = Phpfox_Validator::instance()->set(array('sFormName' => 'js_form', 'aParams' => $aValidation));

        if (count($aVals)) {
            (($sPlugin = Phpfox_Plugin::get('user.component_controller_setting_process_check')) ? eval($sPlugin) : false);

            if ($oValid->isValid($aVals)) {
                $bAllowed = true;
                $bChangedEmail = $bChangedPhone = false;
                $bRemoveEmail = $bRemovePhone = false;
                $sMessage = _p('account_settings_updated');
                if (Phpfox::getParam('core.enable_register_with_phone_number') && empty($aVals['phone_number'])
                    && !filter_var(isset($aVals['email']) ? $aVals['email'] : '',FILTER_VALIDATE_EMAIL)) {
                    Phpfox_Error::set(_p('provide_a_valid_email_address_or_phone_number'));
                    $bAllowed = false;
                }
                if (Phpfox::getUserParam('user.can_change_email') && isset($aVals['email'])) {
                    if (empty($aVals['email'])) {
                        $bRemoveEmail = true;
                    } elseif ($aUser['email'] != $aVals['email']) {
                        Phpfox::getService('user.validate')->email($aVals['email'], null, true);
                        if (!Phpfox_Error::isPassed()) {
                            $bAllowed = false;
                        } else {
                            $bChangedEmail = true;
                        }
                    }
                }
                if ($bAllowed && Phpfox::getUserParam('user.can_change_phone') && isset($aVals['phone_number'])) {
                    if (empty($aVals['phone_number'])) {
                        $bRemovePhone = true;
                    } else {
                        $oPhone = Phpfox::getLib('phone');
                        if ($oPhone->setRawPhone($aVals['phone_number']) && $oPhone->isValidPhone()) {
                            $sPhone = $oPhone->getPhoneE164();
                            if ($sPhone != $aUser['full_phone_number']) {
                                Phpfox::getService('user.validate')->phone($sPhone, true, false, null, false, true);
                                if (!Phpfox_Error::isPassed()) {
                                    $bAllowed = false;
                                } else {
                                    $bChangedPhone = true;
                                }
                            }
                        }
                    }
                }
                if ($bAllowed && ($iId = Phpfox::getService('user.process')->update(Phpfox::getUserId(), $aVals, array(
                        'changes_allowed' => Phpfox::getUserParam('user.total_times_can_change_user_name'),
                        'total_user_change' => $aUser['total_user_change'],
                        'full_name_changes_allowed' => Phpfox::getUserParam('user.total_times_can_change_own_full_name'),
                        'total_full_name_change' => $aUser['total_full_name_change'],
                        'current_full_name' => $aUser['full_name']
                    ), true, true
                    )
                    )
                ) {
                    if ($bChangedEmail || $bRemoveEmail) {
                        //Get new user info
                        $aUser = Phpfox::getService('user')->get(Phpfox::getUserId());
                        $bAllowed = Phpfox::getService('user.verify.process')->changeEmail($aUser, $aVals['email'], true, $bRemoveEmail);
                        if (is_string($bAllowed)) {
                            Phpfox_Error::set($bAllowed);
                        }
                        if (Phpfox::getParam('user.verify_email_at_signup') && !$bRemoveEmail) {
                            $sMessage = _p('account_settings_updated_your_new_mail_address_requires_verification_and_an_email_has_been_sent_until_then_your_email_remains_the_same');
                            if (Phpfox::getParam('user.logout_after_change_email_if_verify')) {
                                $this->url()->send('user.verify', null, _p('email_updated_you_need_to_verify_your_new_email_address_before_logging_in'));
                            }
                        }
                    }
                    if ($bChangedPhone || $bRemovePhone) {
                        //Get new user info
                        $aUser = Phpfox::getService('user')->get(Phpfox::getUserId());
                        $bAllowed = Phpfox::getService('user.verify.process')->changePhone($aUser, $aVals['phone_number'], true, $bRemovePhone);
                        if ($bAllowed === true && !$bRemovePhone) {
                            //Changed phone, redirect to verify
                            $sMessage = _p('account_settings_updated_your_new_phone_number_requires_verification_and_an_sms_has_been_sent_until_then_your_phone_remains_the_same');
                            $this->url()->send('user.sms.send', ['sent' => 1, 'force' => 1]);
                        }
                    }
                    $this->url()->send('user.setting', null, $sMessage);
                }
            }
        }

        if (!empty($aUser['birthday'])) {
            $aUser = array_merge($aUser, Phpfox::getService('user')->getAgeArray($aUser['birthday']));
        }

        $aGateways = Phpfox::getService('api.gateway')->getActive();
        $aUnsetFields = [
            'paypal' => ['client_id', 'client_secret'],
        ];

        if (!empty($aGateways)) {
            $aGatewayValues = Phpfox::getService('api.gateway')->getUserGateways($aUser['user_id']);
            foreach ($aGateways as $iKey => $aGateway) {
                foreach ($aGateway['custom'] as $iCustomKey => $aCustom) {
                    if (isset($aUnsetFields[$aGateway['gateway_id']]) && in_array($iCustomKey, $aUnsetFields[$aGateway['gateway_id']])) {
                        unset($aGateways[$iKey]['custom'][$iCustomKey]);
                        continue;
                    }
                    if (isset($aGatewayValues[$aGateway['gateway_id']]['gateway'][$iCustomKey])) {
                        $aGateways[$iKey]['custom'][$iCustomKey]['user_value'] = $aGatewayValues[$aGateway['gateway_id']]['gateway'][$iCustomKey];
                    }
                }
            }
        }

        $aTimeZones = Phpfox::getService('core')->getTimeZones();
        if (count($aTimeZones) > 100) // we are using the php 5.3 way
        {
            $this->template()->setHeader('cache', array('setting.js' => 'module_user'));
        }
        $sFullNamePhrase = Phpfox::getUserParam('user.custom_name_field');
        if (Core\Lib::phrase()->isPhrase($sFullNamePhrase)) {
            $sFullNamePhrase = _p($sFullNamePhrase);
        } else {
            $sFullNamePhrase = _p('full_name');
        }
        (($sPlugin = Phpfox_Plugin::get('user.component_controller_setting_settitle')) ? eval($sPlugin) : false);

        if (Phpfox::getParam('user.split_full_name') && empty($aUser['first_name']) && empty($aUser['last_name'])) {
            preg_match('/(.*) (.*)/', $aUser['full_name'], $aNameMatches);
            if (isset($aNameMatches[1]) && isset($aNameMatches[2])) {
                $aUser['first_name'] = $aNameMatches[1];
                $aUser['last_name'] = $aNameMatches[2];
            } else {
                $aUser['first_name'] = $aUser['full_name'];
            }
        }
        if ($aUser['status_id'] == 1 && Phpfox::getUserParam('user.can_change_phone') && Phpfox::getParam('core.enable_register_with_phone_number')) {
            list($iVerifyType, $sVerifyBy) = Phpfox::getService('user.verify')->getVerificationByUser($aUser['user_id'], true);
            if ($iVerifyType === 2) {
                Phpfox::getLib('session')->set('sms_verify_phone', $sVerifyBy);
            }
            $this->template()->assign([
                'bIsWaitingVerifyPhone' => $iVerifyType === 2,
                'sWaitingVerifyPhone' => $sVerifyBy
            ]);
        }
        if (!empty($aUser['full_phone_number'])) {
            $oPhoneLib = Phpfox::getLib('phone');
            $oPhoneLib->setRawPhone($aUser['full_phone_number']);
            $aUser['full_phone_number'] = $oPhoneLib->getPhoneInternational();
        }
        $this->template()->setTitle(_p('account_settings'))
            ->setBreadCrumb(_p('account_settings'))
            ->setHeader('cache', array(
                    'country.js' => 'module_core',
                    'clipboard.min.js' => 'static_script',
                    '<script type="text/javascript">sSetTimeZone = "' . Phpfox::getUserBy('time_zone') . '";</script>',
                    'jquery/plugin/intlTelInput.js' => 'static_script',
                )
            )->setPhrase([
                'two_step_verification'
            ])
            ->assign(array(
                    'sCreateJs' => $oValid->createJS(),
                    'sGetJsForm' => $oValid->getJsForm(),
                    'aForms' => $aUser,
                    'bEnable2StepVerification' => !empty($aUser['two_step_verification']) && Phpfox::getUserParam('user.can_use_2step_verification'),
                    'aTimeZones' => $aTimeZones,
                    'sFullNamePhrase' => $sFullNamePhrase,
                    'iTotalChangesAllowed' => Phpfox::getUserParam('user.total_times_can_change_user_name'),
                    'iTotalFullNameChangesAllowed' => Phpfox::getUserParam('user.total_times_can_change_own_full_name'),
                    'aLanguages' => Phpfox::getService('language')->get(array('l.user_select = 1')),
                    'sDobStart' => Phpfox::getParam('user.date_of_birth_start'),
                    'sDobEnd' => Phpfox::getParam('user.date_of_birth_end'),
                    'aCurrencies' => Phpfox::getService('core.currency')->get(),
                    'aGateways' => $aGateways,
                    'bRequiredChangePassword' => !empty(storage()->get('fb_new_users_' . $aUser['user_id']))
                )
            )->buildSectionMenu('user', [
                _p('account_settings') => 'user.setting',
                _p('privacy_settings') => 'user.privacy',
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
        (($sPlugin = Phpfox_Plugin::get('user.component_controller_setting_clean')) ? eval($sPlugin) : false);
    }
}
