<?php
defined('PHPFOX') or exit('NO DICE!');
?>

{if $iStep==1}
    <form method="post" class="form">
        <input type="hidden" name="force" value="{$bForceVerify}">
        <div class="form-group">
            <label for="verify_phone">{_p var='enter_your_phone'}</label>
            <p class="help-block">e.g +1 999 33 4455</p>
            <input id="verify_phone" class="form-control" type="tel" name="val[phone]" value=""/>
        </div>
        <div class="form-group">
            <div class="flex-wrapper-buttons">
                <input type="submit" name="val[publish]" value="{_p var='get_token'}" class="btn btn-primary">
                <a class="btn btn-default" href="{url link='login'}">{_p var='cancel_uppercase'}</a>
            </div>
        </div>
    </form>
    {module name='user.phone-number-country-codes' phone_field_id='#verify_phone'}
{/if}

{if $iStep==2 || $iStep==3}
<form method="post" action="{url link='user.sms.send'}" id="js_form_account_verification">
    <input type="hidden" name="val[phone]" value="{$sPhone}">
    <input type="hidden" name="val[email]" value="{$sEmail}">
    <input type="hidden" name="force" value="{$bForceVerify}">
    <input type="hidden" name="sent" value="{$bIsSent}">
    <div class="form-group">
        <label>{_p var="enter_a_verification_code"}</label>
        <p class="help-block">{if !empty($sPhone)}{_p var='text_message_was_just_sent_to_phone' phone=$sPhone}{else}{_p var='text_message_was_sent_to_to_phone'}{/if}</p>
        <input type="text" class="form-control" name="val[verify_sms_token]" value=""/>
    </div>
    <div class="form-group">
        <div class="flex-wrapper-buttons">
            <input type="submit" name="val[publish_passcode]" value="{_p var='submit'}" class="btn btn-primary">
            <input type="submit" name="val[resend_passcode]" value="{_p var='resend_passcode'}" class="btn btn-success">
            {if !$bIgnoreEmail}
                <input type="submit" name="val[change_phone]" value="{_p var='change_phone_number'}" class="btn btn-warning">
            {/if}
            <a class="btn btn-default" href="{url link=''}">{_p var='cancel_uppercase'}</a>
        </div>
    </div>
</form>
{/if}