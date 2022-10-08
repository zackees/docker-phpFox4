<?php 
defined('PHPFOX') or exit('NO DICE!');
?>
{if $bIsFeedDetail}
    {module name='feed.display'}
{else}
    <div class="go_left t_center" style="width:125px;">
        {img user=$aUser suffix='_120_square' max_width='120' max_height='120'}
    </div>
    <div style="margin-left:125px;">
        <div class="extra_info">
            {_p var='profile_is_private'}
        </div>
    </div>
    <div class="clear"></div>
{/if}