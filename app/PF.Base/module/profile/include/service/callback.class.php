<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Profile_Service_Callback
 */
class Profile_Service_Callback extends Phpfox_Service
{
    /**
     * Class constructor
     *
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('user');
    }

    /**
     * Inserts a track record, i.e. when $iUserId visits $iId's profile
     */
    public function addTrack($iId, $iUserId = null)
    {
        $this->database()->insert(Phpfox::getT('track'), [
            'type_id'    => 'user',
            'item_id'    => (int)$iId,
            'ip_address' => '',
            'user_id'    => Phpfox::getUserBy('user_id'),
            'time_stamp' => PHPFOX_TIME
        ]);
        return null;
    }

    /**
     * Gets the latest users to profile $iId filtering out $iUserId
     *
     * @param $iId     int The stalkee user
     * @param $iUserId int The stalker
     *
     * @return array|bool
     */
    public function getLatestTrackUsers($iId, $iUserId)
    {
        $aRows = $this->database()->select('track.time_stamp,' . Phpfox::getUserField())
            ->from(Phpfox::getT('track'), 'track')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = track.user_id AND u.profile_page_id = 0')
            ->where('track.item_id = ' . (int)$iId . ' AND track.user_id != ' . (int)$iUserId . ' AND track.type_id="user"')
            ->order('track.time_stamp DESC')
            ->limit(0, 7)
            ->execute('getSlaveRows');

        return (count($aRows) ? $aRows : false);
    }

    public function getCommentNewsFeed($aRow)
    {
        $oUrl = Phpfox_Url::instance();
        if ($aRow['owner_user_id'] == $aRow['item_id']) {
            $aRow['text'] = _p('a_href_user_link_full_name_a_added_a_new_comment_on_their_own_a_href_title_link_profile_a',
                [
                    'user_link'  => $oUrl->makeUrl('feed.user', ['id' => $aRow['user_id']]),
                    'full_name'  => $this->preParse()->clean($aRow['owner_full_name']),
                    'title_link' => $aRow['link']
                ]
            );
        } else if ($aRow['item_id'] == Phpfox::getUserId()) {
            $aRow['text'] = _p('a_href_user_link_full_name_a_added_a_new_comment_on_your_a_href_title_link_profile_a',
                [
                    'user_link'  => $oUrl->makeUrl('feed.user', ['id' => $aRow['user_id']]),
                    'full_name'  => $this->preParse()->clean($aRow['owner_full_name']),
                    'title_link' => $aRow['link']
                ]
            );
        } else {
            $aRow['text'] = _p('a_href_user_link_full_name_a_added_a_new_comment_on_a_href_title_link_item_user_name_s_a_profile',
                [
                    'user_link'      => $oUrl->makeUrl('feed.user', ['id' => $aRow['user_id']]),
                    'full_name'      => $this->preParse()->clean($aRow['owner_full_name']),
                    'title_link'     => $aRow['link'],
                    'item_user_name' => $this->preParse()->clean($aRow['viewer_full_name'])
                ]
            );
        }

        $aRow['text'] .= Phpfox::getService('feed')->quote($aRow['content']);

        return $aRow;
    }

    public function getAjaxCommentVar()
    {
        return 'profile.can_post_comment_on_profile';
    }

