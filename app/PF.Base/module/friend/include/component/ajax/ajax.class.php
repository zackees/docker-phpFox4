<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 *
 *
 * @copyright       [PHPFOX_COPYRIGHT]
 * @author          phpFox LLC
 * @package         Module_Friend
 */
class Friend_Component_Ajax_Ajax extends Phpfox_Ajax
{
    public function getOnlineFriends()
    {
        Phpfox::getBlock('friend.mini');

        $this->call('$(\'#js_block_border_friend_mini\').find(\'.content:first\').html(\'' . $this->getContent() . '\');')
            ->call('$Core.loadInit();');
    }

    /**
     * Load friend action content for ajax
     * @param $targetUserId
     */
    private function _loadFriendActionContent($targetUserId)
    {
        $requestIdFromCurrentUser = Phpfox::isUser() ? Phpfox::getService('friend.request')->isRequested(Phpfox::getUserId(), $targetUserId, true, true) : false;
        $requestIdFromOtherUser = Phpfox::isUser() ? Phpfox::getService('friend.request')->isRequested($targetUserId, Phpfox::getUserId(), true, true) : false;

        $is_friend = Phpfox::getService('friend')->isFriend(Phpfox::getUserId(), $targetUserId, false, true);
        if (!$is_friend) {
            $is_friend = ($requestIdFromCurrentUser ? 2 : false);
        }

        $user = [
            'user_id' => $targetUserId,
            'is_friend_request' => !empty($requestIdFromCurrentUser) ? 2 : (!empty($requestIdFromOtherUser) ? 3 : null)
        ];

        $params = [
            'aUser' => $user,
            'is_friend' => $is_friend,
            'user_id' => $targetUserId,
            'type' => 'icon',
            'requested' => $requestIdFromOtherUser,
            'request_id' => $requestIdFromCurrentUser
        ];

        Phpfox_Template::instance()->assign($params)->getTemplate('user.block.friend-action');
        $friendActionContent = $this->getContent(false);
        $this->html('.js_friend_actions_' . $params['user_id'], $friendActionContent);
    }

    /**
     * Delete friend request
     * @return bool
     */
    public function deleteRequest()
    {
        Phpfox::isUser(true);
        $requestId = $this->get('request_id');
        $request = Phpfox::getService('friend.request')->getRequest($requestId, true);
        if (empty($request)) {
            return false;
        }

        if (Phpfox::getService('friend.request.process')->delete($requestId, Phpfox::getUserId())) {
            if ($this->get('friend_request_ajax')) {
                $this->_loadFriendActionContent($request['user_id']);
            }
        }
    }

    public function request()
    {
        Phpfox::isUser(true);
        $userId = $this->get('user_id');
        if (Phpfox::getService('friend.request')->isRequested(Phpfox::getUserId(), $userId, false, true)) {
            $this->setTitle(_p('confirm_friend_request'));
        } else {
            $this->setTitle(_p('add_to_friends'));
        }
        Phpfox::getBlock('friend.request', [
            'user_id' => $userId
        ]);
        $this->call('<script>$Behavior.globalInit();</script>');
    }

