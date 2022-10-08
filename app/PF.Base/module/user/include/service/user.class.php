<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Service_User
 */
class User_Service_User extends Phpfox_Service
{
    private $_aUser = [];
    private $_aUserThumbnailSizes = [50, 120, 200];

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('user');
    }

    /**
     * Check if current user has pending profile photo for requiring user photo from user group setting "Users are required to upload a profile image?"
     * @param null $userId
     * @return bool
     */
    public function hasPendingProfilePhoto($userId = null)
    {
        if (!Phpfox::isAppActive('Core_Photos')) {
            return false;
        }

        empty($userId) && $userId = Phpfox::getUserId();

        $profilePhotoPrefix = 'user_profile_photo_pending_';

        $profilePhotoName = db()->select('file_name')
            ->from(':cache')
            ->where([
                'file_name' => ['like' => '%' . $profilePhotoPrefix . '%'],
                'cache_data' => ['like' => '%"user_id":' . $userId . '%'],
            ])
            ->order('cache_id DESC')
            ->executeField(false);

        if (empty($profilePhotoName) || !preg_match('/^' . $profilePhotoPrefix . '([0-9]+)$/', $profilePhotoName, $match)
            || empty($match[1]) || !is_numeric($match[1])) {
            return false;
        }

        $check = db()->select('photo_id')
            ->from(':photo')
            ->where([
                'photo_id' => $match[1],
                'view_id' => 1,
            ])->executeField(false);

        return !!$check;
    }

    public function getData($sSaveData, $iUserId = null)
    {
        static $aCache = [];

        if ($iUserId === null) {
            if (!Phpfox::isUser()) {
                return false;
            }

            $iUserId = Phpfox::getUserId();
        }

        if (!isset($aCache[$iUserId])) {
            $aCache[$iUserId] = [];

            $sCacheId = $this->cache()->set(['userdata', $iUserId]);
            $aCache[$iUserId] = (array)$this->cache()->get($sCacheId);
        }

        if (isset($aCache[$iUserId][$sSaveData])) {
            return $aCache[$iUserId][$sSaveData];
        }

        return false;
    }

    public function getStaticInfo($iUserId)
    {
        static $aCachedUserInfo = [];

        if (isset($aCachedUserInfo[$iUserId])) {
            return $aCachedUserInfo[$iUserId];
        }

        $sInnerJoinCacheId = Phpfox::getLib('cache')->set(['userjoin', $iUserId]);
        $aCachedUserInfo[$iUserId] = Phpfox::getLib('cache')->get($sInnerJoinCacheId);
        if (!empty($aCachedUserInfo[$iUserId])) {
            return $aCachedUserInfo[$iUserId];
        }

        $aCachedUserInfo[$iUserId] = false;

        return false;
    }

    public function getCurrentName($iUserId, $sName)
    {
        return $sName;
    }

    public function getUserName($iUserId, $sUserName)
    {
        if (!empty($sUserName)) {
            return $sUserName;
        }
        return $iUserId;
    }

    public function getByUserName($sUser)
    {
        $this->database()->leftJoin(Phpfox::getT('log_session'), 'ls', 'ls.user_id = u.user_id AND ls.im_hide = 0');

        $aRow = $this->database()->select('u.*, ls.user_id AS is_online, user_field.*')
            ->from($this->_sTable, 'u')
            ->join(Phpfox::getT('user_field'), 'user_field', 'user_field.user_id = u.user_id')
            ->where("u.user_name = '" . $this->database()->escape($sUser) . "'")
            ->execute('getSlaveRow');

        if (isset($aRow['is_invisible']) && $aRow['is_invisible']) {
            $aRow['is_online'] = '0';
        }

        return $aRow;
    }

    public function getByUserId($iUser)
    {
        $this->database()->leftJoin(Phpfox::getT('log_session'), 'ls', 'ls.user_id = u.user_id AND ls.im_hide = 0');

        $aRow = $this->database()->select('u.*, ls.user_id AS is_online, user_field.*')
            ->from($this->_sTable, 'u')
            ->join(Phpfox::getT('user_field'), 'user_field', 'user_field.user_id = u.user_id')
            ->where("u.user_id = " . $this->database()->escape($iUser))
            ->execute('getSlaveRow');

        if (isset($aRow['is_invisible']) && $aRow['is_invisible']) {
            $aRow['is_online'] = '0';
        }

        return $aRow;
    }

    public function getPageType($pageId)
    {
        return $this->database()->select('p.item_type')
            ->from(':pages', 'p')
            ->where('p.page_id = ' . $this->database()->escape($pageId))
            ->execute('getSlaveField');
    }

    public function getUser($mUser, $sSelect = 'u.*', $bUserName = false)
    {
        (($sPlugin = Phpfox_Plugin::get('user.service_user_getuser_start')) ? eval($sPlugin) : false);

        if ($bUserName === false) {
            if ((int)$mUser === 0) {
                return false;
            }
        } else {
            if (empty($mUser)) {
                return false;
            }
        }

        $aRow = $this->database()->select($sSelect)
            ->from($this->_sTable, 'u')
            ->where(($bUserName ? "u.user_name = '" . $this->database()->escape($mUser) . "'" : 'u.user_id = ' . (int)$mUser))
            ->execute('getSlaveRow');

        (($sPlugin = Phpfox_Plugin::get('user.service_user_getuser_end')) ? eval($sPlugin) : false);

        return $aRow;
    }


    public function get($mName = null, $bUseId = true, $bStaticCache = true)
    {
        static $aUser = [];

        if (isset($aUser[$mName]) && $bStaticCache) {
            return $aUser[$mName];
        }

        /*
         * For this super caching we need to clear the profile cache when:
         * 	- [Y] The admin changes anything related to the theme.
         * 	- [Y] In designer the user changes the style
         * 	- [Y] Updates the cover photo
         * 	- [Tradeoff] User changes does any activity OR query here for `user_activity`
         */

        if (Phpfox::getParam('profile.profile_caches_user') && Phpfox_Request::instance()->get('req2') == '') {
            // Any way to avoid this query?
            if ($bUseId != true) {
                if ($mName != Phpfox::getUserBy('user_name')) {
                    $mName = $this->database()->select('user_id')->from(Phpfox::getT('user'))->where('user_name = "' . $this->database()->escape($mName) . '"')->execute('getSlaveField');
                } else {
                    $mName = Phpfox::getUserId();
                }
                $bUseId = true;
            }
            $sCacheId = $this->cache()->set(['profile', 'user_id_' . $mName]);
            if (false !== ($aCachedUser = $this->cache()->get($sCacheId)) && is_array($aCachedUser)) {
                $aUser[$mName] = $aCachedUser;
                if (!isset($this->_aUser[$aCachedUser['user_id']])) {
                    $this->_aUser[$aCachedUser['user_id']] = $aCachedUser;
                }
                return $aCachedUser;
            }
        }

        (($sPlugin = Phpfox_Plugin::get('user.service_user_get_start')) ? eval($sPlugin) : false);

        if (Phpfox::isUser() && Phpfox::getParam('profile.profile_caches') != true) {
            // Try to cache this one
            $this->database()->select('ut.item_id AS is_viewed, ')->leftJoin(Phpfox::getT('track'), 'ut', 'ut.item_id = u.user_id AND ut.user_id = ' . Phpfox::getUserId() . ' AND ut.type_id=\'user\'');
        }

        // This is only needed in the info page
        if (Phpfox_Request::instance()->get('req2') == 'info') {
            // Implement later, we're on the profile.index right now. Lets do profile.info tomorrow
            $this->database()->select('ur.rate_id AS has_rated, ')->leftJoin(Phpfox::getT('user_rating'), 'ur', 'ur.item_id = u.user_id AND ur.user_id = ' . Phpfox::getUserId());
        }

        $this->database()->join(Phpfox::getT('user_group'), 'ug', 'ug.user_group_id = u.user_group_id')
            ->join(Phpfox::getT('user_space'), 'user_space', 'user_space.user_id = u.user_id')
            ->join(Phpfox::getT('user_field'), 'user_field', 'user_field.user_id = u.user_id')
            ->join(Phpfox::getT('user_activity'), 'user_activity', 'user_activity.user_id = u.user_id')
            ->leftJoin(Phpfox::getT('theme_style'), 'ts', 'ts.style_id = user_field.designer_style_id AND ts.is_active = 1')
            ->leftJoin(Phpfox::getT('theme'), 't', 't.theme_id = ts.theme_id')
            ->leftJoin(Phpfox::getT('user_featured'), 'uf', 'uf.user_id = u.user_id');

        $this->database()->leftJoin(Phpfox::getT('log_session'), 'ls', 'ls.user_id = u.user_id AND ls.im_hide = 0');

        if (Phpfox::isAppActive('Core_Photos')) {
            $this->database()->select('p.photo_id as cover_photo_exists, ')->leftJoin(Phpfox::getT('photo'), 'p', 'p.photo_id = user_field.cover_photo');
        }

        $aRow = $this->database()->select('u.*, user_space.*, user_field.*, user_activity.*, ls.user_id AS is_online, ts.style_id AS designer_style_id, ts.folder AS designer_style_folder, t.folder AS designer_theme_folder, t.total_column, ts.l_width, ts.c_width, ts.r_width, t.parent_id AS theme_parent_id, ug.prefix, ug.suffix, ug.icon_ext, ug.title, uf.user_id as is_featured')
            ->from($this->_sTable, 'u')
            ->where(($bUseId ? "u.user_id = " . (int)$mName . "" : "u.user_name = '" . $this->database()->escape($mName) . "'"))
            ->execute('getSlaveRow');

        $aUser[$mName] =& $aRow;

        if (!isset($aUser[$mName]['user_name'])) {
            return false;
        }

        // set default privacy
        if (empty($aRow['dob_setting'])) {
            switch (Phpfox::getParam('user.default_privacy_brithdate')) {
                case 'month_day':
                    $aRow['dob_setting'] = 1;
                    break;
                case 'show_age':
                    $aRow['dob_setting'] = 2;
                    break;
                case 'hide':
                    $aRow['dob_setting'] = 3;
                    break;
                case 'full_birthday':
                    $aRow['dob_setting'] = 4;
                    break;
            }
        }

        (($sPlugin = Phpfox_Plugin::get('user.service_user_get_end')) ? eval($sPlugin) : false);

        if (isset($aRow['is_invisible']) && $aRow['is_invisible']) {
            $aRow['is_online'] = '0';
        }
        if (isset($aRow['cover_photo']) && ((int)$aRow['cover_photo'] > 0) &&
            (
                (isset($aRow['cover_photo_exists']) && $aRow['cover_photo_exists'] != $aRow['cover_photo']) ||
                (!isset($aRow['cover_photo_exists']))
            )) {
            $aRow['cover_photo'] = null;
        }

        $aUser[$mName]['user_server_id'] = $aUser[$mName]['server_id'];

        $aUser[$mName]['is_friend'] = false;
        $aUser[$mName]['is_reverse_friend'] = false;
        $aUser[$mName]['is_friend_of_friend'] = false;
        $aUser[$mName]['is_friend_request'] = false;

        if (Phpfox::isUser() && Phpfox::isModule('friend') && Phpfox::getUserId() != $aUser[$mName]['user_id']) {
            $oFriendRequestService = Phpfox::getService('friend.request');
            $aUser[$mName]['is_friend'] = !!Phpfox::getService('friend')->isFriend(Phpfox::getUserId(), $aUser[$mName]['user_id']);
            $aUser[$mName]['is_reverse_friend'] = !!Phpfox::getService('friend')->isFriend($aUser[$mName]['user_id'], Phpfox::getUserId());
            $aUser[$mName]['is_friend_of_friend'] = !!Phpfox::getService('friend')->isFriendOfFriend($aUser[$mName]['user_id']);
            if (!$aUser[$mName]['is_friend']) {
                $iRequestId = $oFriendRequestService->isRequested(Phpfox::getUserId(), $aUser[$mName]['user_id'], true, true);
                if ($iRequestId) {
                    $aUser[$mName]['is_friend_request'] = 2;
                } else {
                    $aUser[$mName]['is_friend_request'] = false;
                }
                $aUser[$mName]['is_friend_request_id'] = $iRequestId;
                if (!$aUser[$mName]['is_friend_request']) {
                    $iRequestId = Phpfox::getService('friend.request')->isRequested($aUser[$mName]['user_id'], Phpfox::getUserId(), true, true);
                    $aUser[$mName]['is_friend_request'] = ($iRequestId ? 3 : false);
                    $aUser[$mName]['is_friend_request_id'] = $iRequestId;
                }

                $aUser[$mName] = array_merge($aUser[$mName], [
                    'is_ignore_request' => !!$oFriendRequestService->isDenied(Phpfox::getUserId(), $aUser[$mName]['user_id']),
                ]);
            }
        }

        $this->_aUser[$aRow['user_id']] = $aUser[$mName];

        return $aUser[$mName];
    }

    public function getUserObject($iUserId)
    {
        return (object)(isset($this->_aUser[$iUserId]) ? $this->_aUser[$iUserId] : false);
    }

    public function getForEdit($iUserId)
    {
        Phpfox::getUserParam('user.can_edit_users', true);

        (($sPlugin = Phpfox_Plugin::get('user.service_user_getforedit')) ? eval($sPlugin) : false);

        $aUser = $this->database()->select('u.*, uf.*, ua.*')
            ->from($this->_sTable, 'u')
            ->join(Phpfox::getT('user_field'), 'uf', 'uf.user_id = u.user_id')
            ->join(Phpfox::getT('user_activity'), 'ua', 'ua.user_id = u.user_id')
            ->where('u.user_id = ' . (int)$iUserId)
            ->execute('getSlaveRow');

        if (!isset($aUser['user_id'])) {
            return Phpfox_Error::set(_p('unable_to_find_the_user_you_plan_to_edit'));
        }

        return $aUser;
    }

    public function getNew($iLimit = 8)
    {
        return $this->database()->select(Phpfox::getUserField())
            ->from($this->_sTable, 'u')
            ->order('u.joined DESC')
            ->where('u.profile_page_id = 0')
            ->limit($iLimit)
            ->execute('getSlaveRows');
    }

    public function getRandom($iLimit = 4)
    {
        return $this->database()->select(Phpfox::getUserField())
            ->from($this->_sTable, 'u')
            ->where('u.user_image IS NOT NULL AND view_id = 0')
            ->order('RAND()')
            ->limit($iLimit)
            ->execute('getSlaveRows');
    }

    public function isUser($mName, $bId = false)
    {
        (($sPlugin = Phpfox_Plugin::get('user.service_user_isuser')) ? eval($sPlugin) : false);

        return $this->database()->select('COUNT(*)')
            ->from($this->_sTable)
            ->where(($bId ? "user_id = " . (int)$mName : "user_name = '" . $this->database()->escape($mName) . "'"))
            ->execute('getSlaveField');
    }

    public function isFeatured($iId = null)
    {
        if ($iId === null || $iId == Phpfox::getUserId()) {
            if (!empty($this->_aUser) && isset($this->_aUser['is_featured'])) {
                return $this->_aUser['is_featured'];
            }

            if (empty($iId)) {
                return false;
            }
        }
        return $this->database()->select('COUNT(*)')
            ->from(':user_featured')
            ->where("user_id = " . (int)$iId)
            ->execute('getSlaveField');
    }

    /**
     * Gets the language_phrase variable names for the reasons available to show at account cancellation
     * @return array
     */
    public function getReasons()
    {
        $sCacheId = $this->cache()->set('user_cancellations');
        if (false === ($aReasons = $this->cache()->get($sCacheId))) {
            $aReasons = $this->database()->select('*')
                ->from(Phpfox::getT('user_delete'))
                ->order('ordering ASC')
                ->where('is_active = 1')
                ->execute('getSlaveRows');

            $this->cache()->save($sCacheId, $aReasons);
            Phpfox::getLib('cache')->group('user', $sCacheId);
        }
        if (!isset($aReasons) || !is_array($aReasons)) {
            $aReasons = [];
        }
        foreach ($aReasons as $iKey => $aReason) {
            if ($aReasons[$iKey]['is_active'] != 1) {
                unset($aReasons[$iKey]);
                continue;
            }
            $aReasons[$iKey]['phrase_text'] = Phpfox::getLib('parse.output')->clean(_p($aReason['phrase_var']));
        }
        return $aReasons;
    }

    /**
     * get gender name
     *
     * @param int $iGender
     * @param int $iType   ($iType:Result if male|Result if female)
     *                     0: 1|2
     *                     1: their
     *                     2: male|female
     *                     3: himself|herself
     *                     else: empty
     *
     * @return string|int
     */
    public function gender($iGender, $iType = 0)
    {
        static $sPlugin;

        if (null === $sPlugin) {
            $sPlugin = Phpfox_Plugin::get('user.service_user_gender');
        }

        switch ($iType) {
            case 1:
                $sGender = _p('their');
                break;
            default:
                $sGender = '';
        }

        foreach ((array)Phpfox::getParam('user.global_genders') as $iKey => $aGender) {
            if ($iGender == $iKey) {
                if ($iType == 2) {
                    return _p($aGender[2]);
                }
                return ($iType == 1 ? _p($aGender[0]) : _p($aGender[1]));
            }
        }

        ($sPlugin ? eval($sPlugin) : false);

        return $sGender;
    }

    /**
     * Formats the date so its easier to search birthdate
     *
     * @param int $iDay
     * @param int $iMonth
     * @param int $iYear
     *
     * @return String
     * @example buildAge(1,9,1980) returns: "09011980"
     * @example buildAge("8","19",1980) returns false, there is no month 19th
     * @example buildAge("8","11","1978") returns "11081978"
     */
    public function buildAge($iDay, $iMonth, $iYear = null)
    {
        $iDay = (int)$iDay;
        $iMonth = (int)$iMonth;
        $iYear = ($iYear !== null) ? (int)$iYear : null;
        if ((1 > $iDay || $iDay > 31) || (1 > $iMonth || $iMonth > 12)) {
            return false;
        }
        if ($iYear !== null) {
            return ($iMonth < 10 ? '0' . $iMonth : $iMonth) . ($iDay < 10 ? '0' . $iDay : $iDay) . $iYear;
        }

        return ($iMonth < 10 ? '0' . $iMonth : $iMonth) . ($iDay < 10 ? '0' . $iDay : $iDay);
    }

    /**
     * Returns how old is a user based on its birthdate
     *
     * @param String $sAge
     *
     * @return int
     */
    public function age($sAge)
    {
        if (!$sAge) {
            return $sAge;
        }
        $iYear = intval(substr($sAge, 4));
        $iMonth = intval(substr($sAge, 0, 2));
        $iDay = intval(substr($sAge, 2, 2));
        $iAge = date('Y') - (int)$iYear;
        $iCurrDate = date('m') * 100 + date('d');
        $iBirthDate = $iMonth * 100 + $iDay;

        if ($iCurrDate < $iBirthDate) {
            $iAge--;
        }

        return $iAge;
    }

    public function getAgeArray($sAge)
    {
        return [
            'day'   => intval(substr($sAge, 2, 2)),
            'month' => intval(substr($sAge, 0, 2)),
            'year'  => intval(substr($sAge, 4))
        ];
    }

    public function getInlineSearch($sUser, $sOld)
    {
        (($sPlugin = Phpfox_Plugin::get('user.service_user_getinlinesearch')) ? eval($sPlugin) : false);

        $sOld = trim(rtrim($sOld, ','));
        if (strpos($sOld, ',')) {
            $sOld = explode(',', $sOld);
            $sOld = array_map('trim', $sOld);
        }

        $aRows = $this->database()->select('u.user_id, u.full_name AS tag_text, u.user_name, u.server_id, u.user_name, u.user_image')
            ->from($this->_sTable, 'u')
            ->join(Phpfox::getT('friend'), 'f', 'f.user_id = u.user_id AND f.friend_user_id = ' . Phpfox::getUserId())
            ->where((strpos($sUser, '@') ? "u.email LIKE '" . $this->database()->escape($sUser) . "%'" : "(u.full_name LIKE '" . $this->database()->escape($sUser) . "%' OR u.user_name LIKE '" . $this->database()->escape($sUser) . "%')"))
            ->limit(0, 10)
            ->execute('getSlaveRows');

        foreach ($aRows as $iKey => $aRow) {
            if ((is_array($sOld) && in_array($aRow['user_id'], $sOld) || (is_string($sOld) && $aRow['user_id'] === $sOld))) {
                unset($aRows[$iKey]);
            }
        }

        return $aRows;
    }

    public function getSearchUsersToBlock($sUser, $iPage, $iLimit)
    {
        $iUserId = Phpfox::getUserId();
        $aBlockedUserIds = Phpfox::getService('user.block')->get($iUserId, true);
        $searchFields = ['u.full_name', 'u.user_name'];
        $aBlockedUserIds[] = $iUserId;

        if (Phpfox::getParam('core.enable_register_with_phone_number')) {
            $searchFields[] = 'u.full_phone_number';
        }
        if (function_exists('filter_var') && filter_var($sUser, FILTER_VALIDATE_EMAIL)) {
            $searchFields[] = 'u.email';
        } else if (preg_match('/^[0-9a-zA-Z]([\-+.\w]*[0-9a-zA-Z]?)*@([0-9a-zA-Z\-.\w]*[0-9a-zA-Z]\.)+[a-zA-Z]{2,}$/', $sUser) && strlen($sUser) <= 100) {
            $searchFields[] = 'u.email';
        }

        $aCond[] = $this->database()->searchKeywords($searchFields, $sUser) . ' AND u.profile_page_id = 0';

        if (!empty($aBlockedUserIds)) {
            $aCond[] = ' AND u.user_id NOT IN (' . implode(', ', $aBlockedUserIds) . ')';
        }

        return $this->database()->select(Phpfox::getUserField())
            ->from($this->_sTable, 'u')
            ->where($aCond)
            ->limit($iPage, $iLimit)
            ->executeRows();
    }

    public function getLink($iUserId = null, $sUserName = null, $aParams = null)
    {
        if (($iUserId === null || $sUserName === null)) {
            $aRow = $this->database()->select('user_id, user_name')
                ->from($this->_sTable)
                ->where(($iUserId === null ? "user_name = '" . $this->database()->escape($sUserName) . "'" : 'user_id = ' . (int)$iUserId))
                ->execute('getSlaveRow');

            if (!isset($aRow['user_id'])) {
                return Phpfox_Error::trigger('Not a valid user.', E_USER_ERROR);
            }
            $sUserName = $aRow['user_name'];
        }

        return Phpfox_Url::instance()->makeUrl($sUserName, $aParams);
    }

    /**
     * Returns the first name of a users full name.
     *
     * Usage within a template:
     * <code>
     * {$sFullname|first_name}
     * </code>
     *
     * Usage within a PHP class:
     * <code>
     * Phpfox::getService('user')->getFirstName($sFullName);
     * </code>
     *
     * @param string $sName Full name of the member.
     *
     * @return string Returns the first part of the name.
     */
    public function getFirstName($sName)
    {
        // Create an array based on a space between a persons name
        $aParts = explode(' ', $sName);
        // Return the first part of the name, which is the first name
        return $aParts[0];
    }

    /**
     * @param bool        $bReturnUserValues
     * @param null|array  $aUser
     * @param null|string $sPrefix
     * @param null|int    $iUserId
     *
     * @return array|mixed|null
     */
    public function getUserFields($bReturnUserValues = false, &$aUser = null, $sPrefix = null, $iUserId = null)
    {
        $aFields = [
            'user_id',
            'profile_page_id',
            'server_id',
            'user_name',
            'full_name',
            'gender',
            'user_image',
            'is_invisible',
            'user_group_id',
            'language_id'
        ];

        if (Phpfox::getParam('user.display_user_online_status')) {
            $aFields[] = 'last_activity';
        }

        $aFields[] = 'birthday';
        $aFields[] = 'country_iso';

        /* Return $aFields but about iUserId */
        if ($iUserId != null) {
            $aUser = $this->database()->select(implode(',', $aFields))
                ->from(Phpfox::getT('user'))
                ->where('user_id = ' . (int)$iUserId)
                ->execute('getSlaveRow');

            return $aUser;
        }

        (($sPlugin = Phpfox_Plugin::get('user.service_user_getuserfields')) ? eval($sPlugin) : false);

        if ($bReturnUserValues) {
            $aCache = [];
            foreach ($aFields as $sField) {
                if ($sPrefix !== null) {
                    if ($sField == 'server_id') {
                        $sField = 'user_' . $sPrefix . $sField;
                    } else {
                        $sField = $sPrefix . $sField;
                    }
                }

                $aCache[$sField] = ($aUser === null ? Phpfox::getUserBy($sField) : $aUser[$sField]);
            }

            return $aCache;
        }

        return $aFields;
    }

    public function getSpamTotal()
    {
        return $this->database()->select('COUNT(*)')
            ->from(Phpfox::getT('user'))
            ->where('total_spam > ' . (int)Phpfox::getParam('core.auto_deny_items'))
            ->execute('getSlaveField');
    }

    public function getCredit()
    {
        static $sCredit = null;

        if ($sCredit === null) {
            if (Phpfox::getUserId()) {
                $sCredit = $this->database()->select('uf.credit')
                    ->from(Phpfox::getT('user'), 'u')
                    ->join(Phpfox::getT('user_field'), 'uf', 'uf.user_id = u.user_id')
                    ->where('u.user_id = ' . Phpfox::getUserId())
                    ->execute('getSlaveField');
            } else {
                $sCredit = '0.00';
            }
        }

        return $sCredit;
    }

    public function getCurrency()
    {
        static $sCredit = null;
        if ($sPlugin = Phpfox_Plugin::get('user.service_user_getcurrency__1')) {
            eval($sPlugin);
            if (isset($mReturnFromPlugin)) {
                return $mReturnFromPlugin;
            }
        }

        if ($sCredit === null) {
            if (Phpfox::getUserId()) {
                $sCacheId = $this->cache()->set(['currency', Phpfox::getUserId()]);
                $this->cache()->group('currency', $sCacheId);
                if (false === ($sCredit = $this->cache()->get($sCacheId))) {
                    $sCredit = $this->database()->select('uf.default_currency')
                        ->from(Phpfox::getT('user'), 'u')
                        ->join(Phpfox::getT('user_field'), 'uf', 'uf.user_id = u.user_id')
                        ->join(Phpfox::getT('currency'), 'c', 'c.currency_id = uf.default_currency AND c.is_active = 1')
                        ->where('u.user_id = ' . Phpfox::getUserId())
                        ->execute('getSlaveField');

                    if (empty($sCredit)) {
                        $sCredit = $this->database()->select('currency_id')
                            ->from(Phpfox::getT('currency'))
                            ->where('is_active = 1 AND is_default = 1')
                            ->execute('getSlaveField');
                    }

                    $this->cache()->save($sCacheId, $sCredit);
                    Phpfox::getLib('cache')->group('currency', $sCacheId);
                }
            } else {
                $sCredit = false;
            }
        }

        if ($sCredit == null) {
            return $this->database()->select('currency_id')
                ->from(Phpfox::getT('currency'))
                ->where('is_active = 1 AND is_default = 1')
                ->execute('getSlaveField');
        }

        return $sCredit;
    }

    public function isAdminUser($iUserId, $bCheckCurrentUser = false)
    {
        if ($bCheckCurrentUser && ADMIN_USER_ID == Phpfox::getUserBy('user_group_id')) {
            return true;
        }
        $this->database()->clean();
        $sUserGroupId = $this->database()->select('user_group_id')
            ->from(Phpfox::getT('user'))
            ->where('user_id = ' . (int)$iUserId)
            ->execute('getSlaveField');

        if ($sUserGroupId == ADMIN_USER_ID) {
            return true;
        }

        return false;
    }

    public function getProfileBirthDate($aUser)
    {
        static $aUserDetails = null;

        if (is_array($aUserDetails)) {
            return $aUserDetails;
        }

        if ($aUserDetails === null) {
            $aUserDetails = [];
        }

        if (isset($aUser['dob_setting']) && !empty($aUser['birthday']) && $aUser['dob_setting'] != '3') {
            // Take the adminCP setting user.default_privacy_birthdate
            if ($aUser['dob_setting'] == 0) {
                switch (Phpfox::getParam('user.default_privacy_brithdate')) {
                    case 'month_day':
                        $aUser['dob_setting'] = 1;
                        break;
                    case 'show_age':
                        $aUser['dob_setting'] = 2;
                        break;
                    case 'full_birthday':
                        $aUser['dob_setting'] = 4;
                        break;
                }
            }
            $sPhrase = _p('birth_date');
            $aBirthDay = Phpfox::getService('user')->getAgeArray($aUser['birthday_time_stamp']);
            switch ($aUser['dob_setting']) {
                case '1':
                    $sDateExtra = Phpfox::getTime(Phpfox::getParam('user.user_dob_month_day'), mktime(0, 0, 0, $aBirthDay['month'], $aBirthDay['day'], $aBirthDay['year']), false);
                    break;
                case '2':
                    $sDateExtra = $aUser['birthday'];
                    $sPhrase = _p('age');
                    break;
                default:
                    $sDateExtra = Phpfox::getTime(Phpfox::getParam('user.user_dob_month_day_year'), mktime(0, 0, 0, $aBirthDay['month'], $aBirthDay['day'], $aBirthDay['year']), false);
                    break;
            }
            $aUserDetails[$sPhrase] = $sDateExtra;
        }

        return $aUserDetails;
    }


    /**
     * Gets the count for how many members have been inactive since $iDays
     *
     * @param int $iDays
     *
     * @return int inactive members since $iDays
     */
    public function getInactiveMembersCount($iDays)
    {
        $iDays = (int)$iDays;

        $iCnt = $this->database()->select('COUNT(user_id)')
            ->from(Phpfox::getT('user'))
            ->where('profile_page_id = 0 AND last_activity < ' . (PHPFOX_TIME - ($iDays * 86400)))
            ->execute('getSlaveField');

        return $iCnt;
    }

    public function getInactiveMembers($iDays)
    {
        $iDays = (int)$iDays;

        return $this->database()->select('user_id')
            ->from(Phpfox::getT('user'))
            ->where('profile_page_id = 0 AND last_activity < ' . (PHPFOX_TIME - ($iDays * 86400)))
            ->execute('getSlaveRows');
    }

    public function getUserImages()
    {
        $sCacheId = $this->cache()->set(['user', 'user_welcome_image']);

        if (false === ($aRows = $this->cache()->get($sCacheId, 60))) {
            $aRows = $this->database()->select(Phpfox::getUserField())
                ->from(Phpfox::getT('user'), 'u')
                ->where('is_invisible != 1 AND u.status_id = 0 AND u.view_id = 0 AND ' . $this->database()->isNotNull('u.user_image'))
                ->limit(70)
                ->order('u.last_activity DESC')
                ->execute('getSlaveRows');

            $this->cache()->save($sCacheId, $aRows);
            Phpfox::getLib('cache')->group('user', $sCacheId);
        }

        return $aRows;
    }

    public function getSpamQuestion($iQuestionId)
    {
        $aQuestion = $this->database()->select('*')
            ->from(Phpfox::getT('user_spam'))
            ->where('question_id=' . intval($iQuestionId))
            ->execute('getSlaveRow');

        $aQuestion['original_answers_phrases'] = $aQuestion['answers_phrases'] = json_decode($aQuestion['answers_phrases']);

        foreach ($aQuestion['answers_phrases'] as $key => $value) {
            if (Core\Lib::phrase()->isPhrase($value)) {
                $aQuestion['answers_phrases'][$key] = _p($value);
            }
        }

        return $aQuestion;
    }

    public function getSpamQuestions($bActive = true)
    {
        $cache = cache('spam/questions');
        if (!($aQuestions = $cache->get())) {
            $aQuestions = $this->database()->select('*')
                ->from(Phpfox::getT('user_spam'))
                ->execute('getSlaveRows');

            $cache->set($aQuestions);
        }

        $aQuestions = (is_bool($aQuestions) ? [] : $aQuestions);
        foreach ($aQuestions as $iKey => $aQuestion) {
            if ($bActive && !$aQuestion['is_active']) {
                unset($aQuestions[$iKey]);
                continue;
            }

            if ($answerPhrases = json_decode($aQuestion['answers_phrases'])) {
                foreach ($answerPhrases as $key => $value) {
                    if (Core\Lib::phrase()->isPhrase($value)) {
                        $answerPhrases[$key] = Phpfox::getLib('parse.output')->clean(_p($value));
                    }
                }
            }

            $aQuestions[$iKey] = array_merge($aQuestion, [
                'answers_phrases' => $answerPhrases,
                'question_phrase' => Phpfox::getLib('parse.output')->clean(Core\Lib::phrase()->isPhrase($aQuestion['question_phrase']) ? _p($aQuestion['question_phrase']) : $aQuestion['question_phrase']),
            ]);
        }

        return array_values($aQuestions);
    }

    /**
     * @param $iUserId
     *
     * @return array|bool|int|string
     */
    public function getUserGroupId($iUserId)
    {
        if (!$iUserId) {
            $iUserId = Phpfox::getUserId();
        }
        if (!$iUserId) {
            return false;
        }

        return db()->select('user_group_id')
            ->from(Phpfox::getT('user'))
            ->where('user_id = ' . $iUserId)
            ->execute('getSlaveField');
    }

    public function clearUserCache($iUserId = null)
    {
        if (!$iUserId) {
            $iUserId = Phpfox::getUserId();
        }
        if (!$iUserId) {
            return false;
        }
        cache()->del('friend_suggestion_' . $iUserId);
        cache()->del('rec_users_' . $iUserId);
        cache()->del('new_users_' . $iUserId);
        cache()->del('recent_active_users_' . $iUserId);
        cache()->del('featured-users-pages-items_' . $iUserId);
        cache()->del('rec_users_' . $iUserId);
        return true;
    }

    /**
     * @param int $iUserId
     *
     * @return array
     */
    public function getUserStatistics($iUserId)
    {
        $aStats = [];
        $iTotalItem = 0;
        $aCallback = Phpfox::massCallback('getUserStatsForAdmin', $iUserId);
        foreach ($aCallback as $iKey => $aValue) {
            if (!empty($aValue['type']) && $aValue['type'] == 'item') {
                $iTotalItem = $iTotalItem + $aValue['total_value'];
                $aStats[] = [
                    'name'  => $aValue['total_name'],
                    'total' => $aValue['total_value']
                ];
            }
        }
        $aStats[] = [
            'name'  => _p('total_items'),
            'total' => $iTotalItem
        ];

        return $aStats;
    }

    public function getUserGroupName()
    {
        $sUserGroupName = $this->database()->select('ug.title')
            ->from(':user_group', 'ug')
            ->join(':user', 'u', 'u.user_id=' . Phpfox::getUserId() . ' AND u.user_group_id = ug.user_group_id')
            ->executeField();
        return _p($sUserGroupName);
    }

    /**
     * Get user thumbnail sizes
     * @return array
     */
    public function getUserThumbnailSizes()
    {
        $aPicSizes = $this->_aUserThumbnailSizes;

        (($sPlugin = Phpfox_Plugin::get('user.service_user_getuserpicsizes')) ? eval($sPlugin) : false);

        return $aPicSizes;
    }

    /**
     * @param $user
     *
     * @return array|mixed
     */
    public function getCustomGenders($user = null, $htmlEncode = true)
    {
        if ($user == null) {
            $user = $this->get(Phpfox::getUserId(), true);
        }

        $aCustomGenders = [];
        if ($user['gender'] == '127' && !empty($user['custom_gender'])) // 127 is special case for custom gender
        {
            $aCustomGenders = Phpfox::getLib('parse.format')->isSerialized($user['custom_gender']) ? unserialize($user['custom_gender']) : $user['custom_gender'];
            foreach ($aCustomGenders as $iKey => $sValue) {
                $aCustomGenders[$iKey] = Phpfox::getLib('parse.output')->clean($sValue, $htmlEncode);
            }
        }

        return $aCustomGenders;
    }

    /**
     * Check can send message to user
     * @param $userId
     * @param $isFriend
     *
     * @return bool
     */
    public function canSendMessage($userId, $isFriend)
    {
        if (Phpfox::isAppActive('Core_Messages')) {
            $valid = (!Phpfox::getParam('mail.disallow_select_of_recipients') || Phpfox::getService('user.privacy')->hasAccess($userId, 'mail.send_message')) && (!Phpfox::getUserParam('mail.restrict_message_to_friends') || $isFriend);
        } else {
            $valid = (Phpfox::isAppActive('PHPfox_IM') && $isFriend) || Phpfox::isAppActive('P_ChatPlus');
        }
        return $valid;
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
        if ($sPlugin = Phpfox_Plugin::get('user.service_user__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}
