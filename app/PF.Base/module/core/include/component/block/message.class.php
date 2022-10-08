<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Block_Message extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{		
		$this->template()->assign(array(
				'sMessage' => $this->getParam('sMessage')
			)
		);
	}
	
	/**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('core.component_message_clean')) ? eval($sPlugin) : false);
	}
}