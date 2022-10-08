<?php
defined('PHPFOX') or exit('NO DICE!');
?>

<div class="activity_feed_form">
    <form class="form" method="post" action="javascript:void(0);" id="js_activity_feed_edit_form" enctype="multipart/form-data">
        <div><input type="hidden" name="val[feed_id]" value="{$iFeedId}" /></div>
        {if $aForms.type_id == 'feed_comment'}
            <div id="custom_ajax_form_submit" class="hide">feed.updatePost</div>
        {elseif $aForms.type_id == 'user_status'}
            <div id="custom_ajax_form_submit" class="hide">user.updateStatus</div>
        {/if}
        {if isset($aFeedCallback.module)}
            <div><input type="hidden" name="val[callback_item_id]" value="{$aFeedCallback.callback_item_id}" /></div>
            <div><input type="hidden" name="val[callback_module]" value="{$aFeedCallback.module}" /></div>
            <div><input type="hidden" name="val[parent_user_id]" value="{$aFeedCallback.item_id}" /></div>
        {/if}
        {if isset($bFeedIsParentItem)}
            <div><input type="hidden" name="val[parent_table_change]" value="{$sFeedIsParentItemModule}" /></div>
        {/if}
        {if isset($bForceFormOnly) && $bForceFormOnly}
            <div><input type="hidden" name="force_form" value="1" /></div>
        {/if}
        {if Phpfox::isModule('privacy')}
            <div id="js_custom_privacy_input_holder">
                {module name='privacy.build' privacy_item_id=$aForms.item_id privacy_module_id=$aForms.type_id}
            </div>
        {/if}
        <div class="activity_feed_form_holder">
            <div id="activity_feed_upload_error" style="display:none;"><div class="error_message" id="activity_feed_upload_error_message"></div></div>
            <div class="global_attachment_holder_section" id="global_attachment_status" style="display:block;">
                <div id="global_attachment_status_value" style="display:none;"></div>
                <textarea name="val[user_status]" class="close_warning" style="display: none">{$aForms.feed_status}</textarea>
                <div contenteditable="true" id="{if isset($aPage)}pageFeedTextarea{elseif isset($aEvent)}eventFeedTextarea{elseif isset($bOwnProfile) && $bOwnProfile == false}profileFeedTextarea{/if}"
                class="contenteditable close_warning"
                data-text="{if isset($aFeedCallback.module) || defined('PHPFOX_IS_USER_PROFILE')}{_p var='write_something'}{else}{_p var='what_s_on_your_mind'}{/if}"
                style="min-height:50px;">{$generateFeed}</div>
            </div>

            {if Phpfox::isAppActive('Core_eGifts')}
                {module name='egift.display'}
            {/if}
            {if isset($bLoadTagFriends) && $bLoadTagFriends == true}
                <script type="text/javascript">
                    oTranslations['with_name_and_name'] = "{_p var='with_name_and_name'}";
                    oTranslations['with_name'] = "{_p var='with_name'}";
                    oTranslations['with_name_and_number_others'] = "{_p var='with_name_and_number_others'}";
                    oTranslations['number_others'] = "{_p var='number_others'}";
                </script>
                <div class="js_tagged_review"></div>
            {/if}
            {if isset($bLoadCheckIn) && $bLoadCheckIn == true}
                <script type="text/javascript">
                  oTranslations['at_location'] = "{_p var='at_location'}";
                </script>
                <div id="js_location_feedback{$aForms.feed_id}" class="js_location_feedback{$aForms.feed_id} {if !empty($aForms.location_name)}active{/if}">
                    {if !empty($aForms.location_name) }
                        {_p var='at_location' location=$aForms.location_name}
                    {/if}
                </div>
            {/if}
        </div>
        <div class="activity_feed_form_button" style="display: block">
            <div class="activity_feed_form_button_status_info">
                <textarea cols="60" rows="8" name="val[status_info]" style="display: none">{$aForms.feed_status}</textarea>
                <div contenteditable="true" id="activity_feed_textarea_status_info" class="contenteditable" style="height: auto">{$generateFeed}</div>
            </div>
            {if isset($bLoadTagFriends) && $bLoadTagFriends == true}
                {template file='feed.block.tagged'}
            {/if}
            {if $bLoadCheckIn}
                <div id="js_location_input{$aForms.feed_id}">
                    <a class="btn btn-danger toggle-checkin" href="#" title="{_p var='close'}" onclick="$Core.FeedPlace.cancelCheckIn({$aForms.feed_id}, true); return false;"><i class="fa fa-eye-slash"></i></a>
                    <a class="btn btn-danger" href="#" title="{_p var='remove_checkin'}" onclick="$Core.FeedPlace.cancelCheckIn({$aForms.feed_id}); return false;"><i class="fa fa-times"></i></a>
                    <input type="text" id="hdn_location_name{$aForms.feed_id}" {if !empty($aForms.location_name) }value="{$aForms.location_name}"{/if} autocomplete="off">
                </div>
            {/if}
            <div class="activity_feed_form_button_position">
                {if (defined('PHPFOX_IS_PAGES_VIEW') && $aPage.is_admin)}
                    <div id="activity_feed_share_this_one">
                        <ul class="">
                            {if defined('PHPFOX_IS_PAGES_VIEW') && $aPage.is_admin && $aPage.page_id != Phpfox::getUserBy('profile_page_id') && ($aPage.item_type == 0)}
                            <li>
                                <input type="hidden" name="custom_pages_post_as_page" value="{$aPage.page_id}">
                                <a data-toggle="dropdown" role="button" class="btn btn-lg">
                                    <span class="txt-prefix">{_p var='posting_as'}: </span>
                                    <span class="txt-label">{$aPage.full_name|clean|shorten:20:'...'}</span>
                                    <i class="caret"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-checkmark">
                                    <li>
                                        <a class="is_active_image" data-toggle="privacy_item" role="button" rel="{$aPage.page_id}">{$aPage.full_name|clean|shorten:20:'...'}</a>
                                    </li>
                                    <li>
                                        <a data-toggle="privacy_item" role="button" rel="0">{$sGlobalUserFullName|shorten:20:'...'}</a>
                                    </li>
                                </ul>
                            </li>
                            {/if}
                            {if $bLoadTagFriends}
                                {template file='feed.block.with-friend'}
                            {/if}
                            {if $bLoadCheckIn}
                                {template file='feed.block.checkin'}
                            {/if}
                        </ul>
                        <div class="clear"></div>
                    </div>
                {else}
                    <div id="activity_feed_share_this_one">
                        <ul>
                            {if $bLoadTagFriends}
                                {template file='feed.block.with-friend'}
                            {/if}
                            {if $bLoadCheckIn && !$bDisableCheckIn}
                                {template file='feed.block.checkin'}
                            {/if}
                        </ul>
                        <div class="clear"></div>
                    </div>
                {/if}
                <div class="activity_feed_form_button_position_button">
                    <input type="submit" value="{_p var='Update'}"  id="activity_feed_submit" class="button btn-lg btn-primary" />
                </div>
                {if isset($aFeedCallback.module)}
                {else}
                    <div class="special_close_warning">
                        {if !isset($bFeedIsParentItem) && (!defined('PHPFOX_IS_USER_PROFILE') || (defined('PHPFOX_IS_USER_PROFILE') && $aForms.user_id == Phpfox::getUserId() && $aForms.feed_reference == 1) || (defined('PHPFOX_IS_USER_PROFILE') && isset($aUser.user_id) && $aUser.user_id == Phpfox::getUserId() && empty($mOnOtherUserProfile))) && $aForms.type_id != 'feed_comment'}
                            {module name='privacy.form' privacy_name='privacy' privacy_type='mini' btn_size='normal'}
                        {/if}
                    </div>
                {/if}
                <div class="clear"></div>
            </div>
            {if Phpfox::getParam('feed.enable_check_in') && Phpfox::getParam('core.google_api_key') != ''}
                <div id="js_add_location{$aForms.feed_id}">
                    <div><input type="hidden" id="val_location_latlng{$aForms.feed_id}" class="close_warning" name="val[location][latlng]" {if !empty($aForms.location_latlng)}value="{$aForms.location_latlng.latitude},{$aForms.location_latlng.longitude}"{/if}></div>
                    <div><input type="hidden" id="val_location_name{$aForms.feed_id}" name="val[location][name]" {if !empty($aForms.location_name)}value="{$aForms.location_name}"{/if}></div>
                    <div id="js_add_location_suggestions{$aForms.feed_id}" style="overflow-y: auto;"></div>
                    <div id="js_feed_check_in_map{$aForms.feed_id}"></div>
                </div>
            {/if}
        </div>
    </form>
    <div class="activity_feed_form_iframe"></div>
</div>
<script>
  $Core.resizeTextarea($('#js_activity_feed_edit_form #global_attachment_status textarea'));
  $Core.loadInit();
</script>