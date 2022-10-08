<?php
defined('PHPFOX') or exit('NO DICE!');
?>
{if count($aItems) > 0}
<form method="post" class="form" action="{url link='current'}" id="js_storage_transfer_file_form">
    <p>
        {_p var='storage_transfer_description'}
    </p>
    <div id="client_details" class="panel panel-default">
        <div class="panel-body">
            <div>
                <div class="form-group">
                    {template file='admincp.block.transfer-file-configuration-notice'}
                </div>
                <div class="form-group">
                    <label class="required" for="storage_id">{_p var='storage'}</label>
                    {foreach from=$aItems item=aItem}
                    <div>
                        <label style="font-weight: normal !important;">
                            <input type="radio" value="{$aItem.storage_id}" name="val[transfer_storage_id]" {if isset($sTransferStorageId) && $sTransferStorageId == $aItem.storage_id}checked{/if}>
                            &nbsp;#{$aItem.storage_id} {if $aItem.storage_name}{$aItem.storage_name}{else}{$aItem.service_phrase_name|convert}{/if}
                        </label>
                    </div>
                    {/foreach}
                </div>
                <div class="form-group" id="js_storage_remove_local">
                    <label>{_p var='remove_files_from_local'}</label>
                    <div class="item_is_active_holder">
                        <span class="js_item_active item_is_active">
                            <input type="radio" name="val[remove_file]" value="1" {value type='radio' id='remove_file' default='1'}/>
                        </span>
                        <span class="js_item_active item_is_not_active">
                            <input type="radio" name="val[remove_file]" value="0" {value type='radio' id='remove_file' default='0' selected='true'}/>
                        </span>
                    </div>
                    <div class="help-block">{_p var='storage_transfer_delete_local_file_description'}</div>
                </div>
                <div class="form-group" id="js_storage_update_database">
                    <label>{_p var='update_database'}</label>
                    <div class="item_is_active_holder">
                        <span class="js_item_active item_is_active">
                            <input type="radio" name="val[update_database]" value="1" {value type='radio' id='remove_file' default='1'}/>
                        </span>
                        <span class="js_item_active item_is_not_active">
                            <input type="radio" name="val[update_database]" value="0" {value type='radio' id='remove_file' default='0' selected='true'}/>
                        </span>
                    </div>
                    <div class="help-block">{_p var='storage_transfer_update_database_description'}</div>
                    <div class="help-block">{_p var='storage_transfer_update_database_notice'}</div>
                </div>
            </div>
        </div>
        <div class="panel-footer">
            <div class="panel-title">
                {_p var='total_total_files' total=$iTotalFile}
            </div>
            <hr/>
            <div  {if count($aFiles)}style="height: 300px; overflow-y: auto"{/if}>
                <ol>
                    {foreach from=$aFiles item=sFilename}
                    <li>{$sFilename}</li>
                    {/foreach}
                </ol>
            </div>
        </div>
        {if !empty($aForms) && !empty($sStatus)}
            <div class="panel-body {if $sStatus == 'completed'}bg-success{else}bg-info{/if} table-responsive">
                <div class="panel-title">
                    <b>{_p var='latest_transfer_files_process'}</b>
                </div>
                <br>
                <table class="table table-admin">
                    <tr class="tr">
                        <td><b class="text-danger">{_p var='status'}</b></td>
                        <td>{_p var=$sStatus}</td>
                    </tr>
                    <tr>
                        <td><b class="text-danger">{_p var='last_updated'}</b></td>
                        <td>{$aForms.update_time|date:'feed.feed_display_time_stamp'}</td>
                    </tr>
                    <tr>
                        <td><b class="text-danger">{_p var='storage'}</b></td>
                        <td>#{$aForms.storage.storage_id} {if $aForms.storage.storage_name}{$aForms.storage.storage_name}{else}{$aForms.storage.service_phrase_name|convert}{/if}</td>
                    </tr>
                    <tr>
                        <td><b class="text-danger">{_p var='total_files'}</b></td>
                        <td>{$aForms.total_file|number_format}</td>
                    </tr>
                    <tr>
                        <td><b class="text-danger">{_p var='transferred_success'}</b></td>
                        <td>{$aForms.success_file|number_format}</td>
                    </tr>
                    <tr>
                        <td><b class="text-danger">{_p var='transferred_failed'}</b></td>
                        <td>{$aForms.fail_file|number_format}</td>
                    </tr>
                    <tr>
                        <td><b class="text-danger">{_p var='remove_files_from_local'}</b></td>
                        <td>{if !empty($aForms.remove_file)}{_p var='yes'}{else}{_p var='no'}{/if}</td>
                    </tr>
                    <tr>
                        <td><b class="text-danger">{_p var='update_database'}</b></td>
                        <td>{if !empty($aForms.update_database)}{_p var='yes'}{else}{_p var='no'}{/if}</td>
                    </tr>
                    {if $sStatus == 'completed' && !empty($aForms.update_database)}
                    <tr>
                        <td><b class="text-danger">{_p var='update_database_query_log'}</b></td>
                        <td><a target="_blank" href="{url link='admincp.setting.logger.view' service='local' channel='storage.log'}">{_p var='view_query_log'}</a></td>
                    </tr>
                    {/if}
                </table>
            </div>
        {/if}
        {if $sStatus == 'in_process' || (!empty($iTotalFile) && $iTotalFile > 0)}
            <div class="panel-body form-group">
                {if $sStatus == 'in_process'}
                <button class="btn btn-danger" type="submit" role="button" name="stop" value="1">{_p var='stop_transfer_files'}</button>
                {else}
                <button class="btn btn-primary" type="submit" role="button" name="transfer" value="1">{_p var='transfer_files'}</button>
                {/if}
                <a class="btn btn-default" role="button" href="{url link='admincp.setting.storage.manage'}">{_p var='cancel'}</a>
            </div>
        {/if}
</form>
{else}
<div class="alert alert-danger">
    {_p var='there_are_no_external_storage'}
</div>
{/if}