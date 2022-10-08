<?php
defined('PHPFOX') or exit('NO DICE!');
?>
<article class="core-schedule-feed-manage-item-content" id="js_schedule_item_{$aScheduleItem.schedule_id}">
    <div class="item-outer">
        <div class="item-inner">
             <div class="item-edit-area-click" title="{if empty($aScheduleItem.is_temp)}{_p var='click_to_edit_this_item'}{/if}" data-id="{$aScheduleItem.schedule_id}"
             {if empty($aScheduleItem.is_temp)}onclick="tb_show('{_p var='edit_your_scheduled_post'}', $.ajaxBox('core.editScheduleItem', 'height=400&amp;width=600&amp;id={$aScheduleItem.schedule_id}')); return false;"{/if}></div>
             <div class="item-time-wrapper">
                 <div class="item-time">
                    <span class="item-app"><i class="ico ico-clock-o"></i>{$aScheduleItem.item_name}</span>
                    <span>{_p var='will_be_sent_on'} {$aScheduleItem.time_schedule|date:'feed.feed_display_time_stamp'}</span>
                </div>
                <div class="item-action-wrapper">
                    {if empty($aScheduleItem.is_temp)}
                        <div class="item-actions">
                            <span class="item-share-now">
                                <a href="javascript:void(0)" title="{_p var='send_now'}" onclick="$Core.FeedSchedule.sendNowScheduleItem({$aScheduleItem.schedule_id}, this); return false;" class="item-action-btn" data-message="{_p var='are_you_sure_you_want_to_send_this_post_now'}">
                                    <span class="ico ico-paperplane-alt-o"></span>
                                </a>
                            </span>
                            <span class="item-delete">
                                <a href="#" title="{_p var='delete'}" class="item-action-btn" data-message="{_p var='are_you_sure_you_want_to_delete_this_scheduled_item'}" onclick="$Core.FeedSchedule.deleteScheduleItem({$aScheduleItem.schedule_id}, this); return false;">
                                    <span class="ico ico-trash-alt-o"></span>
                                </a>
                            </span>
                        </div>
                    {else}
                        <span class="item-processing">{_p var='processing'}</span>
                    {/if}
                </div>
             </div>
            <div class="item-info">
                <div class="item-title">
                    {$aScheduleItem.item_title|feed_strip|shorten:'150':'...'}
                </div>
                {if !empty($aScheduleItem.item_images)}
                <div class="item-image-wrapper">
                    {foreach from=$aScheduleItem.item_images.images key=iKey item=sImageUrl}
                    {if isset($aScheduleItem.item_images.remaining) && $aScheduleItem.item_images.remaining > 0 && $iKey > 0}
                        <div class="item-image">
                            <span class="item-image-src" style="background-image:url({$sImageUrl})" />
                            <span class="item-image-count">
                            {$aScheduleItem.item_images.remaining} +
                            </span> 
                        </div>
                    {else}
                        <div class="item-image">
                            <span class="item-image-src" style="background-image:url({$sImageUrl})" />
                        </div>
                    {/if}
                    {/foreach}
                </div>
                {/if}
            </div>
        </div>
    </div>
</article>