<?php

defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Service_Validate
 */
class User_Service_Validate extends Phpfox_Service
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

	public function user($sUser, $bReturnError = false)
	{
		Phpfox::getLib('parse.input')->allowTitle($sUser, _p('user_name_is_already_in_use'));

		if (!Phpfox::getService('ban')->check('username', $sUser)) {
            Phpfox_Error::set(_p('invalid_user_name'));
            return false;
		}

		if (!Phpfox::getParam('user.profile_use_id') && (Phpfox::getParam('user.disable_username_on_sign_up') != 'full_name'))
		{
			$sUser = Phpfox::getLib('parse.input')->clean($sUser);
			/* Check if there is a page with the same url as the user name*/
			$aPages = Phpfox::getService('page')->get();
			foreach ($aPages as $aPage)
			{
				if ($aPage['title_url'] == strtolower($sUser))
				{
					return Phpfox_Error::set(_p('invalid_user_name'));
				}
			}
		}
		return true;
	}

	public function email($sEmail, $iIgnoredUserId = null, $bAlreadyLogin = false)
	{
        $iCnt = $this->database()->select('COUNT(*)')
			->from($this->_sTable)
			->where("email = '" . $this->database()->escape($sEmail) . "'" . ($iIgnoredUserId ? " AND user_id <> " . (int)$iIgnoredUserId : ""))
			->execute('getSlaveField');
        if ($iCnt && $sEmail) {
			Phpfox_Error::set(_p($bAlreadyLogin ? 'error_email_is_in_use' : 'email_is_in_use_and_user_can_login', array('email' => trim(strip_tags($sEmail)), 'link' => Phpfox_Url::instance()->makeUrl('user.login', array('email' => base64_encode($sEmail))))));
		}

		if (!Phpfox::getService('ban')->check('email', $sEmail)) {
			Phpfox_Error::set(_p('this_email_is_not_allowed_to_be_used'));
		}

		return $this;
	}

    public function phone($sPhone, $bIsE164 = false, $noThrowErr = false, $iIgnoredUserId = null, $bIsSignUp = false, $bAlreadyLogin = false)
    {
        $oPhone = Phpfox::getLib('phone');
        if (!$bIsE164) {
            if ($oPhone->setRawPhone($sPhone) && $oPhone->isValidPhone()) {
                $sPhone = $oPhone->getPhoneE164();
                $sNationPhone = $oPhone->getPhoneNational();
            } else {
                return $noThrowErr ? false : Phpfox_Error::set(_p('phone_number_is_invalid'));
            }
        } else {
            $oPhone->setRawPhone($sPhone);
            $sNationPhone = $oPhone->getPhoneNational();
        }

        if (!Phpfox::getService('ban')->check('email', $sPhone, false, 'phone_number')
        || !Phpfox::getService('ban')->check('email', $sNationPhone, false, 'phone_number')) {
            return Phpfox_Error::set(_p('this_phone_number_is_not_allowed_to_be_used'));
        }

        $iCnt = $this->database()->select('COUNT(*)')
            ->from($this->_sTable)
            ->where("full_phone_number = '" . $this->database()->escape($sPhone) . "'" . ($iIgnoredUserId ? " AND user_id <> " . (int)$iIgnoredUserId : ""))
            ->execute('getSlaveField');

        if ($iCnt && $sPhone) {
            return $noThrowErr && !$bIsSignUp ? false : Phpfox_Error::set(_p($bAlreadyLogin ? 'error_phone_is_in_use' : 'phone_is_in_use_and_user_can_login', array('phone' => trim(strip_tags($sPhone)), 'link' => Phpfox_Url::instance()->makeUrl('user.login', array('email' => base64_encode($sPhone))))));
        }

        return $this;
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
        if ($sPlugin = Phpfox_Plugin::get('user.service_validate__call'))
        {
            eval($sPlugin);
            return null;
		}

		/**
		 * No method or plug-in found we must throw a error.
		 */
		Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
	}
}
