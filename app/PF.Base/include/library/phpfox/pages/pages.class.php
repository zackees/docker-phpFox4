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
 * @version        $Id: pages.class.php 7234 2014-03-27 14:40:29Z Fern $
 */
abstract class Phpfox_Pages_Pages extends Phpfox_Service
{
    protected $_bIsInViewMode = false;

    protected $_aPage = null;

    protected $_aRow = array();

    protected $_bIsInPage = false;

    protected $_aWidgetMenus = array();
    protected $_aWidgetUrl = array();
    protected $_aWidgetBlocks = array();
    protected $_aWidgets = array();
    protected $_aWidgetEdit = array();

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('pages');
    }

    /**
     * @return Phpfox_Pages_Facade
     */
    abstract public function getFacade();

    public function isTimelinePage($iPageId)
    {
        return ((int)$this->database()->select('COUNT(*)')
            ->from(Phpfox::getT('pages'))
            ->where('page_id = ' . (int)$iPageId . ' AND use_timeline = 1')
            ->execute('getSlaveField') ? true : false);
    }

    public function setMode($bMode = true)
    {
        $this->_bIsInViewMode = $bMode;
    }

    public function isViewMode()
    {
        return (bool)$this->_bIsInViewMode;
    }

    public function setIsInPage()
    {
        $this->_bIsInPage = true;
    }

    public function isInPage()
    {
        return $this->_bIsInPage;
    }

    public function getWidgetsForEdit()
    {
        return $this->_aWidgetEdit;
    }

    public function isWidget($sUrl)
    {
        return isset($this->_aWidgetUrl[$sUrl]);
    }

    public function getWidget($sUrl)
    {
        return $this->_aWidgets[$sUrl];
    }

    public function getWidgetBlocks()
    {
        return $this->_aWidgetBlocks;
    }

    public function getActivePage()
    {
        return $this->_aRow;
    }

    public function isMember($iPage, $iUserId = null)
    {
        if ($iPage == Phpfox::getUserBy('profile_page_id')) {
            return true;
        }
        if (empty($iUserId)) {
            $iUserId = Phpfox::getUserId();
        }
        list(, $members) = $this->getFacade()->getItems()->getMembers($iPage);
        return in_array($iUserId, array_column($members, 'user_id'));
    }

    public function isAdmin($aPage, $iUserId = null)
    {
        if (empty($iUserId)) {
            $iUserId = Phpfox::getUserId();
        }
        if (!Phpfox::isUser() || empty($aPage)) {
            return false;
        }

        if (is_array($aPage)) {
            $iPageId = $aPage['page_id'];
        } else {
            $iPageId = $aPage;
        }

        if ($iPageId == Phpfox::getUserBy('profile_page_id')) {
            return true;
        }

        $admins = $this->getFacade()->getItems()->getPageAdmins($iPageId);
        return in_array($iUserId, array_column($admins, 'user_id'));
    }

    public function getPage($iId = null)
    {
        static $aRow = null;

        if (is_array($aRow) && $iId === null) {
            return $aRow;
        }

        if (Phpfox::isModule('like')) {
            $this->database()->select('l.like_id AS is_liked, ')
                ->leftJoin(Phpfox::getT('like'), 'l', 'l.type_id = \'' . $this->getFacade()->getItemType() . '\' AND l.item_id = p.page_id AND l.user_id = ' . Phpfox::getUserId());
        }

        $aRow = $this->database()->select('p.*, pu.vanity_url, pg.name AS category_name, pg.page_type')
            ->from($this->_sTable, 'p')
            ->leftJoin(Phpfox::getT('pages_url'), 'pu', 'pu.page_id = p.page_id')
            ->leftJoin(Phpfox::getT('pages_category'), 'pg', 'pg.category_id = p.category_id')
            ->where('p.page_id = ' . (int)$iId . ' AND p.item_type = ' . $this->getFacade()->getItemTypeId())
            ->execute('getSlaveRow');

        if (empty($aRow['page_id'])) {
            return false;
        }

        if (empty($aRow['category_name']) && ($type = $this->getFacade()->getType()->getById($aRow['type_id']))) {
            $aRow['category_name'] = $type['name'];
        }

        if (empty($this->_aRow) || $this->_aRow['page_id'] != $aRow['page_id']) {
            $this->_aRow = $aRow;
        }

        if ($this->_aRow['page_id'] == Phpfox::getUserBy('profile_page_id')) {
            $this->_aRow['is_liked'] = true;
        }

        // Issue with like/join button
        // Still not defined
        if (!isset($this->_aRow['is_liked'])) {
            // make it false: not liked or joined yet
            $this->_aRow['is_liked'] = false;
        }

        return $aRow;
    }

    /**
     * Get my pages | Get my pages total
     * @param bool $bIsCount
     * @param bool $bIncludePending
     * @return array|int|string
     */
    public function getMyPages($bIsCount = false, $bIncludePending = false)
    {
        if ($bIsCount) {
            return $this->database()->select('count(*)')->from($this->_sTable)
                ->where(array_merge([
                    'user_id' => Phpfox::getUserId(),
                    'item_type' => $this->getFacade()->getItemTypeId()
                ], $bIncludePending ? [] : ['p.view_id' => 0]))
                ->executeField();
        } else {
            $aRows = $this->database()->select('p.*, pu.vanity_url, ' . Phpfox::getUserField())
                ->from($this->_sTable, 'p')
                ->join(Phpfox::getT('user'), 'u', 'u.profile_page_id = p.page_id')
                ->leftJoin(Phpfox::getT('pages_url'), 'pu', 'pu.page_id = p.page_id')
                ->where(array_merge([
                    'p.user_id' => Phpfox::getUserId(),
                    'p.item_type' => $this->getFacade()->getItemTypeId()
                ], $bIncludePending ? [] : ['p.view_id' => 0]))
                ->order('p.time_stamp DESC')
                ->execute('getSlaveRows');

            foreach ($aRows as $iKey => $aRow) {
                $aRows[$iKey]['link'] = $this->getFacade()->getItems()->getUrl($aRow['page_id'], $aRow['title'],
                    $aRow['vanity_url']);
            }

            return $aRows;
        }
    }

    public function getUrl($iPageId, $sTitle = null, $sVanityUrl = null, $bIsGroup = false)
    {
        if ($sTitle === null && $sVanityUrl === null) {
            $aPage = $this->getPage($iPageId);
            $sVanityUrl = $aPage['vanity_url'];
        }

        if (!empty($sVanityUrl)) {
            return Phpfox_Url::instance()->makeUrl($sVanityUrl);
        }

        return Phpfox_Url::instance()->makeUrl($this->getFacade()->getItemType(), $iPageId);
    }

    public function isPage($sUrl)
    {
        $aPage = $this->database()->select('pu.*')
            ->from(Phpfox::getT('pages_url'), 'pu')
            ->join($this->_sTable, 'p', 'p.page_id = pu.page_id')
            ->where('pu.vanity_url = \'' . $this->database()->escape($sUrl) . '\' AND p.item_type = ' . $this->getFacade()->getItemTypeId())
            ->execute('getSlaveRow');

        if (!isset($aPage['page_id'])) {
            return false;
        }

        $this->_aPage = $aPage;

        return true;
    }

    public function getCurrentInvites($iPageId)
    {
        $aRows = $this->database()->select('*')
            ->from(Phpfox::getT('pages_invite'))
            ->where('page_id = ' . (int)$iPageId . ' AND type_id = 0 AND user_id = ' . Phpfox::getUserId())
            ->execute('getSlaveRows');

        $aInvites = array();
        foreach ($aRows as $aRow) {
            $aInvites[$aRow['invited_user_id']] = $aRow;
        }

        return $aInvites;
    }

    public function isInvited($iPageId)
    {
        $iCnt = $this->database()->select('COUNT(*)')
            ->from(':pages_invite')
            ->where('page_id = ' . (int)$iPageId . ' AND type_id = 0 AND invited_user_id = ' . Phpfox::getUserId())
            ->execute('getSlaveField');
        return ($iCnt) ? true : false;
    }

    /**
     * Get page permissions
     * @param $iPage
     * @return array
     */
    public function getPerms($iPage)
    {
        switch ($this->getFacade()->getItemType()) {
            case 'pages':
                $aCallbacks = Phpfox::massCallback('getPagePerms');
                break;

            case 'groups':
                $aCallbacks = Phpfox::massCallback('getGroupPerms');
                break;

            default:
                $aCallbacks = [];

        }
        $aPerms = array();
        $aUserPerms = $this->getPermsForPage($iPage);
        if ($aIntegrate = storage()->get($this->getFacade()->getItemType() . '_integrate')) {
            $aIntegrate = (array)$aIntegrate->value;
        }
        if(isset($aIntegrate['v'])) { // check special case
            $aIntegrate['pf_video'] = $aIntegrate['v'];
            unset($aIntegrate['v']);
        }
        foreach ($aCallbacks as $aCallback) {
            foreach ($aCallback as $sId => $sPhrase) {
                $sModule = current(explode('.', $sId));
                if ($aIntegrate && array_key_exists($sModule, $aIntegrate) && !$aIntegrate[$sModule]) {
                    continue;
                }

                $hasDefault = is_array($sPhrase) && isset($sPhrase['default']);
                $phrase = is_array($sPhrase) && isset($sPhrase['phrase']) ?  $sPhrase['phrase'] : $sPhrase;
                $default = $hasDefault ? $sPhrase['default'] : 0;
                $params = [
                    'id' => $sId,
                    'phrase' => $phrase,
                    'is_active' => (isset($aUserPerms[$sId]) ? $aUserPerms[$sId] : $default),
                ];
                if ($hasDefault) {
                    $params['has_default'] = true;
                }

                $aPerms[] = $params;
            }
        }

        return $aPerms;
    }

    public function getPermsForPage($iPage)
    {
        static $aPerms = null;

        if (isset($aPerms[$iPage]) && is_array($aPerms[$iPage])) {
            return $aPerms[$iPage];
        }

        $aPerms[$iPage] = array();
        $aRows = $this->database()->select('*')
            ->from(Phpfox::getT('pages_perm'))
            ->where('page_id = ' . (int)$iPage)
            ->execute('getSlaveRows');

        foreach ($aRows as $aRow) {
            $aPerms[$iPage][$aRow['var_name']] = (int)$aRow['var_value'];
        }

        return $aPerms[$iPage];
    }

    public function getPendingTotal()
    {
        return (int)$this->database()->select('COUNT(*)')
            ->from(Phpfox::getT('pages'), 'p')
            ->join(Phpfox::getT('user'), 'u', 'u.profile_page_id = p.page_id')
            ->where('p.app_id = 0 AND p.view_id = 1 AND p.item_type = ' . $this->getFacade()->getItemTypeId())
            ->execute('getSlaveField');
    }

    public function getLastLogin()
    {
        static $aUser = null;

        if ($aUser !== null) {
            return $aUser;
        }

        $this->database()->join(Phpfox::getT('user'), 'u', 'u.user_id = pl.user_id');

        if (($sPlugin = Phpfox_Plugin::get($this->getFacade()->getItemType() . '.service_pages_getlastlogin'))) {
            eval($sPlugin);
        }

        $aUser = $this->database()->select(Phpfox::getUserField() . ', u.email, u.style_id, u.password, u.full_phone_number')
            ->from(Phpfox::getT('pages_login'), 'pl')
            ->where('pl.login_id = ' . (int)Phpfox::getCookie('page_login') . ' AND pl.page_id = ' . Phpfox::getUserBy('profile_page_id'))
            ->execute('getSlaveRow');

        if (!isset($aUser['user_id'])) {
            $aUser = false;

            return false;
        }

        return $aUser;
    }

    public function getMyLoginPages()
    {
        $sCacheId = $this->cache()->set('admin_' . Phpfox::getUserId() . '_' . $this->getFacade()->getItemType());
        if (false === ($aRows = $this->cache()->getLocalFirst($sCacheId))) {
            $iCntAdmins = $this->database()->select('COUNT(*)')
                ->from(Phpfox::getT('pages_admin'), 'pa')
                ->leftJoin(Phpfox::getT('pages'), 'pages', 'pages.page_id = pa.page_id')
                ->where('pa.user_id = ' . Phpfox::getUserId() . ' AND pages.item_type=0')
                ->execute('getSlaveField');

            $this->database()->select('pages.*')
                ->from(Phpfox::getT('pages'), 'pages')
                ->where('pages.app_id = 0 AND pages.view_id = 0 AND pages.user_id = ' . Phpfox::getUserId() . ' AND pages.item_type=0')
                ->union();

            if ($iCntAdmins > 0) {
                $this->database()->select('pages.*')
                    ->from(Phpfox::getT('pages_admin'), 'pa')
                    ->leftJoin(Phpfox::getT('pages'), 'pages', 'pages.page_id = pa.page_id')
                    ->where('pa.user_id = ' . Phpfox::getUserId() . ' AND pages.item_type=0')
                    ->union();
            }

            $aRows = $this->database()->select('pages.*, pu.vanity_url, ' . Phpfox::getUserField())
                ->unionFrom('pages')
                ->join(Phpfox::getT('user'), 'u', 'u.profile_page_id = pages.page_id')
                ->leftJoin(Phpfox::getT('pages_url'), 'pu', 'pu.page_id = pages.page_id')
                ->group('pages.page_id, pu.vanity_url, u.user_id', true)
                ->order('pages.time_stamp DESC')
                ->execute('getSlaveRows');

            foreach ($aRows as $iKey => $aRow) {
                $aRows[$iKey]['link'] = $this->getFacade()->getItems()->getUrl($aRow['page_id'], $aRow['title'], $aRow['vanity_url']);
            }
            $this->cache()->saveBoth($sCacheId, $aRows);
        }

        return array(count($aRows), $aRows);
    }

    public function getClaims()
    {
        $aClaims = $this->database()->select('pc.*, u.full_name, u.user_name, p1.page_id, p1.title, curruser.user_id as curruser_user_id, curruser.full_name as curruser_full_name, curruser.user_name as curruser_user_name')
            ->from(Phpfox::getT('pages_claim'), 'pc')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = pc.user_id')
            ->join(Phpfox::getT('pages'), 'p1', 'p1.page_id = pc.page_id')
            ->join(Phpfox::getT('user'), 'curruser', 'curruser.user_id = p1.user_id')
            ->where('pc.status_id = 1')
            ->order('pc.time_stamp')
            ->execute('getSlaveRows');

        foreach ($aClaims as $iIndex => $aClaim) {
            $aClaims[$iIndex]['url'] = Phpfox::permalink($this->getFacade()->getItemType(), $aClaim['page_id'], $aClaim['title']);
        }
        return $aClaims;
    }

    public function getInfoForAction($aItem)
    {
        if (is_numeric($aItem)) {
            $aItem = array('item_id' => $aItem);
        }
        $aRow = $this->database()->select('p.page_id, p.title, p.user_id, u.gender, u.full_name')
            ->from(Phpfox::getT('pages'), 'p')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = p.user_id')
            ->where('p.page_id = ' . (int)$aItem['item_id'])
            ->execute('getSlaveRow');
        if (defined('PHPFOX_PAGES_ITEM_TYPE')) {
            $sModule = PHPFOX_PAGES_ITEM_TYPE;
        } else {
            $sModule = 'pages';
        }
        $aRow['link'] = Phpfox_Url::instance()->permalink($sModule, $aRow['page_id'], $aRow['title']);
        return $aRow;
    }

    public function getPagesByLocation($fLat, $fLng)
    {
        $aPages = $this->database()->select('page_id, title, location_latitude, location_longitude, (3956 * 2 * ASIN(SQRT( POWER(SIN((' . $fLat . ' - location_latitude) *  pi()/180 / 2), 2) + COS(' . $fLat . ' * pi()/180) * COS(location_latitude * pi()/180) * POWER(SIN((' . $fLng . ' - location_longitude) * pi()/180 / 2), 2) ))) as distance')
            ->from(Phpfox::getT('pages'))
            ->having('distance < 1')// distance in kilometers
            ->limit(10)
            ->execute('getSlaveRows');

        return $aPages;
    }

    public function timelineEnabled($iId)
    {
        return $this->database()->select('use_timeline')
            ->from(Phpfox::getT('pages'))
            ->where('page_id = ' . (int)$iId)
            ->execute('getSlaveField');
    }

    /**
     * Gets the count of pages Without the pages created by apps.
     * @param int $iUser
     * @return int
     */
    public function getPagesCount($iUser)
    {
        if ($iUser == Phpfox::getUserId()) {
            return Phpfox::getUserBy('total_pages');
        }

        $iCount = $this->database()->select('count(*)')
            ->from(Phpfox::getT('pages'))
            ->where('app_id = 0 AND user_id = ' . (int)$iUser . ' AND item_type = ' . $this->getFacade()->getItemTypeId())
            ->execute('getSlaveField');

        return $iCount;
    }

    /**
     * @param int $iPageId
     *
     * @return string
     */
    public function getTitle($iPageId)
    {
        $aPage = $this->getPage($iPageId);
        $sTitle = $aPage['title'];
        return $sTitle;
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
        if ($sPlugin = Phpfox_Plugin::get($this->getFacade()->getItemType() . '.service_pages__call')) {
            eval($sPlugin);
            return;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }

    /**
     * Get user id of page
     * @param $iPageId
     * @return int|string
     */
    public function getUserId($iPageId)
    {
        return db()->select('user_id')->from(':user')->where(['profile_page_id' => $iPageId])->executeField();
    }

    /**
     * Get user_id of page owner
     * @param $iPageId
     * @return int|string
     */
    public function getPageOwnerId($iPageId)
    {
        return db()->select('user_id')->from($this->_sTable)->where(['page_id' => $iPageId])->executeField();
    }

    public function hasPerm($iPage, $sPerm)
    {
        return false;
    }

    /**
     * Get page owner
     * @param $iPageId
     * @return Phpfox_Database_Dba
     */
    public function getPageOwner($iPageId)
    {
        return db()->select(Phpfox::getUserField())
            ->from($this->_sTable, 'p')
            ->join(':user', 'u', 'u.user_id = p.user_id')
            ->where(['p.page_id' => $iPageId])
            ->executeRow();
    }
}
