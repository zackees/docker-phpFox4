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
 * @version        $Id: like.class.php 7054 2014-01-20 18:35:55Z Fern $
 */
class Like_Service_Like extends Phpfox_Service
{
    private $_iTotalLikeCount = 0;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('like');
    }

    public function getTotalLikes()
    {
        return $this->_iTotalLikeCount;
    }

    public function getLikesForFeed($sType, $iItemId, $bIsLiked = false, $iLimit = 4, $bLoadCount = false, $sFeedTablePrefix = '')
    {
        $sWhere = '(l.type_id = \'' . $this->database()->escape(str_replace('-', '_', $sType)) . '\' OR l.type_id = \'' . str_replace('_', '-', $sType) . '\') AND l.item_id = ' . (int)$iItemId;
        if ($sType == 'app') {
            $sWhere .= " AND l.feed_table = '{$sFeedTablePrefix}feed'";
        }
        $this->database()->where($sWhere . ' AND l.user_id != ' . Phpfox::getUserId());
        $aRowLikes = $this->database()->select('l.*, ' . Phpfox::getUserField() . ', a.time_stamp as action_time_stamp, f.friend_id AS is_friend')
            ->from($this->_sTable, 'l')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = l.user_id')
            ->leftJoin(Phpfox::getT('action'), 'a', 'a.item_id = l.item_id AND a.user_id = l.user_id AND a.item_type_id = \'' . str_replace('_', '-', $this->database()->escape($sType)) . '\'')
            ->leftJoin(Phpfox::getT('friend'), 'f', 'f.friend_user_id = l.user_id AND f.user_id = ' . Phpfox::getUserId())
            ->group('u.user_id, l.like_id, action_time_stamp', true)
            ->order('is_friend DESC, l.time_stamp DESC')
            ->limit($iLimit)
            ->execute('getSlaveRows');
        $aLikes = array();
        $aDontCount = array();
        foreach ($aRowLikes as $iKey => $aLike) {
            if (!empty($aLike['action_time_stamp']) && $aLike['action_time_stamp'] > $aLike['time_stamp']) {
                $aDontCount[] = $aLike['like_id'];

                continue;
            }
            $aLikes[$aLike['user_id']] = $aLike;
        }
        $this->_iTotalLikeCount = count($aLikes);
        if ($bLoadCount == true) {
            if (!empty($aDontCount)) {
                $sWhere .= ' AND l.like_id NOT IN (' . implode(',', $aDontCount) . ')';
            }
            $this->_iTotalLikeCount = $this->database()->select('COUNT(*)')
                ->from(Phpfox::getT('like'), 'l')
                ->where($sWhere)
                ->execute('getSlaveField');
        }
        return $aLikes;
    }

    public function getTotalLikeCount()
    {
        return $this->_iTotalLikeCount;
    }

    public function getLikes($sType, $iItemId, $sPrefix = '', $bGetCount = false, $iPage = 0, $iTotal = null)
    {
        $sPrefix = $sPrefix . 'feed';
        if ($sType == 'feed') {
            $sWhere = '(l.type_id = "feed" OR l.type_id = "feed_comment") AND l.item_id = ' . (int)$iItemId;
        } elseif ($sType == 'photo') {
            $sWhere = '(l.type_id = "photo" OR l.type_id = "user_photo") AND l.item_id = ' . (int)$iItemId;
        } else {
            $sWhere = 'l.type_id = \'' . $this->database()->escape($sType) . '\' AND l.item_id = ' . (int)$iItemId . ($sType == 'app' ? " AND feed_table = '{$sPrefix}'" : '');
        }
        if (!$bGetCount) {
            $aBlockUserIds = Phpfox::getService('user.block')->get(Phpfox::getUserId(), true);
            if (count($aBlockUserIds)) {
                $sWhere .= ' AND l.user_id NOT IN (' . implode(',', $aBlockUserIds) . ')';
            }
        }
        $this->database()
            ->from(Phpfox::getT('like'), 'l')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = l.user_id')
            ->leftJoin(Phpfox::getT('friend'), 'f', 'f.friend_user_id = l.user_id AND f.user_id =' . Phpfox::getUserId());

        if ($bGetCount) {
            return $this->database()->select('count(*)')->where($sWhere)->executeField();
        } else {
            if ($iPage) {
                $this->database()->limit($iPage, $iTotal);
            }
            return $this->database()->select(Phpfox::getUserField() . ', f.friend_id AS is_friend')
                ->group('u.user_id')
                ->where($sWhere)
                ->order('FIELD(u.user_id, ' . Phpfox::getUserId() . ') DESC, is_friend DESC, u.full_name ASC')
                ->execute('getSlaveRows');
        }
    }

    public function getForMembers($sType, $iItemId, $iLimit = null)
    {
        $iCnt = $this->database()->select('COUNT(*)')
            ->from(Phpfox::getT('like'), 'l')
            ->where('l.type_id = \'' . $this->database()->escape($sType) . '\' AND l.item_id = ' . (int)$iItemId)
            ->execute('getSlaveField');

        $aLikes = $this->database()->select('uf.total_friend, ' . Phpfox::getUserField())
            ->from(Phpfox::getT('like'), 'l')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = l.user_id')
            ->join(Phpfox::getT('user_field'), 'uf', 'u.user_id = uf.user_id')
            ->where('l.type_id = \'' . $this->database()->escape($sType) . '\' AND l.item_id = ' . (int)$iItemId)
            ->order('u.full_name ASC')
            ->group('u.user_id')
            ->limit(($iLimit === null ? 5 : $iLimit))
            ->execute('getSlaveRows');

        return array($iCnt, $aLikes);
    }

    public function didILike($sType, $iItemId, $aLikes = array(), $sPrefix = '')
    {
        $sType = str_replace('-', '_', $sType);
        if (empty($aLikes) || !is_array($aLikes)) {
            $aLikes = $this->getLikes($sType, $iItemId, $sPrefix);
        }
        foreach ($aLikes as $aLike) {
            if ($aLike['user_id'] == Phpfox::getUserId()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $sType
     * @param $iItem
     * @param string $sPrefix
     * @return array
     * @throws Exception
     */
    public function getAll($sType, $iItem, $sPrefix = '')
    {
        $aLikes = $this->getLikes($sType, $iItem, $sPrefix);
        $aFeed = array('likes' => $aLikes);
        $aFeed['type_id'] = $sType;
        $aFeed['item_id'] = $iItem;
        $aFeed['feed_table_prefix'] = $sPrefix;

        if (Phpfox::isAppActive('P_Reaction')) { // check and support reaction app
            $sLikePhrase = Phpfox::getService('preaction')->getReactionsPhrase($aFeed);
        } else {
            $sLikePhrase = Phpfox::getService('feed')->getPhraseForLikes($aFeed);
        }

        $aOut = array(
            'likes' => array(
                'total' => count($aLikes),
                'phrase' => $sLikePhrase,
                'most_reactions' => isset($aFeed['most_reactions']) ? $aFeed['most_reactions'] : []
            )
        );

        return $aOut;
    }

    public function getLikedByPage($iPageId, $iUserId)
    {
        $aPage = db()->select('p.*, l.like_id as is_liked, pg.page_type')
            ->from(':pages', 'p')
            ->leftJoin(':like', 'l', 'l.type_id = \'pages\' AND l.item_id = p.page_id AND l.user_id =' . (int)$iUserId)
            ->leftJoin(':pages_category', 'pg', 'pg.category_id = p.category_id')
            ->where('p.page_id = ' . (int)$iPageId . ' AND p.item_type = 0')
            ->execute('getRow');
        if (!$aPage) {
            return false;
        }
        if ($aPage['reg_method'] == '2' && $iUserId) {
            $aPage['is_invited'] = (int)db()->select('COUNT(*)')
                ->from(':pages_invite')
                ->where('page_id = ' . (int)$aPage['page_id'] . ' AND invited_user_id = ' . (int)$iUserId)
                ->execute('getSlaveField');
            if (!$aPage['is_invited']) {
                unset($aPage['is_invited']);
            }
        }
        if (($aPage['page_type'] == '1' || $aPage['item_type'] != '0') && $aPage['reg_method'] == '1') {
            $aPage['is_reg'] = (int)db()->select('COUNT(*)')
                ->from(':pages_signup')
                ->where('page_id = ' . (int)$aPage['page_id'] . ' AND user_id = ' . (int)$iUserId)
                ->execute('getSlaveField');
        }
        return $aPage;
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
        if ($sPlugin = Phpfox_Plugin::get('like.service_like__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}