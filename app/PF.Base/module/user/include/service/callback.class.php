<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Service_Callback
 */
class User_Service_Callback extends Phpfox_Service
{
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('user');
    }

    public function getNotificationInformation_Updated_By_Admin($notification)
    {
        if (empty($notification['item_id']) || $notification['item_id'] != Phpfox::getUserId()) {
            return false;
        }

        return [
            'message' => _p('full_name_updated_your_account_information',
                [
                    'full_name' => Phpfox::getService('notification')->getUsers($notification),
                ]),
            'link' => Phpfox::getLib('url')->makeUrl('user.setting'),
        ];
    }

    public function getUploadParamsRegistration()
    {
        $iMaxFileSize = Phpfox::getUserParam('user.max_upload_size_profile_photo');
        $iMaxFileSize = $iMaxFileSize > 0 ? $iMaxFileSize / 1024 : 0;
        $iMaxFileSize = Phpfox::getLib('file')->getLimit($iMaxFileSize);

        return [
            'max_size' => ($iMaxFileSize === 0 ? null : $iMaxFileSize),
            'type_list' => ['jpg', 'jpeg', 'gif', 'png'],
            'upload_dir' => Phpfox::getParam('core.dir_user'),
            'upload_path' => Phpfox::getParam('core.url_user'),
            'upload_now' => "true",
            'preview_template' => '',
            'label' => _p('profile_image'),
            'style' => 'mini',
            'is_required' => true,
            'force_upload' => true,
            'extra_data' => [
                'not-remove-file' => true
            ]
        ];
    }

    public function getUploadParamsCover()
    {
        $iMaxFileSize = (Phpfox::getUserParam('photo.photo_max_upload_size') === 0 ? null : (Phpfox::getUserParam('photo.photo_max_upload_size') / 1024));
        $iMaxFileSize = Phpfox::getLib('file')->getLimit($iMaxFileSize);

        $aParams = [
            'max_size' => ($iMaxFileSize === 0 ? null : $iMaxFileSize),
            'type_list' => ['jpg', 'gif', 'png'],
            'upload_dir'        => Phpfox::getParam('photo.dir_photo'),
            'upload_path'       => Phpfox::getParam('photo.url_photo'),
            'thumbnail_sizes' => [],
            'param_name' => 'image',
            'update_space' => false,
            'type' => 'user_cover'
        ];

        return $aParams;
    }

    public function approveProfilePhoto($photoId)
    {
        Phpfox::getService('user.process')->processAfterProfilePhotoApprovedFromPhotoApp($photoId);
    }

    public function approveCoverPhoto($photoId)
    {
        if (empty($photoId)) {
            return false;
        }

        $cacheKey = 'user_cover_photo_pending_'  .  $photoId;
        $cacheObject = storage()->get($cacheKey);
        $albumId = !empty($cacheObject->value->album_id) ? $cacheObject->value->album_id  : 0;
        $userId = !empty($cacheObject->value) ? $cacheObject->value->user_id : 0;
        storage()->del($cacheKey);

        if (empty($cacheObject) || empty($cacheObject->value) || empty($userId) || empty($albumId)) {
            return false;
        }

        if (Phpfox::isAppActive('Core_Photos')) {
            $photoTable = Phpfox::getT('photo');
            db()->update($photoTable, ['is_cover' => 0,], ['album_id' =>(int)$albumId]);
            $this->database()->update($photoTable, [
                'album_id'         => $albumId,
                'is_cover'         => 1,
                'is_cover_photo'   => 1,
                'is_profile_photo' => 0,
            ], ['photo_id' => (int)$photoId]);
            Phpfox::getService('photo.album.process')->updateCounter((int)$albumId, 'total_photo');
        }

        db()->update(Phpfox::getT('user_field'), ['cover_photo' => $photoId, 'cover_photo_top' => null], 'user_id = ' . (int)$userId);
        (Phpfox::isModule('feed') && Phpfox::getParam('photo.photo_allow_posting_user_photo_feed', 1) && Phpfox::getUserParam('photo.can_view_photos')
            ? Phpfox::getService('feed.process')->add('user_cover', $photoId, 0, 0, 0, (int)$userId) : null);

        if (Phpfox::isAppActive('Core_Activity_Points')) {
            Phpfox::getService('activitypoint.process')->updatePoints($userId, 'user_uploadcoverphoto');
        }

        $repositionCacheObject = storage()->get('photo_cover_reposition_' . $photoId);
        if (is_object($repositionCacheObject) && isset($repositionCacheObject->value) && $repositionCacheObject->value != '') {
            Phpfox::getService('user.process')->updateCoverPosition($repositionCacheObject->value, $userId);
            storage()->del('photo_cover_reposition_' . $photoId);
        }

        storage()->del('user/cover/' . $userId);
        storage()->set('user/cover/' . $userId, $photoId);
    }

    public function paymentApiCallback($aParams)
    {
        Phpfox::log('Module callback recieved: ' . var_export($aParams, true));

        $aRow = $this->database()->select('pp.*, ua.activity_points')->from(Phpfox::getT('point_purchase'),
            'pp')->join(Phpfox::getT('user_activity'), 'ua',
            'ua.user_id = pp.user_id')->where('pp.purchase_id = ' . (int)$aParams['item_number'])->execute('getSlaveRow');

        if (!isset($aRow['purchase_id'])) {
            Phpfox::log('Unable to find this purchase.');

            return false;
        }

        if ($aParams['status'] == 'completed') {
            $iNewTotal = (int)($aRow['activity_points'] + $aRow['total_point']);

            $this->database()->update(Phpfox::getT('point_purchase'), ['status' => '1'],
                'purchase_id = ' . (int)$aRow['purchase_id']);
            $this->database()->update(Phpfox::getT('user_activity'), ['activity_points	' => $iNewTotal],
                'user_id = ' . (int)$aRow['user_id']);

            Phpfox::log('Purchase completed. Giving the user #' . $aRow['user_id'] . ' ' . $iNewTotal . ' points.');

            if ($sPlugin = Phpfox_Plugin::get('user.service_callback_purchase_points_completed')) {
                eval($sPlugin);
            }

            return true;
        }

        Phpfox::log('Purchase was not paid.');

        return false;
    }

    public function getActivityFeedBirth($aRow)
    {
        $sLink = '';
        $aReturn = [
            'no_share' => true,
            'feed_title' => 'Born',
            'feed_link' => $sLink,
            'feed_icon' => Phpfox::getLib('image.helper')->display([
                'theme' => 'misc/application_add.png',
                'return_url' => true
            ]),
            'time_stamp' => $aRow['time_stamp'],
            'enable_like' => false
        ];

        return $aReturn;
    }

    public function getSiteStatsForAdmin($iStartTime, $iEndTime)
    {
        $aCond = [];
        $aCond[] = 'status_id = 0 AND view_id = 0';
        if ($iStartTime > 0) {
            $aCond[] = 'AND joined >= \'' . $this->database()->escape($iStartTime) . '\'';
        }
        if ($iEndTime > 0) {
            $aCond[] = 'AND joined <= \'' . $this->database()->escape($iEndTime) . '\'';
        }

        $iCnt = (int)$this->database()->select('COUNT(*)')->from($this->_sTable)->where($aCond)->execute('getSlaveField');

        $aCond = [];
        if ($iStartTime > 0) {
            $aCond[] = 'AND time_stamp >= \'' . $this->database()->escape($iStartTime) . '\'';
        }
        if ($iEndTime > 0) {
            $aCond[] = 'AND time_stamp <= \'' . $this->database()->escape($iEndTime) . '\'';
        }

        $iStatusCnt = (int)$this->database()->select('COUNT(*)')->from(Phpfox::getT('user_status'))->where($aCond)->execute('getSlaveField');

        return [
            [
                'phrase' => 'user.users',
                'total' => $iCnt
            ],
            [
                'phrase' => 'user.status_updates',
                'total' => $iStatusCnt
            ]
        ];
    }

    public function getNotificationGiftPoint($aRow)
    {
        $link = Phpfox::getLib('url')->makeUrl('activitypoint');
        if (!Phpfox::isAppActive('Core_Activity_Points')) {
            $link = Phpfox::getLib('url')->makeUrl($aRow['user_name']);
        }
        return [
            'message' => _p('notification_gift_point', [
                'fullname' => Phpfox::getService('notification')->getUsers($aRow),
                'points' => $aRow['item_id']
            ]),
            'link' => $link
        ];
    }

    public function getNotificationTaggedStatus($aRow)
    {
        return [
            'message' => _p('user_name_tagged_you_in_a_status_update',
                [
                    'user_name' => Phpfox::getService('notification')->getUsers($aRow),
                ]),
            'link' => Phpfox::getLib('url')->makeUrl($aRow['user_name'], ['status-id' => $aRow['item_id']])
        ];
    }

    public function getNotificationTaggedComment($aRow)
    {
        return [
            'message' => _p('user_name_tagged_you_in_a_comment', ['user_name' => Phpfox::getService('notification')->getUsers($aRow)]),
            'link' => Phpfox::getLib('url')->makeUrl('comment.view', [$aRow['item_id']])
        ];
    }

    public function getCommentNotificationStatustag($aRow)
    {
        return [
            'message' => _p('user_name_tagged_you_in_a_comment', ['user_name' => Phpfox::getService('notification')->getUsers($aRow)]),
            'link' => Phpfox::getLib('url')->makeUrl('comment.view', [$aRow['item_id']])
        ];
    }

    public function massAdmincpProductDelete($sProduct)
    {
        $this->database()->delete(Phpfox::getT('user_group_setting'),
            "product_id = '" . $this->database()->escape($sProduct) . "'");
    }

    public function deleteCommentStatus($iId)
    {
        $this->database()->updateCounter('user_status', 'total_comment', 'status_id', $iId, true);
    }

    public function massAdmincpModuleDelete($iModule)
    {
        $this->database()->delete(Phpfox::getT('user_group_setting'),
            "module_id = '" . $this->database()->escape($iModule) . "'");
        $this->database()->delete(Phpfox::getT('user_group_custom'),
            "module_id = '" . $this->database()->escape($iModule) . "'");
    }

    public function globalSearch($sQuery, $bIsTagSearch = false)
    {
        if ($bIsTagSearch === true) {
            return null;
        }

        $sKeywordSearch = '(u.full_name LIKE \'%' . Phpfox_Database::instance()->escape($sQuery) . '%\' OR (u.email LIKE \'%' . Phpfox_Database::instance()->escape($sQuery) . '@%\' OR u.email = \'' . Phpfox_Database::instance()->escape($sQuery) . '\'))';

        $iCnt = $this->database()->select('COUNT(*)')->from($this->_sTable,
            'u')->where($sKeywordSearch)->execute('getSlaveField');

        $aUsers = $this->database()->select('u.joined, ' . Phpfox::getUserField())->from($this->_sTable,
            'u')->where($sKeywordSearch)->limit(10)->order('u.joined DESC')->execute('getSlaveRows');

        if (count($aUsers)) {
            $aResults = [];
            $aResults['total'] = $iCnt;
            $aResults['menu'] = _p('members');
            $aResults['form'] = '<form method="post" action="' . Phpfox::getLib('url')->makeUrl('user.browse') . '"><div><input type="hidden" name="' . Phpfox::getTokenName() . '[security_token]" value="' . Phpfox::getService('log.session')->getToken() . '" /></div><div><input name="search[keyword]" value="' . Phpfox::getLib('parse.output')->clean($sQuery) . '" size="20" type="hidden" /></div><div><input type="hidden" name="search[type]" value="2" /></div><div><input type="submit" value="' . _p('view_more_members') . '" class="search_button" /></div></form>';
            foreach ($aUsers as $iKey => $aUser) {
                $aResults['results'][$iKey] = [
                    'link' => Phpfox::getLib('url')->makeUrl($aUser['user_name']),
                    'title' => $aUser['full_name'],
                    'image' => Phpfox::getLib('image.helper')->display([
                        'user' => $aUser,
                        'suffix' => '_50',
                        'max_width' => 75,
                        'max_height' => 75
                    ]),
                    'extra_info' => _p('a_href_link_member_a_joined_joined', [
                        'link' => Phpfox::getLib('url')->makeUrl('user.browse'),
                        'joined' => Phpfox::getTime(Phpfox::getParam('core.global_update_time'), $aUser['joined'])
                    ])
                ];
            }

            return $aResults;
        }

        return null;
    }

    public function getNewsFeedStatus($aRow)
    {
        if ($sPlugin = Phpfox_Plugin::get('user.service_callback_getnewsfeedstatus_start')) {
            eval($sPlugin);
        }
        $oParseOutput = Phpfox::getLib('parse.output');

        $aRow['text'] = '<a href="' . Phpfox::getLib('url')->makeUrl($aRow['owner_user_name']) . '">' . $aRow['owner_full_name'] . '</a> ' . Phpfox::getService('feed')->shortenText($oParseOutput->clean($aRow['content'])) . '';
        $aRow['icon'] = 'misc/user_feed.png';
        $aRow['enable_like'] = true;

        return $aRow;
    }

    public function getNewsFeedPhoto($aRow)
    {
        if ($sPlugin = Phpfox_Plugin::get('user.service_callback_getnewsfeedphoto_start')) {
            eval($sPlugin);
        }

        $aRow['text'] = _p('a_href_link_full_name_a_updated_their_profile_picture', [
            'link' => Phpfox::getLib('url')->makeUrl($aRow['owner_user_name']),
            'full_name' => $aRow['owner_full_name']
        ]);

        if (defined('PHPFOX_IS_USER_PROFILE')) {
            $aImage = unserialize($aRow['content']);
            $sImage = Phpfox::getLib('image.helper')->display([
                'server_id' => $aImage['server_id'],
                'path' => 'core.url_user',
                'file' => $aImage['destination'],
                'suffix' => '_50',
                'max_width' => 75,
                'max_height' => 75,
                'style' => 'vertical-align:top; padding-right:5px;'
            ]);
            $aRow['text'] .= '<div class="p_4"><a href="' . Phpfox::getLib('url')->makeUrl($aRow['owner_user_name']) . '">' . $sImage . '</a></div>';
        }

        $aRow['icon'] = 'misc/profile_photo.png';
        $aRow['enable_like'] = true;

        return $aRow;
    }

    public function getNewsFeedJoined($aRow, $iUserId = null)
    {
        if ($sPlugin = Phpfox_Plugin::get('user.service_callback_getnewsfeedjoined_start')) {
            eval($sPlugin);
        }
        $aRow['text'] = _p('a_href_link_full_name_a_joined_the_community', [
            'link' => Phpfox::getLib('url')->makeUrl($aRow['owner_user_name']),
            'full_name' => $aRow['owner_full_name']
        ]);

        $aRow['icon'] = 'misc/user_add.png';
        $aRow['enable_like'] = true;

        return $aRow;
    }

    public function getCustomFieldLocations()
    {
        return [
            'user_main' => _p('users_profile_main_section'),
            'user_panel' => _p('users_profile_basic_information')
        ];
    }

    public function getCustomGroups()
    {
        return [
            'user_profile' => _p('users_profile')
        ];
    }

    public function getReportRedirect($iUserId)
    {
        $aUser = $this->database()->select('user_id, user_name')->from(Phpfox::getT('user'))->where('user_id = ' . (int)$iUserId)->execute('getSlaveRow');

        if (!isset($aUser['user_id'])) {
            return false;
        }

        return Phpfox::getLib('url')->makeUrl($aUser['user_name']);
    }

    public function getReportRedirectStatus($iStatusId)
    {
        $aUser = $this->database()->select('u.user_name')->from(Phpfox::getT('user_status'),
            'us')->join(Phpfox::getT('user'), 'u',
            'u.user_id = us.user_id')->where('us.status_id = ' . (int)$iStatusId)->execute('getSlaveRow');

        if (!isset($aUser['user_name'])) {
            return false;
        }

        return Phpfox::getLib('url')->makeUrl($aUser['user_name'], ['status-id' => $iStatusId]);
    }

    public function getRatingData($iId)
    {
        return [
            'field' => 'user_id',
            'table' => 'user_field',
            'table_rating' => 'user_rating'
        ];
    }

    public function verifyFavorite($iItemId)
    {
        $aItem = $this->database()->select('i.user_id')->from($this->_sTable,
            'i')->where('i.user_id = ' . (int)$iItemId)->execute('getSlaveRow');

        if (!isset($aItem['user_id'])) {
            return false;
        }

        return true;
    }

    public function getFavorite($aFavorites)
    {
        $aItems = $this->database()->select('u.full_name AS title, u.joined AS time_stamp, ' . Phpfox::getUserField())->from($this->_sTable,
            'u')->where('u.user_id IN(' . implode(',', $aFavorites) . ')')->execute('getSlaveRows');

        foreach ($aItems as $iKey => $aItem) {
            $aItems[$iKey]['image'] = Phpfox::getLib('image.helper')->display([
                'user' => $aItem,
                'suffix' => '_50',
                'max_width' => 75,
                'max_height' => 75,
            ]);

            $aItems[$iKey]['link'] = Phpfox::getLib('url')->makeUrl($aItem['user_name']);
            $aItems[$iKey]['extra_info'] = _p('joined_time_stamp', [
                'time_stamp' => Phpfox::getTime(Phpfox::getParam('core.global_update_time'), $aItem['time_stamp'])
            ]);
        }

        return [
            'title' => _p('members'),
            'items' => $aItems
        ];
    }

    /**
     * Action to take when user cancelled their account
     * @param int $iUser
     */
    public function onDeleteUser($iUser)
    {
        Phpfox::getService('user.block.process')->delete($iUser);
        // delete featured entries
        $this->database()->delete(Phpfox::getT('user_featured'), 'user_id = ' . (int)$iUser);
        Phpfox::getService('user.process')->removeProfilePic($iUser);

        $this->database()->delete(Phpfox::getT('user'), 'user_id = ' . (int)$iUser);
        $this->database()->delete(Phpfox::getT('user_activity'), 'user_id = ' . (int)$iUser);
        $this->database()->delete(Phpfox::getT('user_count'), 'user_id = ' . (int)$iUser);
        $this->database()->delete(Phpfox::getT('user_css'), 'user_id = ' . (int)$iUser);
        $this->database()->delete(Phpfox::getT('user_css_code'), 'user_id = ' . (int)$iUser);
        $this->database()->delete(Phpfox::getT('user_custom'), 'user_id = ' . (int)$iUser);
        $this->database()->delete(Phpfox::getT('user_custom_value'), 'user_id = ' . (int)$iUser);
        $this->database()->delete(Phpfox::getT('user_design_order'), 'user_id = ' . (int)$iUser);
        $this->database()->delete(Phpfox::getT('user_notification'), 'user_id = ' . (int)$iUser);
        $this->database()->delete(Phpfox::getT('user_space'), 'user_id = ' . (int)$iUser);
        $this->database()->delete(Phpfox::getT('track'), 'user_id = ' . (int)$iUser . ' AND type_id="user"');
        $this->database()->delete(Phpfox::getT('user_field'), 'user_id = ' . (int)$iUser);
        $this->database()->delete(Phpfox::getT('user_verify'), 'user_id = ' . (int)$iUser);
        $this->database()->delete(Phpfox::getT('user_privacy'), 'user_id = ' . (int)$iUser);
        $this->database()->delete(Phpfox::getT('user_gateway'), 'user_id = ' . (int)$iUser);
        $this->database()->delete(Phpfox::getT('user_status'), 'user_id = ' . (int)$iUser);
        $this->database()->delete(Phpfox::getT('user_blocked'), 'user_id = ' . (int)$iUser . ' OR block_user_id = '. (int)$iUser);

        $sFilename = $this->database()->select('file_name')
            ->from(':cache')
            ->where('cache_data LIKE \'%"user_id":' . (int)$iUser.',%\' AND file_name LIKE \'google_users_%\'')
            ->executeField();
        if ($sFilename) {
            storage()->del($sFilename);
            storage()->del('fb_new_users_' . (int)$iUser);
            storage()->del('google_force_email_' . (int)$iUser);
            storage()->del('google_user_notice_' . (int)$iUser);
        }
    }

    public function exportModule($sProductId, $sModule = null)
    {
        $iCnt = 0;
        $aSql = [];
        $aSql[] = "product_id = '" . $sProductId . "'";
        if ($sModule !== null) {
            $aSql[] = "AND module_id = '" . $sModule . "'";
        }

        $aRows = $this->database()->select('*')->from(Phpfox::getT('user_delete'))->where($aSql)->execute('getSlaveRows');

        if (count($aRows)) {
            $iCnt++;
            $oXmlBuilder = Phpfox::getLib('xml.builder');
            $oXmlBuilder->addGroup('user_delete');

            foreach ($aRows as $aRow) {
                $oXmlBuilder->addTag('option', '', [
                    'module_id' => $aRow['module_id'],
                    'phrase_var' => $aRow['phrase_var']
                ]);
            }
            $oXmlBuilder->closeGroup();
        }

        (Phpfox::getService('user.group.setting')->export($sProductId, $sModule) ? $iCnt++ : null);

        return ($iCnt ? true : false);
    }

    public function installModule($sProduct, $sModule, $aModule)
    {
        if (isset($aModule['user_delete'])) {
            $aRows = (isset($aModule['user_delete']['option'][1]) ? $aModule['user_delete']['option'] : [$aModule['user_delete']['option']]);
            foreach ($aRows as $aRow) {
                $this->database()->insert(Phpfox::getT('user_delete'), [
                    'module_id' => ($sModule === null ? $aRow['module_id'] : $sModule),
                    'product_id' => $sProduct,
                    'phrase_var' => $aRow['phrase_var']
                ]);
            }
        }
    }

    public function spamCheck()
    {
        return [
            'phrase' => _p('users'),
            'value' => Phpfox::getService('user')->getSpamTotal(),
            'link' => Phpfox::getLib('url')->makeUrl('admincp.user.browse', ['view' => 'spam'])
        ];
    }

    public function legacyRedirect($aRequest)
    {
        if (isset($aRequest['mode'])) {
            switch ($aRequest['mode']) {
                case 'online':
                    return ['user.browse', ['view' => 'online']];
                    break;
                case 'featured':
                    return ['user.browse', ['view' => 'featured']];
                    break;
            }
        }

        if (isset($aRequest['req2'])) {
            switch ($aRequest['req2']) {
                case 'gallery':
                    return [$aRequest['name'], 'photo'];
                    break;
                case 'blogs':
                    return [$aRequest['name'], 'blog'];
                    break;
            }
        }

        if (isset($aRequest['name'])) {
            return $aRequest['name'];
        }

        return 'user.browse';
    }

    public function ipSearch($sSearch)
    {
        $aRows = $this->database()->select('uip.*, ' . Phpfox::getUserField())->from(Phpfox::getT('user_ip'),
            'uip')->join(Phpfox::getT('user'), 'u',
            'u.user_id = uip.user_id')->where('uip.ip_address = \'' . $this->database()->escape($sSearch) . '\'')->order('uip.time_stamp DESC')->limit(50)->execute('getSlaveRows');

        $aResults = [];
        foreach ($aRows as $aRow) {
            $aResults[] = [
                $aRow['full_name'],
                _p($aRow['type_id']),
                Phpfox::getTime(Phpfox::getParam('feed.feed_display_time_stamp'), $aRow['time_stamp'])
            ];
        }

        return [
            'table' => _p('user_activity'),
            'th' => [
                _p('name'),
                _p('type'),
                _p('time_stamp')
            ],
            'results' => $aResults
        ];
    }

    public function removeDuplicateList()
    {
        return [
            'name' => _p('user_group_settings'),
            'key' => 'setting_id',
            'table' => 'user_group_setting',
            'search' => [
                'module_id',
                'name'
            ]
        ];
    }

    public function getAlertItem()
    {
        return [
            'phrase' => _p('users_pending_approval'),
            'value' => $this->database()->select('COUNT(*)')->from(Phpfox::getT('user'))->where('view_id = 1')->execute('getSlaveField'),
            'link' => Phpfox::getLib('url')->makeUrl('admincp.user.browse', ['view' => 'pending'])
        ];
    }

    public function pendingApproval()
    {
        return [
            'phrase' => _p('users_pending_approval'),
            'value' => $this->getPendingTotal(),
            'link' => Phpfox::getLib('url')->makeUrl('admincp.user.browse', ['view' => 'pending'])
        ];
    }

    public function getPendingTotal()
    {
        return $this->database()->select('COUNT(*)')->from(Phpfox::getT('user'))->where('view_id = 1')->execute('getSlaveField');
    }

    public function getAdmincpAlertItems()
    {
        $iTotalPending = $this->getPendingTotal();
        return [
            'message' => _p('you_have_total_pending_users', ['total' => $iTotalPending]),
            'value' => $iTotalPending,
            'link' => Phpfox::getLib('url')->makeUrl('admincp.user.browse', ['view' => 'pending'])
        ];
    }

    public function getSiteStatsForAdmins()
    {
        $iToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));

        return [
            'phrase' => _p('members'),
            'value' => $this->database()->select('COUNT(*)')->from(Phpfox::getT('user'))->where('status_id = 0 AND view_id = 0 AND joined >= ' . $iToday)->execute('getSlaveField')
        ];
    }

    public function getNewsFeedJoined_FeedLike($aRow, $iUserId = null)
    {
        if ($aRow['owner_user_id'] == $aRow['viewer_user_id']) {
            $aRow['text'] = _p('a_href_user_link_full_name_a_liked_that_they_joined_the_community', [
                'full_name' => Phpfox::getLib('parse.output')->clean($aRow['owner_full_name']),
                'user_link' => Phpfox::getLib('url')->makeUrl($aRow['owner_user_name']),
                'link' => Phpfox::getLib('url')->makeUrl($aRow['viewer_user_name'], ['feed' => $aRow['item_id']])
            ]);
        } else {
            $aRow['text'] = _p('a_href_user_link_full_name_a_liked_that_a_href_view_user_link_view_full_name_a_a_href_link_joined_a_the_community',
                [
                    'full_name' => Phpfox::getLib('parse.output')->clean($aRow['owner_full_name']),
                    'user_link' => Phpfox::getLib('url')->makeUrl($aRow['owner_user_name']),
                    'view_full_name' => Phpfox::getLib('parse.output')->clean($aRow['viewer_full_name']),
                    'view_user_link' => Phpfox::getLib('url')->makeUrl($aRow['viewer_user_name']),
                    'link' => Phpfox::getLib('url')->makeUrl($aRow['viewer_user_name'],
                        ['feed' => $aRow['item_id']])
                ]);
        }

        $aRow['icon'] = 'misc/thumb_up.png';

        return $aRow;
    }

    public function getNotificationFeedJoined_NotifyLike($aRow)
    {
        return [
            'message' => _p('a_href_user_link_full_name_a_liked_that_you_joined_the_community', [
                'full_name' => Phpfox::getLib('parse.output')->clean($aRow['full_name']),
                'user_link' => Phpfox::getLib('url')->makeUrl($aRow['user_name'], ['welcome' => 'me'])
            ]),
            'link' => Phpfox::getLib('url')->makeUrl($aRow['user_name'], ['welcome' => 'me'])
        ];
    }

    public function sendLikeEmailJoined($iItemId)
    {
        return _p('a_href_user_link_full_name_a_liked_that_you_joined_the_community', [
            'full_name' => Phpfox::getLib('parse.output')->clean(Phpfox::getUserBy('full_name')),
            'user_link' => Phpfox::getLib('url')->makeUrl(Phpfox::getUserBy('user_name'), ['welcome' => 'me'])
        ]);
    }

    public function getNewsFeedStatus_FeedLike($aRow)
    {
        if ($aRow['owner_user_id'] == $aRow['viewer_user_id']) {
            $aRow['text'] = _p('a_href_user_link_full_name_a_likes_their_own_a_href_link_status_a', [
                'full_name' => Phpfox::getLib('parse.output')->clean($aRow['owner_full_name']),
                'user_link' => Phpfox::getLib('url')->makeUrl($aRow['owner_user_name']),
                'gender' => Phpfox::getService('user')->gender($aRow['owner_gender'], 1),
                'link' => Phpfox::getLib('url')->makeUrl($aRow['viewer_user_name'],
                    ['feed' => $aRow['item_id'], 'flike' => 'status'])
            ]);
        } else {
            $aRow['text'] = _p('a_href_user_link_full_name_a_likes_a_href_view_user_link_view_full_name_a_s_a_href_link_status_a',
                [
                    'full_name' => Phpfox::getLib('parse.output')->clean($aRow['owner_full_name']),
                    'user_link' => Phpfox::getLib('url')->makeUrl($aRow['owner_user_name']),
                    'view_full_name' => Phpfox::getLib('parse.output')->clean($aRow['viewer_full_name']),
                    'view_user_link' => Phpfox::getLib('url')->makeUrl($aRow['viewer_user_name']),
                    'link' => Phpfox::getLib('url')->makeUrl($aRow['viewer_user_name'],
                        ['feed' => $aRow['item_id'], 'flike' => 'status'])
                ]);
        }

        $aRow['icon'] = 'misc/thumb_up.png';

        return $aRow;
    }

    public function getNotificationFeedStatus_NotifyLike($aRow)
    {
        return [
            'message' => _p('a_href_user_link_full_name_a_likes_your_a_href_link_status_a', [
                'full_name' => Phpfox::getLib('parse.output')->clean($aRow['full_name']),
                'user_link' => Phpfox::getLib('url')->makeUrl($aRow['user_name']),
                'link' => Phpfox::getLib('url')->makeUrl(Phpfox::getUserBy('user_name'),
                    ['feed' => $aRow['item_id'], 'flike' => 'status'])
            ]),
            'link' => Phpfox::getLib('url')->makeUrl(Phpfox::getUserBy('user_name'),
                ['feed' => $aRow['item_id'], 'flike' => 'status'])
        ];
    }

    public function sendLikeEmailStatus($iItemId, $aFeed)
    {
        return _p('a_href_user_link_full_name_a_likes_your_a_href_link_status_a', [
            'full_name' => Phpfox::getLib('parse.output')->clean(Phpfox::getUserBy('full_name')),
            'user_link' => Phpfox::getLib('url')->makeUrl(Phpfox::getUserBy('user_name')),
            'link' => Phpfox::getLib('url')->makeUrl($aFeed['user_name'],
                ['feed' => $aFeed['feed_id'], 'flike' => 'status'])
        ]);
    }

    public function getNewsFeedPhoto_FeedLike($aRow)
    {
        if ($aRow['owner_user_id'] == $aRow['viewer_user_id']) {
            $aRow['text'] = _p('a_href_user_link_full_name_a_likes_their_own_profile_a_href_link_photo_a', [
                'full_name' => Phpfox::getLib('parse.output')->clean($aRow['owner_full_name']),
                'user_link' => Phpfox::getLib('url')->makeUrl($aRow['owner_user_name']),
                'gender' => Phpfox::getService('user')->gender($aRow['owner_gender'], 1),
                'link' => Phpfox::getLib('url')->makeUrl($aRow['viewer_user_name'],
                    ['feed' => $aRow['item_id'], 'flike' => 'photo'])
            ]);
        } else {
            $aRow['text'] = _p('a_href_user_link_full_name_a_likes_a_href_view_user_link_view_full_name_a_s_profile_a_href_link_photo_a',
                [
                    'full_name' => Phpfox::getLib('parse.output')->clean($aRow['owner_full_name']),
                    'user_link' => Phpfox::getLib('url')->makeUrl($aRow['owner_user_name']),
                    'view_full_name' => Phpfox::getLib('parse.output')->clean($aRow['viewer_full_name']),
                    'view_user_link' => Phpfox::getLib('url')->makeUrl($aRow['viewer_user_name']),
                    'link' => Phpfox::getLib('url')->makeUrl($aRow['viewer_user_name'],
                        ['feed' => $aRow['item_id'], 'flike' => 'photo'])
                ]);
        }

        $aRow['icon'] = 'misc/thumb_up.png';

        return $aRow;
    }

    public function getNotificationFeedPhoto_NotifyLike($aRow)
    {
        return [
            'message' => _p('a_href_user_link_full_name_a_likes_your_profile_a_href_link_photo_a', [
                'full_name' => Phpfox::getLib('parse.output')->clean($aRow['full_name']),
                'user_link' => Phpfox::getLib('url')->makeUrl($aRow['user_name']),
                'link' => Phpfox::getLib('url')->makeUrl(Phpfox::getUserBy('user_name'),
                    ['feed' => $aRow['item_id'], 'flike' => 'photo'])
            ]),
            'link' => Phpfox::getLib('url')->makeUrl(Phpfox::getUserBy('user_name'),
                ['feed' => $aRow['item_id'], 'flike' => 'photo'])
        ];
    }

    public function sendLikeEmailPhoto($iItemId, $aFeed)
    {
        return _p('a_href_user_link_full_name_a_likes_your_profile_a_href_link_photo_a', [
            'full_name' => Phpfox::getLib('parse.output')->clean(Phpfox::getUserBy('full_name')),
            'user_link' => Phpfox::getLib('url')->makeUrl(Phpfox::getUserBy('user_name')),
            'link' => Phpfox::getLib('url')->makeUrl($aFeed['user_name'],
                ['feed' => $aFeed['feed_id'], 'flike' => 'photo'])
        ]);
    }

    public function updateCounterList()
    {
        $aList = [];

        $aList[] = [
            'name' => _p('update_user_photos'),
            'id' => 'update-user-photo'
        ];

        return $aList;
    }

    public function updateCounter($iId, $iPage, $iPageLimit)
    {
        $iCnt = $this->database()->select('COUNT(*)')->from(Phpfox::getT('user'))->where($this->database()->isNotNull('user_image'))->execute('getSlaveField');

        $aRows = $this->database()->select('user_id, user_image')->from(Phpfox::getT('user'))->where($this->database()->isNotNull('user_image'))->limit($iPage,
            $iPageLimit, $iCnt)->execute('getSlaveRows');

        foreach ($aRows as $aRow) {
            if (preg_match("/\{file\/pic\/(.*)\/(.*)\.jpg\}/i", $aRow['user_image'], $aMatches)) {
                $sPath = PHPFOX_DIR;
                $sImagePath = str_replace(['{', '}'], '', $aRow['user_image']);
            } else {
                $sPath = Phpfox::getParam('core.dir_user');
                $sImagePath = $aRow['user_image'];
            }

            if (file_exists($sPath . sprintf($sImagePath, ''))) {
                foreach (Phpfox::getService('user')->getUserThumbnailSizes() as $iSize) {
                    if (!file_exists($sPath . sprintf($sImagePath, '_' . $iSize))) {
                        Phpfox_Image::instance()->createThumbnail($sPath . sprintf($sImagePath, ''),
                            $sPath . sprintf($sImagePath, '_' . $iSize), $iSize, $iSize);
                    }

                    if (!file_exists($sPath . sprintf($sImagePath, '_' . $iSize . '_square'))) {
                        Phpfox_Image::instance()->createThumbnail($sPath . sprintf($sImagePath, ''),
                            $sPath . sprintf($sImagePath, '_' . $iSize . '_square'), $iSize, $iSize, true);
                    }
                }
            }
        }

        return $iCnt;
    }

    public function getFeedRedirectStatus($iId)
    {
        return $this->getReportRedirect($iId) . 'feed_' . Phpfox_Request::instance()->get('id') . '/#feed';
    }

    public function getActivityFeedPhoto($aItem, $aCallback = null, $bIsChildItem = false)
    {
        $sSelect = 'p.*, p.server_id AS photo_server_id';
        if (Phpfox::isModule('like')) {
            $sSelect .= ', count(l.like_id) as total_like';
            $this->database()->leftJoin(Phpfox::getT('like'), 'l',
                'l.type_id = \'photo\' AND l.item_id = p.photo_id');

            $this->database()->group('p.photo_id');

            $sSelect .= ', l2.like_id AS is_liked';
            $this->database()->leftJoin(Phpfox::getT('like'), 'l2',
                'l2.type_id = \'photo\' AND l2.item_id = p.photo_id AND l2.user_id = ' . Phpfox::getUserId());
        }
        $aRow = $this->database()->select($sSelect . ' , p.destination, u.user_image, u.server_id')
            ->from(Phpfox::getT('photo'), 'p')
            ->join(':user', 'u', 'u.user_id=p.user_id')
            ->where([
                'p.photo_id' => (int)$aItem['item_id'],
                'p.is_profile_photo' => 1
            ])->execute('getSlaveRow');

        if (empty($aRow)) {
            return false;
        }

        // current profile image
        $iCurrentProfileImageId = storage()->get("user/avatar/$aItem[user_id]");
        if (!is_null($iCurrentProfileImageId)) {
            $iCurrentProfileImageId = $iCurrentProfileImageId->value;
            if ($iCurrentProfileImageId == $aItem['item_id']) {
                $sImage = Phpfox::getLib('image.helper')->display([
                    'server_id' => $aRow['server_id'],
                    'path' => 'core.url_user',
                    'file' => $aRow['user_image'],
                    'suffix' => '_200_square',
                    'class' => 'photo_holder',
                    'defer' => true
                ]);
            }
        }

        if (!isset($sImage)) {
            $sImage = Phpfox::getLib('image.helper')->display([
                'server_id' => $aRow['photo_server_id'],
                'path' => 'photo.url_photo',
                'file' => $aRow['destination'],
                'suffix' => '_240',
                'class' => 'photo_holder',
                'defer' => true
            ]);
        }

        $aReturn = [
            'feed_title' => '',
            'feed_info' => _p('updated_gender_profile_photo',
                ['gender' => Phpfox::getService('user')->gender($aItem['gender'], 1)]),
            'feed_link' => Phpfox::getLib('url')->permalink('photo', $aRow['photo_id'], Phpfox::getParam('photo.photo_show_title', 1) ? $aRow['title'] : null),
            'feed_image' => $sImage,
            'feed_icon' => Phpfox::getLib('image.helper')->display([
                'theme' => 'misc/report_user.png',
                'return_url' => true
            ]),
            'time_stamp' => $aItem['time_stamp'],
            'feed_total_like' => $aRow['total_like'],
            'like_type_id' => 'photo',
            'enable_like' => true,
            'feed_is_liked' => isset($aRow['is_liked']) ? $aRow['is_liked'] : false,
            'total_comment' => $aRow['total_comment'],
            'comment_type_id' => 'photo',
            'no_target_blank' => true,
        ];

        if ($bIsChildItem) {
            $aReturn = array_merge($aReturn, $aItem);
        }

        return $aReturn;
    }

    public function getActivityFeedCover($aItem, $aCallback = null, $bIsChildItem = false)
    {
        $sSelect = 'p.*';
        if (Phpfox::isModule('like')) {
            $sSelect .= ', count(l.like_id) as total_like';
            $this->database()->leftJoin(Phpfox::getT('like'), 'l',
                'l.type_id = \'photo\' AND l.item_id = p.photo_id');
            $this->database()->group('p.photo_id');

            $sSelect .= ', l2.like_id AS is_liked';
            $this->database()->leftJoin(Phpfox::getT('like'), 'l2',
                'l2.type_id = \'photo\' AND l2.item_id = p.photo_id AND l2.user_id = ' . Phpfox::getUserId());
        }
        $aRow = $this->database()->select($sSelect)->from(Phpfox::getT('photo'), 'p')->where([
            'p.photo_id' => (int)$aItem['item_id'],
            'p.is_cover_photo' => 1
        ])->execute('getSlaveRow');
        if (empty($aRow)) {
            return false;
        }
        $sImage = Phpfox::getLib('image.helper')->display([
            'server_id' => $aRow['server_id'],
            'path' => 'photo.url_photo',
            'file' => Phpfox::getService('photo')->getPhotoUrl(array_merge($aRow,
                ['full_name' => $aItem['full_name']])),
            'suffix' => '_1024',
            'class' => 'photo_holder',
            'defer' => true
        ]);
        $aReturn = [
            'feed_title' => '',
            'feed_info' => _p('updated_gender_cover_photo',
                ['gender' => Phpfox::getService('user')->gender($aItem['gender'], 1)]),
            'feed_link' => Phpfox::getLib('url')->permalink('photo', $aRow['photo_id'], Phpfox::getParam('photo.photo_show_title', 1) ? $aRow['title'] : null),
            'feed_image' => $sImage,
            'feed_icon' => Phpfox::getLib('image.helper')->display([
                'theme' => 'misc/report_user.png',
                'return_url' => true
            ]),
            'time_stamp' => $aItem['time_stamp'],
            'feed_total_like' => $aRow['total_like'],
            'like_type_id' => 'photo',
            'enable_like' => true,
            'feed_is_liked' => isset($aRow['is_liked']) ? $aRow['is_liked'] : false,
            'total_comment' => $aRow['total_comment'],
            'comment_type_id' => 'photo',
            'no_target_blank' => true
        ];

        if ($bIsChildItem) {
            $aReturn = array_merge($aReturn, $aItem);
        }

        return $aReturn;
    }

    public function getActivityFeedStatus($aItem, $aCallBack = null, $bIsChildItem = false)
    {
        //Check in case the feed with tagging user is private
        if (!empty($aItem['parent_user_id'])
            && $aItem['parent_user_id'] == Phpfox::getUserId()
            && $aItem['user_id'] != $aItem['parent_user_id']
            && Phpfox::isModule('privacy')
            && !Phpfox::getService('privacy')->check($aItem['type_id'], $aItem['item_id'], $aItem['user_id'], $aItem['privacy'], null, true)) {
            return false;
        }

        $sSelect = 'us.*';
        if (Phpfox::isModule('like')) {
            $sSelect .= ', l.like_id AS is_liked';
            $this->database()->leftJoin(Phpfox::getT('like'), 'l',
                'l.type_id = \'user_status\' AND l.item_id = us.status_id AND l.user_id = ' . Phpfox::getUserId());
        }
        $aRow = $this->database()->select($sSelect)->from(Phpfox::getT('user_status'), 'us')->where('us.status_id = ' . (int)$aItem['item_id'])->execute('getSlaveRow');

        if (empty($aRow) || empty($aItem['user_name'])) {
            return false;
        }

        $sLink = Phpfox::getLib('url')->makeUrl($aItem['user_name'], ['status-id' => $aRow['status_id']]);

        $aReturn = [
            'feed_status' => htmlspecialchars($aRow['content']),
            'feed_title' => '',
            'feed_link' => $sLink,
            'total_comment' => $aRow['total_comment'],
            'feed_total_like' => $aRow['total_like'],
            'feed_is_liked' => isset($aRow['is_liked']) ? $aRow['is_liked'] : false,
            'feed_icon' => Phpfox::getLib('image.helper')->display([
                'theme' => 'misc/application_add.png',
                'return_url' => true
            ]),
            'time_stamp' => $aRow['time_stamp'],
            'enable_like' => true,
            'comment_type_id' => 'user_status',
            'like_type_id' => 'user_status'
        ];

        if (!empty($aRow['location_name'])) {
            $aReturn['location_name'] = $aRow['location_name'];
        }
        if (!empty($aRow['location_latlng'])) {
            $aReturn['location_latlng'] = json_decode($aRow['location_latlng'], true);
        }

        // get tagged users
        $aReturn['total_friends_tagged'] = Phpfox::getService('feed.tag')->getTaggedUsers($aItem['item_id'], 'user_status', true);
        if ($aReturn['total_friends_tagged']) {
            $aReturn['friends_tagged'] = Phpfox::getService('feed.tag')->getTaggedUsers($aItem['item_id'], 'user_status', false, 1, 2);
        }

        if ($bIsChildItem) {
            $aReturn = array_merge($aReturn, $aItem);
        }

        return $aReturn;
    }

    public function deleteLikeStatus($iItemId)
    {
        $this->database()->updateCount('like', 'type_id = \'user_status\' AND item_id = ' . (int)$iItemId . '',
            'total_like', 'user_status', 'status_id = ' . (int)$iItemId);
    }

    public function getAjaxCommentVarStatus()
    {
        return null;
    }

    public function addLikeStatus($iItemId, $bDoNotSendEmail = false)
    {
        $aRow = $this->database()->select('us.status_id, us.content, us.user_id, u.user_name, u.full_name')
            ->from(Phpfox::getT('user_status'), 'us')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = us.user_id')
            ->where('us.status_id = ' . (int)$iItemId)
            ->execute('getSlaveRow');

        if (!isset($aRow['status_id'])) {
            return false;
        }
        $this->database()->updateCount('like', 'type_id = \'user_status\' AND item_id = ' . (int)$iItemId . '', 'total_like', 'user_status', 'status_id = ' . (int)$iItemId);
        if (!$bDoNotSendEmail) {
            $sLink = Phpfox::getLib('url')->makeUrl($aRow['user_name'], ['status-id' => $aRow['status_id']]);
            Phpfox::getLib('mail')->to($aRow['user_id'])->subject([
                'user.full_name_liked_your_status_update_content',
                [
                    'full_name' => Phpfox::getUserBy('full_name')
                ]
            ])->message([
                'user.full_name_liked_your_status_update_message',
                [
                    'full_name' => Phpfox::getUserBy('full_name'),
                    'link' => $sLink,
                    'content' => Phpfox::getLib('parse.output')->shorten(strip_tags(Phpfox::getLib('parse.output')->parse($aRow['content'])), 50, '...')
                ]
            ])->notification('like.new_like')->send();

            Phpfox::getService('notification.process')->add('user_status_like', $aRow['status_id'], $aRow['user_id']);

            // get tagged users (tagged by @)
            $aMentions = Phpfox::getService('user.process')->getIdFromMentions($aRow['content'], true, false);
            // get tagged users (tagged by "With")
            $aTaggedByWith = Phpfox::getService('feed.tag')->getTaggedUserIds($aRow['status_id'], 'user_status');

            // check tag privacy
            (Phpfox::isModule('feed') ? Phpfox::getService('feed.tag')->filterTaggedPrivacy($aMentions, $aTaggedByWith, $aRow['status_id'], 'user_status') : null);

            $aTaggedUsers = array_merge($aMentions, $aTaggedByWith);
            $aTaggedUsers = array_diff($aTaggedUsers, [Phpfox::getUserId()]); // remove owner of action

            // notify tagged user
            foreach ($aTaggedUsers as $iUserId) {
                Phpfox::getLib('mail')->to($iUserId)->subject([
                    'full_name_liked_full_name2_status_update',
                    [
                        'full_name' => Phpfox::getUserBy('full_name'),
                        'full_name2' => $aRow['full_name']
                    ]
                ])->message([
                    'full_name_liked_full_name2_status_update_message',
                    [
                        'full_name' => Phpfox::getUserBy('full_name'),
                        'full_name2' => $aRow['full_name'],
                        'link' => $sLink
                    ]
                ])->notification('like.new_like')->send();
                Phpfox::getService('notification.process')->add('user_status_like', $aRow['status_id'], $iUserId);
            }
        }
    }

    public function getNotificationStatus_Like($aNotification)
    {
        $aRow = $this->database()
            ->select('us.status_id, us.content, us.user_id, u.gender, u.user_name, u.full_name')
            ->from(Phpfox::getT('user_status'), 'us')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = us.user_id')
            ->where('us.status_id = ' . (int)$aNotification['item_id'])
            ->execute('getSlaveRow');

        if (!$aRow) {
            return false;
        }

        $aRow['content'] = Phpfox::getLib('parse.bbcode')->cleanCode($aRow['content']);
        if ($aNotification['user_id'] == $aRow['user_id']) {
            $sPhrase = _p('user_name_liked_gender_own_status_update_title', [
                'user_name' => Phpfox::getService('notification')->getUsers($aNotification),
                'gender' => Phpfox::getService('user')->gender($aRow['gender'], 1),
                'title' => Phpfox::getLib('parse.output')->shorten($aRow['content'],
                    Phpfox::getParam('notification.total_notification_title_length'), '...')
            ]);
        } elseif ($aRow['user_id'] == Phpfox::getUserId()) {
            $sPhrase = _p('user_name_liked_your_status_update_title', [
                'user_name' => Phpfox::getService('notification')->getUsers($aNotification),
                'title' => Phpfox::getLib('parse.output')->shorten($aRow['content'],
                    Phpfox::getParam('notification.total_notification_title_length'), '...')
            ]);
        } else {
            $sPhrase = _p('user_name_liked_span_class_drop_data_user_full_name_s_span_status_update_title', [
                'user_name' => Phpfox::getService('notification')->getUsers($aNotification),
                'full_name' => $aRow['full_name'],
                'title' => Phpfox::getLib('parse.output')->shorten($aRow['content'],
                    Phpfox::getParam('notification.total_notification_title_length'), '...')
            ]);
        }

        return [
            'link' => Phpfox::getLib('url')->makeUrl($aRow['user_name'], ['status-id' => $aRow['status_id']]),
            'message' => $sPhrase,
            'icon' => Phpfox_Template::instance()->getStyle('image', 'activity.png', 'blog')
        ];
    }

    public function addCommentStatus($aVals)
    {
        if (Phpfox::isModule('feed')) {
            db()->select('f.privacy AS feed_privacy, ')
                ->join(':feed', 'f', 'f.item_id = us.status_id AND f.type_id = "user_status"');
        }
        $aRow = $this->database()->select('us.status_id, us.content, u.full_name, u.gender, u.user_id, u.user_name')
            ->from(Phpfox::getT('user_status'), 'us')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = us.user_id')
            ->where('us.status_id = ' . (int)$aVals['item_id'])
            ->execute('getSlaveRow');

        // Update the post counter if its not a comment put under moderation or if the person posting the comment is the owner of the item.
        if (empty($aVals['parent_id'])) {
            $this->database()->updateCounter('user_status', 'total_comment', 'status_id', $aRow['status_id']);
        }

        $aComment = $this->database()->select('comment_id, user_id')->from(':comment')->where('item_id=' . (int)$aVals['item_id'])->order('comment_id DESC')->executeRow();
        $iLastId = $aComment['comment_id'];
        $iSenderId = $aComment['user_id'];

        // get tagged users (tagged by "With")
        $aTaggedUsers = Phpfox::getService('feed.tag')->getTaggedUserIds($aRow['status_id'], 'user_status');

        // get tagged users (tagged by @)
        $aMentions = Phpfox::getService('user.process')->getIdFromMentions($aRow['content'], true, false);

        // check tag privacy
        (Phpfox::isModule('feed') ? Phpfox::getService('feed.tag')->filterTaggedPrivacy($aMentions, $aTaggedUsers, $aRow['status_id'], 'user_status') : null);

        $aTaggedUsers = array_merge($aMentions, $aTaggedUsers);
        $aTaggedUsers = array_diff($aTaggedUsers, [$iSenderId]); // remove sender
        $iFeedPrivacy = !empty($aRow['feed_privacy']) ? $aRow['feed_privacy'] : 0;

        if ($iFeedPrivacy == 3) {
            $aTaggedUsers = [];
        } elseif ($iFeedPrivacy && Phpfox::isModule('privacy')) {
            foreach ($aTaggedUsers as $iKey => $iTaggedUser) {
                if (!Phpfox::getService('privacy')->check('user_status', $aVals['item_id'], $aRow['user_id'], $iFeedPrivacy, null, true, false, $iTaggedUser)) {
                    unset($aTaggedUsers[$iKey]);
                }
            }
        }

        // notify tagged users
        if (!empty($aTaggedUsers)) {
            $sLink = Phpfox::getLib('url')->makeUrl($aRow['user_name'], ['status-id' => $aRow['status_id']]) . '#js_comment_text_' . $iLastId;
            $sSubject = ['full_name_commented_on_one_of_other_full_name_s_status_updates', [
                'full_name' => Phpfox::getUserBy('full_name'),
                'other_full_name' => $aRow['full_name']
            ]];
            $sMessage = ['full_name_commented_on_other_full_name_s_status_update_a_href_link_title_a_to_see_the_comment_thread_follow_the_link_below_a_href_link_link_a',
                [
                    'full_name' => Phpfox::getUserBy('full_name'),
                    'other_full_name' => $aRow['full_name'],
                    'link' => $sLink,
                    'title' => Phpfox::getLib('parse.output')->shorten(Phpfox::getLib('parse.output')->parse($aRow['content']), 50, '...')
                ]];

            // send email and notification to each user that were tagged
            foreach ($aTaggedUsers as $iUserId) {
                if ($iFeedPrivacy
                    && Phpfox::isModule('privacy')
                    && !Phpfox::getService('privacy')->check('user_status', $aVals['item_id'], $aRow['user_id'], $iFeedPrivacy, null, true, false, $iUserId)) {
                    continue;
                }
                Phpfox::getLib('mail')->to($iUserId)
                    ->subject($sSubject)
                    ->message($sMessage)
                    ->notification('comment.add_new_comment')
                    ->send();
                Phpfox::getService('notification.process')->add('comment_user_status', $aVals['item_id'], $iUserId, $iSenderId);
            }
        }

        // send email and notification to owner of status and exclude some users that have been sent notification
        $this->_notifyUser($aRow['user_id'], $aVals['item_id'], $iLastId, $iSenderId, $aRow['content'], $aTaggedUsers);
    }

    private function _notifyUser($iUserId, $iStatusId, $iCommentId, $iSenderId, $sContent, $aExcludeUsers)
    {
        $aRow = Phpfox::getService('user')->getUser($iUserId);
        $sLink = Phpfox::getLib('url')->makeUrl($aRow['user_name'],
                ['status-id' => $iStatusId]) . '#js_comment_text_' . $iCommentId;
        $sOwnerSubject = ['full_name_commented_on_your_status_update',
            ['full_name' => Phpfox::getUserBy('full_name')]];
        $sOwnerMessage = [isset($sContent) && $sContent != '' ? 'full_name_commented_on_your_status_update_title_to_see_the_comment_thread_follow_the_link_below_a_href_link_link_a' : 'full_name_commented_on_your_status_update_to_see_the_comment_thread_follow_the_link_below_a_href_link_link_a',
            [
                'full_name' => Phpfox::getUserBy('full_name'),
                'title' => Phpfox::getLib('parse.output')->shorten(Phpfox::getLib('parse.output')->parse($sContent), 50,
                    '...'),
                'link' => $sLink
            ]];

        if (Phpfox::getUserId() == $iUserId) {
            $sMassSubject = ['full_name_commented_on_one_of_gender_status_updates', [
                'full_name' => Phpfox::getUserBy('full_name'),
                'gender' => Phpfox::getService('user')->gender($aRow['gender'], 1)
            ]];
        } else {
            $sMassSubject = ['full_name_commented_on_one_of_other_full_name_s_status_updates',
                ['full_name' => Phpfox::getUserBy('full_name'), 'other_full_name' => $aRow['full_name']]];
        }

        if (Phpfox::getUserId() == $iUserId) {
            $sMassMessage = ['full_name_commented_on_gender_status_update_a_href_link_title_a_to_see_the_comment_thread_follow_the_link_below_a_href_link_link_a',
                [
                    'full_name' => Phpfox::getUserBy('full_name'),
                    'gender' => Phpfox::getService('user')->gender($aRow['gender'], 1),
                    'title' => Phpfox::getLib('parse.output')->shorten(Phpfox::getLib('parse.output')->parse($sContent),
                        50, '...'),
                    'link' => $sLink
                ]];
        } else {
            $sMassMessage = ['full_name_commented_on_other_full_name_s_status_update_a_href_link_title_a_to_see_the_comment_thread_follow_the_link_below_a_href_link_link_a',
                [
                    'full_name' => Phpfox::getUserBy('full_name'),
                    'other_full_name' => $aRow['full_name'],
                    'link' => $sLink,
                    'title' => Phpfox::getLib('parse.output')->shorten(Phpfox::getLib('parse.output')->parse($sContent),
                        50, '...')
                ]];
        }

        Phpfox::getService('comment.process')->notify([
            'sender_id' => $iSenderId,
            'user_id' => $iUserId,
            'item_id' => $iStatusId,
            'owner_subject' => $sOwnerSubject,
            'owner_message' => $sOwnerMessage,
            'owner_notification' => 'comment.add_new_comment',
            'notify_id' => 'comment_user_status',
            'mass_id' => 'user_status',
            'mass_subject' => $sMassSubject,
            'mass_message' => $sMassMessage,
            'exclude_users' => $aExcludeUsers
        ], $iUserId != $iSenderId);
    }

    public function getCommentItemStatus($iId)
    {
        $aRow = $this->database()->select('status_id AS comment_item_id, privacy_comment, user_id AS comment_user_id')->from(Phpfox::getT('user_status'))->where('status_id = ' . (int)$iId)->execute('getSlaveRow');

        $aRow['comment_view_id'] = '0';

        if (!Phpfox::getService('comment')->canPostComment($aRow['comment_user_id'], $aRow['privacy_comment'])) {
            Phpfox_Error::set(_p('unable_to_post_a_comment_on_this_item_due_to_privacy_settings'));

            unset($aRow['comment_item_id']);
        }

        return $aRow;
    }

    public function getCommentNotificationStatus($aNotification)
    {
        $aRow = $this->database()->select('us.status_id, u.user_id, us.content, u.gender, u.user_name, u.full_name')->from(Phpfox::getT('user_status'),
            'us')->join(Phpfox::getT('user'), 'u',
            'u.user_id = us.user_id')->where('us.status_id = ' . (int)$aNotification['item_id'])->execute('getSlaveRow');

        if (empty($aRow['status_id'])) {
            return false;
        }

        $aRow['content'] = Phpfox::getLib('parse.bbcode')->cleanCode($aRow['content']);
        if ($aNotification['user_id'] == $aRow['user_id']) {
            $sPhrase = _p('span_class_drop_data_user_full_name_span_commented_on_gender_status_update_title', [
                'full_name' => $aNotification['full_name'],
                'gender' => Phpfox::getService('user')->gender($aRow['gender'], 1),
                'title' => Phpfox::getLib('parse.output')->shorten($aRow['content'],
                    Phpfox::getParam('notification.total_notification_title_length'), '...')
            ]);
        } elseif ($aRow['user_id'] == Phpfox::getUserId()) {
            $sPhrase = _p('span_class_drop_data_user_full_name_span_commented_on_your_status_update_title', [
                'full_name' => $aNotification['full_name'],
                'title' => Phpfox::getLib('parse.output')->shorten($aRow['content'],
                    Phpfox::getParam('notification.total_notification_title_length'), '...')
            ]);
        } else {
            $sPhrase = _p('span_class_drop_data_user_full_name_span_commented_on_span_class_drop_data_user_other_full_name_s_span_status_update_title',
                [
                    'full_name' => $aNotification['full_name'],
                    'other_full_name' => $aRow['full_name'],
                    'title' => Phpfox::getLib('parse.output')->shorten($aRow['content'],
                        Phpfox::getParam('notification.total_notification_title_length'), '...')
                ]);
        }
        //Get latest comment ID from that user
        $iLastId = $this->database()->select('comment_id')->from(':comment')->where('user_id=' . (int)$aNotification['owner_user_id'] . ' AND item_id=' . (int)$aNotification['item_id'])->order('comment_id DESC')->execute('getSlaveField');

        return [
            'link' => Phpfox::getLib('url')->makeUrl($aRow['user_name'],
                    ['status-id' => $aRow['status_id']]) . '#js_comment_text_' . $iLastId,
            'message' => $sPhrase
        ];
    }

    public function globalUnionSearch($sSearch)
    {
        $this->database()->select('item.user_id AS item_id, item.full_name AS item_title, item.last_login AS item_time_stamp, item.user_id AS item_user_id, \'user\' AS item_type_id, \'\' AS item_photo, 0 AS item_photo_server')->from(Phpfox::getT('user'),
            'item')->where($this->database()->searchKeywords(['item.full_name', 'item.user_name'],
                $sSearch) . ' AND item.status_id = 0 AND item.view_id = 0 AND item.profile_page_id = 0')->union();
    }

    public function getSearchInfo($aRow)
    {
        $aInfo = [];
        $aInfo['item_link'] = Phpfox::getLib('url')->makeUrl($aRow['user_name']);
        $aInfo['item_name'] = _p('members');

        return $aInfo;
    }

    public function getSearchTitleInfo()
    {
        return [
            'name' => _p('members')
        ];
    }

    public function getAdditionalScheduleInfoStatus($aRow) {
        $aInfo = [];
        $data = $aRow['data'];

        $aInfo['item_title'] = $data['user_status'];
        $aInfo['item_name'] = _p('status');

        return $aInfo;
    }

    public function getRedirectCommentStatus($iId)
    {
        $aRow = $this->database()->select('us.*, u.user_name')->from(Phpfox::getT('user_status'),
            'us')->join(Phpfox::getT('user'), 'u',
            'u.user_id = us.user_id')->where('us.status_id = ' . (int)$iId)->execute('getSlaveRow');

        return Phpfox::getLib('url')->makeUrl($aRow['user_name'], ['status-id' => $aRow['status_id']]);
    }

    public function getProfileSettings()
    {
        return [
            'user.can_i_be_tagged' => [
                'phrase' => _p('who_can_tag_me_in_written_contexts'),
                'anyone' => false,
                'friend_only' => true,
            ]
        ];
    }

    public function canShareItemOnFeed()
    {
        return true;
    }

    /**
     * callback when delete status feed
     * @param $iItem
     * @return boolean
     */
    public function deleteFeedItemStatus($iItem)
    {
        // also remove comment and user status
        Phpfox::isModule('comment') ? Phpfox::getService('comment.process')->deleteForItem(Phpfox::getUserId(), $iItem, 'user_status') : null;
        return $this->database()->delete(Phpfox::getT('user_status'), ['status_id' => $iItem]);
    }

    /**
     * Callback when comment be approved
     * @param $aRow
     * @return array
     */
    public function getCommentNotificationApprove($aRow)
    {
        $sPhrase = _p('your_comment_has_been_approved');
        return [
            'message' => $sPhrase,
            'link' => Phpfox::getLib('url')->makeUrl('comment.view', [$aRow['item_id']])
        ];
    }

    public function getUploadParams($aExtraParams = [])
    {
        $iMaxFileSize = Phpfox::getUserParam('user.max_upload_size_profile_photo');
        $iMaxFileSize = $iMaxFileSize > 0 ? $iMaxFileSize / 1024 : 0;
        $iMaxFileSize = Phpfox::getLib('file')->getLimit($iMaxFileSize);
        $sPreviewTemplate =
            '<div class="dz-preview dz-file-preview">
                <div class="dz-image"><img data-dz-thumbnail /></div>
                <div class="dz-uploading-message">' . _p('uploading_your_photo_three_dot') . '</div>
                <div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div>
                <div class="dz-upload-successfully">' . _p('drag_to_reposition_photo') . '</div>
                <div class="dz-error-message"><span data-dz-errormessage></span> <a role="button" class="dz-upload-again" id="profile-image-upload-again">' . _p('change_photo') . '</a></div>
            </div>';

        return [
            'max_size' => ($iMaxFileSize === 0 ? null : $iMaxFileSize),
            'type_list' => ['jpg', 'jpeg', 'gif', 'png'],
            'upload_dir' => Phpfox::getParam('core.dir_user'),
            'upload_path' => Phpfox::getParam('core.url_user'),
            'thumbnail_sizes' => Phpfox::getService('user')->getUserThumbnailSizes(),
            'upload_now' => "true",
            'upload_url' => Phpfox::getLib('url')->makeUrl('user.photo', is_array($aExtraParams) && isset($aExtraParams['is_profile_photo']) ? ['is_profile_photo' => 1] : null),
            'param_name' => 'image',
            'preview_template' => $sPreviewTemplate,
            'label' => '',
            'style' => 'mini',
            'first_description' => '',
            'type_description' => '',
            'max_size_description' => '',
            'extra_description' => '',
            'js_events' => [
                'success' => '$Core.ProfilePhoto.onSuccessUpload',
                'addedfile' => '$Core.ProfilePhoto.onAddedFile',
                'error' => '$Core.ProfilePhoto.onError'
            ],
            'extra_data' => [
                'not-remove-file' => true
            ]
        ];
    }

    public function getItemName($iId, $sName)
    {
        return _p('a_href_link_on_name_s_status_a',
            ['link' => Phpfox::getLib('url')->makeUrl('comment.view', ['id' => $iId]), 'name' => $sName]);
    }

    public function getCopyUserInfoStatus()
    {
        $sDescription = Phpfox::getParam('core.enable_register_with_phone_number') ?
            _p('Your name, email, phone number, birthday, ...') :
            _p('Your name, email, birthday, ...');
        return [
            "account_setting" => [
                "tittle" => _p("account_info"),
                "description" => $sDescription
            ]
        ];
    }

    public function processCopyUserInfo_account_setting()
    {
        $aUserInfo = Phpfox::getLib('database')->select('u. full_name, u.user_name, u.email, u.gender, u.birthday, u.custom_gender, u.full_phone_number')
            ->from(':user', 'u')
            ->where('u.user_id=' . Phpfox::getUserId())
            ->executeRow();
        $sGenderName = "n/a";
        if ($aUserInfo['gender'] == 127) {
            $aCustomGenders = isset($aUserInfo['custom_gender']) ? unserialize($aUserInfo['custom_gender']) : '';
            if (is_array($aCustomGenders)) {
                $iCount = count($aCustomGenders);
                switch ($iCount) {
                    case 1:
                        $sGenderName = $aCustomGenders[0];
                        break;
                    case 2:
                        $sGenderName = implode(' ' . _p('and') . ' ', $aCustomGenders);
                        break;
                    default:
                        if (count($aCustomGenders) > 2) {
                            $sLastGender = array_pop($aCustomGenders);
                            $sGenderName = implode(', ', $aCustomGenders);
                            $sGenderName .= ' ' . _p('and') . ' ' . $sLastGender;
                        }
                        break;
                }
            }
        } else {
            $sGenderName = Phpfox::getService('user')->gender($aUserInfo['gender']);
        }

        $data = [
            'full_name' => $aUserInfo['full_name'],
            'user_name' => $aUserInfo['user_name'],
            'email' => $aUserInfo['email'],
            'gender' => $sGenderName,
            'birthday' => !empty($aUserInfo['birthday']) ? date("F", mktime(0, 0, 0, substr($aUserInfo['birthday'], 0, 2), 10)) . ' ' . substr($aUserInfo['birthday'], 2, 2) . ' - ' . substr($aUserInfo['birthday'], 4, 4) : '',
        ];
        if (Phpfox::getParam('core.enable_register_with_phone_number') && !empty($aUserInfo['full_phone_number'])) {
            $oPhoneLib = Phpfox::getLib('phone');
            $oPhoneLib->setRawPhone($aUserInfo['full_phone_number']);
            $data['phone_number'] = $oPhoneLib->getPhoneInternational();
        }
        return [
            'files' => [],
            'data' => json_encode($data, JSON_PRETTY_PRINT)
        ];
    }

    public function getCopyUserDataStatus()
    {
        return [
            "status" => [
                "tittle" => _p("Update Status"),
                "description" => _p("All your update status")
            ]
        ];
    }

    public function processCopyUserData_status()
    {
        $aAllStatus = Phpfox::getLib('database')->select('content, time_stamp')
            ->from(':user_status')
            ->where('user_id=' . Phpfox::getUserId())
            ->executeRows();
        return [
            'files' => [],
            'data' => json_encode($aAllStatus, JSON_PRETTY_PRINT)
        ];
    }

    /**
     * Notification for tagging in user status
     * @param $aNotification
     * @return array|false
     */
    public function getNotificationTagged_Profile($aNotification)
    {
        $aRow = $this->database()->select('u.user_id, u.gender, u.user_name, u.full_name')
            ->from(Phpfox::getT('user_status'), 'fc')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = fc.user_id')
            ->where('fc.status_id = ' . (int)$aNotification['item_id'])
            ->execute('getSlaveRow');

        if (empty($aRow['user_id'])) {
            return false;
        }

        $sPhrase = _p('user_name_tagged_you_in_a_status_update', ['user_name' => Phpfox::getService('notification')->getUsers($aNotification)]);

        return [
            'link'    => Phpfox_Url::instance()->makeUrl($aRow['user_name'], ['status-id' => (int)$aNotification['item_id']]),
            'message' => $sPhrase,
            'icon'    => Phpfox_Template::instance()->getStyle('image', 'activity.png', 'blog')
        ];
    }

    /**
     * Process send notify to tagged users
     * @param $params
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

        $aCurrentUser = Phpfox::getService('user')->getUser($iOwnerId);
        $sTagger = (isset($aCurrentUser['full_name']) && $aCurrentUser['full_name']) ? $aCurrentUser['full_name'] : $aCurrentUser['user_name'];
        $bIsUserStatus = $sFeedType == 'user_status';
        if ($bIsUserStatus) {
            $link = Phpfox::getLib('url')->makeUrl($aCurrentUser['user_name'], ['status-id' => $iItemId]);
        } else {
            $link = Phpfox::getLib('url')->makeUrl($aCurrentUser['user_name'], ['feed' => $iFeedId]);
        }

        //Send Mail
        foreach ($aTagged as $iUserId) {
            if (in_array($moduleId, ['', 'user'])) {
                if ($iParentUserId == $iUserId) {
                    continue;
                }
                (Phpfox::isModule('feed') ? Phpfox::getService('feed.process')->add($sFeedType, $iItemId, $iPrivacy, 0, $iUserId, $iOwnerId, 1, $iFeedId) : null);
            }

            if (empty($params['no_notification']) && Phpfox::isModule('notification')) {
                Phpfox::getService('notification.process')->add($bIsUserStatus ? 'user_tagged_profile' : 'feed_tagged_profile', $iItemId, $iUserId, $iOwnerId, true);
            }
            empty($params['no_email']) && Phpfox::getService('user.process')->mailToTagged($iUserId, $sTagger, $link);
        }
    }

    public function getTransferFileAllowPath()
    {
        //Just return folder name or array if has many folders, only transfer children in Folder PF.Base/file
        return 'pic/user';
    }

    public function getTransferFileDatabaseParams()
    {
        return [
            [
                'table_name' => 'user',
                'column_id' => 'server_id'
            ]
        ];
    }

    public function addScheduleItemToFeedStatus($aVals) {
        if ($iId = Phpfox::getService('user.process')->updateStatus($aVals)) {
            (($sPlugin = Phpfox_Plugin::get('user.component_ajax_updatestatus')) ? eval($sPlugin) : false);
            return true;
        }
        return false;
    }

    public function getExtraScheduleDataStatus($data) {
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

