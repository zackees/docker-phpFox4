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
 * @package        Phpfox_Ajax
 * @version        $Id: ajax.class.php 7092 2014-02-05 21:42:42Z Fern $
 */
class Feed_Component_Ajax_Ajax extends Phpfox_Ajax
{
    public function checkNew()
    {
        $iLastFeedUpdate = $this->get('iLastFeedUpdate');
        //Make sure feed loaded
        if ($iLastFeedUpdate > 0) {
            define('PHPFOX_CHECK_FOR_UPDATE_FEED', true);
            define('PHPFOX_CHECK_FOR_UPDATE_FEED_UPDATE', $iLastFeedUpdate);
            Phpfox::getBlock('feed.checknew');
            $this->html('#js_new_feed_update', $this->getContent(false));
        }
    }

    public function loadNew()
    {
        $iLastFeedUpdate = $this->get('iLastFeedUpdate');

        define('FEED_LOAD_MORE_NEWS', false);
        define('FEED_LOAD_NEW_NEWS', true);

        define('PHPFOX_CHECK_FOR_UPDATE_FEED', true);
        define('PHPFOX_CHECK_FOR_UPDATE_FEED_UPDATE', $iLastFeedUpdate);

        if ($this->get('callback_module_id') == 'pages' && Phpfox::getService('pages')->isTimelinePage($this->get('callback_item_id'))) {
            define('PAGE_TIME_LINE', true);
        }

        Phpfox::getBlock('feed.display');
        if (!$this->get('forceview') && !$this->get('resettimeline')) {
            $this->html('#js_new_feed_comment', '');
            $this->insertAfter('#js_new_feed_comment', $this->getContent(false));
        } else {
            $this->html('#js_new_feed_comment', '');
            $this->insertAfter('#js_new_feed_comment', $this->getContent(false));
        }
        $this->call('$Core.loadInit();');
    }

    public function loadDropDates()
    {
        Phpfox::getBlock('feed.loaddates');

        $sContent = $this->getContent(false);
        $sContent = str_replace(["\n", "\t"], '', $sContent);

        $this->html('.timeline_date_holder_share', $sContent);
    }

    public function share()
    {
        $aVals = $this->get('val');
        $aVals['user_status'] = $aVals['post_content'];

        if ($aVals['post_type'] == '2') {
            if (!isset($aVals['friends']) || (isset($aVals['friends']) && !count($aVals['friends']))) {
                Phpfox_Error::set(_p('select_a_friend_to_share_this_with_dot'));
            } else {
                $iCnt = 0;
                foreach ($aVals['friends'] as $iFriendId) {
                    $aVals['parent_user_id'] = $iFriendId;
                    $aVals['is_share'] = true;
                    if (Phpfox::getService('user.privacy')->hasAccess($iFriendId, 'feed.share_on_wall') && Phpfox::getUserParam('profile.can_post_comment_on_profile')) {
                        $iCnt++;
                        Phpfox::getService('feed.process')->addComment($aVals);
                    }
                }

                $sMessage = '<div class="message">' . str_replace("'", "\\'", _p('successfully_shared_this_item_on_your_friends_wall')) . '</div>';
                if (!$iCnt) {
                    $sMessage = '<div class="error_message">' . str_replace("'", "\\'", _p('unable_to_share_this_post_due_to_privacy_settings')) . '</div>';


                }
                $this->call('$(\'#\' + tb_get_active()).find(\'.js_box_content:first\').html(\'' . $sMessage . '\');');
                if ($iCnt) {
                    $this->call('setTimeout(\'tb_remove();\', 2000);');
                    if (!empty($aVals['parent_module_id']) && !empty($aVals['parent_feed_id'])) {
                        $this->call('$Core.updateShareFeedCount(\'' . $aVals['parent_module_id'] . '\', ' . $aVals['parent_feed_id'] . ', \'+\', ' . $iCnt . ');');
                    }
                }
            }
            $this->call('$("#btnShareFeed").removeAttr("disabled");');
            return null;
        }

        $aVals['no_check_empty_user_status'] = true;

        if (($iId = Phpfox::getService('user.process')->updateStatus($aVals))) {
            $this->call('$(\'#\' + tb_get_active()).find(\'.js_box_content:first\').html(\'<div class="message">' . str_replace("'", "\\'", _p('successfully_shared_this_item')) . '</div>\'); setTimeout(\'tb_remove();\', 2000);');
            if (!empty($aVals['parent_module_id']) && !empty($aVals['parent_feed_id'])) {
                $this->call('$Core.updateShareFeedCount(\'' . $aVals['parent_module_id'] . '\', ' . $aVals['parent_feed_id'] . ', \'+\', 1);');
            }
        } else {
            $this->call("$('#btnShareFeed').attr('disabled', false); $('#imgShareFeedLoading').hide();");
        }
    }

