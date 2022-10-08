<?php
defined('PHPFOX') or exit('NO DICE!');
?>

<form method="post" class="form" action="{url link='current'}" id="js_form">
    <p>
        {_p var='session_memcached_form_description'}
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
                    <label class="required" for="session_save_path">{_p var='session_save_path'}</label>
                    <input required class="form-control" type="text" name="val[save_path]"
                           id="session_save_path" value="{value type='input' id='save_path'}" size="30"
                           placeholder="{_p var='session_save_path'}"/>
                    <p class="help-text">
                        format: host1:port;host2:port;<br/>
                    </p>
                </div>
                <div class="form-group">
                    {template file='admincp.block.session-default'}
                </div>
                <div class="form-group">
                    <button class="btn btn-primary" type="submit" role="button">{_p var='save_changes'}</button>
                    <a class="btn btn-info" role="button" href="{url link='admincp.setting.session.manage'}">{_p
                        var='cancel'}</a>
                </div>
            </div>
        </div>
        <input type="hidden" name="val[is_save]" value="1"/>
</form>
