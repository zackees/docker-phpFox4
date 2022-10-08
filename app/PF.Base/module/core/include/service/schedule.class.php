<?php
defined('PHPFOX') or exit('NO DICE!');

class Core_Service_Schedule extends Phpfox_Service
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('schedule');
    }

    public function addQueueScheduleItems()
    {
        $aSchedule = $this->database()->select('schedule_id')
            ->from($this->_sTable)
            ->where('is_temp = 0 AND time_schedule <= ' . PHPFOX_TIME)
            ->order('time_schedule ASC')
            ->execute('getSlaveRows');

        foreach ($aSchedule as $key => $val) {
            Phpfox_Queue::instance()->addJob('core_schedule_queue', [
                'schedule_id' => $val['schedule_id'],
            ], null, 3600);
        }
    }

    public function scheduleItem($iUserId, $sItemType, $sModule, $aVals, $iIsTemp = 0)
    {
        $aVals['is_schedule'] = true;
        if ($sItemType == 'user_status' && Phpfox::getLib('parse.format')->isEmpty($aVals['user_status'])) {
            if (empty($aVals['no_check_empty_user_status'])) {
                return Phpfox_Error::set(_p('add_some_text_to_share'));
            }
        }
        if (!Phpfox::hasCallback($sItemType, 'addScheduleItemToFeed') || !Phpfox::getParam('feed.enable_schedule_feed')) {
            return Phpfox_Error::set(_p('opps_something_went_wrong'));
        }
        $iScheduleTime = $this->validateScheduleTime($aVals);
        if (!$iScheduleTime) {
            return false;
        }
        $aVals['schedule_timestamp'] = (int)$iScheduleTime;
        (($sPlugin = Phpfox_Plugin::get('core.service_schedule_schedule_item_start')) ? eval($sPlugin) : null);
        $aInsert = [
            'user_id'       => $iUserId,
            'item_type'     => $sItemType,
            'module_id'     => $sModule,
            'data'          => serialize($aVals),
            'time_stamp'    => PHPFOX_TIME,
            'time_schedule' => (int)$iScheduleTime,
            'is_temp'       => $iIsTemp
        ];
        $iId = db()->insert($this->_sTable, $aInsert);
        if (Phpfox::hasCallback($sItemType, 'onScheduleItemToQueue')) {
            Phpfox::callback($sItemType . '.onScheduleItemToQueue', array_merge($aInsert, ['schedule_id' => $iId]));
        }
        (($sPlugin = Phpfox_Plugin::get('core.service_schedule_schedule_item_end')) ? eval($sPlugin) : null);

        return $iId;
    }

    public function redefineScheduleItem($iScheduleId, $aVals)
    {
        $aScheduleItem = db()->select('*')
            ->from($this->_sTable)
            ->where(['schedule_id' => (int)$iScheduleId])
            ->execute('getSlaveRow');

        $iRedefinedId = db()->insert($this->_sTable, [
            'user_id'       => $aScheduleItem['user_id'],
            'item_type'     => $aScheduleItem['item_type'],
            'module_id'     => $aScheduleItem['module_id'],
            'data'          => serialize($aVals),
            'time_stamp'    => PHPFOX_TIME,
            'time_schedule' => $aScheduleItem['time_schedule'],
            'is_temp'       => 0,
        ]);

        if ($iRedefinedId) {
            db()->delete($this->_sTable, ['schedule_id' => (int)$iScheduleId]);
        }
    }

    public function getScheduleItems(&$iCount, $iPage = 1, $iLimit = 10, $iUserId = null, $aExtraCondition = [])
    {
        $aResults = [];
        if (!$iUserId) {
            $iUserId = Phpfox::getUserId();
        }
        $aCondition = [
            'item.user_id' => (int)$iUserId,
            'AND item.time_schedule > ' . PHPFOX_TIME
        ];
        if (is_array($aExtraCondition)) {
            $aCondition = array_merge($aCondition, $aExtraCondition);
        }
        $iCount = db()->select('COUNT(*)')
            ->from($this->_sTable, 'item')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = item.user_id')
            ->order('item.time_schedule ASC')
            ->where($aCondition)
            ->execute('getField');

        $aRows = [];
        if ($iCount) {
            $aRows = db()->select('item.*, ' . Phpfox::getUserField())
                ->from($this->_sTable, 'item')
                ->join(Phpfox::getT('user'), 'u', 'u.user_id = item.user_id')
                ->order('item.time_schedule ASC')
                ->where($aCondition)
                ->limit($iPage, $iLimit, $iCount)
                ->execute('getSlaveRows');
        }
        foreach ($aRows as $iKey => $aRow) {
            $aRow['data'] = unserialize($aRow['data']);
            if (Phpfox::hasCallback($aRow['item_type'], 'getAdditionalScheduleInfo')) {
                $aResults[] = array_merge($aRow, (array)Phpfox::callback($aRow['item_type'] . '.getAdditionalScheduleInfo', $aRow));
            } else {
                $aResults[] = array_merge($aRow, [
                    'item_title' => ''
                ]);
            }
        }

        return $aResults;
    }

    public function getScheduleItem($iScheduleId, $bGetDetail = false)
    {
        $aSchedule = db()->select('s.*')
            ->from($this->_sTable, 's')
            ->join(':user', 'u', 'u.user_id = s.user_id')
            ->where([
                's.schedule_id' => $iScheduleId
            ])
            ->executeRow();
        if (empty($aSchedule)) {
            return false;
        }
        $aSchedule['data'] = unserialize($aSchedule['data']);
        if ($bGetDetail) {
            if (Phpfox::hasCallback($aSchedule['item_type'], 'getExtraScheduleData')) {
                $aSchedule = array_merge($aSchedule, Phpfox::callback($aSchedule['item_type'] . '.getExtraScheduleData', $aSchedule));
            }
            $aSchedule['data']['raw_schedule_time'] = Phpfox::getTime('m/d/Y - H:i', $aSchedule['time_schedule']);
            $iScheduleTime = Phpfox::getLib('date')->convertFromGmt($aSchedule['time_schedule']);

            $aSchedule['data']['schedule_month'] = date('n', $iScheduleTime);
            $aSchedule['data']['schedule_day'] = date('j', $iScheduleTime);
            $aSchedule['data']['schedule_year'] = date('Y', $iScheduleTime);
            $aSchedule['data']['schedule_hour'] = date('H', $iScheduleTime);
            $aSchedule['data']['schedule_minute'] = date('i', $iScheduleTime);
        }
        return $aSchedule;
    }

    public function updateScheduleItem($aVals)
    {
        $iScheduleId = (int)$aVals['schedule_id'];
        $aSchedule = $this->getScheduleItem($iScheduleId);
        if (empty($aSchedule)) {
            return Phpfox_Error::set(_p('this_scheduled_item_not_exist'));
        }
        if (in_array($aSchedule['item_type'], ['user_status', 'link']) && Phpfox::getLib('parse.format')->isEmpty($aVals['user_status'])) {
            if (empty($aVals['no_check_empty_user_status'])) {
                return Phpfox_Error::set(_p('add_some_text_to_share'));
            }
        }
        $bConfirmSchedule = isset($aVals['confirm_scheduled']) && (int)$aVals['confirm_scheduled'] == 1;
        $data = $aSchedule['data'];
        if (Phpfox::hasCallback($aSchedule['item_type'], 'onUpdateScheduleItem')) {
            $data = Phpfox::callback($aSchedule['item_type'] . '.onUpdateScheduleItem', $data, $aVals);
        } else {
            $data = array_merge($data, $aVals);
        }
        if (!$data || !Phpfox_Error::isPassed()) {
            return false;
        }
        $updateVals = [];
        switch ($aSchedule['item_type']) {
            case 'user_status':
                if (!empty($aVals['link'])) {
                    $updateVals['item_type'] = 'link';
                    $updateVals['module_id'] = 'link';
                }
                break;
            case 'link':
                if (empty($aVals['link'])) {
                    unset($data['link']);
                    $updateVals['item_type'] = 'user_status';
                    $updateVals['module_id'] = 'user';
                }
                break;
            default:
                (($sPlugin = Phpfox_Plugin::get('core_server_schedule_update_schedule_item_switch')) ? eval($sPlugin) : null);
                break;
        }
        $updateVals['data'] = serialize($data);
        if ($bConfirmSchedule) {
            $iScheduleTime = $this->validateScheduleTime($aVals);
            $data['schedule_timestamp'] = (int)$iScheduleTime;
            if (!$iScheduleTime) {
                return false;
            }
            $updateVals['time_schedule'] = $iScheduleTime;
        }
        return db()->update($this->_sTable, $updateVals, ['schedule_id' => $iScheduleId]);
    }

    public function deleteScheduleItem($iScheduleId)
    {
        $aSchedule = $this->getScheduleItem($iScheduleId);
        if (!empty($aSchedule)) {
            if (Phpfox::hasCallback($aSchedule['item_type'], 'onDeleteScheduleItem')) {
                Phpfox::callback($aSchedule['item_type'] . '.onDeleteScheduleItem', $aSchedule['data']);
            }
            db()->delete(':schedule', ['schedule_id' => $iScheduleId]);
        } else {
            return Phpfox_Error::set(_p('this_scheduled_item_not_exist'));
        }
        return true;
    }

    public function validateScheduleTime($aVals, $bThrowError = true)
    {
        if (empty($aVals['schedule_hour']) || empty($aVals['schedule_minute'])
            || empty($aVals['schedule_month']) || empty($aVals['schedule_day']) || empty($aVals['schedule_year'])) {
            return $bThrowError ? Phpfox_Error::set(_p('invalid_schedule_time')) : false;
        }
        $iScheduleTime = Phpfox::getLib('date')->mktime($aVals['schedule_hour'], $aVals['schedule_minute'], 0, $aVals['schedule_month'], $aVals['schedule_day'], $aVals['schedule_year']);
        $iScheduleTime = Phpfox::getLib('date')->convertToGmt($iScheduleTime);
        if ($iScheduleTime <= PHPFOX_TIME) {
            return $bThrowError ? Phpfox_Error::set(_p('you_cant_schedule_in_the_past')) : false;
        }
        return $iScheduleTime;
    }

    public function sendNowScheduleItem($iScheduleId)
    {
        $aSchedule = $this->getScheduleItem($iScheduleId);
        if (!empty($aSchedule) && empty($aSchedule['is_temp']) && Phpfox::hasCallback($aSchedule['item_type'], 'addScheduleItemToFeed')) {
            $aSchedule['data']['schedule_timestamp'] = PHPFOX_TIME;
            Phpfox::callback($aSchedule['item_type'] . '.addScheduleItemToFeed', $aSchedule['data']);
            db()->delete(':schedule', ['schedule_id' => $iScheduleId]);
        } else {
            return false;
        }
        return true;
    }
}
