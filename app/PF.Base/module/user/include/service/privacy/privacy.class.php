<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Service_Privacy_Privacy
 */
class User_Service_Privacy_Privacy extends Phpfox_Service
{
    public function getUserPrivacy($iUserId = null)
    {
        if (empty($iUserId)) {
            $iUserId = Phpfox::getUserId();
        }
        $cacheId = $this->cache()->set('user_privacy_' . $iUserId);
        if (false === ($aPrivacy = $this->cache()->get($cacheId))) {
            $aPrivacy = [];
            $aRows = $this->database()->select('user_privacy, user_value')
                ->from(Phpfox::getT('user_privacy'))
                ->where('user_id = ' . (int)$iUserId)
                ->execute('getSlaveRows');
            foreach ($aRows as $aRow) {
                $aPrivacy[$aRow['user_privacy']] = $aRow['user_value'];
            }
            $this->cache()->save($cacheId, $aPrivacy);
        }
        return $aPrivacy;
    }

    public function getUserNotifications($iUserId = null, $bGetSms = false)
    {
        if (empty($iUserId)) {
            $iUserId = Phpfox::getUserId();
        }
        $cacheId = $this->cache()->set(($bGetSms ? 'user_sms_notification_' : 'user_notification_') . $iUserId);
        if (false === ($aNotifications = $this->cache()->get($cacheId))) {
            $aNotifications = [];
            $aRows = $this->database()->select('user_notification')
                ->from(Phpfox::getT('user_notification'))
                ->where([
                    'user_id' => (int)$iUserId,
                    'notification_type' => $bGetSms ? 'sms' : 'email'
                ])
                ->execute('getSlaveRows');

            foreach ($aRows as $aRow) {
                $aNotifications[$aRow['user_notification']] = true;
            }
            $this->cache()->save($cacheId, $aNotifications);
        }
        return $aNotifications;
    }

    public function getUserSettings($iUserId = null)
    {
        if ($iUserId !== null && $iUserId != Phpfox::getUserId()) {
            Phpfox::getUserParam('user.can_edit_other_user_privacy', true);
        }

        $aNotifications = $this->getUserNotifications($iUserId);
        $aSmsNotifications = $this->getUserNotifications($iUserId, true);
        $aPrivacy = $this->getUserPrivacy($iUserId);

        return [
            'notification' => $aNotifications,
            'privacy'      => $aPrivacy,
            'sms_notification' => $aSmsNotifications
        ];
    }

    /**
     * @param int $iUserId
     * @param array $aProfiles
     * @return array
     */
    private function _getDefaultUserPrivacy($iUserId, $aProfiles = null)
    {
        if ($aProfiles == null) {
            $aProfiles = Phpfox::massCallback('getProfileSettings');
        }
        foreach ($aProfiles as $sModule => $aModules) {
            foreach ($aModules as $sKey => $aProfile) {
                if (isset($aProfiles['privacy'][$sKey])) {
                    $aProfiles[$sModule][$sKey]['default'] = $aProfiles['privacy'][$sKey];
                } else {
                    $aProfiles[$sModule][$sKey]['default'] = (isset($aProfiles[$sModule][$sKey]['default']) ? $aProfiles[$sModule][$sKey]['default'] : 0);
                }
            }
        }
        $aReturn = [];
        $bIsFriendOnly = Phpfox::getParam('core.friends_only_community');
        foreach ($aProfiles as $aModules) {
            foreach ($aModules as $sPrivacy => $aProfile) {
                $aItem = [
                    'user_id'      => $iUserId,
                    'user_privacy' => $sPrivacy
                ];
                if (!isset($aProfile['anyone']) && !$bIsFriendOnly) {
                    $aItem['user_value'] = 0;
                } else if (!isset($aProfile['no_user'])) {
                    if (!isset($aProfile['friend_only']) && (!$bIsFriendOnly || !empty($aProfile['ignore_friend_only']))) {
                        $aItem['user_value'] = 1;
                    } elseif (Phpfox::isModule('friend')) {
                        if (!isset($aProfile['friend']) || $aProfile['friend']) {
                            $aItem['user_value'] = 2;
                        } elseif (!empty($aProfile['friend_of_friend'])) {
                            $aItem['user_value'] = 3;
                        }
                    }
                } else {
                    $aItem['user_value'] = 4;
                }
                if (!isset($aItem['user_value'])) {
                    $aItem['user_value'] = 4;
                }
                if (isset($aProfile['converted_default_value']) && isset($aProfile['converted_default_value'][$aItem['user_value']])) {
                    $aItem['user_value'] = $aProfile['converted_default_value'][$aItem['user_value']];
                }
                $aReturn[] = $aItem;
            }
        }
        return $aReturn;
    }

