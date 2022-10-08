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
 * @package  		Module_Theme
 * @version 		$Id: ajax.class.php 5345 2013-02-13 09:44:03Z phpFox LLC $
 */
class Theme_Component_Ajax_Ajax extends Phpfox_Ajax
{
	public function sample()
	{
		if (Phpfox::isAdmin())
		{
			echo '<iframe src="' . Phpfox_Url::instance()->makeUrl('theme', array('sample', 'get-block-layout' => 'true')) . '" width="100%" height="400" frameborder="0"></iframe>';
		}
	}
}