    public function addComment()
    {
        Phpfox::isUser(true);
        $aVals = (array)$this->get('val');

        if (Phpfox::isAppActive('P_StatusBg') && isset($aVals['status_background_id'])) {
            $iBackgroundId = $aVals['status_background_id'];
        } else {
            $iBackgroundId = 0;
        }

        $feed = null;
        if (!empty($aVals['feed_id'])) {
            $feed = Phpfox::getService('feed')->getFeed($aVals['feed_id']);
        }

        // check status empty
        if (Phpfox::getLib('parse.format')->isEmpty($aVals['user_status']) && !($feed && in_array($feed['type_id'], ['v', 'photo']))) {
            $this->alert(_p('add_some_text_to_share'));
            $this->call('$Core.activityFeedProcess(false);');
            return false;
        }

        // check permission
        if (isset($aVals['parent_user_id']) && $aVals['parent_user_id'] > 0 && !($aVals['parent_user_id'] == Phpfox::getUserId() || (Phpfox::getUserParam('profile.can_post_comment_on_profile') && Phpfox::getService('user.privacy')->hasAccess('' . $aVals['parent_user_id'] . '', 'feed.share_on_wall')))) {
            $this->alert(_p('You don\'t have permission to post comment on this profile.'));
            $this->call('$Core.activityFeedProcess(false);');
            return false;
        }

        /* Check if user chose an egift */
        if (Phpfox::isAppActive('Core_eGifts') && isset($aVals['egift_id']) && !empty($aVals['egift_id'])) {
            /* is this gift a free one? */
            $aGift = Phpfox::getService('egift')->getEgift($aVals['egift_id']);
            if (!empty($aGift)) {
                $bIsFree = true;
                foreach ($aGift['price'] as $sCurrency => $fVal) {
                    if ($fVal > 0) {
                        $bIsFree = false;
                    }
                }
                /* This is an important change, in v2 birthday_id was the mail_id, in v3
                 * birthday_id is the feed_id
                */
                $aVals['feed_type'] = 'feed_egift';
                $iId = Phpfox::getService('feed.process')->addComment($aVals);
                // check and add background id
                if ($iId && $iBackgroundId) {
                    $iStatusId = db()->select('item_id')->from(':feed')->where('feed_id = ' . (int)$iId)->execute('getField');
                    Phpfox::getService('pstatusbg.process')->addBackgroundForStatus('feed_comment', $iStatusId, $iBackgroundId, Phpfox::getUserId(), 'feed');
                }
                // Always make an invoice, so the feed can check on the state
                $aGift['message'] = Phpfox::getLib('parse.input')->prepare($aVals['user_status']);
                $iInvoice = Phpfox::getService('egift.process')->addInvoice($iId, $aVals['parent_user_id'], $aGift);

                if (!$bIsFree) {
                    Phpfox::getBlock('api.gateway.form', [
                        'gateway_data' => [
                            'item_number' => 'egift|' . $iInvoice,
                            'currency_code' => Phpfox::getService('user')->getCurrency(),
                            'amount' => $aGift['price'][Phpfox::getService('user')->getCurrency()],
                            'item_name' => _p('egift_card_with_message') . ': ' . $aVals['user_status'] . '',
                            'return' => Phpfox_Url::instance()->makeUrl('friend.invoice'),
                            'recurring' => 0,
                            'recurring_cost' => '',
                            'alternative_cost' => 0,
                            'alternative_recurring_cost' => 0
                        ]
                    ]);
                    $this->call('$("#js_activity_feed_form").hide().after("' . $this->getContent(true) . '");');
                } else {
                    //send notification
                    $aInvoice = Phpfox::getService('egift')->getEgiftInvoice((int)$iInvoice);
                    Phpfox::getService('egift.process')->sendNotification($aInvoice);

                    // egift is free
                    Phpfox::getService('feed')->processAjax($iId);

                }
            }
        } else {
            $bHasTaggedFriends = false;
            $bCanLoadNewFeedContent = true;
            if (!empty($iProfileUserId = Phpfox::getService('profile')->getProfileUserId())
                && !empty($aVals['feed_id'])
                && !empty($aFeed = Phpfox::getService('feed')->getFeed($aVals['feed_id']))) {
                $bHasTaggedFriends = in_array($iProfileUserId, Phpfox::getService('feed.tag')->getTaggedUserIds($aFeed['item_id'], $aFeed['type_id']));
            }
            if (isset($aVals['user_status']) && ($iId = Phpfox::getService('feed.process')->addComment($aVals))) {
                // check and add background id
                if ($iBackgroundId) {
                    $iStatusId = db()->select('item_id')->from(':feed')->where('feed_id = ' . (int)$iId)->execute('getField');
                    Phpfox::getService('pstatusbg.process')->addBackgroundForStatus('feed_comment', $iStatusId, $iBackgroundId, Phpfox::getUserId(), 'feed');
                }
                if (isset($aVals['feed_id'])) {
                    if ($bHasTaggedFriends) {
                        $aCurrentTaggedFriends = !empty($aVals['tagged_friends']) ? array_map(function($value) {
                            return trim($value);
                        }, explode(',', $aVals['tagged_friends'])) : [];
                        $bCanLoadNewFeedContent = in_array($iProfileUserId, $aCurrentTaggedFriends);
                    }
                    if ($bCanLoadNewFeedContent) {
                        //Mean edit already status
                        Phpfox::getService('feed')->processUpdateAjax($aVals['feed_id']);
                    } elseif (!empty($aVals['feed_id'])) {
                        $this->slideUp('#js_item_feed_' . $aVals['feed_id']);
                        $this->call("tb_remove();");
                        $this->call('setTimeout(function(){$Core.resetActivityFeedForm();$Core.loadInit();}, 500);');
                    }
                } else {
                    Phpfox::getService('feed')->processAjax($iId);
                }
            } else {
                $this->call('$Core.activityFeedProcess(false);');
            }
        }
    }

