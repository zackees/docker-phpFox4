<?php
defined('PHPFOX') or exit('NO DICE!');
?>
{if Phpfox::getUserId() && (Phpfox::getUserId() != $aFeed.user_id) && empty($aFeed.feed_display) && !defined('PHPFOX_IS_USER_PROFILE') && !defined('PHPFOX_IS_PAGES_VIEW') && !defined('PHPFOX_IS_EVENT_VIEW')}
    <li class="js_hide_feed" id="hide_feed_{$aFeed.feed_id}" data-user-id="{$aFeed.user_id}" data-user-full_name="{$aFeed.full_name}">
        <a href="javascript:void(0);" class="feed_hide" title="{_p var='hide_feed'}" onclick="$Core.feed.prepareHideFeed([{$aFeed.feed_id}], []); $.ajaxCall('feed.hideFeed', 'id=' + {$aFeed.feed_id}); return false;">
            <span class="ico ico-eye-alt-blocked" aria-hidden="true"></span> {_p var='hide'}
        </a>
    </li>

    {if Phpfox::getUserBy('profile_page_id') == 0}
        <li class="">
            <a href="javascript:void(0);" class="feed_hide_all" title="{_p var='hide_all_from_full_name_regular' full_name=$aFeed.full_name}" onclick="$Core.feed.prepareHideFeed([], [{$aFeed.user_id}]); $.ajaxCall('feed.hideAllFromUser', 'id=' + {$aFeed.user_id}); return false;">
                <span class="ico ico-eye-alt-blocked" aria-hidden="true"></span> <span>{_p var='hide_all_from_full_name' full_name=$aFeed.full_name}</span>
            </a>
        </li>
    {/if}
{/if}