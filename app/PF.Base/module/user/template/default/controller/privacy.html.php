<?php 
defined('PHPFOX') or exit('NO DICE!');
?>

<div id="_privacy_holder_table" class="block">
	<form method="post" id="js_user_privacy_form" class="form" action="{url link='user.privacy'}">
        <div><input type="hidden" name="val[current_tab]" value="" id="current_tab"></div>
		{if Phpfox::getUserParam('user.hide_from_browse')}
            <div id="js_privacy_block_invisible" class="js_privacy_block page_section_menu_holder" {if empty($sActiveTab) || $sActiveTab != 'invisible'}style="display:none;"{/if}>
                <p class="help-block">
                    {_p var='invisible_mode_allows_you_to_browse_the_site_without_appearing_on_any_online_lists'}
                </p>
                <br />
                <div class="privacy-block-content">
                    <div class="item-outer">
                        <div class="form-group">
                            <label>{_p var='enable_invisible_mode'}</label>
                            <div class="item_is_active_holder">
                                <span class="js_item_active item_is_active"><input value="1" name="val[invisible]" class="checkbox" type="radio"{if $aUserInfo.is_invisible} checked="checked"{/if} /> {_p var='yes'}</span>
                                <span class="js_item_active item_is_not_active"><input value="0" name="val[invisible]" class="checkbox" type="radio"{if !$aUserInfo.is_invisible} checked="checked"{/if} /> {_p var='no'}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group-button">
                    <input type="submit" value="{_p var='save_changes'}" class="btn btn-primary" />
                </div>
            </div>
		{/if}
	
		{if Phpfox::getUserParam('user.can_control_profile_privacy')}
            <div id="js_privacy_block_profile" class="js_privacy_block page_section_menu_holder" {if empty($sActiveTab) || $sActiveTab != 'profile'}style="display:none;"{/if}>
                <p class="help-block">
                    {_p var='customize_how_other_users_interact_with_your_profile'}
                </p>
                <div class="privacy-block-content">
                    {foreach from=$aProfiles item=aModules}
                        {foreach from=$aModules key=sPrivacy item=aProfile}
                            <div class="item-outer">
                                {template file='user.block.privacy-profile'}
                            </div>
                        {/foreach}
                    {/foreach}
                    <div class="item-outer">
                        <div class="form-group">
                            <label for="title">{_p var='date_of_birth'}</label>
                            <div>
                                <select class="form-control" name="val[special][dob_setting]">
                                    <option value="0"{if empty($aUserInfo.dob_setting)} selected="selected"{/if}>{_p var='select'}:</option>
                                    <option value="1"{if $aUserInfo.dob_setting == '1'} selected="selected"{/if}>{_p var='show_only_month_amp_day_in_my_profile'}</option>
                                    <option value="2"{if $aUserInfo.dob_setting == '2'} selected="selected"{/if}>{_p var='display_only_my_age'}</option>
                                    <option value="3"{if $aUserInfo.dob_setting == '3'} selected="selected"{/if}>{_p var='don_t_show_my_birthday_in_my_profile'}</option>
                                    <option value="4"{if $aUserInfo.dob_setting == '4'} selected="selected"{/if}>{_p var='show_my_full_birthday_in_my_profile'}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group-button">
                    <input type="submit" value="{_p var='save_changes'}" class="btn btn-primary" />
                </div>
            </div>
		{/if}
		
		<div id="js_privacy_block_items" class="js_privacy_block page_section_menu_holder" {if empty($sActiveTab) || $sActiveTab != 'items'}style="display:none;"{/if}>
			<p class="help-block">
				{_p var='customize_your_default_settings_for_when_sharing_new_items_on_the_site'}
			</p>
			<div class="privacy-block-content">
				{foreach from=$aItems item=aModules}
                    {foreach from=$aModules key=sPrivacy item=aItem}
                        <div class="item-outer">
                            {template file='user.block.privacy-item'}
                        </div>
                    {/foreach}
				{/foreach}	
			</div>
				
			<div class="form-group-button">
				<input type="submit" value="{_p var='save_changes'}" class="btn btn-primary" />
			</div>			
		</div>
		
		{if Phpfox::getUserParam('user.can_control_notification_privacy')}
            {if count($aPrivacyNotifications) && !empty($aUserInfo.email)}
                <div id="js_privacy_block_notifications" class="js_privacy_block page_section_menu_holder" {if empty($sActiveTab) || $sActiveTab != 'notifications'}style="display:none;"{/if}>
                    <div class="privacy-block-content">
                        {foreach from=$aPrivacyNotifications item=aModules}
                            {foreach from=$aModules key=sNotification item=aNotification}
                                <div class="item-outer">
                                    {template file='user.block.privacy-notification'}
                                </div>
                            {/foreach}
                        {/foreach}
                    </div>

                    <div class="form-group-button">
                        <input type="submit" value="{_p var='save_changes'}" class="btn btn-primary" />
                    </div>
                </div>
            {/if}
            {if Phpfox::getParam('core.enable_register_with_phone_number') && !empty($aUserInfo.full_phone_number) && count($aSmsNotifications)}
                <div id="js_privacy_block_sms_notifications" class="js_privacy_block page_section_menu_holder" {if empty($sActiveTab) || $sActiveTab != 'sms_notifications'}style="display:none;"{/if}>
                    <div class="privacy-block-content">
                        {foreach from=$aSmsNotifications item=aModules}
                            {foreach from=$aModules key=sNotification item=aNotification}
                            <div class="item-outer">
                                {template file='user.block.sms-notification'}
                            </div>
                            {/foreach}
                        {/foreach}
                    </div>

                    <div class="form-group-button">
                        <input type="submit" value="{_p var='save_changes'}" class="btn btn-primary" />
                    </div>
                </div>
            {/if}
		{/if}

		<div id="js_privacy_block_blocked" class="js_privacy_block page_section_menu_holder" {if empty($sActiveTab) || $sActiveTab != 'blocked'}style="display:none;"{/if}>
            <div class="form-group js_wrap_search_block_users">
                <div class="alert alert-warning js_search_error hide"></div>
                <div class="form-group-inline-input-with-btn p-2 bg-gray-lightest">
                    <input style="width: 100%;" type="text" class="" placeholder="{if Phpfox::getParam('core.enable_register_with_phone_number')}{_p var='add_name_email_or_phone_numbber_of_person_you_want_to_block'}{else}{_p var='add_name_or_email_of_person_you_want_to_block'}{/if}">
    
                    <div class="form-group-button">
                        <input type="button" value="{_p var='block_actual'}" onclick="" class="btn btn-danger" />
                    </div>
                </div>
                <script type="text/javascript">
                    oTranslations['please_try_to_search_with_at_latest_min_characters'] = "{_p var='please_try_to_search_with_at_latest_min_characters'}";
                    oTranslations['block_people'] = "{_p var='block_people'}";
                </script>
            </div>

			{if count($aBlockedUsers)}
                <p class="block-help">
                    {_p var='check_the_boxes_to_unblock_users'}
                </p>
                <div class="privacy-block-content">
                    {foreach from=$aBlockedUsers item=aBlockedUser name=blocked}
                        <div class="item-outer">
                            <div class="form-group">
                                <div class="mr-1">
                                    {img user=$aBlockedUser suffix='_120_square' max_width='40' max_height='40'}
                                </div>
                                {$aBlockedUser|user}
                                <a role="button" id="unblock_user_{$aBlockedUser.block_user_id}" onclick="$.ajaxCall('user.unBlock', 'user_id={$aBlockedUser.block_user_id}&remove_button=true&notice=true')" class="btn btn-default btn-sm">{_p var='user_unblock'}</a>
                            </div>
                        </div>
                    {/foreach}
                </div>
                <div class="clear"></div>
			{else}
                <div class="extra_info">
                    {_p var='you_have_not_blocked_any_users'}
                </div>
			{/if}
		</div>
	</form>
</div>
{if isset($bGoToBlocked)}
    <script type="text/javascript">
        $Behavior.showBlocked = function()
        {l}
            $("a[rel^='js_privacy_block_blocke']").click();
        {r}
    </script>
{/if}