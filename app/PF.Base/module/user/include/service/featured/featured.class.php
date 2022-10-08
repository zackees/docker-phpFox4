<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Service_Featured_Featured
 */
class User_Service_Featured_Featured extends Phpfox_Service
{
    const FEATURED_MEMBERS_LIMIT = 6;

    public function __construct()
    {
        $this->_sTable = Phpfox::getT('user_featured');
    }

    /**
     * Gets the featured members according to Phpfox::getParam('user.how_many_featured_members').
     * Uses cache to save a query (stores a cache if none found)
     *
     * @param int|null $iLimit
     * @param int $iCacheTime
     *
     * @return array( array of users, int total featured users )
     */
    public function get($iLimit = null, $iCacheTime = 5)
    {
        if ($sPlugin = Phpfox_Plugin::get('user.service_featured_get_1')) {
            eval($sPlugin);
            if (isset($mPluginReturn)) {
                return $mPluginReturn;
            }
        }

        $sCacheName = 'featured_users';
        $sJoinCond = 'uf.user_id = u.user_id';
        if (Phpfox::isUser()) {
            $sCacheName .= '_' . Phpfox::getUserId();
            $aBlockedUserIds = Phpfox::getService('user.block')->get(null, true);
            if (!empty($aBlockedUserIds)) {
                $sJoinCond .= ' AND u.user_id NOT IN (' . implode(',', $aBlockedUserIds) . ')';
            }
        }

        $sCacheId = $this->cache()->set($sCacheName);
        // the random will be done with php logic
        if (false === ($aUsers = $this->cache()->get($sCacheId, $iCacheTime))) {
            $aUsers = $this->database()->select(Phpfox::getUserField() . ', uf.ordering, usf.total_friend, usf.city_location')
                ->from(Phpfox::getT('user'), 'u')
                ->leftJoin(':user_field', 'usf', 'usf.user_id = u.user_id')
                ->join($this->_sTable, 'uf', $sJoinCond)
                ->order('ordering DESC')
                ->limit(100)
                ->execute('getSlaveRows');

            if ($iCacheTime) {
                $this->cache()->save($sCacheId, $aUsers);
                Phpfox::getLib('cache')->group(  'user', $sCacheId);
            }
        }

        if (!is_array($aUsers)) {
            return array(array(), 0);
        }

        $aOut = array();
        shuffle($aUsers);

        $iCount = count($aUsers); // using count instead of $this->database()->limit to measure the real value
        if ($iLimit === null) {
            $iLimit = self::FEATURED_MEMBERS_LIMIT;
        }
        for ($i = 0; $i <= $iLimit; $i++) {
            if (!isset($aUsers[$iCount - $i])) {
                continue;
            } // availability check
            $aOut[] = $aUsers[$iCount - $i];
        }

        return array($aOut, count($aUsers));
    }


    public function getRecentActiveUsers()
    {
        $sCacheName = 'recent_active_users';
        $aWhere = [
            'u.profile_page_id' => 0,
            'u.view_id' => 0,
            'u.is_invisible' => 0,
            'u.status_id' => 0,
            'AND u.user_group_id NOT IN (' . implode(',', [(int)Phpfox::getParam('core.banned_user_group_id')]) . ')',
        ];
        if (Phpfox::isUser()) {
            $sCacheName .= '_' . Phpfox::getUserId();
            $aBlockedUserIds = Phpfox::getService('user.block')->get(null, true);
            if (!empty($aBlockedUserIds)) {
                $aWhere[] = ' AND u.user_id NOT IN (' . implode(',', $aBlockedUserIds) . ')';
            }
        }
        $cache = $this->cache()->set($sCacheName);
        // We should cached it only 2 minutes. This block always changes
        $users = $this->cache()->get($cache, 2);
        if ($users === false) {
            $users = $this->database()
                ->select('uf.total_friend, uf.country_child_id, uf.city_location, ' . Phpfox::getUserField())
                ->from(Phpfox::getT('user'), 'u')
                ->join(Phpfox::getT('user_field'), 'uf', 'u.user_id = uf.user_id')
                ->where($aWhere)
                ->limit(12)
                ->order('u.last_activity DESC')
                ->executeRows();

            foreach ($users as &$user) {
                $user['is_friend_request'] = (Phpfox::isModule('friend') && Phpfox::getService('friend.request')->isRequested($user['user_id'], Phpfox::getUserId(), false, true)) ? 3 : 0;
            }

            $this->cache()->save($cache, $users);
            Phpfox::getLib('cache')->group(  'user', $cache);
        }

        if (!empty($users)) {
            $featuredUsers = db()->select('user_id')
                ->from(':user_featured')
                ->where([
                    'user_id' => ['in' => implode(',', array_column($users, 'user_id'))]
                ])->executeRows();
            if (!empty($featuredUsers = array_column($featuredUsers, 'user_id'))) {
                foreach ($users as $key => $value) {
                    if (in_array($value['user_id'], $featuredUsers)) {
                        $users[$key]['is_featured'] = true;
                    }
                }
            }
        }

        return $users;
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
        if ($sPlugin = Phpfox_Plugin::get('user.service_featured__call')) {
            eval($sPlugin);

            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}