    public function processRequest()
    {
        Phpfox::isUser(true);
        $userId = $this->get('user_id');
        if (Phpfox::getService('friend')->isFriend(Phpfox::getUserId(), $userId)) {
            if (Phpfox::getParam('friend.friendship_direction', 'two_way_friendships') != 'one_way_friendships') {
                Phpfox::getService('friend.request.process')->delete($this->get('request_id'), $userId);
                $this->call(' $("#js_new_friend_request_' . $this->get('request_id') . '").remove();');
            } else {
                $this->reload();
            }
            return false;
        }

        $bProcessFromPanel = $this->get('inline');
        $bProcessFromManageAllRequests = $this->get('manage_all_request');
        $aVal = $this->get('val');
        $isAjax = false;

        if ($this->get('type') == 'yes') {
            if (Phpfox::getService('friend.process')->add(Phpfox::getUserId(), $userId, (isset($aVal['list_id']) ? (int)$aVal['list_id'] : 0))
            ) {
                if ($bProcessFromPanel) {
                    $aFriendFullName = Phpfox::getService('user')->getUser($userId, 'u.full_name');
                    $this->call(
                        vsprintf("\$Core.FriendRequest.panel.accept(%s, \"%s\");", [
                            $this->get('request_id'),
                            html_entity_decode(_p('you_and_full_name_are_now_friends', ['full_name' => $aFriendFullName['full_name']]), ENT_QUOTES, 'UTF-8')
                        ])
                    );
                } elseif ($bProcessFromManageAllRequests) {
                    $aFriendFullName = Phpfox::getService('user')->getUser($userId, 'u.full_name');
                    $this->call(
                        vsprintf("\$Core.FriendRequest.manageAll.accept(%s, \"%s\");", [
                            $this->get('request_id'),
                            html_entity_decode(_p('you_and_full_name_are_now_friends', ['full_name' => $aFriendFullName['full_name']]), ENT_QUOTES, 'UTF-8')
                        ])
                    );
                } else {
                    $sMess = _p('The request has been accepted successfully!');
                }
            }
        } elseif ($this->get('type') == 'add') {
            if (Phpfox::getService('user.block')->isBlocked($userId, Phpfox::getUserId()) || !Phpfox::getService('user.privacy')->hasAccess($userId, 'friend.send_request')) {
                return Phpfox_Error::set(_p('unable_to_send_a_friend_request_to_this_user_at_this_moment'));
            }
            if (Phpfox::getService('friend.process')->add(Phpfox::getUserId(), $userId)
            ) {
                $aFriendUserName = Phpfox::getService('user')->getUser($userId, 'u.user_name');
                $this->call('$(\'#js_user_tool_tip_cache_' . $aFriendUserName['user_name'] . '\').closest(\'.js_user_tool_tip_holder:first\').remove();');
                $sMess = _p('add_friend_successfully');
                $isAjax = true;
            }
        } else {
            if (Phpfox::getService('friend.process')->deny(Phpfox::getUserId(), $userId)) {
                if ($bProcessFromPanel) {
                    $this->call(sprintf("\$Core.FriendRequest.panel.deny(%s);", $this->get('request_id')));
                } elseif ($bProcessFromManageAllRequests) {
                    $this->call(sprintf("\$Core.FriendRequest.manageAll.deny(%s);", $this->get('request_id')));
                } else {
                    $sMess = _p('The request has been denied successfully!');
                }
            }
        }

        Phpfox_Cache::instance()->remove('recent_active_users_' . Phpfox::getUserId());
        Phpfox_Cache::instance()->remove('recent_active_users_' . $userId);

        // update number of request in panel
        if ($bProcessFromPanel) {
            list(, $aFriends) = Phpfox::getService('friend.request')->get(0, 100);
            foreach ($aFriends as $key => $friend) {
                if ($friend['relation_data_id']) {
                    $sRelationShipName = Phpfox::getService('custom.relation')->getRelationName($friend['relation_id']);
                    if (isset($sRelationShipName) && !empty($sRelationShipName)) {
                        $aFriends[$key]['relation_name'] = $sRelationShipName;
                    } else {
                        //This relationship was removed
                        unset($aFriends[$key]);
                    }
                }
            }
            $iNumberFriendRequest = 0;
            foreach ($aFriends as $aFriend) {
                if (isset($aFriend['is_read']) && $aFriend['is_read'] == 1) {
                    continue;
                }
                $iNumberFriendRequest++;
            }
            if ($iNumberFriendRequest) {
                $this->call('$("span#js_total_new_friend_requests").html("' . $iNumberFriendRequest . '");');
            } else {
                $this->call('$("span#js_total_new_friend_requests").hide();');
            }
        } elseif ($bProcessFromManageAllRequests) {
        } else {
            if (isset($aVal['friend_request_ajax']) || $isAjax) {
                isset($sMess) && $this->call('$Core.processFriendRequest.confirmRequest(' . json_encode(['target_user_id' => $userId, 'message' => $sMess]) . ');');
                $this->_loadFriendActionContent($userId);
            } else {
                // process in browse users page
                isset($sMess) && Phpfox::addMessage($sMess, $this->get('type') == 'yes' ? 'success' : 'warning');
                $this->reload();
            }
        }

        return null;
    }

