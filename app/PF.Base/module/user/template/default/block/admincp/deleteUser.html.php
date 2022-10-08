<?php 
defined('PHPFOX') or exit('NO DICE!');
?>
{if Phpfox::getService('user')->isAdminUser('' . $iUserIdDelete . '')}
    <p>{_p var='you_are_unable_to_delete_a_site_administrator'}</p>
{else}
    <p>
        {_p var='are_you_completely_sure_you_want_to_delete_this_user'}
    </p>
{/if}

{if !Phpfox::getService('user')->isAdminUser('' . $iUserIdDelete . '')}
    <div class="js_box_buttonpane">
        <button type="button" class="btn btn-default" onclick="tb_remove();">{_p var='no_cancel'}</button>
        <button type="button" class="btn btn-danger" data-id="{$aUser.user_id}" onclick="return $Core.UserAdmincp.deleteUser(this);">{_p var='yes_delete'}</button>
    </div>
{/if}
