<?php

defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Service_Auth
 */
class User_Service_Auth extends Phpfox_Service
{
    private $_aUser = [];

    private $_iOverrideUserId = null;

    private $_sNameCookieUserId = 'user_id';
    private $_sNameCookieHash = 'user_hash';
    private $_sNameCookieLoginType = 'user_login_type';

    private $_stayLogin;

    /**
     * @param $iUserId
     *
     * @return mixed
     */
    public function getStayLogin($iUserId)
    {
        if (!isset($this->_stayLogin[$iUserId])) {
            $this->_stayLogin[$iUserId] = Phpfox::getUserParam('user.can_stay_logged_in');
        }
        return $this->_stayLogin[$iUserId];
    }

    /**
     * Class constructor
     */
    public function __construct()
    {
        if (Phpfox::getParam('core.use_custom_cookie_names')) {
            $this->_sNameCookieUserId = md5(Phpfox::getParam('core.custom_cookie_names_hash') . $this->_sNameCookieUserId);
            $this->_sNameCookieHash = md5(Phpfox::getParam('core.custom_cookie_names_hash') . $this->_sNameCookieHash);
            $this->_sNameCookieLoginType = md5(Phpfox::getParam('core.custom_cookie_names_hash') . $this->_sNameCookieLoginType);
        }

        $this->_sTable = Phpfox::getT('user');
        $iUserId = (int)Phpfox::getCookie($this->_sNameCookieUserId);
        $sPasswordHash = Phpfox::getCookie($this->_sNameCookieHash);

        if (defined('PHPFOX_INSTALLER')) {
            $this->_setDefault();
        } else {
            if ($iUserId > 0) {
                $sSelect = '';
                (($sPlugin = Phpfox_Plugin::get('user.service_auth___construct_start')) ? eval($sPlugin) : false);

                $oSession = Phpfox::getLib('session');
                $oRequest = Phpfox_Request::instance();
                $bLoadUserField = false;
                $bIsBetterAds = Phpfox::isAppActive('Core_BetterAds');
                $sUserFieldSelect = '';

                (($sPlugin = Phpfox_Plugin::get('user.service_auth___construct_query')) ? eval($sPlugin) : false);

                if ($oSession->get('session')) {
                    $this->database()
                        ->select('ls.session_hash, ls.id_hash, ls.captcha_hash, ls.user_id as ls_user_id, ls.im_status, ls.last_activity as ls_last_activity, ')
                        ->leftJoin(Phpfox::getT('log_session'), 'ls', "ls.session_hash = '" . $this->database()->escape($oSession->get('session')) . "' AND ls.id_hash = '" . $this->database()->escape($oRequest->getIdHash()) . "'");
                }

                if ((Phpfox_Request::instance()->get('req1') == ''
                    || Phpfox_Request::instance()->get('req1') == 'request'
                    || (Phpfox_Request::instance()->get('req1') == 'theme' && Phpfox_Request::instance()->get('req2') == 'select'))
                ) {
                    $this->database()->select('uc.*, ')->join(Phpfox::getT('user_count'), 'uc', 'uc.user_id = u.user_id');
                }

                if ((Phpfox_Request::instance()->get('req1') == '') || (Phpfox_Request::instance()->get('req1') == 'core')) {
                    $bLoadUserField = true;
                    $sUserFieldSelect .= 'uf.total_view, u.last_login, uf.location_latlng, ';
                }

                if (strtolower(Phpfox_Request::instance()->get('req1')) == 'admincp') {
                    $bLoadUserField = true;
                    $sUserFieldSelect .= 'uf.in_admincp, ';
                }

                if ($bIsBetterAds && Phpfox::getParam('better_ads_advanced_ad_filters')) {
                    $bLoadUserField = true;
                    $sUserFieldSelect .= 'uf.postal_code, uf.city_location, uf.country_child_id, ';
                }

                if ($bLoadUserField === true) {
                    $this->database()->select($sUserFieldSelect)->join(Phpfox::getT('user_field'), 'uf', 'uf.user_id = u.user_id');
                }

                $this->database()->select('uactivity.activity_points, uactivity.user_id AS activity_user_id, ')->leftJoin(Phpfox::getT('user_activity'), 'uactivity', 'uactivity.user_id = u.user_id');

                $this->_aUser = $this->database()->select('u.profile_page_id, u.status_id, u.view_id, u.user_id, u.server_id, u.user_group_id, u.user_name, u.email, u.full_phone_number, u.gender, u.style_id, u.language_id, u.birthday, u.full_name, u.user_image, u.password, u.password_salt, u.joined, u.hide_tip, u.status, u.footer_bar, u.country_iso, u.time_zone, u.dst_check, u.last_activity, u.im_beep, u.im_hide, u.is_invisible, u.total_spam, u.feed_sort ' . $sSelect)
                    ->from($this->_sTable, 'u')
                    ->where("u.user_id = '" . $this->database()->escape($iUserId) . "'")
                    ->execute('getSlaveRow');

                if (!isset($this->_aUser['user_id'])) {
                    $this->_setDefault();
                    $this->logout();
                }

                if (empty($this->_aUser['activity_user_id']) && (Phpfox::getParam('user.check_promotion_system') || $bLoadUserField === true)) {
                    $this->database()->delete(Phpfox::getT('user_activity'), 'user_id = ' . $this->_aUser['user_id']);
                    $this->database()->insert(Phpfox::getT('user_activity'), ['user_id' => $this->_aUser['user_id']]);
                }

                if (isset($this->_aUser['password'])
                    && isset($this->_aUser['password_salt'])
                    && !Phpfox::getLib('hash')->getRandomHash(Phpfox::getLib('hash')->setHash($this->_aUser['password'], $this->_aUser['password_salt']), $sPasswordHash)) {
                    $this->_setDefault();
                    $this->logout();
                }

                if (isset($this->_aUser['user_id'])) {
                    $this->_aUser['age'] = Phpfox::getService('user')->age(isset($this->_aUser['birthday']) ? $this->_aUser['birthday'] : '');
                    $this->_aUser['im_hide'] = ((isset($this->_aUser['is_invisible']) && $this->_aUser['is_invisible']) ? 1 : (isset($this->_aUser['im_hide']) ? $this->_aUser['im_hide'] : 1));
                }

                (($sPlugin = Phpfox_Plugin::get('user.service_auth___construct_end')) ? eval($sPlugin) : false);

                unset($this->_aUser['password'], $this->_aUser['password_salt']);

                if (isset($this->_aUser['fb_user_id']) && $this->_aUser['fb_user_id'] > 0 && $this->_aUser['fb_is_unlinked']) {
                    $this->_aUser['fb_user_id'] = 0;
                }
            } else {
                $this->_setDefault();
            }
        }
    }

