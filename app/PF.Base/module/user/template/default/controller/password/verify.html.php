<?php 
defined('PHPFOX') or exit('NO DICE!');
?>

{if Phpfox::getParam('user.shorter_password_reset_routine') && isset($sRequest)}
	<form id="js_request_password_form"  class="form" action="{url link='user.password.verify' id=$sRequest}" method="post">
		<div>
			<input type="hidden" name="val[request]" class="js_attachment" value="{$sRequest}" />
		</div>
		<div class="form-group">
			<label for="newpassword">{required}{_p var='new_password'}</label>
            <input class="form-control" id="newpassword" type="password" name="val[newpassword]" autocomplete="off" data-password-viewable="1"/>
            {if isset($sPasswordDescription)}<div class="help-block">{$sPasswordDescription}</div>{/if}
		</div>
		<div class="form-group">
			<label for="newpassword2">{required}{_p var='confirm_password'}</label>
            <input class="form-control" id="newpassword2" type="password" name="val[newpassword2]" autocomplete="off" data-password-viewable="1"/>
		</div>
		<div class="form-group">
			<input type="submit" class="btn btn-danger" value="{_p var='update'}" />
            <a href="{url link='login'}" class="btn btn-default">{_p var='cancel'}</a>
	</form>
{/if}