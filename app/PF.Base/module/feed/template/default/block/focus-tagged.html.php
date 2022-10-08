<?php

defined('PHPFOX') or exit('NO DICE!');

?>

{if isset($aFeed.friends_tagged) && isset($aFeed.total_friends_tagged) && $iTotal = $aFeed.total_friends_tagged}
    <span class="activity_feed_tag_with"> {_p('with')} </span>
    {if ($iRemain = ($iTotal - 1)) > 1}
        {$aFeed.friends_tagged.0|user}
        <span class="activity_feed_tag_and"> {_p('and')} </span>
        <span class="activity_feed_tag_other">
            <a href="#" onclick="return $Core.box('feed.friendsTagged', 500, 'type_id={$aFeed.type_id}&amp;item_id={$aFeed.item_id}');">
                {$iRemain} {_p('others')}
            </a>
        </span>
    {else}
        {foreach from=$aFeed.friends_tagged item=aTaggedUser key=iKey}
            {$aTaggedUser|user}
            {if ($iKey+1) < $iTotal}
                <span class="activity_feed_tag_and"> {_p('and')} </span>
            {/if}
        {/foreach}
    {/if}
{/if}
