<?php
defined('PHPFOX') or exit('NO DICE!');
?>
{if $sError}
<div class="error_message">
    {$sError}
</div>
{/if}
{$sCreateJs}
<form method="post" onsubmit="{$sGetJsForm}" class="form" action="{url link='current'}" id="js_storage_sftp_form">
<!--    <p>{_p var='storage_sftp_form_description'}</p>-->
    <input type="hidden" name="val[storage_id]" value="{value type='input' id='storage_id'}">
    <div id="client_details" class="panel panel-default">
        <div class="panel-body">
            <div class="form-group">
                <label class="required" for="storage_name">{_p var='storage_name'}</label>
                <input class="form-control" type="text" name="val[storage_name]" id="storage_name" value="{value type='input' id='storage_name'}" size="30"/>
                <p class="help-text"></p>
            </div>
            <div class="form-group">
                <label class="required" for="host">{_p var='host_name'}</label>
                <input class="form-control" type="text" name="val[host]" id="host" value="{value type='input' id='host'}" placeholder="127.0.0.1"/>
                <p class="help-text"></p>
            </div>
            <div class="form-group">
                <label class="required" for="port">{_p var='port'}</label>
                <input class="form-control" type="text" id="port" name="val[port]" value="{value type='input' id='port'}" size="30"/>
            </div>
            <div class="form-group">
                <label for="username">{_p var='username'}</label>
                <input class="form-control" type="text" id="username" value="{value type='input' id='username'}" name="val[username]" size="30"/>
            </div>
            <div class="form-group">
                <label for="password">{_p var='password'}</label>
                <input class="form-control" type="password" name="val[password]" id="password" value="{value type='input' id='password'}" size="30"/>
            </div>
            <div class="form-group">
                <label class="required" for="base_path">{_p var='storage_base_path'}</label>
                <input class="form-control" type="text" name="val[base_path]" id="base_path" value="{value type='input' id='base_path'}" size="30"/>
                <p class="help-block"></p>
            </div>
            <div class="form-group">
                <label class="required" for="base_url">{_p var='storage_base_url'}</label>
                <input class="form-control" type="text" name="val[base_url]" id="base_url" value="{value type='input' id='base_url'}" size="30"/>
                <p class="help-block"></p>
            </div>
            <div class="form-group">
                {template file="admincp.block.storage-default"}
            </div>
            <div class="form-group">
                {template file="admincp.block.storage-enable"}
            </div>
            <div class="form-group">
                <button class="btn btn-primary" type="submit" role="button">{_p var='save_changes'}</button>
                <a class="btn btn-default" role="button" href="{url link='admincp.setting.storage.manage'}">{_p var='cancel'}</a>
            </div>
        </div>
</form>
