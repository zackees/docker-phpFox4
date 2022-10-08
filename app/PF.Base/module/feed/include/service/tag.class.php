<?php
defined('PHPFOX') or exit('NO DICE!');

class Feed_Service_Tag extends Phpfox_Service
{
    protected $_sTable;
    protected $_sTableRemove;
    private $_bIsCallback;
    private $_aCallback;

    public function __construct()
    {
        $this->_sTable = Phpfox::getT('feed_tag_data');
        $this->_sTableRemove = Phpfox::getT('feed_tag_remove');
    }

    /**
     * @param      $iItemId
     * @param      $sType
     * @param bool $bGetCount
     * @param int $iPage
     * @param null $iTotal
     *
     * @return array|int|string
     */
    public function getTaggedUsers($iItemId, $sType, $bGetCount = false, $iPage = 0, $iTotal = null)
    {
        $aConditions = [
            'td.item_id' => (int)$iItemId,
            'td.type_id' => $sType
        ];
        $aUserIds = Phpfox::getService('user.block')->get(Phpfox::getUserId(), true);
        if (!empty($aUserIds)) {
            $aConditions[] = ' AND td.user_id NOT IN (' . implode(',', $aUserIds) . ')';
        }
        db()->from($this->_sTable, 'td')
            ->join(':user', 'u', 'u.user_id = td.user_id')
            ->where($aConditions);
        if ($bGetCount) {
            return db()->select('COUNT(*)')->executeField();
        } else {
            db()->order('u.full_name ASC');
            if ($iPage) {
                return db()->select(Phpfox::getUserField())
                    ->group('u.user_id')
                    ->limit($iPage, $iTotal)
                    ->execute('getSlaveRows');
            } else {
                $sCacheId = $this->cache()->set('tagged_users_' . $sType . '_' . $iItemId);
                if (false === ($aTaggedUsers = $this->cache()->getLocalFirst($sCacheId))) {
                    $aTaggedUsers = db()->select(Phpfox::getUserField())
                        ->group('u.user_id')
                        ->execute('getSlaveRows');
                    $this->cache()->saveBoth($sCacheId, $aTaggedUsers);
                } else {
                    db()->clean();
                }
                return $aTaggedUsers;
            }
        }
    }

    /**
     * @param $iItemId
     * @param $sType
     *
     * @return array|int|string
     */
    public function getTaggedUserIds($iItemId, $sType)
    {
        $aTaggedUsers = $this->getTaggedUsers($iItemId, $sType);
        return array_column($aTaggedUsers, 'user_id');
    }

    /**
     * @param $aMentions
     * @param $aTagged
     * @param null $iItemId
     * @param null $sTypeId
     */
    public function filterTaggedPrivacy(&$aMentions, &$aTagged, $iItemId = null, $sTypeId = null)
    {
        $allTagged = array_unique(array_merge($aTagged, $aMentions));
        // check permission 'can_i_be_tagged' = 4 (no one)
        if (array_filter($allTagged)) {
            $aPerms = $this->database()->select('user_id')->from(Phpfox::getT('user_privacy'))->where('`user_id` IN (' . implode(',', $allTagged) . ' ) AND `user_privacy` = \'user.can_i_be_tagged\' AND `user_value` = 4')->execute('getSlaveRows');
            $noTagUserIds = array_column($aPerms, 'user_id');
            $aUserIds = Phpfox::getService('user.block')->get(Phpfox::getUserId(), true);
            if (count($aUserIds)) {
                $noTagUserIds = array_merge($noTagUserIds, $aUserIds);
            }
            if (!empty($iItemId) && !empty($sTypeId)) {
                $aRemovedTags = $this->getAllRemovedTag($iItemId, $sTypeId);
                $removedUserIds = array_column($aRemovedTags, 'user_id');
                $noTagUserIds = array_merge($noTagUserIds, $removedUserIds);
            }
            $aMentions = array_diff($aMentions, $noTagUserIds); // remove noTagUserIds
            $aTagged = array_diff($aTagged, $noTagUserIds); // remove noTagUserIds
        }
    }

    /**
     * @param $iItemId
     * @param $sType
     * @param $iUserId
     *
     * @return array|int|string
     */
    public function checkTaggedUser($iItemId, $sType, $iUserId)
    {
        $aTaggedUserIds = $this->getTaggedUserIds($iItemId, $sType);
        return in_array($iUserId, $aTaggedUserIds);
    }

