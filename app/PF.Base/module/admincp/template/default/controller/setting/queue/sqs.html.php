<?php
defined('PHPFOX') or exit('NO DICE!');
?>

<form method="post" class="form" action="{url link='current'}" id="js_form">
    <p>
        {_p var='message_queue_sqs_form_description'}
    </p>
    {if $sError}
    <div class="alert alert-danger">
        {$sError}
    </div>
    {/if}
    <div id="client_details" class="panel panel-default">
        <div class="panel-body">
            <div>
                <div class="form-group">
                    <label class="required" for="aws_key">{_p var='aws_key'}</label>
                    <input required class="form-control" type="text"
                           id="aws_key" value="{value type='input' id='key'}" name="val[key]"/>
                    <p class="help-text">
                    </p>
                </div>
                <div class="form-group">
                    <label class="required" for="aws_secret">{_p var='aws_secret'}</label>
                    <input required class="form-control" type="text"
                           id="aws_secret" value="{value type='input' id='secret'}" name="val[secret]"/>
                    <p class="help-text">
                    </p>
                </div>
                <div class="form-group">
                    <label class="required" for="region">{_p var='region'}</label>
                    <input required class="form-control" type="text"
                           id="region" value="{value type='input' id='region'}" name="val[region]"/>
                    <p class="help-text">
                    </p>
                </div>
                <div class="form-group">
                    {template file="admincp.block.message-queue-default"}
                </div>
                <div class="form-group">
                    <button class="btn btn-primary" type="submit" role="button">{_p var='save_changes'}</button>
                    <a class="btn btn-info" role="button" href="{url link='admincp.setting.queue.manage'}">{_p
                        var='cancel'}</a>
                </div>
            </div>
        </div>
        <input type="hidden" name="val[is_save]" value="1"/>
</form>