    public function addRequest()
    {
        Phpfox::isUser(true);
        Phpfox::getUserParam('friend.can_add_friends', true);

        $aVals = $this->get('val');
        $aUser = Phpfox::getService('user')->getUser($aVals['user_id'], 'u.user_id, u.user_name, u.user_image, u.server_id');

        if (Phpfox::getUserId() === $aUser['user_id']) {
            $this->call('tb_remove();');
            return false;
        } elseif (Phpfox::getService('friend.request')->isRequested(Phpfox::getUserId(), $aUser['user_id'], false, true) || Phpfox::getService('friend.request')->isDenied(Phpfox::getUserId(), $aUser['user_id'])) {
            Phpfox_Error::set(_p('you_were_already_requested_to_be_friends'));
        } elseif (Phpfox::getService('friend.request')->isRequested($aUser['user_id'], Phpfox::getUserId(), false, true)) {
            Phpfox_Error::set(_p('you_already_requested_to_be_friends'));
        } elseif (Phpfox::getService('friend')->isFriend(Phpfox::getUserId(), $aUser['user_id'])) {
            Phpfox_Error::set(_p('you_are_already_friends_with_this_user'));
        } else {
            if (Phpfox::getService('user.block')->isBlocked($aUser['user_id'], Phpfox::getUserId()) || !Phpfox::getService('user.privacy')->hasAccess($aUser['user_id'], 'friend.send_request')) {
                $this->call('tb_remove();');
                return Phpfox_Error::set(_p('unable_to_send_a_friend_request_to_this_user_at_this_moment'));
            }
        }
        if (Phpfox_Error::isPassed() != true) {
            $this->call('tb_remove();');
            return false;
        }
        if ($requestId = Phpfox::getService('friend.request.process')->add(Phpfox::getUserId(), $aVals['user_id'], 0, null, true)) {
            if (isset($aVals['invite'])) {
                $this->call('tb_remove();')->html('#js_invite_user_' . $aVals['user_id'], '' . html_entity_decode(_p('friend_request_successfully_sent')) . '');
            } else {
                $this->call('$Core.submitFriendRequest();');
            }

            $this->call('$(\'#js_user_tool_tip_cache_' . $aUser['user_name'] . '\').closest(\'.js_user_tool_tip_holder:first\').remove();');

            if (isset($aVals['suggestion'])) {
                $this->loadSuggestion(false);
            }

            if (isset($aVals['page_suggestion'])) {
                $this->hide('#js_suggestion_parent_' . $aVals['user_id']);
            }

            if (isset($aVals['friend_request_ajax'])) {
                $this->_loadFriendActionContent($aVals['user_id']);
            } else {
                $this->call('$(".friend_request_reload").removeClass("built");');
                $this->call('$Core.loadInit();');
            }
            Phpfox_Cache::instance()->remove('recent_active_users_' . Phpfox::getUserId());
            Phpfox_Cache::instance()->remove('recent_active_users_' . $aVals['user_id']);
        }
        $this->remove('.add_as_friend_button');

        return null;
    }

    public function addList()
    {
        Phpfox::isUser(true);
        Phpfox::getUserParam('friend.can_add_folders', true);

        $sName = $this->get('name');

        if (Phpfox::getLib('parse.format')->isEmpty($sName)) {
            $this->html('#js_friend_list_add_error', _p('provide_a_name_for_your_list'), '.show()');
            $this->call('$Core.processForm(\'#js_friend_list_add_submit\', true);');
        } elseif (Phpfox::getService('friend.list')->reachedLimit()) // Did they reach their limit?
        {
            $this->html('#js_friend_list_add_error', _p('you_have_reached_your_limit'), '.show()');
            $this->call('$Core.processForm(\'#js_friend_list_add_submit\', true);');
        } elseif (Phpfox::getService('friend.list')->isFolder($sName)) {
            $this->html('#js_friend_list_add_error', _p('folder_already_use'), '.show()');
            $this->call('$Core.processForm(\'#js_friend_list_add_submit\', true);');
        } else {
            if ($iId = Phpfox::getService('friend.list.process')->add($sName)) {
                if ($this->get('custom')) {
                    $this->hide('#js_create_custom_friend_list')->show('#js_add_friends_to_list')->val('#js_custom_friend_list_id',
                        $iId);
                } else {
                    $this->call('js_box_remove($(\'#js_friend_list_add_error\', true));');
                    $this->alert(_p('list_successfully_created'), _p('create_new_list'), 400, 150, true);
                    $this->call('$Core.reloadPage();');
                }
                $this->call('$Core.loadInit();');
            }
        }
    }

