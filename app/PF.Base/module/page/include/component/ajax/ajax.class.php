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
 * @package 		Phpfox_Ajax
 * @version 		$Id: ajax.class.php 100 2009-01-26 15:15:26Z phpFox LLC $
 */
class Page_Component_Ajax_Ajax extends Phpfox_Ajax
{
	public function view()
	{
		Phpfox::getBlock('page.view');
	}
}