<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Service_Password
 */
class User_Service_Password extends Phpfox_Service
{
    const TYPE_EMAIL = 'email';
    const TYPE_PHONE = 'phone';
    const VERIFY_TYPE_ID = 'request_password';

    public function __construct()
    {
        $this->_sTable = Phpfox::getT('user');
    }

    public function requestPassword($aVals)
    {
        $bUsePhone = false;
        $sPhone = '';
        $this->database()->select('user_id, profile_page_id, email, full_name, user_name, phone_number, full_phone_number')
            ->from($this->_sTable);
        $bEnablePhone = Phpfox::getParam('core.enable_register_with_phone_number');
        if ($bEnablePhone && !filter_var($aVals['email'], FILTER_VALIDATE_EMAIL)) {
            //Request via phone
            $oPhone = Phpfox::getLib('phone');
            if ($oPhone->setRawPhone($aVals['email']) && $oPhone->isValidPhone()) {
                $sPhone = $oPhone->getPhoneE164();
                $this->database()->where('full_phone_number = \'' . $sPhone . '\'');
                $bUsePhone = true;
            } else {
                return Phpfox_Error::set(_p('phone_number_is_invalid'));
            }
        } else {
            $this->database()->where('email = \'' . $this->database()->escape($aVals['email']) . '\'');
        }

        $aUser = $this->database()->executeRow();

        if (!isset($aUser['user_id'])) {
            return Phpfox_Error::set(_p($bEnablePhone ? 'provide_a_valid_email_address_or_phone_number' : 'provide_a_valid_email_address'));
        }

        if ((empty($aUser['email']) && empty($aUser['full_phone_number'])) || $aUser['profile_page_id'] > 0) {
            return Phpfox_Error::set(_p('unable_to_attain_a_password_for_this_account_dot'));
        }

        $oBan = Phpfox::getService('ban');
        if (!Phpfox::getService('user')->isAdminUser($aUser['user_id'])
            && (!$oBan->check('email', $aUser['email'])
                || !$oBan->check('email', $aUser['full_phone_number'], false, 'phone_number')
                || !$oBan->check('email', $aUser['phone_number'], false, 'phone_number')
                || !$oBan->check('username', $aUser['user_name']) || !$oBan->check('display_name', $aUser['full_name']))) {
            return Phpfox_Error::set(_p('global_ban_message'));
        }
        $aBanned = Phpfox::getService('ban')->isUserBanned($aUser);
        if ($aBanned['is_banned']) {
            if (isset($aBanned['reason']) && !empty($aBanned['reason'])) {
                $aBanned['reason'] = str_replace('&#039;', "'", $aBanned['reason']);
                $sReason = Phpfox::getLib('parse.output')->cleanPhrases($aBanned['reason']);
                $banMessage = _p('you_have_been_banned_for_the_following_reason', ['reason' => $sReason]) . '.';

            } else {
                $banMessage = _p('global_ban_message');
            }
            if (!empty($aBanned['end_time_stamp'])) {
                $banMessage .= ' ' . _p('the_ban_will_be_expired_on_datetime', ['datetime' => Phpfox::getTime(Phpfox::getParam('core.global_update_time'), $aBanned['end_time_stamp'])]);
            }
            return Phpfox_Error::set($banMessage);
        }

        if ($bUsePhone && $sPhone) {
            //Send verify code
            $oService = Phpfox::getLib('phpfox.verify');
            $sSendToken = Phpfox::getService('user.verify')->getVerifyHashByEmail($sPhone, true, false, true, self::VERIFY_TYPE_ID);
            $sSendToken = substr($sSendToken, 0, 3) . ' ' . substr($sSendToken, 3);

            $sMsg = _p('sms_registration_verification_message', ['token' => $sSendToken]);

            if (!$oService->sendSMS($sPhone, $sMsg)) {
                return Phpfox_Error::set(_p('cannot_send_sms_contact_admin'));
            }
            $sHash = md5($aUser['user_id'] . $aUser['full_phone_number'] . Phpfox::getParam('core.salt'));
            $this->database()->delete(Phpfox::getT('password_request'), 'user_id = ' . $aUser['user_id']);
            $this->database()->insert(Phpfox::getT('password_request'), [
                    'user_id' => $aUser['user_id'],
                    'request_id' => $sHash,
                    'request_type' => self::TYPE_PHONE,
                    'time_stamp' => PHPFOX_TIME
                ]
            );
            return $sHash;
        }
        // Send the user an email
        $sHash = md5($aUser['user_id'] . $aUser['email'] . Phpfox::getParam('core.salt'));
        $sLink = Phpfox_Url::instance()->makeUrl('user.password.verify', ['id' => $sHash]);
        Phpfox::getLib('mail')->to($aUser['user_id'])
            ->subject(['password_request_for_site_title', ['site_title' => Phpfox::getParam('core.site_title')]])
            ->message(['you_have_requested_for_us_to_send_you_a_new_password_for_site_title', [
                    'site_title' => Phpfox::getParam('core.site_title'),
                    'link' => $sLink
                ]
                ]
            )
            ->skipSms(true)
            ->send(false, true);

        $this->database()->delete(Phpfox::getT('password_request'), 'user_id = ' . $aUser['user_id']);
        $this->database()->insert(Phpfox::getT('password_request'), [
                'user_id' => $aUser['user_id'],
                'request_id' => $sHash,
                'request_type' => self::TYPE_EMAIL,
                'time_stamp' => PHPFOX_TIME
            ]
        );

        return true;
    }

