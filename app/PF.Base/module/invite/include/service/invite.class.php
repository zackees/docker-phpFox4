<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 *
 *
 * @copyright        [PHPFOX_COPYRIGHT]
 * @author        phpFox LLC
 * @package        Module_Invite
 * @version        $Id: invite.class.php 6880 2013-11-12 13:56:55Z phpFox LLC $
 */
class Invite_Service_Invite extends Phpfox_Service
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('invite');
    }

    /**
     * Check if invite menu can be shown in footer menu
     * @return bool
     */
    public function canShowInviteMenu()
    {
        return Phpfox::getParam('user.allow_user_registration') && (Phpfox::getUserParam('invite.can_invite_friends') || $this->getPendingInvitationCount());
    }

    /**
     *    Checks every mail to see if its valid and filters the one that have already been invited if set in admin panel
     * @param array $aMails
     * @param int $iUser
     * @param bool $isPhoneNumber
     * @return array with uninvited and valid emails
     */
    public function getValid($aMails, $iUser, $isPhoneNumber = false)
    {
        if (empty($aMails)) {
            return [[],[],[]];
        }

        $aValid = [];
        $aInvalid = [];
        $sDbCheck = '';

        $aMails = array_unique(array_map(function($sMail) {
            return trim($sMail);
        }, $aMails));

        if (!$isPhoneNumber) {
            $oMail = Phpfox::getLib('mail');
            foreach ($aMails as $sMail) {
                $sMail = trim($sMail);
                $sDbCheck .= '\'' . $this->database()->escape($sMail) . '\',';
                if ($oMail->checkEmail($sMail)) {
                    array_push($aValid, $sMail);
                } else {
                    array_push($aInvalid, $sMail . ' - ' . _p('not_a_valid_email'));
                }
            }
            $sDbCheck = rtrim($sDbCheck, ',');
        } else {
            $aPhoneLib = Phpfox::getLib('phone');
            foreach($aMails as $sPhone) {
                if ($aPhoneLib->setRawPhone($sPhone) && $aPhoneLib->isValidPhone()) {
                    array_push($aValid, $aPhoneLib->getPhoneE164());
                    $sDbCheck .= '\''. $aPhoneLib->getPhoneE164() .'\',';
                } else {
                    array_push($aInvalid, $sPhone . ' - ' . _p('not_a_valid_phone_number'));
                }
                $aPhoneLib->reset();
            }
            $sDbCheck = rtrim($sDbCheck, ',');
        }
        $aCacheUsers = [];
        if (empty($sDbCheck)) {
            return [$aValid, $aInvalid, $aCacheUsers];
        }
        if (Phpfox::isModule('friend')) {
            $aUsers = $this->database()->select(Phpfox::getUserField() . ', u.email, u.full_phone_number, f.friend_id')
                ->from(Phpfox::getT('user'), 'u')
                ->leftJoin(Phpfox::getT('friend'), 'f', 'f.user_id = ' . Phpfox::getUserId() . ' AND f.friend_user_id = u.user_id')
                ->where(($isPhoneNumber ? 'u.full_phone_number' : 'u.email') . ' IN(' . $sDbCheck . ')')
                ->execute('getSlaveRows');
        } else {
            $aUsers = $this->database()->select(Phpfox::getUserField() . ', u.email, u.full_phone_number')
                ->from(Phpfox::getT('user'), 'u')
                ->where(($isPhoneNumber ? 'u.full_phone_number' : 'u.email') . ' IN(' . $sDbCheck . ')')
                ->execute('getSlaveRows');
        }

        foreach ($aUsers as $aUser) {
            if ($isPhoneNumber) {
                $aCacheUsers[strtolower($aUser['full_phone_number'])] = $aUser;
            } else {
                $aCacheUsers[strtolower($aUser['email'])] = $aUser;
            }
        }

        foreach ($aValid as $iKey => $sEmail) {
            if (isset($aCacheUsers[strtolower($sEmail)])) {
                unset($aValid[$iKey]);
            }
        }

        // should we check for duplicate invites by this user
        if (Phpfox::getParam('invite.check_duplicate_invites') && !empty($sDbCheck)) {
            // get the invites that he is trying to resend
            $aDuplicate = $this->database()->select('user_id, email, time_stamp')
                ->from($this->_sTable)
                ->where('email IN(' . $sDbCheck . ')')
                ->execute('getSlaveRows');

            if (empty($aDuplicate)) {
                return [$aValid, $aInvalid, $aCacheUsers];
            }

            // go through the elements
            foreach ($aDuplicate as $aDupl) {
                // in both arrays
                foreach ($aValid as $iKey => $sIndex) {
                    // and compare if one of the valid ones has been sent already
                    if ($aDupl['email'] == $sIndex) {
                        // check invitation if it expired
                        if (setting('invite.invite_expire') && (time() - $aDupl['time_stamp'] > setting('invite.invite_expire') * 86400)) {
                            continue;
                        }

                        // if it has, add to the invalid
                        array_push($aInvalid, $aDupl['email'] . ' - ' . (isset($aDupl['user_id']) && $aDupl['user_id'] != $iUser ? 'Already invited' : _p('you_have_already_invited')));
                        // and remove from the valid ones
                        unset($aValid[$iKey]);
                    }
                }
            }
        }

        return [$aValid, $aInvalid, $aCacheUsers];
    }

    /**
     *    Gets a set of invites to display in the pending invitation section
     * @param int $iUser
     * @param int $iPage
     * @param int $iPageSize
     * @return array
     */
    public function get($iUser, $iPage, $iPageSize)
    {
        $iCnt = $this->getPendingInvitationCount($iUser);

        $aInvites = $this->database()->select('*')
            ->from($this->_sTable)
            ->where('user_id = ' . (int)$iUser)
            ->limit($iPage, $iPageSize, $iCnt)
            ->order('invite_id')
            ->execute('getSlaveRows');

        $iTotal = ($iPage > 1 ? (($iPageSize * $iPage) - $iPageSize) : 0);
        foreach ($aInvites as $iKey => $aPost) {
            $iTotal++;
            $aInvites[$iKey]['count'] = $iTotal;
        }

        return [$iCnt, $aInvites];
    }

    public function getPendingInvitationCount($iUserId = null)
    {
        empty($iUserId) && $iUserId = Phpfox::getUserId();

        return (int)$this->database()->select("COUNT(*)")
            ->from($this->_sTable, 'i')
            ->where([
                'i.user_id' => $iUserId,
            ])->execute('getSlaveField');
    }

    public function isValidInvite($sEmail, $bDetailError = false, $sPhone = null)
    {
        $oPhoneLib = Phpfox::getLib('phone');
        $bEmailIsPhoneNumber = false;
        if ($sPhone === null && Phpfox::getParam('core.enable_register_with_phone_number') && $oPhoneLib->setRawPhone($sEmail) && $oPhoneLib->isValidPhone()) {
            $sEmail = $oPhoneLib->getPhoneE164();
            $bEmailIsPhoneNumber = true;
        }
        $aInvite = $this->database()->select('invite_id')
            ->from($this->_sTable)
            ->where('email = \'' . $this->database()->escape($sEmail) . '\'' . ($sPhone != null ? ' or email = \'' . $sPhone . '\'' : ''))
            ->execute('getSlaveRow');

        if (!isset($aInvite['invite_id'])) {
            $sDetailErrorMessage = _p(Phpfox::getParam('core.enable_register_with_phone_number') ? 'unable_to_find_your_invitation_site_is_invite_only_with_enable_phone' : 'unable_to_find_your_invitation_site_is_invite_only', ['site' => Phpfox::getParam('core.site_title')]);
            return Phpfox_Error::set($bDetailError ? $sDetailErrorMessage : _p('unable_to_find_your_invitation'));
        }
        $aConditions = [];
        if (!empty($sEmail)) {
            $aConditions[] = ($bEmailIsPhoneNumber ? 'full_phone_number' : 'email') . ' = \'' . $this->database()->escape($sEmail) . '\'';
        }
        if (!empty($sPhone)) {
            $aConditions[] = 'full_phone_number = \'' . $sPhone . '\'';
        }
        $aUser = $this->database()->select('user_id')
            ->from(Phpfox::getT('user'))
            ->where(implode(' or ', $aConditions))
            ->execute('getSlaveRow');

        if (isset($aUser['user_id'])) {
            return Phpfox_Error::set(_p($bEmailIsPhoneNumber || !empty($sPhone) ?
                'this_phone_is_already_registered_within_our_community' : 'this_email_is_already_registered_within_our_community'));
        }

        return true;
    }

    public function isInviteOnly()
    {
        if (Phpfox::getCookie('invite_only_pass') != '') {
            return false;
        }

        if (Phpfox::getParam('user.invite_only_community')) {
            return true;
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
     */
    public function __call($sMethod, $aArguments)
    {
        /**
         * Check if such a plug-in exists and if it does call it.
         */
        if ($sPlugin = Phpfox_Plugin::get('invite.service_invite__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}