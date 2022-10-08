<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Report_Component_Controller_Admincp_Index
 */
class Report_Component_Controller_Admincp_Index extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        if ($iId = $this->request()->getInt('view')) {
            if ($sRedirect = Phpfox::getService('report')->getRedirect($iId)) {
                $this->url()->forward($sRedirect);
            }
        }

        if ($aIds = $this->request()->getArray('id')) {
            if ($this->request()->get('ignore')) {
                foreach ($aIds as $iId) {
                    if (!is_numeric($iId)) {
                        continue;
                    }

                    Phpfox::getService('report.data.process')->ignore($iId);
                }

                $this->url()->send('admincp.report', null, _p('report_s_successfully_ignored'));
            } elseif ($this->request()->get('process')) {
                foreach ($aIds as $iId) {
                    if (!is_numeric($iId)) {
                        continue;
                    }

                    Phpfox::getService('report.data.process')->process($iId);
                }

                $this->url()->send('admincp.report', null, _p('report_s_successfully_processed'));
            }
        }

        $iPage = $this->request()->getInt('page');

        $aPages = [5, 10, 15, 20];
        $aDisplays = [];
        foreach ($aPages as $iPageCnt) {
            $aDisplays[$iPageCnt] = _p('per_page', ['total' => $iPageCnt]);
        }

        $aSorts = [
            'added' => _p('time')
        ];

        $aFilters = [
            'display' => [
                'type' => 'select',
                'options' => $aDisplays,
                'default' => '10'
            ],
            'sort' => [
                'type' => 'select',
                'options' => $aSorts,
                'default' => 'added',
                'alias' => 'rd'
            ],
            'sort_by' => [
                'type' => 'select',
                'options' => [
                    'DESC' => _p('descending'),
                    'ASC' => _p('ascending')
                ],
                'default' => 'DESC'
            ]
        ];

        $oSearch = Phpfox_Search::instance()->set([
                'type' => 'reports',
                'filters' => $aFilters,
                'search' => 'search'
            ]
        );

        $iLimit = $oSearch->getDisplay();

        list($iCnt, $aReports) = Phpfox::getService('report')->get($oSearch->getConditions(), $oSearch->getSort(), $oSearch->getPage(), $iLimit);

        Phpfox_Pager::instance()->set(['page' => $iPage, 'size' => $iLimit, 'count' => $oSearch->getSearchTotal($iCnt)]);

        $this->template()->setTitle(_p('reports'))
            ->setActiveMenu('admincp.maintain.report')
            ->setBreadCrumb(_p('apps'), $this->url()->makeUrl('admincp.apps'))
            ->setBreadCrumb(_p('reports'))
            ->assign([
                    'aReports' => $aReports
                ]
            );
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('report.component_controller_admincp_index_clean')) ? eval($sPlugin) : false);
    }
}