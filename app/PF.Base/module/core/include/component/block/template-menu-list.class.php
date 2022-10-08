<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Block_Template_Menu_List extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{
        if (!Phpfox::isUser() && Phpfox::getParam('user.hide_main_menu')){
			return false;
        }
	}
	
	/**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('core.component_block_template_menu_list_clean')) ? eval($sPlugin) : false);
	}
}