    public function get($iUserId = null)
    {
        $aEnableNotification = [
            'core' => [
                'core.enable_notifications' => [
                    'phrase' => _p('enable_email_notifications'),
                    'default' => 1
                ]
            ]
        ];
        $aEnableSmsNotification = [
            'core' => [
                'core.enable_notifications' => [
                    'phrase' => _p('enable_sms_notifications'),
                    'default' => 1
                ]
            ]
        ];
        $aUserPrivacy = Phpfox::getService('user.privacy')->getUserSettings($iUserId);
        $aNotifications = $aEnableNotification + Phpfox::massCallback('getNotificationSettings');
        $aSmsNotifications = $aEnableSmsNotification + Phpfox::massCallback('getNotificationSettings');
        $aProfiles = Phpfox::massCallback('getProfileSettings');
        $aItems = Phpfox::massCallback('getGlobalPrivacySettings');

        if (is_array($aNotifications)) {
            foreach ($aNotifications as $sModule => $aModules) {
                if (!is_array($aModules)) {
                    continue;
                }
                foreach ($aModules as $sKey => $aNotification) {
                    if (isset($aUserPrivacy['notification'][$sKey])) {
                        $aNotifications[$sModule][$sKey]['default'] = 0;
                    }
                }
            }
            foreach ($aSmsNotifications as $sModule => $aModules) {
                if (!is_array($aModules)) {
                    continue;
                }
                foreach ($aModules as $sKey => $aNotification) {
                    if (isset($aUserPrivacy['sms_notification'][$sKey])) {
                        $aSmsNotifications[$sModule][$sKey]['default'] = 0;
                    }
                }
            }
        }

        $aDefaultUserPrivacy = $this->_getDefaultUserPrivacy($iUserId, $aProfiles);
        $aDefaultUserPrivacy = array_combine(array_column($aDefaultUserPrivacy, 'user_privacy'), array_column($aDefaultUserPrivacy, 'user_value'));

        foreach ($aProfiles as $sModule => $aModules) {
            foreach ($aModules as $sKey => $aProfile) {
                if (isset($aUserPrivacy['privacy'][$sKey])) {
                    $aProfiles[$sModule][$sKey]['default'] = $aUserPrivacy['privacy'][$sKey];
                } else {
                    $aProfiles[$sModule][$sKey]['default'] = (isset($aDefaultUserPrivacy[$sKey]) ? $aDefaultUserPrivacy[$sKey] : 0);
                }
            }
        }

        // reorder profile to top
        $profileModule = $aProfiles['profile'];
        unset($aProfiles['profile']);
        $aProfiles = array_merge(['profile' => $profileModule], $aProfiles);

        foreach ($aItems as $sModule => $aModules) {
            foreach ($aModules as $sKey => $aItem) {
                $aItems[$sModule][$sKey]['custom_id'] = str_replace('.', '_', $sKey);
            }
        }

        /* Reminder for purefan add a hook here */
        if ($sPlugin = Phpfox_Plugin::get('user.service_privacy_privacy_get')) {
            eval($sPlugin);
        }
        return [
            $aUserPrivacy,
            $aNotifications,
            $aProfiles,
            $aItems,
            $aSmsNotifications
        ];
    }

