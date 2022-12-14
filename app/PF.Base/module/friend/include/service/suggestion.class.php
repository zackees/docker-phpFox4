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
 * @package        Phpfox_Service
 * @version        $Id: suggestion.class.php 3327 2011-10-20 09:26:10Z phpFox LLC $
 */
class Friend_Service_Suggestion extends Phpfox_Service
{
    private $_aUsers = [];

    public function getSingle($iLimit = 1)
    {
        $this->_build();
        if (is_array($this->_aUsers) && count($this->_aUsers)) {
            shuffle($this->_aUsers);
            $aUsers = array_slice($this->_aUsers, 0, $iLimit);
            $aSuggestions = [];
            foreach ($aUsers as $aUser) {
                $aUserItem = Phpfox::getService('user')->getUser($aUser['friend_user_id']);
                $aUserItem['total_friend'] = db()->select('total_friend')->from(':user_field')->where(['user_id' => $aUser['friend_user_id']])->executeField();
                if (isset($aUserItem['user_id'])) {
                    $aSuggestions[] = $aUserItem;
                }

            }
            return $aSuggestions;
        }

        return false;
    }

    private function _build()
    {
        $sCacheId = $this->cache()->set('friend_suggestion_' . Phpfox::getUserId());

        if (false === ($this->_aUsers = $this->cache()->get($sCacheId, Phpfox::getParam('friend.friend_suggestion_timeout')))) {
            $aCache = [];
            $aIgnoredUserGroupIds = [(int)Phpfox::getParam('core.banned_user_group_id')];

            // Lets get some of the users friends
            $aFriends = $this->database()->select('f.friend_user_id')
                ->from(Phpfox::getT('friend'), 'f')
                ->join(':user', 'u', 'u.user_id = f.friend_user_id AND u.user_group_id NOT IN (' . implode(',', $aIgnoredUserGroupIds) . ')')
                ->where('f.user_id = ' . (int)Phpfox::getUserId())
                ->limit(Phpfox::getParam('friend.friend_suggestion_search_total'))
                ->order('RAND()')
                ->execute('getSlaveRows');

            $iCnt = 0;
            $sExtraCond = '';
            $aBlockedUserIds = Phpfox::getService('user.block')->get(null, true);
            if (!empty($aBlockedUserIds)) {
                $sExtraCond .= ' AND u.user_id NOT IN (' . implode(',', $aBlockedUserIds) . ')';
            }

            foreach ($aFriends as $aFriend) {
                // Lets find some friends of this persons list of friends
                $aSubFriends = $this->database()->select('f.friend_user_id, u.country_iso, uf.country_child_id, uf.city_location')
                    ->from(Phpfox::getT('friend'), 'f')
                    ->join(Phpfox::getT('user'), 'u', 'u.user_id = f.friend_user_id')
                    ->join(Phpfox::getT('user_field'), 'uf', 'uf.user_id = f.friend_user_id')
                    ->leftJoin(Phpfox::getT('friend_hide'), 'fh', 'fh.user_id = ' . Phpfox::getUserId() . ' AND fh.friend_user_id = f.friend_user_id')
                    ->leftJoin(Phpfox::getT('friend_request'), 'fr', 'fr.user_id = f.friend_user_id AND fr.friend_user_id = ' . Phpfox::getUserId())
                    ->where('f.user_id = ' . (int)$aFriend['friend_user_id'] . ' AND u.user_group_id NOT IN (' . implode(',', $aIgnoredUserGroupIds) . ') AND ' . $this->database()->isNull('fh.hide_id') . ' AND ' . $this->database()->isNull('fr.request_id') . ' AND u.profile_page_id = 0 AND u.is_invisible = 0' . $sExtraCond)
                    ->limit(Phpfox::getParam('friend.friend_suggestion_search_total'))
                    ->order('RAND()')
                    ->execute('getSlaveRows');

                foreach ($aSubFriends as $aSubFriend) {
                    if ($aSubFriend['friend_user_id'] == Phpfox::getUserId()) {
                        continue;
                    }

                    if (!isset($aCache[$aSubFriend['friend_user_id']])) {
                        $iCnt++;

                        $aCache[$aSubFriend['friend_user_id']] = $aSubFriend;
                    }
                }

                if ($iCnt >= 100) {
                    break;
                }
            }

            unset($aFriends, $aFriend);

            $sQuery = '';
            foreach ($aCache as $iFriendId => $aFriend) {
                $sQuery .= ',' . $iFriendId;
            }
            $sQuery = ltrim($sQuery, ',');

            if (empty($sQuery)) {
                return false;
            }

            $aFriends = $this->database()->select('f.friend_user_id')
                ->from(Phpfox::getT('friend'), 'f')
                ->join(':user', 'u', 'u.user_id = f.friend_user_id AND u.user_group_id NOT IN (' . implode(',', $aIgnoredUserGroupIds) . ')')
                ->where('f.user_id = ' . (int)Phpfox::getUserId() . ' AND f.friend_user_id IN(' . $sQuery . ')')
                ->execute('getSlaveRows');
            foreach ($aFriends as $aFriend) {
                unset($aCache[$aFriend['friend_user_id']]);
            }

            $aCurrentUser = Phpfox::getService('user')->get(Phpfox::getUserId());

            $iCnt = 0;
            $this->_aUsers = [];
            foreach ($aCache as $iKey => $aUser) {
                if (Phpfox::getParam('friend.friend_suggestion_user_based')) {
                    if (!empty($aCurrentUser['country_iso']) && $aCurrentUser['country_iso'] != $aUser['country_iso']) {
                        continue;
                    }

                    if (!empty($aCurrentUser['country_child_id']) && $aCurrentUser['country_child_id'] != $aUser['country_child_id']) {
                        continue;
                    }

                    if (!empty($aCurrentUser['city_location']) && $this->_city($aCurrentUser['city_location']) != $this->_city($aUser['city_location'])) {
                        continue;
                    }
                }

                if ($sPlugin = Phpfox_Plugin::get('friend.service_suggestion__build_search')) {
                    eval($sPlugin);
                }

                $iCnt++;

                if ($iCnt === 22) {
                    break;
                }

                $this->_aUsers[$iCnt] = $aUser;
            }

            $this->cache()->save($sCacheId, $this->_aUsers);
            Phpfox::getLib('cache')->group('friend', $sCacheId);
        }

        return null;
    }

