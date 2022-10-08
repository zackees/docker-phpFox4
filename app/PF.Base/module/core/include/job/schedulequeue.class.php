<?php
defined('PHPFOX') or exit('NO DICE!');

class Core_Job_ScheduleQueue extends \Core\Queue\JobAbstract
{
    public function perform()
    {
        $aParams = $this->getParams();
        $iScheduleId = $aParams['schedule_id'];
        $aSchedule = Phpfox::getService('core.schedule')->getScheduleItem($iScheduleId);
        if (!empty($aSchedule) && Phpfox::hasCallback($aSchedule['item_type'], 'addScheduleItemToFeed')) {
            $aOwner = Phpfox::getService('user')->getUser($aSchedule['user_id']);
            Phpfox::getService('user.auth')->setUserId($aSchedule['user_id'], $aOwner);
            Phpfox::callback($aSchedule['item_type'] . '.addScheduleItemToFeed', $aSchedule['data']);
            db()->delete(':schedule', ['schedule_id' => $iScheduleId]);
            Phpfox::getService('user.auth')->setUserId(null, ['user_id' => 0]);
        }

        $this->delete();
    }
}