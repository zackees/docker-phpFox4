<?php
defined('PHPFOX') or exit('NO DICE!');
?>
{literal}
<style type="text/css">

</style>
{/literal}
<div class="login-auth-methods" id="login-auth-methods">
    <ul class="methods-container" id="js_auth_methods">
        {if !empty($sPhoneNumber)}
            <li class="auth-method">
                <a href="javascript:void(0)" onclick="return sendLoginPasscodeVia('phone_{$iUserId}','{$sPhoneNumber}');">
                    <div class="item">
                        <span class="ico ico-comment-square-o"></span>
                        <div>
                            <span>{_p var='get_a_verification_code_at_item' item=$sPhoneNumber}</span>
                            <span class="help-block">{_p var='text_me_a_verification_code_via_phone_number'}</span>
                        </div>
                    </div>
                </a>
            </li>
        {/if}
        {if !empty($sEmail)}
            <li class="auth-method">
                <a href="javascript:void(0)" onclick="return sendLoginPasscodeVia('email_{$iUserId}','{$sEmail}');">
                    <div class="item">
                        <span class="ico ico-envelope-o"></span>
                        <div>
                            <span>{_p var='get_a_verification_code_at_item' item=$sEmail}</span>
                            <span class="help-block">{_p var='send_me_a_verification_code_via_email'}</span>
                        </div>
                    </div>
                </a>
            </li>
        {/if}
    </ul>
    <div id="js_sending_passcode" style="display: none"></div>
</div>

{literal}
<script>
    function sendLoginPasscodeVia(user, login) {
        $.ajaxCall('user.sendLoginPasscode', $.param({
            user: user,
            login: login
        }));
        $('#js_login_passcode_note').addClass('disabled');
        $('#js_auth_methods').hide();
        $('#js_sending_passcode').html('<div class="t_center"><i class="fa fa-spinner fa-spin" style="font-size:200%"></i><div class="mt-1 help-block">{/literal}{_p var='sending_verification_code'}{literal}</div></div>').show()
        return false;
    }
</script>
{/literal}