    public function hasAccess($iUserId, $sPrivacy, $bRedirect = false)
    {
        static $aPrivacy = [];
        static $aIsFriend = [];
        static $aIsFriendOfFriend = [];
        static $aUserAge = [];

        if (Phpfox::getUserParam('user.can_override_user_privacy')) {
            return true;
        }

        $iCurrentUserId = Phpfox::getUserId();
        if ($iUserId == $iCurrentUserId) {
            return true;
        }

        $iUserAgeLimit = Phpfox::getParam('user.user_profile_private_age');

        if ($iUserAgeLimit > 0) {
            if (!isset($aUserAge[$iUserId])) {
                $aUserAge[$iUserId] = (int)Phpfox::getService('user')->age($this->database()->select('birthday')->from(Phpfox::getT('user'))->where('user_id = ' . (int)$iUserId)->execute('getSlaveField'));
            }

            if ($aUserAge[$iUserId] < $iUserAgeLimit) {
                if (!Phpfox::isUser()) {
                    return false;
                }

                if (!isset($aIsFriend[$iUserId][$iCurrentUserId]) && Phpfox::isModule('friend')) {
                    $aIsFriend[$iUserId][$iCurrentUserId] = Phpfox::getService('friend')->isFriend($iUserId, $iCurrentUserId);
                }

                return $aIsFriend[$iUserId][$iCurrentUserId];
            }
        }

        $bPass = true;
        if (!isset($aPrivacy[$iUserId])) {
            $aSettings = $this->database()->select('user_id, user_privacy, user_value')
                ->from(Phpfox::getT('user_privacy'))
                ->where('user_id = ' . (int)$iUserId)
                ->execute('getSlaveRows');
            if (empty($aSettings)) {
                $aSettings = $aPrivacy[$iUserId] = $this->_getDefaultUserPrivacy($iUserId);
            }
            foreach ($aSettings as $aSetting) {
                $aPrivacy[$aSetting['user_id']][$aSetting['user_privacy']] = $aSetting['user_value'];
            }
        }

        if (isset($aPrivacy[$iUserId][$sPrivacy])) {
            switch ($aPrivacy[$iUserId][$sPrivacy]) {
                // Network (Logged in users)
                case '1':
                    if (!Phpfox::isUser()) {
                        $bPass = false;
                    }
                    break;
                // Friends Only
                case '2':
                    if (!Phpfox::isUser()) {
                        $bPass = false;
                    } else {
                        if (!isset($aIsFriend[$iUserId][$iCurrentUserId]) && Phpfox::isModule('friend')) {
                            $aIsFriend[$iUserId][$iCurrentUserId] = Phpfox::getService('friend')->isFriend($iUserId, $iCurrentUserId);
                        }

                        if (isset($aIsFriend[$iUserId]) && !$aIsFriend[$iUserId][$iCurrentUserId]) {
                            $bPass = false;
                        }
                    }
                    break;
                // Friends of friends
                case '3':
                    if (!Phpfox::isUser()) {
                        $bPass = false;
                    } else {
                        if (!isset($aIsFriend[$iUserId][$iCurrentUserId]) && Phpfox::isModule('friend')) {
                            $aIsFriend[$iUserId][$iCurrentUserId] = Phpfox::getService('friend')->isFriend($iUserId, $iCurrentUserId);
                        }
                        if (!isset($aIsFriendOfFriend[$iUserId][$iCurrentUserId]) && Phpfox::isModule('friend')) {
                            $aIsFriendOfFriend[$iUserId][$iCurrentUserId] = Phpfox::getService('friend')->isFriendOfFriend($iUserId);
                        }
                        if (isset($aIsFriend[$iUserId]) && !$aIsFriend[$iUserId][$iCurrentUserId] && isset($aIsFriendOfFriend[$iUserId]) && !$aIsFriendOfFriend[$iUserId][$iCurrentUserId]) {
                            $bPass = false;
                        }
                    }
                    break;
                    break;
                // No one
                case '4':
                    $bPass = false;
                    break;
            }
        }

        if (Phpfox::getService('user.block')->isBlocked($iUserId, $iCurrentUserId)) {
            $bPass = false;
        }

        return $bPass;
    }

    public function getValue($sVar)
    {
        static $aPrivacy = [];

        $iUserId = Phpfox::getUserId();

        if (!isset($aPrivacy[$iUserId])) {
            $aSettings = $this->database()->select('user_id, user_privacy, user_value')
                ->from(Phpfox::getT('user_privacy'))
                ->where('user_id = ' . (int)$iUserId)
                ->execute('getSlaveRows');

            if (empty($aSettings)) {
                $aPrivacy[$iUserId] = [];
            } else {
                foreach ($aSettings as $aSetting) {
                    $aPrivacy[$aSetting['user_id']][$aSetting['user_privacy']] = $aSetting['user_value'];
                }
            }
        }

        return (int)(isset($aPrivacy[$iUserId][$sVar]) ? $aPrivacy[$iUserId][$sVar] : 0);
    }

    /**
     * If a call is made to an unknown method attempt to connect
     * it to a specific plug-in with the same name thus allowing
     * plug-in developers the ability to extend classes.
     *
     * @param string $sMethod    is the name of the method
     * @param array  $aArguments is the array of arguments of being passed
     */
    public function __call($sMethod, $aArguments)
    {
        /**
         * Check if such a plug-in exists and if it does call it.
         */
        if ($sPlugin = Phpfox_Plugin::get('user.service_privacy_privacy__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}