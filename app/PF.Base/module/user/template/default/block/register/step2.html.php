<?php
defined('PHPFOX') or exit('NO DICE!');
?>

<div id="js_register_step2">
    {plugin call='user.template_default_block_register_step2_6'}

	{if !isset($bIsPosted) && Phpfox::getParam('user.multi_step_registration_form')}
		<div class="p_bottom_10">{_p var='complete_this_step_to_setup_your_personal_profile'}</div>
	{/if}

	{if Phpfox::getParam('core.registration_enable_dob')}
		<div class="table form-group">
			<label class="required">{_p var='birthday'}</label>
            {select_date start_year=$sDobStart end_year=$sDobEnd field_separator=' / ' field_order='MDY' bUseDatepicker=false sort_years='DESC'}
		</div>
	{/if}

	{if Phpfox::getParam('core.registration_enable_gender')}
		<div class="form-group">
            <label for="gender" class="required">{_p var='i_am'}</label>
            {select_gender}
		</div>
	{/if}

	{if Phpfox::getParam('core.registration_enable_location')}
		<div class="form-group js_core_init_selectize_form_group selectize-mcustomscroll">
            <label for="country_iso" class="required">{_p var='location'}:</label>
            {select_location}
            {module name='core.country-child' country_force_div=true}
		</div>
	{/if}

	{if Phpfox::getParam('core.city_in_registration')}
		<div class="form-group">
            <label for="city_location">{_p var='city'}</label>
            <input class="form-control" type="text" name="val[city_location]" id="city_location" value="{value type='input' id='city_location'}" size="30" />
		</div>
	{/if}
	
	{if Phpfox::getParam('core.registration_enable_timezone')}
		<div class="form-group js_core_init_selectize_form_group">
			<label class="table_left">{_p var='time_zone'}</label>
            <select class="form-control" name="val[time_zone]">
                {foreach from=$aTimeZones key=sTimeZoneKey item=sTimeZone}
                    <option value="{$sTimeZoneKey}"{if (Phpfox::getTimeZone() == $sTimeZoneKey && !isset($iTimeZonePosted)) || (isset($iTimeZonePosted) && $iTimeZonePosted == $sTimeZoneKey) || (Phpfox::getParam('core.default_time_zone_offset') == $sTimeZoneKey)} selected="selected"{/if}>{$sTimeZone}</option>
                {/foreach}
            </select>
		</div>
	{/if}

    {plugin call='user.template_default_block_register_step2_7'}
    {template file='user.block.custom'}
    {plugin call='user.template_default_block_register_step2_8'}
    {if Phpfox::isAppActive('Core_Subscriptions') && Phpfox::getParam('subscribe.enable_subscription_packages') && count($aPackages)}
    <div class="separate"></div>
    <div class="form-group">
        <label for="package_id">{if Phpfox::getParam('subscribe.subscribe_is_required_on_sign_up')}{required}{/if}{_p var='membership'}</label>
        <select class="form-control" name="val[package_id]" id="js_subscribe_package_id">
            {if Phpfox::getParam('subscribe.subscribe_is_required_on_sign_up')}
            <option value=""{value type='select' id='package_id' default='0'}>{_p var='select'}:</option>
            {else}
            <option value=""{value type='select' id='package_id' default='0'}>{_p var='free_normal'}</option>
            {/if}
            {foreach from=$aPackages item=aPackage}
            <option value="{$aPackage.package_id}"{value type='select' id='package_id' default=''$aPackage.package_id''}>{if $aPackage.show_price}({if $aPackage.default_cost == '0.00'}{_p var='free'}{else}{$aPackage.default_currency_id|currency_symbol}{$aPackage.default_cost}{/if}) {/if}{$aPackage.title|convert|clean}</option>
            {/foreach}
        </select>
        <div class="extra_info">
            <a href="#" onclick="tb_show('{_p var='membership_upgrades' phpfox_squote=true}', $.ajaxBox('subscribe.listUpgradesOnSignup', 'height=400&width=500')); return false;">{_p var='click_here_to_learn_more_about_our_membership_upgrades'}</a>
        </div>
    </div>
    {/if}

    {module name='user.showspamquestion'}

    {if Phpfox::getParam('user.force_user_to_upload_on_sign_up')}
        {module name='core.upload-form' type='user_registration' unique_id=true force_convert_file=true}
    {/if}

    {plugin call='user.template_controller_register_pre_captcha'}

    {if Phpfox::isAppActive('Core_Captcha') && Phpfox::getParam('user.captcha_on_signup')  && ($sCaptchaType = Phpfox::getParam('captcha.captcha_type'))}
        <div id="js_register_capthca_image" {if Phpfox::getParam('user.multi_step_registration_form') && !isset($bIsPosted)} style="display:none;"{/if}  class="{$sCaptchaType}">
            {module name='captcha.form'}
        </div>
    {/if}
</div>

