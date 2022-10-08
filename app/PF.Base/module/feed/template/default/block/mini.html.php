<?php
    defined('PHPFOX') or exit('NO DICE!');
?>
{if $aParentFeed }
    <div class="feed_share_holder feed_share_{$aParentFeed.type_id}">
        <div class="feed_share_header">
            {if !isset($aParentFeed.no_user_show)}
                {$aParentFeed|user:'':'':50}
            {/if}
            {if isset($aParentFeed.parent_user)} <span class="ico ico-caret-right"></span> {$aParentFeed.parent_user|user:'parent_':'':50} {/if}{if !empty($aParentFeed.feed_info)} {$aParentFeed.feed_info}{/if}
            {if Phpfox::getParam('feed.enable_check_in') && Phpfox::getParam('core.google_api_key') != '' && !empty($aParentFeed.location_name)}
                <span class="activity_feed_location_at">{_p('at')} </span>
                <span class="js_location_name_hover activity_feed_location_name">
                    <span class="ico ico-checkin"></span>
                    <a href="https://maps.google.com/maps?daddr={$aParentFeed.location_latlng.latitude},{$aParentFeed.location_latlng.longitude}" target="_blank">{$aParentFeed.location_name}</a>
                </span>
            {/if}

            {if Phpfox::getParam('feed.enable_tag_friends') && !empty($aParentFeed.friends_tagged) && !empty($aParentFeed.total_friends_tagged)}
            <?php
                $this->_aVars['aFeed']['friends_tagged'] = $this->_aVars['aParentFeed']['friends_tagged'];
                $this->_aVars['aFeed']['total_friends_tagged'] = $this->_aVars['aParentFeed']['total_friends_tagged'];
                $this->_aVars['aFeed']['temp_type_id'] = $this->_aVars['aFeed']['type_id'];
                $this->_aVars['aFeed']['temp_item_id'] = $this->_aVars['aFeed']['item_id'];
                $this->_aVars['aFeed']['item_id'] = $this->_aVars['aParentFeed']['item_id'];
                $this->_aVars['aFeed']['type_id'] = $this->_aVars['aParentFeed']['type_id'];
            ?>
            <span class="activity_feed_tagged_user">{template file='feed.block.focus-tagged'}</span>
            <?php
                $this->_aVars['aFeed']['item_id'] = $this->_aVars['aFeed']['temp_item_id'];
                $this->_aVars['aFeed']['type_id'] = $this->_aVars['aFeed']['temp_type_id'];
                unset($this->_aVars['aFeed']['temp_type_id']);
                unset($this->_aVars['aFeed']['temp_item_id']);
            ?>
            {/if}
            <div class="activity-feed-time-privacy-block">
                <time>
                    <a href="{$aParentFeed.feed_link}" class="feed_permalink">{$aParentFeed.time_stamp|convert_time:'feed.feed_display_time_stamp'}</a>
                </time>
                {if !empty($aParentFeed.privacy_icon_class)}
                    <span class="{$aParentFeed.privacy_icon_class}"></span>
                {/if}
            </div>

            {if !empty($aParentFeed.feed_mini_content)}
                <div class="activity_feed_content_status">
                    <div class="activity_feed_content_status_left">
                        <img src="{$aParentFeed.feed_icon}" alt="" class="v_middle" /> {$aParentFeed.feed_mini_content}
                    </div>
                    <div class="activity_feed_content_status_right">
                        {template file='feed.block.link'}
                    </div>
                    <div class="clear"></div>
                </div>
            {/if}
            {if isset($aParentFeed.feed_status) && (!empty($aParentFeed.feed_status) || $aParentFeed.feed_status == '0')}
                {if !empty($aParentFeed.status_background)}
                <div class="p-statusbg-feed" style="background-image: url('{$aParentFeed.status_background}');">
                {/if}
                    <div class="activity_feed_content_status">
                        {$aParentFeed.feed_status|feed_strip|shorten:200:'feed.view_more':true|split:55}
                    </div>
                {if !empty($aParentFeed.status_background)}
                </div>
                {/if}
            {/if}
        </div>
        {if isset($aParentFeed.load_block)}
            {module name=$aParentFeed.load_block this_feed_id=$aParentFeed.feed_id}
        {else}
            <div class="activity_feed_content_link"{if isset($aParentFeed.no_user_show)} style="margin-top:0px;"{/if}>
                {if $aParentFeed.type_id == 'friend' && isset($aParentFeed.more_feed_rows) && is_array($aParentFeed.more_feed_rows) && count($aParentFeed.more_feed_rows)}
                    {foreach from=$aParentFeed.more_feed_rows item=aFriends}
                        {$aFriends.feed_image}
                    {/foreach}
                    {$aParentFeed.feed_image}
                {else}
                    {if !empty($aParentFeed.feed_image)}
                        <div class="activity_feed_content_image"{if isset($aParentFeed.feed_custom_width)} style="width:{$aParentFeed.feed_custom_width};"{/if}>
                            {if is_array($aParentFeed.feed_image)}
                                <div class="activity_feed_multiple_image feed-img-stage-{$aParentFeed.total_image}">
                                    {foreach from=$aParentFeed.feed_image item=sFeedImage name=image}
                                    <div class="img-{$phpfox.iteration.image}">
                                        {$sFeedImage}
                                    </div>
                                    {/foreach}
                                </div>
                                <div class="clear"></div>
                            {else}
                                <a href="{$aParentFeed.feed_link}" target="_blank" class="{if isset($aParentFeed.custom_css)} {$aParentFeed.custom_css} {/if}{if !empty($aParentFeed.feed_image_onclick)}{if !isset($aParentFeed.feed_image_onclick_no_image)}play_link {/if} no_ajax_link{/if}"{if !empty($aParentFeed.feed_image_onclick)} onclick="{$aParentFeed.feed_image_onclick}"{/if}{if !empty($aParentFeed.custom_rel)} rel="{$aParentFeed.custom_rel}"{/if}{if isset($aParentFeed.custom_js)} {$aParentFeed.custom_js} {/if}>{if !empty($aParentFeed.feed_image_onclick)}{if !isset($aParentFeed.feed_image_onclick_no_image)}<span class="play_link_img">{_p var='play'}</span>{/if}{/if}{$aParentFeed.feed_image}</a>
                            {/if}
                        </div>
                    {/if}
                    <div class="{if (!empty($aParentFeed.feed_content) || !empty($aParentFeed.feed_custom_html)) && empty($aParentFeed.feed_image)} activity_feed_content_no_image{/if}{if !empty($aParentFeed.feed_image)} activity_feed_content_float{/if}"{if isset($aParentFeed.feed_custom_width)} style="margin-left:{$aParentFeed.feed_custom_width};"{/if}>
                        {if !empty($aParentFeed.feed_title)}
                            <a href="{$aParentFeed.feed_link}" class="activity_feed_content_link_title{if isset($aParentFeed.custom_css)} {$aParentFeed.custom_css}{/if}"{if isset($aParentFeed.feed_title_extra_link)} target="_blank"{/if}>{$aParentFeed.feed_title|clean|split:30}</a>
                            {if !empty($aParentFeed.feed_title_extra)}
                                <div class="activity_feed_content_link_title_link">
                                    <a href="{$aParentFeed.feed_title_extra_link}" class="{if isset($aParentFeed.custom_css)}{$aParentFeed.custom_css}{/if}" target="_blank">{$aParentFeed.feed_title_extra|clean}</a>
                                </div>
                            {/if}
                        {/if}
                        {if !empty($aParentFeed.feed_content)}
                            <div class="activity_feed_content_display">
                                {$aParentFeed.feed_content|feed_strip|split:55}
                            </div>
                        {/if}
                        {if !empty($aParentFeed.feed_custom_html)}
                            <div class="activity_feed_content_display_custom">
                                {$aParentFeed.feed_custom_html}
                            </div>
                        {/if}

                        {if !empty($aParentFeed.app_content)}
                            {$aParentFeed.app_content}
                        {/if}

                    </div>
                    {if !empty($aParentFeed.feed_image)}
                        <div class="clear"></div>
                    {/if}
                {/if}

                {if $showMap}
                    <div class="activity_feed_location">
                        <div id="feed_{$aFeed.feed_id}_share_{$aParentFeed.feed_id}" class="pf-feed-map" data-component="pf_map" data-lat="{$aParentFeed.location_latlng.latitude}" data-lng="{$aParentFeed.location_latlng.longitude}" data-id="feed_{$aFeed.feed_id}_share_{$aParentFeed.feed_id}"></div>
                    </div>
                {/if}
            </div>
        {/if}
    </div>
{else}
    <div class="alert alert-warning m_bottom_0 mt-1" role="alert">
        <h4 class="alert-heading mb-1">{_p var='this_content_is_not_available_at_the_moment'}</h4>
        <p>{_p var='when_this_happens_its_usually_because_the_owner_only_shared_it_with_a_small_group_of_people_or_changed_who_can_see_it_or_its_been_deleted'}</p>
    </div>
{/if}
			