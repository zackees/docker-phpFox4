<?php 
/**
 * [PHPFOX_HEADER]
 * 
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		phpFox LLC
 * @package 		Phpfox
 * @version 		$Id: missing.html.php 1390 2010-01-13 13:30:08Z phpFox LLC $
 */
 
defined('PHPFOX') or exit('NO DICE!'); 

?>
<div class="alert alert-success" role="alert">
	{_p var='checking_the_following_modules_for_missing_settings'}:
</div>
<ul>
{foreach from=$aModules item=sModule}
	<li>{$sModule}</li>
{/foreach}
</ul>