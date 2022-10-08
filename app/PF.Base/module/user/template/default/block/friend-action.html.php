<?php
defined('PHPFOX') or exit('NO DICE!');
?>

{if !$is_friend && Phpfox::getService('user.privacy')->hasAccess('' . $user_id . '', 'friend.send_request') && ($requested || empty($is_ignore_request))}
    <li><a class="btn btn-sm {if $type == 'string'}btn-default{else}btn-success{/if}" href="#" onclick="return $Core.processFriendRequest.addAsFriend('{$user_id}');" title="{_p var='add_to_friends'}">
        {if $type == 'string'}
            <i class="fa fa-plus"></i>{_p var='add_as_friend'}
        {else}
            {if $requested}
                <i class="fa fa-check"></i>
            {else}
                <i class="fa fa-user-plus"></i>
            {/if}
        {/if}
    </a></li>
{/if}