    public function getCookieNames()
    {
        return [$this->_sNameCookieUserId, $this->_sNameCookieHash];
    }

    public function getUserSession()
    {
        return $this->_aUser;
    }

    public function getUserBy($sVar = null)
    {
        if ($sVar === null && isset($this->_aUser['user_id']) && $this->_aUser['user_id'] > 0) {
            return $this->_aUser;
        }

        if (isset($this->_aUser[$sVar])) {
            return $this->_aUser[$sVar];
        }
        return false;
    }

    public function setUserId($iUserId, $aUser = null)
    {
        $this->_iOverrideUserId = $iUserId;
        if (!empty($aUser)) {
            $this->_aUser = $aUser;
        }
    }

    public function getUserId()
    {
        if ($this->_iOverrideUserId !== null) {
            return $this->_iOverrideUserId;
        }

        return (int)$this->_aUser['user_id'];
    }

    public function isUser()
    {
        if ($this->_iOverrideUserId !== null) {
            return true;
        }

        return $this->_aUser['user_id'] && $this->getStayLogin($this->_aUser['user_id']);
    }

    public function isActiveAdminSession()
    {
        if (!Phpfox::getParam('core.admincp_do_timeout')) {
            if (Phpfox::isAdminPanel()) {
                $this->database()->update(Phpfox::getT('user_field'), ['in_admincp' => PHPFOX_TIME], 'user_id = ' . Phpfox::getUserId());
            }

            return true;
        }

        if (Phpfox::getUserBy('fb_user_id') > 0) {
            return true;
        }

        $iLastLoggedIn = (int)Phpfox::getUserBy('in_admincp');
        if ($iLastLoggedIn < (PHPFOX_TIME - (Phpfox::getParam('core.admincp_timeout') * 60))) {
            return false;
        }

        $this->database()->update(Phpfox::getT('user_field'), ['in_admincp' => PHPFOX_TIME], 'user_id = ' . Phpfox::getUserId());

        return true;
    }

    public function setUser($aUser)
    {
        $this->_aUser = $aUser;
    }

