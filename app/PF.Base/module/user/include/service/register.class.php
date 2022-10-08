<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Service_Register
 */
class User_Service_Register extends Phpfox_Service
{
    public function getValidation($sStep = null, $bIsApi = false, $iUserGroupId = null, $bCustomField = false)
    {
        $aValidation = array();
        if ($sStep == 1 || $sStep === null) {
            if (Phpfox::getParam('user.split_full_name') && Phpfox::getParam('user.disable_username_on_sign_up') != 'username') {
                $aValidation['first_name'] = _p('provide_your_first_name');
                $aValidation['last_name'] = _p('provide_your_last_name');
            }
            if (Phpfox::getParam('user.disable_username_on_sign_up') != 'username' && Phpfox::getParam('user.validate_full_name')) {
                $aChange = ['max' => Phpfox::getParam('user.maximum_length_for_full_name')];
                $sTitle = Phpfox::getParam('user.display_or_full_name') == 'full_name' ?
                    _p('provide_a_valid_full_name', $aChange) : _p('provide_a_valid_display_name', $aChange);
                $aValidation['full_name'] = array(
                    'def' => 'full_name',
                    'title' => $sTitle
                );
            }
            if (!Phpfox::getParam('user.profile_use_id') && (Phpfox::getParam('user.disable_username_on_sign_up') != 'full_name')) {
                $aValidation['user_name'] = array(
                    'def' => 'username',
                    'subdef' => 'no_duplicate',
                    'title' => _p('provide_a_valid_user_name', array(
                        'min' => Phpfox::getParam('user.min_length_for_username'),
                        'max' => Phpfox::getParam('user.max_length_for_username')
                    ))
                );
            }
            $bEnablePhone = Phpfox::getParam('core.enable_register_with_phone_number');
            $aValidation['email'] = array(
                'def'   => 'email',
                'subdef' => $bEnablePhone ? 'phone:required' : '',
                'title' => _p($bEnablePhone ? 'provide_a_valid_email_address_or_phone_number' : 'provide_a_valid_email_address')
            );
            if (Phpfox::getParam('user.reenter_email_on_signup') && !$bIsApi) {
                $aValidation['confirm_email'] = array(
                    'def'   => 'reenter',
                    'compare_with' => 'email',
                    'subdef' => $bEnablePhone ? 'phone' : '',
                    'subtitle' => _p($bEnablePhone ? 'confirm_your_email_s_or_phone' : 'confirm_your_email'),
                    'title' => _p($bEnablePhone ? 'email_s_or_phone_do_not_match' : 'email_s_do_not_match')
                );
            }
            $aValidation['password'] = array(
                'def' => 'password',
                'title' => _p('provide_a_valid_password')
            );

            if (Phpfox::getParam('user.signup_repeat_password') && !$bIsApi) {
                $aValidation['repassword'] = array(
                    'def' => 'reenter',
                    'compare_with' => 'password',
                    'subtitle' => _p('confirm_your_password'),
                    'title' => _p('passwords_do_not_match')
                );
            }
            if ($sStep == 1) {
                if (Phpfox::getParam('user.new_user_terms_confirmation') && !$bIsApi) {
                    $aValidation['agree'] = array(
                        'def'   => 'checkbox',
                        'title' => _p('check_our_agreement_in_order_to_join_our_site')
                    );
                }
            }
        }

        if ($sStep == 2 || $sStep === null) {
            if (Phpfox::getParam('core.registration_enable_dob')) {
                if ($bIsApi) {
                    $aValidation['month'] = _p('Provide month of birth.');
                    $aValidation['day'] = _p('Provide day of birth.');
                    $aValidation['year'] = _p('Provide year of birth.');
                } else {
                    $aValidation['month'] = _p('select_month_of_birth');
                    $aValidation['day'] = _p('select_day_of_birth');
                    $aValidation['year'] = _p('select_year_of_birth');
                }
            }
            if (Phpfox::getParam('core.registration_enable_gender')) {
                if ($bIsApi) {
                    $aValidation['gender'] = array(
                        'def' => 'gender:required',
                        'title' => _p('Provide your gender.'),
                        'subtitle' => _p('please_type_at_least_one_custom_gender')
                    );
                } else {
                    $aValidation['gender'] = array(
                        'def' => 'gender:required',
                        'title' => _p('select_your_gender'),
                        'subtitle' => _p('please_type_at_least_one_custom_gender')
                    );
                }
            }
            if (Phpfox::getParam('core.registration_enable_location')) {
                if ($bIsApi) {
                    $aValidation['country_iso'] = _p('Provide current location.');
                } else {
                    $aValidation['country_iso'] = _p('select_current_location');
                }
            }
            //Add validation for custom field
            if ($bCustomField) {
                $aFields = Phpfox::getService('custom')->getForEdit(array('user_main', 'user_panel', 'profile_panel'),
                    null,
                    $iUserGroupId, true);
                foreach ($aFields as $iKey => $aField) {
                    if (!$aField['is_required']) {
                        continue;
                    }
                    $aValidation['custom[' . $aField['field_id'] . ']'] = _p('Please provide ') . Phpfox::getLib('parse.output')->clean(_p($aField['phrase_var_name']));
                }
            }

            if (Phpfox::isAppActive('Core_Subscriptions')
                && Phpfox::getParam('subscribe.enable_subscription_packages')
                && Phpfox::getParam('subscribe.subscribe_is_required_on_sign_up')) {
                $aValidation['package_id'] = _p('select_a_membership_package');
            }

            if (Phpfox::getParam('user.force_user_to_upload_on_sign_up')) {
                $aValidation['temp_file'] = array(
                    'def'   => 'int:required',
                    'title' => _p('please_upload_an_image_for_your_profile')
                );
            }

            if (Phpfox::isAppActive('Core_Captcha')
                && Phpfox::getParam('user.captcha_on_signup')
                && !$bIsApi) {
                $aValidation['image_verification'] = [
                    'def' => 'required',
                    'title' => _p('complete_captcha_challenge'),
                    'subdef' => $sStep == 2 ? 'no_submit' : ''
                ];
            }

            if ($sStep == null) {
                if (Phpfox::getParam('user.new_user_terms_confirmation') && !$bIsApi) {
                    $aValidation['agree'] = array(
                        'def'   => 'checkbox',
                        'title' => _p('check_our_agreement_in_order_to_join_our_site')
                    );
                }
            }
        }

        return $aValidation;
    }

    /**
     * If a call is made to an unknown method attempt to connect
     * it to a specific plug-in with the same name thus allowing
     * plug-in developers the ability to extend classes.
     *
     * @param string $sMethod is the name of the method
     * @param array $aArguments is the array of arguments of being passed
     * @return null
     */
    public function __call($sMethod, $aArguments)
    {
        /**
         * Check if such a plug-in exists and if it does call it.
         */
        if ($sPlugin = Phpfox_Plugin::get('user.service_register__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}
