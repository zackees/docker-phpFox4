<?php 
/**
 * [PHPFOX_HEADER]
 * 
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		phpFox LLC
 * @package 		Phpfox
 * @version 		$Id: admin-stat.html.php 4093 2012-04-16 12:54:05Z phpFox LLC $
 */
 
defined('PHPFOX') or exit('NO DICE!'); 

?>
{foreach from=$aStats name=stats item=aStat}
{template file='core.block.admin-stattr'}
{/foreach}