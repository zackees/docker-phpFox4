<?php
/**
 * [PHPFOX_HEADER]
 */


defined('PHPFOX') or exit('NO DICE!');


class Core_Component_Block_Gmap_Block extends \Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        if (defined('PHPFOX_IS_PAGES_VIEW') || defined('PHPFOX_IS_USER_PROFILE')) {
            return false;
        }

        $aParams = $this->getParam('aGmapView');
        if (empty($aParams)) {
            return false;
        }
        $sUrl = '';
        if (isset($aParams['url'])) {
            $sUrl = isset($aParams['url']) ? $aParams['url'] : '';
        } elseif (!empty($aParams['type'])) {
            $sUrl = $this->url()->makeUrl('core.gmap', ['type' => $aParams['type']]);
        }
        if (!$sUrl) {
            return false;
        }
        $this->template()->assign([
            'sUrl' => $sUrl,
            'sImage' => Phpfox::getLib('assets')->getAssetUrl('PF.Base/module/core/static/image/map.png'),
            'sHeader' => '',
            'sCustomClassName' => 'block-core-map-view'
        ]);
        return 'block';
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('core.block_gmap_block_clean')) ? eval($sPlugin) : false);
    }
}