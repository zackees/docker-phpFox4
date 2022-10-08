<?php

defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Service_Verify_Process
 *
 * To create a mass reminder to verify their email address should be just a matter of getting all the hashes and looping
 * through $this->verify(sHash)
 */
class User_Service_Verify_Process extends Phpfox_Service
{
    /**
     * @var string
     */
    protected $_sTable = '';

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('user_verify');
    }

    /**
     * Delete pending verifications users after days
     */
    public function deletePendingVerifications()
    {
        $days = (int)Phpfox::getParam('user.days_for_delete_pending_user_verification');

        if ($days <= 0)
            return false;

        $pendingVerifications = db()->select('uv.user_id, u.status_id')
            ->from($this->_sTable, 'uv')
            ->leftJoin(':user', 'u', 'u.user_id = uv.user_id')
            ->where('uv.time_stamp <= ' . (PHPFOX_TIME - $days * 86400))
            ->executeRows(false);

        if (empty($pendingVerifications))
            return false;

        foreach ($pendingVerifications as $pendingVerification) {
            if (!isset($pendingVerification['status_id']) || (int)$pendingVerification['status_id'] == 0) {
                db()->delete($this->_sTable, ['user_id' => $pendingVerification['user_id']]);
            } else {
                defined('PHPFOX_CANCEL_ACCOUNT') || define('PHPFOX_CANCEL_ACCOUNT', true);
                Phpfox::getService('user.auth')->setUserId($pendingVerification['user_id']);
                Phpfox::massCallback('onDeleteUser', $pendingVerification['user_id']);
                Phpfox::getService('user.auth')->setUserId(null);
            }
        }
    }

    /**
     * Direct verify of a user, only admins should be allowed to trigger this function.
     * @param Int $iUser
     * @return bool
     */
    public function adminVerify($iUser)
    {
        // Is this user allowed to verify others?
        if (!Phpfox::getUserParam('user.can_verify_others_emails')) return false;
        $sNewEmail = $this->database()->select('email')->from($this->_sTable)->where('user_id = ' . (int)$iUser)->execute('getSlaveField');
        $aUpdate = ['status_id' => 0];
        $oPhone = Phpfox::getLib('phone');
        if (isset($sNewEmail) && $sNewEmail != '') {
            if (filter_var($sNewEmail, FILTER_VALIDATE_EMAIL)) {
                $aUpdate['email'] = $sNewEmail;
            } elseif ($oPhone->setRawPhone($sNewEmail) && $oPhone->isValidPhone()) {
                $aUpdate['phone_number'] = $oPhone->getPhoneNational();
                $aUpdate['full_phone_number'] = $oPhone->getPhoneE164();
            }
        }

        $this->database()->update(Phpfox::getT('user'), $aUpdate, 'user_id = ' . (int)$iUser);
        $this->database()->delete($this->_sTable, 'user_id = ' . (int)$iUser);
        $this->database()->update(Phpfox::getT('photo'), ['view_id' => '0'], 'view_id = 3 AND user_id = ' . (int)$iUser);

        // update the friends count when "on signup new friend is enabled
        if (Phpfox::getParam('user.on_signup_new_friend') && Phpfox::isModule('friend')) {
            Phpfox::getService('friend.process')->updateFriendCount($iUser, Phpfox::getParam('user.on_signup_new_friend'));
        }

        // check invitation with user email/phone
        (Phpfox::isModule('invite') ? Phpfox::getService('invite.process')->registerByEmail($iUser, true) : null);

        return true;
    }

    /**
     * Changes a user's email address, checks if user is allowed and if he should be made verify their email address
     * afterwards and if it should be logged out immediately after changing it.
     * @param array $aUser
     * @param string $sMail
     * @param bool $bSkipValidate
     * @param bool $bRemoveEmail
     * @return bool
     */
    public function changeEmail($aUser, $sMail, $bSkipValidate = false, $bRemoveEmail = false)
    {
        // check if user has enough permissions and the mails don't match if they have to verify the new email upon sign up it
        $sMail = strtolower($sMail);
        if (Phpfox::getUserGroupParam($aUser['user_group_id'], 'user.can_change_email')) {
            if (!$bSkipValidate) {
                Phpfox::getService('user.validate')->email($sMail);
                if (!Phpfox_Error::isPassed()) {
                    return false;
                }
            }
            $sMail = Phpfox::getLib('parse.input')->prepare($sMail);
            // set the status to need to be verified only if they are required at signup
            if (Phpfox::getParam('user.verify_email_at_signup') && !$bRemoveEmail) {
                $mUser = [
                    'user_id' => $aUser['user_id'],
                    'email' => $sMail,
                    'password' => $aUser['password'],
                    'language_id' => $aUser['language_id'],
                    'full_name' => $aUser['full_name']
                ];
                $this->database()->update(Phpfox::getT('user'), ['status_id' => 1], 'user_id = ' . (int)$aUser['user_id']);
                $this->sendMail($mUser);
            } else {
                // just change the email
                $this->database()->update(Phpfox::getT('user'), ['email' => $sMail], 'user_id = ' . (int)$aUser['user_id']);
                $aUser['email'] = $sMail;
                // check invitation with new email
                (Phpfox::isModule('invite') ? Phpfox::getService('invite.process')->registerByEmail($aUser, true) : null);
            }

            (($sPlugin = Phpfox_Plugin::get('user.component_service_verify_process_change_email_end')) ? eval($sPlugin) : false);

            // check if they should be logged out immediately after changing it. Only then should their status_id be changed
            if (!$bRemoveEmail && Phpfox::getParam('user.verify_email_at_signup') && Phpfox::getParam('user.logout_after_change_email_if_verify') == true) {
                Phpfox::getService('user.auth')->logout();
            }
            return true;
        }

        return false;
    }

    /**
     * @param $aUser
     * @param $sPhone
     * @param bool $bSkipValidate
     * @param bool $bRemovePhone
     * @return bool|int
     * @throws Exception
     */
    public function changePhone($aUser, $sPhone, $bSkipValidate = false, $bRemovePhone = false)
    {
        $oPhone = Phpfox::getLib('phone');
        if ($bRemovePhone) {
            // just update the phone
            $this->database()->update(Phpfox::getT('user'),
                ['phone_number' => '', 'full_phone_number' => ''], 'user_id = ' . (int)$aUser['user_id']
            );
            return true;
        }
        if ($oPhone->setRawPhone($sPhone) && $oPhone->isValidPhone()) {
            $sDisplayPhone = $oPhone->getPhoneNational();
            $sPhone = $oPhone->getPhoneE164();
            if ($sPhone == $aUser['full_phone_number']) {
                //No change phone
                return 1;
            } else {
                if (!$bSkipValidate) {
                    Phpfox::getService('user.validate')->phone($sPhone, true);
                    if (!Phpfox_Error::isPassed()) {
                        return false;
                    }
                }
                $oService = Phpfox::getLib('phpfox.verify');
                $sSendToken = Phpfox::getLib('phpfox.verify')->generateOneTimeTokenToSMS();
                $sSentToken = substr($sSendToken, 0, 3) . ' ' . substr($sSendToken, 3);

                $sMsg = _p('sms_registration_verification_message', ['token' => $sSentToken]);

                if (!$oService->sendSMS($sPhone, $sMsg)) {
                    return Phpfox_Error::set(_p('cannot_send_sms_contact_admin'));
                }
                //Must verify phone again
                $this->database()->update(Phpfox::getT('user'), ['status_id' => 1], 'user_id = ' . (int)$aUser['user_id']);
                //Delete old verify code
                $this->database()->delete($this->_sTable, 'user_id = ' . (int)$aUser['user_id']);
                $this->database()->insert($this->_sTable, [
                    'user_id' => $aUser['user_id'],
                    'hash_code' => $sSendToken,
                    'time_stamp' => PHPFOX_TIME,
                    'email' => $sPhone
                ]);
                defined('PHPFOX_FORCE_VERIFY_PHONE_NUMBER') or define('PHPFOX_FORCE_VERIFY_PHONE_NUMBER', true);
                Phpfox::getLib('session')->set('sms_verify_phone', $sPhone);

                (($sPlugin = Phpfox_Plugin::get('user.component_service_verify_process_change_phone_end')) ? eval($sPlugin) : false);

                if (Phpfox::getParam('user.logout_after_change_phone_number')) {
                    Phpfox::getService('user.auth')->logout();
                }
                return true;
            }
        } else {
            return Phpfox_Error::set(_p('phone_number_is_invalid'));
        }
    }

    /**
     * This function checks if the hash submitted is valid.
     * In every case it deletes the hash from the database, if the hash expired it creates a new one and sends an email to the user.
     * @param String $sHash
     * @param Boolean $bStrict tells if we should check if the password has expired, added to complement the adminVerify
     * @param bool $bVerifyOnly
     * @param null $sEmail
     * @param string $sType
     * @return boolean false if the hash is not found on the db or if it has expired | true if the hash matches
     * @throws Exception
     */
    public function verify($sHash, $bStrict = true, $bVerifyOnly = false, $sEmail = null, $sType = 'verify_account')
    {
        $sHash = preg_replace("#\s+#", '', $sHash);
        $aVerify = $this->database()
            ->select('uv.verify_id, uv.user_id, uv.email as newMail, u.password, uv.time_stamp, u.email, u.full_phone_number, uv.hash_code')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = uv.user_id')
            ->from($this->_sTable, 'uv')
            ->where('uv.hash_code = \'' . Phpfox::getLib('parse.input')->clean($sHash) . '\'' .
                ' AND uv.type_id = \'' . $sType . '\'' .
                (!empty($sEmail) ? (' AND uv.email = \'' . Phpfox::getLib('parse.input')->clean($sEmail) . '\'') : ''))
            ->execute('getSlaveRow');

        if (empty($aVerify)) {
            return false;
        }

        // Delete the entry from the user_verify table
        $this->database()->delete($this->_sTable, 'verify_id = ' . $aVerify['verify_id']);

        if ((Phpfox::getParam('user.verify_email_timeout') == 0 ||
            ($aVerify['time_stamp'] + (Phpfox::getParam('user.verify_email_timeout') * 60)) >= PHPFOX_TIME)) {
            // Update the user table where user_id = aVerify[user_id]

            (($sPlugin = Phpfox_Plugin::get('user.service_verify_process_verify_pass')) ? eval($sPlugin) : false);

            if ($bVerifyOnly) {
                return true;
            }

            $view_id = 0;
            if (Phpfox::getParam('user.approve_users')) {
                $view_id = 1;// 1 = need to approve the user
            }
            $aUpdate = [
                'status_id' => 0,
                'view_id' => $view_id,
            ];
            $bIsUpdateAccount = false;
            $oPhone = Phpfox::getLib('phone');
            if (filter_var($aVerify['newMail'], FILTER_VALIDATE_EMAIL)) {
                $bIsUpdateAccount = !$aVerify['email'] || $aVerify['newMail'] != $aVerify['email'];
                $aUpdate['email'] = $aVerify['newMail'];
            } elseif ($oPhone->setRawPhone($aVerify['newMail']) && $oPhone->isValidPhone()) {
                $bIsUpdateAccount = !$aVerify['full_phone_number'] || $oPhone->getPhoneE164() != $aVerify['full_phone_number'];
                $aUpdate['phone_number'] = $oPhone->getPhoneNational();
                $aUpdate['full_phone_number'] = $oPhone->getPhoneE164();
            }
            $this->database()->update(Phpfox::getT('user'), $aUpdate, 'user_id = ' . $aVerify['user_id']);

            // check invitation with new email/phone
            (Phpfox::isModule('invite') ? Phpfox::getService('invite.process')->registerByEmail($aVerify['user_id'], true) : null);

            Phpfox::getLib('session')->set('verified_account_changed', $bIsUpdateAccount);
            if ($view_id) {
                // Send the pending approval email
                Phpfox::getLib('mail')
                    ->to($aVerify['user_id'])
                    ->subject('pending_approval')
                    ->message(['we_are_reviewing_your_account_and_pending_approval', [
                                'site_title' => Phpfox::getParam('core.site_title'),
                                'link' => Phpfox_Url::instance()->makeUrl('')
                            ]
                        ]
                    )
                    ->send();
            } else {
                $this->database()->update(Phpfox::getT('photo'), ['view_id' => '0'], 'view_id = 3 AND user_id = ' . $aVerify['user_id']);
                // update the friends count when "on signup new friend is enabled
                if (Phpfox::getParam('user.on_signup_new_friend') && Phpfox::isModule('friend')) {
                    Phpfox::getService('friend.process')->updateFriendCount($aVerify['user_id'], Phpfox::getParam('user.on_signup_new_friend'));
                }
                // Send the welcome email
                Phpfox::getLib('mail')
                    ->to($aVerify['user_id'])
                    ->subject(['core.welcome_email_subject', ['site' => Phpfox::getParam('core.site_title')]])
                    ->message('core.welcome_email_content')
                    ->send();
            }
            return (int)$aVerify['user_id'];
        } else {
            //Create new hash code if the old is expired to prevent missing verification email
            if (!is_numeric($aVerify['hash_code'])) {
                //Old code is not a number -> get new hash string
                $sHash = Phpfox::getService('user.verify')->getVerifyHash($aVerify);
            } else {
                //Old code is number -> get new one
                $sHash = Phpfox::getLib('phpfox.verify')->generateOneTimeTokenToSMS();
            }
            if ($sHash) {
                $this->database()->insert($this->_sTable, [
                    'user_id' => $aVerify['user_id'],
                    'hash_code' => $sHash,
                    'time_stamp' => PHPFOX_TIME,
                    'email' => $aVerify['newMail'],
                    'type_id' => $sType
                ]);
            }
        }

        if ($bStrict === false) return true;
        // Its invalid (timeout) so add the entry to the error log table
        $aError = [
            'ip_address' => Phpfox::getIp(),
            'hash_code' => Phpfox::getLib('parse.input')->prepare($sHash),
            'email' => $aVerify['newMail'], // should we add also the email address here ?
            'time_stamp' => PHPFOX_TIME
        ];
        $this->database()->insert(Phpfox::getT('user_verify_error'), $aError);

        return false;
    }

    /**
     * Sends an email with the verification link. Accepts an integer, an array() or an array(array())
     * @param int|array[]|array[][] $mUser If int its taken as user_id, if array we save a query, if array[][] we mass mail their verification links
     * @param  bool $bResend
     * @return boolean
     */
    public function sendMail($mUser, $bResend = false)
    {
        // this function to be flexible, allows receiving an integer or an array, if its an array then we
        // don't query the database looking for the info needed.
        // Info needed: email in the user_verify table if exists.
        // when we add the new email to the user_verify table then when they log in using their old email,
        // until they verify the new one

        if (is_numeric($mUser) || (!isset($mUser['email']) && !isset($mUser[0]['email']))) {
            // check if the user exists:
            $aUser = $this->database()
                ->select('user_id, full_name, email, password, language_id, status_id')
                ->from(Phpfox::getT('user'))
                ->where('user_id = ' . (int)$mUser)
                ->execute('getSlaveRow');
            if (!$aUser || !$aUser['status_id']) {
                return false;
            }

            $aUserV = $this->database()
                ->select('uv.email')// select the new email
                ->from($this->_sTable, 'uv')
                ->where('uv.user_id = ' . (int)$mUser)
                ->limit(1)
                ->execute('getSlaveRow');

            if (!$aUserV) {
                // we know the user exists, so we generate a new hash and send that instead
                $this->database()->insert($this->_sTable, [
                    'user_id' => $aUser['user_id'],
                    'hash_code' => Phpfox::getService('user.verify')->getVerifyHash($aUser),
                    'time_stamp' => PHPFOX_TIME,
                    'email' => $aUser['email']]);
                $aUserV = ['email' => $aUser['email']];
            }
            $mUser = ['user_id' => $mUser, 'email' => $aUserV['email'], 'password' => $aUser['password'], 'full_name' => $aUser['full_name'], 'language_id' => $aUser['language_id']];
        } else {
            $aUser = $mUser;
        }

        // Check if we're mass mailing
        if (isset($mUser[0]['email'])) {
            // its an array of users
            foreach ($mUser as $aUser) {
                $this->sendMail($aUser);
            }
            return true;
        }

        // Set the hash code
        if (!isset($sHash)) {
            $sHash = Phpfox::getService('user.verify')->getVerifyHash($aUser);
        }
        // There may already be an entry so to avoid duplicates and not risk an update on a missing entry we delete:
        $this->database()->delete($this->_sTable, 'user_id = ' . (int)$mUser['user_id']);

        $this->database()->insert($this->_sTable, [
            'user_id' => $mUser['user_id'],
            'hash_code' => $sHash,
            'time_stamp' => PHPFOX_TIME,
            'email' => $mUser['email']
        ]);

        // send email
        $sLink = Phpfox_Url::instance()->makeUrl('user.verify', ['link' => $sHash]);
        if ($bResend) {
            $body = [
                'resend_email_on_site_title_before',
                [
                    'site_title' => Phpfox::getParam('core.site_title'),
                    'link' => $sLink
                ]
            ];

        } else {
            $body = [
                'you_changed_email_on_site_title_before',
                [
                    'site_title' => Phpfox::getParam('core.site_title'),
                    'link' => $sLink
                ]
            ];
        }
        Phpfox::getLib('mail')
            ->to($mUser['email'])
            ->aUser($mUser)
            ->subject(['email_verification_on_site_title', ['site_title' => Phpfox::getParam('core.site_title')]])
            ->message($body)
            ->sendToSelf(true)
            ->send(false, true);

        return true;
    }

    public function resendSMS($iUserId)
    {
        $sVerifyEmail = $this->database()
            ->select('email')
            ->from(Phpfox::getT('user_verify'))
            ->where(['user_id' => (int)$iUserId])
            ->execute('getSlaveField');
        if (empty($sVerifyEmail)) {
            return false;
        }
        $sSendToken = Phpfox::getService('user.verify')->getVerifyHashByEmail($sVerifyEmail, !filter_var($sVerifyEmail, FILTER_VALIDATE_EMAIL), false, true);
        $sSendToken = substr($sSendToken, 0, 3) . ' ' . substr($sSendToken, 3);
        $sMsg = _p('sms_registration_verification_message', ['token' => $sSendToken]);
        $oService = Phpfox::getLib('phpfox.verify');
        if ($oService->sendSMS($sVerifyEmail, $sMsg)) {
            return true;
        }
        return false;
    }
}
