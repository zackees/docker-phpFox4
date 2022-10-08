<?php 
defined('PHPFOX') or exit('NO DICE!');
?>
<div class="main_break">
    <form class="form" method="post" action="{url link='user.password.request'}" id="js_request_password_form">
        {if !empty($bPassCodeForm)}
            <input type="hidden" name="val[email]" value="{if !empty($aForms.email)}{$aForms.email}{/if}">
            <input type="hidden" name="val[request_hash]" value="{$sHash}">
            <div class="form-group">
                <label>{_p var="enter_a_verification_code"}</label>
                <p class="help-block">{_p var='text_message_was_sent_to_to_phone'} {if !empty($aForms.email)}<b>{$aForms.email}</b>{/if}</p>
                <input type="text" class="form-control" name="val[verify_sms_token]" value="" />
                <input type="hidden" name="val[is_phone_verify]" value="{$isPhoneVerify}" />
            </div>
            {if Phpfox::isAppActive('Core_Captcha')}{module name='captcha.form' sType='lostpassword'}{/if}
            <div class="form-group">
                <div class="flex-wrapper-buttons">
                    <input type="submit" name="val[verify_code]" value="{_p var='submit'}" class="btn btn-primary">
                    <input type="submit" name="val[resend_passcode]" value="{_p var='resend_passcode'}" class="btn btn-success">
                    <a class="btn btn-default" href="{url link=''}">{_p var='cancel_uppercase'}</a>
                </div>
            </div>
        {else}
            <div class="form-group">
                <label for="email">{if Phpfox::getParam('core.enable_register_with_phone_number')}{_p var='email_or_phone_number'}{else}{_p var='email'}{/if}</label>
                <input class="form-control" type="text" name="val[email]" id="email" value="{value id='email' type='input'}" size="40" />
                {if Phpfox::getParam('core.enable_register_with_phone_number')}
                    {module name='user.phone-number-country-codes' init_onchange=1 phone_field_id='#email'}
                {/if}
            </div>
            {if Phpfox::isAppActive('Core_Captcha')}{module name='captcha.form' sType='lostpassword'}{/if}
            <div class="form-group">
                <input type="submit" value="{_p var='request_new_password'}" class="btn btn-danger" />
            </div>
        {/if}
    </form>
</div>
