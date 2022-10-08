<?php
defined('PHPFOX') or exit('NO DICE!');
?>

<label>{_p var='is_active'}</label>
<div class="item_is_active_holder">
    <span class="js_item_active item_is_active">
        <input type="radio" name="val[is_active]" value="1" {value type='radio' id='is_active' default='1'}/>
    </span>
    <span class="js_item_active item_is_not_active">
        <input type="radio" name="val[is_active]" value="0" {value type='radio' id='is_active' default='0' selected='true'}/>
    </span>
</div>
<div class="help-block">{_p var='storage_will_be_forced_active_if_it_is_default'}</div>