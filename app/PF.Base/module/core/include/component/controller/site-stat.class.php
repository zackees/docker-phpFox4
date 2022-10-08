<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Controller_Site_Stat extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $aStats = [];

        $aOnline = Phpfox::getService('log.session')->getOnlineStats();

        $aStats[_p('online')] = [
            [
                'phrase' => _p('members'),
                'value'  => $aOnline['members'],
                'link'   => $this->url()->makeUrl('admincp.user.browse', ['view' => 'online'])
            ]

        ];

        $aPendingCallback = Phpfox::massCallback('pendingApproval');
        $aStats[_p('pending_approval')] = [];
        $iTotalApprove = 0;
        foreach ($aPendingCallback as $sModule => $aPendings) {
            if (isset($aPendings['value'])) {
                if (!$aPendings['value']) {
                    continue;
                }

                $iTotalApprove++;
                $aStats[_p('pending_approval')][] = $aPendings;
            } else {
                foreach ($aPendings as $sKey => $aValue) {
                    if (!$aValue['value']) {
                        continue;
                    }

                    $iTotalApprove++;
                    $aStats[_p('pending_approval')][] = $aValue;
                }
            }
        }

        if ($iTotalApprove === 0) {
            unset($aStats[_p('pending_approval')]);
        }

        if (Phpfox::isModule('report')) {
            $aReports = Phpfox::getService('report')->getActiveReports();
            if (count($aReports)) {
                $aStats[_p('reported_items_users')] = [
                    'view_all_link' => $this->url()->makeUrl('admincp.report'),
                    'items' => [],
                ];
                foreach ($aReports as $aReport) {
                    if (Core\Lib::phrase()->isPhrase($aReport['phrase'])) {
                        $aReport['phrase'] = _p($aReport['phrase']);
                    }
                    $aStats[_p('reported_items_users')]['items'][] = $aReport;
                }
            }
        }

        $aSpamCallback = Phpfox::massCallback('spamCheck');
        $aStats[_p('spam')] = [];
        $iTotalSpam = 0;
        foreach ($aSpamCallback as $sModule => $aSpam) {
            if (!$aSpam['value']) {
                continue;
            }

            $iTotalSpam++;
            $aStats[_p('spam')][] = $aSpam;
        }

        if ($iTotalSpam === 0) {
            unset($aStats[_p('spam')]);
        }

        $aSiteStats = Phpfox::getService('core.stat')->getTodaySiteStats();
        $aStats[_p('today_s_site_stats')] = [];
        $iTotalStats = 0;
        foreach ($aSiteStats as $sModule => $aValue) {
            if (!$aValue['value']) {
                continue;
            }

            $iTotalStats++;

            $aStats[_p('today_s_site_stats')][] = $aValue;
        }

        if ($iTotalStats === 0) {
            unset($aStats[_p('today_s_site_stats')]);
        }

        echo $this->template()
            ->assign([
                'aStats' => $aStats,
            ])
            ->getTemplate('core.controller.site-stat', true);
        exit;
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('core.component_controller_offline_clean')) ? eval($sPlugin) : false);
    }
}