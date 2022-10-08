<?php
defined('PHPFOX') or exit('NO DICE!');
?>

{if isset($aCustomField.fields)}
	{foreach from=$aCustomField.fields item=aField}
		<div class="form-group {if $aField.var_type != 'date'} js_core_init_selectize_form_group {/if} {if isset($sFormGroupClass)}{$sFormGroupClass}{/if}">
            <label>{$aField.phrase_var_name_text}</label>
            {if $aField.var_type == 'textarea' || $aField.var_type == 'text'}
                <input type="text" class="form-control js_custom_search" name="custom[{$aField.field_id}]" value="{value id=''$aField.field_id'' type='input'}" size="25" />
            {elseif $aField.var_type == 'select'}
                <!-- custom input type select -->
                <select name="custom[{$aField.field_id}]" class="form-control js_custom_search">
                    <option value="">{_p var='any'}</option>
                    {foreach from=$aField.options item=aOption}
                        <option value="{$aOption.option_id}"{value parent=''$aField.field_id'' id=''$aOption.option_id'' type='select' default=''$aOption.option_id''}>{$aOption.phrase_var_name_text}</option>
                    {/foreach}
                </select>
            {elseif $aField.var_type == 'multiselect'}
                <!-- custom input type multi select -->
                <select name="custom[{$aField.field_id}][]" multiple class="form-control js_custom_search" >
                    <option value="0">{_p var='any'}</option>
                        {foreach from=$aField.options item=aOption}
                            <option value="{$aOption.option_id}"{value parent=''$aField.field_id'' id=''$aOption.option_id'' type='multiselect' default=''$aOption.option_id''}>{$aOption.phrase_var_name_text}</option>
                        {/foreach}
                </select>
            {elseif $aField.var_type == 'date'}
                {$aField.build_html}
            {elseif $aField.var_type == 'radio'}
                {foreach from=$aField.options item=aOption}
                    <div class="custom-radio-wrapper core-radio-custom">
                        <label>
                            <input type="radio" name="custom[{$aField.field_id}]" value="{$aOption.option_id}"{value id=''$aOption.option_id'' type='radio' default=''$aOption.option_id''} class="js_custom_search">
                            <span class="custom-radio custom-icon"></span>
                            {$aOption.phrase_var_name_text}
                        </label>
                    </div>
                {/foreach}
            {elseif $aField.var_type == 'checkbox'}
                {foreach from=$aField.options item=aOption}
                    <div class="custom-checkbox-wrapper core-checkbox-custom">
                        <label>
                            <input type="checkbox" name="custom[{$aField.field_id}][{$aOption.option_id}]" value="{$aOption.option_id}"{value id=''$aOption.option_id'' parent=''$aField.field_id'' type='checkbox' default=''$aOption.option_id''} class="js_custom_search v_middle">
                            <span class="custom-checkbox custom-icon"></span>
                            {$aOption.phrase_var_name_text}
                        </label>
                    </div>
                {/foreach}
            {/if}
		</div>
	{/foreach}
{/if}

