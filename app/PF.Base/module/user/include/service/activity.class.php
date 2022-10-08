<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Service_Activity
 */
class User_Service_Activity extends Phpfox_Service
{
    /**
     * @var string
     */
    protected $_sTable = '';

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('user_activity');
    }

    public function getTop($sCategory)
    {
        return Phpfox::hasCallback($sCategory, 'getTopUsers') ? Phpfox::callback($sCategory . '.getTopUsers') : null;
    }

    public function update($iUserId, $sType, $sMethod = '+', $iCnt = 0, $updatePoint = true)
    {
        if (!$iUserId) {
            return false;
        }

        if ($sMethod != '+' && $sMethod != '-') {
            return Phpfox_Error::trigger('Invalid activity method: ' . $sMethod);
        }

        $sModule = $sType;
        $sModuleExtra = null;
        if (preg_match('/(.*)_(.*)/i', $sModule, $aMatches)) {
            $sModule = $aMatches[1];
        }

        $aTotalItemInfo = [];
        if (Phpfox::isModule($sModule) && Phpfox::hasCallback($sType, 'getTotalItemCount')) {
            $aTotalItemInfo = Phpfox::callback($sType . '.getTotalItemCount', $iUserId);
            if (isset($aTotalItemInfo['field'])) {
                $this->database()->select('uf.' . $aTotalItemInfo['field'] . ', ')->join(Phpfox::getT('user_field'), 'uf', 'uf.user_id = ua.user_id');
            }
        }

        $aRow = $this->database()->select("ua.activity_" . $sType . ", ua.activity_total")
            ->from($this->_sTable, 'ua')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = ua.user_id')
            ->where('ua.user_id = ' . (int)$iUserId)
            ->execute('getSlaveRow');

        $iTotal = 1;
        if ($iCnt) {
            $iTotal = ($iTotal * $iCnt);
        }

        if ($sMethod == '+') {
            $iItemTotal = ($aRow['activity_' . $sType] + $iTotal);
            $iTotal = ($aRow['activity_total'] + $iTotal);
        } else {
            $iItemTotal = ($aRow['activity_' . $sType] - $iTotal);
            $iTotal = ($aRow['activity_total'] - $iTotal);

            if ($iItemTotal < 0) {
                $iItemTotal = 0;
            }

            if ($iTotal < 0) {
                $iTotal = 0;
            }
        }

        $this->database()->update($this->_sTable, ['activity_' . $sType => (int)$iItemTotal, 'activity_total' => $iTotal], ['user_id' => (int)$iUserId]);

        if (!empty($aTotalItemInfo)) {
            if ($sMethod == '+' && isset($aTotalItemInfo['field'])) {
                $iNewFieldCount = ($aRow[$aTotalItemInfo['field']] + $iTotal);
            } elseif (isset($aTotalItemInfo['field'])) {
                $iNewFieldCount = ((int)$aRow[$aTotalItemInfo['field']] <= 0 ? 0 : ($aRow[$aTotalItemInfo['field']] - $iTotal));
            }

            if (isset($aTotalItemInfo['total'])) {
                $iNewFieldCount = $aTotalItemInfo['total'];
            }
            if (isset($iNewFieldCount)) {
                $this->database()->update(Phpfox::getT('user_field'), [$aTotalItemInfo['field'] => $iNewFieldCount], 'user_id = ' . (int)$iUserId);
            }
        }

        // update points
        if (Phpfox::isAppActive('Core_Activity_Points') && $updatePoint) {
            Phpfox::getService('activitypoint.process')->updatePoints($iUserId, $sType, $sMethod, $iCnt);
        }

        (($sPlugin = Phpfox_Plugin::get('user.service_activity_update')) ? eval($sPlugin) : false);

        return true;
    }

    /**
     * @param $iTrgUser
     * @param $iAmount
     * @return bool
     * @throws Exception
     */
    public function doGiftPoints($iTrgUser, $iAmount)
    {
        if (!Phpfox::isAppActive('Core_Activity_Points')) {
            return Phpfox_Error::set(_p('unfortunately_you_do_not_have_enough_points_to_gift_away'));
        }
        Phpfox::getUserParam('activitypoint.can_gift_activity_points', true);
        $iAmount = (int)$iAmount;
        // How many points do we have?
        if ((Phpfox::getUserBy('activity_points') < 1) || ($iAmount > Phpfox::getUserBy('activity_points'))) {
            return Phpfox_Error::set(_p('unfortunately_you_do_not_have_enough_points_to_gift_away'));
        } else if (Phpfox::getUserBy('activity_points') == 1) {
            $iAmount = 1;
        }
        $iAmount = abs($iAmount);
        // Get current values
        $aValues = $this->database()->select('activity_points, user_id, activity_points_gifted')
            ->from(Phpfox::getT('user_activity'))
            ->where('user_id = ' . Phpfox::getUserId() . ' OR user_id = ' . (int)$iTrgUser)
            ->execute('getSlaveRows');

        foreach ($aValues as $aValue) {
            if ($aValue['user_id'] == Phpfox::getUserId()) {
                $aSender = $aValue;
                continue;
            } else if ($aValue['user_id'] == $iTrgUser) {
                $aReceiver = $aValue;
                continue;
            }
        }

        if (!isset($aSender) || !isset($aReceiver)) {
            return Phpfox_Error::set(_p('invalid_transaction'));
        }

        // Substract points
        $this->database()->update(Phpfox::getT('user_activity'), ['activity_points' => ($aSender['activity_points'] - ((int)$iAmount))], 'user_id = ' . Phpfox::getUserId());
        // Add points
        $this->database()->update(Phpfox::getT('user_activity'), ['activity_points' => ($aReceiver['activity_points'] + ((int)$iAmount))], 'user_id = ' . (int)$iTrgUser);

        Phpfox::getService('activitypoint.process')->doGiftPoints(Phpfox::getUserId(), $iTrgUser, $iAmount);

        // Notification
        Phpfox::getService('notification.process')->add('user_GiftPoint', $iAmount, $iTrgUser, Phpfox::getUserId());
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
        if ($sPlugin = Phpfox_Plugin::get('user.service_activity__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}