    public function loginAdmin($sEmail, $sPassword)
    {
        $bLoginAsEmail = preg_match('/[-0-9a-zA-Z.+_]+@[-0-9a-zA-Z.+_]+/', $sEmail) || filter_var($sEmail, FILTER_VALIDATE_EMAIL);
        $bLoginAsPhone = false;
        $sPhone = '';
        if (Phpfox::getParam('core.enable_register_with_phone_number') && !$bLoginAsEmail) {
            $oPhone = Phpfox::getLib('phone');
            if ($oPhone->setRawPhone($sEmail) && $oPhone->isValidPhone()) {
                $bLoginAsPhone = true;
                $sPhone = $oPhone->getPhoneE164();
            }
        }
        $aRow = $this->database()->select('user_id, user_name, password, password_salt, status_id')
            ->from($this->_sTable)
            ->where("email = '" . $this->database()->escape($sEmail) . "'" . (!empty($sPhone) ? " OR full_phone_number = '" . $sPhone . "'" : ""))
            ->execute('getSlaveRow');

        if (!isset($aRow['user_name'])) {
            $this->_logAdmin(1);

            return Phpfox_Error::set(_p('not_a_valid_account'));
        }

        if ($bLoginAsEmail && strtolower($sEmail) != strtolower(Phpfox::getUserBy('email'))) {
            $this->_logAdmin(2);

            return Phpfox_Error::set(_p('email_does_not_match_the_one_that_is_currently_in_use'));
        }

        if ($bLoginAsPhone && strtolower($sPhone) != strtolower(Phpfox::getUserBy('full_phone_number'))) {
            $this->_logAdmin(4);

            return Phpfox_Error::set(_p('phone_number_does_not_match_the_one_that_is_currently_in_use'));
        }

        if (strlen($aRow['password']) > 32) {
            $Hash = new Core\Hash();
            if (!$Hash->check($sPassword, $aRow['password'])) {
                $this->_logAdmin(3);

                return Phpfox_Error::set(_p('invalid_password'));
            }
        } else {
            if (Phpfox::getLib('hash')->setHash($sPassword, $aRow['password_salt']) != $aRow['password']) {
                $this->_logAdmin(3);

                return Phpfox_Error::set(_p('invalid_password'));
            }
        }

        $this->database()->update(Phpfox::getT('user_field'), ['in_admincp' => PHPFOX_TIME], 'user_id = ' . $aRow['user_id']);

        $this->_logAdmin();

        return true;
    }

    public function logoutAdmin()
    {
        $this->database()->update(Phpfox::getT('user_field'), ['in_admincp' => 0], 'user_id = ' . Phpfox::getUserId());
    }

    public function verify($userId = null, $loginType = null, $redirect = false)
    {
        empty($userId) && $userId = Phpfox::getUserId();
        if (empty($loginType) && empty($loginType = Phpfox::getCookie($this->_sNameCookieLoginType))) {
            $loginType = 'email';
        }

        $isLoginAsEmail = $loginType == 'email';
        $user = Phpfox::getService('user')->get($userId);
        $canLogin = true;

        list($verifyType, $verifyBy, $isSmsCode) = Phpfox::getService('user.verify')->getVerificationByUser($user['user_id'], true, true);
        if (Phpfox::getParam('core.enable_register_with_phone_number') && $verifyType === 2 && !$isLoginAsEmail) {
            if ($redirect) {
                Phpfox::getLib('session')->set('sms_verify_phone', $verifyBy);
                Phpfox_Url::instance()->send('user.sms.send', ['resend' => 1], _p('your_account_has_not_been_verified_we_sent_a_verification_code_to_your_phone_number'));
            } else {
                $canLogin = false;
            }
        } elseif (Phpfox::getParam('core.registration_sms_enable') && $isSmsCode && $verifyType === 1) {
            if ($redirect) {
                Phpfox::getLib('session')->remove('sms_verify_phone');
                Phpfox::getLib('session')->set('sms_verify_email', $user['email']);
                Phpfox_Url::instance()->send('user.sms.send', null, _p('you_still_need_to_verify_your_account'));
            } else {
                $canLogin = false;
            }
        } elseif ($verifyType === 1 && $isLoginAsEmail) {
            if ($redirect) {
                Phpfox_Url::instance()->send('user.verify', null, _p('you_need_to_verify_your_email_address_before_logging_in', ['email' => !empty($verifyBy) ? $verifyBy : $user['email']]));
            } else {
                $canLogin = false;
            }
        }

        if (!$redirect) {
            return $canLogin;
        }
    }

