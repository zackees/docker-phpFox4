<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Core_Component_Block_Site_Stat
 */
class Core_Component_Block_Site_Stat extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{
		return 'block';
	}
	
	/**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('core.component_block_site_stat_clean')) ? eval($sPlugin) : false);
	}
}