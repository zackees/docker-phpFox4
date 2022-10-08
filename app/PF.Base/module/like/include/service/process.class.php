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
 * @package        Module_Like
 * @version        $Id: process.class.php 7114 2014-02-17 19:38:37Z phpFox LLC $
 */
class Like_Service_Process extends Phpfox_Service
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('like');
    }

    /**
     * Add like/reaction
     * @param $sType
     * @param $iItemId
     * @param null $iUserId
     * @param null $app_id
     * @param array $params
     * @param string $sTablePrefix
     * @param null $iReactId
     * @param bool $bIsReReact
     * @return bool
     * @throws Exception
     */
    public function add($sType, $iItemId, $iUserId = null, $app_id = null, $params = [], $sTablePrefix = '', $iReactId = null, $bIsReReact = false)
    {
        $bIsNotNull = false;
        if ($iUserId === null) {
            $iUserId = Phpfox::getUserId();
            $bIsNotNull = true;
        }
        if ($sType == 'pages') {
            $bIsNotNull = false;
        }

        // check if iUserId can Like this item
        $aFeed = $this->database()->select('*')
            ->from(Phpfox::getT($sTablePrefix . 'feed'))
            ->where(($app_id === null ? (!empty($params['feed_id']) ? 'feed_id = ' . db()->escape($params['feed_id']) : 'item_id = ' . (int)$iItemId . ' AND type_id = \'' . Phpfox::getLib('parse.input')->clean($sType) . '\'') : 'feed_id = ' . (int)$iItemId))
            ->execute('getSlaveRow');

        if (!empty($aFeed['privacy'])
            && $aFeed['user_id'] != $iUserId) {

            $granted = true;

            if (Phpfox::getService('user.block')->isBlocked($iUserId, $aFeed['user_id'])) {
                $granted = false;
            } elseif (!empty($aFeed['parent_user_id']) && !$sTablePrefix && Phpfox::getService('user.block')->isBlocked($iUserId, $aFeed['parent_user_id'])) {
                $granted = false;
            }

            if ($granted) {
                switch ((int)$aFeed['privacy']) {
                    case 1:
                        if (Phpfox::isModule('friend')) {
                            if (!$sTablePrefix && !empty($aFeed['parent_user_id']) && $aFeed['parent_user_id'] != $iUserId) {
                                $granted = Phpfox::getService('friend')->isFriend($aFeed['parent_user_id'], $iUserId);
                            } else {
                                $granted = Phpfox::getService('friend')->isFriend($aFeed['user_id'], $iUserId);
                            }
                        } else {
                            $granted = false;
                        }
                        break;
                    case 2:
                        if (Phpfox::isModule('friend')) {
                            if (!$sTablePrefix && !empty($aFeed['parent_user_id']) && $aFeed['parent_user_id'] != $iUserId) {
                                $granted = Phpfox::getService('friend')->isFriend($aFeed['parent_user_id'], $iUserId) || Phpfox::getService('friend')->isFriendOfFriend($aFeed['parent_user_id']);
                            } else {
                                $granted = Phpfox::getService('friend')->isFriend($aFeed['user_id'], $iUserId) || Phpfox::getService('friend')->isFriendOfFriend($aFeed['user_id']);
                            }
                        } else {
                            $granted = false;
                        }
                        break;
                    case 3:
                        $granted = Phpfox::isModule('feed') && Phpfox::getService('feed.tag')->checkTaggedUser($iItemId, $sType, $iUserId);
                        break;
                    case 4:
                        $granted = Phpfox::getService('privacy')->check($sType, $iItemId, $aFeed['user_id'], $aFeed['privacy'], null, true);
                        break;
                }
            }

            if (!$granted) {
                return Phpfox_Error::set(_p('you_are_not_allowed_to_like_this_item'));
            }
        }

        $iCheck = $this->database()->select('COUNT(*)')
            ->from(Phpfox::getT('like'))
            ->where('type_id = \'' . $this->database()->escape($sType) . '\' AND item_id = ' . (int)$iItemId . ' AND user_id = ' . $iUserId)
            ->execute('getSlaveField');

        if ($iCheck) {
            if (Phpfox::isAppActive('P_Reaction') && $bIsReReact) {
                $iCheckReacted = db()->select('COUNT(*)')
                    ->from($this->_sTable)
                    ->where('type_id = \'' . db()->escape($sType) . '\' AND item_id = ' . (int)$iItemId . ' AND user_id = ' . $iUserId . ' AND react_id = ' . (int)$iReactId)
                    ->execute('getSlaveField');
                if ($iCheckReacted) {
                    return Phpfox_Error::set(_p('you_have_already_reacted_this_feed'));
                }
                $this->delete($sType, $iItemId, $iUserId, false, $sTablePrefix);
            } else {
                return Phpfox_Error::set(_p('you_have_already_reacted_this_feed'));
            }
        }

        //check permission when like an item
        if (empty($params['ignoreCheckPermission']) && Phpfox::isModule($sType) && Phpfox::hasCallback($sType, 'canLikeItem') && !Phpfox::callback($sType . '.canLikeItem', $iItemId)) {
            return Phpfox_Error::set(_p('you_are_not_allowed_to_like_this_item'));
        }

        $iCnt = (int)$this->database()->select('COUNT(*)')
            ->from(Phpfox::getT('like_cache'))
            ->where('type_id = \'' . $this->database()->escape($sType) . '\' AND item_id = ' . (int)$iItemId . ' AND user_id = ' . (int)$iUserId)
            ->execute('getSlaveField');

        $data = [
            'type_id' => $sType,
            'item_id' => (int)$iItemId,
            'user_id' => $iUserId,
            'time_stamp' => PHPFOX_TIME
        ];

        if ($sPlugin = Phpfox_Plugin::get('like.service_process_add_start')) {
            eval($sPlugin);
        }

        if (Phpfox::isAppActive('P_Reaction') && $iReactId) {
            $data['react_id'] = (int)$iReactId;
        }

        if ($sType == 'app') {
            $data['feed_table'] = $sTablePrefix . 'feed';
        }
        $this->database()->insert($this->_sTable, $data);
        //Update time_update of feed when like
        if (Phpfox::getParam('feed.top_stories_update') != 'comment') {
            $this->database()->update(Phpfox::getT($sTablePrefix . 'feed'), [
                'time_update' => PHPFOX_TIME
            ], [
                    'item_id' => (int)$iItemId,
                    'type_id' => $sType
                ]
            );

            if (!empty($sTablePrefix)) {
                $this->database()->update(Phpfox::getT('feed'), [
                    'time_update' => PHPFOX_TIME
                ], [
                        'item_id' => (int)$iItemId,
                        'type_id' => $sType
                    ]
                );
            }
        }
        if (!$iCnt) {
            $this->database()->insert(Phpfox::getT('like_cache'), [
                    'type_id' => $sType,
                    'item_id' => (int)$iItemId,
                    'user_id' => $iUserId
                ]
            );
        }

        (Phpfox::isModule('feed') ? Phpfox::getService('feed.process')->clearCache($sType, $iItemId) : null);

        if ($sPlugin = Phpfox_Plugin::get('like.service_process_add__1')) {
            eval($sPlugin);
        }

        if ($sType == 'app') {
            $app = app($app_id);
            if (isset($app->notifications) && isset($app->notifications->{'__like'})) {
                notify($app->id, '__like', $iItemId, $aFeed['user_id'], false);
            }
            return true;
        }
        if (Phpfox::hasCallback($sType, 'addLike')) {
            Phpfox::callback($sType . '.addLike', $iItemId, ($iCnt ? true : false), ($bIsNotNull ? null : $iUserId));
        }

        return true;
    }

    public function delete($sType, $iItemId, $iUserId = 0, $bDeleteItem = false, $sTablePrefix = '')
    {
        $sExtraCond = ($sType == 'app') ? " AND feed_table = '{$sTablePrefix}feed'" : '';
        if ($iUserId > 0 && ($sType == 'pages' || $sType == 'groups')) {
            if (!Phpfox::getService($sType)->isAdmin($iItemId)) {
                return Phpfox_Error::set(_p('unable_to_remove_this_user_dot'));
            }

            $this->database()->delete(Phpfox::getT('like'), 'type_id = \'' . $this->database()->escape($sType) . '\' AND item_id = ' . (int)$iItemId . ' AND user_id = ' . $iUserId . $sExtraCond);
        } else {
            if (!$bDeleteItem) {
                $iUserId = Phpfox::getUserId();
                $this->database()->delete(Phpfox::getT('like'), 'type_id = \'' . $this->database()->escape($sType) . '\' AND item_id = ' . (int)$iItemId . ' AND user_id = ' . $iUserId . $sExtraCond);
            } else {
                $this->database()->delete(Phpfox::getT('like'), 'type_id = \'' . $this->database()->escape($sType) . '\' AND item_id = ' . (int)$iItemId . $sExtraCond);
                $this->database()->delete(Phpfox::getT('like_cache'), 'type_id = \'' . $this->database()->escape($sType) . '\' AND item_id = ' . (int)$iItemId);
            }

        }

        (Phpfox::isModule('feed') ? Phpfox::getService('feed.process')->clearCache($sType, $iItemId) : null);

        if (!$bDeleteItem && Phpfox::hasCallback($sType, 'deleteLike')) {
            Phpfox::callback($sType . '.deleteLike', $iItemId, $iUserId);
        }

        return true;
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
        if ($sPlugin = Phpfox_Plugin::get('like.service_process__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}