<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 *
 *
 * @copyright        [PHPFOX_COPYRIGHT]
 * @author           phpFox LLC
 * @package          Module_Friend
 * @version          $Id: friend.class.php 7274 2014-04-21 13:25:12Z Fern $
 */
class Friend_Service_Friend extends Phpfox_Service
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('friend');
    }

    /**
     * Filter friends for disallowing taging them in Feed
     * @param $friendUserIds
     * @return array
     */
    public function filterDisallowedTaggingFriends($friendUserIds)
    {
        if (empty($friendUserIds)) {
            return [];
        }

        $privacyUserIds = $this->database()->select('user_id')
            ->from(Phpfox::getT('user_privacy'))
            ->where('`user_id` IN (' . implode(',', $friendUserIds) . ' ) AND `user_privacy` = \'user.can_i_be_tagged\' AND `user_value` = 4')
            ->execute('getSlaveRows');

        return !empty($privacyUserIds) ? array_column($privacyUserIds, 'user_id') : [];
    }

    public function get($aCond, $sSort = 'friend.time_stamp DESC', $iPage = '', $sLimit = '', $bCount = true, $bAddDetails = false, $bIsOnline = false, $iUserId = null, $bIncludeList = false, $iListId = 0)
    {
        $bSuperCache = false;
        $sSuperCacheId = '';
        // Not all calls to this function can be cached in the same way
        if ((Phpfox::getParam('friend.cache_rand_list_of_friends') > 0) &&
            (is_string($aCond) && strpos($aCond, 'friend.is_page = 0 AND friend.user_id = ') !== false) &&
            ($sSort == 'friend.is_top_friend DESC, friend.ordering ASC, RAND()') &&
            ($iPage == 0)
            && ($bIsOnline === false)
        ) {
            $iUserId = str_replace('friend.is_page = 0 AND friend.user_id = ', '', $aCond);
            // the folder name has to be fixed so we can clear it from the add and delete functions
            $sCacheId = $this->cache()->set(['friend_rand_6', $iUserId]);

            $sSuperCacheId = $sCacheId;

            if (false !== ($aRows = $this->cache()->get($sCacheId, Phpfox::getParam('friend.cache_rand_list_of_friends')))) {
                if (is_bool($aRows)) {
                    return [];
                }

                return $aRows;
            }
            $bSuperCache = true;
        }

        $bIsListView = ((Phpfox_Request::instance()->get('view') == 'list' || (defined('PHPFOX_IS_USER_PROFILE') && Phpfox_Request::instance()->getInt('list'))) ? true : false);
        $iCnt = ($bCount ? 0 : 1);
        $aRows = [];

        if ($sPlugin = Phpfox_Plugin::get('friend.service_friend_get')) {
            eval($sPlugin);
        }

        if ($bIsOnline) {
            if (is_string($aCond)) {
                $aCond .= ' AND u.is_invisible = 0';
            } else if (is_array($aCond)) {
                $aCond[] = ' AND u.is_invisible = 0';
            }
        }

        if ($bCount === true) {
            if ($bIsOnline === true) {
                $this->database()->join(Phpfox::getT('log_session'), 'ls', 'ls.user_id = friend.friend_user_id AND ls.last_activity > \'' . Phpfox::getService('log.session')->getActiveTime() . '\' AND ls.im_hide = 0');
            }

            if ($iUserId !== null && !empty($iUserId)) {
                $this->database()->innerJoin('(SELECT friend_user_id FROM ' . Phpfox::getT('friend') . ' WHERE is_page = 0 AND user_id = ' . $iUserId . ')', 'sf', 'sf.friend_user_id = friend.friend_user_id');
            }

            if ($bIsListView) {
                $this->database()->join(Phpfox::getT('friend_list_data'), 'fld', 'fld.friend_user_id = friend.friend_user_id');
                $aCond[] = 'AND friend.user_id = ' . (int)Phpfox::getUserId() . ' AND friend.friend_user_id = fld.friend_user_id';
            }

            if ((int)$iListId > 0) {
                $this->database()->innerJoin(Phpfox::getT('friend_list_data'), 'fld', 'fld.list_id = ' . (int)$iListId . ' AND fld.friend_user_id = friend.friend_user_id');
            }

            $iCnt = $this->database()->select('COUNT(DISTINCT u.user_id)')
                ->from($this->_sTable, 'friend')
                ->join(Phpfox::getT('user'), 'u', 'u.user_id = friend.friend_user_id AND u.status_id = 0 AND u.view_id = 0')
                ->where($aCond)
                ->execute('getSlaveField');
        }

        if ($iCnt) {
            if ($bAddDetails === true) {
                $this->database()->select('u.status, u.user_id, u.birthday, u.gender, u.country_iso AS location, ');
            }

            if ($bIsOnline === true) {
                $this->database()->select('ls.last_activity, ')->join(Phpfox::getT('log_session'), 'ls', 'ls.user_id = friend.friend_user_id AND ls.last_activity > \'' . Phpfox::getService('log.session')->getActiveTime() . '\' AND ls.im_hide = 0');
            }

            if ($iUserId !== null && !empty($iUserId)) {
                $this->database()->innerJoin('(SELECT friend_user_id FROM ' . Phpfox::getT('friend') . ' WHERE is_page = 0 AND user_id = ' . $iUserId . ')', 'sf', 'sf.friend_user_id = friend.friend_user_id');
            }

            if ($bIsListView) {
                $this->database()->join(Phpfox::getT('friend_list_data'), 'fld', 'fld.friend_user_id = friend.friend_user_id');
                $aCond[] = 'AND friend.user_id = ' . (int)Phpfox::getUserId() . ' AND friend.friend_user_id = fld.friend_user_id';
            }

            if ((int)$iListId > 0) {
                $this->database()->innerJoin(Phpfox::getT('friend_list_data'), 'fld', 'fld.list_id = ' . (int)$iListId . ' AND fld.friend_user_id = friend.friend_user_id');
            }
            $aRows = $this->database()->select('uf.total_friend, uf.dob_setting, friend.friend_id, friend.friend_user_id, friend.is_top_friend, friend.time_stamp, ' . Phpfox::getUserField())
                ->from($this->_sTable, 'friend')
                ->join(Phpfox::getT('user'), 'u', 'u.user_id = friend.friend_user_id AND u.status_id = 0 AND u.view_id = 0')
                ->join(Phpfox::getT('user_field'), 'uf', 'u.user_id = uf.user_id')
                ->where($aCond)
                ->group('u.user_id', true)
                ->order($sSort)
                ->limit($iPage, $sLimit, $iCnt)
                ->execute('getSlaveRows');

            if ($bAddDetails === true) {
                $oUser = Phpfox::getService('user');
                $oCoreCountry = Phpfox::getService('core.country');
                foreach ($aRows as $iKey => $aRow) {
                    $aBirthDay = Phpfox::getService('user')->getAgeArray($aRow['birthday']);

                    $aRows[$iKey]['month'] = Phpfox::getLib('date')->getMonth($aBirthDay['month']);
                    $aRows[$iKey]['day'] = $aBirthDay['day'];
                    $aRows[$iKey]['year'] = $aBirthDay['year'];
                    $aRows[$iKey]['gender_phrase'] = $oUser->gender($aRow['gender']);
                    $aRows[$iKey]['birthday'] = $oUser->age($aRow['birthday']);
                    $aRows[$iKey]['location'] = $oCoreCountry->getCountry($aRow['location']);

                    (($sPlugin = Phpfox_Plugin::get('friend.service_friend_get_2')) ? eval($sPlugin) : false);
                }
            }

            if ($bIncludeList) {
                foreach ($aRows as $iKey => $aRow) {
                    $aRows[$iKey]['lists'] = Phpfox::getService('friend.list')->getListForUser($aRow['friend_user_id']);
                }
            }
        }

        if ($bCount === false) {
            if ($bSuperCache == true) {
                $this->cache()->save($sSuperCacheId, $aRows);
            }
            return $aRows;
        }

        return [$iCnt, $aRows];
    }

    /**
     * Get friends from Cache
     *
     * @param bool $mAllowCustom
     * @param bool $sUserSearch
     * @param bool $bIncludeCurrentUser
     * @param null $userId
     *
     * @param bool $forMessage
     * @return array|bool|int|string
     */
    public function getFromCache($mAllowCustom = false, $sUserSearch = false, $bIncludeCurrentUser = false, $userId = null, $forMessage = false)
    {
        if (Phpfox::getUserBy('profile_page_id')) {
            //Login as paged can't use this feature
            return [];
        }

        if (!Phpfox::isUser()) {
            return [];
        }

        if ($userId == null) {
            $userId = Phpfox::getUserId();
        }

        $pareInput = Phpfox::getLib('parse.input');
        $sCacheId = Phpfox::getLib('cache')->set('friend_build_cache_data_' . $userId);
        $aRows = Phpfox::getLib('cache')->getLocalFirst($sCacheId);
        $bLiveSearch = $sUserSearch != false;

        if ($mAllowCustom || $bLiveSearch || $aRows === false) {
            (($sPlugin = Phpfox_Plugin::get('friend.service_getfromcachequery')) ? eval($sPlugin) : false);
            if (!isset($bForceQuery)) {
                if ($bLiveSearch) {
                    $sUserSearch = $pareInput->clean($sUserSearch);
                    $select = $this->database()->select('' . Phpfox::getUserField())
                        ->from(Phpfox::getT('user'), 'u')
                        ->join($this->_sTable, 'f', 'u.user_id = f.friend_user_id AND f.user_id=' . $userId)
                        ->where('u.full_name LIKE "%' . $sUserSearch . '%" AND u.profile_page_id = 0');
                } else {
                    $select = $this->database()->select('f.*, ' . Phpfox::getUserField())
                        ->from($this->_sTable, 'f')
                        ->join(Phpfox::getT('user'), 'u', 'u.user_id = f.friend_user_id')
                        ->where(($mAllowCustom ? '' : 'f.is_page = 0 AND') . ' f.user_id = ' . $userId);
                }
                if (Phpfox::getParam('friend.friend_cache_limit')) {
                    $select->limit(Phpfox::getParam('friend.friend_cache_limit'));
                }
                $aRows = $select->order('u.last_activity DESC')->execute('getSlaveRows');
            }

            if (!$mAllowCustom && !$bLiveSearch) {
                Phpfox::getLib('cache')->saveBoth($sCacheId, $aRows);
            }
        }

        foreach ($aRows as $iKey => $aRow) {
            if (!$this->_checkPrivacy($aRow, $userId, $forMessage)) {
                unset($aRows[$iKey]);
                continue;
            }

            $aRows[$iKey] = $this->_parseUserInfo($aRow);
        }

        if ($bIncludeCurrentUser && !in_array($userId, array_column($aRows, 'user_id'))) {
            $aCurrentUser = db()->select(Phpfox::getUserField())
                ->from(Phpfox::getT('user'), 'u')
                ->where('u.user_id = ' . $userId . ' AND u.profile_page_id = 0')
                ->execute('getSlaveRow');

            $aRows[] = $this->_parseUserInfo($aCurrentUser);
        }

        return array_values($aRows);
    }

    /**
     * @param $aRow
     * @param $userId
     *
     * @param bool $forMessage
     * @return bool
     */
    private function _checkPrivacy($aRow, $userId, $forMessage = false)
    {
        $valid = true;
        if ($userId == $aRow['user_id']) {
            $valid = false;
        }
        if ($forMessage && $valid && Phpfox::isAppActive('Core_Messages')
            && Phpfox::getParam('mail.disallow_select_of_recipients')
            && !Phpfox::getService('mail')->canMessageUser($aRow['user_id'])) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * @param array $aUser
     *
     * @return array
     */
    private function _parseUserInfo(array $aUser)
    {
        $pareOutput = Phpfox::getLib('parse.output');
        $aUser['full_name'] = html_entity_decode($pareOutput->split($pareOutput->clean($aUser['full_name']), 20), null, 'UTF-8');
        $aUser['user_profile'] = ($aUser['profile_page_id'] ? Phpfox::getService('pages')->getUrl($aUser['profile_page_id'],
            '', $aUser['user_name']) : Phpfox_Url::instance()->makeUrl($aUser['user_name']));
        $aUser['is_page'] = ($aUser['profile_page_id'] ? true : false);
        $aUser['user_image'] = Phpfox::getLib('image.helper')->display([
            'user'       => $aUser,
            'suffix'     => '_50_square',
            'max_height' => 32,
            'max_width'  => 32,
            'no_link'    => true,
            'return_url' => true
        ]);
        $aUser['user_image_actual'] = Phpfox::getLib('image.helper')->display([
            'user'       => $aUser,
            'suffix'     => '_50_square',
            'max_height' => 32,
            'max_width'  => 32,
            'no_link'    => true
        ]);
        $aUser['has_image'] = isset($aUser['user_image']) && $aUser['user_image'];
        return $aUser;
    }

    /**
     * This function returns information about $iUser's friends' upcoming birthdays
     *
     * @param Int $iUser
     *
     * @return array
     */
    public function getBirthdays($iUser)
    {
        $iUser = (int)$iUser;

        // Calculate how many days in advance to check and
        $iDaysInAdvance = Phpfox::getParam('friend.days_to_check_for_birthday') >= 0 ? Phpfox::getParam('friend.days_to_check_for_birthday') : 0;
        $iThisMonth = date('m', Phpfox::getTime());
        $iToday = date('d', Phpfox::getTime());

        $iThisYear = date('Y', Phpfox::getTime());
        $iLastDayOfMonth = Phpfox::getLib('date')->lastDayOfMonth($iThisMonth);

        $sMonthUntil = $iThisMonth;
        $sDayUntil = $iToday;
        while ($iDaysInAdvance > 0) {
            if ($sDayUntil > $iLastDayOfMonth) {
                if ($sMonthUntil < 12) {
                    $sMonthUntil++;
                } else {
                    $sMonthUntil = 1;
                    $iLastDayOfMonth = Phpfox::getLib('date')->lastDayOfMonth($sMonthUntil, $iThisYear);
                }
                $sDayUntil = 0;
            }
            $iDaysInAdvance--;
            $sDayUntil++;
        }
        $sMonthUntil = substr((string)$sMonthUntil, 0, 1) != '0' ? ($sMonthUntil < 10) ? '0' . $sMonthUntil : $sMonthUntil : $sMonthUntil;
        $sDayUntil = ($sDayUntil < 10) ? '0' . $sDayUntil : $sDayUntil;
        if ($sMonthUntil < $iThisMonth) // its next year
        {
            $sBirthdays = '\'' . $iThisMonth . '' . $iToday . '\' <= uf.birthday_range OR \'' . $sMonthUntil . $sDayUntil . '\' >= uf.birthday_range';
        } else {
            $sBirthdays = '\'' . $iThisMonth . '' . $iToday . '\' <= uf.birthday_range AND \'' . $sMonthUntil . $sDayUntil . '\' >= uf.birthday_range';
        }

        // cache this query
        $sCacheId = $this->cache()->set('friend_birthday_' . $iUser);
        if (false === ($aBirthdays = $this->cache()->get($sCacheId, 5 * 60 * 60))) // cache is in 5 hours
        {
            $aBirthdays = $this->database()->select(Phpfox::getUserField() . ', uf.dob_setting, fb.birthday_user_receiver')
                ->from(Phpfox::getT('friend'), 'f')
                ->join(Phpfox::getT('user'), ' u', 'u.user_id = f.friend_user_id')
                ->join(Phpfox::getT('user_field'), 'uf', 'uf.user_id = u.user_id')
                ->leftJoin(Phpfox::getT('friend_birthday'), 'fb', 'fb.birthday_user_receiver = u.user_id AND fb.time_stamp > ' . (PHPFOX_TIME - 2629743))/* Fixes (SHB-989762) */
                ->where('f.user_id = ' . $iUser . ' AND (' . $sBirthdays . ') AND (uf.dob_setting != 2 AND uf.dob_setting != 3) AND fb.birthday_user_receiver IS NULL')
                ->order('uf.birthday_range ASC')
                ->limit(15)
                ->execute('getSlaveRows');
            $this->cache()->save($sCacheId, $aBirthdays);
            Phpfox::getLib('cache')->group('friend', $sCacheId);
        }
        if (!is_array($aBirthdays)) {
            $aBirthdays = [];
        }

        //Default Dob setting
        switch (Phpfox::getParam('user.default_privacy_brithdate')) {
            case 'month_day':
                $iDefaultDob = 1;
                break;
            case 'show_age':
                $iDefaultDob = 2;
                break;
            case 'hide':
                $iDefaultDob = 3;
                break;
            default:
                $iDefaultDob = 4;
                break;
        }

        foreach ($aBirthdays as $iKey => $aFriend) {
            // add when is their birthday and how old are they going to be
            $iAge = Phpfox::getService('user')->age($aFriend['birthday']);

            if (substr($aFriend['birthday'], 0, 2) . '-' . substr($aFriend['birthday'], 2, 2) == date('m-d', Phpfox::getTime())) {
                $aBirthdays[$iKey]['new_age'] = $iAge;
            } else {
                $aBirthdays[$iKey]['new_age'] = ($iAge + 1);
            }

            if (!isset($aFriend['birthday']) || empty($aFriend['birthday'])) {
                $iDays = -1;
            } else {
                $iDays = Phpfox::getLib('date')->daysToDate($aFriend['birthday'], null, false);
            }
            if (empty($aFriend['dob_setting'])) {
                $aFriend['dob_setting'] = $iDefaultDob;
            }
            if ($iDays < 0 || $aFriend['dob_setting'] == 2 || $aFriend['dob_setting'] == 3) {
                unset($aBirthdays[$iKey]);
                continue;
            } else {
                $aBirthdays[$iKey]['days_left'] = floor($iDays);
            }

            // do we show the age?
            if (($aFriend['dob_setting'] < 3 & $aFriend['dob_setting'] != 1) || ($aFriend['dob_setting'] == 4)) // 0 => age and dob; 1 => age and day/month; 2 => age
            {
                $aBirthdays[$iKey]['show_age'] = true;
            } else {
                $aBirthdays[$iKey]['show_age'] = false;
            }
            // fail safe
            $aBirthdays[$iKey]['birthdate'] = '';
            // Format the birthdate according to the profile
            $aBirthDay = Phpfox::getService('user')->getAgeArray($aFriend['birthday']);
            if ($aFriend['dob_setting'] == 4)// just copy the arbitrary format on the browse.html
            {
                unset($aBirthDay['year']);
            } else if ($aFriend['dob_setting'] == 0) {
                $aBirthdays[$iKey]['birthdate'] = Phpfox::getLib('date')->getMonth($aBirthDay['month']) . ' ' . $aBirthDay['day'] . ', ' . $aBirthDay['year'];
            } else if ($aFriend['dob_setting'] == 1) {
                $aBirthdays[$iKey]['birthdate'] = Phpfox::getLib('date')->getMonth($aBirthDay['month']) . ' ' . $aBirthDay['day'];
            }
        }

        $aReturnBirthday = [];
        foreach ($aBirthdays as $iBirthKey => $aBirthData) {
            $aReturnBirthday[$aBirthData['days_left']][] = $aBirthData;
        }

        ksort($aReturnBirthday);

        return $aReturnBirthday;
    }

    /**
     * This is a very fail safe function, if there is an id it gets the message but if its not set or equals zero
     * it then can get all the messages since $iTime
     *
     * @param int $iUser user id
     * @param int @iId Message id, used to fetch only one message
     * @param int $iTime moment since we should fetch records onwards
     *
     * @return array
     */
    public function getBirthdayMessages($iUser, $iId = 0, $iTime = 0)
    {
        $aWhere = ['fb.status_id = 1 AND fb.birthday_user_receiver = ' . (int)$iUser];
        if (isset($iId) && is_int($iId) && $iId > 0) $aWhere[] = 'AND birthday_id = ' . (int)$iId;
        else if ($iTime > 0) $aWhere[] = 'AND fb.time_stamp >= ' . (int)$iTime;

        return $this->database()->select('fb.birthday_message, egift.*, ' . Phpfox::getUserField())
            ->from(Phpfox::getT('friend_birthday'), 'fb')
            ->leftJoin(Phpfox::getT('user'), 'u', 'u.user_id = fb.birthday_user_sender')
            ->leftJoin(Phpfox::getT('egift'), 'egift', 'egift.egift_id = fb.egift_id')
            ->where($aWhere)
            ->execute('getSlaveRows');
    }

    /**
     * Checks if userA is friends with userB
     *
     * Here we are caching all the friends from $iUserId
     *
     * @param int     $iUserId
     * @param int     $iFriendId
     * @param boolean $bRedirect
     * @param boolean $noCache
     *
     * @return boolean
     */
    public function isFriend($iUserId, $iFriendId, $bRedirect = false, $noCache = false)
    {
        static $aCache = [];

        if (isset($aCache[$iUserId][$iFriendId]) && !$noCache) {
            if (!$aCache[$iUserId][$iFriendId] && $bRedirect) {
                Phpfox_Url::instance()->send('friend', 'request');
            }

            return $aCache[$iUserId][$iFriendId];
        }

        if ($iFriendId === $iUserId) {
            return true;
        }

        $iCnt = $this->database()->select('/* friend.is_friend */COUNT(*)')
            ->from($this->_sTable)
            ->where('user_id = ' . (int)$iUserId . ' AND friend_user_id = ' . (int)$iFriendId)
            ->execute('getSlaveField');

        if ($iCnt) {
            $aCache[$iUserId][$iFriendId] = true;

            return true;
        }

        if ($bRedirect) {
            Phpfox_Url::instance()->send('friend', 'request');
        }

        $aCache[$iUserId][$iFriendId] = false;

        return false;
    }

    public function getTop($iUserId, $iLimit = null)
    {
        $aFriends = $this->database()->select('f.friend_id, ' . Phpfox::getUserField())
            ->from($this->_sTable, 'f')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = f.friend_user_Id')
            ->where('f.user_id = ' . (int)$iUserId . ' AND f.is_top_friend = 1')
            ->order('f.ordering ASC, f.time_stamp DESC')
            ->limit($iLimit)
            ->execute('getSlaveRows');

        return $aFriends;
    }

    public function getMutualFriends($iUserId, $iLimit = 7, $bNoCount = false, $iCurrentUserId = null)
    {
        if (empty($iCurrentUserId)) {
            $iCurrentUserId = Phpfox::getUserId();
        }

        static $aCache = [];
        $iUserId = (int)$iUserId;
        if (isset($aCache[$iUserId . '_' . $iCurrentUserId . '_' . $iLimit])) {
            return $aCache[$iUserId . '_' . $iCurrentUserId . '_' . $iLimit];
        }

        $sExtra1 = '';
        $sExtra2 = '';

        if ($sPlugin = Phpfox_Plugin::get('friend.service_friend_getmutualfriends')) {
            eval($sPlugin);
        }

        $iCnt = 0;
        if ($bNoCount == false) {
            $iCnt = $this->database()->select('count(f.user_id)')
                ->from(Phpfox::getT('friend'), 'f')
                ->join(Phpfox::getT('friend'), 'sf', 'sf.friend_user_id = f.friend_user_id AND sf.user_id = ' . (int)$iUserId . $sExtra1)
                ->join(Phpfox::getT('user'), 'u', 'u.user_id = f.friend_user_id')
                ->where('f.is_page = 0 AND f.user_id = ' . $iCurrentUserId . $sExtra2)
                ->group('f.friend_user_id')
                ->execute('getSlaveRows');
            $iCnt = count($iCnt);

        }
        $aRows = $this->database()->select('uf.total_friend, ' . Phpfox::getUserField())
            ->from(Phpfox::getT('friend'), 'f')
            ->join(Phpfox::getT('friend'), 'sf', 'sf.friend_user_id = f.friend_user_id AND sf.user_id = ' . (int)$iUserId . $sExtra1)
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = f.friend_user_id')
            ->join(Phpfox::getT('user_field'), 'uf', 'u.user_id = uf.user_id')
            ->where('f.is_page = 0 AND f.user_id = ' . $iCurrentUserId . $sExtra2)
            ->group('f.friend_user_id', true)
            ->order('f.time_stamp DESC')
            ->limit($iLimit)
            ->execute('getSlaveRows');

        $aCache[$iUserId . '_' . $iCurrentUserId . '_' . $iLimit] = [$iCnt, $aRows];

        return [$iCnt, $aRows];
    }

    public function isFriendOfFriend($iUserId, $iCurrentUserId = null)
    {
        empty($iCurrentUserId) && $iCurrentUserId = Phpfox::getUserId();

        static $aCache = [];

        if (isset($aCache[$iUserId . '_' . $iCurrentUserId])) {
            return $aCache[$iUserId . '_' . $iCurrentUserId];
        }

        list($iCnt,) = $this->getMutualFriends($iUserId, 7, false, $iCurrentUserId);

        $bReturn = ($iCnt ? true : false);

        $aCache[$iUserId . '_' . $iCurrentUserId] = $bReturn;

        return $bReturn;
    }

    /**
     * Checks if we already sent a user a birthday notification.
     *
     * @param int $iUserId   User ID of the sender
     * @param int $iFriendId User ID of the friend to check
     *
     * @return bool TRUE for sent, FALSE for not sent
     */
    public function isBirthdaySent($iUserId, $iFriendId)
    {
        return ((int)$this->database()->select('COUNT(*)')
            ->from(Phpfox::getT('friend_birthday'))
            ->where('birthday_user_sender = ' . (int)$iUserId . ' AND birthday_user_receiver = ' . (int)$iFriendId)
            ->execute('getSlaveField') ? true : false);
    }

    public function queryJoin($bNoQueryFriend)
    {
        $sView = $this->request()->get('view', '');
        if ($sView == 'friend'
            || ($bNoQueryFriend === false && (Phpfox::getParam('core.friends_only_community') && $sView == ''))) {
            return true;
        }

        return false;
    }

    public function buildMenu()
    {
        // Add a hook with return here
        $aFilterMenu = [
            _p('all_friends')       => '',
            _p('incoming_requests') => 'friend.accept',
            _p('pending_requests')  => 'friend.pending'
        ];

        $aFilterMenu[] = true;

        $aLists = Phpfox::getService('friend.list')->get();

        if (count($aLists)) {
            foreach ($aLists as $aList) {
                $aList['name'] = Phpfox::getLib('parse.output')->clean($aList['name']);
                if (is_numeric($aList['name'])) {
                    $aList['name'] = 'phpfox_numeric_friend_list_' . $aList['name'];
                }
                $aFilterMenu[$aList['name']] = 'friend.view_list.id_' . $aList['list_id'];
            }
        }

        Phpfox_Template::instance()->buildSectionMenu('friend', $aFilterMenu);
    }

    /*
        @param $aFriends array|string if string its meant for an IN select
    */
    public function getFriendsOfFriends($aFriends = [])
    {
        if (!Phpfox::isUser()) {
            return [];
        }

        if (is_array($aFriends) && !empty($aFriends)) {
            $aMyFriends = $aFriends;
        } else if (is_string($aFriends) && !empty($aFriends)) {
            $sIn = $aFriends;
        } else {
            $aMyFriends = $this->database()->select('friend_user_id')
                ->from(Phpfox::getT('friend'))
                ->where('user_id = ' . Phpfox::getUserId())
                ->group('friend_user_id')
                ->execute('getSlaveRows');
        }

        if (isset($aMyFriends)) {
            $sIn = '(';
            foreach ($aMyFriends as $aFriend) {
                $sIn .= $aFriend['friend_user_id'] . ',';
            }
            $sIn = rtrim($sIn, ',') . ')';
        }

        $aOfFriends = $this->database()->select('friend_user_id')
            ->from(Phpfox::getT('friend'))
            ->where('user_id IN ' . $sIn)
            ->group('friend_user_id')
            ->execute('getSlaveRows');

        $aFriendsOfFriends = [];
        foreach ($aOfFriends as $aUser) {
            $aFriendsOfFriends[] = $aUser['friend_user_id'];
        }
        return $aFriendsOfFriends;
    }

    /*
     * Get UserName from userId
     */
    public function getUserName($iUserId)
    {
        $sUserName = $this->database()->select('user_name')
            ->from(Phpfox::getT('user'))
            ->where('user_id=' . (int)$iUserId)
            ->execute('getSlaveField');
        return $sUserName;
    }

    /**
     * If a call is made to an unknown method attempt to connect
     * it to a specific plug-in with the same name thus allowing
     * plug-in developers the ability to extend classes.
     *
     * @param string $sMethod    is the name of the method
     * @param array  $aArguments is the array of arguments of being passed
     */
    public function __call($sMethod, $aArguments)
    {
        /**
         * Check if such a plug-in exists and if it does call it.
         */
        if ($sPlugin = Phpfox_Plugin::get('friend.service_friend___call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}