<?php 
defined('PHPFOX') or exit('NO DICE!');
?>

<form method="post" class="form" action="{url link='current'}" onsubmit="$(this).ajaxCall('user.updateStatus'); return false;" id="js_user_status_form">
	<div id="header_top_notify" class="core-user-status-block">
		<ul>		
            {if Phpfox::isModule('notification') && Phpfox::getParam('notification.notify_on_new_request')}
                {module name='notification.link'}
            {else}
                <li><a href="{url link='user.photo'}">{$sUserGlobalImage}</a></li>
            {/if}
		</ul>
		<div class="clear"></div>
	</div>
</form>