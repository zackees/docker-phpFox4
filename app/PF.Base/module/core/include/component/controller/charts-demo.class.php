<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Controller_Charts_Demo extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $this->template()
            ->setHeader('cache', [
                'chart.js' => 'static_script',
            ])
            ->setTitle('Charts Demo')
            ->setBreadCrumb('Charts Demo')
            ->assign([
                'mapsApiKey' => Phpfox::getParam('core.google_api_key'),
            ]
        );
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('core.component_controller_charts_demo_clean')) ? eval($sPlugin) : false);
    }
}