    public function addComment($aVals, $iUserId = null, $sUserName = null)
    {
        Phpfox::getService('user.field.process')->updateCommentCounter($aVals['item_id']);

        $aUser = $this->database()->select('user_id, user_name')
            ->from(Phpfox::getT('user'))
            ->where('user_id = ' . (int)$aVals['item_id'])
            ->execute('getSlaveRow');

        (Phpfox::isModule('feed') ? Phpfox::getService('feed.process')->add('comment_profile', $aVals['item_id'],
            $aVals['text_parsed'], $iUserId, $aUser['user_id'], $aVals['comment_id']) : null);

        $sLink = Phpfox_Url::instance()->makeUrl($aUser['user_name']);
        Phpfox::getLib('mail')
            ->to($aUser['user_id'])
            ->subject([
                'profile.user_name_left_you_a_comment_on_site_title',
                ['user_name' => $sUserName, 'site_title' => Phpfox::getParam('core.site_title')]
            ])
            ->message([
                    'profile.user_name_left_you_a_comment_on_your_profile_message',
                    [
                        'user_name' => $sUserName,
                        'link'      => $sLink
                    ]
                ]
            )
            ->notification('comment.add_new_comment')
            ->send();

        $aActualUser = Phpfox::getService('user')->getUser($iUserId);
        Phpfox::getService('notification.process')->add('comment_profile', $aUser['user_id'], $aUser['user_id'], [
                'title'     => '',
                'user_id'   => $aActualUser['user_id'],
                'image'     => $aActualUser['user_image'],
                'server_id' => $aActualUser['server_id']
            ]
        );
    }

    public function updateCommentText($aVals, $sText)
    {
        (Phpfox::isModule('feed') ? Phpfox::getService('feed.process')->update('comment_profile', $aVals['item_id'],
            $sText, $aVals['comment_id']) : null);
    }

    public function getCommentNotificationFeed($aRow)
    {
        return [
            'message' => _p('a_href_user_link_full_name_a_wrote_a_comment_on_your_a_href_profile_link_profile_a', [
                    'user_link'    => Phpfox_Url::instance()->makeUrl($aRow['user_name']),
                    'full_name'    => $this->preParse()->clean($aRow['full_name']),
                    'profile_link' => Phpfox_Url::instance()->makeUrl('profile')
                ]
            ),
            'link'    => Phpfox_Url::instance()->makeUrl('profile'),
            'path'    => 'core.url_user',
            'suffix'  => '_50'
        ];
    }

    public function getFeedRedirect($iId, $iChild = 0)
    {
        $aUser = $this->database()->select(Phpfox::getUserField())
            ->from(Phpfox::getT('user'), 'u')
            ->where('u.user_id = ' . (int)$iId)
            ->execute('getSlaveRow');

        if ($iChild > 0) {
            return Phpfox_Url::instance()->makeUrl($aUser['user_name'], ['comment' => $iChild, '#comment-view']);
        }
        return Phpfox_Url::instance()->makeUrl($aUser['user_name']);
    }

    public function getCommentItem($iId)
    {
        $aUser = $this->database()->select('user_id AS comment_item_id, user_id AS comment_user_id')
            ->from($this->_sTable)
            ->where('user_id = ' . (int)$iId)
            ->execute('getSlaveRow');

        $aUser['comment_view_id'] = '0';

        return $aUser;
    }

    public function getProfileSettings()
    {
        return [
            'profile.view_profile'  => [
                'phrase'      => _p('who_can_view_your_profile_page')
            ],
            'profile.profile_info'  => [
                'phrase' => _p('who_can_view_the_info_tab_on_your_profile_page')
            ],
            'profile.basic_info'    => [
                'phrase'      => _p('who_can_view_your_basic_info')
            ],
            'profile.view_location' => [
                'phrase' => _p('who_can_view_your_location')
            ]
        ];
    }

    public function deleteComment($iId)
    {
        Phpfox::getService('user.field.process')->updateCommentCounter($iId, true);
    }

    public function getBlocksIndex()
    {
        return [
            'table' => 'user_design_order',
            'field' => 'user_id'
        ];
    }

    public function getRedirectComment($iId)
    {
        return $this->getFeedRedirect($iId);
    }

    public function getRssTitle($iId)
    {
        $aUser = Phpfox::getService('user')->getUser($iId, 'u.full_name');
        return _p('comments_on') . ': ' . Phpfox::getLib('parse.output')->clean($aUser['full_name']);
    }

