<?php

defined('PHPFOX') or exit('NO DICE!');

?>
{if count($aTaggedUsers)}
    {if !$bIsPaging}
        <div class="popup-user-with-btn-container popup-user-total-container">
    {/if}
        {foreach from=$aTaggedUsers name=user item=aUser}
            <div id="js_row_friend_tagged_{$aUser.user_id}"  class="friend-tagged-browse-item popup-user-item">
                {module name='user.listing-item' user_id=$aUser.user_id}
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