    /**
     * Currently, this function is used when we choose privacy as Custom then add new list.
     */
    public function addFriendsList()
    {
        Phpfox::isUser(true);
        Phpfox::getUserParam('friend.can_add_folders', true);
        $sName = $this->get('name');
        $aFriends = $this->get('friends');

        if (Phpfox::getLib('parse.format')->isEmpty($sName)) {
            $sError = _p('provide_a_name_for_your_list');
        } elseif (Phpfox::getService('friend.list')->reachedLimit()) {
            // Did they reach their limit?
            $sError = _p('you_have_reached_your_limit');
        } elseif (Phpfox::getService('friend.list')->isFolder($sName)) {
            $sError = _p('folder_already_use');
        } elseif (empty($aFriends)) {
            $sError = _p('please_add_friends_to_your_list');
        }

        if (isset($sError)) {
            $this->html('#js_friend_list_add_error', $sError, '.show()');

            return;
        }

        if ($iId = Phpfox::getService('friend.list.process')->add($sName)) {
            Phpfox::getService('friend.list.process')->addFriendsToList($iId, $aFriends);
            $this->call(strtr('$Core.Privacy.addListDone("{name}", "{value}");', [
                '{name}' => $sName,
                '{value}' => $iId
            ]));
        }
    }

    public function editListName()
    {
        Phpfox::isUser(true);

        $sName = $this->get('name');
        $iListId = $this->get('id');

        if (Phpfox::getLib('parse.format')->isEmpty($sName)) {
            $this->html('#js_friend_list_edit_name_error', _p('provide_a_name_for_your_list'), '.show()');
            $this->call('$Core.processForm(\'#js_friend_list_edit_name_submit\', true);');
        } elseif (Phpfox::getService('friend.list')->isFolder($sName, $iListId)) {
            $this->html('#js_friend_list_edit_name_error', _p('folder_already_use'), '.show()');
            $this->call('$Core.processForm(\'#js_friend_list_edit_name_submit\', true);');
        } else {
            if (Phpfox::getService('friend.list.process')->update($iListId, $sName)) {
                $this->call('js_box_remove($(\'#js_friend_list_edit_name_error\', true));');
                $this->alert(_p('list_successfully_edited'), _p('edit_list_name'), 400, 150, true);
                $this->call('$Core.reloadPage();');
            }
        }
    }


    public function executeAddFriendToList()
    {
        Phpfox::isUser(true);
        $sUserIds = $this->get('user_id_list');
        $iListId = $this->get('list_id');
        $aUserIds = explode(',', $sUserIds);
        Phpfox::getService('friend.list.process')->updateFriendListData($iListId, $aUserIds);
        $this->call('$Core.reloadPage();');
    }

    public function addFriendToList()
    {
        Phpfox::isUser(true);
        Phpfox::getBlock('friend.list.add-friend', [
            'list_id' => $this->get('list_id')
        ]);
    }

    public function addNewList()
    {
        $this->setTitle(_p('create_new_list'));

        Phpfox::getBlock('friend.list.add');
    }

    public function editName()
    {
        $this->setTitle(_p('edit_list_name'));

        Phpfox::getBlock('friend.list.edit-name');
    }

    public function buildCache()
    {
        $friends = Phpfox::getService('friend')->getFromCache($this->get('allow_custom'), false, (bool)$this->get('include_current_user'));
        if (!empty($friends)) {
            $this->call('$Cache.disallowedTaggingFriends = ' . json_encode(Phpfox::getService('friend')->filterDisallowedTaggingFriends(array_column($friends, 'user_id'))) . ';');
        }
        $this->call('$Cache.friends = ' . json_encode(Phpfox::getService('friend')->getFromCache($this->get('allow_custom'), false, (bool)$this->get('include_current_user'))) . ';');
    }

