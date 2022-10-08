<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Service_Block_Process
 */
class User_Service_Block_Process extends Phpfox_Service
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
        $this->_sTable = Phpfox::getT('user_blocked');
    }

    public function add($iBlockedUserId)
    {
        Phpfox::isUser(true);
        Phpfox::getUserParam('user.can_block_other_members', true);

        if ($iBlockedUserId == Phpfox::getUserId()) {
            return Phpfox_Error::set(_p('not_able_to_block_yourself'));
        }

        if (Phpfox::getService('user.block')->isBlocked(Phpfox::getUserId(), $iBlockedUserId)) {
            return Phpfox_Error::set(_p('you_have_already_blocked_this_user'));
        }

        $aUser = Phpfox::getService('user')->getUser($iBlockedUserId, 'u.user_id, u.user_group_id');

        if (!Phpfox::getUserGroupParam($aUser['user_group_id'], 'user.can_be_blocked_by_others')) {
            return Phpfox_Error::set(_p('unable_to_block_this_user'));
        }

        $this->database()->insert($this->_sTable, [
                'user_id'       => Phpfox::getUserId(),
                'block_user_id' => (int)$iBlockedUserId,
                'time_stamp'    => PHPFOX_TIME,
                'ip_address'    => Phpfox::getIp()
            ]
        );

        cache()->del('user_block_both_' . Phpfox::getUserId());
        cache()->del('user_block_both_' . $iBlockedUserId);
        Phpfox::getService('user')->clearUserCache();
        Phpfox::getService('user')->clearUserCache($iBlockedUserId);

        // Mass callback
        Phpfox::massCallback('onBlockUser', $iBlockedUserId);

        if (Phpfox::isModule('friend')) {
            Phpfox::getService('friend.process')->deleteFromConnection(Phpfox::getUserId(), $iBlockedUserId);
            Phpfox::getService('friend.process')->deleteFromConnection($iBlockedUserId, Phpfox::getUserId());

            //Delete friend request
            $request = db()->select('request_id, user_id')
                ->from(Phpfox::getT('friend_request'))
                ->where('(friend_user_id = ' . Phpfox::getUserId() . ' AND user_id = ' . (int)$iBlockedUserId . ') OR (friend_user_id = ' . (int)$iBlockedUserId . ' AND user_id = ' . Phpfox::getUserId() . ')')
                ->execute('getSlaveRow');
            if (!empty($request)) {
                $this->database()->delete(Phpfox::getT('friend_request'), 'request_id = ' . (int)$request['request_id']);
                (Phpfox::isModule('request') ? Phpfox::getService('request.process')->delete('friend_request', $request['request_id'], $request['user_id']) : false);
            }
        }

        return true;
    }

    /**
     * This function is called when a user unblocks another user
     *
     * @param integer $iBlockedUserId
     * @param boolean $bBoth
     *
     * @return bool
     */
    public function delete($iBlockedUserId, $bBoth = false)
    {
        Phpfox::isUser(true);

        $this->database()->delete($this->_sTable, 'user_id = ' . Phpfox::getUserId() . ' AND block_user_id = ' . (int)$iBlockedUserId);

        if ($bBoth) {
            $this->database()->delete($this->_sTable, 'user_id = ' . (int)$iBlockedUserId . ' AND block_user_id = ' . Phpfox::getUserId());
        }
        // Mass callback
        Phpfox::massCallback('onUnBlockUser', $iBlockedUserId, $bBoth);

        cache()->del('user_block_both_' . Phpfox::getUserId());
        cache()->del('user_block_both_' . $iBlockedUserId);

        Phpfox::getService('user')->clearUserCache();
        Phpfox::getService('user')->clearUserCache($iBlockedUserId);

        return true;
    }

    /**
     * If a call is made to an unknown method attempt to connect
     * it to a specific plug-in with the same name thus allowing
     * plug-in developers the ability to extend classes.
     *
     * @param string $sMethod    is the name of the method
     * @param array  $aArguments is the array of arguments of being passed
     *
     * @return mixed
     */
    public function __call($sMethod, $aArguments)
    {
        /**
         * Check if such a plug-in exists and if it does call it.
         */
        if ($sPlugin = Phpfox_Plugin::get('user.service_block_process__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}