    public function viewMore()
    {
        define('FEED_LOAD_MORE_NEWS', true);

        $sCallbackModuleId = $this->get('callback_module_id', false);
        if ($sCallbackModuleId && in_array($sCallbackModuleId, ['pages', 'groups'])) {
            define('PHPFOX_IS_PAGES_VIEW', true);
            define('PHPFOX_PAGES_ITEM_TYPE', $sCallbackModuleId);
        }

        if ($sCallbackModuleId == 'pages' && Phpfox::getService('pages')->isTimelinePage($this->get('callback_item_id'))) {
            define('PAGE_TIME_LINE', true);
        }

        Phpfox::getBlock('feed.display');

        $this->remove('#feed_view_more');
        if (!$this->get('forceview') && !$this->get('resettimeline')) {
            $this->call('var feed_current_position = $(window).scrollTop();');
            $this->append('#js_feed_content', $this->getContent(false));
            $this->call('$(window).scrollTop(feed_current_position);');
        } else {
            $this->call('$.scrollTo(\'.timeline_left\', 800);');
            $this->html('#js_feed_content', $this->getContent(false));
        }
        $this->call('$iReloadIteration = 0;$Core.loadInit();');
    }

    public function rate()
    {
        Phpfox::isUser(true);

        list($sRating, $iLastVote) = Phpfox::getService('feed.process')->rate($this->get('id'), $this->get('type'));
        Phpfox::getBlock('feed.rating', [
                'sRating' => (int)$sRating,
                'iFeedId' => $this->get('id'),
                'bHasRating' => true,
                'iLastVote' => $iLastVote
            ]
        );
        $this->html('#js_feed_rating' . $this->get('id'), $this->getContent(false));
    }

    public function delete()
    {
        if (Phpfox::getService('feed.process')->deleteFeed($this->get('id'), $this->get('module'), $this->get('item'))) {
            $this->slideUp('#js_item_feed_' . $this->get('id'));
            $this->alert(_p('feed_successfully_deleted'), _p('feed_deletion'), 300, 150, true);
        } else {
            $this->alert(_p('unable_to_delete_this_entry'));
        }
    }

