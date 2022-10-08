<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Service_Verify_Verify
 */
class User_Service_Verify_Verify extends Phpfox_Service
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
        $this->_sTable = Phpfox::getT('user');
    }


    /**
     * @param $userId
     * @return array|bool|int|string
     */
    public function getVerificationTimeByUserId($userId)
    {
        if (empty($userId)) {
            return false;
        }
        $time = db()->select('time_stamp')
            ->from(Phpfox::getT('user_verify'))
            ->where('user_id = ' . (int)$userId)
            ->execute('getSlaveField');
        return $time ? $time : 0;
    }

    /**
     * @param string $sEmail
     * @param bool $bIsPhone
     * @param bool $bCheckOnly
     * @param bool $bForceNew
     * @param string $sType
     * @return string
     */
    public function getVerifyHashByEmail($sEmail, $bIsPhone = false, $bCheckOnly = false, $bForceNew = false, $sType = 'verify_account')
    {
        if (!$sEmail) {
            return false;
        }

        $aHashcode = $this->database()
            ->select('uv.verify_id, uv.hash_code, uv.email, uv.user_id, u.password')
            ->from(Phpfox::getT('user_verify'), 'uv')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = uv.user_id')
            ->where('uv.email=\'' . $this->database()->escape($sEmail) . '\' AND uv.type_id =\'' . $sType . '\'')
            ->execute('getRow');
        $sHashcode = isset($aHashcode['hash_code']) ? $aHashcode['hash_code'] : null;
        if ((empty($aHashcode) || $bForceNew) && !$bCheckOnly) {
            if ($bForceNew && !empty($aHashcode)) {
                $this->database()->delete(Phpfox::getT('user_verify'), ['verify_id' => $aHashcode['verify_id']]);
            }
            $sHashcode = $this->generateHash($sEmail, $bIsPhone, $bForceNew && !empty($aHashcode) ? [
                'user_id' => $aHashcode['user_id'],
                'email' => $aHashcode['email'],
                'password' => $aHashcode['password'],
                'full_phone_number' => $bIsPhone ? $aHashcode['email'] : null
            ] : [], $sType);
        }
        return $sHashcode;
    }

    public function generateHash($sEmail, $bIsPhone = false, $aUser = [], $sType = 'verify_account')
    {
        if (empty($aUser)) {
            $aUser = $this->database()
                ->select('user_id, email, password, full_phone_number')
                ->from($this->_sTable)
                ->where(($bIsPhone ? 'full_phone_number = \'' : 'email = \'') . $this->database()->escape($sEmail) . '\'')
                ->execute('getSlaveRow');
        }
        $sHash = '';
        if ($aUser) {
            if (Phpfox::getParam('core.registration_sms_enable') || $bIsPhone) {
                $sHash = Phpfox::getLib('phpfox.verify')->generateOneTimeTokenToSMS();
            } elseif (Phpfox::getParam('user.verify_email_at_signup')) {
                $sHash = $this->getVerifyHash($aUser);
            }
            if ($sHash) {
                $this->database()->insert(':user_verify', [
                    'user_id' => $aUser['user_id'],
                    'hash_code' => $sHash,
                    'time_stamp' => PHPFOX_TIME,
                    'email' => $bIsPhone ? $aUser['full_phone_number'] : $aUser['email'],
                    'type_id' => $sType
                ]);
            }
        }
        return $sHash;
    }

    /**
     * Generates the unique hash to be used when verifying email addresses
     * @param array $aUser
     * @return String 50~52 chars
     */
    public function getVerifyHash($aUser)
    {
        return Phpfox::getLib('hash')->setRandomHash($aUser['user_id'] . $aUser['email'] . $aUser['password'] . Phpfox::getParam('core.salt'));
    }

    public function getVerificationByUser($iUserId, $bGetEmail = false, $bCheckStatus = false)
    {
        if (empty($iUserId)) {
            return false;
        }
        $aVerify = db()->select('uv.verify_id, uv.email, uv.hash_code, u.status_id')
            ->from(Phpfox::getT('user_verify'), 'uv')
            ->join($this->_sTable, 'u', 'u.user_id = uv.user_id')
            ->where('uv.user_id = ' . (int)$iUserId . ' AND uv.type_id = \'verify_account\'')
            ->order('uv.time_stamp DESC')
            ->executeRow();
        if (!empty($aVerify['email'])) {
            if ($bCheckStatus && (int)$aVerify['status_id'] === 0) {
                //User isn't pending verification
                db()->delete(Phpfox::getT('user_verify'), ['verify_id' => $aVerify['verify_id']]);
                return false;
            }
            $bIsSmsCode = preg_match('/^[0-9]{6}$/', $aVerify['hash_code']);
            if ((function_exists('filter_var') && filter_var($aVerify['email'], FILTER_VALIDATE_EMAIL))
                || preg_match('/[-0-9a-zA-Z.+_]+@[-0-9a-zA-Z.+_]+/', $aVerify['email'])) {
                //Verify email
                return $bGetEmail ? [1, $aVerify['email'], $bIsSmsCode] : 1;
            } else {
                //Verify phone
                return $bGetEmail ? [2, $aVerify['email'], $bIsSmsCode] : 2;
            }
        }
        return false;
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
        if ($sPlugin = Phpfox_Plugin::get('user.service_activity__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}
