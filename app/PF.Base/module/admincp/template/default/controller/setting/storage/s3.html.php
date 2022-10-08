<?php
defined('PHPFOX') or exit('NO DICE!');
?>
{if $sError}
    <div class="error_message">
        {$sError}
    </div>
{/if}
{$sCreateJs}
<form method="post" onsubmit="{$sGetJsForm}" class="form" action="{url link='current'}" id="js_storage_s3_form">
    <input type="hidden" name="val[storage_id]" value="{value type='input' id='storage_id'}">
    <div id="client_details" class="panel panel-default">
        <div class="panel-body">
            <div class="form-group">
                <label class="required" for="storage_name">{_p var='storage_name'}</label>
                <input class="form-control" type="text" name="val[storage_name]" id="storage_name" value="{value type='input' id='storage_name'}" size="30"/>
                <p class="help-text"></p>
            </div>
            <div class="form-group">
                <label class="required" for="aws_key">{_p var='aws_key'}</label>
                <input class="form-control" type="text"
                       id="aws_key" value="{value type='input' id='key'}" name="val[key]"/>
                <p class="help-text">
                </p>
            </div>
            <div class="form-group">
                <label class="required" for="aws_secret">{_p var='aws_secret'}</label>
                <input class="form-control" type="password"
                       id="aws_secret" value="{value type='input' id='secret'}" name="val[secret]"/>
                <p class="help-text">
                </p>
            </div>
            <div class="form-group">
                <label class="required" for="bucket">{_p var='bucket'}</label>
                <input class="form-control" type="text"
                       id="bucket" value="{value type='input' id='bucket'}" name="val[bucket]"/>
                <p class="help-text"></p>
            </div>
            <div class="form-group">
                <label class="required" for="region">{_p var='region'}</label>
                <input required class="form-control" type="text"
                       id="region" value="{value type='input' id='region'}" name="val[region]"/>
                <p class="help-text">
                </p>
            </div>
            <div class="form-group">
                <label for="prefix">{_p var='storage_path_prefix'}</label>
                <input class="form-control" type="text" id="prefix" value="{value type='input' id='prefix'}" name="val[prefix]"/>
                <p class="help-block">
                    <span class="text-danger">{_p var='storage_path_prefix_danger_description'}</span></p>
            </div>
            <div class="form-group">
                <label class="required">{_p var='aws_cloudfront_enable'}</label>
                <div class="item_is_active_holder">
                    <span class="js_item_active item_is_active">
                        <input type="radio" name="val[cloudfront_enabled]" value="1" {value type='radio' id='cloudfront_enabled' default='1' }/>
                    </span>
                    <span class="js_item_active item_is_not_active">
                        <input type="radio" name="val[cloudfront_enabled]" value="0" {value type='radio' id='cloudfront_enabled' default='0' selected='true'}/>
                    </span>
                </div>
            </div>
            <div class="form-group">
                <label class="" for="cloudfront_url">{_p var='aws_cloudfront_url'}</label>
                <input  class="form-control" type="text" id="cloudfront_url" value="{value type='input' id='cloudfront_url'}" name="val[cloudfront_url]"/>
                <p class="help-text"></p>
            </div>

            {template file='admincp.block.storage-metadata'}

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
