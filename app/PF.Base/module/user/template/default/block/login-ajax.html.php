<?php
defined('PHPFOX') or exit('NO DICE!');

//use when do some action need to be user
?>
{if Phpfox::getParam('core.enable_register_with_phone_number')}
    {add_script key='jquery/plugin/intlTelInput.js' value='static_script'}
{/if}

{if $bIsAJaxAdminCp}
<div class="error_message">
	{_p var='you_have_logged_out_of_the_site'}
</div>
<script type="text/javascript">
	window.location.href = '{url link='user.login'}';
</script>
{else}
<div class="error_message">
	{_p var='you_need_logged_that'}
</div>
<form method="post" id="js_login_form" class="form" action="{url link="user.login"}">
    <input type="hidden" name="val[parent_refresh]" value="1"/>
	<div class="p_top_4">
        {if !Phpfox::getParam('core.enable_register_with_phone_number')}
            <label for="js_email">{if Phpfox::getParam('user.login_type') == 'user_name'}{_p var='user_name'}{elseif Phpfox::getParam('user.login_type') == 'email'}{_p var='email'}{else}{_p var='email_or_user_name'}{/if}</label>:
        {else}
            {if Phpfox::getParam('user.login_type') != 'user_name'}
                {module name='user.phone-number-country-codes' init_onchange=1 phone_field_id='#js_email'}
            {/if}
            <label for="js_email">{if Phpfox::getParam('user.login_type') == 'user_name'}{_p var='user_name'}{elseif Phpfox::getParam('user.login_type') == 'email'}{_p var='email_or_phone_number'}{else}{_p var='email_or_user_name_or_phone_number'}{/if}</label>:
        {/if}
		<div class="mb-1">
			<input type="text" name="val[login]" id="js_email" value="" class="form-control p_4"/>
	    </div>
    </div>

	<div class="form-group">
		<label for="js_password">{_p var='password'}:</label>
        <input type="password" name="val[password]" id="js_password" value="" class="form-control" autocomplete="off" />
	</div>

    {if Phpfox::isAppActive('Core_Captcha') && Phpfox::getParam('user.captcha_on_login') && ($sCaptchaType = Phpfox::getParam('captcha.captcha_type'))}
        <div id="js_register_capthca_image" class="{$sCaptchaType}">
            {module name='captcha.form'}
        </div>
    {/if}

    <div class="checkbox">
        <label>
            <input type="checkbox" name="val[remember_me]" value="" class="checkbox" /> {_p var='remember'}
        </label>
    </div>
	
	<div class="form-buttons-group">
		{if Phpfox::getParam('user.allow_user_registration')}
		<div class="action_contain">
			<button type="button" class="btn btn-sm btn-primary" onclick="window.location.href = '{url link='user.register'}';" >{_p var='register_for_an_account'}</button>
		</div>			
		{/if}
		<button type="submit" class="btn btn-sm btn-success">
			{_p var='sign_in'}
		</button>
	</div>
</form>
{literal}
<style>
	.form-buttons-group{
		display: flex;
		flex-direction: row-reverse;
		justify-content: space-between;
		margin-top: 16px;
	}
</style>
{/literal}
<script type="text/javascript">
  $Core.loadInit();
</script>
{/if}