<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Profile_Service_Process
 */
class Profile_Service_Process extends Phpfox_Service
{
    public function saveTempFileToLocalServer($profileImage, $userId = null, $forPending = false)
    {
        if (!$userId) {
            $userId = Phpfox::getUserId();
        }

        if (!$forPending) {
            $profileImagePath = Phpfox::getService('core.temp-file')->getProfile($userId);
            if($profileImagePath) {
                @unlink(Phpfox::getParam('core.dir_file_temp') . $profileImagePath);
            }
        }

        $pendingTempFileCacheId = 'user_pending_temp_file_cache_' . $userId;

        if (empty($profileImage)) {
            $cache = storage()->get($pendingTempFileCacheId);
            if (empty($cache) || empty($cache->value)) {
                return false;
            }
            $profileImage = Phpfox::getParam('core.dir_file_temp') . $cache->value;
            if (!$forPending) {
                storage()->del($pendingTempFileCacheId);
            }
        }

        $aProfileImage = explode('.', $profileImage);
        $tempProfile = 'temp_' . ($forPending ? 'pending_' : '') . 'profile_image_' . uniqid($userId) . '.' . end($aProfileImage);
        $tempFile = Phpfox::getParam('core.dir_file_temp') . $tempProfile;

        if (!is_dir(Phpfox::getParam('core.dir_file_temp'))) {
            Phpfox_File::instance()->mkdir(Phpfox::getParam('core.dir_file_temp'), true, 777);
        }
        Phpfox_File::instance()->write($tempFile, fox_get_contents($profileImage));

        if ($forPending) {
            storage()->del($pendingTempFileCacheId);
            storage()->set($pendingTempFileCacheId, $tempProfile);
        } else {
            if ($profileImagePath) {
                Phpfox::getService('core.temp-file')->updateProfile([
                    'size'      => filesize($tempFile),
                    'path'      => str_replace(Phpfox::getParam('core.dir_file_temp'), '', $tempFile),
                    'user_id'   => $userId
                ]);
            } else {
                Phpfox::getService('core.temp-file')->add([
                    'type'      => 'profile',
                    'size'      => filesize($tempFile),
                    'path'      => str_replace(Phpfox::getParam('core.dir_file_temp'), '', $tempFile),
                    'user_id'   => $userId,
                    'server_id' => 1
                ]);
            }
        }

        return str_replace(Phpfox::getParam('core.dir_file_temp'), Phpfox::getParam('core.url_file_temp'), $tempFile);
    }

    /**
     * @param $sMethod
     * @param $aArguments
     *
     * @return null
     */
    public function __call($sMethod, $aArguments)
    {
        /**
         * Check if such a plug-in exists and if it does call it.
         */
        if ($sPlugin = Phpfox_Plugin::get('profile.service_process__call')) {
            eval($sPlugin);

            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}