    public function isValidRequest($sId)
    {
        $aRequest = $this->database()->select('r.*, u.email, u.full_name, u.full_phone_number')
            ->from(Phpfox::getT('password_request'), 'r')
            ->join($this->_sTable, 'u', 'u.user_id = r.user_id')
            ->where('r.request_id = \'' . $this->database()->escape($sId) . '\'')
            ->execute('getSlaveRow');

        if (!isset($aRequest['user_id']) || !in_array($aRequest['request_type'], [self::TYPE_EMAIL, self::TYPE_PHONE])) {
            return Phpfox_Error::set(_p('not_a_valid_password_request'));
        }
        if (
            ($aRequest['request_type'] == self::TYPE_EMAIL && md5($aRequest['user_id'] . $aRequest['email'] . Phpfox::getParam('core.salt')) != $sId) &&
            ($aRequest['request_type'] == self::TYPE_PHONE && md5($aRequest['user_id'] . $aRequest['full_phone_number'] . Phpfox::getParam('core.salt')) != $sId)
        ) {
            return Phpfox_Error::set(_p('password_request_id_does_not_match'));
        }
        if (Phpfox::getParam('user.verify_email_timeout') > 0 && ($aRequest['time_stamp'] < (PHPFOX_TIME - (Phpfox::getParam('user.verify_email_timeout') * 60)))) {
            $this->database()->delete(Phpfox::getT('password_request'), 'request_id = "' . $this->database()->escape($sId) . '"');
            return Phpfox_Error::set(_p('request_expired_please_try_again'));
        }
        return true;
    }

    public function verifyRequest($sId)
    {
        $sSelect = 'r.*, u.email, u.full_name, u.full_phone_number';
        $sWhere = 'r.request_id = \'' . $this->database()->escape($sId) . '\'';
        $sJoin = 'u.user_id = r.user_id';

        if ($sPlugin = Phpfox_Plugin::get('user.service_password_verifyrequest_start')) {
            eval($sPlugin);
        }
        $aRequest = $this->database()->select($sSelect)
            ->from(Phpfox::getT('password_request'), 'r')
            ->join($this->_sTable, 'u', $sJoin)
            ->where($sWhere)
            ->execute('getSlaveRow');

        (($sPlugin = Phpfox_Plugin::get('user.service_password_verifyrequest_2')) ? eval($sPlugin) : false);

        if (!isset($aRequest['user_id']) || !in_array($aRequest['request_type'], [self::TYPE_EMAIL, self::TYPE_PHONE])) {
            return Phpfox_Error::set(_p('not_a_valid_password_request'));
        }

        if ($sPlugin = Phpfox_Plugin::get('user.service_password_verifyrequest_check_1')) {
            eval($sPlugin);
        }

        if (
            ($aRequest['request_type'] == self::TYPE_EMAIL && md5($aRequest['user_id'] . $aRequest['email'] . Phpfox::getParam('core.salt')) != $sId) &&
            ($aRequest['request_type'] == self::TYPE_PHONE && md5($aRequest['user_id'] . $aRequest['full_phone_number'] . Phpfox::getParam('core.salt')) != $sId)
        ) {
            return Phpfox_Error::set(_p('password_request_id_does_not_match'));
        }

        $sNewPassword = $this->generatePassword(15);
        $sSalt = $this->_getSalt();
        $aUpdate = [];
        $aUpdate['password'] = Phpfox::getLib('hash')->setHash($sNewPassword, $sSalt);
        $aUpdate['password_salt'] = $sSalt;

        (($sPlugin = Phpfox_Plugin::get('user.service_password_verifyrequest_3')) ? eval($sPlugin) : false);
        $this->database()->update($this->_sTable, $aUpdate, 'user_id = ' . $aRequest['user_id']);
        $this->database()->delete(Phpfox::getT('password_request'), 'user_id = ' . $aRequest['user_id']);

        // Delete skip required old password cache
        storage()->del('fb_new_users_' . $aRequest['user_id']);

        // Send the user an email
        $sLink = Phpfox_Url::instance()->makeUrl('user.login');

        (($sPlugin = Phpfox_Plugin::get('user.service_password_verifyrequest_4')) ? eval($sPlugin) : false);

        if ($aRequest['request_type'] == self::TYPE_PHONE) {
            //Send password to phone
            $sMsg = _p('you_have_requested_for_us_to_send_you_a_new_password_for_site_title_with_password_via_phone', [
                'site_title' => Phpfox::getParam('core.site_title'),
                'password' => $sNewPassword,
            ]);

            if (!Phpfox::getLib('phpfox.verify')->sendSMS($aRequest['full_phone_number'], $sMsg)) {
                return Phpfox_Error::set(_p('phone_number_is_invalid'));
            }
        } else {
            Phpfox::getLib('mail')->to($aRequest['user_id'])
                ->subject(['new_password_for_site_title', ['site_title' => Phpfox::getParam('core.site_title')]])
                ->message(['you_have_requested_for_us_to_send_you_a_new_password_for_site_title_with_password', [
                        'site_title' => Phpfox::getParam('core.site_title'),
                        'password' => $sNewPassword,
                        'link' => $sLink
                    ]]
                )
                ->skipSms(true)
                ->send(false, true);
        }

        if ($sPlugin = Phpfox_Plugin::get('user.service_password_verifyrequest_end')) {
            eval($sPlugin);
        }
        return true;
    }

