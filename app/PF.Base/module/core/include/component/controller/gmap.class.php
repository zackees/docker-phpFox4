<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');
defined('PHPFOX_IS_CONTROLLER_GMAP') or define('PHPFOX_IS_CONTROLLER_GMAP', true);

/**
 *
 *
 * @copyright        [PHPFOX_COPYRIGHT]
 * @author        phpFox LLC
 * @package        Phpfox_Component
 */
class Core_Component_Controller_Gmap extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $oModule = Phpfox::getLib('module');
        $sType = $this->request()->get('type');
        if (empty($sType)) {
            return $oModule->setController('error.404');
        }
        if (!Phpfox::hasCallback($sType, 'getMapViewItems') || !Phpfox::hasCallback($sType, 'getMapViewParams')) {
            return $oModule->setController('error.404');
        }
        $this->template()->setHeader([
            'gmap.js' => 'module_core'
        ])
            ->setPhrase([
                'error_when_load_map_view_missing_google_api_key'
            ])
            ->setTitle(_p($sType))
            ->assign([
                'sType' => $sType
            ]);

        $oModule->appendPageClass('core-gmap-controller' . ' core-gmap-' . $sType);

        return 'controller';
    }


    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('core.component_block_gmap_clean')) ? eval($sPlugin) : false);
    }
}