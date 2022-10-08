<?php
defined('PHPFOX') or exit('NO DICE!');
?>
{if defined('PHPFOX_IS_ADMIN_SEARCH')}

{if !PHPFOX_IS_AJAX}
{template file="user.block.user_filter_admin"}

<div class="block_content">
	{literal}
	<script>
		function process_admincp_browse() {
			$('input.button').hide();
			$('#table_hover_action_holder, .table_hover_action').prepend('<div class="t_center admincp-browse-fa"><i class="fa fa-circle-o-notch fa-spin"></i></div>');
		}

		function delete_users(response, form, data) {
			// p(form);
			$('.admincp-browse-fa').remove();
			$('input.button').show();
			for (var i in data) {
				var e = data[i];
					// p('is delete...');
					form.find('input[type="checkbox"]').each(function() {
						if ($(this).is(':checked')) {
							if (e.name == 'delete') {
								$('#js_user_' + $(this).val()).remove();
							}
							else {
								$(this).prop('checked', false);
								var thisClass = $('#js_user_' + $(this).val());
								thisClass.removeClass('is_checked').addClass('is_processed');
								setTimeout(function() {
									thisClass.removeClass('is_processed');
								}, 600);
							}
						}
					});
			}
		};
	</script>
	{/literal}
	<form method="post" action="{url link='admincp.user.browse'}" class="ajax_post" data-include-button="true" data-callback-start="process_admincp_browse" data-callback="delete_users">
{/if}
		{if $aUsers}
        <div class="table-responsive">
            <table class="table table-admin" {if isset($bShowFeatured) && $bShowFeatured == 1} id="js_drag_drop"{/if}>
            <thead>
                <tr>
                    <th class="w20">
                        {if !PHPFOX_IS_AJAX}
                        <div class="custom-checkbox-wrapper">
                            <label>
                                <input type="checkbox" name="val[id]" value="" id="js_check_box_all" class="main_checkbox" />
                                <span class="custom-checkbox"></span>
                            </label>
                        </div>
                        {/if}
                    </th>
                    <th {table_sort class="w80 centered" asc="u.user_id asc" desc="u.user_id desc" query="search[sort]"}>
                        {_p var='id'}
                    </th>
                    <th>{_p var='photo'}</th>
                    <th {table_sort class="centered" asc="u.full_name asc" desc="u.full_name desc" query="search[sort]"}>
                        {_p var='display_name'}
                    </th>
                    <th>{_p var='email_address'}</th>
                    {if Phpfox::getParam('core.enable_register_with_phone_number')}
                        <th>{_p var='phone_number'}</th>
                    {/if}
                    <th>
                        {_p var='group'}
                    </th>
                    <th {table_sort class="centered" asc="u.last_activity asc" desc="u.last_activity desc" query="search[sort]"}>{_p var='last_activity'}</th>
                    <th>{_p var='last_ip_address'}</th>
                    <th class="w80 text-center">{_p var='settings'}</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$aUsers name=users key=iKey item=aUser}
                    {template file="user.block.user_entry_admin"}
                {/foreach}
            </tbody>
            </table>
        </div>

		{pager}
        {else}
            <div class="alert alert-empty">
            {_p var="No members found."}
            </div>
		{/if}

	{/if}
    {if !PHPFOX_IS_AJAX && defined('PHPFOX_IS_ADMIN_SEARCH')}
        <div class="table_hover_action hidden">
            <input type="button" name="move-to-group" value="{_p var='move_to_group'}" class="btn btn-default sJsCheckBoxButton disabled" disabled="disabled" data-ajax-box="user.moveUsersToGroup">
            <input type="submit" name="approve" value="{_p var='approve'}" class="btn btn-default sJsCheckBoxButton disabled" disabled="disabled" />
            <input type="submit" name="ban" value="{_p var='ban'}" class="btn btn-default sJsCheckBoxButton disabled" disabled="disabled" data-confirm-message="{_p var='are_you_sure_you_want_to_ban_selected_users'}"/>
            <input type="submit" name="unban" value="{_p var='un_ban'}" class="btn btn-default sJsCheckBoxButton disabled" disabled="disabled" data-confirm-message="{_p var='are_you_sure_you_want_to_un_ban_selected_users'}"/>
            <input type="submit" name="verify" value="{_p var='Verify'}" class="btn btn-default sJsCheckBoxButton disabled" disabled="disabled"/>
            <input type="submit" name="resend-verify" value="{_p var='resend_verification_mail'}" class="btn btn-default sJsCheckBoxButton disabled" disabled="disabled" />
            <input type="submit" name="resend-verify-code" value="{_p var='resend_verification_passcode'}" class="btn btn-default sJsCheckBoxButton disabled" disabled="disabled" />
            {if Phpfox::getUserParam('user.can_delete_others_account')}
                <input type="submit" name="delete" value="{_p var='delete'}" class="btn btn-danger sJsCheckBoxButton disabled" disabled="disabled" data-confirm-message="{_p var='are_you_sure_you_want_to_delete_selected_users'}"/>
            {/if}
        </div>
	</form>
</div>
{else}
    {if !PHPFOX_IS_AJAX}
        {module name='user.search'}
    {/if}
    {if !isset($highlightUsers) || !empty($bOldWay)}
        {if $aUsers}
            {if !PHPFOX_IS_AJAX}
            <div class="wrapper-items item-container user-listing" id="collection-users">
            {/if}
                {foreach from=$aUsers name=users item=aUser}
                    <article class="user-item js_user_item_{$aUser.user_id}">
                        {template file='user.block.rows_wide'}
                    </article>
                {/foreach}
                {pager}
            {if !PHPFOX_IS_AJAX}
            </div>
            {/if}
        {elseif !PHPFOX_IS_AJAX}
            <div class="alert">
                {_p var="No members found."}
            </div>
        {/if}
    {/if}
{/if}