    public function getLiveSearch()
    {
        // This function is called from friend.static.search.js::getFriends in response to a key up event when is_mail is passed as true in building the template
        // parent_id we have to find the class "js_temp_friend_search_form" from its parents
        // search_for
        $aUsers = Phpfox::getService('friend')->getFromCache(false, $this->get('search_for'));

        if (empty($aUsers)) {
            return false;
        }
        // The next block is copied and modified from friend.static.search.js::getFriends
        $sHtml = '';
        $iFound = 0;
        $sStoreUser = '';
        foreach ($aUsers as $aUser) {
            $iFound++;
            if (substr($aUser['user_image'], 0, 5) == 'http:') {
                $aUser['user_image'] = '<img src="' . $aUser['user_image'] . '">';
            }
            $sHtml .= '<li><div rel="' . $aUser['user_id'] . '" class="js_friend_search_link ' . (($iFound == 1) ? 'js_temp_friend_search_form_holder_focus' : '') . '" href="#" onclick="return $Core.searchFriendsInput.processClick(this, \'' . $aUser['user_id'] . '\');"><span class="image">' . $aUser['user_image'] . '</span><span class="user">' . $aUser['full_name'] . '</span></div></li>';
            $sStoreUser .= '$Core.searchFriendsInput.storeUser(' . $aUser['user_id'] . ', JSON.parse(' . json_encode(json_encode($aUser)) . '));';

            if ($iFound > $this->get('total_search')) {
                break;
            }
        }
        $sHtml = '<div class="js_temp_friend_search_form_holder"><ul>' . $sHtml . '</ul></div>';
        $this->call($sStoreUser);
        $this->call('$("#' . $this->get('parent_id') . '").parent().find(".js_temp_friend_search_form").html(\'' . str_replace("'",
                "\\'", $sHtml) . '\').show();');
    }

    public function delete()
    {
        $friendUseId = $this->get('friend_user_id');
        $bDeleted = $this->get('id') ? Phpfox::getService('friend.process')->delete($this->get('id')) : Phpfox::getService('friend.process')->delete($friendUseId, false);

        if ($bDeleted) {
            //remove recently active users cache of each user
            Phpfox_Cache::instance()->remove('recent_active_users_' . Phpfox::getUserId());
            if (Phpfox::getParam('friend.friendship_direction', 'two_way_friendships') != 'one_way_friendships') {
                Phpfox_Cache::instance()->remove('recent_active_users_' . $friendUseId);
            }

            if ($this->get('reload')) {
                $this->call('$Core.reloadPage();');

                return;
            }
            if ($this->get('friend_request_ajax')) {
                $this->_loadFriendActionContent($friendUseId);
                return;
            }
            $this->call('$("#js_friend_' . $this->get('id') . '").remove();');
            $this->alert(_p('friend_successfully_removed'), _p('remove_friend'), 300, 150, true);
        }
    }

    public function search()
    {
        Phpfox::getBlock('friend.search', [
            'input' => $this->get('input'),
            'friend_module_id' => $this->get('friend_module_id'),
            'friend_item_id' => $this->get('friend_item_id'),
            'type' => $this->get('type')
        ]);
        if ($this->get('type') == 'mail') {
            $this->call('<script type="text/javascript">$(\'#TB_ajaxWindowTitle\').html(\'' . _p('search_for_members',
                    ['phpfox_squote' => true]) . '\');</script>');
        } else {
            $this->call('<script type="text/javascript">$(\'#TB_ajaxWindowTitle\').html(\'' . _p('search_for_your_friends',
                    ['phpfox_squote' => true]) . '\');</script>');
        }
    }

