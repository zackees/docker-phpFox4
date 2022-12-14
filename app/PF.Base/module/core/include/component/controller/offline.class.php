<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Controller_Offline extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{
	    $iStaticPage = Phpfox::getParam('core.site_offline_static_page');
        $aPage = [];
	    if (!empty($iStaticPage)) {
	        $aPage = Phpfox::getService('page')->getPage($iStaticPage);
        }
		$this->template()->assign(array(
				'sOfflineMessage' => nl2br(Phpfox::getParam('core.site_offline_message')),
                'aStaticPage' => $aPage
			)
		);
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