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
 * @package         Phpfox_Service
 * @version         $Id: process.class.php 7230 2014-03-26 21:14:12Z phpFox $
 */
abstract class Phpfox_Pages_Process extends Phpfox_Service
{
    protected $_bHasImage = false;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('pages');
    }

    /**
     * @return Phpfox_Pages_Facade
     */
    abstract public function getFacade();

    public function updateCategory($iId, $aVals)
    {
        //Update phrase
        $aLanguages = Phpfox::getService('language')->getAll();
        if (Core\Lib::phrase()->isPhrase($aVals['name'])) {
            foreach ($aLanguages as $aLanguage) {
                if (isset($aVals['name_' . $aLanguage['language_id']])) {
                    $name = $aVals['name_' . $aLanguage['language_id']];
                    Phpfox::getService('language.phrase.process')->updateVarName($aLanguage['language_id'],
                        $aVals['name'], $name);
                }
            }
        } else {
            //Add new phrase if before is not phrase
            $name = $aVals['name_' . $aLanguages[0]['language_id']];
            $phrase_var_name = $this->getFacade()->getItemType() . '_category_' . md5('Pages/Groups Category' . $name . PHPFOX_TIME);
            $aText = [];
            foreach ($aLanguages as $aLanguage) {
                if (isset($aVals['name_' . $aLanguage['language_id']]) && !empty($aVals['name_' . $aLanguage['language_id']])) {
                    $aText[$aLanguage['language_id']] = $aVals['name_' . $aLanguage['language_id']];
                } else {
                    Phpfox_Error::set((_p('Provide a "{{ language_name }}" name.',
                        ['language_name' => $aLanguage['title']])));
                }
            }
            $aValsPhrase = [
                'product_id' => 'phpfox',
                'module'     => $this->getFacade()->getItemType() . '|' . $this->getFacade()->getItemType(),
                'var_name'   => $phrase_var_name,
                'text'       => $aText
            ];
            $aVals['name'] = Phpfox::getService('language.phrase.process')->add($aValsPhrase);
        }

        if (!empty($aVals['type_id'])) {
            $this->database()->update(Phpfox::getT('pages_category'), array(
                'type_id'   => (int)$aVals['type_id'],
                'name'      => $aVals['name'],
                'page_type' => isset($aVals['page_type']) ? (int)$aVals['page_type'] : 0
            ), 'category_id = ' . (int)$iId
            );

            // update item's type_id
            db()->update(':pages', ['type_id' => $aVals['type_id']], 'category_id = ' . (int)$iId);
        } else {
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $this->getFacade()->getType()->deleteImage((int)$iId);
                $sFileName = $this->_processImage();
            }
            $this->database()->update(Phpfox::getT('pages_type'), array_merge([
                'name' => $aVals['name']
            ], !isset($sFileName) ? [] : [
                'image_path'      => $sFileName,
                'image_server_id' => \Phpfox_Request::instance()->getServer('PHPFOX_SERVER_ID')
            ]), 'type_id = ' . (int)$iId);
        }
        //remove category cache
        Core\Lib::phrase()->clearCache();
        $this->cache()->removeGroup($this->getFacade()->getItemType());

        return true;
    }

    public function addCategory($aVals)
    {
        if (!isset($aVals['phrase_var_name'])) {
            //Add phrase for category
            $aLanguages = Phpfox::getService('language')->getAll();
            $name = $aVals['name_' . $aLanguages[0]['language_id']];
            $phrase_var_name = $this->getFacade()->getItemType() . '_category_' . md5('Pages/Groups Category' . $name . PHPFOX_TIME);
            //Add phrases
            $aText = [];
            foreach ($aLanguages as $aLanguage) {
                if (isset($aVals['name_' . $aLanguage['language_id']]) && !empty($aVals['name_' . $aLanguage['language_id']])) {
                    $aText[$aLanguage['language_id']] = $aVals['name_' . $aLanguage['language_id']];
                } else {
                    return Phpfox_Error::set((_p('Provide a "{{ language_name }}" name.',
                        ['language_name' => $aLanguage['title']])));
                }
            }
            $aValsPhrase = [
                'var_name' => $phrase_var_name,
                'text'     => $aText
            ];
            $finalPhrase = Phpfox::getService('language.phrase.process')->add($aValsPhrase);
        } else {
            $finalPhrase = $aVals['phrase_var_name'];
        }

        if (!empty($aVals['type_id'])) {
            $iId = $this->database()->insert(Phpfox::getT('pages_category'), array(
                    'type_id'   => (int)$aVals['type_id'],
                    'is_active' => isset($aVals['is_active']) ? $aVals['is_active'] : '1',
                    'name'      => $finalPhrase,
                    'page_type' => isset($aVals['page_type']) ? (int)$aVals['page_type'] : 0
                )
            );
        } else {
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $sFileName = $this->_processImage();
            }
            $iId = $this->database()->insert(Phpfox::getT('pages_type'), array_merge([
                'is_active'  => isset($aVals['is_active']) ? $aVals['is_active'] : '1',
                'name'       => $finalPhrase,
                'time_stamp' => PHPFOX_TIME,
                'ordering'   => '0',
                'item_type'  => isset($aVals['item_type']) ? $aVals['item_type'] : $this->getFacade()->getItemTypeId(),
            ], !isset($sFileName) ? [] : [
                'image_path'      => $sFileName,
                'image_server_id' => \Phpfox_Request::instance()->getServer('PHPFOX_SERVER_ID')
            ]));
        }
        Core\Lib::phrase()->clearCache();
        $this->cache()->removeGroup($this->getFacade()->getItemType());
        return $iId;
    }

    private function _processImage()
    {
        // upload image
        $oImage = \Phpfox_Image::instance();
        $oFile = Phpfox_File::instance();
        $oFile->load('image', array('jpg', 'gif', 'png'),
            (Phpfox::getUserParam('user.max_upload_size_profile_photo') === 0 ? null : (Phpfox::getUserParam('user.max_upload_size_profile_photo') / 1024)));

        $sFileName = $oFile->upload('image', Phpfox::getParam('pages.dir_image'), '');
        $iFileSizes = filesize(Phpfox::getParam('pages.dir_image') . sprintf($sFileName, ''));

        $iSize = 50;
        $oImage->createThumbnail(Phpfox::getParam('pages.dir_image') . sprintf($sFileName, ''),
            Phpfox::getParam('pages.dir_image') . sprintf($sFileName, '_' . $iSize), $iSize, $iSize, false);
        $iFileSizes += filesize(Phpfox::getParam('pages.dir_image') . sprintf($sFileName, '_' . $iSize));

        $iSize = 120;
        $oImage->createThumbnail(Phpfox::getParam('pages.dir_image') . sprintf($sFileName, ''),
            Phpfox::getParam('pages.dir_image') . sprintf($sFileName, '_' . $iSize), $iSize, $iSize, false);
        $iFileSizes += filesize(Phpfox::getParam('pages.dir_image') . sprintf($sFileName, '_' . $iSize));

        $iSize = 200;
        $oImage->createThumbnail(Phpfox::getParam('pages.dir_image') . sprintf($sFileName, ''),
            Phpfox::getParam('pages.dir_image') . sprintf($sFileName, '_' . $iSize), $iSize, $iSize, false);
        $iFileSizes += filesize(Phpfox::getParam('pages.dir_image') . sprintf($sFileName, '_' . $iSize));

        //Crop max width
        if (Phpfox::isAppActive('Core_Photos')) {
            Phpfox::getService('photo')->cropMaxWidth(Phpfox::getParam('pages.dir_image') . sprintf($sFileName, ''));
        }
        // Update user space usage
        Phpfox::getService('user.space')->update(Phpfox::getUserId(), $this->getFacade()->getItemType(), $iFileSizes);

        return str_replace(Phpfox::getParam('core.path_actual'), '', Phpfox::getParam('pages.url_image')) . $sFileName;
    }

    public function updateActivity($iId, $iType, $iSub)
    {
        Phpfox::isUser(true);
        $this->getFacade()->getUserParam('admincp.has_admin_access');

        $this->database()->update(($iSub ? Phpfox::getT('pages_category') : Phpfox::getT('pages_type')),
            array('is_active' => (int)($iType == '1' ? 1 : 0)),
            ($iSub ? 'category_id' : 'type_id') . ' = ' . (int)$iId);

        $this->cache()->remove();
    }

    public function updateTitle($iId, $sNewTitle)
    {
        if (!Phpfox::getService('ban')->check('username', $sNewTitle) || !Phpfox::getService('ban')->check('word', $sNewTitle)
        ) {
            return Phpfox_Error::set($this->getFacade()->getPhrase('that_title_is_not_allowed'));
        }

        $aTitle = $this->database()->select('*')
            ->from(Phpfox::getT('pages_url'))
            ->where('page_id = ' . (int)$iId)
            ->execute('getSlaveRow');

        if (isset($aTitle['vanity_url'])) {
            $this->database()->update(Phpfox::getT('pages_url'), array('vanity_url' => $sNewTitle),
                'page_id = ' . (int)$iId);
        } else {
            $this->database()->insert(Phpfox::getT('pages_url'),
                array('vanity_url' => $sNewTitle, 'page_id' => (int)$iId));
        }

        $this->database()->update(Phpfox::getT('user'), array('user_name' => $sNewTitle),
            'profile_page_id = ' . (int)$iId);

        return true;
    }

    /**
     * Mass action moderations
     * @param $aModerations
     * @param $sAction
     * @return bool
     */
    public function moderation($aModerations, $sAction)
    {
        $iCnt = 0;
        if (is_array($aModerations) && count($aModerations)) {
            foreach ($aModerations as $iModeration) {
                $iCnt++;
                $aPage = $this->database()->select('p.*, ps.user_id AS post_user_id')
                    ->from(Phpfox::getT('pages_signup'), 'ps')
                    ->join(Phpfox::getT('pages'), 'p', 'p.page_id = ps.page_id')
                    ->where('ps.signup_id = ' . (int)$iModeration)
                    ->execute('getSlaveRow');

                if (!isset($aPage['page_id'])) {
                    return Phpfox_Error::display($this->getFacade()->getPhrase('unable_to_find_the_page'));
                }

                if (!$this->getFacade()->getItems()->isAdmin($aPage)) {
                    return Phpfox_Error::display($this->getFacade()->getPhrase('unable_to_moderate_this_page'));
                }

                if ($sAction == 'approve') {
                    Phpfox::getService('like.process')->add($this->getFacade()->getItemType(), $aPage['page_id'],
                        $aPage['post_user_id'], null, ['ignoreCheckPermission' => true]);
                }

                Phpfox::getService('notification.process')->delete($this->getFacade()->getItemType() . '_register', $iModeration, Phpfox::getUserId());
                $this->database()->delete(Phpfox::getT('pages_signup'), 'signup_id = ' . (int)$iModeration);
                $this->cache()->remove($this->getFacade()->getItemType() . '_' . $aPage['page_id'] . '_pending_users');
            }
        }

        return true;
    }

    public function login($iPageId)
    {
        $aPage = $this->database()->select('p.*, p.user_id AS owner_user_id, u.*')
            ->from(Phpfox::getT('pages'), 'p')
            ->join(Phpfox::getT('user'), 'u', 'u.profile_page_id = p.page_id')
            ->where('p.page_id = ' . (int)$iPageId)
            ->execute('getSlaveRow');

        if (!isset($aPage['page_id'])) {
            return Phpfox_Error::set($this->getFacade()->getPhrase('unable_to_find_the_page_you_are_trying_to_login_to'));
        }

        $iCurrentUserId = Phpfox::getUserId();

        $bCanLogin = false;
        if ($aPage['owner_user_id'] == Phpfox::getUserId()) {
            $bCanLogin = true;
        }

        if (!$bCanLogin) {
            if (Phpfox::getService('pages')->isAdmin($aPage)) {
                $bCanLogin = true;
            }
        }

        if (!$bCanLogin) {
            return Phpfox_Error::set($this->getFacade()->getPhrase('unable_to_log_in_as_this_page'));
        }

        $sPasswordHash = Phpfox::getLib('hash')->setRandomHash(Phpfox::getLib('hash')->setHash($aPage['password'],
            $aPage['password_salt']));

        $iTime = 0;

        $aUserCookieNames = Phpfox::getService('user.auth')->getCookieNames();

        Phpfox::setCookie($aUserCookieNames[0], $aPage['user_id'], $iTime);
        Phpfox::setCookie($aUserCookieNames[1], $sPasswordHash, $iTime);

        Phpfox::getLib('session')->remove('theme');

        $this->database()->update(Phpfox::getT('user'), array('last_login' => PHPFOX_TIME),
            'user_id = ' . $aPage['user_id']);
        $this->database()->insert(Phpfox::getT('user_ip'), array(
                'user_id'    => $aPage['user_id'],
                'type_id'    => 'login',
                'ip_address' => Phpfox::getIp(),
                'time_stamp' => PHPFOX_TIME
            )
        );

        $iLoginId = $this->database()->insert(Phpfox::getT('pages_login'), array(
                'page_id'    => $aPage['page_id'],
                'user_id'    => $iCurrentUserId,
                'time_stamp' => PHPFOX_TIME
            )
        );

        Phpfox::setCookie('page_login', $iLoginId, $iTime);

        return true;
    }

    public function clearLogin($iUserId)
    {
        $this->database()->delete(Phpfox::getT('pages_login'), 'user_id = ' . (int)$iUserId);

        Phpfox::setCookie('page_login', '', -1);
    }

    public function approve($iId)
    {
        $bCanModerate = $this->getFacade()->getUserParam('can_moderate_pages');
        if ($bCanModerate === null) {
            $bCanModerate = $this->getFacade()->getUserParam('can_approve_pages');
        }

        if (!$bCanModerate) {
            return false;
        }

        $aPage = $this->getFacade()->getItems()->getPage($iId);

        if (!isset($aPage['page_id'])) {
            return Phpfox_Error::set($this->getFacade()->getPhrase('unable_to_find_the_page_you_are_trying_to_approve'));
        }

        if ($aPage['view_id'] != '1') {
            return false;
        }

        $this->database()->update(Phpfox::getT('pages'), array('view_id' => '0', 'time_stamp' => PHPFOX_TIME),
            'page_id = ' . $aPage['page_id']);

        if (Phpfox::isModule('notification')) {
            Phpfox::getService('notification.process')->add($this->getFacade()->getItemType() . '_approved',
                $aPage['page_id'], $aPage['user_id']);
        }

        Phpfox::getService('user.activity')->update($aPage['user_id'], $this->getFacade()->getItemType());

        (($sPlugin = Phpfox_Plugin::get($this->getFacade()->getItemType() . '.service_process_approve__1')) ? eval($sPlugin) : false);

        // Send the user an email
        $sLink = $this->getFacade()->getItems()->getUrl($aPage['page_id'], $aPage['title'], $aPage['vanity_url']);
        Phpfox::getLib('mail')->to($aPage['user_id'])
            ->subject([$this->getFacade()->getPhraseName('page_title_approved'), ['title' => $aPage['title']]])
            ->message([$this->getFacade()->getPhraseName('your_page_title_has_been_approved'), ['title' => $aPage['title'], 'link' => $sLink]])
            ->notification($this->getFacade()->getItemType() . '.email_notification')
            ->send();

        return true;
    }

    /* Claim status:
            1: Not defined
            2: Approved
            3: Denied
    */
    public function approveClaim($iClaimId)
    {
        // get the claim
        $aClaim = $this->database()->select('pc.*, p.user_id as old_user_id, u.full_name, u.user_name, p.title, pu.vanity_url')
            ->from(':pages_claim', 'pc')
            ->join(':pages', 'p', 'p.page_id = pc.page_id')
            ->join(':user', 'u', 'u.user_id = p.user_id')
            ->leftJoin(':pages_url', 'pu', 'pu.page_id = p.page_id')
            ->where('pc.claim_id = ' . (int)$iClaimId . ' AND pc.status_id = 1')
            ->execute('getSlaveRow');

        if (empty($aClaim)) {
            return Phpfox_Error::set($this->getFacade()->getPhrase('not_a_valid_claim'));
        }

        // set the user_id to the page
        $this->database()->update(Phpfox::getT('pages'), array('user_id' => $aClaim['user_id']),
            'page_id = ' . $aClaim['page_id']);
        $this->database()->update(Phpfox::getT('pages_claim'), array('status_id' => 2), 'claim_id = ' . (int)$iClaimId);
        //update user activity
        Phpfox::getService('user.activity')->update($aClaim['user_id'], 'pages');
        Phpfox::getService('user.activity')->update($aClaim['old_user_id'], 'pages', '-');
        $sLink = Phpfox::getService('pages')->getUrl($aClaim['page_id'], $aClaim['title'], $aClaim['vanity_url']);
        Phpfox::getLib('mail')->to($aClaim['user_id'])
            ->subject([
                'email_your_claim_has_been_approved_subject', ['title' => $aClaim['title']]
            ])
            ->message([
                'email_your_claim_has_been_approved_message',
                [
                    'full_name' => Phpfox::getUserBy('full_name'),
                    'link' => $sLink,
                    'title' => $aClaim['title']
                ]
            ])
            ->notification($this->getFacade()->getItemType() . '.email_notification')
            ->send();
        // send notification to claimer
        Phpfox::getService('notification.process')->add('pages_approve_claim', $aClaim['page_id'], $aClaim['user_id']);
        // send notification to old owner
        Phpfox::getLib('mail')->to($aClaim['old_user_id'])
            ->subject([
                'email_you_has_been_removed_as_owner_of_page_subject', ['title' => $aClaim['title']]
            ])
            ->message([
                'email_you_has_been_removed_as_owner_of_page_message',
                [
                    'full_name' => Phpfox::getUserBy('full_name'),
                    'link' => $sLink,
                    'user_link' => Phpfox::getLib('url')->makeUrl($aClaim['user_name']),
                    'owner_full_name' => $aClaim['full_name'],
                    'title' => $aClaim['title']
                ]
            ])
            ->notification($this->getFacade()->getItemType() . '.email_notification')
            ->send();
        Phpfox::getService('notification.process')->add('pages_remove_owner', $aClaim['page_id'],
            $aClaim['old_user_id']);

        return true;
    }

    public function denyClaim($iClaimId)
    {
        // get the claim
        $aClaim = $this->database()->select('*')
            ->from(Phpfox::getT('pages_claim'))
            ->where('claim_id = ' . (int)$iClaimId . ' AND status_id = 1')
            ->execute('getSlaveRow');

        if (empty($aClaim)) {
            return Phpfox_Error::set($this->getFacade()->getPhrase('not_a_valid_claim'));
        }

        // set the user_id to the page
        $this->database()->update(Phpfox::getT('pages_claim'), array('status_id' => 3), 'claim_id = ' . (int)$iClaimId);

        // send notification
        Phpfox::getService('notification.process')->add('pages_deny_claim', $aClaim['page_id'], $aClaim['user_id']);

        return true;
    }

    public function updateCoverPosition($iPageId, $iPosition)
    {
        if (!$this->getFacade()->getItems()->isAdmin($iPageId) && !Phpfox::isAdmin()) {
            return Phpfox_Error::set($this->getFacade()->getPhrase('user_is_not_an_admin'));
        }
        $this->database()->update(Phpfox::getT('pages'), array(
            'cover_photo_position' => (int)$iPosition
        ), 'page_id = ' . (int)$iPageId);

        return true;
    }

    public function removeCoverPhoto($iPageId)
    {
        if (!Phpfox::isAdmin()) {
            $bIsAdmin = Phpfox::getLib('pages.pages')->isAdmin($iPageId);
            if (empty($bIsAdmin)) {
                return Phpfox_Error::set($this->getFacade()->getPhrase('user_is_not_an_admin'));
            }
        }

        $this->database()->update(Phpfox::getT('pages'), array('cover_photo_id' => '', 'cover_photo_position' => ''),
            'page_id = ' . (int)$iPageId);
        return true;
    }

    /**
     * set default permissions for page/group
     * @param integer $iPageId is the ID of page/group
     * @return bool
     */
    public function setDefaultPermissions($iPageId)
    {
        $iDefaultValue = 0;
        $aPermissions = [];
        switch ($this->getFacade()->getItemType()) {
            case 'pages':
                $iDefaultValue = 0;
                $aPermissions = $this->getFacade()->getItems()->getPerms($iPageId);
                break;

            case 'groups':
                $iDefaultValue = 1;
                $aPermissions = Phpfox::getService('groups')->getPerms($iPageId);
                break;
        }
        foreach ($aPermissions as $aPerm) {
            $this->database()->insert(Phpfox::getT('pages_perm'), [
                'page_id' => (int)$iPageId,
                'var_name' => $aPerm['id'],
                'var_value' => isset($aPerm['has_default']) ? $aPerm['is_active'] : $iDefaultValue,
            ]);
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
     */
    public function __call($sMethod, $aArguments)
    {
        /**
         * Check if such a plug-in exists and if it does call it.
         */
        if ($sPlugin = Phpfox_Plugin::get($this->getFacade()->getItemType() . '.service_process__call')) {
            eval($sPlugin);
            return;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }

    /**
     * update album id of cover photo
     * @param $iPhotoId
     * @param $iGroupId
     */
    public function updateCoverPhoto($iPhotoId, $iGroupId, $bForcePublic = false)
    {
        $iUserId = $this->getFacade()->getItems()->getUserId($iGroupId);
        $iAlbumId = db()->select('album_id')->from(':photo_album')
            ->where(['module_id' => $this->getFacade()->getItemType(), 'group_id' => $iGroupId, 'cover_id' => $iUserId])
            ->executeField();
        if (empty($iAlbumId)) {
            $iAlbumId = db()->insert(':photo_album', [
                'module_id'       => $this->getFacade()->getItemType(),
                'group_id'        => $iGroupId,
                'privacy'         => '0',
                'privacy_comment' => '0',
                'user_id'         => $iUserId,
                'name'            => "{_p var='cover_photo'}",
                'time_stamp'      => PHPFOX_TIME,
                'cover_id'        => $iUserId,
                'total_photo'     => 0
            ]);
            db()->insert(':photo_album_info', array('album_id' => $iAlbumId));
        }

        $bDirectlyPublic = $bForcePublic || !Phpfox::getUserParam('photo.photo_must_be_approved');
        if ($bDirectlyPublic) {
            db()->update(':photo', ['is_cover' => 0], 'album_id=' . (int)$iAlbumId);
            db()->update(':photo', [
                'album_id'         => $iAlbumId,
                'is_cover'         => 1,
                'is_profile_photo' => 0,
                'view_id' => 0,
            ], 'photo_id=' . (int)$iPhotoId);
        } else {
            $pendingCoverPhotoKey = $this->getFacade()->getItemType() . '_cover_photo_pending_' . $iGroupId;
            $cachedItems = db()->select('cache_data')
                ->from(':cache')
                ->where([
                    'file_name' => $pendingCoverPhotoKey,
                    'cache_data' => ['like' => '%"album_id":"' . $iAlbumId . '"%']
                ])->executeRows(false);
            if (!empty($cachedItems)) {
                $oldCoverPhotoIds = [];
                foreach ($cachedItems as $cacheItem) {
                    $data = json_decode($cacheItem['cache_data'], true);
                    if (!empty($data['photo_id'])) {
                        $oldCoverPhotoIds[] = $data['photo_id'];
                    }
                }
                if (!empty($oldCoverPhotoIds) && db()->update(':photo', ['album_id' => $iAlbumId], ['photo_id' => ['in' => implode(',', $oldCoverPhotoIds)]])) {
                    foreach ($oldCoverPhotoIds as $oldCoverPhotoId) {
                        storage()->set('photo_no_feed_' . $oldCoverPhotoId, 1);
                    }
                }
            }
            storage()->del($pendingCoverPhotoKey);
            storage()->set($pendingCoverPhotoKey, [
                'album_id' => $iAlbumId,
                'photo_id' => $iPhotoId,
            ]);
        }

        if ($bDirectlyPublic) {
            Phpfox::getService('photo.album.process')->updateCounter((int)$iAlbumId, 'total_photo');
        }
    }
}