    public function searchAjax()
    {
        Phpfox::getBlock('friend.search', [
            'search' => true,
            'friend_module_id' => $this->get('friend_module_id'),
            'friend_item_id' => $this->get('friend_item_id'),
            'page' => $this->get('page'),
            'find' => $this->get('find'),
            'letter' => $this->get('letter'),
            'input' => $this->get('input'),
            'view' => $this->get('view'),
            'type' => $this->get('type')
        ]);

        $this->call('$(\'#js_friend_search_content\').html(\'' . $this->getContent() . '\');$Core.searchFriend.updateFriendsList();$Behavior.globalInit();');
    }

    public function searchDropDown()
    {
        Phpfox::isUser(true);
        $oDb = Phpfox_Database::instance();
        $sFind = $this->get('search');
        if (empty($sFind)) {
            $iCnt = 0;
        } else {
            list($iCnt, $aFriends) = Phpfox::getService('friend')->get('friend.is_page = 0 AND friend.user_id = ' . Phpfox::getUserId() . ' AND (u.full_name LIKE \'%' . Phpfox::getLib('parse.input')->convert($oDb->escape($sFind)) . '%\' OR (u.email LIKE \'%' . $oDb->escape($sFind) . '@%\' OR u.email = \'' . $oDb->escape($sFind) . '\'))',
                'friend.time_stamp DESC', 0, 10, true, true);
        }

        if ($iCnt && isset($aFriends)) {
            $sHtml = '';
            foreach ($aFriends as $aFriend) {
                $sImage = Phpfox::getLib('image.helper')->display([
                    'user' => $aFriend,
                    'suffix' => '_120_square',
                    'no_link' => true
                ]);
                $sFullName = str_replace("&#039;", "'", $aFriend['full_name']);
                $sHtml .= '<li><a href="#" onclick="$(\'#' . $this->get('div_id') . '\').parent().hide(); $(\'#' . $this->get('input_id') . '\').val(\'' . $aFriend['user_id'] . '\'); $(\'#' . $this->get('text_id') . '\').val(\'' . addslashes($sFullName) . '\'); return false;">' . $sImage . Phpfox::getLib('parse.output')->shorten(Phpfox::getLib('parse.output')->clean($aFriend['full_name']),
                        40, '...') . '</a></li>';
            }
            $this->html('#' . $this->get('div_id'), '<ul>' . $sHtml . '</ul>');
            $this->call('$(\'#' . $this->get('div_id') . '\').parent().show();');
        } else {
            $this->html('#' . $this->get('div_id'), '');
            $this->call('$(\'#' . $this->get('div_id') . '\').parent().hide();');
        }
    }

    public function loadSuggestion($bLoadTemplate = true)
    {
        Phpfox::getBlock('friend.suggestion', 'reload=true');

        if ($bLoadTemplate === true) {
            Phpfox_Template::instance()->getTemplate('friend.block.suggestion');
        }

        $this->slideUp('#js_friend_suggestion_loader')->html('#js_friend_suggestion',
            $this->getContent(false))->slideDown('#js_friend_suggestion');
        $this->call('$Core.loadInit();');
    }

    public function removeSuggestion()
    {
        Phpfox::isUser(true);
        if (Phpfox::getService('friend.suggestion')->remove($this->get('user_id'))) {
            if ($this->get('load')) {
                $this->loadSuggestion(false);
            }
        }
    }

    public function manageList()
    {
        Phpfox::isUser(true);

        if ($this->get('type') == 'add') {
            Phpfox::getService('friend.list.process')->addFriendsTolist($this->get('list_id'), $this->get('friend_id'));
        } else {
            Phpfox::getService('friend.list.process')->removeFriendsFromlist($this->get('list_id'),
                $this->get('friend_id'));
        }
    }

    public function setProfileList()
    {
        Phpfox::isUser(true);

        if ($this->get('type') == 'add') {
            if (Phpfox::getService('friend.list.process')->addListToProfile($this->get('list_id'))) {
                $this->call('$(\'.friend_list_display_profile\').parent().hide();');
                $this->call('$(\'.friend_list_remove_profile\').parent().show();');
                $this->alert(_p('successfully_added_this_list_to_your_profile'), _p('profile_friend_lists'), 300, 150,
                    true);
            }
        } else {
            if (Phpfox::getService('friend.list.process')->removeListFromProfile($this->get('list_id'))) {
                $this->call('$(\'.friend_list_display_profile\').parent().show();');
                $this->call('$(\'.friend_list_remove_profile\').parent().hide();');
            }
        }
    }