    /**
     * Add tag when tag a friend using "With friend" feature
     * @param $iItemId
     * @param $aUserIds
     * @param $sType
     * @return bool
     */
    public function addTaggedUsers($iItemId, $aUserIds, $sType)
    {
        foreach ($aUserIds as $iUserId) {
            if (!$iUserId) continue;
            db()->insert(Phpfox::getT('feed_tag_data'),
                [
                    'user_id' => (int)$iUserId,
                    'item_id' => (int)$iItemId,
                    'type_id' => $sType
                ]);
        }
        $this->cache()->remove('tagged_users_' . $sType . '_' . $iItemId);
        return true;
    }

    /**
     * @param $iItemId
     * @param $iUserId
     * @param $sType
     * @return bool
     */
    public function deleteTaggedUser($iItemId, $iUserId, $sType)
    {
        db()->delete(Phpfox::getT('feed_tag_data'),
            [
                'user_id' => $iUserId,
                'item_id' => $iItemId,
                'type_id' => $sType
            ]);
        $this->cache()->remove('tagged_users_' . $sType . '_' . $iItemId);
        return true;
    }

    /**
     * Update tag when update status with tag
     * @param $iItemId
     * @param $aUserId
     * @param $sType
     * @return bool
     */
    public function updateTaggedUsers($iItemId, $sType, $aUserId = null)
    {
        db()->delete(Phpfox::getT('feed_tag_data'), 'item_id =' . (int)$iItemId . ' AND type_id = \'' . $sType . '\'');
        if (is_array($aUserId) && count($aUserId)) {
            return $this->addTaggedUsers($iItemId, $aUserId, $sType);
        } else {
            $this->cache()->remove('tagged_users_' . $sType . '_' . $iItemId);
        }
        return true;
    }

    /**
     *  Add/update tagged users in feed
     * @param $params
     * @return bool
     */
    public function updateFeedTaggedUsers($params)
    {
        if (!Phpfox::getParam('feed.enable_tag_friends')) {
            return false;
        }
        $sFeedType = $params['feed_type'];
        $aMentions = Phpfox::getService('user.process')->getIdFromMentions($params['content'], true, false);
        $aTagged = $params['tagged_friend'];
        $allTagged = array_unique(array_merge($aTagged, $aMentions));
        $iItemId = $params['item_id'];
        $oldMentionedFriends = isset($params['old_mentioned_friend']) ? $params['old_mentioned_friend'] : [];
        $oldTaggedFriends = isset($params['old_tagged_friend']) ? $params['old_tagged_friend'] : [];
        $allOldTagged = array_unique(array_merge($oldTaggedFriends, $oldMentionedFriends));
        $aRemoveTagged = array_diff($allOldTagged, $allTagged);

        if (array_filter($aRemoveTagged)) { // delete feed of tagged users
            db()->delete(Phpfox::getT('feed'), '`type_id` = \'' . $sFeedType . '\' AND `item_id` = ' . (int)$iItemId . ' AND `feed_reference` = 1 AND `parent_user_id` IN (' . implode(',', $aRemoveTagged) . ')');
            if ($sPlugin = Phpfox_Plugin::get('feed.service_process_delete__end')) {
                eval($sPlugin);
            }
        }

        Phpfox::getService('feed.tag')->filterTaggedPrivacy($aMentions, $aTagged, $iItemId, $sFeedType);

        if (count($oldTaggedFriends)) {
            $this->updateTaggedUsers($iItemId, $sFeedType, $aTagged);
        } else {
            $this->addTaggedUsers($iItemId, $aTagged, $sFeedType);
        }

        $aMentions = array_diff($aMentions, $oldMentionedFriends); // remove oldMentions
        $aTagged = array_diff($aTagged, $oldTaggedFriends); // remove oldTaggedFriends
        $allTagged = array_unique(array_merge($aTagged, $aMentions));

        $callbackModuleId = $sFeedType;
        if (in_array($callbackModuleId, ['user_status', 'feed_comment'])) {
            $callbackModuleId = 'user';
        }

        //Remove owner from tagged list - don't need to send notification
        $ownerId = isset($params['owner_id']) ? (int)$params['owner_id'] : (int)Phpfox::getUserId();
        $allTagged = array_filter($allTagged, function ($tagId) use ($ownerId) {
            return (int)$tagId != $ownerId;
        });

        if (!empty($params['privacy']) && (int)$params['privacy'] == 3) {
            $params = array_merge($params, [
                'no_email' => true,
                'no_notification' => true,
            ]);
        }

        if (count($allTagged) && Phpfox::hasCallback($callbackModuleId, 'sendNotifyToTaggedUsers')) {
            $params['tagged_friend'] = $allTagged;
            Phpfox::callback($callbackModuleId . '.sendNotifyToTaggedUsers', $params);
        }

        return true;
    }

