<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Tag_Service_Tag
 */
class Tag_Service_Tag extends Phpfox_Service
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('tag');
    }

    public function get($sCategory, $sTag, $aConds = array(), $sSort = '', $iPage = '', $sLimit = '')
    {
        return Phpfox::hasCallback($sCategory, 'getTags') ? Phpfox::callback($sCategory . '.getTags', $sTag, $aConds, $sSort, $iPage, $sLimit) : null;
    }

    public function getSearch($sCategory, $aConds, $sSort)
    {
        return Phpfox::hasCallback($sCategory, 'getTagSearch') ? Phpfox::callback($sCategory . '.getTagSearch', $aConds, $sSort) : null;
    }

    public function getTagInfo($sModule, $sTag, $iType = 0)
    {
        $aRow = $this->database()->select('*, COUNT(item_id) AS total')
            ->from(Phpfox::getT('tag'))
            ->where('category_id = \'' . $this->database()->escape($sModule) . '\' AND tag_url = \'' . $this->database()->escape($sTag) . '\' AND tag_type = ' . (int)$iType)
            ->group('tag_id')
            ->execute('getSlaveRow');

        return (isset($aRow['tag_id']) ? $aRow : false);
    }

    public function getTagsById($sCategory, $sId, $iType = 0)
    {
        if (!$sId) {
            return [];
        }

        $bUseSubQuery = !is_numeric($sId);
        $aWhere = [
            'tag.tag_type' => (int)$iType,
        ];

        if ($bUseSubQuery) {
            db()->select('tag.tag_id')
                ->from(':tag', 'tag')
                ->where([
                    'tag.item_id' => ['in' => $sId],
                    'tag.category_id' => $sCategory,
                ])
                ->union()
                ->unionFrom('sub_tag');
        } else {
            $aWhere = array_merge([
                'tag.item_id' => $sId,
                'tag.category_id' => $sCategory,
            ], $aWhere);
        }

        if ($bUseSubQuery) {
            db()->join(':tag', 'tag', 'tag.tag_id = sub_tag.tag_id');
        } else {
            db()->from(':tag', 'tag');
        }

        //Tag type: 1 HashTag | 0 Tag
        $aRows = db()->select('tag.tag_id, tag.item_id, tag.tag_text, tag.tag_url')
            ->where($aWhere)
            ->limit(Phpfox::getParam('tag.tag_trend_total_display'))
            ->execute('getSlaveRows');

        $aTags = [];
        foreach ($aRows as $aRow) {
            $aTags[$aRow['item_id']][] = $aRow;
        }

        return $aTags;
    }

    public function getForEdit($sCategory, $iId, $iType = 0)
    {
        $sList = '';
        $aTags = Phpfox::getService('tag')->getTagsById($sCategory, $iId, $iType);

        if (isset($aTags[$iId])) {
            foreach ($aTags[$iId] as $aTag) {
                $sList .= ' ' . $aTag['tag_text'] . ',';
            }
            $sList = trim(trim($sList, ','));
        }

        return $sList;
    }

    public function getTagCloud($sCategory, $iUserId = null, $mMaxDisplay = null, $iType = null)
    {
        (($sPlugin = Phpfox_Plugin::get('tag.service_tag_gettagcloud_start')) ? eval($sPlugin) : false);

        if ($sCategory === null) {
            $aParams = Phpfox::massCallback('getTagCloud');
        } else {
            $aParams = Phpfox::hasCallback($sCategory, 'getTagCloud') ? Phpfox::callback($sCategory . '.getTagCloud') : [];
        }

        $sCacheId = $this->cache()->set('tag_cloud_' . ($sCategory === null ? 'global' : str_replace('/', '_', $aParams['link'])) . ($iUserId !== null ? '_' . $iUserId : '') . (defined('TAG_ITEM_ID') ? '_' . TAG_ITEM_ID : ''));

        if (defined('PHPFOX_IS_GROUP_INDEX')) {
            $sCategory = 'video_group';
        }

        if (($aTempTags = $this->cache()->get($sCacheId, Phpfox::getParam('tag.tag_cache_tag_cloud'))) === false) {
            $aWhere = [];

            if (defined('TAG_ITEM_ID')) {
                $aWhere[] = 'AND t.item_id = ' . (int)TAG_ITEM_ID;
            }

            if ($sCategory !== null) {
                $aWhere[] = "AND t.category_id = '" . $this->database()->escape($aParams['category']) . "'" . ($iUserId !== null ? ' AND t.user_id = ' . (int)$iUserId : '');
            }

            if (!defined('TAG_ITEM_ID')) {
                $aWhere[] = 'AND t.added > ' . (PHPFOX_TIME - (86400 * Phpfox::getParam('tag.tag_days_treading')));
            }

            if ($iType !== null) {
                $aWhere[] = 'AND t.tag_type =' . (int)$iType;
            }

            $iLimit = ($mMaxDisplay) ? $mMaxDisplay : Phpfox::getParam('tag.total_tag_display');

            (($sPlugin = Phpfox_Plugin::get('tag.service_tag_gettagcloud_before_query')) ? eval($sPlugin) : false);

            $aRows = $this->database()->select('t.tag_text AS tag, t.tag_url, COUNT(t.item_id) AS total')
                ->from(Phpfox::getT('tag'), 't')
                ->where($aWhere)
                ->group('t.tag_text, t.tag_url')
                ->having('COUNT(t.item_id) >= ' . (int)Phpfox::getParam('tag.tag_min_display'))
                ->order('total DESC')
                ->limit($iLimit)
                ->execute('getSlaveRows');

            if (!count($aRows)) {
                $this->cache()->save($sCacheId, []);
                Phpfox::getLib('cache')->group('tag', $sCacheId);
                return array();
            }

            if ($sCategory === null) {
                $aParams['link'] = 'search';
            }

            if ($sCategory === null && Phpfox::getParam('tag.enable_hashtag_support')) {
                $sLink = Phpfox_Url::instance()->makeUrl('hashtag');
            } else {
                $sLink = Phpfox_Url::instance()->makeUrl($aParams['link']) . 'tag/';
            }

            $aTempTags = array();
            foreach ($aRows as $aRow) {
                $aTempTags[] = array
                (
                    'value' => $aRow['total'],
                    'key' => $aRow['tag'],
                    'url' => $aRow['tag_url'],
                    'link' => $sLink . urlencode($aRow['tag_url']) . '/'
                );
            }

            if (!count($aTempTags)) {
                $this->cache()->save($sCacheId, []);
                Phpfox::getLib('cache')->group('tag', $sCacheId);
                return array();
            }

            $this->cache()->save($sCacheId, $aTempTags);
            Phpfox::getLib('cache')->group('tag', $sCacheId);
        }

        if ($aTempTags === true) {
            $aTempTags = [];
        }

        (($sPlugin = Phpfox_Plugin::get('tag.service_tag_gettagcloud_end')) ? eval($sPlugin) : false);

        return $aTempTags;
    }

    public function getInlineSearchForUser($iUserId, $sTag, $sCategory)
    {
        (($sPlugin = Phpfox_Plugin::get('tag.service_tag_getinlinesearchforuser_start')) ? eval($sPlugin) : false);

        $aTags = array();
        $aRows = $this->database()->select('tag.tag_text')
            ->from($this->_sTable, 'tag')
            ->where("tag.category_id = '" . $this->database()->escape($sCategory) . "' AND tag.user_id = " . $iUserId . " AND tag.tag_text LIKE '%" . $this->database()->escape($sTag) . "%'")
            ->limit(0, 5)
            ->execute('getSlaveRows');

        foreach ($aRows as $aRow) {
            if (isset($aTags[$aRow['tag_text']])) {
                continue;
            }
            $aTags[$aRow['tag_text']]['tag_text'] = $aRow['tag_text'];
        }
        unset($aRows);

        (($sPlugin = Phpfox_Plugin::get('tag.service_tag_getinlinesearchforuser_end')) ? eval($sPlugin) : false);

        return $aTags;
    }

    public function hasAccess($sType, $iId, $sUserPerm, $sGlobalPerm)
    {
        (($sPlugin = Phpfox_Plugin::get('tag.service_tag_hasaccess_start')) ? eval($sPlugin) : false);

        $aRow = $this->database()->select('u.user_id')
            ->from($this->_sTable, 'tag')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = tag.user_id')
            ->where("tag.item_id = " . (int)$iId . " AND tag.category_id = '" . $this->database()->escape($sType) . "'")
            ->execute('getSlaveRow');

        (($sPlugin = Phpfox_Plugin::get('tag.service_tag_hasaccess_end')) ? eval($sPlugin) : false);

        if (!isset($aRow['user_id'])) {
            return false;
        }

        if ((Phpfox::getUserId() == $aRow['user_id'] && Phpfox::getUserParam('tag.' . $sUserPerm)) || Phpfox::getUserParam('tag.' . $sGlobalPerm)) {
            return $aRow['user_id'];
        }

        return false;
    }

    public function getCount($mTags)
    {
        $iTagCount = 0;

        if (is_array($mTags)) {
            foreach ($mTags as $sTag) {
                if (empty($sTag)) {
                    continue;
                }
                $iTagCount++;
            }
        } else {
            $iTagCount = count(explode(',', rtrim($mTags, ',')));
        }

        return $iTagCount;
    }

    /**
     * Returns the keywords used in a <meta> keyword call.
     *
     * @param array $aTags Is the array of tags
     *
     * @return string New string of tags separated with a comma
     */
    public function getKeywords($aTags)
    {
        $sTags = '';
        foreach ($aTags as $aTag) {
            $sTags .= $aTag['tag_text'] . ', ';
        }
        $sTags = rtrim(trim($sTags), ',');

        return Phpfox::getLib('parse.output')->clean($sTags);
    }

    /**
     * If a call is made to an unknown method attempt to connect
     * it to a specific plug-in with the same name thus allowing
     * plug-in developers the ability to extend classes.
     *
     * @param string $sMethod is the name of the method
     * @param array $aArguments is the array of arguments of being passed
     * @return mixed
     */
    public function __call($sMethod, $aArguments)
    {
        /**
         * Check if such a plug-in exists and if it does call it.
         */
        if ($sPlugin = Phpfox_Plugin::get('tag.service_tag__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}
