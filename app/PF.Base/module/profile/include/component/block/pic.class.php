<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Profile_Component_Block_Pic
 */
class Profile_Component_Block_Pic extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        if (!defined('PHPFOX_IS_USER_PROFILE') && !defined('PAGE_TIME_LINE')) {
            return false;
        }

        (($sPlugin = Phpfox_Plugin::get('profile.component_block_pic_start')) ? eval($sPlugin) : false);

        if (isset($bHideThisBlock)) {
            return false;
        }

        $aUser = $this->getParam('aUser');

        if ($aUser === null) {
            $aUser = $this->getParam('aPage');
            $aUser['user_image'] = $aUser['image_path'];
            foreach ($aUser as $sKey => $sValue) {
                if (strpos($sKey, 'owner_') !== false && $sKey != 'owner_user_image') {
                    $aUser[str_replace('owner_', '', $sKey)] = $sValue;
                }
            }
        }

        if (defined('PHPFOX_IS_PAGES_VIEW')) {
            $userId = $aUser['page_user_id'];
            $aUser['server_id'] = $aUser['image_server_id'];
            $aUser['full_name'] = $aUser['title'];
            $aUser['user_name'] = !empty($aUser['vanity_url']) ? $aUser['vanity_url'] : $aUser['title'];
            $aUser['user_group_id'] = 2;

            $this->template()->assign([
                    'aUser' => $aUser
                ]
            );
        } else {
            $userId = $aUser['user_id'];
        }

        $aUserInfo = [
            'title'      => $aUser['full_name'],
            'path'       => 'core.url_user',
            'file'       => $aUser['user_image'],
            'suffix'     => '_200_square',
            'max_width'  => 200,
            'no_default' => (Phpfox::getUserId() == $userId ? false : true),
            'thickbox'   => true,
            'class'      => 'profile_user_image',
            'no_link'    => true,
            'time_stamp' => true
        ];

        (($sPlugin = Phpfox_Plugin::get('profile.component_block_pic_process')) ? eval($sPlugin) : false);

        $sImage = Phpfox::getLib('image.helper')->display(array_merge([
            'user' => Phpfox::getService('user')->getUserFields(true, $aUser)
        ], $aUserInfo));
        if ($oAvatar = storage()->get('user/avatar/' . $userId)) {
            $aProfileImage = Phpfox::getService('photo')->getPhoto($oAvatar->value);
        }

        if (!empty($aProfileImage)) {
            $sPhotoUrl = Phpfox::getLib('image.helper')->display([
                'server_id'  => $aProfileImage['server_id'],
                'title'      => $aProfileImage['title'],
                'path'       => 'photo.url_photo',
                'file'       => $aProfileImage['destination'],
                'suffix'     => '',
                'no_default' => true,
                'return_url' => true,
            ]);
        }

        $this->template()->assign([
                'sProfileImage' => $sImage,
                'sPhotoUrl'     => isset($sPhotoUrl) ? $sPhotoUrl : '',
                'aProfileImage' => isset($aProfileImage) ? $aProfileImage : false,
                'iServerId'     => Phpfox::getUserBy('server_id')
            ]
        );


        $bCanSendPoke = Phpfox::isAppActive('Core_Poke')
            && PhpFox::getService('poke')->canSendPoke($userId)
            && Phpfox::getService('user.privacy')->hasAccess($userId, 'poke.can_send_poke');

        $isFriend = isset($aUser['is_friend']) && $aUser['is_friend'] === true;
        $bCanSendMessage = Phpfox::getService('user')->canSendMessage($userId, $isFriend);

        $aCoverPhoto = Phpfox::getService('photo')->getCoverPhoto($aUser['cover_photo']);

        $sRelationship = Phpfox::getService('custom')->getRelationshipPhrase($aUser, [], [], '', true);

        $this->template()->assign([
                'bCanPoke'            => $bCanSendPoke,
                'bCanSendMessage'     => $bCanSendMessage,
                'aCoverPhoto'         => $aCoverPhoto,
                'sCoverPhotoLink'     => !empty($aCoverPhoto['photo_id']) ? Phpfox::permalink('photo', $aCoverPhoto['photo_id'], Phpfox::getParam('photo.photo_show_title', 1) ? $aCoverPhoto['title'] : null) : null,
                'aUser'               => $aUser,
                'iCoverPhotoPosition' => intval($aUser['cover_photo_top']),
                'sCoverDefaultUrl'    => flavor()->active->default_photo('user_cover_default', true),
                'sRelationship'       => trim($sRelationship),
                'sModule'             => $this->request()->get('req2', '')
            ]
        );
        return null;
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('profile.component_block_pic_clean')) ? eval($sPlugin) : false);
    }
}
