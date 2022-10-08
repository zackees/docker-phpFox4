<?php

defined('PHPFOX') or exit('NO DICE!');

/**
 * @copyright        [PHPFOX_COPYRIGHT]
 * @author           phpFox LLC
 * @package          Module_Feed
 */
class Feed_Service_Feed extends Phpfox_Service
{
    /**
     * @var array
     */
    private $_aViewMoreFeeds = [];

    /**
     * @var array
     */
    private $_aCallback = [];

    /**
     * @var string
     */
    private $_sLastDayInfo = '';

    /**
     * @var array
     */
    private $_aFeedTimeline = ['left' => [], 'right' => []];

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('feed');

        (($sPlugin = Phpfox_Plugin::get('feed.service_feed___construct')) ? eval($sPlugin) : false);
    }

    /**
     * @param int $iUserId
     * @param int $iLastTimeStamp
     *
     * @return array|mixed
     */
    public function getTimeLineYears($iUserId, $iLastTimeStamp)
    {
        static $aCachedYears = [];

        if (isset($aCachedYears[$iUserId])) {
            return $aCachedYears[$iUserId];
        }

        $sCacheId = $this->cache()->set(['timeline', $iUserId]);
        if (false === ($aNewYears = $this->cache()->get($sCacheId))) {
            $aYears = range(date('Y', PHPFOX_TIME), date('Y', $iLastTimeStamp));
            foreach ($aYears as $iYear) {
                $iStartYear = mktime(0, 0, 0, 1, 1, $iYear);
                $iEndYear = mktime(0, 0, 0, 12, 31, $iYear);

                $iCnt = $this->database()->select('COUNT(*)')
                    ->from(Phpfox::getT('feed'))
                    ->forceIndex('time_stamp')
                    ->where('user_id = ' . (int)$iUserId . ' AND feed_reference = 0 AND time_stamp > \'' . $iStartYear . '\' AND time_stamp <= \'' . $iEndYear . '\'')
                    ->execute('getSlaveField');

                if ($iCnt) {
                    $aNewYears[] = $iYear;
                }
            }

            $this->cache()->save($sCacheId, $aNewYears);
        }

        if (!is_array($aNewYears)) {
            $aNewYears = [];
        }

        $iBirthYear = date('Y', $iLastTimeStamp);

        $sDobCacheId = $this->cache()->set(['udob', $iUserId]);

        if (false === ($iDOB = $this->cache()->get($sDobCacheId))) {
            $iDOB = $this->database()->select('dob_setting')->from(Phpfox::getT('user_field'))->where('user_id = ' . (int)$iUserId)->execute('getSlaveField');
            $this->cache()->save($sDobCacheId, $iDOB);
            Phpfox::getLib('cache')->group('user', $sCacheId);
        }

        if ($iDOB == 0) {
            $sPermission = Phpfox::getParam('user.default_privacy_brithdate');
            $bShowBirthYear = ($sPermission == 'full_birthday' || $sPermission == 'show_age');
        }

        if (!in_array($iBirthYear, $aNewYears) && ($iDOB == 2 || $iDOB == 4 || ($iDOB == 0 && isset($bShowBirthYear) && $bShowBirthYear))) {
            $aNewYears[] = $iBirthYear;
        }

        $aYears = [];
        foreach ($aNewYears as $iYear) {
            $aMonths = [];
            foreach (range(1, 12) as $iMonth) {
                if ($iYear == date('Y', PHPFOX_TIME) && $iMonth > date('n', PHPFOX_TIME)) {

                } else if ($iYear == date('Y', $iLastTimeStamp) && $iMonth > date('n', $iLastTimeStamp)) {

                } else {
                    $aMonths[] = [
                        'id'     => $iMonth,
                        'phrase' => Phpfox::getTime('F', mktime(0, 0, 0, $iMonth, 1, $iYear), false),
                    ];
                }
            }

            $aMonths = array_reverse($aMonths);

            $aYears[] = [
                'year'   => $iYear,
                'months' => $aMonths,
            ];
        }

        $aCachedYears[$iUserId] = $aYears;

        return $aYears;
    }

    /**
     * @param string $sModule
     * @param int    $iItemId
     *
     * @return array|bool
     */
    public function getForItem($sModule, $iItemId)
    {
        $aRow = $this->database()->select('*')
            ->from(Phpfox::getT('feed'))
            ->where('type_id = \'' . $this->database()->escape($sModule) . '\' AND item_id = ' . (int)$iItemId)
            ->executeRow();

        if (isset($aRow['feed_id'])) {
            return $aRow;
        }

        return false;
    }

    /**
     * @param array $aCallback
     *
     * @return $this
     */
    public function callback($aCallback)
    {
        $this->_aCallback = $aCallback;
        return $this;
    }

    /**
     * @param string $sTable
     *
     * @return void
     */
    public function setTable($sTable)
    {
        $this->_sTable = $sTable;
    }

    /**
     * @var array
     */
    private $_params = [];

    /**
     * @return bool
     */
    public function isSearchHashtag()
    {
        $sSearch = Phpfox_Request::instance()->get('hashtagsearch');
        return ('hashtag' == Phpfox_Request::instance()->get('req1')) || !empty($sSearch);
    }

    /**
     * @return string
     */
    public function getSearchHashtag()
    {
        if (!$this->isSearchHashtag()) return '';
        $sRequest = (isset($_GET[PHPFOX_GET_METHOD]) ? $_GET[PHPFOX_GET_METHOD] : '');
        $sReq2 = '';
        if (!empty($sRequest)) {
            $aParts = explode('/', trim($sRequest, '/'));
            $iCnt = 0;
            // We have to count the "mobile" part as a req1
            // add one to the count
            $iCntTotal = 2;
            foreach ($aParts as $sPart) {
                $iCnt++;

                if ($iCnt === $iCntTotal) {
                    $sReq2 = $sPart;
                    break;
                }
            }
        }

        $sTag = (Phpfox_Request::instance()->get('hashtagsearch') ? Phpfox_Request::instance()->get('hashtagsearch') : urldecode($sReq2));
        return $sTag;
    }

    /**
     * @param null|int|array $iUserId
     * @param null|int       $iFeedId
     * @param int            $iPage
     * @param bool           $bForceReturn
     * @param bool           $bLimit
     * @param null|int       $iLastFeedId
     * @param int            $iSponsorFeedId
     *
     * @return array
     * @throws Exception
     */
    public function get($iUserId = null, $iFeedId = null, $iPage = 0, $bForceReturn = false, $bLimit = true, $iLastFeedId = null, $iSponsorFeedId = 0)
    {
        $params = [];
        if (is_array($iUserId)) {
            $params = $iUserId;
            $iUserId = null;
            if (isset($params['id'])) {
                $iFeedId = $params['id'];
            }

            if (isset($params['page'])) {
                $iPage = (int)$params['page'];
            }

            if (isset($params['user_id'])) {
                $iUserId = $params['user_id'];
            }
        }
        $this->_params = $params;
        $oReq = Phpfox_Request::instance();
        $bIsCheckForUpdate = defined('PHPFOX_CHECK_FOR_UPDATE_FEED') ? 1 : 0;
        $iLastFeedUpdate = defined('PHPFOX_CHECK_FOR_UPDATE_FEED_UPDATE') ? PHPFOX_CHECK_FOR_UPDATE_FEED_UPDATE : 0;
        $iLastStoreUpdate = Phpfox::getCookie('feed-last-check-id');
        if ($iLastFeedUpdate && $bIsCheckForUpdate && ($iLastStoreUpdate > $iLastFeedUpdate)) {
            $iLastFeedUpdate = $iLastStoreUpdate;
        }
        $iUserFeedSort = Phpfox::getUserBy('feed_sort');

        if ($iLastFeedUpdate != $iLastStoreUpdate) {
            Phpfox::removeCookie('feed-last-check-id');
            Phpfox::setCookie('feed-last-check-id', $iLastFeedUpdate);
        }

        if (!isset($params['bIsChildren']) || !$params['bIsChildren']) {
            if (($iCommentId = $oReq->getInt('comment-id'))) {
                if (isset($this->_aCallback['feed_comment'])) {
                    $aCustomCondition = ['feed.type_id = \'' . $this->_aCallback['feed_comment'] . '\' AND feed.item_id = ' . (int)$iCommentId . ' AND feed.parent_user_id = ' . (int)$this->_aCallback['item_id']];
                } else {
                    $aCustomCondition = ['feed.type_id IN(\'feed_comment\', \'feed_egift\') AND feed.item_id = ' . (int)$iCommentId . ' AND feed.parent_user_id = ' . (int)$iUserId];
                }

                $iFeedId = true;
            } else if (($iStatusId = $oReq->getInt('status-id'))) {
                $aCustomCondition = ['feed.type_id = \'user_status\' AND feed.item_id = ' . (int)$iStatusId . ' AND feed.user_id = ' . (int)$iUserId];
                $iFeedId = true;
            } else if (($iLinkId = $oReq->getInt('link-id'))) {
                $aCustomCondition = ['feed.type_id = \'link\' AND feed.item_id = ' . (int)$iLinkId];
                $iFeedId = true;
            } else if (($iPokeId = $oReq->getInt('poke-id'))) {
                $aCustomCondition = ['feed.type_id = \'poke\' AND feed.item_id = ' . (int)$iPokeId . ' AND feed.user_id = ' . (int)$iUserId];
                $iFeedId = true;
            }
        }

        $iTotalFeeds = (int)Phpfox::getComponentSetting(($iUserId === null ? Phpfox::getUserId() : $iUserId), 'feed.feed_display_limit_' . ($iUserId !== null ? 'profile' : 'dashboard'), Phpfox::getParam('feed.feed_display_limit'));
        if (isset($params['limit'])) {
            $iTotalFeeds = $params['limit'];
        }
        if (!$bLimit || (defined('FEED_LOAD_NEW_NEWS') && FEED_LOAD_NEW_NEWS)) {
            $iTotalFeeds = 101;
        }
        $sLoadMoreCond = null;
        $iOffset = (($iPage * $iTotalFeeds));
        if ($iOffset == '-1') {
            $iOffset = 0;
        }

        if ($iLastFeedId != null) {
            if ($iUserFeedSort || defined('PHPFOX_IS_USER_PROFILE')) {
                $iOffset = 0;
                $sLoadMoreCond = 'AND feed.feed_id < ' . (int)$iLastFeedId;
            } else {
                $aLastFeed = $this->getFeed($iLastFeedId);
                if (!empty($aLastFeed['time_update'])) {
                    $iOffset = 0;
                    $sLoadMoreCond = 'AND feed.time_update < ' . (int)$aLastFeed['time_update'];
                }
            }
        } else if (isset($params['order']) && $params['order'] == 'feed.total_view DESC' && isset($params['v_page'])) {
            $iOffset = (int)($params['v_page'] * $iTotalFeeds);
        } else if (isset($params['last-item']) && $params['last-item']) {
            $sLoadMoreCond = ' AND feed.feed_id < ' . (int)$params['last-item'];
        }
        $extra = '';

        if (Phpfox::isUser()) {
            $aBlockedUserIds = Phpfox::getService('user.block')->get(null, true);
            if (!empty($aBlockedUserIds)) {
                $extra .= ' AND feed.user_id NOT IN (' . implode(',', $aBlockedUserIds) . ')';
            }
        }

        if ($sLoadMoreCond != null) {
            $extra .= ' ' . $sLoadMoreCond;
        }
        (($sPlugin = Phpfox_Plugin::get('feed.service_feed_get_start')) ? eval($sPlugin) : false);

        if (isset($params['type_id'])) {
            $extra .= ' AND feed.type_id ' . (is_array($params['type_id']) ? 'IN(' . implode(',', array_map(function ($value) {
                        return "'{$value}'";
                    }, $params['type_id'])) . ')' : '= \'' . $params['type_id'] . '\'') . '';
        }
        //Do not hide feed when login as pages
        if (!Phpfox::getUserBy('profile_page_id') && defined('PHPFOX_IS_USER_PROFILE') && PHPFOX_IS_USER_PROFILE) {
            //Hide feed add on other user wall
            if (isset($iUserId)) {
                $extra .= ' AND (feed.parent_user_id=0 OR feed.parent_user_id = ' . (int)$iUserId . ')';
            }
        }

        // define order
        $sOrder = 'feed.time_update DESC';
        if ($iUserFeedSort || defined('PHPFOX_IS_USER_PROFILE')) {
            $sOrder = 'feed.time_stamp DESC';
        }
        if (isset($this->_params['order'])) {
            $sOrder = $this->_params['order'];
        }

        // define where for check update
        $aCheckCond = [];
        if ($bIsCheckForUpdate) {
            if ($iUserFeedSort || defined('PHPFOX_IS_USER_PROFILE')) {
                $aCheckCond[] = 'feed.time_stamp > ' . intval($iLastFeedUpdate);
            } else {
                $aCheckCond[] = 'feed.time_update > ' . intval($iLastFeedUpdate);
            }
        }

        $aCond = [];
        // check hidden feeds
        $aHiddenCond = Phpfox::getService('feed.hide')->getHideCondition();
        if ($aHiddenCond) {
            $aCheckCond = array_merge($aCheckCond, $aHiddenCond);
        }

        $checkWhere = '';
        if ($aCheckCond) {
            $checkWhere = implode(' AND ', $aCheckCond);
        }

        // Users must be active within 7 days or we skip their activity feed
        $iLastActiveTimeStamp = (((int)Phpfox::getParam('feed.feed_limit_days') <= 0 || !empty($this->_params['ignore_limit_feed'])) ? 0 : (PHPFOX_TIME - (86400 * Phpfox::getParam('feed.feed_limit_days'))));
        $is_app = false;
        if (isset($params['type_id']) && Phpfox::getCoreApp()->exists($params['type_id'])) {
            $is_app = true;
        }

        // get feeds
        if (isset($this->_aCallback['module'])) {
            $aNewCond = [];
            if (($iCommentId = $oReq->getInt('comment-id'))) {
                if (!isset($this->_aCallback['feed_comment'])) {
                    $aCustomCondition = ['feed.type_id = \'' . $this->_aCallback['module'] . '_comment\' AND feed.item_id = ' . (int)$iCommentId . ''];
                }
            }
            $aNewCond[] = 'AND feed.parent_user_id = ' . (int)$this->_aCallback['item_id'];
            if ($iUserId !== null && $iFeedId !== null) {
                $aNewCond[] = 'AND feed.feed_id = ' . (int)$iFeedId . ' AND feed.user_id = ' . (int)$iUserId;
            }

            if ($iUserId === null && $iFeedId !== null) {
                $aNewCond = [];
                $aNewCond[] = 'AND feed.feed_id = ' . (int)$iFeedId;
            }

            if (Phpfox::isUser()) {
                $aBlockedUserIds = Phpfox::getService('user.block')->get(null, true);
                if (!empty($aBlockedUserIds)) {
                    $aNewCond[] = 'AND feed.user_id NOT IN (' . implode(',', $aBlockedUserIds) . ')';
                    if (!empty($aCustomCondition)) {
                        $aCustomCondition[] = 'AND feed.user_id NOT IN (' . implode(',', $aBlockedUserIds) . ')';
                    }
                }
            }

            if ($iFeedId === null && is_string($extra) && !empty($extra)) {
                $aNewCond[] = $extra;
            }

            if (isset($this->_params['search']) && !empty($this->_params['search'])) {
                $aNewCond[] = 'AND feed.content LIKE \'%' . $this->database()->escape($this->_params['search']) . '%\'';
            }

            if (isset($this->_params['parent_feed_id']) && is_numeric($this->_params['parent_feed_id'])) {
                $aNewCond[] = 'AND feed.parent_feed_id = ' . $this->_params['parent_feed_id'];
            }

            if ($is_app && isset($this->_params['when']) && $this->_params['when']) {
                $iTimeDisplay = Phpfox::getLib('date')->mktime(0, 0, 0, Phpfox::getTime('m'), Phpfox::getTime('d'), Phpfox::getTime('Y'));
                switch ($params['when']) {
                    case 'today':
                        $iEndDay = Phpfox::getLib('date')->mktime(23, 59, 0, Phpfox::getTime('m'), Phpfox::getTime('d'), Phpfox::getTime('Y'));
                        $aNewCond[] = ' AND (' . 'feed.time_stamp' . ' >= \'' . Phpfox::getLib('date')->convertToGmt($iTimeDisplay) . '\' AND ' . 'feed.time_stamp' . ' < \'' . Phpfox::getLib('date')->convertToGmt($iEndDay) . '\')';
                        break;
                    case 'this-week':
                        $aNewCond[] = ' AND ' . 'feed.time_stamp' . ' >= ' . (int)Phpfox::getLib('date')->convertToGmt(Phpfox::getLib('date')->getWeekStart());
                        $aNewCond[] = ' AND ' . 'feed.time_stamp' . ' <= ' . (int)Phpfox::getLib('date')->convertToGmt(Phpfox::getLib('date')->getWeekEnd());
                        break;
                    case 'this-month':
                        $aNewCond[] = ' AND ' . 'feed.time_stamp' . ' >= \'' . Phpfox::getLib('date')->convertToGmt(Phpfox::getLib('date')->getThisMonth()) . '\'';
                        $iLastDayMonth = Phpfox::getLib('date')->mktime(0, 0, 0, date('n'), Phpfox::getLib('date')->lastDayOfMonth(date('n')), date('Y'));
                        $aNewCond[] = ' AND ' . 'feed.time_stamp' . ' <= \'' . Phpfox::getLib('date')->convertToGmt($iLastDayMonth) . '\'';
                        break;
                    default:
                        break;
                }
            }

            (($sPlugin = Phpfox_Plugin::get('feed.service_feed_get_parent_callback')) ? eval($sPlugin) : false);

            $aRows = $this->database()->select('feed.*, ' . Phpfox::getUserField() . ', u.view_id')
                ->from(Phpfox::getT((isset($this->_aCallback['table_prefix']) ? $this->_aCallback['table_prefix'] : '') . 'feed'), 'feed')
                ->join(Phpfox::getT('user'), 'u', 'u.user_id = feed.user_id')
                ->where((isset($aCustomCondition) ? $aCustomCondition : $aNewCond))
                ->order($sOrder)
                ->limit($iOffset, $iTotalFeeds, null, false, true)
                ->execute('getSlaveRows');

            // Fixes missing page_user_id, required to create the proper feed target
            if ($this->_aCallback['module'] == 'pages') {
                foreach ($aRows as $iKey => $aValue) {
                    $aRows[$iKey]['page_user_id'] = $iUserId;
                }
            }
        } // check feed id in exists list.
        else if ($iUserId === null && $iFeedId === null && ($sIds = $oReq->get('ids'))) {
            $aParts = explode(',', $oReq->get('ids'));
            $sNewIds = '';
            foreach ($aParts as $sPart) {
                $sNewIds .= (int)$sPart . ',';
            }
            $sNewIds = rtrim($sNewIds, ',');

            $aRows = $this->database()->select('feed.*, ' . Phpfox::getUserField() . ', u.view_id')
                ->from($this->_sTable, 'feed')
                ->join(Phpfox::getT('user'), 'u', 'u.user_id = feed.user_id')
                ->where('feed.feed_id IN(' . $sNewIds . ')')
                ->order('feed.time_stamp DESC')
                ->execute('getSlaveRows');
        } // get particular feed by id
        else if ($iUserId === null && $iFeedId !== null) {
            if (isset($this->_aCallback['module'])) {
                $this->_sTable = Phpfox::getT($this->_aCallback['table_prefix'] . 'feed');
            }

            $aRows = $this->database()->select('feed.*, ' . Phpfox::getUserField() . ', u.view_id')
                ->from($this->_sTable, 'feed')
                ->join(Phpfox::getT('user'), 'u', 'u.user_id = feed.user_id')
                ->where('feed.feed_id = ' . (int)$iFeedId)
                ->order('feed.time_stamp DESC')
                ->execute('getSlaveRows');
        } // get particular feed by id
        else if ($iUserId !== null && $iFeedId !== null) {
            $aRows = $this->database()->select('feed.*, ' . Phpfox::getUserField() . ', u.view_id')
                ->from($this->_sTable, 'feed')
                ->join(Phpfox::getT('user'), 'u', 'u.user_id = feed.user_id')
                ->where((isset($aCustomCondition) ? $aCustomCondition : 'feed.feed_id = ' . (int)$iFeedId . ' AND (feed.user_id = ' . (int)$iUserId . ' OR feed.parent_user_id = ' . (int)$iUserId . ')'))
                ->order('feed.time_stamp DESC')
                ->limit(1)
                ->execute('getSlaveRows');

            if (count($aRows)) {
                $aRow = $aRows[0];
                if ($aRow['user_id'] != Phpfox::getUserId() && $iUserId != Phpfox::getUserId()) {
                    switch ($aRow['privacy']) {
                        case 1:
                        case 2:
                            $oUserObject = Phpfox::getService('user')->getUserObject($iUserId);
                            if (isset($oUserObject->is_reverse_friend) && $oUserObject->is_reverse_friend) {
                                break;
                            } else if (isset($oUserObject->is_friend_of_friend) && $oUserObject->is_friend_of_friend && $aRow['privacy'] == 2) {
                                break;
                            }
                            $aRows = [];
                            break;
                        case 3:
                            $aRows = [];
                            break;
                        case 4:
                            // --- Get feeds based on custom friends lists ---
                            if (Phpfox::isUser()) {
                                if (Phpfox::isModule('privacy')) {
                                    $this->database()->join(Phpfox::getT('privacy'), 'p', 'p.module_id = feed.type_id AND p.item_id = feed.item_id')
                                        ->join(Phpfox::getT('friend_list_data'), 'fld', 'fld.list_id = p.friend_list_id AND fld.friend_user_id = ' . Phpfox::getUserId() . '');

                                }
                                $checkFeedId = $this->database()->select('feed_id')
                                    ->from($this->_sTable, 'feed')
                                    ->where('feed.feed_id = ' . $aRow['feed_id'])
                                    ->limit(1)
                                    ->execute('getSlaveField');
                                if ($checkFeedId) {
                                    break;
                                }
                            }
                            $aRows = [];
                            break;
                        case 6:
                            if (!Phpfox::isUser()) {
                                $aRows = [];
                            }
                            break;
                    }
                }
            }

        } // get feed on particular profile, does not need to improve.
        else if ($iUserId !== null) {
            $privacyCond = [];
            $isUser = Phpfox::isUser();
            if ($iUserId == Phpfox::getUserId()) {
                $privacyCond[] = 'AND feed.privacy IN(0,1,2,3,4,6)';
            } else {
                $oUserObject = Phpfox::getService('user')->getUserObject($iUserId);
                if (isset($oUserObject->is_reverse_friend) && $oUserObject->is_reverse_friend) {
                    $privacyCond[] = 'AND feed.privacy IN(0,1,2' . ($isUser ? ',6' : '') . ')';
                } else if (isset($oUserObject->is_friend_of_friend) && $oUserObject->is_friend_of_friend) {
                    $privacyCond[] = 'AND feed.privacy IN(0,2' . ($isUser ? ',6' : '') . ')';
                } else {
                    $privacyCond[] = 'AND feed.privacy IN(0' . ($isUser ? ',6' : '') . ')';
                }
            }
            $aCond[] = $extra;

            if (isset($this->_params['search']) && !empty($this->_params['search'])) {
                $aCond[] = 'AND feed.content LIKE \'%' . $this->database()->escape($this->_params['search']) . '%\'';
            }

            if (isset($this->_params['parent_feed_id']) && is_numeric($this->_params['parent_feed_id'])) {
                $aCond[] = 'AND feed.parent_feed_id = ' . $this->_params['parent_feed_id'];
            }

            if (!$this->_params) {
                // There is no reciprocal feed when you add someone as friend
                if (isset($this->_params['search']) && !empty($this->_params['search'])) {
                    $this->database()->join(Phpfox::getT('feed'), 'feed_search', 'feed_search.feed_id = feed.feed_id AND feed_search.content LIKE \'%' . $this->database()->escape($this->_params['search']) . '%\'');
                }
                $this->database()->select('DISTINCT feed.*')
                    ->from($this->_sTable, 'feed')
                    ->where(array_merge($aCond, $privacyCond, ['AND type_id = \'friend\' AND feed.user_id = ' . (int)$iUserId . '']))
                    ->order($sOrder)
                    ->limit($iOffset, $iTotalFeeds, null, false, true)
                    ->union();
            }

            (($sPlugin = Phpfox_Plugin::get('feed.service_feed_get_userprofile')) ? eval($sPlugin) : '');

            $this->database()->select('DISTINCT feed.*')
                ->from($this->_sTable, 'feed')
                ->where(array_merge($aCond, $privacyCond, ['AND type_id = \'feed_comment\' AND feed.user_id = ' . (int)$iUserId . '']))
                ->order($sOrder)
                ->limit($iOffset, $iTotalFeeds, null, false, true)
                ->union();

            $this->database()->select('DISTINCT feed.*')
                ->from($this->_sTable, 'feed')
                ->where(array_merge($aCond, $privacyCond, ['AND feed.user_id = ' . (int)$iUserId . ' AND feed.feed_reference = 0 AND feed.parent_user_id = 0']))
                ->order($sOrder)
                ->limit($iOffset, $iTotalFeeds, null, false, true)
                ->union();

            // --- Get feeds based on custom friends lists ---
            if (Phpfox::isUser()) {
                if (Phpfox::isModule('privacy')) {
                    $this->database()->join(Phpfox::getT('privacy'), 'p', 'p.module_id = feed.type_id AND p.item_id = feed.item_id')
                        ->join(Phpfox::getT('friend_list_data'), 'fld', 'fld.list_id = p.friend_list_id AND fld.friend_user_id = ' . Phpfox::getUserId() . '');
                }

                $this->database()->select('DISTINCT feed.*')
                    ->from($this->_sTable, 'feed')
                    ->where(array_merge($aCond, ['AND feed.privacy IN(4) AND feed.user_id = ' . (int)$iUserId . ' AND feed.feed_reference = 0']))
                    ->order($sOrder)
                    ->limit($iOffset, $iTotalFeeds, null, false, true)
                    ->union();
            }

            $this->database()->select('DISTINCT feed.*')
                ->from($this->_sTable, 'feed')
                ->where(array_merge($aCond, $privacyCond, ['AND feed.parent_user_id = ' . (int)$iUserId]))
                ->order($sOrder)
                ->limit($iOffset, $iTotalFeeds, null, false, true)
                ->union();

            $aRows = $this->database()->select('feed.*, ' . Phpfox::getUserField())
                ->unionFrom('feed')
                ->join(Phpfox::getT('user'), 'u', 'u.user_id = feed.user_id')
                ->order($sOrder)
                ->limit(0, $iTotalFeeds, null, false, true)
                ->execute('getSlaveRows');
        } else if (
            // get main feed on "feed_only_friends" ON.
            // case 01.
            ((Phpfox::getParam('feed.feed_only_friends') && !$is_app)
                || Phpfox::getParam('core.friends_only_community')
                || isset($this->_params['friends']))
            && !$this->isSearchHashtag()) {
            if (isset($this->_params['search']) && !empty($this->_params['search'])) {
                $extra .= ' AND feed.content LIKE \'%' . $this->database()->escape($this->_params['search']) . '%\'';
            }

            if (isset($this->_params['parent_feed_id']) && is_numeric($this->_params['parent_feed_id'])) {
                $extra .= ' AND feed.parent_feed_id = ' . $this->_params['parent_feed_id'];
            }

            if (!empty($checkWhere)) {
                $extra .= ' AND ' . $checkWhere;
            }

            $isLogined = Phpfox::isUser();
            $friendActive = Phpfox::isModule('friend');

            if ($isLogined) {
                if ($friendActive) {
                    // get my friend feeds
                    if ($sOrder == 'feed.time_update DESC') {
                        $this->database()->forceIndex('time_update');
                    }
                    if (isset($this->_params['join_query']) && is_callable($this->_params['join_query'])) {
                        call_user_func($this->_params['join_query']);
                    }
                    $this->database()->select('DISTINCT feed.*')
                        ->from($this->_sTable, 'feed')
                        ->join(Phpfox::getT('friend'), 'f', 'f.user_id = feed.user_id AND f.friend_user_id = ' . Phpfox::getUserId())
                        ->where('feed.privacy IN(0,1,2,6) ' . $extra . ' AND feed.time_stamp > \'' . $iLastActiveTimeStamp . '\' AND feed.feed_reference = 0')
                        ->order($sOrder)
                        ->limit($iOffset, $iTotalFeeds, null, false, true)
                        ->union();
                }

                // Get feeds based on custom friends lists
                if (Phpfox::isModule('privacy')) {
                    $this->database()->join(Phpfox::getT('privacy'), 'p', 'p.module_id = feed.type_id AND p.item_id = feed.item_id')
                        ->join(Phpfox::getT('friend_list_data'), 'fld', 'fld.list_id = p.friend_list_id AND fld.friend_user_id = ' . Phpfox::getUserId() . '');

                }
                $this->database()->select('DISTINCT feed.*')
                    ->from($this->_sTable, 'feed')
                    ->join(Phpfox::getT('friend'), 'f', 'f.user_id = feed.user_id AND f.friend_user_id = ' . Phpfox::getUserId())
                    ->where('feed.privacy IN(4) ' . $extra . ' AND feed.time_stamp > \'' . $iLastActiveTimeStamp . '\' AND feed.feed_reference = 0 ')
                    ->order($sOrder)
                    ->limit($iOffset, $iTotalFeeds, null, false, true)
                    ->union();

                // Get my feeds
                if (!isset($this->_params['friends'])) {
                    if (isset($this->_params['join_query']) && is_callable($this->_params['join_query'])) {
                        call_user_func($this->_params['join_query']);
                    }
                    $this->database()->select('DISTINCT feed.*')
                        ->from($this->_sTable, 'feed')
                        ->forceIndex('user_id')
                        ->where('feed.privacy IN(0,1,2,3,4,6) ' . $extra . ' AND feed.user_id = ' . Phpfox::getUserId() . ' AND feed.time_stamp > \'' . $iLastActiveTimeStamp . '\' AND feed.feed_reference = 0')
                        ->order($sOrder)
                        ->limit($iOffset, $iTotalFeeds, null, false, true)
                        ->union();
                }

                if (empty($this->_aCallback['module'])) {
                    $pageFeedTable = Phpfox::getT('pages_feed');
                    $pageTable = Phpfox::getT('pages');
                    $likeTable = Phpfox::getT('like');
                    $parentTypes = [];
                    $where = [
                        'AND feed.time_stamp > \'' . $iLastActiveTimeStamp . '\' AND feed.feed_reference = 0 ' . $extra
                    ];

                    if (Phpfox::isAppActive('Core_Pages')) {
                        $parentTypes[] = 'pages';
                    }
                    if (Phpfox::isAppActive('PHPfox_Groups')) {
                        $parentTypes[] = 'groups';
                    }

                    if (!empty($parentTypes)) {
                        foreach ($parentTypes as $parentType) {
                            if (in_array($parentType, ['groups', 'pages'])) {
                                // Get liked page and joined group feeds
                                $this->database()->select('DISTINCT feed.*')
                                    ->from($this->_sTable, 'feed')
                                    ->join($pageFeedTable, 'page_feed', 'page_feed.type_id = feed.type_id AND page_feed.item_id = feed.item_id')
                                    ->join($pageTable, 'page', 'page.page_id = page_feed.parent_user_id AND page.item_type = ' . ($parentType == 'pages' ? 0 : 1))
                                    ->join($likeTable, 'like_feed', 'like_feed.item_id = page.page_id AND like_feed.type_id = "' . $parentType . '" AND like_feed.user_id = ' . Phpfox::getUserId())
                                    ->where($where)
                                    ->order($sOrder)
                                    ->limit($iOffset, $iTotalFeeds, null, false, true)
                                    ->union();
                            }
                        }
                    }
                }
            }

            $sSelect = 'feed.*, u.view_id,  ' . Phpfox::getUserField();
            if ($isLogined && $friendActive) {
                $sSelect .= ', f.friend_id AS is_friend';
                $this->database()->leftJoin(Phpfox::getT('friend'), 'f', 'f.user_id = feed.user_id AND f.friend_user_id = ' . Phpfox::getUserId())
                    ->limit($iOffset, $iTotalFeeds, null, false, true)
                    ->order($sOrder);
            }

            $aRows = $this->database()->select($sSelect)
                ->unionFrom('feed')
                ->join(Phpfox::getT('user'), 'u', 'u.user_id = feed.user_id')
                ->where($checkWhere)
                ->order($sOrder)
                ->group('feed.feed_id')
                ->limit(0, $iTotalFeeds)
                ->execute('getSlaveRows');

        } else if (!$this->isSearchHashtag()) {
            // no search
            $sMoreWhere = '';
            if ($checkWhere) {
                $sMoreWhere = ' AND ' . $checkWhere;
            }

            (($sPlugin = Phpfox_Plugin::get('feed.service_feed_get_buildquery')) ? eval($sPlugin) : '');

            if (isset($this->_params['search']) && !empty($this->_params['search']) && is_scalar($this->_params['search'])) {
                $extra .= ' AND feed.content LIKE \'%' . $this->database()->escape($this->_params['search']) . '%\'';
            }

            if (isset($this->_params['parent_feed_id']) && is_numeric($this->_params['parent_feed_id'])) {
                $extra .= ' AND feed.parent_feed_id = ' . $this->_params['parent_feed_id'];
            }

            if ($is_app && isset($this->_params['when']) && $this->_params['when']) {
                $iTimeDisplay = Phpfox::getLib('date')->mktime(0, 0, 0, Phpfox::getTime('m'), Phpfox::getTime('d'), Phpfox::getTime('Y'));
                switch ($params['when']) {
                    case 'today':
                        $iEndDay = Phpfox::getLib('date')->mktime(23, 59, 0, Phpfox::getTime('m'), Phpfox::getTime('d'), Phpfox::getTime('Y'));
                        $extra .= ' AND (' . 'feed.time_stamp' . ' >= \'' . Phpfox::getLib('date')->convertToGmt($iTimeDisplay) . '\' AND ' . 'feed.time_stamp' . ' < \'' . Phpfox::getLib('date')->convertToGmt($iEndDay) . '\')';
                        break;
                    case 'this-week':
                        $extra .= ' AND ' . 'feed.time_stamp' . ' >= ' . (int)Phpfox::getLib('date')->convertToGmt(Phpfox::getLib('date')->getWeekStart());
                        $extra .= ' AND ' . 'feed.time_stamp' . ' <= ' . (int)Phpfox::getLib('date')->convertToGmt(Phpfox::getLib('date')->getWeekEnd());
                        break;
                    case 'this-month':
                        $extra .= ' AND ' . 'feed.time_stamp' . ' >= \'' . Phpfox::getLib('date')->convertToGmt(Phpfox::getLib('date')->getThisMonth()) . '\'';
                        $iLastDayMonth = Phpfox::getLib('date')->mktime(0, 0, 0, date('n'), Phpfox::getLib('date')->lastDayOfMonth(date('n')), date('Y'));
                        $extra .= ' AND ' . 'feed.time_stamp' . ' <= \'' . Phpfox::getLib('date')->convertToGmt($iLastDayMonth) . '\'';
                        break;
                    default:
                        break;
                }
            }

            // --- Get my friends feeds ---
            if (Phpfox::isUser() && Phpfox::isModule('friend')) {
                if (isset($this->_params['join_query']) && is_callable($this->_params['join_query'])) {
                    call_user_func($this->_params['join_query']);
                }
                $this->database()->select('DISTINCT feed.*')
                    ->from($this->_sTable, 'feed')
                    ->join(Phpfox::getT('friend'), 'f', 'f.user_id = feed.user_id AND f.friend_user_id = ' . Phpfox::getUserId())
                    ->where('feed.privacy IN(1,2) ' . $extra . ' AND feed.time_stamp > \'' . $iLastActiveTimeStamp . '\' AND feed.feed_reference = 0' . $sMoreWhere)
                    ->order($sOrder)
                    ->limit($iOffset, $iTotalFeeds, null, false, true)
                    ->group('feed.feed_id')
                    ->union();

                // Get my friends of friends feeds
                if (isset($this->_params['join_query']) && is_callable($this->_params['join_query'])) {
                    call_user_func($this->_params['join_query']);
                }
                $this->database()->select('DISTINCT feed.*')
                    ->from($this->_sTable, 'feed')
                    ->join(Phpfox::getT('friend'), 'f1', 'f1.user_id = feed.user_id')
                    ->join(Phpfox::getT('friend'), 'f2', 'f2.user_id = ' . Phpfox::getUserId() . ' AND f2.friend_user_id = f1.friend_user_id')
                    ->where('feed.privacy IN(2) ' . $extra . ' AND feed.time_stamp > \'' . $iLastActiveTimeStamp . '\' AND feed.feed_reference = 0' . $sMoreWhere)
                    ->group('feed.feed_id')
                    ->order($sOrder)
                    ->limit($iOffset, $iTotalFeeds, null, false, true)
                    ->union();
            }

            // --- Get my feeds ---
            if (Phpfox::isUser()) {
                if (isset($this->_params['join_query']) && is_callable($this->_params['join_query'])) {
                    call_user_func($this->_params['join_query']);
                }

                $this->database()->select('DISTINCT feed.*')
                    ->from($this->_sTable, 'feed')
                    ->where('feed.privacy IN(1,2,3,4) ' . $extra . ' AND feed.user_id = ' . Phpfox::getUserId() . ' AND feed.time_stamp > \'' . $iLastActiveTimeStamp . '\' AND feed.feed_reference = 0' . $sMoreWhere)
                    ->order($sOrder)
                    ->limit($iOffset, $iTotalFeeds, null, false, true)
                    ->union();
            }

            // --- Get public feeds ---
            if (isset($this->_params['join_query']) && is_callable($this->_params['join_query'])) {
                call_user_func($this->_params['join_query']);
            }
            $this->database()->select('DISTINCT feed.*')
                ->from($this->_sTable, 'feed')
                ->where('feed.privacy IN(0) ' . $extra . ' AND feed.time_stamp > \'' . $iLastActiveTimeStamp . '\' AND feed.feed_reference = 0' . $sMoreWhere)
                ->order($sOrder)
                ->limit($iOffset, $iTotalFeeds, null, false, true)
                ->union();

            if (isset($this->_params['join_query']) && is_callable($this->_params['join_query'])) {
                call_user_func($this->_params['join_query']);
            }

            // --- Get feeds based on custom friends lists ---
            if (Phpfox::isUser()) {
                // --- Get community feed ---
                $this->database()->select('DISTINCT feed.*')
                    ->from($this->_sTable, 'feed')
                    ->where('feed.privacy IN(6) ' . $extra . ' AND feed.time_stamp > \'' . $iLastActiveTimeStamp . '\' AND feed.feed_reference = 0' . $sMoreWhere)
                    ->order($sOrder)
                    ->limit($iOffset, $iTotalFeeds, null, false, true)
                    ->union();

                if (Phpfox::isModule('privacy')) {
                    $this->database()->join(Phpfox::getT('privacy'), 'p', 'p.module_id = feed.type_id AND p.item_id = feed.item_id')
                        ->join(Phpfox::getT('friend_list_data'), 'fld', 'fld.list_id = p.friend_list_id AND fld.friend_user_id = ' . Phpfox::getUserId() . '');

                }
                $this->database()->select('DISTINCT feed.*')
                    ->from($this->_sTable, 'feed')
                    ->where('feed.privacy IN(4) ' . $extra . ' AND feed.time_stamp > \'' . $iLastActiveTimeStamp . '\' AND feed.feed_reference = 0 ' . $sMoreWhere)
                    ->order($sOrder)
                    ->limit($iOffset, $iTotalFeeds, null, false, true)
                    ->union();
            }

            $sSelect = 'feed.*, u.view_id,  ' . Phpfox::getUserField();
            if (Phpfox::isUser() && Phpfox::isModule('friend')) {
                $sSelect .= ', f.friend_id AS is_friend';
                $this->database()->leftJoin(Phpfox::getT('friend'), 'f', 'f.user_id = feed.user_id AND f.friend_user_id = ' . Phpfox::getUserId());
            }

            $aRows = $this->database()->select($sSelect)
                ->unionFrom('feed')
                ->join(Phpfox::getT('user'), 'u', 'u.user_id = feed.user_id')
                ->where($checkWhere)
                ->order($sOrder)
                ->limit(0, $iTotalFeeds)
                ->execute('getSlaveRows');

        } else {
            // Search hashtag
            $sOrder = 'feed.time_update DESC';

            $sTag = $this->getSearchHashtag();
            $sTag = \Phpfox_Parse_Output::instance()->parse($sTag);
            $sTag = Phpfox::getLib('parse.input')->clean($sTag, 255);
            $sTag = mb_convert_case($sTag, MB_CASE_LOWER, "UTF-8");
            $sTag = Phpfox_Database::instance()->escape($sTag);

            $sMoreWhere = '';
            if ($checkWhere) {
                $sMoreWhere = ' AND ' . $checkWhere;
            }
            $sMyFeeds = '0,1,2,3,4,6';

            (($sPlugin = Phpfox_Plugin::get('feed.service_feed_get_buildquery')) ? eval($sPlugin) : '');

            if (Phpfox::isUser()) {
                if (Phpfox::isModule('friend')) {
                    db()->select('DISTINCT feed.*')
                        ->from($this->_sTable, 'feed')
                        ->join(Phpfox::getT('friend'), 'f', 'f.user_id = feed.user_id AND f.friend_user_id = ' . Phpfox::getUserId())
                        ->where('feed.privacy IN(1,2) ' . $extra . ' AND feed.time_stamp > \'' . $iLastActiveTimeStamp . '\'' . $sMoreWhere)
                        ->union();

                    // Get my friends of friends feeds
                    db()->select('DISTINCT feed.*')
                        ->from($this->_sTable, 'feed')
                        ->join(Phpfox::getT('friend'), 'f1', 'f1.user_id = feed.user_id')
                        ->join(Phpfox::getT('friend'), 'f2', 'f2.user_id = ' . Phpfox::getUserId() . ' AND f2.friend_user_id = f1.friend_user_id')
                        ->where('feed.privacy IN(2) ' . $extra . ' AND feed.time_stamp > \'' . $iLastActiveTimeStamp . '\'' . $sMoreWhere)
                        ->union();
                }

                // Get my feeds
                db()->select('DISTINCT feed.*')
                    ->from($this->_sTable, 'feed')
                    ->where('feed.privacy IN(' . $sMyFeeds . ') ' . $extra . ' AND feed.user_id = ' . Phpfox::getUserId() . ' AND feed.time_stamp > \'' . $iLastActiveTimeStamp . '\'' . $sMoreWhere)
                    ->union();

                // Get feeds based on custom friends lists
                if (Phpfox::isModule('privacy')) {
                    db()->join(Phpfox::getT('privacy'), 'p', 'p.module_id = feed.type_id AND p.item_id = feed.item_id')
                        ->join(Phpfox::getT('friend_list_data'), 'fld', 'fld.list_id = p.friend_list_id AND fld.friend_user_id = ' . Phpfox::getUserId() . '');
                }
                db()->select('DISTINCT feed.*')
                    ->from($this->_sTable, 'feed')
                    ->where('feed.privacy IN(4) ' . $extra . ' AND feed.time_stamp > \'' . $iLastActiveTimeStamp . '\'' . $sMoreWhere)
                    ->union();

                // --- Get community feed ---
                $this->database()->select('DISTINCT feed.*')
                    ->from($this->_sTable, 'feed')
                    ->where('feed.privacy IN(6) ' . $extra . ' AND feed.time_stamp > \'' . $iLastActiveTimeStamp . '\' AND feed.feed_reference = 0' . $sMoreWhere)
                    ->union();
            }

            // Get public feeds
            db()->select('DISTINCT feed.*')
                ->from($this->_sTable, 'feed')
                ->where('feed.privacy IN(0) ' . $extra . ' AND feed.time_stamp > \'' . $iLastActiveTimeStamp . '\' AND feed.feed_reference = 0' . $sMoreWhere)
                ->union()
                ->unionFrom('feed');

            $sSelect = 'feed.*, u.view_id,  ' . Phpfox::getUserField();
            if (Phpfox::isUser() && Phpfox::isModule('friend')) {
                $sSelect .= ', f.friend_id AS is_friend';
                $this->database()->leftJoin(Phpfox::getT('friend'), 'f', 'f.user_id = feed.user_id AND f.friend_user_id = ' . Phpfox::getUserId());
            }

            $aRows = $this->database()->select($sSelect)
                ->join(Phpfox::getT('user'), 'u', 'u.user_id = feed.user_id')
                ->join(Phpfox::getT('tag'), 'hashtag', 'hashtag.item_id = feed.item_id AND hashtag.category_id = feed.type_id AND feed.feed_reference = 0 AND hashtag.tag_type = 1 AND (tag_text = \'' . $sTag . '\' OR tag_url = \'' . $sTag . '\')')
                ->order($sOrder)
                ->limit($iOffset, $iTotalFeeds, null, false, true)
                ->execute('getSlaveRows');
        }

        if ($bForceReturn === true) {
            return $aRows;
        }

        $bFirstCheckOnComments = false;
        if (Phpfox::isUser() && Phpfox::isModule('comment')) {
            $bFirstCheckOnComments = true;
        }

        $aFeeds = [];
        $aParentFeeds = [];
        $sHandleCommentFeedModuleId = 'comment';
        $aDefaultStatusFeedComments = ['feed', 'pages', 'groups', 'event'];

        (($sPlugin = Phpfox_Plugin::get('feed.service_feed_get_before_parse_item')) ? eval($sPlugin) : false);

        foreach ($aRows as $sKey => $aRow) {
            if ($iLastFeedId) $iLastFeedId = $aRow['feed_id'];
            if ($aRow['parent_module_id'] && !Phpfox::hasCallback($aRow['parent_module_id'], 'getActivityFeed')) continue;
            $aRow['feed_time_stamp'] = $aRow['time_stamp'];

            if (($aReturn = $this->_processFeed($aRow, $sKey, $iUserId, $bFirstCheckOnComments, $iSponsorFeedId))) {
                if (isset($aReturn['force_user'])) {
                    $aReturn['user_name'] = $aReturn['force_user']['user_name'];
                    $aReturn['full_name'] = $aReturn['force_user']['full_name'];
                    $aReturn['user_image'] = $aReturn['force_user']['user_image'];
                    $aReturn['server_id'] = $aReturn['force_user']['server_id'];
                }

                $aReturn['feed_month_year'] = date('m_Y', $aRow['feed_time_stamp']);
                $aReturn['feed_time_stamp'] = $aRow['feed_time_stamp'];

                /* Lets figure out the phrases for like.display right here */
                if (Phpfox::isModule('like')) {
                    $this->getPhraseForLikes($aReturn);
                }

                if (isset($aReturn['feed_status'])
                    && $aReturn['feed_status'] != ''
                    && $aRow['type_id']
                    && preg_match('/^(.*)_comment$/', $aRow['type_id'], $matches)
                    && !in_array($matches[1], $aDefaultStatusFeedComments)
                    && Phpfox::isModule($sHandleCommentFeedModuleId)
                    && Phpfox::hasCallback($sHandleCommentFeedModuleId, 'handleCommentFeedStatus')) {
                    $aReturn = array_merge($aReturn, [
                        'feed_status' => Phpfox::callback($sHandleCommentFeedModuleId . '.handleCommentFeedStatus', $aReturn['feed_status']),
                        'is_feed_status_handled' => true,
                    ]);
                }

                $aFeeds[] = $aReturn;
            }

            // Show the feed properly. If user A posted on page 1, then feed will say "user A > page 1 posted ..."
            $aCustomModule = [
                'pages',
                'groups',
            ];
            (($sPlugin = Phpfox_Plugin::get('feed.service_feed_get_custom_module')) ? eval($sPlugin) : false);

            if (isset($this->_aCallback['module']) && in_array($this->_aCallback['module'], $aCustomModule)) {
                // If defined parent user, and the parent user is not the same page (logged in as a page)
                if (isset($aRow['page_user_id']) && $aReturn['page_user_id'] != $aReturn['user_id']) {
                    $aParentFeeds[$aReturn['feed_id']] = $aRow['page_user_id'];
                }
            } else if (isset($this->_aCallback['module']) && $this->_aCallback['module'] == 'event') {
                // Keep it empty
                $aParentFeeds = [];
            } else if (isset($aRow['parent_user_id']) && !isset($aRow['parent_user']) && $aRow['type_id'] != 'friend') {
                if (!empty($aRow['parent_user_id'])) {
                    $aParentFeeds[$aRow['feed_id']] = $aRow['parent_user_id'];
                }
            }
        }

        if (empty($aFeeds) && (count($aRows) == $iTotalFeeds)) {
            return $this->get($iUserId, $iFeedId, ++$iPage, $bForceReturn, $bLimit, $iLastFeedId);
        }

        // remove parent user when open user profile or feed detail
        if ($iFeedId && count($aRows)) {
            $aFeed = $aRows[0];
            $iUserId = $aFeed['parent_user_id'];
        }
        if ($iUserId) { // check user id
            foreach ($aParentFeeds as $key => $iParentId) {
                if ($iParentId == $iUserId) {
                    unset($aParentFeeds[$key]);
                }
            }
        }

        // Get the parents for the feeds so it displays arrow.png
        if (!empty($aParentFeeds)) {
            $search = implode(',', array_values($aParentFeeds));
            if (!empty($search)) {
                $aParentUsers = $this->database()->select(Phpfox::getUserField())
                    ->from(Phpfox::getT('user'), 'u')
                    ->where('user_id IN (' . $search . ')')
                    ->execute('getSlaveRows');

                $aFeedsWithParents = array_keys($aParentFeeds);
                foreach ($aFeeds as $sKey => $aRow) {
                    if (in_array($aRow['feed_id'], $aFeedsWithParents) && $aRow['type_id'] != 'photo_tag' && empty($aFeeds[$sKey]['friends_tagged'])) {
                        foreach ($aParentUsers as $aUser) {
                            if ($aUser['user_id'] == $aRow['parent_user_id']) {
                                $aTempUser = [];
                                foreach ($aUser as $sField => $sVal) {
                                    $aTempUser['parent_' . $sField] = $sVal;
                                }
                                $aFeeds[$sKey]['parent_user'] = $aTempUser;
                            }
                        }
                        // get tagged users
                        $aFeeds[$sKey]['total_friends_tagged'] = Phpfox::getService('feed.tag')->getTaggedUsers($aRow['item_id'], $aRow['type_id'], true);
                        if ($aFeeds[$sKey]['total_friends_tagged']) {
                            $aFeeds[$sKey]['friends_tagged'] = Phpfox::getService('feed.tag')->getTaggedUsers($aRow['item_id'], $aRow['type_id'], false, 1, 2);
                        }
                    }
                }
            }
        }

        $oReq = Phpfox_Request::instance();
        if (($oReq->getInt('status-id')
                || $oReq->getInt('comment-id')
                || $oReq->getInt('link-id')
                || $oReq->getInt('poke-id')
            )
            && isset($aFeeds[0])
        ) {
            $aFeeds[0]['feed_view_comment'] = true;
        }
        return $aFeeds;
    }

    /**
     * @return void
     */
    public function _hashSearch()
    {
        if (Phpfox_Request::instance()->get('req1') != 'hashtag' && Phpfox_Request::instance()->get('hashtagsearch') == '') {
            if (isset($this->_params['search'])) {
                $this->database()->join(Phpfox::getT('feed'), 'feed_search', 'feed_search.feed_id = feed.feed_id AND feed_search.content LIKE \'%' . $this->database()->escape($this->_params['search']) . '%\'');
            }

            return;
        }


        $sRequest = (isset($_GET[PHPFOX_GET_METHOD]) ? $_GET[PHPFOX_GET_METHOD] : '');
        $sReq2 = '';
        if (!empty($sRequest)) {
            $aParts = explode('/', trim($sRequest, '/'));
            $iCnt = 0;
            // We have to count the "mobile" part as a req1
            // add one to the count
            $iCntTotal = 2;
            foreach ($aParts as $sPart) {
                $iCnt++;

                if ($iCnt === $iCntTotal) {
                    $sReq2 = $sPart;
                    break;
                }
            }
        }

        $sTag = (Phpfox_Request::instance()->get('hashtagsearch') ? Phpfox_Request::instance()->get('hashtagsearch') : $sReq2);
        $sTag = \Phpfox_Parse_Output::instance()->parse($sTag);
        $sTag = urldecode($sTag);
        if (empty($sTag)) {
            return;
        }

        $sTag = Phpfox::getLib('parse.input')->clean($sTag, 255);
        $sTag = mb_convert_case($sTag, MB_CASE_LOWER, "UTF-8");

        $this->database()->join(Phpfox::getT('tag'), 'hashtag', 'hashtag.item_id = feed.item_id AND hashtag.category_id = feed.type_id AND tag_type = 1 AND (tag_text = \'' . Phpfox_Database::instance()->escape($sTag) . '\' OR tag_url = \'' . Phpfox_Database::instance()->escape($sTag) . '\')');
    }

    /**
     * @param      $aFeed
     * @param bool $bForce
     *
     * @return string
     * @throws Exception
     */
    public function getPhraseForLikes(&$aFeed, $bForce = false)
    {
        if (!Phpfox::isModule('like')) {
            return '';
        }
        $iCountLikes = (isset($aFeed['likes']) && !empty($aFeed['likes'])) ? count($aFeed['likes']) : 0;
        $sOriginalIsLiked = ((isset($aFeed['feed_is_liked']) && $aFeed['feed_is_liked']) ? $aFeed['feed_is_liked'] : '');
        if (!isset($aFeed['feed_total_like'])) {
            $aFeed['feed_total_like'] = $iCountLikes;
        }

        if (!isset($aFeed['like_type_id'])) {
            $aFeed['like_type_id'] = isset($aFeed['type_id']) ? $aFeed['type_id'] : null;
        }
        if (!isset($aFeed['like_item_id'])) {
            $aFeed['like_item_id'] = isset($aFeed['item_id']) ? $aFeed['item_id'] : 0;
        }

        $sPhrase = '<span class="people-liked-feed">';
        $oLike = Phpfox::getService('like');

        if ((empty($aFeed['likes']) && isset($oLike))) {
            $aFeed['likes'] = $oLike->getLikesForFeed($aFeed['like_type_id'], $aFeed['like_item_id'], false, 2, false, (isset($aFeed['feed_table_prefix']) ? $aFeed['feed_table_prefix'] : ''));
            $aFeed['total_likes'] = $iCountLikes;
        }

        $oUrl = Phpfox_Url::instance();
        $iPhraseLimiter = 2;
        $iIteration = 0;

        $aLikes = [];
        if ($iCountLikes > 0) {
            foreach ($aFeed['likes'] as $aLike) {
                if ($iIteration >= $iPhraseLimiter) {
                    break;
                } else {
                    if (empty($aLike['is_friend']) || $aLike['user_id'] == Phpfox::getUserId()) {
                        continue;
                    }
                    $aLike['full_name'] = Phpfox::getLib('parse.output')->clean($aLike['full_name']);
                    if (Phpfox::isUser() && Phpfox::getService('user.block')->isBlocked(null, $aLike['user_id'])) {
                        $sUserLink = '<span class="user_profile_link_span" id="js_user_name_link_' . $aLike['user_name'] . '">' . $aLike['full_name'] . '</span>';
                    } else {
                        $sUserLink = '<span class="user_profile_link_span" id="js_user_name_link_' . $aLike['user_name'] . '"><a href="' . $oUrl->makeUrl($aLike['user_name']) . '">' . $aLike['full_name'] . '</a></span>';
                    }
                    $aLikes[] = $sUserLink;
                    $iIteration++;
                }
            }
        }

        $bDidILikeIt = false;
        /* Check to see if I liked this */
        if (!isset($aFeed['feed_is_liked']) && isset($oLike)) {
            $aFeed['feed_is_liked'] = $oLike->didILike($aFeed['like_type_id'], $aFeed['like_item_id'], [], (isset($aFeed['feed_table_prefix']) ? $aFeed['feed_table_prefix'] : ''));
        }

        if ($aFeed['feed_total_like'] < $iCountLikes) {
            $aFeed['feed_total_like'] = $iCountLikes;
        }

        if (isset($aFeed['feed_is_liked']) && $aFeed['feed_is_liked']) {
            if ($iPhraseLimiter == 1 || $iPhraseLimiter == 2) {
                if ($aFeed['feed_total_like'] == 2 && $iIteration == 1) {
                    $sPhrase .= _p('you_and') . '&nbsp;';
                } else {
                    if ($iIteration > 1) {
                        $sPhrase .= _p('you_comma') . '&nbsp;';
                    } else {
                        $sPhrase .= _p('you');
                    }
                }
            } else if ($aFeed['feed_total_like'] == 1) {
                $sPhrase .= _p('you');
            } else if ($aFeed['feed_total_like'] == 2) {
                $sPhrase .= _p('you_and') . '&nbsp;';
            } else if ($iPhraseLimiter > 2) {
                $sPhrase .= _p('you_comma') . '&nbsp;';
            }
            $bDidILikeIt = true;
        }

        $sTempUser = '';
        if ($iIteration > 1 || $bDidILikeIt) {
            $sTempUser = array_pop($aLikes);
        }

        $sImplode = implode(', ', $aLikes);
        $sPhrase .= $sImplode . ' ';

        $iIteration = $iIteration + (int)$bDidILikeIt;

        if ($iIteration > 1) {
            if ((int)$aFeed['feed_total_like'] > $iIteration) {
                $sPhrase = trim($sPhrase) . ', ';
            } else {
                if ((!$bDidILikeIt && $iIteration == 2) || ($bDidILikeIt && $iIteration > 2)) {
                    $sPhrase .= _p('and') . ' ';
                }
            }
        } else {
            $sPhrase = trim($sPhrase);
        }
        $sPhrase .= $sTempUser;
        $sLink = '<a href="#" onclick="return $Core.box(\'like.browse\', 400, \'in_feed=true&type_id=' . $aFeed['like_type_id'] . '&amp;item_id=' . $aFeed['like_item_id'] . '\');">';

        $iTotalLeftShow = $aFeed['feed_total_like'] - $iIteration;
        if (($bDidILikeIt || $iIteration > 0) && $iTotalLeftShow >= 1) {
            if ($iTotalLeftShow == 1) {
                $sPhrase .= '&nbsp;' . _p('and') . '&nbsp;' . $sLink . _p('1_other_person');
            } else {
                $sPhrase .= '&nbsp;' . _p('and') . '&nbsp;' . $sLink . Phpfox::getService('core.helper')->shortNumber($iTotalLeftShow) . '&nbsp;' . _p('others');
            }
            $sPhrase .= '</a></span>&nbsp;' . _p('like_this');
        } else {
            if ($iIteration > 1) {
                $sPhrase .= '</span>&nbsp;' . _p('like_this');
            } else {
                if ($bDidILikeIt) {
                    $sPhrase .= '</span>&nbsp;' . _p('like_this');
                } else {
                    if ($iIteration == 1) {
                        $sPhrase .= '</span>&nbsp;' . _p('likes_this');
                    }
                }
            }
        }

        $aActions = [];
        if (count($aActions) > 0) {
            $aFeed['bShowEnterCommentBlock'] = true;
            $aFeed['call_displayactions'] = true;
        }
        if (strlen($sPhrase) > 1 || count($aActions) > 0) {
            $aFeed['bShowEnterCommentBlock'] = true;
        }
        $sPhrase = str_replace(["&nbsp;&nbsp;", '  ', "\n"], ['&nbsp;', ' ', ''], $sPhrase);
        $sPhrase = str_replace(['  ', " &nbsp;", "&nbsp; "], ' ', $sPhrase);

        //',&nbsp;,'
        $sPhrase = str_replace(["\r\n", "\r"], "\n", $sPhrase);

        if (!$bDidILikeIt && !$iIteration) {
            if ($aFeed['feed_total_like'] > 0) {
                if ($aFeed['feed_total_like'] == 1) {
                    $sPhrase .= $sLink . _p('1_person') . '</a></span>&nbsp;' . _p('likes_this');;
                } else {
                    $sPhrase .= $sLink . _p('total_people', ['total' => Phpfox::getService('core.helper')->shortNumber($aFeed['feed_total_like'])]) . '</a></span>&nbsp;' . _p('like_this');;
                }
            } else {
                $sPhrase = '';
            }
        }
        $aFeed['feed_like_phrase'] = $sPhrase;

        if (!empty($sOriginalIsLiked) && !$bForce) {
            $aFeed['feed_is_liked'] = $sOriginalIsLiked;
        }

        return $sPhrase;
    }

    /**
     * @return array
     */
    public function getTimeline()
    {
        return $this->_aFeedTimeline;
    }

    /**
     * @return string
     */
    public function getLastDay()
    {
        return $this->_sLastDayInfo;
    }

    /**
     * @param int $iFeed
     *
     * @return array
     */
    public function getLikeForFeed($iFeed)
    {
        $aLikeRows = $this->database()
            ->select('fl.feed_id, ' . Phpfox::getUserField())
            ->from(Phpfox::getT('feed_like'), 'fl')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = fl.user_id')
            ->where('fl.feed_id = ' . (int)$iFeed)
            ->execute('getSlaveRows');

        $aLikes = [];
        $aLikesCount = [];
        foreach ($aLikeRows as $aLikeRow) {
            if (!isset($aLikesCount[$aLikeRow['feed_id']])) {
                $aLikesCount[$aLikeRow['feed_id']] = 0;
            }

            $aLikesCount[$aLikeRow['feed_id']]++;

            if ($aLikesCount[$aLikeRow['feed_id']] > 3) {
                continue;
            }

            $aLikes[$aLikeRow['feed_id']][] = $aLikeRow;
        }

        return [$aLikesCount, $aLikes];
    }

    /**
     * We get the redirect URL of the item depending on which module
     * it belongs to. We use the callback to connect to the correct module.
     *
     * @param integer $iId Is the ID# of the feed
     *
     * @return boolean|string If we are unable to find the correct feed, If we find the correct feed
     */
    public function getRedirect($iId)
    {
        // Get the feed
        $aFeed = $this->database()->select('privacy_comment, feed_id, type_id, item_id, user_id')
            ->from($this->_sTable)
            ->where('feed_id =' . (int)$iId)
            ->execute('getSlaveRow');


        // Make sure we found a feed
        if (!isset($aFeed['feed_id'])) {
            return false;
        }
        $aProcessedFeed = $this->_processFeed($aFeed, false, $aFeed['user_id'], false);
        Phpfox_Url::instance()->send($aProcessedFeed['feed_link'], [], null, 302);
        /* Apparently in some CGI servers for some reason the redirect
         * triggers a 500 error when the callback doesnt exist
         * http://www.phpfox.com/tracker/view/6356/
         */
        if (!Phpfox::hasCallback($aFeed['type_id'], 'getFeedRedirect')) {
            return false;
        }

        // Run the callback so we get the correct link
        return Phpfox::callback($aFeed['type_id'] . '.getFeedRedirect', $aFeed['item_id'], $aFeed['child_item_id']);
    }

    /**
     * @param int    $iId
     * @param string $sPrefix
     *
     * @return array
     */
    public function getFeed($iId, $sPrefix = '')
    {
        return $this->database()->select('*')
            ->from(Phpfox::getT(($sPrefix ? $sPrefix : (isset($this->_aCallback['table_prefix']) ? $this->_aCallback['table_prefix'] : '')) . 'feed'))
            ->where('feed_id =' . (int)$iId)
            ->executeRow();
    }

    /**
     * @param string $sText
     *
     * @return mixed
     */
    public function shortenText($sText)
    {
        $oParseOutput = Phpfox::getLib('parse.output');

        return $oParseOutput->split($oParseOutput->shorten($oParseOutput->parse($sText), 300, 'feed.view_more', true), 40);
    }

    /**
     * @param string $sText
     *
     * @return mixed
     */
    public function shortenTitle($sText)
    {
        $oParseOutput = Phpfox::getLib('parse.output');

        return $oParseOutput->shorten($oParseOutput->clean($sText), 60, '...');
    }

    /**
     * @param string $sText
     *
     * @return string
     */
    public function quote($sText)
    {
        Phpfox::getLib('parse.output')->setImageParser(['width' => 200, 'height' => 200]);

        $sNewText = '<div class="p_4">' . $this->shortenText($sText) . '</div>';

        Phpfox::getLib('parse.output')->setImageParser(['clear' => true]);

        return $sNewText;
    }

    /**
     * @param array  $aConds
     * @param string $sSort
     * @param string $iRange
     * @param string $sLimit
     *
     * @return array
     */
    public function getForBrowse($aConds, $sSort = 'feed.time_stamp DESC', $iRange = '', $sLimit = '')
    {
        $iCnt = $this->database()->select('COUNT(*)')
            ->from($this->_sTable, 'feed')
            ->where($aConds)
            ->execute('getSlaveField');

        $aRows = $this->database()->select('feed.*, fl.feed_id AS is_liked, ' . Phpfox::getUserField('u1', 'owner_') . ', ' . Phpfox::getUserField('u2', 'viewer_'))
            ->from($this->_sTable, 'feed')
            ->join(Phpfox::getT('user'), 'u1', 'u1.user_id = feed.user_id')
            ->leftJoin(Phpfox::getT('user'), 'u2', 'u2.user_id = feed.item_user_id')
            ->leftJoin(Phpfox::getT('feed_like'), 'fl', 'fl.feed_id = feed.feed_id AND fl.user_id = ' . Phpfox::getUserId())
            ->where($aConds)
            ->order($sSort)
            ->limit($iRange, $sLimit, $iCnt)
            ->execute('getSlaveRows');

        $aFeeds = [];
        foreach ($aRows as $aRow) {
            $aRow['link'] = Phpfox_Url::instance()->makeUrl('feed.view', ['id' => $aRow['feed_id']]);

            $aParts1 = explode('.', $aRow['type_id']);
            $sModule = $aParts1[0];
            if (strpos($sModule, '_')) {
                $aParts = explode('_', $sModule);
                $sModule = $aParts[0];
                if ($sModule == 'comment' && isset($aParts[1]) && !Phpfox::isModule($aParts[1])) {
                    continue;
                }
            }

            if (!Phpfox::isModule($sModule)) {
                continue;
            }

            if (Phpfox::hasCallback($aRow['type_id'], 'getNewsFeed')) {
                $aFeed = Phpfox::callback($aRow['type_id'] . '.getNewsFeed', $aRow);
                $aFeeds[] = $aFeed;
            }
        }

        return [$iCnt, $aFeeds];
    }

    /**
     * @param int $iId
     *
     * @return void
     * @throws Exception
     */
    public function processAjax($iId, $userId = null, $update = false)
    {
        $oAjax = Phpfox_Ajax::instance();
        if (empty($userId)) {
            $userId = Phpfox::getUserId();
        }
        $aFeed = Phpfox::getService('feed')->get($userId, $iId);
        $aFeed = reset($aFeed);

        if (!isset($aFeed['feed_id'])) {
            $oAjax->alert(_p('this_item_has_successfully_been_submitted'));
            $oAjax->call('$Core.resetActivityFeedForm();');

            return;
        }

        if (isset($aFeed['type_id'])) {
            Phpfox_Template::instance()->assign([
                'aFeed'         => $aFeed,
                'aFeedCallback' => [
                    'module'  => !empty($this->_aCallback['module']) ? $this->_aCallback['module'] : str_replace('_comment', '', $aFeed['type_id']),
                    'item_id' => !empty($this->_aCallback['item_id']) ? $this->_aCallback['item_id'] : $aFeed['item_id']
                ],
            ])->getTemplate('feed.block.entry');
        } else {
            Phpfox_Template::instance()->assign(['aFeed' => $aFeed])->getTemplate('feed.block.entry');
        }

        $content = $oAjax->getContent(false);

        if ($update) {
            $oAjax->call('$("#js_item_feed_' . $iId . '").closest(".js_feed_view_more_entry_holder").html(' . json_encode($content) . ');');
        } else {
            $sId = 'js_tmp_comment_' . md5('feed_' . uniqid() . Phpfox::getUserId()) . '';
            $sNewContent = '<div id="' . $sId . '" class="js_temp_new_feed_entry js_feed_view_more_entry_holder">' . $content . '</div>';
            $oAjax->insertAfter('#js_new_feed_comment', $sNewContent);
        }


        $oAjax->removeClass('.js_user_feed', 'row_first');
        $oAjax->call("iCnt = 0; \$('.js_user_feed').each(function(){ iCnt++; if (iCnt == 1) { \$(this).addClass('row_first'); } });");
        if ($oAjax->get('force_form')) {
            $oAjax->call('tb_remove();');
            $oAjax->show('#js_main_feed_holder');
            $oAjax->call('setTimeout(function(){$Core.resetActivityFeedForm();$Core.loadInit();}, 500);');
        } else {
            $oAjax->call('$Core.resetActivityFeedForm();');
            $oAjax->call('$Core.loadInit();');
        }
    }

    /**
     * @param int $iId
     *
     * @return void
     */
    public function processUpdateAjax($iId)
    {
        $oAjax = Phpfox_Ajax::instance();
        $aFeeds = Phpfox::getService('feed')->get(null, $iId);
        if (!isset($aFeeds[0])) {
            $oAjax->alert(_p('this_item_has_successfully_been_submitted'));
            $oAjax->call('$Core.resetActivityFeedForm();');
            return;
        }

        if (isset($aFeeds[0]['type_id'])) {
            Phpfox_Template::instance()->assign([
                'aFeed'         => $aFeeds[0],
                'aFeedCallback' => [
                    'module'  => !empty($this->_aCallback['module']) ? $this->_aCallback['module'] : str_replace('_comment', '', $aFeeds[0]['type_id']),
                    'item_id' => !empty($this->_aCallback['item_id']) ? $this->_aCallback['item_id'] : $aFeeds[0]['item_id'],
                ],
            ])->getTemplate('feed.block.entry');
        } else {
            Phpfox_Template::instance()->assign(['aFeed' => $aFeeds[0]])->getTemplate('feed.block.entry');
        }

        $oAjax->call('$("#js_item_feed_' . $iId . '").parent().html("' . $oAjax->getContent(true) . '");');
        $oAjax->call("tb_remove();");
        $oAjax->call('setTimeout(function(){$Core.resetActivityFeedForm();$Core.loadInit();}, 500);');
    }

    /**
     * @return array|int|mixed|string
     */
    public function getShareLinks()
    {
        if ($sPlugin = Phpfox_Plugin::get('feed.service_feed_getsharelinks__start')) {
            eval($sPlugin);
            if (isset($aPluginReturn)) {
                return $aPluginReturn;
            }
        }
        $sCacheId = $this->cache()->set('feed_share_link');

        if (false === ($aLinks = $this->cache()->get($sCacheId))) {
            $aLinks = $this->database()->select('fs.*')
                ->from(Phpfox::getT('feed_share'), 'fs')
                ->join(Phpfox::getT('module'), 'm', 'm.module_id = fs.module_id AND m.is_active = 1')
                ->order('fs.ordering ASC')
                ->execute('getSlaveRows');

            foreach ($aLinks as $iKey => $aLink) {
                $aLinks[$iKey]['module_block'] = $aLink['module_id'] . '.' . $aLink['block_name'];
            }

            $this->cache()->save($sCacheId, $aLinks);
            Phpfox::getLib('cache')->group('feed', $sCacheId);
        }
        $aNoDuplicates = [];
        if (!is_array($aLinks) || empty($aLinks)) {
            return $aLinks;
        }
        foreach ($aLinks as $iKey => $aLink) {
            unset($aLink['share_id']);
            if (in_array(serialize($aLink), $aNoDuplicates)) {
                unset($aLinks[$iKey]);
                continue;
            }
            if (Phpfox::hasCallback($aLink['module_id'], 'checkFeedShareLink') && Phpfox::callback($aLink['module_id'] . '.checkFeedShareLink') === false) {
                unset($aLinks[$iKey]);
            }
            $aNoDuplicates[] = serialize($aLink);
        }

        $aAcceptedTypes = ['photo'];

        if ($sPlugin = Phpfox_Plugin::get('feed.service_feed_getsharelinks__end')) {
            eval($sPlugin);
            if (isset($aPluginReturn)) {
                return $aPluginReturn;
            }
        }

        foreach ($aLinks as $key => $value) {
            if (!in_array($value['module_id'], $aAcceptedTypes)) {
                unset($aLinks[$key]);
            }
        }

        return $aLinks;
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
        if ($sPlugin = Phpfox_Plugin::get('feed.service_feed__call')) {
            return eval($sPlugin);
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
        return null;
    }

    /**
     * @param $aRow
     * @param $sKey
     * @param $iUserId
     * @param $bFirstCheckOnComments
     * @param $iSponsorFeedId
     *
     * @return array|bool
     * @throws Exception
     */
    private function _processFeed($aRow, $sKey, $iUserId, $bFirstCheckOnComments, $iSponsorFeedId = 0)
    {
        $original = (isset($aRow['content']) ? $aRow['content'] : '');
        switch ($aRow['type_id']) {
            case 'comment_profile':
            case 'comment_profile_my':
                $aRow['type_id'] = 'profile_comment';
                break;
            case 'profile_info':
                $aRow['type_id'] = 'custom';
                break;
            case 'comment_photo':
                $aRow['type_id'] = 'photo_comment';
                break;
            case 'comment_blog':
                $aRow['type_id'] = 'blog_comment';
                break;
            case 'comment_video':
                $aRow['type_id'] = 'video_comment';
                break;
            case 'comment_group':
                $aRow['type_id'] = 'pages_comment';
                break;
        }

        if (preg_match('/(.*)_feedlike/i', $aRow['type_id'])
            || $aRow['type_id'] == 'profile_design'
        ) {
            $this->database()->delete(Phpfox::getT('feed'), 'feed_id = ' . (int)$aRow['feed_id']);

            return false;
        }

        try {
            $App = Phpfox::getCoreApp()->get($aRow['type_id']); // type is app_id => support app feed with feed content data.
        } catch (Exception $e) {
        }

        if (empty($App) && !Phpfox::hasCallback($aRow['type_id'], 'getActivityFeed')) {
            return false;
        }
        $aRow['sponsor_feed_id'] = $iSponsorFeedId;

        if (!empty($App)) { // support app feed with content object
            $aMap = $aRow;
            if ($aRow['parent_feed_id']) {
                $aRow['main_feed_id'] = $aRow['feed_id'];
                $aMap['feed_id'] = $aRow['parent_feed_id'];
                $aRow['feed_id'] = $aRow['parent_feed_id'];
            }
            $aRow['ori_item_id'] = $aRow['feed_id'];
            $aRow['item_id'] = $aRow['feed_id'];
            $Map = $App->map($aRow['content'], $aMap);
            $Map->data_row = $aRow;
            \Core\Event::trigger('feed_map', $Map);
            \Core\Event::trigger('feed_map_' . $App->id, $Map);
            if ($Map->error) {
                return false;
            }

            $aFeed = [
                'feed_table_prefix' => $Map->feed_table_prefix,
                'is_app'            => true,
                'app_object'        => $App->id,
                'feed_link'         => $Map->link,
                'feed_title'        => $Map->title,
                'feed_info'         => $Map->feed_info,
                'item_id'           => $aRow['feed_id'],
                'comment_type_id'   => 'app',
                'like_type_id'      => 'app',
                'feed_total_like'   => (int)$this->database()->select('COUNT(*)')->from(':like')->where(['type_id' => 'app', 'item_id' => $aRow['feed_id'], 'feed_table' => ($Map->feed_table_prefix . 'feed')])->execute('getSlaveField'),
                'total_comment'     => (int)$this->database()->select('COUNT(*)')->from(':comment')->where(['type_id' => 'app', 'item_id' => $aRow['feed_id'], 'feed_table' => ($Map->feed_table_prefix . 'feed')])->execute('getSlaveField'),
                'feed_is_liked'     => ($this->database()->select('COUNT(*)')->from(':like')->where(['type_id' => 'app', 'item_id' => $aRow['feed_id'], 'user_id' => Phpfox::getUserId()])->execute('getSlaveField') ? true : false),
            ];

            if ($Map->content) {
                $aFeed['app_content'] = $Map->content;
            }
            if ($Map->more_params) {
                $aFeed = array_merge($aFeed, $Map->more_params);
            }
        } else {
            $aFeed = Phpfox::callback($aRow['type_id'] . '.getActivityFeed', $aRow, (isset($this->_aCallback['module']) ? $this->_aCallback : null));
            if (!empty($aRow['parent_feed_id']) && Phpfox::getCoreApp()->exists($aRow['parent_module_id'])) {
                $parent = $this->get(['id' => $aRow['parent_feed_id'], 'bIsChildren' => true]);
                if (isset($parent[0]) && isset($parent[0]['feed_id']) && Phpfox::getCoreApp()->exists($parent[0]['type_id'])) {
                    $aFeed['parent_is_app'] = $parent[0]['feed_id'];
                    if (Phpfox::hasCallback($parent[0]['type_id'], 'getActivityFeed')) {
                        $aFeed['parent_module_id'] = $parent[0]['type_id'];
                    }
                }
            }
            if ($aFeed === false) {
                return false;
            }
        }

        if (isset($this->_aViewMoreFeeds[$sKey])) {
            foreach ($this->_aViewMoreFeeds[$sKey] as $iSubKey => $aSubRow) {
                $mReturnViewMore = $this->_processFeed($aSubRow, $iSubKey, $iUserId, $bFirstCheckOnComments, $iSponsorFeedId);

                if ($mReturnViewMore === false) {
                    continue;
                }
                $mReturnViewMore['call_displayactions'] = true;
                $aFeed['more_feed_rows'][] = $mReturnViewMore;
            }
        }

        if (Phpfox::isModule('like') && (isset($aFeed['like_type_id']) || isset($aRow['item_id'])) && ((isset($aFeed['enable_like']) && $aFeed['enable_like'])) || (!isset($aFeed['enable_like'])) && (isset($aFeed['feed_total_like']) && (int)$aFeed['feed_total_like'] > 0)) {
            $aFeed['likes'] = Phpfox::getService('like')->getLikesForFeed($aFeed['like_type_id'], (isset($aFeed['like_item_id']) ? $aFeed['like_item_id'] : $aRow['item_id']), ((int)$aFeed['feed_is_liked'] > 0 ? true : false), 2, !isset($aFeed['feed_total_like']), (isset($aFeed['feed_table_prefix']) ? $aFeed['feed_table_prefix'] : ''));
            if (!isset($aFeed['feed_total_like'])) {
                $aFeed['feed_total_like'] = Phpfox::getService('like')->getTotalLikeCount();
            }
        }

        if (isset($aFeed['comment_type_id']) && (int)$aFeed['total_comment'] > 0 && Phpfox::isModule('comment')) {
            $aFeed['comments'] = Phpfox::getService('comment')->getCommentsForFeed($aFeed['comment_type_id'], $aRow['item_id'], Phpfox::getParam('comment.comment_page_limit'), null, null, (isset($aFeed['feed_table_prefix']) ? $aFeed['feed_table_prefix'] : ''));
        }

        $aRow['can_post_comment'] = true;
        $aFeed['bShowEnterCommentBlock'] = false;

        $aOut = array_merge($aRow, $aFeed);
        $aFeedActions = $this->getFeedActions($aOut);
        $aOut = array_merge($aOut, $aFeedActions);
        $aOut['_content'] = $original;
        $aOut['type_id'] = $aRow['type_id'];

        // check status background
        if (Phpfox::isAppActive('P_StatusBg')) {
            $aOut['status_background'] = Phpfox::getService('pstatusbg')->getFeedStatusBackground($aOut['item_id'], $aOut['type_id'], $aOut['user_id']);
        }
        // check remove tag
        if (isset($aOut['item_id'], $aOut['type_id'])) {
            $sFeedStatus = isset($aOut['feed_status']) ? $aOut['feed_status'] : '';
            $aOut['can_remove_tag'] = Phpfox::getService('feed.tag')->canRemoveTagFromFeed($sFeedStatus, $aOut['item_id'], $aOut['type_id']);
            $aOut['feed_status'] = Phpfox::getService('feed.tag')->stripContentHashTag($sFeedStatus, $aOut['item_id'], $aOut['type_id']);
        }
        if (($sPlugin = Phpfox_Plugin::get('feed.service_feed_processfeed'))) {
            eval($sPlugin);
        }
        return $aOut;
    }

    /**
     * Get feed actions
     *
     * @param $aFeed
     *
     * @return array
     */
    public function getFeedActions($aFeed)
    {
        $aActions = [];
        // can user like this feed?
        $aActions['can_like'] = (isset($aFeed['like_type_id']) || !empty($aFeed['type_id'])) && !(isset($aFeed['disable_like_function']) && $aFeed['disable_like_function']) && !Phpfox::getService('user.block')->isBlocked(null, $aFeed['user_id']);

        // check group member
        if (defined('PHPFOX_PAGES_ITEM_ID')
            && defined('PHPFOX_PAGES_ITEM_TYPE')
            && in_array(PHPFOX_PAGES_ITEM_TYPE, ['groups', 'pages'])) {
            $aGroup = Phpfox::getService(PHPFOX_PAGES_ITEM_TYPE)->getPage(PHPFOX_PAGES_ITEM_ID);
            $bGroupIsShareable = true;
            if (PHPFOX_PAGES_ITEM_TYPE == 'groups') {
                if (isset($aGroup['reg_method'])) {
                    $bGroupIsShareable = $aGroup['reg_method'] == 0 && isset($aGroup['view_id']) && $aGroup['view_id'] == 0 ? true : false;
                }
                $bIsGroupMember = Phpfox::isAdmin() ? true : Phpfox::getService('groups')->isMember($aGroup['page_id']);
            } else {
                $bGroupIsShareable = isset($aGroup['view_id']) && $aGroup['view_id'] == 0;
            }
        }
        // can user comment this feed?
        $aActions['can_comment'] = Phpfox::isModule('comment') && isset($aFeed['comment_type_id']) &&
            Phpfox::getUserParam('comment.can_post_comments') && Phpfox::isUser() && $aFeed['can_post_comment'] && (!isset($bIsGroupMember) || $bIsGroupMember);

        // can user share this feed?
        $aActions['can_share'] = Phpfox::isModule('share') && Phpfox::getUserParam('share.can_share_items') && !isset($aFeed['no_share']) && !empty($aFeed['type_id']) && isset($aFeed['privacy']) && $aFeed['privacy'] == 0 &&
            (!isset($bGroupIsShareable) || $bGroupIsShareable) &&
            !Phpfox::getService('user.block')->isBlocked(null, $aFeed['user_id']);

        // total action
        $aActions['total_action'] = intval($aActions['can_like']) + intval($aActions['can_comment']) + intval($aActions['can_share']);

        if (($sPlugin = Phpfox_Plugin::get('feed.service_feed_get_feed_actions_end'))) {
            eval($sPlugin);
        }

        return $aActions;
    }

    /**
     * @param string $sTypeId
     * @param int    $iItemId
     *
     * @return array
     */
    public function getParentFeedItem($sTypeId, $iItemId)
    {
        $aRow = $this->database()->select('f.*,' . Phpfox::getUserField('u'))
            ->from(':feed', 'f')
            ->join(':user', 'u', 'u.user_id=f.user_id')
            ->where('type_id=\'' . $sTypeId . '\' AND item_id=' . (int)$iItemId)
            ->executeRow();
        return $aRow;
    }

    public function getShareCount($sTypeId, $iItemId)
    {
        $aRow = $this->database()->select('COUNT(*)')
            ->from(':feed', 'f')
            ->where('parent_module_id=\'' . $sTypeId . '\' AND parent_feed_id=' . (int)$iItemId)
            ->executeField();
        return $aRow;
    }

    /**
     * @param      $aCallback
     * @param      $iFeedId
     * @param bool $bUseCache
     *
     * @return array|bool
     * @throws Exception
     */
    public function getUserStatusFeed($aCallback, $iFeedId, $bUseCache = true)
    {
        //Make hash for cache
        $hash = $this->makeHashStatusCache($aCallback);

        $sCacheId = $this->cache()->set('feed_status_' . $iFeedId . '_' . $hash);

        if (!$bUseCache || false === ($aStatusFeed = $this->cache()->get($sCacheId))) {
            $aData = $this->callback($aCallback)->get(null, $iFeedId);
            if (isset($aData[0])) {
                $aStatusFeed = $aData[0];
            } else {
                return false;
            }
            $this->cache()->save($sCacheId, $aStatusFeed);
            Phpfox::getLib('cache')->group('feed', $sCacheId);
        }
        $aStatusFeed['feed_status'] = Phpfox::getLib('parse.output')->clean($aStatusFeed['feed_status']);
        return $aStatusFeed;
    }

    public function makeHashStatusCache(&$aCallback)
    {
        $hash = 'hash_';
        if (isset($aCallback['module'])) {
            $hash .= $aCallback['module'];
        }
        if (isset($aCallback['table_prefix'])) {
            if ($aCallback['module'] == 'groups') {
                $aCallback['table_prefix'] = 'pages_';
            }
            $hash .= $aCallback['table_prefix'];
        }
        if (isset($aCallback['item_id'])) {
            $hash .= $aCallback['item_id'];
        }
        return md5($hash);
    }
    /**
     * @param string $sType
     * @param int    $iId
     *
     * @return bool
     */
    public function canSponsoredInFeed($sType, $iId)
    {
        $bPluginInChange = true;
        if (($sPlugin = Phpfox_Plugin::get('feed.service_feed_can_sponsored'))) {
            eval($sPlugin);
        }

        if ($bPluginInChange && !Phpfox::isAppActive('Core_BetterAds')) {
            return false;
        }

        $iFeedId = $this->database()->select('feed_id')
            ->from(':feed')
            ->where('type_id="' . $sType . '" AND item_id=' . (int)$iId)
            ->execute('getSlaveField');

        if (!$iFeedId) {
            return false;
        }

        $aRow = $this->database()->select('*')
            ->from(Phpfox::getT('better_ads_sponsor'))
            ->where('module_id = "feed" AND item_id=' . (int)$iFeedId . ' AND is_custom != 4')
            ->execute('getSlaveRow');

        if (empty($aRow)) {
            return true;
        }

        if ($aRow['is_active'] == 0 || in_array($aRow['is_custom'], [1, 2])) {
            return false;
        }

        return $aRow['item_id'];
    }

    /**
     * @param      $iItemId
     * @param      $sType
     * @param bool $bGetCount
     * @param int  $iPage
     * @param null $iTotal
     * @deprecated from v4.8.1
     * @return array|int|string
     */
    public function getTaggedUsers($iItemId, $sType, $bGetCount = false, $iPage = 0, $iTotal = null)
    {
        return Phpfox::getService('feed.tag')->getTaggedUsers($iItemId, $sType, $bGetCount, $iPage, $iTotal);
    }

    /**
     * @param $iItemId
     * @param $sType
     * @deprecated from v4.8.1
     * @return array|int|string
     */
    public function getTaggedUserIds($iItemId, $sType)
    {
        return Phpfox::getService('feed.tag')->getTaggedUserIds($iItemId, $sType);
    }

    /**
     * @param $aMentions
     * @param $aTagged
     * @param null $iItemId
     * @param null $sTypeId
     * @deprecated from v4.8.1
     */
    public function filterTaggedPrivacy(&$aMentions, &$aTagged, $iItemId = null, $sTypeId = null)
    {
        Phpfox::getService('feed.tag')->filterTaggedPrivacy($aMentions, $aTagged, $iItemId, $sTypeId);
    }

    /**
     * @param $iItemId
     * @param $sType
     * @param $iUserId
     * @deprecated from v4.8.1
     * @return array|int|string
     */
    public function checkTaggedUser($iItemId, $sType, $iUserId)
    {
        return Phpfox::getService('feed.tag')->checkTaggedUser($iItemId, $sType, $iUserId);
    }

    /**
     * @param $sName
     *
     * @return array
     */
    public function getUsersForMention($sName)
    {
        $buildTaggedQuery = db()->select('user_id')
            ->from(':user_privacy')
            ->where([
                'user_privacy' => 'user.can_i_be_tagged',
                'user_value' => 4
            ])->execute();

        $sName = strtolower(trim($sName));
        $search = '';
        if (!empty($sName)) {
            $search = ' AND ( u.full_name LIKE \'%' . ($sName) . '%\' ';
            //Search 100 results only
            $banWord = db()->select('find_value')->from(':ban')->where('replacement LIKE \'%'. $sName .'%\'')->limit(100)->executeRows();
            if (count(($banWord))) {
                $banWordFilter = array_map(function($word) {
                    return 'u.full_name LIKE \'%' . str_replace('&#42;', '%', $word['find_value']) . '%\'';
                }, $banWord);
                $search .= ' OR ' . implode(' OR ', $banWordFilter) . ')';
            } else {
                $search .= ')';
            }
        }

        $userId = Phpfox::getUserId();
        if (Phpfox::isModule('friend')) {
            $cond = [
                'OR (friend.is_page = 0 AND friend.user_id = ' . $userId . $search . ')',
                'AND u.user_id NOT IN (' . $buildTaggedQuery . ')'
            ];
        } else {
            $cond = [];
        }
        $hasPageGroup = false;
        if (Phpfox::isAppActive('Core_Pages')) {
            $cond[] = 'OR (u.profile_page_id > 0 AND p.item_type = 0 AND p.view_id = 0 ' . $search . ')';
            $hasPageGroup = true;
        }
        if (Phpfox::isAppActive('PHPfox_Groups')) {
            $sExtraCond = 'p.item_type = 1 AND u.profile_page_id > 0 AND p.view_id = 0';
            if (Phpfox::hasCallback(Phpfox::getService('groups.facade')->getItemType(), 'getExtraBrowseConditions')
            ) {
                $sExtraCond .= Phpfox::callback(Phpfox::getService('groups.facade')->getItemType() . '.getExtraBrowseConditions', 'p');
            }
            $cond[] = 'OR (' . $sExtraCond . ' ' . $search . ')';
            $hasPageGroup = true;
        }
        if ($hasPageGroup) {
            db()->leftJoin(Phpfox::getT('pages'), 'p', 'u.profile_page_id = p.page_id');
        }

        $sort = 'u.full_name ASC, friend.friend_id DESC';
        $aProcessedMentions = [];
        $limit = 20;

        // Current user
        $aUser = Phpfox::getUserBy();
        if (is_array($aUser) && !empty($aUser) && strpos(strtolower($aUser['full_name']), $sName) !== false) {
            $aUser = $this->addMoreUserInfo($aUser);
            $aUser['is_you'] = true;
            $aProcessedMentions[] = $aUser;
            $limit = $limit - 1;
        }

        $iCnt = db()->select('COUNT(DISTINCT u.user_id)')
            ->from(Phpfox::getT('user'), 'u')
            ->leftJoin(Phpfox::getT('friend'), 'friend', 'u.user_id = friend.friend_user_id AND u.status_id = 0')
            ->where($cond)
            ->execute('getSlaveField');

        if ($iCnt) {
            db()->select('uf.dob_setting, friend.friend_id, friend.friend_user_id, friend.is_top_friend, friend.time_stamp, ' . Phpfox::getUserField());
            if ($hasPageGroup) {
                db()->select(', p.item_type as page_type')
                    ->leftJoin(Phpfox::getT('pages'), 'p', 'u.profile_page_id = p.page_id');
            }
            $aMentions = db()->from(Phpfox::getT('user'), 'u')
                ->join(Phpfox::getT('user_field'), 'uf', 'u.user_id = uf.user_id')
                ->leftJoin(Phpfox::getT('friend'), 'friend', 'u.user_id = friend.friend_user_id AND u.status_id = 0')
                ->where($cond)
                ->limit($limit)
                ->order($sort)
                ->group('u.user_id')
                ->execute('getSlaveRows');

            foreach ($aMentions as $aMention) {
                $aProcessedMentions[] = $this->addMoreUserInfo($aMention);
            }
        }

        return $aProcessedMentions;
    }

    /**
     * @param $aUser
     *
     * @return mixed
     */
    public function addMoreUserInfo($aUser)
    {
        $pareOutput = Phpfox::getLib('parse.output');
        $aUser['full_name'] = $this->cleanFullName($aUser['full_name']);
        $aUser['is_page'] = ($aUser['profile_page_id'] ? true : false);
        $aUser['user_image'] = Phpfox::getLib('image.helper')->display([
                'user'       => $aUser,
                'suffix'     => '_50_square',
                'max_height' => 32,
                'max_width'  => 32,
                'no_link'    => true,
                'return_url' => true
            ]
        );
        $aUser['user_image_actual'] = Phpfox::getLib('image.helper')->display([
                'user'       => $aUser,
                'suffix'     => '_50_square',
                'max_height' => 32,
                'max_width'  => 32,
                'no_link'    => true
            ]
        );
        $aUser['has_image'] = isset($aUser['user_image']) && $aUser['user_image'];
        return $aUser;
    }

    public function cleanFullName($sName)
    {
        $sName = Phpfox::getLib('parse.output')->clean($sName);
        $sName = Phpfox::getLib('parse.output')->cleanScriptTag($sName);
        $sName = preg_replace(['/<(span)([^>]*)>(.*?)<\/span>/mi', '/<(a)([^>]*)>(.*?)<\/a>/mi', '/<(b)([^>]*)>(.*?)<\/b>/mi', '/<(strong)([^>]*)>(.*?)<\/strong>/mi'], '[PHPFOX_START]$1$2[PHPFOX_END]$3[PHPFOX_START]/$1[PHPFOX_END]', $sName);
        $sName = preg_replace(['/</','/>/'], ['&lt;', '&gt;'] ,$sName);
        $sName = str_replace(['[PHPFOX_START]', '[PHPFOX_END]'], ['<', '>'], $sName);
        return $sName;
    }
}