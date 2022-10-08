<?php
defined('PHPFOX') or exit('NO DICE!');
?>

<form method="post" class="form" action="{url link='current'}" id="js_form">
    <p>
        {_p var='message_queue_beanstalk_form_description'}
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
                    <label class="required" for="host">{_p var='host'}</label>
                    <input id="host" required class="form-control" type="text" name="val[host]" value="{value type='input' id='host'}" size="150" placeholder="{_p var='host'}"/>
                </div>
                <div class="form-group">
                    <label for="port">{_p var='port'}</label>
                    <input class="form-control" type="number" value="{value type='input' id='port'}" name="val[port]" min="0">
                </div>
                <div class="form-group">
                    {template file='admincp.block.message-queue-default'}
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
