<?php 
/**
 * [PHPFOX_HEADER]
 * 
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		phpFox LLC
 * @package  		Module_Error
 * @version 		$Id: display.html.php 4410 2012-06-28 08:51:00Z phpFox LLC $
 */
 
defined('PHPFOX') or exit('NO DICE!'); 

?>
{if isset($sErrorMessage)}
	<div class="error_message">
	{$sErrorMessage}
	</div>
{/if}