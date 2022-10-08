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
 * @package 		Phpfox_Module
 * @version 		$Id: phpfox.class.php 64 2009-01-19 15:05:54Z phpFox LLC $
 */
class Module_Link
{
	public static $aDevelopers = array(
		array(
			'name' => 'phpFox LLC',
			'website' => 'www.phpfox.com'
		)
	);
	
	public static $aTables = array(
		'link',
		'link_embed'
	);
}