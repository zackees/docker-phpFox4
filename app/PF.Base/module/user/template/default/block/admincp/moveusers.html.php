<?php
defined('PHPFOX') or exit('NO DICE!');
?>

<form class="form" method="post" action="{url link='admincp.user.browse'}" id="js-user_move_to_group">
    <div class="form-group js_core_init_selectize_form_group">
        <label for="user_group_id">{required}{_p var='user_group'}:</label>
        {foreach from=$aUserIds item=iUserId}
            <input type="hidden" name="id[]" value="{$iUserId}"/>
        {/foreach}
        <select class="form-control" name="user_group_id" id="user_group_id" required>
            {foreach from=$aGroups item=aGroup}
                <option value="{$aGroup.user_group_id}" {if $aGroup.user_group_id == 2}selected{/if} >{$aGroup.title|convert}</option>
            {/foreach}
        </select>
        <input type="hidden" name="move-to-group" value="1">
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-primary sJsConfirm">{_p var='submit'}</button>
    </div>
</form>