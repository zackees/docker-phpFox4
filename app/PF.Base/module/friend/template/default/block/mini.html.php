<?php 
defined('PHPFOX') or exit('NO DICE!');
?>
{if count($aFriends)}
<div class="block_listing_inline">
    <ul>
{foreach from=$aFriends name=friend item=aFriend}
        <li>
            {if ($redis_enabled)}
                {$aFriend.photo_link}
            {else}
                {img user=$aFriend suffix='_120_square' max_width=50 max_height=50}
            {/if}
        </li>
{/foreach}
    </ul>
    <div class="clear"></div>
</div>
{else}
<div class="extra_info">
    {_p var='no_friends_online'}
</div>
{/if}
