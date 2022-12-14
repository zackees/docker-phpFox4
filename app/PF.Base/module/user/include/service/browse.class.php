<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Service_Browse
 */
class User_Service_Browse extends Phpfox_Service
{
    private $_aConditions = [];

    private $_sSort = 'u.joined DESC';

    private $_iPage = 0;

    private $_iLimit = 9;

    private $_bIsOnline = false;

    private $_bExtend = false;

    private $_aCallback = false;

    /**
     * boolean show featured or non featured | null: show all
     * @var mixed
     */
    private $_mFeatured = null;
    /**
     * Tells if admin is looking for users pending email verification
     * @var bool
     */
    private $_bPendingVerify = null;

    private $_aCustom = false;

    private $_bIsGender = false;

    private $_sIp = null;

    private $_bGetPendingType = false;

    public function __construct()
    {
        $this->_sTable = Phpfox::getT('user');
    }

    public function conditions($aConditions)
    {
        $this->_aConditions = $aConditions;

        return $this;
    }

    public function callback($aCallback)
    {
        $this->_aCallback = $aCallback;

        return $this;
    }

    public function sort($sSort)
    {
        $this->_sSort = $sSort;

        if ($this->_sSort == 'u.last_login ASC') {
            $this->_aConditions[] = 'AND u.last_login > 0';
        }

        return $this;
    }

    public function page($iPage)
    {
        $this->_iPage = $iPage;
        return $this;
    }

    public function pending($bPending)
    {
        $this->_bPendingVerify = (bool)$bPending;
        return $this;
    }

    public function featured($bFeatured)
    {
        $this->_mFeatured = $bFeatured;

        return $this;
    }

    public function limit($iLimit)
    {
        $this->_iLimit = $iLimit;
        return $this;
    }

    public function online($bIsOnline)
    {
        $this->_bIsOnline = $bIsOnline;

        return $this;
    }

    public function extend($bExtend)
    {
        $this->_bExtend = $bExtend;

        return $this;
    }

    public function custom($mCustom)
    {
        $this->_aCustom = $mCustom;
        return $this;
    }

    public function gender($bGender)
    {
        $this->_bIsGender = $bGender;

        return $this;
    }

    public function ip($sIp)
    {
        $this->_sIp = '%' . str_replace('&#42;', '', $sIp) . '%';
    }