    public function updatePassword($sRequest, $aVals)
    {
        if (!isset($aVals['newpassword']) || !isset($aVals['newpassword2']) || $aVals['newpassword'] != $aVals['newpassword2']) {
            return Phpfox_Error::set(_p('passwords_do_not_match'));
        }
        $aRequest = $this->database()->select('r.*, u.email, u.full_name')
            ->from(Phpfox::getT('password_request'), 'r')
            ->join($this->_sTable, 'u', 'u.user_id = r.user_id')
            ->where('r.request_id = \'' . $this->database()->escape($sRequest) . '\'')
            ->execute('getSlaveRow');

        $sSalt = $this->_getSalt();
        $aUpdate = [];
        $aUpdate['password'] = Phpfox::getLib('hash')->setHash($aVals['newpassword'], $sSalt);
        $aUpdate['password_salt'] = $sSalt;

        $this->database()->update($this->_sTable, $aUpdate, 'user_id = ' . $aRequest['user_id']);
        $this->database()->delete(Phpfox::getT('password_request'), 'user_id = ' . $aRequest['user_id']);

        // Delete skip required old password cache
        storage()->del('fb_new_users_' . $aRequest['user_id']);
        return true;
    }

    /**
     * If a call is made to an unknown method attempt to connect
     * it to a specific plug-in with the same name thus allowing
     * plug-in developers the ability to extend classes.
     *
     * @param string $sMethod is the name of the method
     * @param array $aArguments is the array of arguments of being passed
     *
     * @return null
     */
    public function __call($sMethod, $aArguments)
    {
        /**
         * Check if such a plug-in exists and if it does call it.
         */
        if ($sPlugin = Phpfox_Plugin::get('user.service_password__call')) {
            return eval($sPlugin);
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }

    public function generatePassword($iLength = 9, $iStrength = 10)
    {
        $sVowels = 'aeuy';
        $sConsonants = 'bdghjmnpqrstvz';

        if ($iStrength > 1) {
            $sConsonants .= 'BDGHJLMNPQRSTVWXZ';
        }

        if ($iStrength > 2) {
            $sVowels .= "AEUY";
        }

        if ($iStrength > 4) {
            $sConsonants .= '23456789';
        }

        if ($iStrength > 8) {
            $sConsonants .= '@#$%{}[]!?*;:';
        }

        $sPassword = '';
        $sAlt = time() % 2;
        for ($i = 0; $i < $iLength; $i++) {
            if ($sAlt == 1) {
                $sPassword .= $sConsonants[(rand() % strlen($sConsonants))];
                $sAlt = 0;
            } else {
                $sPassword .= $sVowels[(rand() % strlen($sVowels))];
                $sAlt = 1;
            }
        }
        return $sPassword;
    }

    private function _getSalt($iTotal = 3)
    {
        $sSalt = '';
        for ($i = 0; $i < $iTotal; $i++) {
            $sSalt .= chr(rand(33, 126));
        }

        return $sSalt;
    }
}
