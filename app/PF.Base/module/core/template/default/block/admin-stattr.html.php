<?php 
/**
 * [PHPFOX_HEADER]
 * 
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		phpFox LLC
 * @package 		Phpfox
 * @version 		$Id: admin-stattr.html.php 4093 2012-04-16 12:54:05Z phpFox LLC $
 */
 
defined('PHPFOX') or exit('NO DICE!'); 

?>
<tr>
	<td>{if isset($aStat.label)}{$aStat.label}{else}{_p var=$aStat.phrase}{/if}</td>
	<td>{$aStat.total|number_format}</td>
	<td>{if isset($aStat.average)}{$aStat.average}{else}N/A{/if}</td>
</tr>