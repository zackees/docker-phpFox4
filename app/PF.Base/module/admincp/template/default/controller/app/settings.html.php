<?php
defined('PHPFOX') or exit('NO DICE!');
/**
 * @copyright [PHPFOX_COPYRIGHT]
 * @author phpFox LLC
 */
?>
<script>
    var set_active = false, setting_group_class = '';
    {if ($group_class)}
    setting_group_class = '{$group_class}';
    {/if}
    {literal}
    $Ready(function() {
        if (set_active) {
            return;
        }
        set_active = true;
        $('._is_app_settings').show();
        $('.js-acp-header-section a[href*="admincp/setting/edit"]').addClass('active');
        if (setting_group_class) {
            $('.' + setting_group_class + ':not(.is_option_class)').show();
            $('.' + setting_group_class + '.is_option_class').each(function() {
                var option_class = $(this).data('option-class').split('='),
                    s_key = option_class[0],
                    s_value = option_class[1],
                    i = $(this),
                    t = $('.__data_option_' + s_key + '');
                if (t.length) {
                    if (t.val() == s_value) {
                        i.show();
                    } else {
                        i.hide();
                    }
                }
            });
        }
    });
    {/literal}
</script>
{if !PHPFOX_IS_AJAX_PAGE}
<div id="app-custom-holder" style="display:none; min-height:400px;"></div>
<div id="app-content-holder">
{/if}
    {if isset($settings) && $settings}
    <section class="app_grouping _is_app_settings">
        <form class="on_change_submit{if !isset($bHideAutoSwitcherButton) || !$bHideAutoSwitcherButton} build {/if}" method="post" action="{url link='current'}">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="panel-title">{_p var='manage_settings'}</div>
                </div>
                <div class="panel-body">
                    {foreach from=$settings item=setting key=var}
                    <div class="form-group lines"
                            {if (isset($setting.group_class) && $setting.group_class) || (isset($setting.option_class) && $setting.option_class)}
                            class="form-group lines {$setting.group_class} {if (isset($setting.option_class) && $setting.option_class)} is_option_class{/if}" {if (isset($setting.option_class) && $setting.option_class)}
                            data-option-class="{$setting.option_class}"
                            {else}
                            class="form-group lines"
                            {/if}
                    {if $group_class != $setting.group_class}style="display:none;"{/if}
                    {/if}
                    >
                    <label>{$setting.info}</label>
                    <div class="">
                        {if $setting.type == 'input:text'}
                        <input type="text" name="setting[{$var}]" value="{$setting.value|clean}" class="form-control">
                        {elseif $setting.type == 'currency'}
                        <div class="currency_setting">
                            {foreach from=$setting.value key=sName item=aValue}
                                <div class="currency input-group">
                                    <span class="input-group-addon" title="{_p var=$aValue.name}">{$aValue.symbol}</span>
                                    <input type="text" name="setting[{$var}][{$sName}]" value="{$aValue.value}" size="10" class="form-control" />
                                </div>
                            {/foreach}
                        </div>
                        {elseif $setting.type == 'password'}
                            <input type="password" name="setting[{$var}]" value="{$setting.value|clean}" class="form-control">
                        {elseif $setting.type == 'input:radio'}
                            <div class="item_is_active_holder">
                                <span class="js_item_active item_is_active">
                                    <input type="radio"{if $setting.value == 1} checked="checked"{/if} name="setting[{$var}]" value="1">
                                </span>
                                <span class="js_item_active item_is_not_active">
                                    <input type="radio"{if $setting.value != 1} checked="checked"{/if} name="setting[{$var}]" value="0">
                                </span>
                            </div>
                        {elseif $setting.type == 'select'}
                            <select name="setting[{$var}]" class="form-control __data_option_{$var}" data-rel="__data_option_{$var}">
                                {foreach item=option key=name from=$setting.options}
                                    <option value="{$name}"{if ($name == $setting.value)} selected="selected"{/if}>{_p var=$option}</option>
                                {/foreach}
                            </select>
                        {/if}
                        {if !empty($setting.description)}
                        <div class="help-block">{ $setting.description }</div>
                        {/if}
                    </div>
                </div>
                {/foreach}

                </div>
                <div class="panel-footer">
                    <input type="submit" class="btn btn-danger" value="{_p var='Save Changes'}">
                </div>
            </div>
</form>
</section>

{$extra}
{/if}
{if !PHPFOX_IS_AJAX_PAGE}
</div>
<div id="app-details">
    {if (!$ActiveApp.is_phpfox_default)}
        <ul>
            {if $ActiveApp.allow_disable}
                <li><a {if $App.is_module}class="sJsConfirm" data-message="{_p var='are_you_sure' phpfox_squote=true}"{/if} href="{$uninstallUrl}">{_p var='uninstall'}</a></li>
            {/if}
            {if $export_path && Phpfox::isTechie()}
                <li><a href="{$export_path}">{_p var="Export"}</a></li>
            {/if}
        </ul>
    {/if}
    <div class="app-copyright">
        {if $ActiveApp.vendor}
            ©{$ActiveApp.vendor}
        {/if}
        {if $ActiveApp.credits}
            <div class="app-credits">
                <div>{_p var="Credits"}</div>
                {foreach from=$ActiveApp.credits item=url key=name}
                <ul>
                    <li><a href="{$url}">{$name|clean}</a></li>
                </ul>
                {/foreach}
            </div>
        {/if}
    </div>
</div>
{/if}