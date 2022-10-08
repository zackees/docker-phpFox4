<?php 
 
defined('PHPFOX') or exit('NO DICE!'); 

?>
{if count($aLoggedInUsers)}
<div class="block_listing_inline">
	<ul>
{foreach from=$aLoggedInUsers name=loggedusers item=aLoggedInUser}
	<li>
		{img user=$aLoggedInUser suffix='_120_square' max_width=50 max_height=50 class='js_hover_title'}
	</li>
{/foreach}
	</ul>
	<div class="clear"></div>
</div>
{/if}