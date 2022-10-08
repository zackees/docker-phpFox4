<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Block_Template_Footer extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{		
		define('PHPFOX_MEM_END', memory_get_usage());

		$this->template()->assign(array(
				'sDebugInfo' => (PHPFOX_DEBUG ? Phpfox_Debug::getDetails() : '')
			)
		);		
	}
	
	/**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('core.component_block_template-footer_clean')) ? eval($sPlugin) : false);
	}
}