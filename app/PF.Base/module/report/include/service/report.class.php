<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 *
 *
 * @copyright        [PHPFOX_COPYRIGHT]
 * @author           phpFox LLC
 * @package          Module_Report
 * @version          $Id: report.class.php 2525 2011-04-13 18:03:20Z phpFox LLC $
 */
class Report_Service_Report extends Phpfox_Service
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('report');

        (($sPlugin = Phpfox_Plugin::get('report.service_report___construct')) ? eval($sPlugin) : false);
    }

    public function getCategories()
    {
        return $this->database()->select('*')
            ->from($this->_sTable)
            ->order('ordering ASC')
            ->execute('getSlaveRows');
    }

    /**
     * Get another categories
     *
     * @param $iReportId
     *
     * @return array
     */
    public function getAnotherCategories($iReportId)
    {
        return db()->select('report_id, message')->from($this->_sTable)->where("report_id != " . (int)$iReportId)->executeRows();
    }

    public function getForEdit($iId)
    {
        $aCategory = $this->database()->select('*')
            ->from($this->_sTable)
            ->where('report_id = ' . (int)$iId)
            ->execute('getSlaveRow');

        if (!isset($aCategory['report_id'])) {
            return Phpfox_Error::set(_p('not_a_valid_category_to_edit'));
        }
        $aLanguages = Phpfox::getService('language')->getAll();
        foreach ($aLanguages as $aLanguage) {
            $aCategory['name_' . $aLanguage['language_id']] = (Core\Lib::phrase()->isPhrase($aCategory['message'])) ? _p($aCategory['message'], [], $aLanguage['language_id']) : $aCategory['message'];;
        }
        return $aCategory;
    }

    public function get($aConds, $sSort = 'rd.added DESC', $iPage = '', $iLimit = '')
    {
        $iCnt = $this->database()->select('COUNT(DISTINCT rd.item_id)')
            ->from(Phpfox::getT('report_data'), 'rd')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = rd.user_id')
            ->leftJoin(Phpfox::getT('report'), 'r', 'r.report_id = rd.report_id')
            ->where($aConds)
            ->execute('getSlaveField');

        $aItems = [];
        if ($iCnt) {
            db()->select('MAX(rd.added) AS added, rd.item_id')
                ->from(':report_data', 'rd')
                ->group('rd.item_id')
                ->union()
                ->unionFrom('sub_rd');
            $aItems = $this->database()->select('rd.*, r.message, ' . Phpfox::getUserField())
                ->join(Phpfox::getT('report_data'), 'rd', 'rd.item_id = sub_rd.item_id AND rd.added = sub_rd.added')
                ->join(Phpfox::getT('user'), 'u', 'u.user_id = rd.user_id')
                ->leftJoin(Phpfox::getT('report'), 'r', 'r.report_id = rd.report_id')
                ->where($aConds)
                ->group('rd.item_id', true)
                ->order($sSort)
                ->limit($iPage, $iLimit, $iCnt)
                ->execute('getSlaveRows');

            foreach ($aItems as $iKey => $aItem) {
                $aParts = explode('_', $aItem['item_id']);
                unset($aParts[(count($aParts) - 1)]);
                $aItems[$iKey]['module_id'] = implode(' ', $aParts);
                if ($aItems[$iKey]['module_id'] == 'v') {
                    $aItems[$iKey]['module_id'] = 'video';
                }
                $aItems[$iKey]['total_report'] = $this->database()->select('COUNT(*)')
                    ->from(Phpfox::getT('report_data'), 'rd')
                    ->join(Phpfox::getT('user'), 'u', 'u.user_id = rd.user_id')
                    ->where(array_merge($aConds, ['rd.item_id' => $aItem['item_id']]))
                    ->execute('getSlaveField');
            }
        }

        return [$iCnt, $aItems];
    }

    public function getOptions($sType)
    {
        $sCacheId = $this->cache()->set('report');

        if (false === ($aOptions = $this->cache()->get($sCacheId))) {
            $aOptions = $this->_getOptions($sType);

            if (!count($aOptions)) {
                $aOptions = $this->_getOptions('core');
            }

            $this->cache()->save($sCacheId, $aOptions);
            Phpfox::getLib('cache')->group('report', $sCacheId);
        }

        return $aOptions;
    }

    public function getRedirect($iId)
    {
        $aReport = $this->database()->select('data_id, item_id')
            ->from(Phpfox::getT('report_data'))
            ->where('data_id = ' . (int)$iId)
            ->execute('getSlaveRow');

        if (!isset($aReport['data_id'])) {
            return Phpfox_Error::set(_p('not_a_valid_report'));
        }

        $aParts = explode('_', $aReport['item_id']);
        if (!Phpfox::hasCallback($aParts[0], 'getReportRedirect' . (isset($aParts[2]) ? ucfirst($aParts[1]) : ''))) {
            return Phpfox_Error::set(_p('not_a_valid_report'));
        }

        return Phpfox::callback($aParts[0] . '.getReportRedirect' . (isset($aParts[2]) ? ucfirst($aParts[1]) : ''), $aParts[(count($aParts) - 1)]);
    }

    public function canReport($sCategory, $iItemId)
    {
        $iCnt = $this->database()->select('COUNT(*)')
            ->from(Phpfox::getT('report_data'))
            ->where('item_id = \'' . $this->database()->escape($sCategory) . '_' . (int)$iItemId . '\' AND user_id = ' . Phpfox::getUserId())
            ->execute('getSlaveField');

        if ($iCnt > 0) {
            return false;
        }

        return true;
    }

    public function export($sProductId, $sModule = null)
    {
        $aSql = [];
        $aSql[] = "r.product_id = '" . $sProductId . "'";
        if ($sModule !== null) {
            $aSql[] = "AND r.module_id = '" . $sModule . "'";
        }

        $aRows = $this->database()->select('r.*')
            ->from($this->_sTable, 'r')
            ->where($aSql)
            ->execute('getSlaveRows');

        if (!count($aRows)) {
            return false;
        }

        $oXmlBuilder = Phpfox::getLib('xml.builder');
        $oXmlBuilder->addGroup('reports');

        foreach ($aRows as $aRow) {
            $oXmlBuilder->addTag('report', $aRow['message'], [
                    'module_id' => $aRow['module_id']
                ]
            );
        }
        $oXmlBuilder->closeGroup();

        return true;
    }

    public function getActiveReports()
    {
        $sCacheId = $this->cache()->set('report_data');
        if (($aReports = Phpfox::getLib('cache')->get($sCacheId, 1)) === false) {
            $aCategories = $this->database()->select('report_id, message')
                ->from(Phpfox::getT('report'))
                ->execute('getSlaveRows');
            $aReports = [];
            foreach ($aCategories as $aCategory) {
                $iTotal = $this->database()->select('COUNT(*)')
                    ->from(Phpfox::getT('report_data'))
                    ->where('report_id = ' . $aCategory['report_id'])
                    ->execute('getSlaveField');

                if ($iTotal <= 0) {
                    continue;
                }

                $aReports[] = [
                    'phrase' => Phpfox_Locale::instance()->convert($aCategory['message']),
                    'value'  => $iTotal
                ];
            }
            $this->cache()->save($sCacheId, $aReports);
        }

        return $aReports;
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
        if ($sPlugin = Phpfox_Plugin::get('report.service_report__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }

    private function _getOptions($sType)
    {
        return $this->database()->select('report_id, message')
            ->from($this->_sTable)
            ->where('module_id = \'' . $this->database()->escape($sType) . '\'')
            ->order('ordering ASC')
            ->execute('getSlaveRows');
    }
}