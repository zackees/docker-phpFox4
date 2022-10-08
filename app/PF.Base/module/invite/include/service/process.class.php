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
 * @version        $Id: process.class.php 3403 2011-11-01 09:32:44Z phpFox LLC $
 */
class Invite_Service_Process extends Phpfox_Service
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('invite');
    }

    /**
     *
     * @param string $sMail email of the invited person
     * @param integer $iInviter user who invited $sMail
     * @param bool $bGetHash return hash code or invite id
     * @return integer id of the new insert
     */
    public function addInvite($sMail, $iInviter, $bGetHash = false)
    {
        $sHashCode = Phpfox::getLib('hash')->setRandomHash($iInviter . $sMail . Phpfox::getParam('core.salt'));
        $iId = $this->database()->insert($this->_sTable, [
                'user_id' => (int)$iInviter,
                'email' => $sMail,
                'time_stamp' => PHPFOX_TIME,
                'is_used' => 0,
                'hash_code' => $sHashCode
            ]
        );
        if ($iId) {
            return $bGetHash ? $sHashCode : $iId;
        }
        return false;
    }

    /**
     * Deletes one invite
     * @param int $iInvite
     * @param int $iUser
     * @return bool
     */
    public function delete($iInvite, $iUser)
    {
        // check if the invite exists
        $iExists = $this->database()->select('COUNT(*)')
            ->from($this->_sTable)
            ->where('invite_id = ' . (int)$iInvite . ' AND user_id = ' . (int)$iUser)
            ->execute('getSlaveField');
        if ($iExists > 0) {
            $this->database()->delete($this->_sTable, 'invite_id = ' . (int)$iInvite . ' AND user_id = ' . (int)$iUser);
            return true;
        }
        return false;
    }

    /**
     *
     * @param integer $iId user who invited the guest, or the entry in the invite table
     * @param boolean $bFromMail defines if user was invited by email or from a link
     * @return boolean
     */
    public function updateInvite($iId, $bFromMail = true)
    {
        if (is_numeric($iId)) {
            $aCondition = ['invite_id' => (int)$iId];
        } else {
            $aCondition = ['hash_code' => $iId];
        }
        $aInvite = $this->database()->select('*')
            ->from($this->_sTable)
            ->where($aCondition)
            ->execute('getSlaveRow');

        if (!isset($aInvite['invite_id']) || ($bFromMail && $aInvite['is_used'])) {
            return false;
        }

        if (!Phpfox::getParam('user.allow_user_registration')) {
            return true;
        }
        $iExpire = (Phpfox::getParam('invite.invite_expire') > 0 ? (Phpfox::getParam('invite.invite_expire') * 60 * 60 * 24) : (7 * 60 * 60 * 24));

        if (!$bFromMail) {
            Phpfox::setCookie('invited_by_user', $aInvite['invite_id'], PHPFOX_TIME + $iExpire);
        } else {
            Phpfox::setCookie('invited_by_email', $aInvite['invite_id'], PHPFOX_TIME + $iExpire);
            Phpfox::setCookie('invited_by_email_form', $aInvite['email'], PHPFOX_TIME + $iExpire);
            $this->database()->update($this->_sTable, ['is_used' => 1], 'invite_id = ' . (int)$aInvite['invite_id']);
        }

        return true;
    }

    /**
     * Actions to take after an invited guest signs up
     * @param $iUserId
     */
    public function registerInvited($iUserId)
    {
        $aInvite = [];
        if (($iInviteId = Phpfox::getCookie('invited_by_user'))) {
            $aInvite = $this->database()->select('user_id')
                ->from(Phpfox::getT('user'))
                ->where('user_id = ' . (int)$iInviteId)
                ->execute('getSlaveRow');
        } elseif (($iInviteId = Phpfox::getCookie('invited_by_email'))) {
            $aInvite = $this->database()->select('invite_id, user_id, email')
                ->from($this->_sTable)
                ->where('invite_id = ' . (int)$iInviteId)
                ->execute('getSlaveRow');

            if (isset($aInvite['invite_id'])) {
                $invitedUserInfo = db()->select('email, full_phone_number')
                    ->from(':user')
                    ->where([
                        'user_id' => $iUserId
                    ])->executeRow(false);
                $aInvite['no_make_friend'] = !in_array($aInvite['email'], array_values($invitedUserInfo));
                $this->database()->delete(Phpfox::getT('invite'), "invite_id = '" . $aInvite['invite_id'] . "'");
            }
        }

        if (isset($aInvite['user_id'])) {
            if (empty($aInvite['no_make_friend'])) {
                // Both should now be friends
                $this->_makeFriends($iUserId, $aInvite['user_id']);
            }

            // update the user table field for invite_user_id
            $this->database()->update(Phpfox::getT('user'), ['invite_user_id' => (int)$aInvite['user_id']], 'user_id = ' . $iUserId);

            // award points
            // relying on the script's type validation as its defined as integer
            Phpfox::getService('user.activity')->update($iUserId, 'invite', '+');
            Phpfox::getService('user.activity')->update($aInvite['user_id'], 'invite', '+');
        }

        Phpfox::setCookie('invited_by_user', 0, '-1');
        Phpfox::setCookie('invited_by_email', 0, '-1');
    }

    /**
     * This function is called when a user registers in the site but did not follow a link sent via
     * invitation.
     * @param array|int $aUser Full array as used when user.
     * @param bool $bCheckIsFriend
     * This function is only used in the user.process service
     */
    public function registerByEmail($aUser, $bCheckIsFriend = false)
    {
        if (is_numeric($aUser)) {
            $aUser = Phpfox::getService('user')->get((int)$aUser);
        }
        if (empty($aUser)) {
            return false;
        }
        if (Phpfox::isModule('friend')) {
            // get all the invitations sent to this email address
            $aInvites = $this->database()->select('i.*')
                ->from(Phpfox::getT('invite'), 'i')
                ->join(Phpfox::getT('user'), 'u', 'u.user_id = i.user_id')
                ->where('i.email = \'' . $aUser['email'] . '\'' . (!empty($aUser['full_phone_number']) ? ' OR i.email = \'' . $aUser['full_phone_number'] . '\'' : ''))
                ->execute('getSlaveRows');

            foreach ($aInvites as $aInvite) {
                $this->_makeFriends($aUser['user_id'], $aInvite['user_id'], $bCheckIsFriend);
            }
        }
        //Delete all invitation sent to this user via email or phone
        return $this->database()->delete(Phpfox::getT('invite'), 'email=\'' . $aUser['email'] . '\'' . (!empty($aUser['full_phone_number']) ? ' OR email = \'' . $aUser['full_phone_number'] . '\'' : ''));
    }

    private function _makeFriends($iUser, $iGuestId, $bCheckIsFriend = false)
    {
        if (Phpfox::isModule('friend') && Phpfox::getParam('invite.make_friends_on_invitee_registration')) {
            $isUserFriend = $isGuestFriend = false;
            if ($bCheckIsFriend) {
                $isUserFriend = Phpfox::getService('friend')->isFriend($iUser, $iGuestId);
                $isGuestFriend = Phpfox::getService('friend')->isFriend($iGuestId, $iUser);
            }
            if (!$isUserFriend) {
                $this->database()->insert(Phpfox::getT('friend'), [
                        'user_id'        => (int)$iUser,
                        'friend_user_id' => (int)$iGuestId,
                        'time_stamp'     => PHPFOX_TIME
                    ]
                );
            }
            if (!$isGuestFriend) {
                $this->database()->insert(Phpfox::getT('friend'), [
                        'user_id'        => (int)$iGuestId,
                        'friend_user_id' => (int)$iUser,
                        'time_stamp'     => PHPFOX_TIME
                    ]
                );
            }
            if (Phpfox::isModule('friend')) {
                Phpfox::getService('friend.process')->updateFriendCount($iUser, $iGuestId);
            }
        }
        return true;
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
        if ($sPlugin = Phpfox_Plugin::get('invite.service_process__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}