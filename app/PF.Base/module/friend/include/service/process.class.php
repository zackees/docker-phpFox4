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
 * @package        Module_Friend
 * @version        $Id: process.class.php 7274 2014-04-21 13:25:12Z Fern $
 */
class Friend_Service_Process extends Phpfox_Service
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('friend');
    }

    /**
     * @param $iUserId int Is the user accepting the Friend Request
     * @param $iFriendId int is the user who sent the friend request
     * @param int $iFolderId
     * @return bool
     */
    public function add($iUserId, $iFriendId, $iFolderId = 0)
    {
        $iIsFriend = $this->database()->select('COUNT(*)')
            ->from(Phpfox::getT('friend'), 'f')
            ->where('f.user_id = ' . (int)$iUserId . ' AND f.friend_user_id = ' . (int)$iFriendId)
            ->execute('getSlaveField');

        // They are already friends lets not add them again
        if ($iIsFriend) {
            // and remove the friend request
            $this->database()->delete(Phpfox::getT('friend_request'), 'user_id = ' . (int)$iUserId . ' AND friend_user_id = ' . (int)$iFriendId);
            return false;
        }

        $friendshipDirection = Phpfox::getParam('friend.friendship_direction', 'two_way_friendships');
        if ($friendshipDirection != 'one_way_friendships') {
            $aRow = $this->database()->select('fr.request_id, fr.friend_user_id, fr.list_id, u.user_id, u.email, u.user_name, u.full_name')
                ->from(Phpfox::getT('friend_request'), 'fr')
                ->join(Phpfox::getT('user'), 'u', 'u.user_id = fr.friend_user_id')
                ->where('fr.user_id = ' . (int)$iUserId . ' AND fr.friend_user_id = ' . (int)$iFriendId)
                ->execute('getSlaveRow');

            // No such requests, lets skip it
            if (!isset($aRow['user_id'])) {
                return false;
            }

            $iFriendToUser = $this->database()->insert($this->_sTable, [
                    'list_id' => $aRow['list_id'],
                    'user_id' => $iFriendId,
                    'friend_user_id' => $iUserId,
                    'time_stamp' => PHPFOX_TIME
                ]
            );

            // Add to feed
            (Phpfox::isModule('feed') && Phpfox::getParam('friend.friend_allow_posting_on_main_feed', 1) ? Phpfox::getService('feed.process')->add('friend', $iFriendToUser, 0, 0, $iFriendId, Phpfox::getUserId()) : false);

            $this->database()->delete(Phpfox::getT('user_blocked'), 'user_id = ' . ((int)$iFriendId) . ' AND block_user_id = ' . ((int)$iUserId));
            $this->database()->delete(Phpfox::getT('friend_request'), '(user_id = ' . (int)$iUserId . ' AND friend_user_id = ' . (int)$iFriendId . ') OR (user_id = ' . (int)$iFriendId . ' AND friend_user_id = ' . (int)$iUserId . ')');

            // Remove the initial request
            (Phpfox::isModule('request') ? Phpfox::getService('request.process')->delete('friend_request', $aRow['request_id'], $iUserId) : false);

            // Send the user an email
            $sLink = Phpfox::getService('user')->getLink(Phpfox::getUserId(), Phpfox::getUserBy('user_name'));
            Phpfox::getLib('mail')->to($iFriendId)
                ->subject(['full_name_confirmed_you_as_a_friend_on_site_title', ['full_name' => Phpfox::getUserBy('full_name'), 'site_title' => Phpfox::getParam('core.site_title')]])
                ->message(['full_name_confirmed_you_as_a_friend_on_site_title_to_view_their_profile', ['full_name' => Phpfox::getUserBy('full_name'), 'site_title' => Phpfox::getParam('core.site_title'), 'link' => $sLink]])
                ->notification('friend.new_friend_accepted')
                ->send();

            Phpfox::getService('notification.process')->add('friend_accepted', $iUserId, $iFriendId);
        } else {
            // Send the user an email
            $sLink = Phpfox::getService('user')->getLink(Phpfox::getUserId(), Phpfox::getUserBy('user_name'));
            Phpfox::getLib('mail')->to($iFriendId)
                ->subject(['full_name_added_you_as_a_friend_on_site_title', ['full_name' => Phpfox::getUserBy('full_name'), 'site_title' => Phpfox::getParam('core.site_title')]])
                ->message(['full_name_added_you_as_a_friend_on_site_title_to_view_the_profile_of_this_friend_follow_the_link_below_link', ['full_name' => Phpfox::getUserBy('full_name'), 'site_title' => Phpfox::getParam('core.site_title'), 'link' => $sLink]])
                ->notification('friend.new_friend_accepted')
                ->send();

            Phpfox::getService('notification.process')->add('friend_added', $iUserId, $iFriendId);
        }

        $this->database()->insert($this->_sTable, [
                'list_id' => $iFolderId,
                'user_id' => $iUserId,
                'friend_user_id' => $iFriendId,
                'time_stamp' => PHPFOX_TIME
            ]
        );
        $this->database()->delete(Phpfox::getT('user_blocked'), 'user_id = ' . ((int)$iUserId) . ' AND block_user_id = ' . ((int)$iFriendId));

        if (Phpfox::getParam('friend.cache_rand_list_of_friends') > 0) {
            $sCacheId = $this->cache()->set(['friend_rand_6', $iUserId]);
            $this->cache()->remove($sCacheId);
            if ($friendshipDirection != 'one_way_friendships') {
                $sCacheId = $this->cache()->set(['friend_rand_6', $iFriendId]);
                $this->cache()->remove($sCacheId);
            }
        }

        // Update friend count
        $this->_updateFriendCount($iUserId, $iFriendId);

        if (Phpfox::getParam('friend.enable_friend_suggestion')) {
            Phpfox::getService('friend.suggestion')->reBuild($iUserId);
            if ($friendshipDirection != 'one_way_friendships') {
                Phpfox::getService('friend.suggestion')->reBuild($iFriendId);
            }
        }

        if (Phpfox::isAppActive('Core_Activity_Points')) {
            Phpfox::getService('activitypoint.process')->updatePoints($iFriendId, 'friend_addnewfriend');
        }

        if ($sPlugin = Phpfox_Plugin::get('friend.service_process_add__1')) {
            eval($sPlugin);
        }

        return true;
    }

    /**
     * Denies a friend request
     * @param $iUserId
     * @param $iFriendId
     * @return bool
     */
    public function deny($iUserId, $iFriendId)
    {
        $aRow = $this->database()->select('fr.request_id, fr.user_id, fr.friend_user_id, u.user_name')
            ->from(Phpfox::getT('friend_request'), 'fr')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = fr.friend_user_id')
            ->where('fr.user_id = ' . (int)$iUserId . ' AND fr.friend_user_id = ' . (int)$iFriendId)
            ->execute('getSlaveRow');

        if (!isset($aRow['user_id'])) {
            $this->database()->delete(Phpfox::getT('friend_request'), 'user_id = ' . (int)$iUserId . ' AND friend_user_id = ' . (int)$iFriendId);
            return false;
        }

        $this->database()->update(Phpfox::getT('friend_request'), ['is_ignore' => 1], [
            'user_id' => (int)$iUserId,
            'friend_user_id' => (int)$iFriendId,
        ]);

        $this->database()->delete(Phpfox::getT('friend_request'), [
            'is_ignore' => 1,
            'user_id' => (int)$iFriendId,
            'friend_user_id' => (int)$iUserId,
        ]);

        (Phpfox::isModule('request') ? Phpfox::getService('request.process')->delete('friend_request', $aRow['request_id'], $iUserId) : false);

        $this->_updateFriendCount($iUserId, $iFriendId);

        if (Phpfox::getParam('friend.enable_friend_suggestion')) {
            Phpfox::getService('friend.suggestion')->reBuild($iUserId);
            Phpfox::getService('friend.suggestion')->reBuild($iFriendId);
        }

        return true;
    }

    public function updateOrder($aVals)
    {
        asort($aVals);

        foreach ($aVals as $iKey => $iId) {
            $this->database()->update($this->_sTable, ['ordering' => ($iKey + 1)], 'friend_id = ' . (int)$iId . ' AND user_id = ' . Phpfox::getUserId());
        }

        return true;
    }

    /**
     * Sends the birthday congratulation
     * @param int $iUser
     * @param string $sMessage
     * @param int| $iEgift egift_id or 0
     * @return int|boolean int if fCost > 0, boolean otherwise
     */
    public function sendCongrats($iUser, $sMessage, $iEgift = 0, $fCost = 0)
    {

        $iUser = (int)$iUser;
        if ($iUser < 1) {
            return false;
        }


        /* Lets skip sending a notification until the user has paid if its a charged gift*/
        if ($fCost > 0) {
            // Notification.process::add checks for this before adding it.
            define('SKIP_NOTIFICATION', true);
            // this is checked in the mail lib
            define('PHPFOX_SKIP_MAIL', true);
        }

        if (!Phpfox::isAppActive('Core_Messages')) {
            return false;
        }
        /* Always send the message but need to alter the mail display routine to just
         * display messages that have been paid */
        $aMail = [
            'to' => $iUser,
            'subject' => _p('happy_birthday')
        ];
        $iBirthdayId = Phpfox::getService('mail')->add($aMail);
        /* if its free then send the notification and mail*/
        if ($fCost != 0 && $iEgift > 0) {
            /* Create an invoice*/
            $iInvoice = $this->database()->insert(Phpfox::getT('egift_invoice'), [
                'user_from' => Phpfox::getUserId(),
                'user_to' => $iUser,
                'egift_id' => $iEgift,
                'birthday_id' => $iBirthdayId,
                'currency_id' => Phpfox::getService('user')->getCurrency(),
                'price' => $fCost,
                'time_stamp_created' => PHPFOX_TIME,
                'status' => 'pending'
            ]);
            return $iInvoice;
        }
        return true;
    }

    public function toggleTop($iId, $bRemove = false)
    {
        $this->database()->update($this->_sTable, ['is_top_friend' => ($bRemove ? 0 : 1)], 'friend_id = ' . (int)$iId . ' AND user_id = ' . Phpfox::getUserId());
        // when adding a top friend we have to set the ordering to the last+1 ordering of the existing users
        if ($bRemove == false) {
            $iHighOrdering = $this->database()->select('ordering')
                ->from($this->_sTable)
                ->where('is_top_friend = 1 AND user_id = ' . Phpfox::getUserId())
                ->order('ordering DESC')
                ->execute('getSlaveField');
            ++$iHighOrdering;

            $this->database()->update($this->_sTable, ['ordering' => $iHighOrdering], 'friend_id = ' . $iId);
        } else {
            $this->database()->update($this->_sTable, ['ordering' => 0], 'friend_id = ' . $iId . ' AND user_id = ' . Phpfox::getUserId());
        }
        // if we are removing from top list it does'nt matter because the "small" controller fetches by is_top_friend first, so
        // non top friends even with higher ordering will be listed after the top friends
        return true;
    }


    public function delete($iId, $bIsFriendId = true)
    {
        $aFriend = $this->database()->select('f.*')
            ->from($this->_sTable, 'f')
            ->where(($bIsFriendId == true ? 'f.friend_id =' : 'f.friend_user_id =') . (int)$iId . ' AND f.user_id = ' . Phpfox::getUserId())
            ->execute('getSlaveRow');

        // Invalid friend ID#
        if (!isset($aFriend['friend_id'])) {
            return false;
        }
        $friendshipDirection = Phpfox::getParam('friend.friendship_direction', 'two_way_friendships');

        $aFriendReverse = $this->database()->select('f.*')
            ->from($this->_sTable, 'f')
            ->where('f.friend_user_id =' . Phpfox::getUserId() . ' AND f.user_id = ' . $aFriend['friend_user_id'])
            ->execute('getSlaveRow');

        // Remove friends
        $this->database()->delete($this->_sTable, "user_id = " . Phpfox::getUserId() . " AND friend_user_id = " . $aFriend['friend_user_id']);
        if ($friendshipDirection != 'one_way_friendships') {
            $this->database()->delete($this->_sTable, "user_id = " . $aFriend['friend_user_id'] . " AND friend_user_id = " . Phpfox::getUserId());
        }

        if (Phpfox::getParam('friend.cache_rand_list_of_friends') > 0) {
            $sCacheId = $this->cache()->set(['friend_rand_6', Phpfox::getUserId()]);
            $this->cache()->remove($sCacheId);
            if ($friendshipDirection != 'one_way_friendships') {
                $sCacheId = $this->cache()->set(['friend_rand_6', $aFriend['friend_user_id']]);
                $this->cache()->remove($sCacheId);
            }
        }

        if (Phpfox::isModule('feed')) {
            Phpfox::getService('feed.process')->delete('friend', $aFriend['friend_id']);
            (!empty($aFriendReverse['friend_id']) ? Phpfox::getService('feed.process')->delete('friend', $aFriendReverse['friend_id']) : null);
        }
        // Update friend count
        $this->_updateFriendCount(Phpfox::getUserId(), $aFriend['friend_user_id']);

        if (!empty($aFriend['list_id'])) {
            $this->database()->updateCounter('friend_list', 'used', 'list_id', $aFriend['list_id'], true);
        }

        if (!empty($aFriendReverse['list_id'])) {
            $this->database()->updateCounter('friend_list', 'used', 'list_id', $aFriendReverse['list_id'], true);
        }

        if ($sPlugin = Phpfox_Plugin::get('friend.service_process_delete__1')) {
            eval($sPlugin);
        }
        return true;
    }


    public function deleteFromConnection($iUserId, $iFriendId)
    {
        $aFriend = $this->database()->select('friend_id')
            ->from(Phpfox::getT('friend'))
            ->where('friend_user_id =' . $iUserId . ' AND user_id = ' . $iFriendId)
            ->execute('getSlaveRow');

        if (!isset($aFriend['friend_id'])) {
            return false;
        }

        return $this->delete($aFriend['friend_id']);
    }

    public function updateFriendCount($iUserId, $iFriendId)
    {
        return $this->_updateFriendCount($iUserId, $iFriendId);
    }

    private function _updateFriendCount($iUserId, $iFriendId)
    {
        $sExtra = '';

        if ($sPlugin = Phpfox_Plugin::get('friend.service_process__updatefriendcount')) {
            eval($sPlugin);
        }

        $iTotal = $this->database()->select('COUNT(f.user_id)')
            ->from($this->_sTable, 'f')
            ->where('f.is_page = 0 AND f.user_id = ' . (int)$iUserId . $sExtra)
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = f.friend_user_id AND u.status_id = 0 AND u.view_id = 0')
            ->execute('getSlaveField');
        $this->database()->update(Phpfox::getT('user_field'), ['total_friend' => $iTotal], 'user_id = ' . (int)$iUserId);

        $iTotal = $this->database()->select('COUNT(f.user_id)')
            ->from($this->_sTable, 'f')
            ->where('f.is_page = 0 AND f.user_id = ' . (int)$iFriendId . $sExtra)
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = f.friend_user_id AND u.status_id = 0 AND u.view_id = 0')
            ->execute('getSlaveField');
        $this->database()->update(Phpfox::getT('user_field'), ['total_friend' => $iTotal], 'user_id = ' . (int)$iFriendId);

        //remove friends list cache
        $this->cache()->remove('friend_build_cache_data_' . $iUserId);
        $this->cache()->remove('friend_build_cache_data_' . $iFriendId);

        return null;
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
        if ($sPlugin = Phpfox_Plugin::get('friend.service_process__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}