<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Block_Translate_Child_Country extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{
		$this->template()->assign(array(
				'sChildId' => $this->request()->get('child_id'),
				'sChildVarName' => 'core.translate_country_child_' . strtolower($this->request()->get('child_id')),
				'sCountryName' => Phpfox::getService('core.country')->getChild($this->request()->get('child_id'))
			)
		);			
	}
	
	/**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('core.component_block_translate_child_country_clean')) ? eval($sPlugin) : false);
	}
}