<?php
defined('PHPFOX') or exit('NO DICE!');
?>
{if empty($no_button) && !$bLoginAsPage && Phpfox::isUser() && Phpfox::isModule('friend') && Phpfox::getUserParam('friend.can_add_friends')}
    <ul class="list-unstyled js_friend_actions_{$aUser.user_id}">
        {template file='user.block.friend-action'}
    </ul>
{/if}

{if $show_extra}
    <div class="friend-info">
        {if $mutual_count == 0}
            {if !empty($aUserFriendFeed)}
                {if $aUserFriendFeed.total_friend == 1}
                    {_p var='total_friend' total=$aUserFriendFeed.total_friend}
                {else}
                    {_p var='total_friends' total=$aUserFriendFeed.total_friend}
                {/if}
            {/if}
        {else}
            {if $mutual_count == 1}
                {_p var='1_mutual_friend'}
            {else}
                {_p var='total_mutual_friends' total=$mutual_count}
            {/if}
        {/if}
    </div>
{/if}