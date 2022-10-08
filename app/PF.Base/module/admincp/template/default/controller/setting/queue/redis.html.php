<?php
defined('PHPFOX') or exit('NO DICE!');
?>

{if !$passDependencies}
<div class="alert alert-danger">
    Redis Queue required php version >= 7 and redis extension.
</div>
{else}
<form method="post" class="form" action="{url link='current'}" id="js_form">
    <p>
        {_p var='message_queue_redis_form_description'}
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
                    <label class="required" for="redis_host">{_p var='host'}</label>
                    <input required class="form-control" type="text" name="val[host]"
                           id="redis_host" value="{value type='input' id='host'}" size="30"
                           placeholder="{_p var='host'}"/>
                </div>
                <div class="form-group">
                    <label for="redis_port">{_p var='port'}</label>
                    <input id="redis_host" type="number" class="form-control" value="{value type='input' id='port'}" placeholder="{_p var='port'}" min="0" name="val[port]">
                </div>
                <div class="form-group">
                    <label for="redis_database">{_p var='redis_database_number'}</label>
                    <input id="redis_database" type="number" class="form-control" min="0" max="15" placeholder="{_p var='redis_database_number'}" value="{value type='input' id='database'}" name="val[database]">
                </div>
                <div class="form-group">
                    <label for="redis_password">{_p var='password'}</label>
                    <input id="redis_password" type="text" class="form-control" value="{value type='input' id='password'}" placeholder="{_p var='password'}" name="val[password]">
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
{/if}