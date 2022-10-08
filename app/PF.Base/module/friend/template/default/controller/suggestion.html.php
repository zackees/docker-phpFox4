<?php
/**
 * [PHPFOX_HEADER]
 *
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		phpFox LLC
 * @package 		Phpfox
 */

defined('PHPFOX') or exit('NO DICE!');

?>
{if !count($aSuggestions)}
<div class="extra_info">
    {_p var='we_are_unable_to_find_any_friends_to_suggest_at_this_time_once_we_do_you_will_be_notified_within_our_dashboard'}
</div>
{else}
<div class="main_break"></div>
<div class="item-container wrapper-items user-listing clearfix js_suggestion_wrapper" id="collection-suggestions">
    {foreach from=$aSuggestions name=suggestion item=aSuggestion}
    
    <div class="js_suggestion_parent user-item user_rows_item pull-left" id="js_suggestion_parent_{$aSuggestion.user_id}">
        <div class="user_rows_image" id="js_image_div_{$aSuggestion.user_id}">
            {img user=$aSuggestion suffix='_120_square' max_width=120 max_height=120}
        </div>
        <div class="user_rows_inner pt-1">
            {$aSuggestion|user:'':'':50}
            <div class="list-unstyled">
                <a data-toggle="dropdown" class="btn btn-primary"><i class="ico ico-gear"></i></a>
                <ul class="dropdown-menu dropdown-menu-right mt-1">
                    <li>
                        <a href="#" onclick="$(this).parents('.js_suggestion_parent:first').hide(); $.ajaxCall('friend.removeSuggestion', 'user_id={$aSuggestion.user_id}'); return false;" title="{_p var='hide_this_suggestion'}">{_p var='hide_this_suggestion'}</a>
                    </li>
                    {if Phpfox::getService('user.privacy')->hasAccess('' . $aSuggestion.user_id . '', 'friend.send_request')}
                    <li>
                        <a href="#?call=friend.request&amp;user_id={$aSuggestion.user_id}&amp;width=420&amp;height=250&amp;suggestion_page=true" class="inlinePopup" title="{_p var='add_to_friends'}">{_p var='add_to_friends'}</a>
                    </li>
                    {/if}
                </ul>
            </div>
        </div>
    </div>

    {/foreach}
</div>
<div class="clear"></div>
{/if}