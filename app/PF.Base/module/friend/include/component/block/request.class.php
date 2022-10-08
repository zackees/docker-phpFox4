<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 *
 * @copyright       [PHPFOX_COPYRIGHT]
 * @author          phpFox LLC
 * @package         Module_Friend
 */
class Friend_Component_Block_Request extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $sError = false;
        $iUserId = $this->getParam('user_id');

        $aUser = Phpfox::getService('user')->getUser($iUserId, Phpfox::getUserField());

        if (Phpfox::getUserId() === $aUser['user_id']) {
            $sError = 'same_user';
        } elseif (Phpfox::getService('friend.request')->isRequested(Phpfox::getUserId(), $aUser['user_id'], false, true)) {
            $sError = 'already_asked';
        } elseif (Phpfox::getService('friend.request')->isRequested($aUser['user_id'], Phpfox::getUserId(), false, true)) {
            $sError = 'user_asked_already';
        } elseif (Phpfox::getService('friend')->isFriend(Phpfox::getUserId(), $aUser['user_id'])) {
            $sError = 'already_friends';
        }

        // get cover photo
        $iCoverId = db()->select('cover_photo')->from(':user_field')->where(['user_id' => $aUser['user_id']])->executeField();
        if ($iCoverId) {
            $aCoverPhoto = Phpfox::getService('photo')->getCoverPhoto($iCoverId);
            if (!empty($aCoverPhoto)) {
                $aUser['cover_photo_link'] = Phpfox::getLib('image.helper')->display([
                    'server_id' => $aCoverPhoto['server_id'],
                    'path' => 'photo.url_photo',
                    'file' => $aCoverPhoto['destination'],
                    'suffix' => '_500',
                    'return_url' => true
                ]);
            }
        }
        if (!isset($aUser['cover_photo_link'])) {
            $aUser['cover_photo_link'] = flavor()->active->default_photo('user_cover_default', true);
        }

        // count mutual friends
        list($iMutualCount,) = Phpfox::getService('friend')->getMutualFriends($aUser['user_id'], 1);

        // get one more info
        $sAdditionalInfo = '';
        if (Phpfox::getService('user.privacy')->hasAccess($aUser['user_id'], 'profile.view_location')
            && $sLocation = Phpfox::getPhraseT(Phpfox::getService('core.country')->getCountry($aUser['country_iso']), 'country')) {
            $sAdditionalInfo = $sLocation;
        }
        if (empty($sAdditionalInfo) && $sGender = Phpfox::getService('user')->gender($aUser['gender'])) {
            $sAdditionalInfo = $sGender;
        }
        if (empty($sAdditionalInfo) && $sBirthdayDisplay = Phpfox::getService('user')->getProfileBirthDate($aUser)) {
            $sAdditionalInfo = $sBirthdayDisplay;
        }
        if (empty($sAdditionalInfo) && $sRelationship = Phpfox::getService('custom')->getRelationshipPhrase($aUser)) {
            $sAdditionalInfo = $sRelationship;
        }

        $this->template()
            ->setPhrase([
                    'you_cannot_write_more_then_limit_characters',
                    'you_have_limit_character_s_left'
                ]
            )
            ->assign([
                    'aUser' => $aUser,
                    'sError' => $sError,
                    'aOptions' => Phpfox::getService('friend.list')->get(),
                    'bSuggestion' => ($this->request()->get('suggestion') ? true : false),
                    'bPageSuggestion' => ($this->request()->get('suggestion_page') ? true : false),
                    'bInvite' => ($this->request()->get('invite') ? true : false),
                    'iMutualCount' => $iMutualCount,
                    'sAdditionalInfo' => $sAdditionalInfo,
                    'isFriendRequestAjax' => $this->request()->get('friend_request_ajax')
                ]
            );
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('friend.component_block_request_clean')) ? eval($sPlugin) : false);
    }
}