<?php
defined('PHPFOX') or exit('NO DICE!');
?>
{if !$bIsPaging && empty($aFriends)}
    <div class="alert alert-danger">{_p var='you_have_no_friend_online_now'}</div>
{else}
    {if !$bIsPaging}
    <div class="browse-online-container popup-user-total-container popup-user-with-btn-container">
    {/if}
    {foreach from=$aFriends item=aFriend name=friend}
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