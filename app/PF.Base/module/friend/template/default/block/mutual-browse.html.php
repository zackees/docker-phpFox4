<?php

defined('PHPFOX') or exit('NO DICE!');

?>
{if !$iPage && !count($aFriends) && !$bIsPaging}
<div class="extra_info">
    {_p var='no_mutual_friends_found'}.
</div>
{else}
    {if !$bIsPaging}
    <div class="js_friend_mutual_container popup-user-total-container popup-user-with-btn-container">
        {/if}
        {foreach from=$aFriends name=friends item=aFriend}
        <div class="mutual-friend-item popup-user-item">
            {module name='user.listing-item' user_id=$aFriend.user_id}
        </div>
        {/foreach}
        {if $hasPagingNext}
        {pager}
        {/if}
        {if !$bIsPaging}
    </div>
    {/if}
{/if}