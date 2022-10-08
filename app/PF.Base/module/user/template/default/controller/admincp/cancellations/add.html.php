<?php 
defined('PHPFOX') or exit('NO DICE!');
?>
<form method="post" action="{if isset($aForms.delete_id)}{url link='admincp.user.cancellations.add' id=$aForms.delete_id}{else}{url link='admincp.user.cancellations.add'}{/if}">
<div class="panel panel-default">
    <div class="panel-heading">
        <div class="panel-title">{if $bIsEdit}{_p var='edit_new_option'}{else}{_p var='add_new_option'}{/if}</div>
    </div>
    <div class="panel-body">
        {if isset($aForms.delete_id)}
        <input type="hidden" name="val[iDeleteId]" value="{$aForms.delete_id}">
        {/if}
        <div class="form-group">
            <label for="cancellation_reason">{required}{_p var='cancellation_reason'}</label>
            {if isset($aForms.phrase_var)}
            {module name='language.admincp.form' type='text' id='phrase_var' var_name=$aForms.phrase_var close_warning=1}
            {else}
            {module name='language.admincp.form' type='text' id='phrase_var' close_warning=1}
            {/if}
        </div>
        <div class="form-group">
            <label for="is_active">{required}{_p var='is_active'}</label>
            <div class="item_is_active_holder">
                <span class="js_item_active item_is_active"><input type="radio" name="val[is_active]" class="close_warning" value="1" {value type='radio' id='is_active' default='1' selected='true'}/></span>
                <span class="js_item_active item_is_not_active"><input type="radio" name="val[is_active]" class="close_warning" value="0" {value type='radio' id='is_active' default='0'}/></span>
            </div>
        </div>
    </div>
    <div class="panel-footer">
        <button type="submit" class="btn btn-primary">{_p var='submit'}</button>
        <a class="btn btn-link" href="{url link='admincp.user.cancellations.manage'}">{_p var='cancel'}</a>
    </div>
</div>
</form>