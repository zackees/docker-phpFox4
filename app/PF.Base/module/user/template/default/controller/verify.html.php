<?php
defined('PHPFOX') or exit('NO DICE!');
?>
{if isset($sTime)}
	<div>
        {_p var='the_link_that_brought_you_here_is_not_valid_it_may_already_have_expired' time=$sTime}
	</div>
{elseif !empty($bVerified)}
    <div>
        {_p var='your_email_has_been_verified_or_your_email_have_associate_with_any_account_on_our_site'}
    </div>
{else}
	<div>
		{_p var='this_site_is_very_concerned_about_security'}
	</div>
    {if $canResend}
	<div>
		<input type="button" value="{_p var='resend_verification_email'}" class="button btn-primary" onclick="$.ajaxCall('user.verifySendEmail', 'iUser={$iVerifyUserId}'); return false;" />
	</div>
    {/if}
{/if}