<?php
defined('PHPFOX') or exit('NO DICE!');

class Feed_Service_Hide extends Phpfox_Service
{
    protected $_sTable;

    public function __construct()
    {
        $this->_sTable = Phpfox::getT('feed_hide');
    }

    /**
     * Hide feed/user
     *
     * @param $iUserId
     * @param $iItemId
     * @param $sTypeId
     * @return bool
     */
    public function add($iUserId, $iItemId, $sTypeId)
    {
        if (!$this->isHidden($iUserId, $iItemId, $sTypeId)) {
            db()->insert($this->_sTable, [
                'user_id' => (int)$iUserId,
                'item_id' => (int)$iItemId,
                'type_id' => $sTypeId
            ]);
        }
        return true;
    }

    /**
     * Un-hide feed/user
     *
     * @param $iUserId
     * @param $iItemId
     * @param $sTypeId
     * @return bool
     */
    public function delete($iUserId, $iItemId, $sTypeId)
    {
        return db()->delete($this->_sTable, [
            'user_id' => (int)$iUserId,
            'item_id' => (int)$iItemId,
            'type_id' => $sTypeId
        ]);
    }

    /**
     * Check is hidden
     *
     * @param $iUserId
     * @param $iItemId
     * @param $sTypeId
     * @return bool
     */
    public function isHidden($iUserId, $iItemId, $sTypeId)
    {
        $isHidden = db()
            ->select('hide_id')
            ->from($this->_sTable)
            ->where([
                'user_id' => (int)$iUserId,
                'item_id' => (int)$iItemId,
                'type_id' => $sTypeId
            ])
            ->execute('getField');
        return $isHidden ? true : false;
    }

    /**
     * Get feed hide conditions
     *
     * @param null $iUserId
     * @return array
     */
    public function getHideCondition($iUserId = null)
    {
        if (!Phpfox::getParam('feed.enable_hide_feed', 1)) {
            return [];
        }
        $aCond = [];
        if ($iUserId == null) {
            $iUserId = Phpfox::getUserId();
        }
        if ($this->hasHideType($iUserId, 'feed')) {
            $aCond[] = 'feed.feed_id NOT IN (SELECT `item_id` FROM `' . $this->_sTable . '` WHERE `type_id` = \'feed\' AND `user_id` = ' . $iUserId . ')';
        }
        if ($this->hasHideType($iUserId, 'user')) {
            $aCond[] = 'feed.user_id NOT IN (SELECT `item_id` FROM `' . $this->_sTable . '` WHERE `type_id` = \'user\' AND `user_id` = ' . $iUserId . ')';
        }
        return $aCond;
    }

    /**
     * Check is hidden
     *
     * @param $iUserId
     * @param $sTypeId
     * @return bool
     */
    public function hasHideType($iUserId, $sTypeId)
    {
        $isHidden = db()
            ->select('hide_id')
            ->from($this->_sTable)
            ->where([
                'user_id' => (int)$iUserId,
                'type_id' => $sTypeId
            ])
            ->execute('getField');
        return $isHidden ? true : false;
    }

    /**
     *  Get list user/page/group hidden
     * @param null $iUserId
     * @param null $sType
     * @param string $sExtraCond
     * @param int $iPage
     * @param int $iLimit
     * @return array
     */
    public function getHiddenUsers($iUserId = null, $sType = null, $sExtraCond = '', $iPage = 1, $iLimit = 10)
    {
        if ($iUserId == null) {
            $iUserId = Phpfox::getUserId();
        }
        db()->select('h.*, ' . Phpfox::getUserField('user'))
            ->from($this->_sTable, 'h')
            ->join(Phpfox::getT('user'), 'user', 'user.user_id = h.item_id');

        if (in_array($sType, ['page', 'group'])) {
            db()->leftJoin(Phpfox::getT('pages'), 'page', 'page.page_id = user.profile_page_id');
        }

        $aHides = db()->where('h.user_id = ' . (int)$iUserId . ' AND h.type_id = \'user\' ' . $sExtraCond)
            ->limit($iPage, $iLimit)
            ->forCount()
            ->execute('getSlaveRows');

        $iCnt = db()->getCount();
        return [$iCnt, $aHides];
    }

    /**
     * Multiple un-hide feed/user
     *
     * @param $aHideIds
     * @param null $iUserId
     * @return bool
     */
    public function multiDelete($aHideIds, $iUserId = null)
    {
        if ($iUserId == null) {
            $iUserId = Phpfox::getUserId();
        }
        if (count($aHideIds) && $iUserId) {
            db()->delete($this->_sTable, 'user_id = ' . (int)$iUserId . ' AND hide_id IN (' . implode(',', $aHideIds) . ')');
            return true;
        }
        return false;
    }
}