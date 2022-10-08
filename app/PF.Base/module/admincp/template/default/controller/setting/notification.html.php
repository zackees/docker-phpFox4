<?php
/**
 * [PHPFOX_HEADER]
 *
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		phpFox LLC
 * @package 		Phpfox
 * @version 		$Id: notification.html.php 2020-12-10 13:30:08Z phpFox LLC $
 */

defined('PHPFOX') or exit('NO DICE!');

?>
<form method="POST">
    <input type="hidden" name="val[notification_type]" value="{$sType}">
    <p>{$sDescription}</p>
    <hr>
    <div class="privacy-block-content">
        {foreach from=$aNotifications item=aModules}
            {foreach from=$aModules key=sNotification item=aNotification}
                <div class="item-outer">
                    {template file='user.block.privacy-notification'}
                    <hr>
                </div>
            {/foreach}
        {/foreach}
    </div>
    <div class="form-group-button">
        <input type="submit" value="{_p var='save_changes'}" class="btn btn-primary" />
    </div>
</form>