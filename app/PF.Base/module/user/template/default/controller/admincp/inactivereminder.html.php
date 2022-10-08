<?php
defined('PHPFOX') or exit('NO DICE!');
?>
<form method="get" action="{url link='admincp.user.inactivereminder'}" id="js_admincp_inactive_reminder">
    <div class="panel panel-default">
        <div class="panel-heading">
            {_p var='inactive_member_reminder'}
        </div>
        <div class="panel-body">
            <div class="form-group form-inline">
                <label>{_p var='show_users_who_have_not_logged_in_for'}:</label>
                <input type="number" class="form-control" min="0" id="inactive_days" name="day" size="3" value="{$iDays}"> {_p var='days'}
            </div>

            {template file="user.block.user_filter_admin"}

            <div class="form-group">
                <label>{_p var='this_feature_uses_the_language_phrases'}</label>
                {if Phpfox::getParam('core.enable_register_with_phone_number')}
                    <label>{_p var='this_feature_also_uses_the_language_phrases_sms'}</label>
                {/if}
            </div>
            <hr />
            <div class="form-group" style="display: flex;flex-flow: wrap;">
                <input id="js_submit_btn" type="submit" value="{_p var='get_inactive_members'}" class="btn btn-primary mb-1" style="margin-right: 8px;"/>
                <input type="button" {if !Phpfox::getParam('core.enable_register_with_phone_number')}value="{_p var='process_mailing_job_to_all_inactive_members'}"{else}value="{_p var='process_mailing_sms_job_to_all_inactive_members'}" data-sms="1" {/if} class="btn btn-primary btn-truncate mb-1" id="btnSendAll" style="margin-right: 8px;"/>
                <a class="btn btn-default" href="{$searchLink}">{_p var='reset'}</a>
            </div>
        </div>
    </div>
    <div class="block_content">
        {if $aUsers}
            <div class="table-responsive">
                <table class="table table-admin">
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
                            <th {table_sort class="w80 centered" asc="u.user_id asc" desc="u.user_id desc" query="search[sort]"}>{_p var='id'}</th>
                            <th>{_p var='photo'}</th>
                            <th {table_sort class="centered" asc="u.full_name asc" desc="u.full_name desc" query="search[sort]"}>
                                {_p var='display_name'}
                            </th>
                            <th>{_p var='email_address'}</th>
                            {if Phpfox::getParam('core.enable_register_with_phone_number')}
                                <th>{_p var='phone_number'}</th>
                            {/if}
                            <th>{_p var='group'}</th>
                            <th {table_sort class="centered" asc="u.last_activity asc" desc="u.last_activity desc" query="search[sort]"}>
                                {_p var='last_activity'}</th>
                            <th class="w80 t_center">{_p var='settings'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$aUsers name=users key=iKey item=aUser}
                            <tr class="{if empty($aUser.in_process)}checkRow{else}process_mail{/if}{if is_int($iKey/2)} tr{else}{/if}" id="js_user_{$aUser.user_id}">
                                <td>
                                    {if !isset($aUser.in_process) || empty($aUser.in_process)}
                                        <div class="custom-checkbox-wrapper">
                                            <label>
                                                <input type="checkbox" name="id[]" class="checkbox" value="{$aUser.user_id}" id="js_id_row{$aUser.user_id}" />
                                                <span class="custom-checkbox"></span>
                                            </label>
                                        </div>
                                    {/if}
                                </td>
                                <td>{$aUser.user_id}</td>
                                <td>{img user=$aUser suffix='_120_square' max_width=50 max_height=50}</td>
                                <td>{$aUser|user}</td>
                                <td>
                                    <a href="mailto:{$aUser.email}">{if (isset($aUser.pendingMail) && $aUser.pendingMail != '')} {$aUser.pendingMail} {else} {$aUser.email} {/if}</a>
                                    {if isset($aUser.unverified) && $aUser.unverified > 0 && (!isset($aUser.unverified_type) || $aUser.unverified_type == 'email')}
                                        <a href="javascript:void(0)" class="js_verify_email_{$aUser.user_id} text-danger" onclick="$.ajaxCall('user.verifyEmail', 'iUser={$aUser.user_id}');">{_p var='verify'}</a>
                                    {/if}
                                </td>
                                {if Phpfox::getParam('core.enable_register_with_phone_number')}
                                    <td>
                                        <a href="tel:{$aUser.full_phone_number}">{$aUser.full_phone_number|phone}</a>
                                        {if isset($aUser.unverified) && $aUser.unverified > 0 && isset($aUser.unverified_type) && $aUser.unverified_type == 'phone'}
                                            <a href="javascript:void(0)" class="js_verify_email_{$aUser.user_id} text-danger" onclick="$.ajaxCall('user.verifyEmail', 'iUser={$aUser.user_id}');">{_p var='verify'}</a>
                                        {/if}
                                    </td>
                                {/if}
                                <td>
                                    {if ($aUser.status_id == 1)}
                                        <div class="js_verify_email_{$aUser.user_id}">
                                            {if isset($aUser.unverified_type)}
                                                {if $aUser.unverified_type == 'phone'}
                                                    {_p var='pending_phone_number_verification'}
                                                {elseif $aUser.unverified_type == 'sms'}
                                                    {_p var='pending_sms_verification'}
                                                {else}
                                                    {_p var='pending_email_verification'}
                                                {/if}
                                            {else}
                                                {_p var='pending_email_verification'}
                                            {/if}
                                        </div>
                                    {/if}
                                    {if Phpfox::getParam('user.approve_users') && $aUser.view_id == '1'}
                                        <span id="js_user_pending_group_{$aUser.user_id}">{_p var='pending_approval'}</span>
                                    {elseif $aUser.view_id == '2'}
                                        {_p var='not_approved'}
                                    {else}
                                        {$aUser.user_group_title|convert}
                                    {/if}
                                </td>
                                <td>
                                    {if $aUser.last_activity > 0}
                                        {$aUser.last_activity|date:'core.global_update_time'}
                                    {/if}
                                    {if !empty($aUser.last_ip_address)}
                                        <div class="">
                                            (<a href="{url link='admincp.core.ip' search=$aUser.last_ip_address_search}" title="{_p var='view_all_the_activity_from_this_ip'}">{$aUser.last_ip_address}</a>)
                                        </div>
                                    {/if}
                                </td>
                                <td class="t_center">
                                    {if !isset($aUser.in_process) || empty($aUser.in_process)}
                                        <a role="button" class="js_drop_down_link" title="{_p var='manage'}"></a>
                                        <div class="link_menu">
                                            <ul class="dropdown-menu dropdown-menu-right">
                                                <li><a href="#?call=user.addInactiveJob&amp;id={$aUser.user_id}" class="js_item_active_link">{if Phpfox::getParam('core.enable_register_with_phone_number')}{_p var='process_mailing_sms_job'}{else}{_p var='process_mailing_job'}{/if}</a></li>
                                            </ul>
                                        </div>
                                    {/if}
                                </td>
                            </tr>
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

        <div class="table_hover_action hidden">
            <input type="submit" name="resend-verify" value="{if !Phpfox::getParam('core.enable_register_with_phone_number')}{_p var='process_mailing_job_to_selected'}{else}{_p var='process_mailing_sms_job_to_selected'}{/if}" class="btn btn-primary sJsCheckBoxButton disabled" disabled="disabled" />
        </div>
    </div>
</form>

{literal}
    <script type="text/javascript">
        $Behavior.initAdmincpInactiveReminder = function() {
            let form =  $('#js_admincp_inactive_reminder');
            if (form.length) {
                form.find('#js_submit_btn').off('click').on('click', function() {
                    form.find('input[type="checkbox"]').prop('checked', false);
                    form.submit();
                    return false;
                });
            }
        }
    </script>
{/literal}
