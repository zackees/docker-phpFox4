<?php
defined('PHPFOX') or exit('NO DICE!');
?>

<!--Search-->
{if $iPage == 1 && !$bSearch}
<div id="feed_manage_hidden" class="feed-manage-hidden-popup">
    <form id="feed_search_hidden" method="POST" onsubmit="$Core.feed.searchHidden(this);return false;">
        <div class="feed-form-search">
         <input type="text" placeholder="{_p('search_name')}" name="name" class="form-control">
         <select name="type" class="form-control">
            <option value="user">{_p('all')}</option>
            <option value="friend">{_p('users')}</option>
             <option value="page">{_p('pages')}</option>
             <option value="group">{_p('groups')}</option>
         </select>
         <input type="submit" value="{_p('search')}" class="btn btn-primary">
        </div>
        <p class="feed-tips">{_p('hide_posts_from_below_users_pages_or_groups')}</p>
        <div id="feed_list_hidden" class="feed-list-hidden-popup">
          <div class="feed-list-headline">
             <span>{_p var='number_items_selected' number=0}</span>
             <div class="feed-select-all checkbox">
                <label>
                   <input type="checkbox" onchange="$Core.feed.selectAllHiddens(this);"> {_p('select_all')}
                </label>
             </div>
          </div>
          <input type="hidden" id="feed_list_unhide">
          <div class="feed-hidden-items clearfix">
{/if}
      <!--Ajax loading part-->
      {if $iCnt > 0}
         {foreach from=$aHides item=aHidden}
             <div id="feed_item_hidden_{$aHidden.hide_id}" class="feed-hidden-item">
                <div class="feed-hidden-item-content">
                   <label for="feed_item_hidden_checkbox_{$aHidden.hide_id}">
                       {img user=$aHidden suffix='_120_square' max_width=32 max_height=32}
                       {$aHidden.full_name}
                   </label>
                   <span class="feed-delete">
                      <i class="fa fa-close" title="{_p('unhide')}" onclick="$Core.feed.unhide({$aHidden.hide_id}, {$aHidden.item_id}, '{$aHidden.type_id}'); return false;"></i>
                   </span>
                   <input type="checkbox" id="feed_item_hidden_checkbox_{$aHidden.hide_id}" class="feed_item_hidden_checkbox" onchange="$Core.feed.selectUnhide(this)" data-hid="{$aHidden.hide_id}">
                </div>
             </div>
         {/foreach}
         {pager}
      {else}
         <div id="feed_no_hidden" class="extra_info">{_p('no_hidden_items_found')}</div>
      {/if}
      <!--End ajax loading part-->
{if $iPage == 1 && !$bSearch}
      </div>
    </div>

    <div id="feed_action_hidden">
        <a class="btn btn-default" onclick="return js_box_remove(this);">{_p('cancel')}</a>
        <a class="btn btn-primary disabled" id="feed_unhide_button" onclick="$Core.feed.multiUnhide();return false;">{_p var='unhide_selected'}</a>
    </div>
    </form>
</div>
{/if}