    /* Loads Pages and results from Google Places Autocomplete given a latitude and longitude
     * This function populates $Core.FeedPlace.aPlaces with new items by passing parameters in jSon format */

    public function loadEstablishments()
    {
        $aPages = [];
        if (Phpfox::isAppActive('Core_Pages')) {
            $aPages = Phpfox::getService('pages')->getPagesByLocation($this->get('latitude'), $this->get('longitude'));
        }

        if (count($aPages)) {
            foreach ($aPages as $iKey => $aPage) {
                $aPages[$iKey]['geometry'] = ['latitude' => $aPage['location_latitude'], 'longitude' => $aPage['location_longitude']];
                $aPages[$iKey]['name'] = $aPage['title'];
                unset($aPages[$iKey]['location_latitude']);
                unset($aPages[$iKey]['location_longitude']);
            }
        }

        if (!empty($aPages)) {
            $jPages = json_encode($aPages);
            $this->call('$Core.FeedPlace.storePlaces(\'' . $jPages . '\');');
        }
    }

    public function editUserStatus()
    {
        Phpfox::isUser(true);
        $iFeedId = $this->get('id');
        $sModule = $this->get('module');
        $itemId = $this->get('item_id');
        Phpfox::getBlock('feed.edit-user-status', ['id' => $iFeedId, 'module' => $sModule]);
        $tablePrefix = !empty($sModule) && !in_array($sModule, ['link', 'photo', 'v']) ? (in_array($sModule, ['pages', 'groups']) ? 'pages_' : ($sModule . '_')) : '';
        $feed = Phpfox::getService('feed')->getFeed($iFeedId, $tablePrefix);
        if (!empty($feed)) {
            $params = [
                'type' => $feed['type_id']
            ];
            $this->call('<script type="text/javascript">$Core.editFeedStatus(' . json_encode($params) . '); if (typeof sCurrentFeedType !== "undefined") { sCurrentFeedType = "' . $feed['type_id'] . '"; } else { var sCurrentFeedType = "' . $feed['type_id'] . '"; }</script>');
            if (!empty($feed['parent_user_id']) && empty($sModule) && empty($itemId)) {
                $user = Phpfox::getService('user')->getUser($feed['parent_user_id'], 'u.user_id, u.profile_page_id');
                if (!empty($user) && (int)$user['profile_page_id'] == 0 && in_array($feed['type_id'], ['v', 'photo', 'video'])) {
                    $this->call('<script type="text/javascript">setTimeout(function(){editFeedStatusObject.changeFormAjaxRequest("feed.addComment");},100)</script>');
                }
            }
        }
    }

    public function updatePost()
    {
        $aVals = (array)$this->get('val');
        $aStatusFeed = !empty($aVals['feed_id']) ? Phpfox::getService('feed')->getUserStatusFeed(null, $aVals['feed_id']) : null;
        // check status empty
        if ((empty($aStatusFeed['feed_id']) || empty($aStatusFeed['parent_feed_id'])) && Phpfox::getLib('parse.format')->isEmpty($aVals['user_status'])) {
            $this->alert(_p('add_some_text_to_share'));
            $this->call('$Core.activityFeedProcess(false);');
            return false;
        }

        //Check if the tagged user is removed from feed in their profile
        $bHasTaggedFriends = false;
        $bCanLoadNewFeedContent = true;
        if (!empty($iProfileUserId = Phpfox::getService('profile')->getProfileUserId())
            && !empty($aVals['feed_id'])
            && !empty($aFeed = Phpfox::getService('feed')->getFeed($aVals['feed_id']))) {
            $bHasTaggedFriends = in_array($iProfileUserId, Phpfox::getService('feed.tag')->getTaggedUserIds($aFeed['item_id'], $aFeed['type_id']));
        }

        if (isset($aVals['feed_id'])
            && Phpfox::getService('feed.process')->updateFeedComment($aVals['feed_id'], $aVals['user_status'], !empty($aVals['tagged_friends']) ? explode(',', $aVals['tagged_friends']) : [], isset($aVals['location']) ? $aVals['location'] : [])) {
            if ($bHasTaggedFriends) {
                $aCurrentTaggedFriends = !empty($aVals['tagged_friends']) ? array_map(function($value) {
                    return trim($value);
                }, explode(',', $aVals['tagged_friends'])) : [];
                $bCanLoadNewFeedContent = in_array($iProfileUserId, $aCurrentTaggedFriends);
            }
            if ($bCanLoadNewFeedContent) {
                //Mean edit already status
                Phpfox::getService('feed')->processUpdateAjax($aVals['feed_id']);
            } elseif (!empty($aVals['feed_id'])) {
                $this->slideUp('#js_item_feed_' . $aVals['feed_id']);
            }
            $this->call('tb_remove();');
            $this->call('setTimeout(function(){$Core.resetActivityFeedForm();$Core.loadInit();}, 500);');
        }
    }

