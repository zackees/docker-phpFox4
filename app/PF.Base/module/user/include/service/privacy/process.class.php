<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Service_Privacy_Process
 */
class User_Service_Privacy_Process extends Phpfox_Service
{
    /**
     * Class constructor
     */
    public function __construct()
    {
    }

    public function update($aVals, $iUserId = null)
    {
        if ($iUserId !== null && $iUserId != Phpfox::getUserId()) {
            Phpfox::getUserParam('user.can_edit_other_user_privacy', true);
        } else {
            $iUserId = Phpfox::getUserId();
        }

        if (Phpfox::getUserParam('user.can_control_notification_privacy') && isset($aVals['notification'])) {
            $aUserNotifications = Phpfox::getService('user.privacy')->getUserNotifications($iUserId);
            foreach ($aVals['notification'] as $sVar => $iVal) {
                if (!$iVal) {
                    $this->database()->delete(Phpfox::getT('user_notification'), [
                            'user_id' => $iUserId,
                            'user_notification' => $sVar,
                            'notification_type' => 'email'
                        ]
                    );
                }
                elseif(!isset($aUserNotifications[$sVar])) {
                    $this->database()->insert(Phpfox::getT('user_notification'), [
                            'user_id' => $iUserId,
                            'user_notification' => $sVar,
                            'notification_type' => 'email'
                        ]
                    );
                }
            }
            // clear notification cache
            Phpfox_Cache::instance()->remove('user_notification_' . $iUserId);
        }

        if (Phpfox::getUserParam('user.can_control_notification_privacy') && Phpfox::getParam('core.enable_register_with_phone_number') && isset($aVals['sms_notification'])) {
            $aSmsNotification = Phpfox::getService('user.privacy')->getUserNotifications($iUserId, true);
            foreach ($aVals['sms_notification'] as $sVar => $iVal) {
                if (!$iVal) {
                    $this->database()->delete(Phpfox::getT('user_notification'), [
                            'user_id' => $iUserId,
                            'user_notification' => $sVar,
                            'notification_type' => 'sms'
                        ]
                    );
                }
                elseif(!isset($aSmsNotification[$sVar])) {
                    $this->database()->insert(Phpfox::getT('user_notification'), [
                            'user_id' => $iUserId,
                            'user_notification' => $sVar,
                            'notification_type' => 'sms'
                        ]
                    );
                }
            }
            // clear sms notification cache
            Phpfox_Cache::instance()->remove('user_sms_notification_' . $iUserId);
        }


        if (Phpfox::getUserParam('user.can_control_profile_privacy')) {
            $aUserPrivacy = Phpfox::getService('user.privacy')->getUserPrivacy($iUserId);
            if(isset($aVals['privacy'])) {
                foreach ($aVals['privacy'] as $sVar => $iVal) {
                    if(isset($aUserPrivacy[$sVar])) {
                        if($iVal) {
                            $this->database()->update(Phpfox::getT('user_privacy'),
                                [
                                    'user_value' => $iVal
                                ],
                                [
                                    'user_id' => $iUserId,
                                    'user_privacy' => $sVar
                                ]
                            );
                        }
                        else {
                            $this->database()->delete(Phpfox::getT('user_privacy'), [
                                    'user_id' => $iUserId,
                                    'user_privacy' => $sVar
                                ]
                            );
                        }
                    }
                    elseif($iVal) {
                        $this->database()->insert(Phpfox::getT('user_privacy'), array(
                                'user_id' => $iUserId,
                                'user_privacy' => $sVar,
                                'user_value' => $iVal
                            )
                        );
                    }
                }
            }

            foreach ($aVals as $sVar => $aVal) {
                if (!preg_match('/(.*)\.(.*)/', $sVar, $aMatches)) {
                    continue;
                }

                if (!isset($aMatches[1])) {
                    continue;
                }

                if (!Phpfox::isModule($aMatches[1])) {
                    continue;
                }

                if(isset($aUserPrivacy[$sVar])) {
                    $this->database()->update(Phpfox::getT('user_privacy'),
                        [
                            'user_value' => (int)$aVal[$sVar]
                        ],
                        [
                            'user_id' => $iUserId,
                            'user_privacy' => $sVar
                        ]
                    );
                }
                else {
                    $this->database()->insert(Phpfox::getT('user_privacy'), array(
                            'user_id' => $iUserId,
                            'user_privacy' => $sVar,
                            'user_value' => (int)$aVal[$sVar]
                        )
                    );
                }
            }
            // clear privacy cache
            Phpfox_Cache::instance()->remove('user_privacy_' . $iUserId);
        }

        if (isset($aVals['blocked'])) {
            foreach ($aVals['blocked'] as $iBlockId) {
                if (!is_numeric($iBlockId)) {
                    continue;
                }

                Phpfox::getService('user.block.process')->delete($iBlockId);
            }
        }

        if (isset($aVals['special'])) {
            if (isset($aVals['special']['dob_setting'])) {
                Phpfox::getService('user.field.process')->update($iUserId, 'dob_setting', (int)$aVals['special']['dob_setting']);
                $this->cache()->remove(array('udob', $iUserId));
            }
        }

        if (Phpfox::getUserParam('user.hide_from_browse') && isset($aVals['invisible'])) {
            $this->database()->update(Phpfox::getT('user'), array('is_invisible' => (int)$aVals['invisible']), 'user_id = ' . (int)$iUserId);
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
     *
     * @return null
     */
    public function __call($sMethod, $aArguments)
    {
        /**
         * Check if such a plug-in exists and if it does call it.
         */
        if ($sPlugin = Phpfox_Plugin::get('user.service_privacy_process__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}
