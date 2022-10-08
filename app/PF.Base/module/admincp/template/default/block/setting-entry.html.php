<?php
defined('PHPFOX') or exit('NO DICE!');
?>

<div id="{$aSetting.var_name}" class="form-group {if isset($aSetting.is_danger) && $aSetting.is_danger} has-warning {/if} {if !empty($aSetting.error)}has-error{/if} lines{if !empty($aSetting.group_class)} {$aSetting.group_class}{/if}{if !empty($aSetting.option_class)} is_option_class{/if}{if !empty($sGroupClass) && !empty($aSetting.group_class) && $sGroupClass != $aSetting.group_class} hidden{/if} clearfix {if !empty($aSetting.dependency.parent_setting) && isset($aSetting.dependency.parent_option) && isset($aSetting.dependency.current_parent_option) && $aSetting.dependency.parent_option != $aSetting.dependency.current_parent_option}hidden{/if}"{if !empty($aSetting.option_class)} data-option-class="{$aSetting.option_class}"{/if} {if !empty($aSetting.dependency.parent_setting)}data-parent-setting="{$aSetting.dependency.parent_setting}"{/if} {if isset($aSetting.dependency.parent_option)}data-parent-setting-option="{$aSetting.dependency.parent_option}"{/if}>
    {if PHPFOX_DEBUG && $aSetting.type_id != 'readonly'}
        <div class="pull-right">
            <input readonly type="text" name="val[order][{$aSetting.var_name}]" value="{$aSetting.ordering}" class="input_xs_readonly" onclick="this.select();" size="2" />
            <input readonly type="text" name="param{$aSetting.var_name}" value="{$aSetting.module_id}.{$aSetting.var_name}" class="input_xs_readonly" onclick="this.select();" />
        </div>
    {/if}
    <label class="setting-title">{_p var=$aSetting.setting_title}</label>
    {if isset($aSetting.is_danger) && $aSetting.is_danger}
        <div class="alert alert-warning alert-labeled">
            <div class="alert-labeled-row">
                <p class="alert-body alert-body-right alert-labelled-cell">
                    <strong>{_p var="Warning"}</strong>
                    {_p var="This is an important setting. Select a wrong option here can break the site or affect some features. If you are at all unsure about which option to configure, use the default value or contact us for support"}.
                </p>
            </div>
        </div>
    {/if}
    <div class="clear"></div>
    {if $aSetting.is_file_config == false}
        {if $aSetting.type_id == 'readonly'}
            <pre>{$aSetting.value_actual|clean}</pre>
        {elseif $aSetting.type_id == 'multi_text'}
            {foreach from=$aSetting.values key=mKey item=sDropValue}
            <div class="p_4">
                <div class="input-group">
                    <span class="input-group-addon">{$mKey}</span>
                    <input class="form-control change_warning" type="text" name="val[value][{$aSetting.var_name}][{$mKey}]" value="{$sDropValue|clean}" {if !empty($aSetting.read_only)}readonly{/if} />
                </div>
            </div>
            {/foreach}
        {elseif $aSetting.type_id == 'currency'}
            {module name='core.currency' currency_field_name='val[value]['{$aSetting.var_name']' value_actual=$aSetting.values }
        {elseif $aSetting.type_id == 'large_string' || $aSetting.type_id=='big_string'}
            <textarea cols="60" rows="8" class="form-control change_warning" name="val[value][{$aSetting.var_name}]" {if !empty($aSetting.read_only)}readonly{/if} >{$aSetting.value_actual|htmlspecialchars}</textarea>
        {elseif ($aSetting.type_id == 'string') || $aSetting.type_id == 'input:text'}
            <div>
                <input type="text" class="form-control change_warning" name="val[value][{$aSetting.var_name}]" value="{$aSetting.value_actual|clean}" size="40" {if !empty($aSetting.read_only)}readonly{/if} />
            </div>
        {elseif ($aSetting.type_id == 'password')}
            <div>
                <input class="form-control change_warning" type="password" name="val[value][{$aSetting.var_name}]" value="{$aSetting.value_actual}" size="40" autocomplete="new-password" {if !empty($aSetting.read_only)}readonly{/if} />
            </div>
        {elseif ($aSetting.type_id == 'drop')}
            <div>
                <input type="hidden" name="val[value][{$aSetting.var_name}][real]" value="{$aSetting.value_actual}" size="40" />
            </div>
            <select name="val[value][{$aSetting.var_name}][value]" class="form-control change_warning {if !empty($aSetting.read_only)}setting-readonly{/if}">
                {foreach from=$aSetting.values.values key=mKey item=sDropValue}
                    <option value="{$sDropValue}" {if $aSetting.values.default == $sDropValue}selected="selected"{/if}>
                    {if !empty($sDropValue) && !stripos( $sDropValue, ' ') && !stripos($sDropValue, '.')}
                        {php}{$this->_aVars['sDropValue'] = strtolower($this->_aVars['sDropValue']);}{/php}
                        {_p var=$sDropValue}
                    {else}
                        {_p var=$sDropValue}
                    {/if}
                    </option>
                {/foreach}
            </select>
        {elseif ($aSetting.type_id == 'multi_checkbox')}
            <input type="hidden" name="val[value][{$aSetting.var_name}][]" value="core_multi_checkbox_off">
            {foreach from=$aSetting.values key=mKey item=sDropValue}
            <div class="custom-checkbox-wrapper">
                <label>
                    <input type="checkbox" name="val[value][{$aSetting.var_name}][]" value="{$mKey}" {if is_array($aSetting.value_actual) && in_array($mKey, $aSetting.value_actual)}checked{/if} {if !empty($aSetting.read_only)}readonly{/if} />
                    <span class="custom-checkbox"></span>
                    {_p var=$sDropValue}
                </label>
            </div>
            {/foreach}
        {elseif ($aSetting.type_id == 'drop_with_key' || $aSetting.type_id== 'select')}
            <select name="val[value][{$aSetting.var_name}]" class="form-control __data_option_{$aSetting.var_name} change_warning {if !empty($aSetting.read_only)}setting-readonly{/if}" data-rel="__data_option_{$aSetting.var_name}">
                {foreach from=$aSetting.values key=mKey item=sDropValue}
                <option value="{$mKey}"{if $aSetting.value_actual == $mKey} selected="selected"{/if}>{_p var=$sDropValue}</option>
                {/foreach}
            </select>
        {elseif $aSetting.type_id == 'radio'}
            {foreach from=$aSetting.values key=mKey item=sDropValue}
            <div class="custom-radio-wrapper">
                <label>
                    <input class="change_warning" name="val[value][{$aSetting.var_name}]" type="radio" {if $aSetting.value_actual == $mKey} checked{/if} value="{$mKey}" {if !empty($aSetting.read_only)}readonly{/if}/>
                    <span class="custom-radio"></span>
                    {_p var=$sDropValue}
                </label>
            </div>
            {/foreach}
        {elseif ($aSetting.type_id == 'integer')}
            <input class="form-control change_warning" type="text" name="val[value][{$aSetting.var_name}]" value="{$aSetting.value_actual}" size="40" onclick="this.select();" {if !empty($aSetting.read_only)}readonly{/if}/>
        {elseif ($aSetting.type_id == 'boolean') || $aSetting.type_id == 'input:radio'}
            <div class="item_is_active_holder {if !empty($aSetting.read_only)}setting-readonly{/if}">
                <span class="js_item_active item_is_active hide">
                    <input class="change_warning" type="radio" value="1" name="val[value][{$aSetting.var_name}]"{if $aSetting.value_actual == 1} checked="checked"{/if}>
                </span>
                <span class="js_item_active item_is_not_active hide">
                    <input class="change_warning" type="radio" value="0" name="val[value][{$aSetting.var_name}]"{if $aSetting.value_actual != 1} checked="checked"{/if}>
                </span>
            </div>
        {elseif ($aSetting.type_id == 'array')}
        <div class="js_array_holder">
            {if is_array($aSetting.values)}
                {foreach from=$aSetting.values key=iKey item=sValue}
                <div class="p_4" class="js_array{$iKey}">
                    <div class="input-group">
                        <input type="text" name="val[value][{$aSetting.var_name}][]" value="{$sValue}" size="120" class="form-control change_warning" {if !empty($aSetting.read_only)}readonly{/if}/>
                        <span class="input-group-btn">
                            <a class="btn btn-danger" data-cmd="admincp.site_setting_remove_input" data-rel="setting={$aSetting.var_name}&value={$sValue}" title="{_p var='remove'}"><i class="fa fa-remove"></i> </a>
                        </span>
                    </div>
                </div>
                {/foreach}
            {/if}
            <div class="js_array_data"></div>
            <div class="js_array_count" style="display:none;">{if isset($iKey)}{$iKey+1}{/if}</div>
            <br />
            <div class="p_4">
                <div class="input-group">
                    <input type="text" name="" placeholder="{_p var='add_a_new_value' phpfox_squote=true}" size="30" class="js_add_to_array form-control" />
                    <span class="input-group-btn">
                        <input type="button" value="{_p var='add'}" class="btn btn-primary" data-cmd="admincp.site_setting_add_input" data-rel="val[value][{$aSetting.var_name}][]" />
                    </span>
                </div>
            </div>
        </div>
        {/if}
    {else}
        {if ($aSetting.type_id == 'drop_with_key' || $aSetting.type_id== 'select')}
            <input type="hidden" class="__data_option_{$aSetting.var_name}" value="{$aSetting.value_actual}">
        {/if}
        <div class="alert alert-info alert-labeled">
            <div class="alert-labeled-row">
                <p class="alert-body alert-body-right alert-labelled-cell">
                    <strong>{_p var="info"}</strong>
                    {_p var='this_configuration_is_set_in_a_configuration_file'}
                </p>
            </div>
        </div>
    {/if}
    <p class="help-block">
        {_p var=$aSetting.setting_info}
    </p>
    {if !empty($aSetting.setting_warning_info)}
        <div class="alert alert-danger alert-labeled">
            <div class="alert-labeled-row">
                <p class="alert-body alert-body-right alert-labelled-cell">
                    {_p var=$aSetting.setting_warning_info}
                </p>
            </div>
        </div>
    {/if}
</div>