    public function getNewsFeedInfo($aRow)
    {
        if ($sPlugin = Phpfox_Plugin::get('profile.service_callback_getnewsfeedinfo_start')) {
            eval($sPlugin);
        }
        $aRow['text'] = _p((empty($aRow['owner_gender']) ? 'full_name_s_profile_has_been_updated' : 'a_href_user_link_full_name_a_updated_their_profile'),
            [
                'user_link' => Phpfox_Url::instance()->makeUrl($aRow['owner_user_name']),
                'full_name' => $this->preParse()->clean($aRow['owner_full_name']),
                'gender'    => Phpfox::getService('user')->gender($aRow['owner_gender'], 1)
            ]
        );

        $aRow['icon'] = 'misc/application_edit.png';

        return $aRow;
    }

    public function getNewsFeedDesign($aRow)
    {
        $aRow['text'] = _p((empty($aRow['owner_gender']) ? 'full_name_s_profile_design_has_been_updated' : 'a_href_user_link_full_name_a_updated_their_profile_design'),
            [
                'user_link' => Phpfox_Url::instance()->makeUrl($aRow['owner_user_name']),
                'full_name' => $this->preParse()->clean($aRow['owner_full_name']),
                'gender'    => Phpfox::getService('user')->gender($aRow['owner_gender'], 1)
            ]
        );

        $aRow['icon'] = 'misc/color_swatch.png';
        $aRow['enable_like'] = true;

        return $aRow;
    }

    public function getItemName($iId, $sName)
    {
        return '<a href="' . Phpfox_Url::instance()->makeUrl('comment.view',
                ['id' => $iId]) . '">' . _p('on_name_s_profile', ['name' => $sName]) . '</a>';
    }

    public function getCommentNewsFeedMy($aRow)
    {
        if ($aRow['type_id'] == 'comment_profile_my_feedLike') {
            if ($aRow['owner_user_id'] == $aRow['viewer_user_id']) {
                $aRow['text'] = _p('a_href_user_link_full_name_a_likes_their_own_a_href_link_coment_a', [
                        'full_name' => Phpfox::getLib('parse.output')->clean($aRow['owner_full_name']),
                        'user_link' => Phpfox_Url::instance()->makeUrl($aRow['owner_user_name']),
                        'gender'    => Phpfox::getService('user')->gender($aRow['owner_gender'], 1),
                        'link'      => Phpfox_Url::instance()->makeUrl($aRow['content'],
                            ['feed' => $aRow['item_id'], 'flike' => 'fcomment'])
                    ]
                );
            } else {
                $aRow['text'] = _p('a_href_user_link_full_name_a_likes_a_href_view_user_link_view_full_name_a_s_a_href_link_comment_a',
                    [
                        'full_name'      => Phpfox::getLib('parse.output')->clean($aRow['owner_full_name']),
                        'user_link'      => Phpfox_Url::instance()->makeUrl($aRow['owner_user_name']),
                        'view_full_name' => Phpfox::getLib('parse.output')->clean($aRow['viewer_full_name']),
                        'view_user_link' => Phpfox_Url::instance()->makeUrl($aRow['viewer_user_name']),
                        'link'           => Phpfox_Url::instance()->makeUrl($aRow['content'],
                            ['feed' => $aRow['item_id'], 'flike' => 'fcomment'])
                    ]
                );
            }

            $aRow['icon'] = 'misc/thumb_up.png';
        } else {
            $aRow['text'] = $aRow['content'];
            $aRow['owner_user_link'] = Phpfox_Url::instance()->makeUrl($aRow['owner_user_name']);
            $aRow['viewer_user_link'] = Phpfox_Url::instance()->makeUrl($aRow['viewer_user_name']);
        }

        return $aRow;
    }

