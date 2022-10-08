<?php
defined('PHPFOX') or exit('NO DICE!');
?>
{if !empty($sCreateJs)}
    {$sCreateJs}
{/if}
{if !empty($bVerifyTwoStepLogin)}
    <form method="post" action="{url link='login' token=$sCurrentToken}" id="js_login_form">
        <div class="form-group mt-2">
            <p class="mb-1"><strong>{_p var='two_step_verification_explain_in_login_process'}</strong></p>
            <p class="mb-2"><strong>{_p var='please_enter_your_authenticator_six_digit_code'}</strong></p>
            <input class="form-control" required placeholder="{_p var='enter_passcode'}" type="text" name="val[passcode]" id="passcode" value="" size="40" />
            <input type="hidden" name="val[login]" value="{$sCurrentLogin}">
            <input type="hidden" name="val[password]" value="{$sCurrentPassword}">
            <input type="hidden" name="val[token]" value="{$sCurrentToken}">
            {if !empty($sCurrentRemember)}
                <input type="hidden" name="val[remember_me]" value="1">
            {/if}
            <p class="help-block">{_p var='get_a_verification_code_from_the_authenticator_app'}</p>
            <a id="js_login_passcode_note" class="login-passcode-note" href="javascript:void(0)" onclick="tb_show('{_p var='try_another_way_to_authenticate'}', $.ajaxBox('user.getAuthMethods', 'user_id={$sCurrentLoginUser}&amp;width=500&amp;height=300'));return false;">{_p var='try_another_way_to_authenticate'}</a><span id="js_login_passcode_waiting_time" class="pl-1 text text-danger"></span>
        </div>
        {if Phpfox::isAppActive('Core_Captcha') && Phpfox::getParam('user.captcha_on_login') && ($sCaptchaType = Phpfox::getParam('captcha.captcha_type'))}
            <div id="js_register_capthca_image" class="{$sCaptchaType}">
                {module name='captcha.form'}
            </div>
        {/if}
        <div class="form-button-group">
            <button id="_submit" type="submit" class="btn btn-primary mr-1">
                {_p var='verify_passcode'}
            </button>
            <a href="{url link=''}" class="btn btn-default">{_p var='cancel'}</a>
        </div>
    </form>
{else}
    <ul class="signin_signup_tab clearfix">
        <li class="active"><a rel="hide_box_title" href="javascript:void(0)">{_p var='sign_in'}</a></li>
        {if Phpfox::getParam('user.allow_user_registration')}
            <li><a class="keepPopup" rel="hide_box_title" href="{url link='user.register'}">{_p var='sign_up'}</a></li>
        {/if}
    </ul>
    {plugin call='user.template_controller_login_block__start'}
    <form class="content" method="post" action="{url link="user.login"}" id="js_login_form" {if !empty($sGetJsForm)}onsubmit="{$sGetJsForm}"{/if}>
    <div class="form-group">
        <div class="">
            {if !Phpfox::getParam('core.enable_register_with_phone_number')}
                <input class="form-control" placeholder="{if Phpfox::getParam('user.login_type') == 'user_name'}{_p var='user_name'}{elseif Phpfox::getParam('user.login_type') == 'email'}{_p var='email'}{else}{_p var='email_or_user_name'}{/if}" type="{if Phpfox::getParam('user.login_type') == 'email'}email{else}text{/if}" name="val[login]" id="login" value="{$sDefaultEmailInfo}" size="40" autofocus/>
            {else}
                <input class="form-control" placeholder="{if Phpfox::getParam('user.login_type') == 'user_name'}{_p var='user_name'}{elseif Phpfox::getParam('user.login_type') == 'email'}{_p var='email_or_phone_number'}{else}{_p var='email_or_user_name_or_phone_number'}{/if}" type="text" name="val[login]" id="login" value="{$sDefaultEmailInfo}" size="40" autofocus/>
                {if Phpfox::getParam('user.login_type') != 'user_name'}
                    {module name='user.phone-number-country-codes' init_onchange=1 phone_field_id='#login'}
                {/if}
            {/if}
        </div>
        <div class="clear"></div>
    </div>

    <div class="form-group">
        <input class="form-control" placeholder="{_p var='password'}" type="password" name="val[password]" id="login_password" value="" size="40" autocomplete="off" />
    </div>

    {if Phpfox::isAppActive('Core_Captcha') && Phpfox::getParam('user.captcha_on_login') && ($sCaptchaType = Phpfox::getParam('captcha.captcha_type'))}
    <div id="js_register_capthca_image" class="{$sCaptchaType}">
        {module name='captcha.form'}
    </div>
    {/if}

    {plugin call='user.template_controller_login_end'}

    <div class="form-group">
        <button id="_submit" type="submit" class="btn btn-primary text-uppercase">
            {_p var='sign_in'}
        </button>
        <div class="p_top_base checkbox">
            <ul class="clearfix">
                <li><label><input type="checkbox" class="checkbox" name="val[remember_me]" value="" /> {_p var='remember'}</label></li>
                <li><a class="no_ajax" href="{url link='user.password.request'}">{_p var='forgot_your_password'}</a></li>
            </ul>
        </div>

        {plugin call='user.template.login_header_set_var'}
        {if isset($bCustomLogin)}
            <div class="custom_login_fb">
                <div class="item-or-line"><span>{_p var='or'}</span></div>
                <div class="p_top_4">
                    {plugin call='user.template_controller_login_block__end'}
                </div>
            </div>
        {/if}
    </div>
    <input type="hidden" name="val[parent_refresh]" value="1" />
    {if isset($sMainUrl)}
        <input type="hidden" name="val[redirect_url]" value="{$sMainUrl}">
    {/if}
    </form>
    {if !PHPFOX_IS_AJAX}
        <script type="text/javascript">
            document.getElementById('js_login_form').getElementsByTagName("input")[0].focus()
        </script>
    {/if}
{/if}