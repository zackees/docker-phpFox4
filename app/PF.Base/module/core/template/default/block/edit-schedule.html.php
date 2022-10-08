<?php
defined('PHPFOX') or exit('NO DICE!');
?>

<div class="activity_feed_form" id="js_edit_schedule_form">
    <form class="form" method="post" action="javascript:void(0);" id="js_activity_feed_edit_form" enctype="multipart/form-data">
        <div><input type="hidden" name="val[is_edit_schedule]" value="1"/></div>
        <div><input type="hidden" name="val[schedule_id]" value="{$iScheduleId}"/></div>
        <div><input type="hidden" name="val[module_id]" value="{$iModuleId}"/></div>
        {if $aForms.type_id == 'edit_schedule'}
        <div id="custom_ajax_form_submit" class="hide">core.updateSchedule</div>
        {/if}
        {if Phpfox::isModule('privacy')}
            <div id="js_custom_privacy_input_holder" class="edit_schedule_privacy_list">
                {if !empty($aForms.privacy_list)}
                    {foreach from=$aForms.privacy_list item=iPrivacyList}
                        <div><input type="hidden" name="va[privacy_list][]" value="{$iPrivacyList}" class="privacy_list_array" /></div>
                    {/foreach}
                {/if}
            </div>
        {/if}
        <div class="activity_feed_form_holder">
            <div class="global_attachment_holder_section" id="global_attachment_status" style="display:block;">
                <div id="global_attachment_status_value" style="display:none;"></div>
                <textarea name="val[user_status]" class="close_warning" style="display: none">{$aForms.user_status}</textarea>
                <div contenteditable="true" class="contenteditable close_warning" data-text="{_p var='what_s_on_your_mind'}" style="min-height:50px;">{$generateStatus}</div>
            </div>
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
                <div id="js_location_feedback{$iScheduleId}" class="js_location_feedback{$iScheduleId} {if !empty($aForms.location_name)}active{/if}">
                    {if !empty($aForms.location_name) }
                    {_p var='at_location' location=$aForms.location_name}
                    {/if}
                </div>
            {/if}
            <script type="text/javascript">
                oTranslations['will_send_on_time'] = "{_p var='will_send_on_time'}";
            </script>
            <div class="js_schedule_review">{if !empty($aForms.raw_schedule_time)}{_p var='will_send_on_time' time=$aForms.raw_schedule_time}{/if}</div>
        </div>
        {if isset($additionalEditTemplate)}
            <div class="additional-form-holder">
                {template file=$additionalEditTemplate}
            </div>
        {/if}
        <div class="activity_feed_form_button" style="display: block">
            {if isset($bLoadTagFriends) && $bLoadTagFriends == true}
                {template file='feed.block.tagged'}
            {/if}
            {if $bLoadCheckIn}
            <div id="js_location_input{$iScheduleId}">
                <a class="btn btn-danger toggle-checkin" href="#" title="{_p var='close'}" onclick="$Core.FeedPlace.cancelCheckIn({$iScheduleId}, true); return false;"><i class="fa fa-eye-slash"></i></a>
                <a class="btn btn-danger" href="#" title="{_p var='remove_checkin'}" onclick="$Core.FeedPlace.cancelCheckIn({$iScheduleId}); return false;"><i class="fa fa-times"></i></a>
                <input type="text" id="hdn_location_name{$iScheduleId}" {if !empty($aForms.location_name) }value="{$aForms.location_name}"{/if} autocomplete="off">
            </div>
            {/if}
            <div class="js_feed_schedule_container">
                {template file='feed.block.feed-schedule'}
            </div>
            <div class="activity_feed_form_button_position">
                <div id="activity_feed_share_this_one">
                    <ul>
                        {if $bLoadTagFriends}
                            {template file='feed.block.with-friend'}
                        {/if}
                        {if $bLoadCheckIn && !$bDisableCheckIn}
                            {template file='feed.block.checkin'}
                        {/if}
                        {template file='feed.block.with-schedule'}
                    </ul>
                    <div class="clear"></div>
                </div>
                <div class="activity_feed_form_button_position_button button_position_edit_schedule">
                    <input type="submit" value="{_p var='Update'}"  id="activity_feed_submit" class="button btn-lg btn-primary" />
                </div>

                <div class="special_close_warning">
                    {if $bLoadPrivacy}
                    {module name='privacy.form' privacy_name='privacy' privacy_type='mini' btn_size='normal'}
                    {/if}
                </div>

                <div class="clear"></div>
            </div>
            {if Phpfox::getParam('feed.enable_check_in') && Phpfox::getParam('core.google_api_key') != ''}
            <div id="js_add_location{$iScheduleId}">
                <div><input type="hidden" id="val_location_latlng{$iScheduleId}" class="close_warning" name="val[location][latlng]" {if !empty($aForms.location_latlng)}value="{$aForms.location_latlng.latitude},{$aForms.location_latlng.longitude}"{/if}></div>
                <div><input type="hidden" id="val_location_name{$iScheduleId}" name="val[location][name]" {if !empty($aForms.location_name)}value="{$aForms.location_name}"{/if}></div>
                <div id="js_add_location_suggestions{$iScheduleId}" style="overflow-y: auto;"></div>
                <div id="js_feed_check_in_map{$iScheduleId}"></div>
            </div>
            {/if}
        </div>
    </form>
</div>