    public function login($sLogin, $sPassword, $bRemember = false, $sType = 'email', $bNoPasswordCheck = false, $sVerifyPassCode = false)
    {
        $sSelect = 'user_id, email, user_name, full_name, user_group_id, password, password_salt, status_id, phone_number, full_phone_number, two_step_verification';
        /* Used to control the return in case we detect a brute force attack */
        $bReturn = false;

        $sLogin = $this->database()->escape($sLogin);
        $bLoginAsEmail = preg_match('/[-0-9a-zA-Z.+_]+@[-0-9a-zA-Z.+_]+/', $sLogin) || filter_var($sLogin, FILTER_VALIDATE_EMAIL);
        $bLoginAsPhone = false;

        if ($sPlugin = Phpfox_Plugin::get('user.service_auth_login__start')) {
            eval($sPlugin);
            if (isset($mReturn)) return $mReturn;
        }
        if (!Phpfox::getParam('core.enable_register_with_phone_number')) {
            $sCondition = $sType == 'both' ? "email = '" . $sLogin . "' OR user_name = '" . $sLogin . "'" : ($sType == 'email' ? "email" : "user_name") . " = '" . $sLogin . "'";
        } else {
            if (!$bLoginAsEmail) {
                $oPhone = Phpfox::getLib('phone');
                if ($oPhone->setRawPhone($sLogin) && $oPhone->isValidPhone()) {
                    $bLoginAsPhone = true;
                    $sPhone = $oPhone->getPhoneE164();
                    $sCondition = $sType == 'both' ? "email = '" . $sLogin . "' OR user_name = '" . $sLogin . "' OR full_phone_number = '" . $sPhone . "'" : ($sType == 'email' ? "email = '" . $sLogin . "' OR full_phone_number = '" . $sPhone . "'" : "user_name = '" . $sLogin . "'");
                } else {
                    $sCondition = $sType == 'both' ? "email = '" . $sLogin . "' OR user_name = '" . $sLogin . "'" : ($sType == 'email' ? "email = '" . $sLogin . "'" : "user_name = '" . $sLogin . "'");
                }
            } else {
                $sCondition = $sType == 'both' ? "email = '" . $sLogin . "' OR user_name = '" . $sLogin . "' OR phone_number = '" . $sLogin . "'" : ($sType == 'email' ? "email = '" . $sLogin . "' OR phone_number = '" . $sLogin . "'" : "user_name = '" . $sLogin . "'");
            }
        }

        $aRow = $this->database()->select($sSelect)
            ->from($this->_sTable)
            ->where($sCondition)
            ->execute('getSlaveRow');

        if ($sPlugin = Phpfox_Plugin::get('user.service_auth_login_skip_email_verification')) {
            eval($sPlugin);
        }

        if (!isset($aRow['user_name'])) {
            switch (Phpfox::getParam('user.login_type')) {
                case 'user_name':
                    $sMessage = _p('invalid_user_name');
                    break;
                case 'email':
                    if (!Phpfox::getParam('core.enable_register_with_phone_number')) {
                        $sMessage = _p('invalid_email');
                    } else {
                        $sMessage = _p('invalid_email_phone_number');
                    }
                    break;
                default:
                    if (!Phpfox::getParam('core.enable_register_with_phone_number')) {
                        $sMessage = _p('invalid_email_user_name');
                    } else {
                        $sMessage = _p('invalid_email_username_phone_number');
                    }
            }

            Phpfox_Error::set($sMessage);
            if ($sPlugin = Phpfox_Plugin::get('user.service_auth_login__no_user_name')) {
                eval($sPlugin);
            }
            $bReturn = true;
        } else {
            $bDoPhpfoxLoginCheck = true;
            if ($sPlugin = Phpfox_Plugin::get('user.service_auth_login__password')) {
                eval($sPlugin);
            }

            if (strlen($aRow['password']) > 32) {
                $Hash = new Core\Hash();
                if (!$bNoPasswordCheck && !$Hash->check($sPassword, $aRow['password'])) {
                    Phpfox_Error::set(_p('invalid_password'));
                    $bReturn = true;
                }
            } else {
                if (!$bNoPasswordCheck && $bDoPhpfoxLoginCheck && (Phpfox::getLib('hash')->setHash($sPassword, $aRow['password_salt']) != $aRow['password'])) {
                    Phpfox_Error::set(_p('invalid_password'));
                    $bReturn = true;
                }
            }
        }

        /* Add the check for the brute force here */
        if (!empty($aRow) && !defined('PHPFOX_INSTALLER') && Phpfox::getParam('user.brute_force_time_check') > 0) {
            /* Check if the account is already locked */
            $iLocked = $this->database()->select('brute_force_locked_at')
                ->from(Phpfox::getT('user_field'))
                ->where('user_id = ' . $aRow['user_id'])
                ->execute('getSlaveField');

            $iUnlockTimeOut = $iLocked + (Phpfox::getParam('user.brute_force_cool_down') * 60);
            $iRemaining = $iUnlockTimeOut - PHPFOX_TIME;
            $iTimeFrom = PHPFOX_TIME - (60 * Phpfox::getParam('user.brute_force_time_check'));
            $iAttempts = $this->database()->select('COUNT(*)')
                ->from(Phpfox::getT('user_ip'))
                ->where('user_id = ' . $aRow['user_id'] . ' AND type_id = "login_failed" AND time_stamp > ' . $iTimeFrom)
                ->execute('getSlaveField');

            $aReplace = [
                'iCoolDown'      => Phpfox::getParam('user.brute_force_cool_down'),
                'sForgotLink'    => Phpfox_Url::instance()->makeUrl('user.password.request'),
                'iUnlockTimeOut' => ceil($iRemaining / 60)
            ];

            if ($iRemaining > 0) {
                Phpfox_Error::reset();
                Phpfox_Error::set(_p('brute_force_account_locked', $aReplace));
                return [false, $aRow];
            }

            if ($iAttempts >= Phpfox::getParam('user.brute_force_attempts_count')) {
                $this->database()->update(Phpfox::getT('user_field'), ['brute_force_locked_at' => PHPFOX_TIME], 'user_id = ' . $aRow['user_id']);

                Phpfox_Error::reset();
                /* adjust new remaining time*/
                $aReplace['iUnlockTimeOut'] = Phpfox::getParam('user.brute_force_cool_down');
                Phpfox_Error::set(_p('brute_force_account_locked', $aReplace));
                $bReturn = true;
            }
        }

        if ($bReturn == true) {
            /* Log the attempt */
            $this->database()->insert(Phpfox::getT('user_ip'), [
                    'user_id'    => isset($aRow['user_id']) ? $aRow['user_id'] : '0',
                    'type_id'    => 'login_failed',
                    'ip_address' => Phpfox::getIp(),
                    'time_stamp' => PHPFOX_TIME
                ]
            );
            return [false, $aRow];
        }

        // verification check
        if (!defined('PHPFOX_INSTALLER')
            && isset($aRow['status_id'])
            && $aRow['status_id'] == 1 && !isset($bEmailVerification)) // 0 good status; 1 => need to verify
        {
            Phpfox::getLib('session')->set('cache_user_id', $aRow['user_id']);

            if (defined('PHPFOX_MUST_PAY_FIRST')) {
                Phpfox_Url::instance()->send('subscribe.register', ['id' => PHPFOX_MUST_PAY_FIRST, 'login' => '1']);
            }

            $this->verify($aRow['user_id'], $bLoginAsEmail ? 'email' : 'phone', true);
        }

        // ban check
        $oBan = Phpfox::getService('ban');
        if (!Phpfox::getService('user')->isAdminUser($aRow['user_id'])
            && (!$oBan->check('email', $aRow['email'])
                || !$oBan->check('email', $aRow['full_phone_number'], false, 'phone_number')
                || !$oBan->check('email', $aRow['phone_number'], false, 'phone_number')
                || !$oBan->check('username', $aRow['user_name']) || !$oBan->check('display_name', $aRow['full_name']))) {
            Phpfox_Error::set(_p('global_ban_message'));
        }

        if (!$oBan->check('ip', Phpfox_Request::instance()->getIp())) {
            Phpfox_Error::set(_p('not_allowed_ip_address'));
        }

        $aBanned = Phpfox::getService('ban')->isUserBanned($aRow);

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
            Phpfox_Error::set($banMessage);
        }