    public function getNewsFeedDesign_FeedLike($aRow)
    {
        if ($aRow['owner_user_id'] == $aRow['viewer_user_id']) {
            $aRow['text'] = _p('a_href_user_link_full_name_a_liked_their_own_profile_a_href_link_design_a', [
                    'full_name' => Phpfox::getLib('parse.output')->clean($aRow['owner_full_name']),
                    'user_link' => Phpfox_Url::instance()->makeUrl($aRow['owner_user_name']),
                    'link'      => $aRow['link']
                ]
            );
        } else {
            $aRow['text'] = _p('a_href_user_link_full_name_a_liked_a_href_view_user_link_view_full_name_a_s_profile_a_href_link_design_a',
                [
                    'full_name'      => Phpfox::getLib('parse.output')->clean($aRow['owner_full_name']),
                    'user_link'      => Phpfox_Url::instance()->makeUrl($aRow['owner_user_name']),
                    'view_full_name' => Phpfox::getLib('parse.output')->clean($aRow['viewer_full_name']),
                    'view_user_link' => Phpfox_Url::instance()->makeUrl($aRow['viewer_user_name']),
                    'link'           => $aRow['link']
                ]
            );
        }

        $aRow['icon'] = 'misc/thumb_up.png';

        return $aRow;
    }

    public function getFeedRedirectDesign_FeedLike($iId, $iChildId = 0)
    {
        return $this->getFeedRedirect($iChildId);
    }

    public function getNotificationFeedDesign_NotifyLike($aRow)
    {
        return [
            'message' => _p('a_href_user_link_full_name_a_likes_your_recent_profile_a_href_link_design_a', [
                    'full_name' => Phpfox::getLib('parse.output')->clean($aRow['full_name']),
                    'user_link' => Phpfox_Url::instance()->makeUrl($aRow['user_name'])
                ]
            ),
            'link'    => Phpfox_Url::instance()->makeUrl(Phpfox::getUserBy('user_name'))
        ];
    }

    public function sendLikeEmailDesign($iItemId)
    {
        return _p('a_href_user_link_full_name_a_likes_your_recent_profile_a_href_link_design_a', [
                'full_name' => Phpfox::getLib('parse.output')->clean(Phpfox::getUserBy('full_name')),
                'user_link' => Phpfox_Url::instance()->makeUrl(Phpfox::getUserBy('user_name'))
            ]
        );
    }

    public function getCommentNewsFeedMy_Feedlike($aRow)
    {
        if ($aRow['owner_user_id'] == $aRow['viewer_user_id']) {
            $aRow['text'] = _p('a_href_user_link_full_name_a_likes_their_own_a_href_link_coment_a', [
                    'full_name' => Phpfox::getLib('parse.output')->clean($aRow['owner_full_name']),
                    'user_link' => Phpfox_Url::instance()->makeUrl($aRow['owner_user_name']),
                    'gender'    => Phpfox::getService('user')->gender($aRow['owner_gender'], 1),
                    'link'      => Phpfox_Url::instance()->makeUrl($aRow['content'],
                        ['feed' => $aRow['item_id'], 'flike' => 'fcomment'])
                ]
            );
        } else {
            $aRow['text'] = _p('a_href_user_link_full_name_a_likes_a_href_view_user_link_view_full_name_a_s_a_href_link_comment_a',
                [
                    'full_name'      => Phpfox::getLib('parse.output')->clean($aRow['owner_full_name']),
                    'user_link'      => Phpfox_Url::instance()->makeUrl($aRow['owner_user_name']),
                    'view_full_name' => Phpfox::getLib('parse.output')->clean($aRow['viewer_full_name']),
                    'view_user_link' => Phpfox_Url::instance()->makeUrl($aRow['viewer_user_name']),
                    'link'           => Phpfox_Url::instance()->makeUrl($aRow['content'],
                        ['feed' => $aRow['item_id'], 'flike' => 'fcomment'])
                ]
            );
        }

        $aRow['icon'] = 'misc/thumb_up.png';

        return $aRow;
    }

    public function getFeedRedirectMy($iId)
    {
        return $this->getFeedRedirect($iId) . 'feed_' . Phpfox_Request::instance()->getInt('id') . '/flike_fcomment/';
    }

    public function getCommentNotificationFeedMy($aRow)
    {
        return [
            'message' => _p('a_href_user_link_full_name_a_likes_your_a_href_link_comment_a', [
                    'full_name' => Phpfox::getLib('parse.output')->clean($aRow['full_name']),
                    'user_link' => Phpfox_Url::instance()->makeUrl($aRow['user_name']),
                    'link'      => Phpfox_Url::instance()->makeUrl('feed.view', ['id' => $aRow['item_id']])
                ]
            ),
            'link'    => Phpfox_Url::instance()->makeUrl('feed.view', ['id' => $aRow['item_id']])
        ];
    }

