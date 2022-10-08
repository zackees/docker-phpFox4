<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Block_New extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{			
		list($aMenus, $sModuleBlock) = Phpfox::getService('core')->getNewMenu();
		
		if (!count($aMenus))
		{
			return false;
		}
		
		$this->template()->assign(array(
				'sHeader' => _p('what_s_new'),				
				'aMenu' => $aMenus,
				'sModuleBlock' => $sModuleBlock				
			)
		);
		
		if (Phpfox::isUser())
		{
			$this->template()->assign('sDeleteBlock', 'dashboard');
			$this->template()->assign(array(
					'aEditBar' => array(
						'ajax_call' => 'core.getEditBarNew'						
					),
					'bPassOverAjaxCall' => true
				)
			);
		}
		
		return 'block';
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