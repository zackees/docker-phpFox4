<?php
defined('PHPFOX') or exit('NO DICE!');
?>

<div class="user-listing-item">
    <div class="item-outer">
        <div class="item-media">
            {img user=$aUser suffix='_120_square' max_width=50 max_height=50}
        </div>
        <div class="item-name">
            {$aUser|user}
            {if !Phpfox::getUserBy('profile_page_id') && !$aUser.profile_page_id}
                {module name='user.friendship' friend_user_id=$aUser.user_id type='icon' extra_info=true mutual_list=true no_button=true}
            {/if}
        </div>
        {if \Phpfox::getUserId() != $aUser.user_id && !Phpfox::getUserBy('profile_page_id') && !$aUser.profile_page_id}
        <div class="item-actions">
            {if $bIsFriend}
            <div class="dropdown">
                <a role="button" data-toggle="dropdown" class="btn btn-default btn-sm has-caret">
                    <span class="ico ico-check"></span><span class="item-text ml-1">{_p var='friend'}</span><span class="ml-1 ico ico-caret-down"></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-right">
                    <li>
                        <a role="button" onclick="$Core.composeMessage({l}user_id: {$aUser.user_id}{r}); return false;" title="{_p var='message'}">
                            <span class="ico ico-pencilline-o"></span><span class="item-text ml-1">{_p var='message'}</span>
                        </a>
                    </li>
                    <li>
                        <a href="#?call=report.add&amp;height=220&amp;width=400&amp;type=user&amp;id={$aUser.user_id}" class="inlinePopup" title="{_p var='report_this_user'}">
                            <span class="ico ico-warning-o "></span><span class="item-text ml-1">{_p var='report_this_user'}</span>
                        </a>
                    </li>
                    <li class="item-delete">
                        <a role="button" onclick="$Core.jsConfirm({l}{r}, function(){l}$.ajaxCall('friend.delete', 'friend_user_id={$aUser.user_id}&amp;reload=1');{r}, function(){l}{r}); return false;" title="{_p var='remove_friend'}">
                            <span class="ico ico-user2-del-o"></span><span class="item-text ml-1">{_p var='remove_friend'}</span>
                        </a>
                    </li>
                </ul>
            </div>
            {elseif Phpfox::getService('user.privacy')->hasAccess('' . $aUser.user_id . '', 'friend.send_request')}
                <button onclick="return $Core.addAsFriend({$aUser.user_id});" class="btn btn-default btn-sm">
                    <span class="ico ico-user1-plus-o"></span><span class="item-text ml-1">{_p var='add_friend'}</span>
                </button>
            {/if}
        </div>
        {/if}
        {if !Phpfox::getUserBy('profile_page_id') && Phpfox::isUser() && $aUser.profile_page_id && Phpfox::isAppActive('Core_Pages')}
            {if isset($aUser.page) && $aUser.page.reg_method == '2' && !isset($aUser.page.is_invited) && $aUser.page.page_type == '1'}
            {else}
                {if isset($aUser.page) && isset($aUser.page.is_reg) && $aUser.page.is_reg}
                {else}
                    <div class="item-actions">
                        <div class="dropdown js_unlike_pages_{$aUser.page.page_id}" {if empty($aUser.page.is_liked)}style="display:none;"{/if}>
                            <a role="button" class="btn btn-default item-icon-liked btn-sm" data-toggle="dropdown">
                                <span class="ico ico-thumbup"></span><span class="item-text ml-1">{_p var='liked'}</span><span class="ml-1 ico ico-caret-down"></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <li>
                                    <a role="button" onclick="$.ajaxCall('like.delete', 'type_id=pages&amp;item_id={$aUser.page.page_id}&is_browse_like=1'); return false;">
                                        <span class="mr-1 ico ico-thumbdown"></span>{_p var='unlike'}
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <button class="js_like_pages_{$aUser.page.page_id} btn btn-default btn-sm btn-gradient item-icon-like" {if !empty($aUser.page.is_liked)}style="display:none !important;"{/if} onclick="$.ajaxCall('like.add', 'type_id=pages&item_id={$aUser.page.page_id}&is_browse_like=1');">
                            <span class="ico ico-thumbup-o"></span><span class="item-text ml-1">{_p var='like'}</span>
                        </button>
                    </div>
                {/if}
            {/if}
        {/if}
    </div>
</div>
