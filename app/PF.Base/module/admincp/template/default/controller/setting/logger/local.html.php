<?php
defined('PHPFOX') or exit('NO DICE!');
?>

<form method="post" class="form" action="{url link='current'}" id="js_form">
    <p>
        {_p var='log_local_form_description'}
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
                    <label for="directory">{_p var='log_directory'}</label>
                    <input required class="form-control" type="text"
                           readonly
                           id="log_directory" value="{$sLogDirectory}" size="30"/>
                    <p class="help-text">
                    </p>
                </div>
                <div class="form-group">
                    {template file="admincp.block.log-level-choices" }
                </div>
                <div class="form-group">
                    {template file="admincp.block.log-enable"}
                </div>

                <div class="form-group">
                    <button class="btn btn-primary" type="submit" role="button">{_p var='save_changes'}</button>
                    <a class="btn btn-info" role="button" href="{url link='admincp.setting.logger.manage'}">{_p
                        var='cancel'}</a>
                </div>
            </div>
        </div>
        <input type="hidden" name="val[is_save]" value="1"/>
</form>