        if (Phpfox_Error::isPassed()) {
            if ($sPlugin = Phpfox_Plugin::get('user.service_auth_login__cookie_start')) {
                eval($sPlugin);
            }

            $bSecureHttps = Phpfox::getParam('core.force_https_secure_pages') ? true : false;
            if (!empty($aRow['two_step_verification']) && Phpfox::getUserGroupParam($aRow['user_group_id'], 'user.can_use_2step_verification') && $sVerifyPassCode) {
                $sExpireLogin = PHPFOX_TIME + 1800;
                $sVerifyToken = md5($sLogin . $sPassword . PHPFOX_TIME);
                if ($bLoginAsEmail) {
                    $sSecureLogin = Phpfox::secureText($sLogin, 'email');
                } elseif ($bLoginAsPhone) {
                    $sSecureLogin = Phpfox::secureText(!empty($sPhone) ? $sPhone : $sLogin, 'phone');
                } else {
                    if (!empty($aRow['full_phone_number'])) {
                        $sSecureLogin = Phpfox::secureText($aRow['full_phone_number'], 'phone');
                    } else {
                        $sSecureLogin = Phpfox::secureText($aRow['email'], 'email');
                    }
                }
                $aParams = [
                    'login' => $sLogin,
                    'secure_login' => $sSecureLogin,
                    'password' => $sPassword,
                    'user_id' => $aRow['user_id'],
                    'remember_me' => $bRemember
                ];
                Phpfox::setCookie('login_two_step_' . $sVerifyToken, base64_encode(json_encode($aParams)), $sExpireLogin, $bSecureHttps);
                $aRow['token'] = $sVerifyToken;
                return [-1, $aRow];
            }

            $sPasswordHash = Phpfox::getLib('hash')->setRandomHash(Phpfox::getLib('hash')->setHash($aRow['password'], $aRow['password_salt']));

            // Set cookie (yummy)
            $iTime = ($bRemember ? (PHPFOX_TIME + 3600 * 24 * 365) : 0);
            Phpfox::setCookie($this->_sNameCookieUserId, $aRow['user_id'], $iTime, $bSecureHttps);
            Phpfox::setCookie($this->_sNameCookieHash, $sPasswordHash, $iTime, $bSecureHttps);
            Phpfox::setCookie($this->_sNameCookieLoginType, $bLoginAsEmail ? 'email' : 'phone', $iTime, $bSecureHttps);
            if (!defined('PHPFOX_INSTALLER')) {
                Phpfox::getLib('session')->remove('theme');
            }

            $this->database()->update($this->_sTable, ['last_login' => PHPFOX_TIME], 'user_id = ' . $aRow['user_id']);
            $this->database()->insert(Phpfox::getT('user_ip'), [
                    'user_id'    => $aRow['user_id'],
                    'type_id'    => 'login',
                    'ip_address' => Phpfox::getIp(),
                    'time_stamp' => PHPFOX_TIME
                ]
            );

            if (Phpfox::isAppActive('Core_Activity_Points')) {
                Phpfox::getService('activitypoint.process')->updatePoints($aRow['user_id'], 'user_accesssite');
            }

            if ($sPlugin = Phpfox_Plugin::get('user.service_auth_login__cookie_end')) {
                eval($sPlugin);
            }
            return [true, $aRow];
        }
        if ($sPlugin = Phpfox_Plugin::get('user.service_auth_login__end')) {
            eval($sPlugin);
        }
        return [false, $aRow];
    }

    public function logout()
    {
        if ($sPlugin = Phpfox_Plugin::get('user.service_auth_logout__start')) {
            eval($sPlugin);
        }
        if (!empty($this->_aUser['user_id'])) {
            $this->database()->insert(Phpfox::getT('user_ip'), [
                    'user_id'    => $this->_aUser['user_id'],
                    'type_id'    => 'logout',
                    'ip_address' => Phpfox::getIp(),
                    'time_stamp' => PHPFOX_TIME
                ]
            );
            $this->database()->delete(Phpfox::getT('log_session'), ['user_id' => $this->_aUser['user_id']]);
        }

        Phpfox::setCookie($this->_sNameCookieUserId, '', -1);
        Phpfox::setCookie($this->_sNameCookieHash, '', -1);
        Phpfox::setCookie($this->_sNameCookieLoginType, '', -1);
        Phpfox::getLib('session')->remove('theme');
        Phpfox::getLib('session')->remove('language_id');


        if ($sPlugin = Phpfox_Plugin::get('user.service_auth_logout__end')) {
            eval($sPlugin);
        }
    }

    public function hasAccess($sTable, $sField, $iId, $sUserPerm, $sGlobalPerm, $iUserId = null, $bAlert = true)
    {
        $bAccess = false;

        if (Phpfox::isUser()) {
            if ($iUserId === null) {
                $iUserId = $this->database()->select('u.user_id')
                    ->from(Phpfox::getT($sTable), 'a')
                    ->join(Phpfox::getT('user'), 'u', 'u.user_id = a.user_id')
                    ->where('a.' . $sField . ' = ' . (int)$iId)
                    ->execute('getSlaveField');

                if (!$iUserId) {
                    $bAccess = false;
                }
            }

            if ($iUserId && Phpfox::getUserId() == $iUserId && Phpfox::getUserParam($sUserPerm)) {
                $bAccess = $iUserId;
            }

            if ($iUserId && Phpfox::getUserParam($sGlobalPerm)) {
                $bAccess = $iUserId;
            }

            if ($iUserId && Phpfox::getUserId() != $iUserId && Phpfox::getService('user.block')->isBlocked(null, $iUserId)) {
                $bAccess = false;
            }
        }

        if ($bAccess === false && PHPFOX_IS_AJAX) {
            if ($bAlert) {
                echo 'alert(\'' . _p('you_do_not_have_permission_to_modify_this_item') . '\');';
            }

            return false;
        }

        return $bAccess;
    }

    /**
     * Handles actions depending on the current status_id
     *
     * @param int    $iExpectedValue The expected value to match `user`.`status_id`
     * @param string $sAction        What to do if `status_id` is anything else
     *
     * @example _handleStatus(0,'deny') will return false if status_id == 0, and case to deny if !=
     * @return false|null if they match, | true if sAction was triggered
     *
     */
    public function handleStatus()
    {
        if (defined('PHPFOX_INSTALLER')) {
            return null;
        }

        if (Phpfox::getParam('core.site_is_offline') && !Phpfox::getUserParam('core.can_view_site_offline')) {
            $this->_setDefault();
            $this->logout();
        }

        if (!Phpfox::getUserParam('core.is_spam_free')
            && Phpfox::getParam('core.enable_spam_check')
            && Phpfox::getParam('core.auto_ban_spammer') > 0
            && Phpfox::getUserBy('total_spam') > Phpfox::getParam('core.auto_ban_spammer')
        ) {
            $this->_setDefault();
            $this->logout();

            Phpfox_Url::instance()->send('ban.spam');
        }

        // ban check
        $oBan = Phpfox::getService('ban');
        if (!Phpfox::getService('user')->isAdminUser(Phpfox::getUserId(), true)
            && (!$oBan->check('email', Phpfox::getUserBy('email'))
                || !$oBan->check('username', Phpfox::getUserBy('user_name'))
                || !$oBan->check('display_name', Phpfox::getUserBy('full_name'))
                || !$oBan->check('ip', Phpfox_Request::instance()->getIp()))
        ) {
            $this->_setDefault();
            $this->logout();
            Phpfox_Url::instance()->send('ban.message');
        }

        if (Phpfox::getUserParam('core.user_is_banned')) {
            $aBanned = Phpfox::getService('ban')->isUserBanned();

            if (isset($aBanned['ban_data_id'])) {
                if (isset($aBanned['is_expired']) && $aBanned['is_expired'] == 0
                    && isset($aBanned['end_time_stamp']) && ($aBanned['end_time_stamp'] == 0 || $aBanned['end_time_stamp'] >= PHPFOX_TIME)) {
                    $this->_setDefault();
                    $this->logout();
                    if (isset($aBanned['reason']) && !empty($aBanned['reason'])) {
                        $aBanned['reason'] = str_replace('&#039;', "'", Phpfox::getLib('parse.output')->parse($aBanned['reason']));
                        $sReason = Phpfox::getLib('parse.output')->cleanPhrases($aBanned['reason']);
                        $banMessage = _p('you_have_been_banned_for_the_following_reason', ['reason' => $sReason]) . '.';
                        if ($aBanned['end_time_stamp']) {
                            $banMessage .= ' ' . _p('the_ban_will_be_expired_on_datetime', ['datetime' => Phpfox::getTime(Phpfox::getParam('core.global_update_time'), $aBanned['end_time_stamp'])]);
                        }
                        Phpfox_Url::instance()->send('', null, $banMessage, null, 'danger', false);
                    }
                    Phpfox_Url::instance()->send('ban.message');
                } else {
                    // update user group here
                    if (isset($aBanned['return_user_group']) && !empty($aBanned['returned_user_group'])) {
                        $this->database()->update(Phpfox::getT('user'), ['user_group_id' => $aBanned['return_user_group']], 'user_id = ' . Phpfox::getUserId());
                    } else {
                        $this->database()->update(Phpfox::getT('user'), ['user_group_id' => Phpfox::getParam('user.on_register_user_group')], 'user_id = ' . Phpfox::getUserId());
                    }
                    $this->database()->update(Phpfox::getT('ban_data'), ['is_expired' => '1'], 'user_id = ' . Phpfox::getUserId());
                }
            } else {
                $this->_setDefault();
                $this->logout();
            }
        }

        // user is in good status
        if (Phpfox::isUser() && Phpfox::getUserBy('status_id') === 0) {
            return null;
        }

        if ($sPlugin = Phpfox_Plugin::get('user.service_auth_handlestatus')) {
            eval($sPlugin);
        }

        //user needs to verify their phone
        list($iVerifyType, $sVerifyBy) = Phpfox::getService('user.verify')->getVerificationByUser(Phpfox::getUserId(), true, true);
        if (Phpfox::isUser() && empty($sLoginType = Phpfox::getCookie($this->_sNameCookieLoginType))) {
            $this->_setDefault();
            $this->logout();
        }

        if (Phpfox::isUser()
            && Phpfox::getParam('core.enable_register_with_phone_number')
            && Phpfox::getParam('user.logout_after_change_phone_number')
            && !isset($bPhoneVerification)
            && $iVerifyType === 2
            && $sLoginType == 'phone') {
            Phpfox::getLib('session')->set('sms_verify_phone', $sVerifyBy);
            $this->_setDefault();
            $this->logout();
            //Don't need to check email
            $bEmailVerification = true;
            if (Phpfox_Request::instance()->get('req1') != 'user' && Phpfox_Request::instance()->get('req2') != 'sms' && Phpfox_Request::instance()->get('req3') != 'send') {
                Phpfox_Url::instance()->send('user.sms.send', ['resend' => 1], _p('your_account_has_not_been_verified_we_sent_a_verification_code_to_your_phone_number'));
            }
        }
        // user needs to verify their email address
        if (Phpfox::isUser() && Phpfox::getUserBy('status_id') == 1
            && Phpfox::getParam('user.logout_after_change_email_if_verify')
            && !isset($bEmailVerification)
            && $iVerifyType === 1
            && $sLoginType == 'email') {
            $this->_setDefault();
            $this->logout();
            if (Phpfox_Request::instance()->get('req1') != 'user' && Phpfox_Request::instance()->get('req2') != 'verify') {
                Phpfox_Url::instance()->send('user.verify');
            }
        }

        // user needs to be approved first
        if (Phpfox::isUser() && in_array(Phpfox::getUserBy('view_id'), [2, 1])) {
            $iStatusId = Phpfox::getUserBy('status_id');
            $iViewId = Phpfox::getUserBy('view_id');
            $this->_setDefault();
            $this->logout();

            if ((Phpfox_Request::instance()->get('req1').Phpfox_Request::instance()->get('req2')) != 'user.pending') {
                Phpfox_Url::instance()->send('user.pending', [
                    's' => $iStatusId,
                    'v' => $iViewId,
                ]);
            }
        }

        if (Phpfox::isUser() && Phpfox::getParam('user.check_promotion_system')) {
            Phpfox::getService('user.promotion')->check();
        }

        return null;
    }

    /**
     * If a call is made to an unknown method attempt to connect
     * it to a specific plug-in with the same name thus allowing
     * plug-in developers the ability to extend classes.
     *
     * @param string $sMethod    is the name of the method
     * @param array  $aArguments is the array of arguments of being passed
     *
     * @return null
     */
    public function __call($sMethod, $aArguments)
    {
        /**
         * Check if such a plug-in exists and if it does call it.
         */
        if ($sPlugin = Phpfox_Plugin::get('user.service_auth__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }

    public function reset()
    {
        $this->_setDefault();
    }

    private function _setDefault()
    {
        $this->_aUser = [
            'user_id'       => 0,
            'user_group_id' => GUEST_USER_ID,
            'language_id'   => Phpfox::getParam('core.default_lang_id'),
            'style_folder'  => 'default',
            'theme_folder'  => 'default'
        ];
    }

    private function _logAdmin($iStatus = 0)
    {
        if (isset($_REQUEST['val']['password'])) {
            unset($_REQUEST['val']['password']);
        }
        $this->database()->insert(Phpfox::getT('admincp_login'), [
                'user_id'    => Phpfox::getUserId(),
                'is_failed'  => $iStatus,
                'ip_address' => Phpfox::getIp(),
                'cache_data' => serialize([
                        'location'   => $_SERVER['REQUEST_URI'],
                        'referrer'   => (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null),
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                        'request'    => serialize($_REQUEST)
                    ]
                ),
                'time_stamp' => PHPFOX_TIME
            ]
        );
    }

    /**
     * This function allows a user to log in as another user.
     *
     * @param $aUser
     *
     * @return bool
     * @throws Exception
     */
    public function snoop($aUser)
    {
        Phpfox::isUser(true);
        if (!Phpfox::getUserParam('user.can_member_snoop')) {
            return Phpfox_Error::set(_p('admin_lacks_permissions'));
        }
        $sPasswordHash = Phpfox::getLib('hash')->setRandomHash(Phpfox::getLib('hash')->setHash($aUser['password'], $aUser['password_salt']));

        // Set cookie (yummy)
        $iTime = 0;
        $this->database()->insert(Phpfox::getT('user_snoop'), [
            'time_stamp'    => PHPFOX_TIME,
            'user_id'       => Phpfox::getUserId(),
            'logging_in_as' => $aUser['user_id']
        ]);

        Phpfox::setCookie($this->_sNameCookieUserId, $aUser['user_id'], $iTime);
        Phpfox::setCookie($this->_sNameCookieHash, $sPasswordHash, $iTime);
        Phpfox::setCookie($this->_sNameCookieLoginType, 'email', $iTime);
        if (!defined('PHPFOX_INSTALLER')) {
            Phpfox::getLib('session')->remove('theme');
        }

        $this->database()->update($this->_sTable, ['last_login' => PHPFOX_TIME], 'user_id = ' . $aUser['user_id']);
        $this->database()->insert(Phpfox::getT('user_ip'), [
                'user_id'    => $aUser['user_id'],
                'type_id'    => 'login',
                'ip_address' => Phpfox::getIp(),
                'time_stamp' => PHPFOX_TIME
            ]
        );
        return true;
    }

    public function clearUserIp()
    {
        $sTime = PHPFOX_TIME - 24 * 60 * 60; // only keep 1 day
        $this->database()->delete(Phpfox::getT('user_ip'), 'time_stamp < ' . $sTime);
    }

    public function checkPassword($aUser, $sPassword, $bThrowError = true)
    {
        if (empty($sPassword)) {
            return $bThrowError ? Phpfox_Error::set(_p('provide_a_valid_password')) : false;
        }
        if (strlen($aUser['password']) > 32) {
            $Hash = new Core\Hash();
            if (!$Hash->check($sPassword, $aUser['password'])) {
                return $bThrowError ? Phpfox_Error::set(_p('password_is_not_correct_please_try_again')) : false;
            }
        } else {
            if ((Phpfox::getLib('hash')->setHash($sPassword, $aUser['password_salt']) != $aUser['password'])) {
                return $bThrowError ? Phpfox_Error::set(_p('password_is_not_correct_please_try_again')) : false;
            }
        }
        return true;
    }

    public function sendTwoStepPasscode($sUser)
    {
        $aInput = explode('_', $sUser);
        if (count($aInput) != 2) {
            return Phpfox_Error::set(_p('invalid_user'));
        }
        $aUser = Phpfox::getService('user')->getUser((int)$aInput[1]);
        if (empty($aUser['user_id'])) {
            return Phpfox_Error::set(_p('invalid_user'));
        }
        $sUserString = trim(implode(',', [$aUser['email'], $aUser['full_phone_number']]), ',');
        $oGoogleService = Phpfox::getService('user.googleauth');
        $oGoogleService->setUser($sUserString);
        $sPasscode = $oGoogleService->generateCode($sUserString, 30);
        if (!empty($sPasscode)) {
            if ($aInput[0] == 'email') {
                Phpfox::getLib('mail')
                    ->aUser($aUser)
                    ->skipSms(true)
                    ->subject(['new_sign_in_two_step_verification_subject'])
                    ->message(['new_sign_in_two_step_verification_content', [
                        'code' => $sPasscode
                    ]])->send(false, true);
            } else {
                Phpfox::getLib('phpfox.verify')
                    ->sendSMS($aUser['full_phone_number'], _p('new_sign_in_two_step_verification_sms', ['code' => $sPasscode], $aUser['language_id']));
            }
            return true;
        }
        return false;
    }
}
