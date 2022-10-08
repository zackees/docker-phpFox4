<?php
/**
 * [PHPFOX_HEADER]
 *
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		phpFox LLC
 * @package 		Phpfox
 * @version 		$Id: gmap.html.php 604 2019-08-10 21:28:02Z phpFox LLC $
 */

defined('PHPFOX') or exit('NO DICE!');

?>
<div class="core-map-container">
    <div id="js-map"><div class="item-map-loading"><i class="ico ico-loading-icon"></i></div></div>
	<div class="core-map-listing-container hide" id="js-core-map-listing-container">
        {module name='core.gmap-card-views' sType=$sType}
	</div>
    <div class="gmap-header-bar-search-location-wrapper">
        <div class="gmap-header-bar-search-location">
            <div class="input-group" style="width: 100%">
                <input type="search" id="js-map_view_auto_location" class="form-control" name="" value="" placeholder="{_p var='search_by_location'}..." />
                <a class="form-control-feedback" id="js-map_current_location">
                    <i class="ico ico-compass-o"></i>
                </a>
            </div>
        </div>
    </div>
	<div class="js_core_map_button_toggle_collapse core-map-button-collapse-responsive dont-unbind">
		<div class="item-button-collapse show-list">
			<i class="ico ico-angle-up"></i> <span class="item-text">{_p var='see_all'}</span>
		</div>
		<div class="item-button-collapse show-map">
			<i class="ico ico-angle-down"></i> <span class="item-text">{_p var='hide_all'}</span>
		</div>
	</div>
</div>
{literal}
    <script>
        var isFirstTime = true;
        $Ready(function(){
          if (isFirstTime) {
            $Core.Gmap.isFirstTime = isFirstTime;
          }
          isFirstTime = false;
          $Core.Gmap.initGoogle('initMapView', {type: '{/literal}{$sType}{literal}'})
        })
    </script>
{/literal}