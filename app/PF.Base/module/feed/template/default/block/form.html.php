<?php
/**
 * [PHPFOX_HEADER]
 *
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		phpFox LLC
 * @package  		Module_Feed
 * @version 		$Id: display.html.php 4176 2012-05-16 10:49:38Z phpFox LLC $
 */

defined('PHPFOX') or exit('NO DICE!');

?>
{if !defined('PHPFOX_IS_USER_PROFILE') || (isset($iUserProfileId) && $iUserProfileId == Phpfox::getUserId()) || (Phpfox::getUserParam('profile.can_post_comment_on_profile') && isset($iUserProfileId) && Phpfox::getService('user.privacy')->hasAccess('' . $iUserProfileId . '', 'feed.share_on_wall'))}
<div class="activity_feed_form_share">
  <div class="activity_feed_form_share_process">{img theme='ajax/add.gif' class='v_middle'}</div>
  {if !isset($bSkipShare)}
  <ul class="activity_feed_form_attach">
    <li class="share">
      <a role="button">{_p var='share'}:</a>
    </li>
    {if isset($aFeedCallback.module)}
    <li><a href="#" rel="global_attachment_status" class="global_attachment_status active"><div>{_p var='post'}<span class="activity_feed_link_form_ajax">{$aFeedCallback.ajax_request}</span></div><div class="drop"></div></a></li>
    {elseif !isset($bFeedIsParentItem) && (!defined('PHPFOX_IS_USER_PROFILE') || (defined('PHPFOX_IS_USER_PROFILE') && isset($iUserProfileId) && $iUserProfileId == Phpfox::getUserId()))}
    <li><a href="#" rel="global_attachment_status" class="global_attachment_status active"><div>{_p var='status'}<span class="activity_feed_link_form_ajax">user.updateStatus</span></div><div class="drop"></div></a></li>
    {else}
    <li><a href="#" rel="global_attachment_status" class="global_attachment_status active"><div>{_p var='post'}<span class="activity_feed_link_form_ajax">feed.addComment</span></div><div class="drop"></div></a></li>
    {/if}
    {foreach from=$aFeedStatusLinks item=aFeedStatusLink name=feedlinks}

    {if $phpfox.iteration.feedlinks == 3}
    <li><a href="#" rel="view_more_link" class="timeline_view_more js_hover_title"><span class="js_hover_info">{_p var='load_more'}</span></a>
      <ul class="view_more_drop">
        {/if}

        {if isset($aFeedCallback.module) && $aFeedStatusLink.no_profile}
        {else}
        {if ($aFeedStatusLink.no_profile && !isset($bFeedIsParentItem) && (!defined('PHPFOX_IS_USER_PROFILE') || (defined('PHPFOX_IS_USER_PROFILE') && isset($iUserProfileId) && $iUserProfileId == Phpfox::getUserId()))) || !$aFeedStatusLink.no_profile}
        <li>
          <a href="#" rel="global_attachment_{$aFeedStatusLink.module_id}"{if $aFeedStatusLink.no_input} class="no_text_input"{/if}>
          <span class="activity-feed-form-tab">{$aFeedStatusLink.title|convert}</span>
          <div>
            {if $aFeedStatusLink.is_frame}
            <span class="activity_feed_link_form">{url link=''$aFeedStatusLink.module_id'.frame'}</span>
            {else}
            <span class="activity_feed_link_form_ajax">{$aFeedStatusLink.module_id}.{$aFeedStatusLink.ajax_request}</span>
            {/if}
            <span class="activity_feed_extra_info">{$aFeedStatusLink.description|convert}</span>
          </div>
          <div class="drop"></div>
          </a>
        </li>
        {/if}
        {/if}

        {if $phpfox.iteration.feedlinks == count($aFeedStatusLinks)}
      </ul>
    </li>
    {/if}

    {/foreach}
  </ul>
  {/if}
  <div class="clear"></div>
</div>

