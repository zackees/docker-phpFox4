<?php
defined('PHPFOX') or exit('NO DICE!');
?>

{if !empty($aForms) && $aForms.storage_id == 0 && $aForms.is_default == 1}
    <input type="hidden" name="val[is_default]" value="1"/>
    <div class="help-block">{_p var='local_storage_is_setting_as_default_so_you_cannot_disable_this_option_unless_you_set_another_storage_as_default'}</div>
{else}
    <label>{_p var='is_default'}</label>
    <div class="item_is_active_holder">
        <span class="js_item_active item_is_active">
            <input type="radio" name="val[is_default]" value="1" {value type='radio' id='is_default' default='1' }/>
        </span>
        <span class="js_item_active item_is_not_active">
            <input type="radio" name="val[is_default]" value="0" {value type='radio' id='is_default' default='0' selected='true'}/>
        </span>
    </div>
{/if}