    private function _city($sCity)
    {
        return md5(preg_replace('/\s/m', '', $sCity));
    }

    public function get($bCountOnly = false, $bGetFeatured = false)
    {
        $this->_build();

        if (!is_array($this->_aUsers)) {
            return $bCountOnly ? 0 : [];
        }

        if (!count($this->_aUsers)) {
            return $bCountOnly ? 0 : [];
        }

        $sUsers = '';
        foreach ($this->_aUsers as $aUser) {
            $sUsers .= $aUser['friend_user_id'] . ',';
        }
        $sUsers = rtrim($sUsers, ',');

        if (empty($sUsers)) {
            return $bCountOnly ? 0 : [];
        }

        if ($bCountOnly) {
            return $this->database()->select('COUNT(u.user_id)')
                ->from(Phpfox::getT('user'), 'u')
                ->join(Phpfox::getT('user_field'), 'uf', 'u.user_id = uf.user_id')
                ->where('u.user_id IN(' . $sUsers . ') AND u.is_invisible = 0')
                ->execute('getSlaveField');
        }

        if ($bGetFeatured) {
            db()->select('ufe.user_id AS is_featured, ')
                ->leftJoin(':user_featured', 'ufe', 'ufe.user_id = u.user_id');
        }

        $aUsers = $this->database()->select('uf.total_friend, ' . Phpfox::getUserField())
            ->from(Phpfox::getT('user'), 'u')
            ->join(Phpfox::getT('user_field'), 'uf', 'u.user_id = uf.user_id')
            ->where('u.user_id IN(' . $sUsers . ') AND u.is_invisible = 0')
            ->limit(12)
            ->execute('getSlaveRows');

        foreach ($aUsers as &$aUser) {
            $aUser['is_friend_request'] = Phpfox::getService('friend.request')->isRequested($aUser['user_id'], Phpfox::getUserId(), false, true) ? 3 : 0;
        }

        return $aUsers;
    }

    public function remove($iUserId)
    {
        $this->database()->insert(Phpfox::getT('friend_hide'), array(
                'user_id' => Phpfox::getUserId(),
                'friend_user_id' => (int)$iUserId,
                'time_stamp' => PHPFOX_TIME
            )
        );

        $this->reBuild();

        return true;
    }

    public function reBuild($iUserId = null)
    {
        $this->cache()->remove('friend_suggestion_' . ($iUserId === null ? Phpfox::getUserId() : $iUserId));
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
        if ($sPlugin = Phpfox_Plugin::get('friend.service_suggestion__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}
