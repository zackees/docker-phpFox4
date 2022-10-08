<?php

defined('PHPFOX') or exit('NO DICE!');

?>
{if count($aLikes)}
    {if !$bIsPaging}
    <div class="popup-user-with-btn-container popup-user-total-container">
    {/if}
        {foreach from=$aLikes name=like item=aLike}
        <div id="js_row_like_{$aLike.user_id}"  class="like-browse-item popup-user-item">
            {module name='user.listing-item' user_id=$aLike.user_id}
        </div>
        {/foreach}
        {if $hasPagingNext}
        {pager}
        {/if}
    {if !$bIsPaging}
    </div>
    {/if}
{else}
    {if !$bIsPaging}
    <div class="extra_info">
        {$sErrorMessage}
    </div>
    {/if}
{/if}
