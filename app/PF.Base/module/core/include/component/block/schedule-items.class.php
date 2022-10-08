<?php
defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Block_Schedule_Items extends Phpfox_Component
{
    public function process()
    {
        Phpfox::isUser(true);
        $iPage = $this->getParam('page', 1);
        $iLimit = 10;
        $aSchedules = Phpfox::getService('core.schedule')->getScheduleItems($iCount, $iPage, $iLimit);
        $aParamsPager = array(
            'page' => $iPage,
            'size' => $iLimit,
            'count' => $iCount,
            'paging_mode' => 'pagination',
            'ajax_paging' => [
                'block' => 'core.schedule-items',
                'params' => [],
                'container' => '.js_manage_schedule_items'
            ]
        );
        $this->template()->assign([
            'aScheduleItems' => $aSchedules,
            'iPage' => $iPage,
            'bIsPaging' => $this->getParam('ajax_paging', 0)
        ]);
        Phpfox::getLib('pager')->set($aParamsPager);
        return 'block';
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('core.component_block_schedule_items_clean')) ? eval($sPlugin) : false);
    }
}
