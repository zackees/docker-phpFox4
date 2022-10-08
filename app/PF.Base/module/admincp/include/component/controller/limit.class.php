<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 * 
 * 
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		phpFox LLC
 * @package 		Phpfox_Component
 * @version 		$Id: limit.class.php 5186 2013-01-23 10:53:04Z phpFox LLC $
 */
class Admincp_Component_Controller_Limit extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{
        // check authorization
        Phpfox::isUser(true);
        Phpfox::getUserParam('admincp.has_admin_access', true);
	}
	
	/**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('admincp.component_controller_limit_clean')) ? eval($sPlugin) : false);
	}
}