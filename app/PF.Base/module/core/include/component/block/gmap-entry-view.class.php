<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Block_Gmap_Entry_View extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {

        $this->template()->assign(array(
                'lat' => $this->getParam('lat'),
                'lng' => $this->getParam('lng'),
                'map_height' => $this->getParam('map_height'),
                'map_width' => $this->getParam('map_width'),
            )
        );
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('core.component_block_gmap_entry_view')) ? eval($sPlugin) : false);
    }
}