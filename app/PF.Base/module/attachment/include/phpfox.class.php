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
 * @package  		Module_Attachment
 * @version 		$Id: phpfox.class.php 979 2009-09-14 14:05:38Z phpFox LLC $
 */
class Module_Attachment 
{	
	public static $aTables = array(
		'attachment',
		'attachment_type'
	);
	
	public static $aInstallWritable = array(
		'file/attachment/'
	);	
}