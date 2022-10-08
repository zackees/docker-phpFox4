<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Block_Gmap_Card_Views extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $aSearchTools = $this->template()->getVar('aSearchTool');
        $aPagers = $this->template()->getVar('aPagers');
        if (!empty($aSearchTools) && isset($aSearchTools['filters'])) {
            foreach ($aSearchTools['filters'] as $sKey => $aFilter) {
                if ($sKey == _p('show')) {
                    unset($aSearchTools['filters'][$sKey]);
                    continue;
                }
                if (!isset($aFilter['is_input']) && count($aFilter['data'])) {
                    foreach ($aFilter['data'] as $sKeyData => $aData) {
                        $aFilter['data'][$sKeyData]['query'] = parse_url($aData['link'], PHP_URL_QUERY);
                    }
                    $aSearchTools['filters'][$sKey] = $aFilter;
                }
            }

        }
        if (!empty($aPagers)) {
            foreach ($aPagers as $sKey => $aPager) {
                if (!empty($aPager['rel']) && $this->getParam('sPagingMode') != 'next_prev') {
                    $aPagers[$sKey]['label'] = '';
                }
                $aPagers[$sKey]['params'] = parse_url($aPager['link'], PHP_URL_QUERY);
            }
        }
        $this->template()->clean('aSearchTool');
        $this->template()->clean('aPagers');
        $this->template()->assign([
                'sType' => $this->getParam('sType'),
                'aItems' => $this->getParam('aItems'),
                'aParams' => $this->getParam('aParams'),
                'aSearchParams' => $this->getParam('aSearchParams'),
                'sPagingMode' => $this->getParam('sPagingMode'),
                'sAjax' => $this->getParam('sAjax'),
                'aMapSearchTools' => $aSearchTools,
                'aPagers' => $aPagers
            ]
        );
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('core.component_block_gmap_card_views')) ? eval($sPlugin) : false);
    }
}