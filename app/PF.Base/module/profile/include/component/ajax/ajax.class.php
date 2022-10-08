<?php

defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Profile_Component_Ajax_Ajax
 */
class Profile_Component_Ajax_Ajax extends Phpfox_Ajax
{
    public function logo()
    {
        $this->setTitle(_p('cover_photo'));
        $aParams = array(
            'page_id' => $this->get('page_id'),
            'groups_id' => $this->get('groups_id')
        );

        Phpfox::getBlock('profile.cover', $aParams);
    }

    public function updateProfilePhoto()
    {
        Phpfox::getBlock('user.profile-photo');
    }

    public function getTempProfileImage()
    {
        $profileImagePath = Phpfox::getService('core.temp-file')->getProfile();
        if (!$profileImagePath) {
            $profileImage = $this->get('profile_image');
            if (empty($profileImage)) {
                return;
            }
            $profileImageUrl = Phpfox::getService('profile.process')->saveTempFileToLocalServer($profileImage);
        }
        else {
            $profileImageUrl = Phpfox::getParam('core.url_file_temp') . $profileImagePath;
        }
        // response
        $this->call('$Core.ProfilePhoto.cropProfilePhoto("' . $profileImageUrl . '");');
    }
}
