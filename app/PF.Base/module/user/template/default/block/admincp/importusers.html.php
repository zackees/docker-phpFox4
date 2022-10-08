<?php
defined('PHPFOX') or exit('NO DICE!');
?>

<div class="user-admincp-block-importusers" id="js_user_admincp_block_import_user">
    <div class="panel panel-default">
        <div class="panel-body">
            {if $bIsUpload}
            <div class="form-group">
                <div class="upload-title">{_p var='file_to_import'}</div>
                <input type="file" name="import_user_file" id="js_import_user_file" accept=".csv">
                <div class="upload-warning">{_p var='import_file_warning'}</div>
            </div>
            <div class="form-group">
                <div>{_p var='import_user_notice' link=$sTemplateDownloadLink}</div>
            </div>
            <div class="form-group hide_it" id="js_check_file_message">

            </div>
            <div class="form-group btn-action pull-right">
                <button class="btn btn-default" onclick="js_box_remove(this); return false;">{_p var='cancel'}</button>
                <button class="btn btn-primary" data-type="upload" id="js_import_start_btn" onclick="$Core.AdminCP.processUsers.processImportUser(this);">{_p var='upload'}</button>
            </div>
            {else}
            <form id="js_user_import_info" method="post" action="{url link='current'}">
                <input type="hidden" name="val[include_user_group]" value="{$bIsIncludeUserGroupField}">
                <div class="form-group">
                    {foreach from=$aFields key=sFieldKey item=sFieldValue}
                    <div class="import-field">
                        <label>
                            <input type="checkbox" name="val[selected_field][{$sFieldKey}]" value="{$sFieldKey}" checked {if in_array($sFieldKey, $aRequiredFields)}disabled="true"{/if}>
                            {if in_array($sFieldKey, $aRequiredFields)}
                            <input type="hidden" name="val[selected_field][{$sFieldKey}]" value="{$sFieldKey}" checked>
                            {/if}
                            {$sFieldValue}
                        </label>
                    </div>
                    {/foreach}
                </div>
                <div class="form-group" style="float: left;">
                    <label>{_p var='group'}</label>
                    <select name="val[user_group_id]" class="form-control">
                        <option value="">{_p var='any'}</option>
                        {foreach from=$aUserGroups key=sGroupKey item=sGroupValue}
                        <option value="{$sGroupKey}">{$sGroupValue}</option>
                        {/foreach}
                    </select>
                    <p class="import-group-notice">{_p var='import_info_notice_user_group'}</p>
                </div>
                <div class="form-group hide_it" id="js_check_file_message"></div>
                <div class="form-group pull-right">
                    <button class="btn btn-primary" data-type="import" id="js_import_start_btn" onclick="$Core.AdminCP.processUsers.processImportUser(this); return false;">{_p var='import'}</button>
                </div>
            </form>
            {/if}
        </div>
    </div>
</div>

{literal}
    <script type="text/javascript">
        if (typeof oTranslations !== "undefined") {
            oTranslations['you_need_to_upload_file_first'] = '{/literal}{_p var='you_need_to_upload_file_first'}{literal}';
        }
    </script>
{/literal}

