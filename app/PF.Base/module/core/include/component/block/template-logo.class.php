<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Block_Template_Logo extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{
		$this->template()->assign([
			'site_name' => Phpfox::getParam('core.site_title')
		]);

		(($sPlugin = Phpfox_Plugin::get('core.component_block_template_logo_process')) ? eval($sPlugin) : false);
	}
	
	/**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('core.component_block_template_logo_clean')) ? eval($sPlugin) : false);
	}
}