    public function pendingType($bGetPendingType)
    {
        $this->_bGetPendingType = !!$bGetPendingType;

        return $this;
    }
    /**
     * This function returns user_ids for those that match the search by custom fields
     * if the param $bIsCount is true then it only returns the count and not the user_ids
     * @param bool $bIsCount
     * @param bool $iCount
     * @param bool $bAddCondition
     *
     * @return array|bool|int|string
     */
    public function getCustom($bIsCount = true, $iCount = false, $bAddCondition = true)
    {
        if ($bIsCount) {
            $aUsers = $this->getCustom(false, false, false);
            return is_array($aUsers) ? count($aUsers) : $aUsers;
        }
        $sSelect = 'u.user_id';

        if (is_array($this->_aCustom) && !empty($this->_aCustom)) {
            $sCondition = ' AND (';
            // When searching for more than one custom field searchFields will
            // return more than one join instruction
            $aAlias = [];
            $aCustomSearch = Phpfox::getService('custom')->searchFields($this->_aCustom);

            $iCustomCnt = 0;

            $iJoinsCount = 0;
            $aUserIds = [];
            if (count($aCustomSearch) > 0) {
                $this->database()->select($sSelect)->from(Phpfox::getT('user'), 'u');
            }

            $aAvoidDups = [];

            foreach ($aCustomSearch as $iKey => $aSearch) {
                if (isset($aAvoidDups[$aSearch['on'] . $aSearch['where']])) {
                    unset($aCustomSearch[$iKey]);
                    continue;
                }

                $aAvoidDups[$aSearch['on'] . $aSearch['where']] = $iKey;
            }

            foreach ($aCustomSearch as $iKey => $aSearchParam) {
                $iCustomCnt++;
                if ($iCount !== false && is_numeric($iCount) && $iCount > 0) {
                    $this->database()->order($this->_sSort)
                        ->limit($this->_iPage, $this->_iLimit, $iCount);
                }
                if (is_array($aSearchParam)) {
                    // The following replacements make sure that the joins are unique by using unique aliases
                    $sOldAlias = $aSearchParam['alias'];

                    $aSearchParam['alias'] = $sNewAlias = $aSearchParam['alias'] . $iCustomCnt;

                    $sNewOn = $aSearchParam['on'] = $aCustomSearch[$iKey]['on'] = str_replace($sOldAlias . '.', $sNewAlias . '.', $aSearchParam['on']);

                    $aCustomSearch[$iKey]['where'] = str_replace(['mvc.', $sOldAlias . '.'], $sNewAlias . '.', $aSearchParam['where']);

                    $sNewWhere = $aCustomSearch[$iKey]['where'];

                    $sOn = '' . $sNewOn . ' AND ' . $sNewWhere;

                    $this->database()->join($aSearchParam['table'], $sNewAlias, $sOn);
                    $iJoinsCount++;

                } // end of is_array aSearchParam
                else {
                    $this->database()->join(Phpfox::getT('user_custom'), 'ucv', $aSearchParam);
                    $iJoinsCount++;
                    $sCondition .= ' ' . $aSearchParam . ' AND ';
                }

                if ($iJoinsCount > 2 && !$bIsCount) {
                    $aUsers = $this->database()->execute('getSlaveRows');

                    if (empty($aUsers) || (isset($aUsers[0]['total']) && $aUsers[0]['total'] <= 1)) {
                        $aUserIds[0] = 0;
                    } else {
                        foreach ($aUsers as $aUser) {
                            $aUserIds[$aUser['user_id']] = $aUser['user_id'];
                        }
                    }

                    $this->database()->select($sSelect)->from(Phpfox::getT('user'), 'u')->where('u.user_id IN (' . implode(',', $aUserIds) . ')');
                    $iJoinsCount = 0;
                }
            } // foreach
            if ($bIsCount == true) {
                $aCount = $this->database()->execute('getSlaveRows');
                $aCount = array_pop($aCount);

                return (count($aCustomSearch) ? $aCount['total'] : $aCount[$sSelect]);
            }
            if ($iJoinsCount > 0) {
                $aUsers = $this->database()->execute('getSlaveRows');

                foreach ($aUsers as $aUser) {
                    $aUserIds[$aUser['user_id']] = $aUser['user_id'];
                }
            }
            if (count($aUserIds)) {
                $sCondition = 'AND (u.user_id IN (' . implode(',', $aUserIds) . ')';
            } else if (($iJoinsCount > 0) && (empty($aUsers))) {
                $sCondition = 'AND (1=2';
            }
            $this->database()->clean();

            if ($sCondition != ' AND (' && $bAddCondition) {
                $this->_aConditions[] = rtrim($sCondition, ' AND ') . ')';
            }

        }
        if ($bAddCondition != true && isset($aUsers)) {
            return $aUsers;
        }
        return false;
    }