    public function getCommentNotificationFeedMy_NotifyLike($aRow)
    {
        return [
            'message' => _p('a_href_user_link_full_name_a_likes_your_a_href_link_comment_a', [
                    'full_name' => Phpfox::getLib('parse.output')->clean($aRow['full_name']),
                    'user_link' => Phpfox_Url::instance()->makeUrl($aRow['user_name']),
                    'link'      => Phpfox_Url::instance()->makeUrl(Phpfox::getUserBy('user_name'),
                        ['feed' => $aRow['item_id'], 'flike' => 'fcomment'])
                ]
            ),
            'link'    => Phpfox_Url::instance()->makeUrl(Phpfox::getUserBy('user_name'),
                ['feed' => $aRow['item_id'], 'flike' => 'fcomment'])
        ];
    }

    public function getAjaxProfileController()
    {
        return 'profile.index';
    }

    public function getActivityFeedComment($aRow)
    {
        if (!isset($aRow['item_user_id'])) {
            return false;
        }

        if ($aRow['user_id'] == $aRow['item_user_id']) {
            $aItem = $this->database()->select(Phpfox::getUserField('u', 'parent_'))
                ->from(Phpfox::getT('user'), 'u')
                ->where('u.user_id = ' . (int)$aRow['item_user_id'])
                ->execute('getSlaveRow');
        } else {
            $aItem = $this->database()->select(Phpfox::getUserField('u', 'parent_'))
                ->from(Phpfox::getT('user'), 'u')
                ->where('u.user_id = ' . (int)$aRow['item_id'])
                ->execute('getSlaveRow');

            $aItem2 = $this->database()->select(Phpfox::getUserField('u', 'parent_'))
                ->from(Phpfox::getT('user'), 'u')
                ->where('u.user_id = ' . (int)$aRow['item_user_id'])
                ->execute('getSlaveRow');
        }

        if (empty($aItem['parent_user_id'])) {
            return false;
        }

        $sLink = Phpfox_Url::instance()->makeUrl($aItem['parent_user_name'], ['feed' => $aRow['feed_id']]);

        $aReturn = [
            'no_share'    => true,
            'feed_status' => htmlspecialchars($aRow['content']),
            'feed_link'   => $sLink,
            'feed_icon'   => Phpfox::getLib('image.helper')->display([
                'theme'      => 'misc/comment.png',
                'return_url' => true
            ]),
            'time_stamp'  => $aRow['time_stamp'],
            'enable_like' => false,
        ];

        if ($aRow['user_id'] != $aRow['item_user_id']) {
            $aReturn['parent_user'] = Phpfox::getService('user')->getUserFields(true, $aItem2, 'parent_');
        }

        $aReturn['force_user']['full_name'] = $aItem['parent_full_name'];
        $aReturn['force_user']['user_name'] = $aItem['parent_user_name'];
        $aReturn['force_user']['user_image'] = $aItem['parent_user_image'];
        $aReturn['force_user']['server_id'] = $aItem['user_parent_server_id'];

        return $aReturn;
    }

    public function getUploadParams()
    {
        return [
            'upload_dir' => Phpfox::getParam('core.dir_file_temp'),
            'upload_url' => Phpfox::getParam('core.url_file_temp'),
        ];
    }

    /**
     * If a call is made to an unknown method attempt to connect
     * it to a specific plug-in with the same name thus allowing
     * plug-in developers the ability to extend classes.
     *
     * @param string $sMethod    is the name of the method
     * @param array  $aArguments is the array of arguments of being passed
     *
     * @return null
     */
    public function __call($sMethod, $aArguments)
    {
        /**
         * Check if such a plug-in exists and if it does call it.
         */
        if ($sPlugin = Phpfox_Plugin::get('profile.service_callback___call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}
