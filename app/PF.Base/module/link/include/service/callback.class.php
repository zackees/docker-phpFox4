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
 * @version        $Id: callback.class.php 7309 2014-05-08 16:05:43Z Fern $
 */
class Link_Service_Callback extends Phpfox_Service
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('link');
    }

    public function getCommentNotificationTag($aNotification)
    {
        $aRow = $this->database()->select('c.comment_id, l.link_id, l.title, u.user_name, u.full_name')
            ->from(Phpfox::getT('comment'), 'c')
            ->join(Phpfox::getT('link'), 'l', 'l.link_id = c.item_id')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = l.user_id')
            ->where('c.comment_id = ' . (int)$aNotification['item_id'])
            ->execute('getSlaveRow');
        if (!isset($aRow['full_name'])) {
            return false;
        }
        $sPhrase = _p('user_name_tagged_you_in_a_comment', ['user_name' => Phpfox::getService('notification')->getUsers($aRow)]);
        return [
            'link' => Phpfox_Url::instance()->makeUrl($aRow['user_name']) . 'link-id_' . $aRow['link_id'] . '/comment_' . $aRow['comment_id'] . '/',
            'message' => $sPhrase,
            'icon' => Phpfox_Template::instance()->getStyle('image', 'activity.png', 'blog')
        ];
    }

    public function getActivityFeedCustomChecks($aRow)
    {
        return $aRow;
    }

    public function canShareItemOnFeed()
    {
        return true;
    }

    public function getActivityFeed($aItem, $aCallBack = null, $bIsChildItem = false)
    {
        //Check in case the feed with tagging user is private
        if (!empty($aItem['parent_user_id'])
            && $aItem['parent_user_id'] == Phpfox::getUserId()
            && $aItem['user_id'] != $aItem['parent_user_id']
            && Phpfox::isModule('privacy')
            && !Phpfox::getService('privacy')->check($aItem['type_id'], $aItem['item_id'], $aItem['user_id'], $aItem['privacy'], null, true)) {
            return false;
        }

        if (Phpfox::isModule('like')) {
            $this->database()->select('l.like_id AS is_liked, ')
                ->leftJoin(Phpfox::getT('like'), 'l',
                    'l.type_id = \'link\' AND l.item_id = link.link_id AND l.user_id = ' . Phpfox::getUserId());
        }
        $isSharedFeed = !empty($aItem['parent_feed_id']) && !empty($aItem['parent_module_id']);
        $sSelect = '';
        if ($aCallBack === null) {
            $this->database()->select(Phpfox::getUserField('u', 'parent_') . ', ')
                ->leftJoin(Phpfox::getT('user'), 'u', 'u.user_id = link.parent_user_id');
        }

        if ($bIsChildItem) {
            $this->database()->select(Phpfox::getUserField('u2') . ', ')
                ->join(Phpfox::getT('user'), 'u2', 'u2.user_id = link.user_id');
        }

        $sSelect .= 'link.*';
        $aRow = $this->database()->select($sSelect)
            ->from($this->_sTable, 'link')
            ->where('link.link_id = ' . (int)$aItem['item_id'])
            ->execute('getSlaveRow');

        if ($bIsChildItem) {
            $aItem = $aRow;
        }

        if (!isset($aRow['link_id'])) {
            return false;
        }

        if (defined('PHPFOX_IS_PAGES_VIEW') && defined('PHPFOX_PAGES_ITEM_TYPE') &&
            ((PHPFOX_PAGES_ITEM_TYPE == 'pages' && !Phpfox::getService('pages')->hasPerm($aRow['item_id'], 'pages.view_browse_updates'))
                || (PHPFOX_PAGES_ITEM_TYPE == 'groups' && !Phpfox::getService('groups')->hasPerm($aRow['item_id'], 'groups.view_browse_updates')))) {
            return false;
        }

        if (!defined('PHPFOX_IS_PAGES_VIEW')) {
            if ($aRow['module_id'] == 'pages' && !Phpfox::getService('pages')->hasPerm($aRow['item_id'], 'pages.view_browse_updates')) {
                return false;
            }
            if ($aRow['module_id'] == 'groups') {
                if (!Phpfox::getService('groups')->hasPerm($aRow['item_id'], 'groups.view_browse_updates')) {
                    return false;
                }
                $group = db()->select('p.reg_method, p.page_id')
                    ->from(Phpfox::getT('link'), 'l')
                    ->join(Phpfox::getT('pages'), 'p', 'p.page_id = l.item_id AND p.item_type = 1')
                    ->where('l.link_id = ' . (int)($bIsChildItem ? $aItem['link_id'] : $aItem['item_id']))
                    ->execute('getSlaveRow');
                if (empty($group) || ((int)$group['reg_method'] == 2 && !Phpfox::getService('groups')->isMember($group['page_id']) &&
                        !Phpfox::getService('groups')->isAdmin($group['page_id']) &&
                        Phpfox::getService('user')->isAdminUser(Phpfox::getUserId()))) {
                    return false;
                }
            }
        }

        if (empty($aRow['link'])) {
            return false;
        }

        $aRow['image'] = Phpfox::getService('link')->getImage($aRow);

        if ($aRow['module_id'] == 'pages' && Phpfox::isAppActive('Core_Pages')) {
            $aPage = Phpfox::getService('pages')->getForView($aRow['parent_user_id']);

            if (empty($aPage)) {
                return false;
            }
            $aNewUser = Phpfox::getService('user')->getUser($aPage['page_user_id']);
            // Override the values
            $aRow['parent_profile_page_id'] = $aNewUser['profile_page_id'];
            $aRow['user_parent_server_id'] = $aNewUser['server_id'];
            $aRow['parent_user_name'] = (!empty($aNewUser['user_name']) ? $aNewUser['user_name'] : '');
            $aRow['parent_full_name'] = $aNewUser['full_name'];
            $aRow['parent_gender'] = $aNewUser['gender'];
            $aRow['parent_user_image'] = $aNewUser['user_image'];
            $aRow['parent_is_invisible'] = $aNewUser['is_invisible'];
            $aRow['parent_user_group_id'] = $aNewUser['user_group_id'];
            $aRow['parent_language_id'] = $aNewUser['language_id'];
            $aRow['parent_last_activity'] = $aNewUser['last_activity'];
            unset($aNewUser);
        }

        if ($aRow['module_id'] == 'groups' && Phpfox::isAppActive('PHPfox_Groups')) {
            $aPage = Phpfox::getService('groups')->getForView($aRow['parent_user_id']);

            if (empty($aPage)) {
                return false;
            }
            $aNewUser = Phpfox::getService('user')->getUser($aPage['page_user_id']);
            // Override the values
            $aRow['parent_profile_page_id'] = $aNewUser['profile_page_id'];
            $aRow['user_parent_server_id'] = $aNewUser['server_id'];
            $aRow['parent_user_name'] = (!empty($aNewUser['user_name']) ? $aNewUser['user_name'] : '');
            $aRow['parent_full_name'] = $aNewUser['full_name'];
            $aRow['parent_gender'] = $aNewUser['gender'];
            $aRow['parent_user_image'] = $aNewUser['user_image'];
            $aRow['parent_is_invisible'] = $aNewUser['is_invisible'];
            $aRow['parent_user_group_id'] = $aNewUser['user_group_id'];
            $aRow['parent_language_id'] = $aNewUser['language_id'];
            $aRow['parent_last_activity'] = $aNewUser['last_activity'];
            unset($aNewUser);
        }

        if (substr($aRow['link'], 0, 7) != 'http://' && substr($aRow['link'], 0, 8) != 'https://') {
            $aRow['link'] = 'http://' . $aRow['link'];
        }

        $feedLink = Phpfox::getService('link')->getFeedLink($aRow['link_id']);

        $aParts = parse_url($aRow['link']);
        $aReturn = [
            'feed_title' => !$isSharedFeed ? $aRow['title'] : '',
            'feed_status' => $aRow['status_info'] ? $aRow['status_info'] : ($isSharedFeed ? $aRow['link'] : ''),
            'feed_link_comment' => $feedLink,
            'feed_link' => $feedLink,
            'feed_link_actual' => $aRow['link'],
            'feed_content' => !$isSharedFeed ? $aRow['description'] : '',
            'total_comment' => $aRow['total_comment'],
            'feed_total_like' => $aRow['total_like'],
            'feed_is_liked' => (isset($aRow['is_liked']) ? $aRow['is_liked'] : false),
            'feed_icon' => Phpfox::getLib('image.helper')->display([
                'theme' => 'feed/link.png',
                'return_url' => true
            ]),
            'time_stamp' => $aRow['time_stamp'],
            'enable_like' => true,
            'comment_type_id' => 'link',
            'like_type_id' => 'link',
            'feed_title_extra' => !$isSharedFeed ? $aParts['host'] : '',
            'feed_title_extra_link' => !$isSharedFeed ? $aParts['scheme'] . '://' . $aParts['host'] : '',
            'custom_data_cache' => $aRow,
            'custom_class' => $isSharedFeed ? 'shared_feed' : '',
            'privacy' => $aRow['privacy']
        ];

        // get tagged users
        $aReturn['total_friends_tagged'] = Phpfox::getService('feed.tag')->getTaggedUsers($aItem['item_id'], 'link', true);
        if ($aReturn['total_friends_tagged']) {
            $aReturn['friends_tagged'] = Phpfox::getService('feed.tag')->getTaggedUsers($aItem['item_id'], 'link', false, 1, 2);
        }

        if (!empty($aRow['location_name'])) {
            $aReturn['location_name'] = $aRow['location_name'];
        }
        if (!empty($aRow['location_latlng'])) {
            $aReturn['location_latlng'] = json_decode($aRow['location_latlng'], true);
        }

        if (Phpfox::getParam('core.warn_on_external_links')) {
            if (!preg_match('/' . preg_quote(Phpfox::getParam('core.host')) . '/i', $aRow['link'])) {
                $aReturn['feed_link_actual'] = $aRow['link'];
                $aReturn['custom_css'] = 'external_link_warning';
            }
        }

        if (!empty($aRow['image']) && !$isSharedFeed) {
            $sImage = Phpfox::getLib('url')->secureUrl($aRow['image']);
            $aReturn['feed_image'] = '<img src="' . $sImage . '" alt="" />';
        }

        if (empty($aRow['module_id']) && !empty($aRow['parent_user_name']) && !defined('PHPFOX_IS_USER_PROFILE')) {
            $aReturn['parent_user'] = Phpfox::getService('user')->getUserFields(true, $aRow, 'parent_');
        }

        if ($aRow['has_embed']) {
            $aReturn['feed_image_onclick'] = '$Core.box(\'link.play\', 700, \'id=' . $aRow['link_id'] . '&amp;feed_id=' . $aItem['feed_id'] . '&amp;popup=true\', \'GET\'); return false;';
        }
        if ($bIsChildItem) {
            $aReturn = array_merge($aReturn, $aItem);
        }

        (($sPlugin = Phpfox_Plugin::get('link.component_service_callback_getactivityfeed__1')) ? eval($sPlugin) : false);

        if (!defined('PHPFOX_IS_PAGES_VIEW') && (($aRow['module_id'] == 'groups' && Phpfox::isAppActive('PHPfox_Groups')) || ($aRow['module_id'] == 'pages' && Phpfox::isAppActive('Core_Pages')))) {
            $aPage = $this->database()->select('p.*, pu.vanity_url, ' . Phpfox::getUserField('u', 'parent_'))
                ->from(':pages', 'p')
                ->join(':user', 'u', 'p.page_id=u.profile_page_id')
                ->leftJoin(Phpfox::getT('pages_url'), 'pu', 'pu.page_id = p.page_id')
                ->where('p.page_id=' . (int)$aRow['item_id'])
                ->execute('getSlaveRow');
            if (empty($aPage)) {
                return false;
            }
            $aReturn['parent_user_name'] = Phpfox::getService($aRow['module_id'])->getUrl($aPage['page_id'], $aPage['title'], $aPage['vanity_url']);
            $aReturn['feed_table_prefix'] = 'pages_';
            if ($aRow['user_id'] != $aPage['parent_user_id'] && $aItem['user_id'] != $aPage['parent_user_id']) {
                $aReturn['parent_user'] = Phpfox::getService('user')->getUserFields(true, $aPage, 'parent_');
            }
        }

        if (!Phpfox::getService('link')->isInternalLink($aRow['link'])) {
            if (Phpfox::getParam('core.disable_all_external_urls')) {
                $aReturn['feed_image_onclick'] = 'return false;';
                $aReturn['feed_link_actual'] = '#';
                $aReturn['custom_class'] = 'activity_feed_disabled_link';
                unset($aReturn['feed_title_extra_link']);
                unset($aReturn['feed_image']);
                unset($aReturn['feed_image_banner']);
                unset($aReturn['feed_title']);
                unset($aReturn['feed_title_extra']);
                unset($aReturn['feed_content']);

                if (!$aReturn['feed_status']) {
                    $aReturn['feed_status'] = $aRow['link'];
                }
            }
            $aReturn['is_external_url'] = true;
        }

        if (Phpfox_Request::instance()->get('id') && empty($aReturn['feed_status'])) {
            $aReturn['feed_status'] = $aRow['link'];
        }

        return $aReturn;
    }

    public function addLike($iItemId, $bDoNotSendEmail = false)
    {
        $aRow = $this->database()->select('l.link_id, l.title, l.user_id, l.module_id, u.profile_page_id')
            ->from(Phpfox::getT('link'), 'l')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = l.user_id')
            ->where('link_id = ' . (int)$iItemId)
            ->execute('getSlaveRow');

        if (!isset($aRow['link_id'])) {
            return false;
        }
        $this->database()->updateCount('like', 'type_id = \'link\' AND item_id = ' . (int)$iItemId . '', 'total_like', 'link', 'link_id = ' . (int)$iItemId);

        if ($aRow['profile_page_id'] && in_array($aRow['module_id'], ['pages', 'groups'])) {
            $aPage = Phpfox::getService($aRow['module_id'])->getPage($aRow['profile_page_id']);
            $aRow['user_id'] = $aPage['user_id'];
        }

        if (!$bDoNotSendEmail) {
            $sLink = Phpfox::permalink('link', $aRow['link_id'], $aRow['title']);
            Phpfox::getLib('mail')->to($aRow['user_id'])
                ->subject(['full_name_liked_your_link_title', ['full_name' => Phpfox::getUserBy('full_name'), 'title' => $aRow['title']]])
                ->message(['full_name_liked_your_link_title_message', ['full_name' => Phpfox::getUserBy('full_name'), 'link' => $sLink, 'title' => $aRow['title']]])
                ->notification('like.new_like')
                ->send();

        }
        Phpfox::getService('notification.process')->add('link_like', $aRow['link_id'], $aRow['user_id']);

        return null;
    }

    public function getNotificationLike($aNotification)
    {
        $aRow = $this->database()->select('l.link_id, l.title, l.user_id, u.gender, u.full_name')
            ->from(Phpfox::getT('link'), 'l')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = l.user_id')
            ->where('l.link_id = ' . (int)$aNotification['item_id'])
            ->execute('getSlaveRow');

        $sUsers = Phpfox::getService('notification')->getUsers($aNotification);
        $sTitle = Phpfox::getLib('parse.output')->shorten($aRow['title'], Phpfox::getParam('notification.total_notification_title_length'), '...');

        if ($aNotification['user_id'] == $aRow['user_id']) {
            $sPhrase = _p('users_liked_gender_own_link_title', ['users' => $sUsers, 'gender' => Phpfox::getService('user')->gender($aRow['gender'], 1), 'title' => $sTitle]);
        } elseif ($aRow['user_id'] == Phpfox::getUserId()) {
            $sPhrase = _p('users_liked_your_link_title', ['users' => $sUsers, 'title' => $sTitle]);
        } else {
            $sPhrase = _p('users_liked_span_class_drop_data_user_row_full_name_s_span_link_title', ['users' => $sUsers, 'row_full_name' => $aRow['full_name'], 'title' => $sTitle]);
        }

        return [
            'link' => Phpfox_Url::instance()->permalink('link', $aRow['link_id'], $aRow['title']),
            'message' => $sPhrase,
            'icon' => Phpfox_Template::instance()->getStyle('image', 'activity.png', 'blog')
        ];
    }

    public function deleteLike($iItemId)
    {
        $this->database()->updateCount('like', 'type_id = \'link\' AND item_id = ' . (int)$iItemId . '', 'total_like', 'link', 'link_id = ' . (int)$iItemId);
    }

    public function deleteComment($iId)
    {
        $this->database()->update(Phpfox::getT('link'), ['total_comment' => ['= total_comment -', 1]], 'link_id = ' . (int)$iId);
    }

    public function getAjaxCommentVar()
    {
        return null;
    }

    public function addComment($aVals, $iUserId = null, $sUserName = null)
    {
        $aRow = $this->database()->select('l.link_id, l.title, l.status_info, l.module_id, u.full_name, u.user_id, u.user_name, u.gender, u.profile_page_id')
            ->from(Phpfox::getT('link'), 'l')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = l.user_id')
            ->where('l.link_id = ' . (int)$aVals['item_id'])
            ->execute('getSlaveRow');

        // Update the post counter if its not a comment put under moderation or if the person posting the comment is the owner of the item.
        if (empty($aVals['parent_id'])) {
            $this->database()->updateCounter('link', 'total_comment', 'link_id', $aRow['link_id']);
        }

        if ($aRow['profile_page_id'] && in_array($aRow['module_id'], ['pages', 'groups'])) {
            $aPage = Phpfox::getService($aRow['module_id'])->getPage($aRow['profile_page_id']);
            $aRow['user_id'] = $aPage['user_id'];
        }

        // Send the user an email
        $sLink = Phpfox_Url::instance()->permalink('link', $aRow['link_id'], $aRow['title']);
        Phpfox::getService('comment.process')->notify([
                'user_id' => $aRow['user_id'],
                'item_id' => $aRow['link_id'],
                'owner_subject' => ['full_name_commented_on_your_link_title', ['full_name' => Phpfox::getUserBy('full_name'), 'title' => $this->preParse()->clean($aRow['title'], 100)]],
                'owner_message' => ['full_name_commented_on_your_link_a_href_link_title_a', ['full_name' => Phpfox::getUserBy('full_name'), 'link' => $sLink, 'title' => $aRow['title']]],
                'owner_notification' => 'comment.add_new_comment',
                'notify_id' => 'comment_link',
                'mass_id' => 'link',
                'mass_subject' => (Phpfox::getUserId() == $aRow['user_id'] ? ['full_name_commented_on_gender_link', ['full_name' => Phpfox::getUserBy('full_name'), 'gender' => Phpfox::getService('user')->gender($aRow['gender'], 1)]] : ['full_name_commented_on_row_full_name_s_link', ['full_name' => Phpfox::getUserBy('full_name'), 'row_full_name' => $aRow['full_name']]]),
                'mass_message' => (Phpfox::getUserId() == $aRow['user_id'] ? ['full_name_commented_on_gender_link_a_href_link_title_a', ['full_name' => Phpfox::getUserBy('full_name'), 'gender' => Phpfox::getService('user')->gender($aRow['gender'], 1), 'link' => $sLink, 'title' => $aRow['title']]] : ['full_name_commented_on_row_full_name_s_link_a_href_link_title_a_message', ['full_name' => Phpfox::getUserBy('full_name'), 'row_full_name' => $aRow['full_name'], 'link' => $sLink, 'title' => $aRow['title']]])
            ]
        );

        // send notification to tagged users
        $aTaggedUsers = Phpfox::getService('feed.tag')->getTaggedUserIds($aRow['link_id'], 'link');
        $aMentions = Phpfox::getService('user.process')->getIdFromMentions($aRow['status_info'], true);

        (Phpfox::isModule('feed') ? Phpfox::getService('feed.tag')->filterTaggedPrivacy($aMentions, $aTaggedUsers, $aRow['link_id'], 'link') : null);

        $aMentions = array_merge($aMentions, $aTaggedUsers);
        $aUsers = array_diff($aMentions, [Phpfox::getUserId()]); // remove sender

        foreach ($aUsers as $iUserId) {
            Phpfox::getService('notification.process')->add('comment_link', $aRow['link_id'], $iUserId, Phpfox::getUserId());
            // send email
            Phpfox::getLib('mail')->to($iUserId)
                ->subject(['full_name_commented_on_row_full_name_s_link', [
                    'full_name' => Phpfox::getUserBy('full_name'),
                    'row_full_name' => $aRow['full_name']
                ]])
                ->message(['full_name_commented_on_row_full_name_s_link_a_href_link_title_a_message', [
                    'full_name' => Phpfox::getUserBy('full_name'),
                    'row_full_name' => $aRow['full_name'],
                    'link' => $sLink,
                    'title' => $aRow['title']
                ]])
                ->send();
        }
    }

    public function getCommentItem($iId)
    {
        $aRow = $this->database()->select('link_id AS comment_item_id, privacy_comment, user_id AS comment_user_id, module_id AS parent_module_id')
            ->from(Phpfox::getT('link'))
            ->where('link_id = ' . (int)$iId)
            ->execute('getSlaveRow');

        $aRow['comment_view_id'] = '0';

        if (!Phpfox::getService('comment')->canPostComment($aRow['comment_user_id'], $aRow['privacy_comment'])) {
            Phpfox_Error::set(_p('unable_to_post_a_comment_on_this_item_due_to_privacy_settings'));

            unset($aRow['comment_item_id']);
        }

        return $aRow;
    }

    public function getCommentNotification($aNotification)
    {
        $aRow = $this->database()->select('l.link_id, l.title, u.user_id, u.gender, u.user_name, u.full_name')
            ->from(Phpfox::getT('link'), 'l')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = l.user_id')
            ->where('l.link_id = ' . (int)$aNotification['item_id'])
            ->execute('getSlaveRow');

        $sUsers = Phpfox::getService('notification')->getUsers($aNotification);
        $sTitle = Phpfox::getLib('parse.output')->shorten($aRow['title'], Phpfox::getParam('notification.total_notification_title_length'), '...');

        if ($aNotification['user_id'] == $aRow['user_id'] && !isset($aNotification['extra_users'])) {
            $sPhrase = _p('users_commented_on_gender_link_title', ['users' => $sUsers, 'gender' => Phpfox::getService('user')->gender($aRow['gender'], 1), 'title' => $sTitle]);
        } elseif ($aRow['user_id'] == Phpfox::getUserId()) {
            $sPhrase = _p('users_commented_on_your_link_title', ['users' => $sUsers, 'title' => $sTitle]);
        } else {
            $sPhrase = _p('users_commented_on_span_class_drop_data_user_row_full_name_s_span_link_title', ['users' => $sUsers, 'row_full_name' => $aRow['full_name'], 'title' => $sTitle]);
        }

        return [
            'link' => Phpfox_Url::instance()->permalink('link', $aRow['link_id'], $aRow['title']),
            'message' => $sPhrase,
            'icon' => Phpfox_Template::instance()->getStyle('image', 'activity.png', 'blog')
        ];
    }

    public function canViewPageSection($iPage)
    {
        if (!Phpfox::isAppActive('Core_Pages')) {
            return false;
        }

        return true;
    }

    public function checkFeedShareLink()
    {
        (($sPlugin = Phpfox_Plugin::get('link.service_callback_checkfeedsharelink')) ? eval($sPlugin) : '');

        if (isset($bNoFeedLink)) {
            return false;
        }

        if (defined('PHPFOX_IS_PAGES_VIEW') && defined('PHPFOX_PAGES_ITEM_TYPE') && !Phpfox::getService(PHPFOX_PAGES_ITEM_TYPE)->hasPerm(null, 'link.share_links')) {
            return false;
        }
    }

    /**
     * @param int $iId
     *
     * @return bool|string
     */
    public function getReportRedirect($iId)
    {
        return $this->getRedirectComment($iId);
    }

    public function getRedirectComment($iId)
    {
        $aLink = $this->database()->select('u.user_name')
            ->from(Phpfox::getT('link'), 'l')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = l.user_id')
            ->where('l.link_id = ' . (int)$iId)
            ->execute('getSlaveField');

        $sLink = Phpfox_Url::instance()->makeUrl($aLink, ['link-id' => $iId]);
        return $sLink;
    }

    /**
     * Process send notify to tagged users
     * @param $params
     * @return bool
     */
    public function sendNotifyToTaggedUsers($params)
    {
        $sFeedType = $params['feed_type'];
        $aTagged = $params['tagged_friend'];
        $iItemId = $params['item_id'];
        $iOwnerId = $params['owner_id'];
        $iFeedId = $params['feed_id'];
        $iPrivacy = $params['privacy'];
        $iParentUserId = (int)$params['parent_user_id'];
        $moduleId = isset($params['module_id']) ? $params['module_id'] : '';

        // check link exist
        $link = Phpfox::getService('link')->getFeedLink($iItemId);
        if (empty($link)) {
            return false;
        }

        $aCurrentUser = Phpfox::getService('user')->getUser($iOwnerId);
        $sTagger = (isset($aCurrentUser['full_name']) && $aCurrentUser['full_name']) ? $aCurrentUser['full_name'] : $aCurrentUser['user_name'];

        //Send Mail and add feed
        foreach ($aTagged as $iUserId) {
            if (in_array($moduleId, ['', 'user'])) {
                if ($iParentUserId == $iUserId) {
                    continue;
                }
                (Phpfox::isModule('feed') ? Phpfox::getService('feed.process')->add($sFeedType, $iItemId, $iPrivacy, 0, $iUserId, $iOwnerId, 1, $iFeedId) : null);
            }
            if (empty($params['no_notification']) && Phpfox::isModule('notification')) {
                Phpfox::getService('notification.process')->add('feed_tagged_link', $iItemId, $iUserId, $iOwnerId, true);
            }

            if (empty($params['no_email'])) {
                Phpfox::getLib('mail')->to($iUserId)
                    ->notification('feed.tagged_in_post')
                    ->subject(['full_name_tagged_you_in_a_link', ['full_name' => $sTagger]])
                    ->message(['full_name_tagged_you_in_a_link_you_can_view_here', [
                        'full_name' => $sTagger,
                        'link' => $link
                    ]])
                    ->send();
            }
        }
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
        if ($sPlugin = Phpfox_Plugin::get('link.service_callback__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }

    public function deleteFeedItem($iLinkId)
    {
        if (!$iLinkId) {
            return false;
        }
        //Remove comments
        Phpfox::isModule('comment') ? Phpfox::getService('comment.process')->deleteForItem(Phpfox::getUserId(), $iLinkId, 'link') : null;
        //Remove link_embed
        db()->delete(':link_embed', 'link_id = ' . $iLinkId);
        //Remove comment_link
        db()->delete(':notification', 'type_id = \'comment_link\' and item_id = ' . $iLinkId);
        //Remove link_like
        db()->delete(':notification', 'type_id = \'link_like\' and item_id = ' . $iLinkId);

        return true;
    }

    public function addScheduleItemToFeed($aVals) {
        $aCallback = null;
        if (isset($aVals['callback_module']) && Phpfox::hasCallback($aVals['callback_module'], 'addLink')) {
            $aCallback = Phpfox::callback($aVals['callback_module'] . '.addLink', $aVals);
        }
        if (!empty($aCallback) && $aCallback['module'] == 'pages') {
            $valid = false;
            if(Phpfox::isAppActive('Core_Pages')) {
                $aPage = Phpfox::getService('pages')->getForView($aCallback['item_id']);
                if(!empty($aPage)) {
                    $valid = true;
                    if(isset($aPage['use_timeline']) && $aPage['use_timeline']) {
                        if (!defined('PAGE_TIME_LINE')) {
                            define('PAGE_TIME_LINE', true);
                        }
                    }
                }
            }
            if(!$valid && Phpfox::isAppActive('PHPfox_Groups')) {
                $aPage = Phpfox::getService('groups')->getForView($aCallback['item_id']);
                if(!empty($aPage)) {
                    $aVals['callback_module'] = $aCallback['module'] = 'groups';
                    if(isset($aPage['use_timeline']) && $aPage['use_timeline']) {
                        if (!defined('PAGE_TIME_LINE')) {
                            define('PAGE_TIME_LINE', true);
                        }
                    }
                }
            }
        }
        if (($iId = Phpfox::getService('link.process')->add($aVals, false, $aCallback))) {
            (($sPlugin = Phpfox_Plugin::get('link.component_ajax_addviastatusupdate')) ? eval($sPlugin) : false);
            return true;
        }
        return false;
    }

    public function getAdditionalScheduleInfo($aRow) {
        $aInfo = [];
        $data = $aRow['data'];
        $aInfo['item_title'] = !empty($data['user_status']) ? $data['user_status'] : (isset($data['link']['url']) ? $data['link']['url'] : '');
        $aInfo['item_name'] = _p('link');
        if (isset($data['link']['image'])) {
            $aInfo['item_images'] = [
                'remaining' => 0,
                'images' => [$data['link']['image']]
            ];
        }

        return $aInfo;
    }

    public function getExtraScheduleData($data) {
        if(!empty($data['data']['location']['latlng'])) {
            $aLatLng = explode(',', $data['data']['location']['latlng']);
            $data['data']['location_latlng']['latitude'] = $aLatLng[0];
            $data['data']['location_latlng']['longitude'] = $aLatLng[1];
        }
        if(!empty($data['data']['location']['name'])) {
            $data['data']['location_name'] = $data['data']['location']['name'];
        }
        return $data;
    }
}