    public function get()
    {
        $aReturnUsers = [];
        if ($sPlugin = Phpfox_Plugin::get('user.service_browse_get__start')) {
            return eval($sPlugin);
        }
        if (!defined('PHPFOX_IS_ADMIN_SEARCH')) {
            // user groups that should be hidden
            $aHiddenFromBrowse = Phpfox::getService('user.group.setting')->getUserGroupsBySetting('user.hide_from_browse');
        }

        if (($sPlugin = Phpfox_Plugin::get('user.service_browse_get__start_no_return'))) {
            eval($sPlugin);
        }

        // If there are custom fields to look for we need to know how many users satisfy this criteria
        $iCount = $this->getCustom(true);

        if ($iCount !== false && $iCount < 1) {
            $bNoMatches = true;
        } else {
            $aUsers = $this->getCustom(false);

            if ($aUsers !== false) {
                foreach ($this->_aConditions as $iKey => $sCondition) {
                    if (preg_match('/u.user_id IN (\([0-9]+\))/', $sCondition, $aMatch) > 0) {
                        $this->_aConditions[$iKey] = str_replace($aMatch[1], '(' . implode(',', $aUsers) . ')', $sCondition);
                    }
                }
            }

        }

        if (!isset($bNoMatches)) {
            if ($this->_bIsOnline === true) {
                $iActiveSession = Phpfox::getService('log.session')->getActiveTime();
                $this->database()->select('COUNT(DISTINCT u.user_id)')->join(Phpfox::getT('log_session'), 'ls', 'ls.user_id = u.user_id AND ls.last_activity > ' . $iActiveSession . (!defined('PHPFOX_IS_ADMIN_SEARCH') ? ' AND ls.im_hide = 0' : '') . '');
            } else {
                if ($this->_sIp !== null) {
                    $this->database()->select('COUNT(DISTINCT u.user_id)');
                } else {
                    $this->database()->select('COUNT(*)');
                }
            }

            if ($iCount > 0) {
                $this->database()->leftJoin(Phpfox::getT('user_custom'), 'ucv', 'ucv.user_id = u.user_id');
            }

            // one page to display all, one page to display only featured.
            if ($this->_mFeatured === true) {
                $this->database()->join(Phpfox::getT('user_featured'), 'uf', 'uf.user_id = u.user_id');
            }

            if ($this->_aCallback !== false && isset($this->_aCallback['query'])) {
                if (Phpfox::hasCallback($this->_aCallback['module'], 'getBrowseQueryCnt')) {
                    Phpfox::callback($this->_aCallback['module'] . '.getBrowseQueryCnt', $this->_aCallback);
                }
            }

            if ($this->_sIp !== null) {
                $this->_aConditions[] = 'AND u.last_ip_address LIKE \'' . $this->database()->escape($this->_sIp) . '\'';
            }

            if (!defined('PHPFOX_IS_ADMIN_SEARCH') && isset($aHiddenFromBrowse) && is_array($aHiddenFromBrowse) && !empty($aHiddenFromBrowse)) {
                // skip users in these user groups that are invisible
                foreach ($aHiddenFromBrowse as $iGroupId => $aGroup) {
                    $this->_aConditions[] = 'AND (u.user_group_id != ' . $iGroupId . ' OR u.is_invisible != 1)';
                }
            }

            if ($sPlugin = Phpfox_Plugin::get('user.service_browse_get_1')) {
                eval($sPlugin);
            }

            $iCnt = $this->database()->from($this->_sTable, 'u')
                ->join(Phpfox::getT('user_field'), 'ufield', 'ufield.user_id = u.user_id')
                ->forceIndex('status_id')
                ->where($this->_aConditions)
                ->execute('getSlaveField');
        } else {
            $iCnt = 0;
        }

        if ($iCnt > 0) {
            if ($sPlugin = Phpfox_Plugin::get('user.service_browse_get__cnt')) {
                eval($sPlugin);
            }

            if ($iCount > 0) {
                $this->database()->leftJoin(Phpfox::getT('user_custom'), 'ucv', 'ucv.user_id = u.user_id');
            }

            if ($this->_bIsOnline === true) {
                $iActiveSession = Phpfox::getService('log.session')->getActiveTime();
                $this->database()->join(Phpfox::getT('log_session'), 'ls', 'ls.user_id = u.user_id AND ls.last_activity > ' . $iActiveSession . (!defined('PHPFOX_IS_ADMIN_SEARCH') ? ' AND ls.im_hide = 0' : '') . '')->group('u.user_id');
            }

            if ($this->_aCallback !== false && isset($this->_aCallback['query'])) {
                if (Phpfox::hasCallback($this->_aCallback['module'], 'getBrowseQuery')) {
                    Phpfox::callback($this->_aCallback['module'] . '.getBrowseQuery', $this->_aCallback);
                }
            }

            if (defined('PHPFOX_IS_ADMIN_SEARCH')) {
                $this->database()->select('ug.title AS user_group_title, ')->leftJoin(Phpfox::getT('user_group'), 'ug', 'ug.user_group_id = u.user_group_id');
            }

            // display the Unfeature/Feature option when landing on the Search page.
            // using bIsOnline as its not defined in the admincp but it is on the user browse page
            if ($this->_mFeatured !== true || (Phpfox::getUserParam('user.can_feature') && $this->_bIsOnline)) {
                $this->database()
                    ->select('uf.user_id as is_featured, uf.ordering as featured_order, ')
                    ->leftJoin(Phpfox::getT('user_featured'), 'uf', 'uf.user_id = u.user_id');
            }

            // Users cannot send Friend Requests if they have been blocked by the target user
            if (!defined('PHPFOX_IS_ADMIN_SEARCH') && Phpfox::isModule('friend')) {
                $this->database()->select('ub.block_id as user_is_blocked, ')
                    ->leftJoin(Phpfox::getT('user_blocked'), 'ub', 'ub.user_id = u.user_id AND block_user_id = ' . Phpfox::getUserId());
            }

            // display the Unfeature/Feature option when landing on the Search page.
            if ($this->_mFeatured === true && !$this->_bIsOnline) {
                $this->database()
                    ->select('uf.user_id as is_featured, uf.ordering as featured_order, ')
                    ->join(Phpfox::getT('user_featured'), 'uf', 'uf.user_id = u.user_id');
            }

            if (!defined('PHPFOX_IS_ADMIN_SEARCH') && Phpfox::isUser() && Phpfox::isModule('friend')) {
                $this->database()->select('friend.friend_id AS is_friend, ')
                    ->leftJoin(Phpfox::getT('friend'), 'friend', 'friend.user_id = u.user_id AND friend.friend_user_id = ' . Phpfox::getUserId());

                $this->database()->select('frequest.request_id AS is_friend_request, ')
                    ->leftJoin(Phpfox::getT('friend_request'), 'frequest', 'frequest.user_id = u.user_id AND frequest.friend_user_id = ' . Phpfox::getUserId());
            }

            if ($this->_bGetPendingType) {
                $this->database()->select('uv.hash_code as verify_hash_code, uv.email as verify_email, ')
                    ->leftJoin(Phpfox::getT('user_verify'), 'uv', 'u.user_id = uv.user_id');
            }

            $aReturnUsers = $this->database()->select('u.status_id as unverified, ' . ($this->_bExtend ? 'u.*, ufield.*' : Phpfox::getUserField()))
                ->from($this->_sTable, 'u')
                ->join(Phpfox::getT('user_field'), 'ufield', 'ufield.user_id = u.user_id')
                ->where($this->_aConditions)
                ->group('u.user_id', true)
                ->order($this->_sSort)
                ->limit($this->_iPage, $this->_iLimit, $iCnt)
                ->execute('getSlaveRows');


            if (Phpfox::isModule('friend')) {
                foreach ($aReturnUsers as $iKey => $aUser) {
                    $aReturnUsers[$iKey]['mutual_friends'] = (Phpfox::getUserId() == $aUser['user_id'] ? 0 : $this->database()->select('COUNT(*)')
                        ->from(Phpfox::getT('friend'), 'f')
                        ->innerJoin('(SELECT friend_user_id FROM ' . Phpfox::getT('friend') . ' WHERE is_page = 0 AND user_id = ' . $aUser['user_id'] . ')', 'sf', 'sf.friend_user_id = f.friend_user_id')
                        ->where('f.user_id = ' . Phpfox::getUserId())
                        ->execute('getSlaveField'));
                }
            }

            if ($this->_bExtend) {
                foreach ($aReturnUsers as $iKey => $aUser) {
                    if (empty($aUser['dob_setting'])) {
                        switch (Phpfox::getParam('user.default_privacy_brithdate')) {
                            case 'month_day':
                                $aReturnUsers[$iKey]['dob_setting'] = '1';
                                break;
                            case 'show_age':
                                $aReturnUsers[$iKey]['dob_setting'] = '2';
                                break;
                            case 'hide':
                                $aReturnUsers[$iKey]['dob_setting'] = '3';
                                break;
                        }
                    }

                    $aBirthDay = Phpfox::getService('user')->getAgeArray($aUser['birthday']);

                    $aReturnUsers[$iKey]['month'] = Phpfox::getLib('date')->getMonth($aBirthDay['month']);
                    $aReturnUsers[$iKey]['day'] = $aBirthDay['day'];
                    $aReturnUsers[$iKey]['year'] = $aBirthDay['year'];
                    if (isset($aUser['last_ip_address'])) {
                        $aReturnUsers[$iKey]['last_ip_address_search'] = str_replace('.', '-', $aUser['last_ip_address']);
                    }
                    if ($aUser['unverified']) {
                        if (!empty($aUser['verify_hash_code']) && !empty($aUser['verify_email'])) {
                            $bIsSmsCode = preg_match('/^[0-9]{1,6}$/', $aUser['verify_hash_code']);
                            $bIsEmail = filter_var($aUser['verify_email'], FILTER_VALIDATE_EMAIL);
                            $aReturnUsers[$iKey]['unverified_type'] = !$bIsSmsCode ? 'email' : ($bIsEmail ? 'sms' : 'phone');
                        } else {
                            $aReturnUsers[$iKey]['unverified_type'] = 'email';
                        }
                    } else {
                        $aReturnUsers[$iKey]['unverified_type'] = '';
                    }
                    if ($this->_bGetPendingType) {
                        unset($aReturnUsers[$iKey]['verify_hash_code']);
                    }
                }
            }
        }

        if ($sPlugin = Phpfox_Plugin::get('user.service_browse_get__end')) {
            eval($sPlugin);
        }

        return [$iCnt, $aReturnUsers];
    }

    /**
     * If a call is made to an unknown method attempt to connect
     * it to a specific plug-in with the same name thus allowing
     * plug-in developers the ability to extend classes.
     *
     * @param string $sMethod is the name of the method
     * @param array $aArguments is the array of arguments of being passed
     *
     * @return null
     */
    public function __call($sMethod, $aArguments)
    {
        /**
         * Check if such a plug-in exists and if it does call it.
         */
        if ($sPlugin = Phpfox_Plugin::get('user.service_browse__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}
