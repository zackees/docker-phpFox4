<?php
defined('PHPFOX') or exit('NO DICE!');
?>
{if $sError}
    <div class="error_message">
        {$sError}
    </div>
{/if}
<form method="post" class="form" action="{url link='current'}" id="js_form">
<!--    <p>{_p var='storage_local_form_description'}</p>-->
    <div id="client_details" class="panel panel-default">
        <div class="panel-body">
            <div class="form-group">
                <label class="required" for="storage_name">{_p var='storage_name'}</label>
                <input readonly required class="form-control" type="text" name="val[storage_name]" id="storage_name" value="{value type='input' id='storage_name'}" size="30"/>
                <p class="help-text"></p>
            </div>
            <div class="form-group">
                <label for="base_path">{_p var='storage_base_path'}</label>
                <input required class="form-control" type="text" readonly id="base_path" value="{$base_path}" size="30"/>
                <p class="help-text">
                </p>
            </div>
            <div class="form-group">
                <label for="base_url">{_p var='storage_base_url'}</label>
                <input required class="form-control" type="text" readonly id="base_url" value="{$base_url}" size="30"/>
                <p class="help-text">
                </p>
            </div>
            <div class="form-group">
                {template file="admincp.block.storage-default"}
            </div>
            <div class="form-group">
                <button class="btn btn-primary" type="submit" role="button">{_p var='save_changes'}</button>
                <a class="btn btn-default" role="button" href="{url link='admincp.setting.storage.manage'}">{_p
                    var='cancel'}</a>
            </div>
        </div>
</form>