    public function friendsTagged()
    {
        $this->error(false);
        Phpfox::getBlock('feed.friends-tagged');
        $iTotalTaggedUsers = Phpfox::getService('feed.tag')->getTaggedUsers($this->get('item_id'), $this->get('type_id'), true);
        $this->setTitle(_p('total_friends', ['total' => $iTotalTaggedUsers]));
        $this->call('<script>$Core.loadInit();</script>');
    }

    public function buildMentionCache()
    {
        $sName = $this->get('name');
        $aMentions = Phpfox::getService('feed')->getUsersForMention($sName);
        if (!empty($aMentions)) {
            $this->call('$Cache.users_mention = ' . json_encode($aMentions) . ';');
            $this->call('$Core.loadInit();');
        }
        else {
            $this->call('$Cache.users_mention = [];');
        }
    }


    /** Hide Feed AJAX Functions */

    /**
     * Hide feed
     */
    public function hideFeed()
    {
        $iFeedId = (int)$this->get('id');
        if (!($iUserId = Phpfox::getUserId())) {
            $this->alert(_p('please_sign_in_to_continue_this_action'));
            return false;
        }
        if ($iFeedId) {
            if (Phpfox::getService('feed.hide')->add($iUserId, $iFeedId, 'feed')) {
                $this->call("\$Core.feed.hideFeed(" . json_encode([$iFeedId]) . ", " . json_encode([]) . ");");
                return true;
            }
        }
        $this->alert(_p('could_not_hide_this_feed'));
        $this->call("\$Core.feed.hideFeedFail(" . json_encode([$iFeedId]) . ", " . json_encode([]) . ");");
        return false;
    }

    /**
     * Hide all feeds of user
     */
    public function hideAllFromUser()
    {
        $iItemId = (int)$this->get('id');
        if (!($iUserId = Phpfox::getUserId())) {
            $this->alert(_p('please_sign_in_to_continue_this_action'));
            return false;
        }
        if ($iItemId && $iItemId != $iUserId) {
            if (Phpfox::getService('feed.hide')->add($iUserId, $iItemId, 'user')) {
                $this->call("\$Core.feed.hideFeed(" . json_encode([]) . ", " . json_encode([$iItemId]) . ");");
                return true;
            }
        }
        $this->alert(_p('could_not_hide_feed_from_this_user'));
        $this->call("\$Core.feed.hideFeedFail(" . json_encode([]) . ", " . json_encode([$iItemId]) . ");");
        return false;
    }

    /**
     * Undo feed is hidden
     */
    public function undoHideFeed()
    {
        $iUserId = Phpfox::getUserId();
        $iFeedId = $this->get('id');
        if ($iFeedId && $iUserId) {
            Phpfox::getService('feed.hide')->delete($iUserId, $iFeedId, 'feed');
        }
    }

    /**
     * Undo user's feeds is hidden
     */
    public function undoHideAllFromUser()
    {
        $iUserId = Phpfox::getUserId();
        $iHideUserId = $this->get('id');
        if ($iUserId && $iHideUserId) {
            Phpfox::getService('feed.hide')->delete($iUserId, $iHideUserId, 'user');
        }
    }

    /**
     * Show popup to manage hidden items
     */
    public function manageHidden()
    {
        $this->error(false);
        Phpfox::getBlock('feed.manage-hidden');
        $iPage = $this->get('page');
        if ($iPage) {
            $content = $this->getContent(false);
            $this->call('$("#feed_list_hidden").find(".js_pager_popup_view_more_link").remove();');
            if ($iPage == 1) {
                $this->html('.feed-hidden-items', $content);
                $this->call('$Core.feed.updateSelectedUnhideNumber();');
            } else {
                $this->append('.feed-hidden-items', $content);
            }
        }
    }

