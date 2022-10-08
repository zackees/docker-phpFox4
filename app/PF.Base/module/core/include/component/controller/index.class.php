<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Controller_Index extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{
		
	}
	
	/**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('core.component_controller_index_clean')) ? eval($sPlugin) : false);
	}
}