    /**
     * @param $feedType
     * @param $sContent
     * @param $iItemId
     * @param $iOwnerId
     * @param int $iFeedId
     * @param array $aTagged
     * @param int $iPrivacy
     * @param int $iParentUserId
     * @param array $oldTagged
     * @param array $oldMentions
     * @param string $moduleId
     * @return bool
     */
    public function notifyTaggedInFeed($feedType, $sContent, $iItemId, $iOwnerId, $iFeedId = 0, $aTagged = [], $iPrivacy = 0, $iParentUserId = 0, $oldTagged = [], $oldMentions = [], $moduleId = '')
    {
        return $this->updateFeedTaggedUsers([
            'feed_type' => $feedType,
            'content' => $sContent,
            'owner_id' => $iOwnerId,
            'privacy' => $iPrivacy,
            'tagged_friend' => $aTagged,
            'item_id' => $iItemId,
            'feed_id' => $iFeedId,
            'parent_user_id' => $iParentUserId,
            'old_tagged_friend' => $oldTagged,
            'old_mentioned_friend' => $oldMentions,
            'module_id' => $moduleId
        ]);
    }

    /**
     * @param $sStatus
     * @param $itemId
     * @param $aTagged
     * @param $callback
     * @param array $oldMentions
     * @param array $oldTagged
     */
    public function notifyTaggedIsCallbackFeed($sStatus, $itemId, $aTagged, $callback, $oldMentions = [], $oldTagged = [])
    {
        $aMentions = Phpfox::getService('user.process')->getIdFromMentions($sStatus, true, false);
        $this->filterTaggedPrivacy($aMentions, $aTagged, $itemId, isset($callback['feed_id']) ? $callback['feed_id'] : null);
        $aMentions = array_diff($aMentions, $oldMentions); // remove oldMentions
        $aTagged = array_diff($aTagged, $oldTagged); // remove oldTaggedFriends
        $allTagged = array_unique(array_merge($aTagged, $aMentions));
        foreach ($allTagged as $userId) {
            Phpfox::getService('notification.process')->add($callback['notification_post_tag'], $itemId, $userId);
        }
    }

    /**
     * @param $sStatus
     * @param $iItemId
     * @param $sTypeId
     * @param null $iUserId
     * @return bool
     */
    public function canRemoveTagFromFeed($sStatus, $iItemId, $sTypeId, $iUserId = null)
    {
        if (!$iUserId) {
            $iUserId = (int)Phpfox::getUserId();
        }
        if ($this->isRemovedTag($iUserId, $iItemId, $sTypeId)) {
            return false;
        }
        // check tags status
        $iCountTag = 0;
        if (!empty($sStatus)) {
            //check mention first
            $iCountTag = preg_match_all('/\[user=' . $iUserId . '\].+?\[\/user\]/i', $sStatus, $aMatches);
        }
        if (!$iCountTag) {
            //check tag if no mention
            $iCountTag = db()->select('COUNT(*)')
                ->from($this->_sTable, 'td')
                ->join(':user', 'u', 'u.user_id = td.user_id')
                ->where([
                    'td.item_id' => (int)$iItemId,
                    'td.type_id' => $sTypeId,
                    'td.user_id' => (int)$iUserId
                ])->executeField();
        }

        return !!$iCountTag;
    }

