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
 * @package        Module_Privacy
 * @version        $Id: privacy.class.php 6872 2013-11-11 16:30:16Z Fern $
 */
class Privacy_Service_Privacy extends Phpfox_Service
{
    public $service;
    public $isCount = false;
    public $condition = [];

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('privacy');
    }

    /**
     * @param $itemType
     * @param null $callbackModule
     * @param null $callbackItemId
     * @return false|int
     */
    public function getDefaultItemPrivacy($itemType, $callbackModule = null, $callbackItemId = null)
    {
        if (empty($itemType)) {
            return false;
        }

        if (!empty($callbackModule) && !empty($callbackItemId)) {
            if (in_array($callbackModule, ['pages', 'groups']) || !Phpfox::hasCallback($callbackModule, 'getDefaultItemPrivacy')) {
                $defaultPrivacy = 0;
            } else {
                $defaultPrivacy = Phpfox::callback($callbackModule . '.getDefaultItemPrivacy', [
                    'parent_id' => $callbackItemId,
                    'item_type' => $itemType,
                ]);
            }
        } else {
            $defaultPrivacy = Phpfox::getParam('core.friends_only_community') ? (Phpfox::isModule('friend') ? 1 : 3) : 0;
        }

        return $defaultPrivacy;
    }

    public function get($sModule, $iItemId)
    {
        (($sPlugin = Phpfox_Plugin::get('privacy.service_privacy_get')) ? eval($sPlugin) : false);

        $aRows = $this->database()->select('privacy.*')
            ->from($this->_sTable, 'privacy')
            ->where("module_id = '" . $this->database()->escape($sModule) . "' AND item_id = " . (int)$iItemId . "")
            ->execute('getSlaveRows');

        return $aRows;
    }

    /**
     * Verify if a user is allowed to view a private item (eg. blog, photo etc...)
     *
     * @param string $sCategory Is the module name
     * @param int $iItemId Is the item unique ID#
     * @param int $iUserId Is the items users ID#
     * @param bool $bRedirect Option to redirect on failure
     * @return bool Return true if user can view the item, or false on failure
     */
    public function verify($sCategory, $iItemId, $iUserId, $bRedirect = true)
    {
        $iCnt = 0;
        if (Phpfox::getUserParam('core.can_view_private_items')) {
            $iCnt = 1;
        }

        if (!Phpfox::getUserId()) {
            $iCnt = 0;
        }

        if ($iCnt === 0) {
            $iCnt = $this->database()->select('COUNT(*)')
                ->from($this->_sTable)
                ->where("item_id = " . (int)$iItemId . " AND module_id = '" . $this->database()->escape($sCategory) . "' AND user_id = " . Phpfox::getUserId() . "")
                ->execute('getSlaveField');
        }

        if ((int)$iCnt === 0) {
            if ($bRedirect) {
                Phpfox_Url::instance()->send('privacy.invalid');
            }

            return false;
        }

        return true;
    }

    public function getForBrowse(&$aUser)
    {
        $sPrivacy = '0';
        if ($aUser['user_id'] == Phpfox::getUserId() || Phpfox::getUserParam('privacy.can_view_all_items')) {
            $sPrivacy = '0,1,2,3,4,6';
        } else {
            if ($aUser['is_friend']) {
                $sPrivacy = '0,1,2,6';
            } elseif ($aUser['is_friend_of_friend']) {
                $sPrivacy = '0,2,6';
            }
        }

        return $sPrivacy;
    }

    public function check($sModule, $iItemId, $iUserId, $iPrivacy, $iIsFriend = null, $bReturn = false, $bCheckCommunity = false, $iCurrentUserId = null)
    {
        if (empty($iCurrentUserId)) {
            $iCurrentUserId = Phpfox::getUserId();
        }

        if (!isset($iIsFriend) && Phpfox::isModule('friend')) {
            if (Phpfox::getService('friend')->isFriend($iCurrentUserId, $iUserId)) {
                $iIsFriend = $iCurrentUserId;
            } else {
                $iIsFriend = 0;
            }
        }

        if ($iCurrentUserId == Phpfox::getUserId()) {
            $bCanViewAllItems = Phpfox::getUserParam('privacy.can_view_all_items');
        } else {
            $userGroupId = db()->select('user_group_id')
                ->from(':user')
                ->where([
                    'user_id' => $iCurrentUserId
                ])->executeField();
            $bCanViewAllItems = Phpfox::getService('user.group.setting')->getGroupParam($userGroupId, 'privacy.can_view_all_items');
        }

        $bCanViewItem = true;
        if ($iUserId != $iCurrentUserId && !$bCanViewAllItems) {
            switch ($iPrivacy) {
                case 0:
                    if ($bCheckCommunity && Phpfox::getParam('core.friends_only_community') && !$iIsFriend) {
                        $bCanViewItem = false;
                    }
                    break;
                case 1:
                    if ((int)$iIsFriend <= 0) {
                        $bCanViewItem = false;
                    }
                    break;
                case 2:
                    if ((int)$iIsFriend > 0) {
                        $bCanViewItem = true;
                    } else {
                        if (Phpfox::isModule('friend') && !Phpfox::getService('friend')->isFriendOfFriend($iUserId)) {
                            $bCanViewItem = false;
                        }
                    }
                    break;
                case 3:
                    $bCanViewItem = false;
                    break;
                case 4:
                    if (Phpfox::isUser()) {
                        $iCheck = (int)$this->database()->select('COUNT(privacy_id)')
                            ->from($this->_sTable, 'p')
                            ->join(Phpfox::getT('friend_list_data'), 'fld', 'fld.list_id = p.friend_list_id AND fld.friend_user_id = ' . $iCurrentUserId)
                            ->where('p.module_id = \'' . $this->database()->escape($sModule) . '\' AND p.item_id = ' . (int)$iItemId . '')
                            ->execute('getSlaveField');

                        if ($iCheck === 0) {
                            $bCanViewItem = false;
                        }
                    } else {
                        $bCanViewItem = false;
                    }
                    break;
                case 6:
                    if (!Phpfox::isUser() || (Phpfox::getParam('core.friends_only_community') && !$iIsFriend)) {
                        $bCanViewItem = false;
                    }
                    break;
            }
        }

        if ($bReturn === true) {
            return $bCanViewItem;
        }

        if ($bCanViewItem === false) {
            Phpfox_Url::instance()->send('privacy.invalid');
        }

        return null;
    }

    public function getPhrase($iPrivacy)
    {
        switch ((int)$iPrivacy) {
            case 1:
                $sPhrase = _p('friends');
                break;
            case 2:
                $sPhrase = _p('friends_of_friends');
                break;
            case 3:
                $sPhrase = _p('only_me');
                break;
            case 4:
                $sPhrase = _p('custom');
                break;
            default:
                $sPhrase = _p('everyone');
                break;
        }

        (($sPlugin = Phpfox_Plugin::get('privacy.service_privacy_getphrase')) ? eval($sPlugin) : '');

        return $sPhrase;
    }

    public function buildPrivacy($aCond = [], $sOrder = null, $iPage = null, $sDisplay = null, $extra_conditions = null, $bUnionLimit = false)
    {
        $bIsCount = (isset($aCond['count']) ? true : false);

        $oObject = Phpfox::getService($aCond['service']);

        $this->service = $oObject;
        $this->isCount = $bIsCount;
        $this->condition = $aCond;

        if ($sPlugin = Phpfox_Plugin::get('privacy.service_privacy_buildprivacy')) {
            eval($sPlugin);
        }

        if (isset($callback) && is_callable($callback)) {
            return call_user_func($callback, $this);
        }

        $conditions = $this->search()->getConditions();

        if (!empty($extra_conditions)) {
            $conditions[] = $extra_conditions;
        }


        if (Phpfox::getUserParam('core.can_view_private_items')) {
            $oObject->getQueryJoins($bIsCount, true);
            if (!$bIsCount && isset($aCond['join']) && !empty($aCond['join'])) {
                $this->database()->leftJoin(
                    $aCond['join']['table'],
                    $aCond['join']['alias'],
                    $aCond['join']['alias'] . "." . $aCond['join']['field'] . ' = ' . $aCond['alias'] . "." . $aCond['field']
                );
            }
            $this->database()->select(($bIsCount ? (isset($aCond['distinct']) ? 'COUNT(DISTINCT ' . $aCond['distinct'] . ')' : 'COUNT(*)') : $aCond['alias'] . '.*'))
                ->from($aCond['table'], $aCond['alias'])
                ->where(str_replace('%PRIVACY%', '0,1,2,3,4,6', $conditions));

            if ($bUnionLimit) {
                $this->database()->order($sOrder)->limit($iPage, $sDisplay)->union();
            } else {
                $this->database()->union();
            }
            return null;
        }

        $aUserCond = [];
        $aFriendCond = [];
        $aFriendOfFriends = [];
        $aCustomCond = [];
        $aPublicCond = [];
        $aCommunityCond = [];
        foreach ($conditions as $sCond) {
            $aFriendCond[] = str_replace('%PRIVACY%', '1,2,6', $sCond);
            $aFriendOfFriends[] = str_replace('%PRIVACY%', '2', $sCond);
            $aUserCond[] = str_replace('%PRIVACY%', '1,2,3,4,6', $sCond);
            $aCustomCond[] = str_replace('%PRIVACY%', '4', $sCond);
            $aPublicCond[] = str_replace('%PRIVACY%', '0', $sCond);
            $aCommunityCond[] = str_replace('%PRIVACY%', '6', $sCond);
        }

        // Users items
        if (Phpfox::isUser()) {
            $oObject->getQueryJoins($bIsCount, true);

            if (!$bIsCount && isset($aCond['join']) && !empty($aCond['join'])) {
                $this->database()->leftJoin(
                    $aCond['join']['table'],
                    $aCond['join']['alias'],
                    $aCond['join']['alias'] . "." . $aCond['join']['field'] . ' = ' . $aCond['alias'] . "." . $aCond['field']
                );
            }

            $this->database()->select(($bIsCount ? (isset($aCond['distinct']) ? 'COUNT(DISTINCT ' . $aCond['distinct'] . ')' : 'COUNT(*)') : $aCond['alias'] . '.*'))
                ->from($aCond['table'], $aCond['alias'])
                ->where(array_merge(['AND ' . $aCond['alias'] . '.user_id = ' . Phpfox::getUserId()], $aUserCond));

            if ($bUnionLimit) {
                $this->database()->order($sOrder)->limit($iPage, $sDisplay)->union();
            } else {
                $this->database()->union();
            }

            // Items based on custom lists
            $oObject->getQueryJoins($bIsCount);

            if (!$bIsCount && isset($aCond['join']) && !empty($aCond['join'])) {
                $this->database()->leftJoin(
                    $aCond['join']['table'],
                    $aCond['join']['alias'],
                    $aCond['join']['alias'] . "." . $aCond['join']['field'] . ' = ' . $aCond['alias'] . "." . $aCond['field']
                );
            }

            $this->database()->select(($bIsCount ? (isset($aCond['distinct']) ? 'COUNT(DISTINCT ' . $aCond['distinct'] . ')' : 'COUNT(*)') : $aCond['alias'] . '.*'))
                ->from($aCond['table'], $aCond['alias'])
                ->join(Phpfox::getT('privacy'), 'p', 'p.module_id = \'' . str_replace('.', '_', $aCond['module_id']) . '\' AND p.item_id = ' . $aCond['alias'] . '.' . $aCond['field'])
                ->join(Phpfox::getT('friend_list_data'), 'fld', 'fld.list_id = p.friend_list_id AND fld.friend_user_id = ' . Phpfox::getUserId() . '')
                ->where($aCustomCond);

            if ($bUnionLimit) {
                $this->database()->order($sOrder)->limit($iPage, $sDisplay)->union();
            } else {
                $this->database()->union();
            }
        }

        // Friend of friends items
        if (!Phpfox::getParam('core.friends_only_community') && Phpfox::isUser()) {
            $oObject->getQueryJoins($bIsCount);

            if (!$bIsCount && isset($aCond['join']) && !empty($aCond['join'])) {
                $this->database()->leftJoin(
                    $aCond['join']['table'],
                    $aCond['join']['alias'],
                    $aCond['join']['alias'] . "." . $aCond['join']['field'] . ' = ' . $aCond['alias'] . "." . $aCond['field']
                );
            }

            $whereInFriendList = strtr('f1.friend_user_id IN (SELECT friend_user_id from :friend WHERE is_page=0 AND user_id=:user_id) AND ', [
                ':friend' => Phpfox::getT('friend'),
                ':user_id' => intval(Phpfox::getUserId()),
            ]);

            $this->database()->select(($bIsCount ? (isset($aCond['distinct']) ? 'COUNT(DISTINCT ' . $aCond['distinct'] . ')' : 'COUNT(*)') : $aCond['alias'] . '.*'))
                ->from($aCond['table'], $aCond['alias'])
                ->join(Phpfox::getT('friend'), 'f1', 'f1.is_page = 0 AND f1.user_id = ' . $aCond['alias'] . '.user_id')
                ->where(array_merge([$whereInFriendList, $aCond['alias'] . '.user_id = f1.user_id AND ' . $aCond['alias'] . '.user_id != ' . Phpfox::getUserId() . ''], $aFriendOfFriends));

            if ($bUnionLimit) {
                $this->database()->order($sOrder)->limit($iPage, $sDisplay)->union();
            } else {
                $this->database()->union();
            }
        }

        // Friends items
        if (Phpfox::isUser()) {
            $oObject->getQueryJoins($bIsCount, true);

            if (!$bIsCount && isset($aCond['join']) && !empty($aCond['join'])) {
                $this->database()->leftJoin(
                    $aCond['join']['table'],
                    $aCond['join']['alias'],
                    $aCond['join']['alias'] . "." . $aCond['join']['field'] . ' = ' . $aCond['alias'] . "." . $aCond['field']
                );
            }

            $this->database()->select(($bIsCount ? (isset($aCond['distinct']) ? 'COUNT(DISTINCT ' . $aCond['distinct'] . ')' : 'COUNT(*)') : $aCond['alias'] . '.*'))
                ->from($aCond['table'], $aCond['alias'])
                ->join(Phpfox::getT('friend'), 'f', 'f.is_page = 0 AND f.user_id = ' . $aCond['alias'] . '.user_id AND f.friend_user_id = ' . Phpfox::getUserId())
                ->where($aFriendCond);

            if ($bUnionLimit) {
                $this->database()->order($sOrder)->limit($iPage, $sDisplay)->union();
            } else {
                $this->database()->union();
            }
        }

        $forcePublic = false;

        (($sPlugin = Phpfox_Plugin::get('privacy.service_privacy_build_privacy')) ? eval($sPlugin) : false);

        if (Phpfox::getParam('core.friends_only_community')
            && !$forcePublic
        ) {
            // Public items
            $oObject->getQueryJoins($bIsCount);

            if (!$bIsCount && isset($aCond['join']) && !empty($aCond['join'])) {
                $this->database()->leftJoin(
                    $aCond['join']['table'],
                    $aCond['join']['alias'],
                    $aCond['join']['alias'] . "." . $aCond['join']['field'] . ' = ' . $aCond['alias'] . "." . $aCond['field']
                );
            }

            $this->database()->select(($bIsCount ? (isset($aCond['distinct']) ? 'COUNT(DISTINCT ' . $aCond['distinct'] . ')' : 'COUNT(*)') : $aCond['alias'] . '.*'))
                ->from($aCond['table'], $aCond['alias'])
                ->where(array_merge(['AND ' . $aCond['alias'] . '.user_id != ' . Phpfox::getUserId()], $aPublicCond));

            if ($bUnionLimit) {
                $this->database()->order($sOrder)->limit($iPage, $sDisplay)->union();
            } else {
                $this->database()->union();
            }

            // Public items for the specific user
            $oObject->getQueryJoins($bIsCount, true);

            if (!$bIsCount && isset($aCond['join']) && !empty($aCond['join'])) {
                $this->database()->leftJoin(
                    $aCond['join']['table'],
                    $aCond['join']['alias'],
                    $aCond['join']['alias'] . "." . $aCond['join']['field'] . ' = ' . $aCond['alias'] . "." . $aCond['field']
                );
            }

            $this->database()->select(($bIsCount ? (isset($aCond['distinct']) ? 'COUNT(DISTINCT ' . $aCond['distinct'] . ')' : 'COUNT(*)') : $aCond['alias'] . '.*'))
                ->from($aCond['table'], $aCond['alias'])
                ->where(array_merge(['AND ' . $aCond['alias'] . '.user_id = ' . Phpfox::getUserId()], $aPublicCond));

            if ($bUnionLimit) {
                $this->database()->order($sOrder)->limit($iPage, $sDisplay)->union();
            } else {
                $this->database()->union();
            }
        } else {
            // Public items
            $oObject->getQueryJoins($bIsCount);

            if (!$bIsCount && isset($aCond['join']) && !empty($aCond['join'])) {
                $this->database()->leftJoin(
                    $aCond['join']['table'],
                    $aCond['join']['alias'],
                    $aCond['join']['alias'] . "." . $aCond['join']['field'] . ' = ' . $aCond['alias'] . "." . $aCond['field']
                );
            }

            $this->database()->select(($bIsCount ? (isset($aCond['distinct']) ? 'COUNT(DISTINCT ' . $aCond['distinct'] . ')' : 'COUNT(*)') : $aCond['alias'] . '.*'))
                ->from($aCond['table'], $aCond['alias'])
                ->where($aPublicCond);

            if ($bUnionLimit) {
                $this->database()->order($sOrder)->limit($iPage, $sDisplay)->union();
            } else {
                $this->database()->union();
            }
        }

        if(Phpfox::isUser() && (!Phpfox::getParam('core.friends_only_community') || $forcePublic)) {
            // Community items
            $oObject->getQueryJoins($bIsCount);

            if (!$bIsCount && isset($aCond['join']) && !empty($aCond['join'])) {
                $this->database()->leftJoin(
                    $aCond['join']['table'],
                    $aCond['join']['alias'],
                    $aCond['join']['alias'] . "." . $aCond['join']['field'] . ' = ' . $aCond['alias'] . "." . $aCond['field']
                );
            }

            $this->database()->select(($bIsCount ? (isset($aCond['distinct']) ? 'COUNT(DISTINCT ' . $aCond['distinct'] . ')' : 'COUNT(*)') : $aCond['alias'] . '.*'))
                ->from($aCond['table'], $aCond['alias'])
                ->where($aCommunityCond);

            if ($bUnionLimit) {
                $this->database()->order($sOrder)->limit($iPage, $sDisplay)->union();
            } else {
                $this->database()->union();
            }
        }

        return null;
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
        if ($sPlugin = Phpfox_Plugin::get('privacy.service_privacy__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}