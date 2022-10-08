<?php 
/**
 * [PHPFOX_HEADER]
 *
 */
 
defined('PHPFOX') or exit('NO DICE!'); 

?>
<div class="core-map-mode-view-target-block">
    {plugin call='core.gmap_block_start'}
    <div class="item-block-wrapper" style="background-image: url({$sImage})">
        <a href="{$sUrl}" class="no_ajax btn btn-sm btn-primary">{_p var='view_on_map'}</a>
    </div>
    {plugin call='core.gmap_block_end'}
</div>
		