<?php
defined('PHPFOX') or exit('NO DICE!');
?>

<form method="post" class="form" action="{url link='current'}" id="js_form">
	<p>
		{_p var='log_mongodb_form_description'}
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
                    <label class="required" for="connection_string">{_p var='connection_string'}</label>
                    <input required class="form-control" type="text" name="val[connection_string]"
                           id="connection_string" value="{value type='input' id='connection_string'}" size="30"
                           placeholder="mongodb://fox@phpfox123456@127.0.0.1:27017/phpfoxdb"/>
                    <p class="help-text">
                        format: mongodb://[username:password@]host1[:port1][,...hostN[:portN]][/[defaultauthdb][?options]]<br/>
                    </p>
                </div>
                <div class="form-group">
                    <label class="required" for="database">{_p var='database_name'}</label>
                    <input required class="form-control" type="text" name="val[database]"
                           id="database" value="{value type='input' id='database'}" size="30"
                           placeholder="{_p var='database_name'}"/>
                </div>
                <div class="form-group">
                    <label class="required" for="collection">{_p var='collection_name'}</label>
                    <input required class="form-control" type="text" name="val[collection]"
                           id="collection" value="{value type='input' id='collection'}" size="30"
                           placeholder="{_p var='collection_name'}"/>
                </div>
                <div class="form-group">
                    {template file="admincp.block.log-level-choices" }
                </div>
				<div class="form-group">
                    {template file="admincp.block.log-enable"}
				</div>

				<div class="form-group">
					<button class="btn btn-primary" type="submit" role="button">{_p var='save_changes'}</button>
					<a class="btn btn-info" role="button" href="{url link='admincp.setting.logger.manage'}">{_p var='cancel'}</a>
				</div>
			</div>
		</div>
		<input type="hidden" name="val[is_save]" value="1" />
</form>
