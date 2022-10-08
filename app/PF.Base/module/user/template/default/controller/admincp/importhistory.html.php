<?php
defined('PHPFOX') or exit('NO DICE!');
?>
<div class="admincp-user-import-history" id="js_admincp_user_import_history">
    {if $bIsImportHistoryPage}
    <div class="js_import_history_content" data-date-core-format="{$sCurrentDate}" data-date-default-format="{$sDefaultDateFormat}">
        <div class="search">
            <div class="panel panel-default">
                <div class="panel-heading">{_p var='search'}</div>
                <div class="panel-body">
                    <form id="js_import_user_history_form" method="GET" action="{url link='admincp.user.importhistory'}">
                        <div class="form-group">
                            <label>{_p var='owner'}</label>
                            <input class="form-control" type="text" name="search[owner]" value="{value type='input' id='owner'}" id="js_search_owner">
                        </div>
                        <div class="form-group">
                            <label>{_p var='status'}</label>
                            <select class="form-control"  name="search[status]" id="js_search_status">
                                <option value="" >{_p var='select'}:</option>
                                {foreach from=$aStatus key=status_key item=status_value}
                                <option value="{$status_key}" {value type='select' id='status' default=$status_key}>{$status_value}</option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{_p var='from_date'}</label>
                            {select_date prefix='from_' id='_start' start_year='2000' end_year='+1' field_separator=' / ' field_order='MDY' default_all=true time_separator='core.time_separator' name='search'}
                        </div>
                        <div class="form-group">
                            <label>{_p var='to_date'}</label>
                            {select_date prefix='to_' id='_end' start_year='2000' end_year='+1' field_separator=' / ' field_order='MDY' default_all=true time_separator='core.time_separator' name='search'}
                        </div>
                        <div class="form-group">
                            <button class="btn btn-primary">{_p var='search'}</button>
                            <button class="btn btn-default" onclick="$Core.AdminCP.processUsers.resetHistorySearchForm(this); return false;">{_p var='reset'}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="import-list">
            {if count($aImports)}
            <div class="table-responsive">
                <table class="table table-admin">
                    <thead>
                    <tr>
                        <th class="text-center">{_p var='owner'}</th>
                        <th class="text-center">{_p var='date'}</th>
                        <th class="text-center">{_p var='file_name'}</th>
                        <th class="text-center">{_p var='status'}</th>
                        <th class="text-center">{_p var='total_users'}</th>
                        <th class="text-center">{_p var='total_imported'}</th>
                        <th class="text-center">{_p var='action'}</th>
                    </tr>
                    </thead>
                    <tbody>
                    {foreach from=$aImports item=aImport}
                    <tr id="js_import_item_{$aImport.import_id}">
                        <td class="text-center">{$aImport|user}</td>
                        <td class="text-center">{$aImport.time_stamp|convert_time}</td>
                        <td class="text-center">{$aImport.file_name}</td>
                        <td class="text-center js_import_status">
                            {if $aImport.status == 'processing'}
                                {_p var='processing'}
                            {elseif $aImport.status == 'stopped'}
                                {_p var='stopped'}
                            {elseif $aImport.status == 'completed'}
                                {_p var='done'}
                            {/if}
                        </td>
                        <td class="text-center">{$aImport.total_user}</td>
                        <td class="text-center">{$aImport.total_imported}</td>
                        <td class="text-center js_import_action">
                            {if $aImport.status == 'processing'}
                            <a href="javascript:void(0);" onclick="$.ajaxCall('user.deleteProcessingImport', 'import_id={$aImport.import_id}'); return false;">{_p var='stop'}</a>
                            {elseif $aImport.status == 'completed'}
                            <a href="{url link='admincp.user.importhistory' import_id=$aImport.import_id}">{_p var='view_log'}</a>
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
                {_p var='no_import_found'}
            </div>
            {/if}
        </div>
    </div>
    {else}
    <div class="js_import_error_log_content">
        {if $bIsValidImport}
        <div class="title">
            {_p var='error_log'}
        </div>
        <div class="content">
            <div>
                <span class="mr-32"><span class="fw-bold">{_p var='owner'}</span>: {$aImport|user}</span>
                <span class="mr-32"><span class="fw-bold">{_p var='import_date'}</span>: {$aImport.time_stamp|convert_time}</span>
            </div>
            {if !empty($aErrorLogs)}
            <div class="mt-16">
                <div class="table-responsive">
                    <table class="table table-admin">
                        <thead>
                        <tr>
                            <th class="text-center">{_p var='row'}</th>
                            {foreach from=$aFields key=field_key_title item=field}
                                <th class="text-center">{$field}{if in_array($field_key_title, $aRequiredFields)}{required}{/if}</th>
                            {/foreach}
                        </tr>
                        </thead>
                        <tbody>
                        {foreach from=$aErrorLogs key=error_key item=aErrorLog}
                            <tr>
                                <td class="text-center">{$error_key}</td>
                                {foreach from=$aErrorLog key=field_key item=error_value}
                                    <td class="text-center">
                                        {if !empty($error_value.error_code)}
                                            <span class="ico ico-warning-circle-o cursor-pointer" onclick="tb_show('{_p var='error_log'}',$.ajaxBox('user.showImportLog','width=400&height=300&row={$error_key}&field={$field_key}&log={$error_value.error_code}')); return false;"></span>
                                        {else}
                                            {$error_value|clean}
                                        {/if}
                                    </td>
                                {/foreach}
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
            {pager}
            {else}
            <div class="alert alert-empty" style="background-color: antiquewhite">
                {_p var='import_no_errors_found'}
            </div>
            {/if}
        </div>
        {else}
        <div class="error_message">
            {_p var='import_invalid_import'}
        </div>
        {/if}
    </div>
    {/if}
</div>
