<?php
    defined('PHPFOX') or exit('NO DICE!');
?>
{if isset($ajaxLoadLike) && $ajaxLoadLike}
<div id="js_like_body_{$aFeed.feed_id}">
{/if}
    {if !empty($aFeed.feed_like_phrase)}
        {if (isset($aFeed.feed_total_like) && $aFeed.feed_total_like)}<a href="#" class="activity_like_holder_total hide_it" onclick="return $Core.box('like.browse', 450, 'type_id={if isset($aFeed.like_type_id)}{$aFeed.like_type_id}{else}{$aFeed.type_id}{/if}&amp;item_id={$aFeed.item_id}');"><i>{$aFeed.feed_total_like|number_format} {_p var='liked'}</i></a>{/if}
        <div class="activity_like_holder" id="activity_like_holder_{$aFeed.feed_id}">
            {$aFeed.feed_like_phrase}
        </div>
    {else}
        <div class="activity_like_holder activity_not_like">
            {_p var='when_not_like'}
        </div>
    {/if}
{if isset($ajaxLoadLike) && $ajaxLoadLike}
</div>
{/if}