<div class="activity_feed_form">
  <form method="post" action="#" id="js_activity_feed_form" enctype="multipart/form-data">
    <div id="js_custom_privacy_input_holder"></div>
    {if isset($aFeedCallback.module)}
    <div><input type="hidden" name="val[callback_item_id]" value="{$aFeedCallback.item_id}" /></div>
    <div><input type="hidden" name="val[callback_module]" value="{$aFeedCallback.module}" /></div>
    <div><input type="hidden" name="val[parent_user_id]" value="{$aFeedCallback.item_id}" /></div>
    {/if}
    {if isset($bFeedIsParentItem)}
    <div><input type="hidden" name="val[parent_table_change]" value="{$sFeedIsParentItemModule}" /></div>
    {/if}
    {if isset($aFeedCallback.module)}
    {elseif isset($iUserProfileId) && $iUserProfileId && $iUserProfileId != Phpfox::getUserId()}
    <div><input type="hidden" name="val[parent_user_id]" value="{$iUserProfileId}" /></div>
    {/if}
    {if isset($bForceFormOnly) && $bForceFormOnly}
    <div><input type="hidden" name="force_form" value="1" /></div>
    {/if}
    <div class="activity_feed_form_holder">

      <div id="activity_feed_upload_error" style="display:none;"><div class="error_message" id="activity_feed_upload_error_message"></div></div>

      <div class="global_attachment_holder_section" id="global_attachment_status" style="display:block;">
        <div id="global_attachment_status_value" style="display:none;"></div>
        <textarea cols="60" rows="2" name="val[user_status]" style="display:none" class="close_warning"></textarea>
          <div contenteditable="true" id="{if isset($aPage)}pageFeedTextarea{elseif isset($aEvent)}eventFeedTextarea{elseif isset($bOwnProfile) && $bOwnProfile == false}profileFeedTextarea{/if}"
          class="contenteditable {if Phpfox::isAppActive('Core_eGifts')}textarea-has-egift{/if}"
          data-text="{if isset($aFeedCallback.module) || defined('PHPFOX_IS_USER_PROFILE')}{_p var='write_something'}{else}{_p var='what_s_on_your_mind'}{/if}"
          style="min-height:40px;"></div>
      </div>
      
      {foreach from=$aFeedStatusLinks item=aFeedStatusLink}
        {if !empty($aFeedStatusLink.module_block)}
            {module name=$aFeedStatusLink.module_block}
        {/if}
      {/foreach}
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
        <div id="js_location_feedback" class="js_location_feedback"></div>
        {/if}
        {if !empty($bLoadSchedule)}
            <script type="text/javascript">
                oTranslations['will_send_on_time'] = "{_p var='will_send_on_time'}";
            </script>
        {/if}
        <div class="js_schedule_review"></div>
    </div>
    <div class="activity_feed_form_button">
      <div class="activity_feed_form_button_status_info">
        <textarea cols="60" rows="8" name="val[status_info]" style="display: none"></textarea>
          <div contenteditable="true" id="activity_feed_textarea_status_info" class="contenteditable" style="height: auto"></div>
          {if isset($bLoadTagFriends) && $bLoadTagFriends == true}
            <div class="js_tagged_review"></div>
          {/if}
          {if isset($bLoadCheckIn) && $bLoadCheckIn == true}
            <div id="js_location_feedback" class="js_location_feedback"></div>
          {/if}
          <div class="js_schedule_review"></div>
      </div>
        {if isset($bLoadTagFriends) && $bLoadTagFriends == true}
        {template file='feed.block.tagged'}
        {/if}
        {if !empty($bLoadSchedule)}
            <div class="js_feed_schedule_container">
                {template file='feed.block.feed-schedule'}
            </div>
        {/if}
        {if $bLoadCheckIn}
        <div id="js_location_input">
            <a class="btn btn-danger toggle-checkin" href="#" title="{_p var='close'}" onclick="$Core.FeedPlace.cancelCheckIn('', true); return false;"><i class="fa fa-eye-slash"></i></a>
            <a class="btn btn-danger" href="#" title="{_p var='remove_checkin'}" onclick="$Core.FeedPlace.cancelCheckIn(''); return false;"><i class="fa fa-times"></i></a>
            <input type="text" id="hdn_location_name" autocomplete="off">
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
            {if !empty($bLoadSchedule)}
              {template file='feed.block.with-schedule'}
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
            {if $bLoadCheckIn}
                {template file='feed.block.checkin'}
            {/if}
            {if !empty($bLoadSchedule)}
              {template file='feed.block.with-schedule'}
            {/if}
          </ul>
          <div class="clear"></div>
        </div>
        {/if}

        <div class="activity_feed_form_button_position_button">
          <input type="submit" value="{_p var='share'}"  id="activity_feed_submit" class="button btn-lg btn-primary" />
        </div>
        {if isset($aFeedCallback.module)}
        {else}
          <div class="special_close_warning">
            {if !isset($bFeedIsParentItem) && (!defined('PHPFOX_IS_USER_PROFILE') || (defined('PHPFOX_IS_USER_PROFILE') && isset($iUserProfileId) && $iUserProfileId == Phpfox::getUserId()))}
                {module name='privacy.form' privacy_name='privacy' privacy_type='mini' default_privacy='feed.default_privacy_setting'}
            {/if}
          </div>
        {/if}
        <div class="clear"></div>
      </div>

      {if Phpfox::getParam('feed.enable_check_in') && Phpfox::getParam('core.google_api_key') != ''}
      <div id="js_add_location">
        <div><input type="hidden" id="val_location_latlng" name="val[location][latlng]" class="close_warning"></div>
        <div><input type="hidden" id="val_location_name" name="val[location][name]"></div>
        <div id="js_add_location_suggestions" style="overflow-y: auto;"></div>
        <div id="js_feed_check_in_map"></div>
      </div>
      {/if}

    </div>
  </form>
  <div class="activity_feed_form_iframe"></div>
</div>
{/if}