    /**
     * Un-hide feed/user
     */
    public function unhide()
    {
        $iUserId = Phpfox::getUserId();
        $iHideId = $this->get('hide_id');
        $iItemId = $this->get('item_id');
        $sTypeId = $this->get('type_id');

        if ($iUserId && $iHideId && $iItemId && $sTypeId) {
            if (Phpfox::getService('feed.hide')->delete($iUserId, $iItemId, $sTypeId)) {
                $this->call('$("#feed_item_hidden_' . $iHideId . '").hide("fast", function() {$(this).remove();$Core.feed.updateSelectedUnhideNumber();} );');
            } else {
                $this->alert(_p('could_not_unhide_from_this_user'));
            }
        }
    }

    /**
     * Un-hide multiple feeds/users
     *
     * @return bool
     */
    public function multiUnhide()
    {
        $iUserId = Phpfox::getUserId();
        $aIds = explode(',', $this->get('ids', ''));
        if ($iUserId && count($aIds)) {
            $aHideIds = [];
            foreach ($aIds as $key => $iHideId) {
                if (is_numeric($iHideId)) {
                    $aHideIds[] = $iHideId;
                }
            }
            if (Phpfox::getService('feed.hide')->multiDelete($aHideIds, $iUserId)) {
                $this->call('$Core.feed.deleteElemsById("feed_item_hidden_", ' . json_encode($aHideIds) . ', $Core.feed.resetSelectedUnhide);');
                return true;
            }
        }
        $this->alert(_p('could_not_unhide_selected_items'));
        return false;
    }
    /** End Hide Feed AJAX Functions */

    /** Hide Feed AJAX Functions */

    /**
     * Hide feed
     */
    public function removeTag()
    {
        $iFeedId = (int)$this->get('feed_id');
        $iItemId = (int)$this->get('item_id');
        $sTypeId = $this->get('type_id');
        if (!($iUserId = Phpfox::getUserId())) {
            $this->alert(_p('please_sign_in_to_continue_this_action'));
            return false;
        }
        if ($iFeedId) {
            if (Phpfox::getService('feed.tag')->removeTag($iUserId, $iItemId, $sTypeId)) {
                $this->call("\$Core.feed.removeTagSuccess(" . $iFeedId . ");");
                return true;
            }
        }
        $this->alert(_p('could_not_remove_tag_on_this_feed'));
        $this->call("\$Core.feed.removeTagFail(" . $iFeedId . ");");
        return false;
    }

    public function editInlinePrivacy()
    {
        Phpfox::getBlock('feed.inline-privacy-edit', $this->getAll());
    }

    public function redirectToProfile()
    {
        $iUserId = (int)$this->get('id');
        if (!$iUserId) {
            return false;
        }
        $aUser = Phpfox::getService('user')->getUser($iUserId, 'u.user_id, u.profile_page_id, u.user_name');
        if (empty($aUser['user_id'])) {
            return false;
        }
        $sUrl = '';
        if (!empty($aUser['profile_page_id']) && empty($aUser['user_name'])) {
            $iPageType = Phpfox::getService('user')->getPageType($aUser['profile_page_id']);
            if ($iPageType == 0 && Phpfox::isAppActive('Core_Pages')) { // is Page
                $sUrl = Phpfox::getLib('url')->makeUrl('pages', $aUser['profile_page_id']);
            } else if ($iPageType == 1 && Phpfox::isAppActive('PHPfox_Groups')) { // is Group
                $sUrl = Phpfox::getLib('url')->makeUrl('groups', $aUser['profile_page_id']);
            }
        } else {
            $sUrl = Phpfox::getLib('url')->makeUrl('profile', $aUser['user_name']);
        }
        if ($sUrl) {
            $this->call('window.open("'. $sUrl .'");');
        }
        return true;
    }
    public function resetScheduleForm()
    {
        $id = $this->get('id');
        if (!$id) {
            return false;
        }
        Phpfox::getLib('template')->getTemplate('feed.block.feed-schedule');
        $this->call('$("'. $id .'").html("'. $this->getContent() . '");');
        $this->call('$Core.loadInit();');
        return true;
    }
}