    public function updateListOrder()
    {
        Phpfox::isUser(true);

        if (Phpfox::getService('friend.list.process')->updateListOrder($this->get('list_id'),
            $this->get('friend_id'))) {
            $this->alert(_p('order_successfully_saved'), _p('list_order'), 400, 150, true);
            $this->call('$Core.processForm(\'#js_friend_list_order_form\', true);');
        }
    }

    public function viewMoreFriends()
    {
        Phpfox::getComponent('friend.index', [], 'controller');
        $this->remove('.js_pager_view_more_link');
        $this->append('#js_view_more_friends', $this->getContent(false));
        $this->call('$Core.loadInit();');
    }

    public function getMutualFriends()
    {
        Phpfox::isUser(true);
        if ((int)$this->get('page') == 0) {
            list($iCnt,) = Phpfox::getService('friend')->get(['friend.user_id' => Phpfox::getUserId()],
                'friend.time_stamp DESC', '',
                '', true, false, false, $this->get('user_id'));
            $this->setTitle($iCnt == 1 ? _p('1_mutual_friend') : _p('total_mutual_friends', ['total' => $iCnt]));
        }
        Phpfox::getBlock('friend.mutual-browse');

        if ((int)$this->get('page') > 0) {
            $this->remove('#js_friend_mutual_browse_append_pager');
            $this->append('#js_friend_mutual_browse_append', $this->getContent(false));
        }
        $this->call('<script>$Core.loadInit();$Behavior.globalInit();</script>');
    }

    public function moderation()
    {
        Phpfox::isUser(true);
        switch ($this->get('action')) {
            case 'accept':
                foreach ((array)$this->get('item_moderate') as $iId) {
                    if (($aRequest = Phpfox::getService('friend.request')->getRequest($iId)) === false) {
                        continue;
                    }
                    if (!empty($aRequest['relation_data_id'])) { // relationship request
                        if (Phpfox::isModule('custom') && $aRequest['user_id'] == Phpfox::getUserId()) {
                            Phpfox::getService('custom.relation.process')->updateRelationship(0, $aRequest['friend_user_id'], $aRequest['user_id'], $aRequest['relation_data_id']);
                        }
                    } else {
                        Phpfox::getService('friend.process')->add(Phpfox::getUserId(), $aRequest['friend_user_id']);
                    }
                    $this->remove('.js_friend_request_' . $iId);
                }
                $this->updateCount();
                break;
            case 'deny':
                foreach ((array)$this->get('item_moderate') as $iId) {
                    if (($aRequest = Phpfox::getService('friend.request')->getRequest($iId)) === false) {
                        continue;
                    }
                    if (!empty($aRequest['relation_data_id'])) { // relationship request
                        if (Phpfox::isModule('custom') && $aRequest['user_id'] == Phpfox::getUserId()) {
                            Phpfox::getService('custom.relation.process')->denyStatus($this->get('relation_data_id'), $aRequest['user_id']);
                            if (Phpfox::isModule('friend')) {
                                Phpfox::getService('friend.request.process')->delete($aRequest['request_id'], $aRequest['friend_user_id']);
                            }
                        }
                    } else {
                        Phpfox::getService('friend.process')->deny(Phpfox::getUserId(), $aRequest['friend_user_id']);
                    }
                    $this->remove('.js_friend_request_' . $iId);
                }
                break;
        }
        $this->call('$Core.reloadPage();');
    }

    public function removePendingRequest()
    {
        $iId = $this->get('id');
        if (Phpfox::getService('friend.request.process')->delete($iId, Phpfox::getUserId())) {
            $this->call('$Core.reloadPage();');
        }
    }

    public function denyRequest()
    {
        if (Phpfox::getService('friend.process')->deny(Phpfox::getUserId(), $this->get('user_id'))) {
            $this->call('$Core.reloadPage();');
        }
    }

    public function browseOnline()
    {
        $this->setTitle(_p('friends_online'));
        Phpfox::getBlock('friend.browse-online');
    }
}