    /**
     * @param $iUserId
     * @param $iItemId
     * @param $sTypeId
     * @return array|int|resource|string
     */
    public function isRemovedTag($iUserId, $iItemId, $sTypeId)
    {
        return db()->select('COUNT(*)')
            ->from($this->_sTableRemove)
            ->where([
                'user_id' => (int)$iUserId,
                'item_id' => $iItemId,
                'type_id' => $sTypeId
            ])->executeField();
    }

    /**
     * @param array $aCallback
     *
     * @return $this
     */
    public function callback($aCallback)
    {
        if (isset($aCallback['module'])) {
            $this->_bIsCallback = true;
            $this->_aCallback = $aCallback;
        }

        return $this;
    }

    /**
     * @param $iUserId
     * @param $iItemId
     * @param $sTypeId
     * @return bool|int|string
     */
    public function removeTag($iUserId, $iItemId, $sTypeId)
    {
        if ($this->isRemovedTag($iUserId, $iItemId, $sTypeId)) {
            return false;
        }
        $iId = db()->insert($this->_sTableRemove, [
            'user_id' => $iUserId,
            'item_id' => $iItemId,
            'type_id' => $sTypeId
        ]);
        //Remove tag
        db()->delete($this->_sTable, [
            'user_id' => $iUserId,
            'item_id' => $iItemId,
            'type_id' => $sTypeId
        ]);

        //Remove feed
        db()->delete(Phpfox::getT(($this->_bIsCallback ? $this->_aCallback['table_prefix'] : '') . 'feed'), "item_id = {$iItemId} AND type_id = '{$sTypeId}' AND parent_user_id = {$iUserId} AND parent_feed_id > 0");

        //Remove notification
        db()->delete(':notification', "item_id = {$iItemId} AND user_id = {$iUserId} AND (type_id LIKE \"%_tag_%\" OR type_id LIKE \"%_tag\" OR type_id LIKE \"%_tagged_%\" OR type_id LIKE \"%_tagged\")");

        $this->cache()->remove('tagged_users_' . $sTypeId . '_' . $iItemId);

        (($sPlugin = Phpfox_Plugin::get('feed.service_tag_remove_tag_end')) ? eval($sPlugin) : false);

        return $iId;
    }

    public function stripContentHashTag($sContent, $iItemId, $sTypeId)
    {
        return preg_replace_callback('/\[user=(\d+)\](.+?)\[\/user\]/iu', function ($aMatch) use ($iItemId, $sTypeId) {
            return $this->_stripHashTag($aMatch, $iItemId, $sTypeId);
        }, $sContent);
    }

    private function _stripHashTag($aMatch, $iItemId, $sTypeId)
    {
        static $aRemovedTag = [];

        if (is_array($aMatch) && count($aMatch) > 2) {
            $iUserId = $aMatch[1];
            if (isset($aRemovedTag["{$sTypeId}_{$iItemId}"]) && in_array($iUserId, $aRemovedTag["{$sTypeId}_{$iItemId}"])) {
                return $aMatch[2];
            } elseif ($this->isRemovedTag($iUserId, $iItemId, $sTypeId)) {
                $aRemovedTag["{$sTypeId}_{$iItemId}"][] = $iUserId;
                return $aMatch[2];
            }
            return $aMatch[0];
        }
        return '';
    }

    public function getAllRemovedTag($iItemId, $sTypeId)
    {
        return db()->select('ftr.*')
                ->from($this->_sTableRemove, 'ftr')
                ->join(':user', 'u', 'u.user_id = ftr.user_id')
                ->where([
                    'ftr.item_id' => (int)$iItemId,
                    'ftr.type_id' => $sTypeId
                ])->executeRows();
    }

    public function validateEditStatusTag($sTagsId, $iItemId, $sTypeId)
    {
        if (is_array($sTagsId)) {
            $sTagsId = implode(',', $sTagsId);
        }
        if (!empty($sTagsId)) {
            $iRemoved = db()->select('COUNT(*)')
                ->from($this->_sTableRemove, 'ftr')
                ->join(':user', 'u', 'u.user_id = ftr.user_id')
                ->where([
                    'ftr.item_id' => (int)$iItemId,
                    'ftr.type_id' => $sTypeId,
                    ' AND ftr.user_id IN ('. $sTagsId .')'
                ])->executeField();
            if ($iRemoved) {
                Phpfox_Error::set(_p('cannot_add_tag_error'));
                return false;
            }
        }
        return true;
    }
}