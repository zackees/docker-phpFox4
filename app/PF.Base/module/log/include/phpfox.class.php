<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Module_Log 
{
	public static $aDevelopers = array(
		array(
			'name' => 'phpFox LLC',
			'website' => 'www.phpfox.com'
		)
	);
	
	public static $aTables = array(
		'session',
		'log_session',
		'log_staff',
		'log_view'
	);
}