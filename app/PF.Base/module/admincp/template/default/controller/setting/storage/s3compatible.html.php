<?php
defined('PHPFOX') or exit('NO DICE!');
?>
{if $sError}
<div class="error_message">
    {$sError}
</div>
{/if}
{$sCreateJs}
<form method="post" onsubmit="{$sGetJsForm}" class="form" action="{url link='current'}" id="js_storage_dos_form">
    <!--    <p>{_p var='storage_s3_form_description'}</p>-->
    <input type="hidden" name="val[storage_id]" value="{value type='input' id='storage_id'}">
    <div id="client_details" class="panel panel-default">
        <div class="panel-body">
            <div class="form-group">
                <label class="required" for="storage_name">{_p var='storage_name'}</label>
                <input class="form-control" type="text" name="val[storage_name]" id="storage_name" value="{value type='input' id='storage_name'}" size="30"/>
                <p class="help-text"></p>
            </div>
            <div class="form-group">
                <label class="required" for="dos_key">{_p var='s3compatible_api_key'}</label>
                <input required class="form-control" type="text" id="dos_key" value="{value type='input' id='key'}" name="val[key]"/>
                <p class="help-block"></p>
            </div>
            <div class="form-group">
                <label class="required" for="dos_secret">{_p var='s3compatible_api_secret'}</label>
                <input required class="form-control" type="password" id="dos_secret" value="{value type='input' id='secret'}" name="val[secret]"/>
                <p class="help-block"></p>
            </div>
            <div class="form-group">
                <label class="required" for="region">{_p var='s3compatible_region'}</label>
                <input required class="form-control" type="text" id="region" value="{value type='input' id='region'}" name="val[region]"/>
                <p class="help-block"></p>
            </div>
            <div class="form-group">
                <label class="required" for="bucket">{_p var='s3compatible_bucket'}</label>
                <input required class="form-control" type="text" id="bucket" value="{value type='input' id='bucket'}" name="val[bucket]"/>
                <p class="help-block"></p>
            </div>
            <div class="form-group">
                <label class="required" for="base_url">{_p var='base_url'}</label>
                <input class="form-control" type="text" name="val[base_url]"
                       id="base_url" value="{value type='input' id='base_url'}"
                       placeholder="{_p var='base_url'}"/>
                <p class="help-block">
                    {_p var='storage_base_url_description'}
                </p>
            </div>
            <div class="form-group">
                <label for="endpoint">{_p var='endpoint_url'}</label>
                <input class="form-control" type="text" name="val[endpoint]"
                       id="endpoint" value="{value type='input' id='endpoint'}"
                       placeholder="{_p var='endpoint_url'}"/>
            </div>
            <div class="form-group">
                <label for="prefix">{_p var='storage_path_prefix'}</label>
                <input class="form-control" type="text" id="prefix" value="{value type='input' id='prefix'}" name="val[prefix]"/>
                <p class="help-block">
                    <span class="text-danger">{_p var='storage_path_prefix_danger_description'}</span>
                </p>
            </div>
            <div class="form-group">
                <label for="cdn_base_url">{_p var='cdn_base_url'}</label>
                <input class="form-control" type="text" name="val[cdn_base_url]"
                       id="cdn_base_url" value="{value type='input' id='cdn_base_url'}"
                       placeholder="{_p var='cdn_base_url'}"/>
                <p class="help-block">
                    {_p var='storage_cdn_base_url_description'}
                </p>
            </div>
            <div class="form-group">
                <label>{_p var='enable_cdn'}</label>
                <div class="item_is_active_holder">
                    <span class="js_item_active item_is_active">
                        <input type="radio" name="val[cdn_enabled]" value="1" {value type='radio' id='cdn_enabled' default='1' }/>
                    </span>
                    <span class="js_item_active item_is_not_active">
                        <input type="radio" name="val[cdn_enabled]" value="0" {value type='radio' id='cdn_enabled' default='0' selected='true' }/>
                    </span>
                </div>
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
