<?php 
defined('PHPFOX') or exit('NO DICE!');

?>
{if $bIsRegistration}
    <div class="t_right p_top_10" style="font-size:10pt; font-weight:bold;">
        <a href="{$sNextUrl}">{_p var='skip_this_step'}</a>
    </div>
{/if}

<div class="">
	<div class="form-group">
		{if isset($aValid)}
            {if count($aValid)}
                {_p var='you_have_successfully_sent_an_invitation_to'}:
                <div class="p_4">
                    <div class="label_flow" style="height:100px;">
                        {foreach from=$aValid name=emails item=sEmail}
                            <div class="{if is_int($phpfox.iteration.emails/2)}row1{else}row2{/if} {if $phpfox.iteration.emails == 1} row_first{/if}" style="padding-top: 0; padding-bottom: 8px">{$sEmail}</div>
                        {/foreach}
                    </div>
                </div>
                <br/>
            {/if}
            {if count($aInValid)}
                {if Phpfox::getParam('core.enable_register_with_phone_number')}{_p var='the_following_emails_or_phone_numbers_were_not_sent'}{else}{_p var='the_following_emails_were_not_sent'}{/if}:
                <div class="p_4">
                    <div class="label_flow" style="height:100px;">
                        {foreach from=$aInValid name=emails item=sEmail}
                            <div class="{if is_int($phpfox.iteration.emails/2)}row1{else}row2{/if} {if $phpfox.iteration.emails == 1} row_first{/if}" style="padding-top: 0; padding-bottom: 8px">{$sEmail}</div>
                        {/foreach}
                    </div>
                </div>
                <br/>
            {/if}
            {if count($aUsers) || count($aUsersByPhone)}
                {_p var='the_following_users_are_already_a_member_of_our_community'}:
                <div class="p_4">
                    <div class="label_flow" style="height:100px;">
                        {foreach from=$aUsers name=users item=aUser}
                            <div class="{if is_int($phpfox.iteration.users/2)}row1{else}row2{/if} {if $phpfox.iteration.users == 1} row_first{/if}" id="js_invite_user_{$aUser.user_id}" style="padding-top: 0; padding-bottom: 8px">
                                {if $aUser.user_id == Phpfox::getUserId()}
                                    {$aUser.email} - {_p var='that_s_you'}
                                {else}
                                    {$aUser.email} - {$aUser|user}{if Phpfox::isModule('friend') && !$aUser.friend_id && Phpfox::getService('user.privacy')->hasAccess('' . $aUser.user_id . '', 'friend.send_request')} - <a href="#?call=friend.request&amp;user_id={$aUser.user_id}&amp;width=420&amp;height=250&amp;invite=true" class="inlinePopup" title="{_p var='add_to_friends'}">{_p var='add_to_friends'}</a>{/if}
                                {/if}
                            </div>
                        {/foreach}
                        {foreach from=$aUsersByPhone name=users item=aUser}
                        <div class="{if is_int($phpfox.iteration.users/2)}row1{else}row2{/if} {if $phpfox.iteration.users == 1} row_first{/if}" id="js_invite_user_{$aUser.user_id}" style="padding-top: 0; padding-bottom: 8px">
                            {if $aUser.user_id == Phpfox::getUserId()}
                                {$aUser.full_phone_number} - {_p var='that_s_you'}
                            {else}
                                {$aUser.full_phone_number} - {$aUser|user}{if Phpfox::isModule('friend') && !$aUser.friend_id && Phpfox::getService('user.privacy')->hasAccess('' . $aUser.user_id . '', 'friend.send_request')} - <a href="#?call=friend.request&amp;user_id={$aUser.user_id}&amp;width=420&amp;height=250&amp;invite=true" class="inlinePopup" title="{_p var='add_to_friends'}">{_p var='add_to_friends'}</a>{/if}
                            {/if}
                        </div>
                        {/foreach}
                    </div>
                </div>
            {/if}
		{else}
			{_p var='invite_your_friends_to_b_title_b' title=$sSiteTitle}
			<br />
			<br />
			{if Phpfox::getParam('invite.make_friends_on_invitee_registration')}
				{_p var='your_friend_will_automatically_be_added_to_your_friends_list_when_they_join'}
			{/if}
		{/if}
	</div>
	
	{plugin call='invite.template_controller_index_h3_start'}

	<form id="js_invite_form" class="form" method="post" action="{if $bIsRegistration}{url link='invite.register'}{else}{url link='invite'}{/if}">
        <div class="form-group">
            <h3>{_p var='email_your_friends'}</h3>
        </div>
		<div class="">
            <label for="">{_p var='subject'}</label>
            {_p var='full_name_invites_you_to_title' full_name=$sFullName title=$sSiteTitle}
		</div>
		<div class="">
            <label for="">{_p var='from'}</label>
            {$sSiteEmail}
		</div>
		<div class="form-group">
            <label for="emails">{_p var='to'}</label>
            <input class="form-control autogrow" id="emails" name="val[emails]" data-component="tokenfield" data-type="email" onkeydown="$Core.resizeTextarea($(this));" onkeyup="$Core.resizeTextarea($(this));"/>
            <p class="help-block">
                {_p var='separate_multiple_emails_with_comma_or_enter_or_tab'}
            </p>
		</div>
        {if Phpfox::getParam('core.enable_register_with_phone_number')}
        <div class="form-group">
            <h3>{_p var='sms_your_friends'}</h3>
        </div>
        <div class="form-group">
            <input class="form-control autogrow" id="emails" name="val[phones]" data-component="tokenfield" data-type="phone" onkeydown="$Core.resizeTextarea($(this));" onkeyup="$Core.resizeTextarea($(this));"/>
            <p class="help-block">
                {_p var='separate_multiple_phone_numbers_with_a_commas_or_enter_or_tab'}
            </p>
        </div>
        {/if}
        <div class="form-group">
            <h3>{_p var='add_a_personal_message'}</h3>
            <textarea rows="3" name="val[personal_message]" id="personal_message" class="form-control" placeholder="{_p var='write_message'}"></textarea>
        </div>
        <input type="submit" value="{_p var='send_invitation_s'}" class="btn btn-primary" />
	</form>
</div>