<?php 
/**
 * [PHPFOX_HEADER]
 * 
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		phpFox LLC
 * @package  		Module_Feed
 * @version 		$Id: display.html.php 4176 2012-05-16 10:49:38Z phpFox LLC $
 * This fileis called from the form.html.php template in the feed module
 */
 
defined('PHPFOX') or exit('NO DICE!'); 

?>

<li>
	<a href="#" type="button" id="btn_display_check_in{if isset($aForms.feed_id)}{$aForms.feed_id}{/if}" data-map-index="{if isset($aForms.feed_id)}{$aForms.feed_id}{/if}" class="dont-unbind parent js_hover_title btn btn-lg btn-default" onclick="return false;">
		<i class="fa fa-map-marker"></i>
		<span class="js_hover_info">
			{_p var='check_in'}
		</span>
	</a>

	<script type="text/javascript">
		var bCheckinInit = false;
		$Behavior.prepareInitFeedPlaces = function()
		{l}
			$Core.FeedPlace.sIPInfoDbKey = '';
			$Core.FeedPlace.sGoogleKey = '{param var="core.google_api_key"}';
			
			{if isset($aVisitorLocation)}
				$Core.FeedPlace.setVisitorLocation({$aVisitorLocation.latitude}, {$aVisitorLocation.longitude} );
			{else}
				
			{/if}
			
			$Core.FeedPlace.googleReady('{param var="core.google_api_key"}', '{if isset($aForms.feed_id)}{$aForms.feed_id}{/if}');
		{r}
	</script>
</li>