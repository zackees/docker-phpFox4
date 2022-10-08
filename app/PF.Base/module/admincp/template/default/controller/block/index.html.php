<?php 
defined('PHPFOX') or exit('NO DICE!');
?>

<form class="form" method="get">
    <div class="panel panel-default">
        <div class="panel-body">
            <div class="form-group js_core_init_selectize_form_group">
                <label for="m_connection">
                    {_p var='connection_page'}
                </label>
                <select class="form-control" name="m_connection" id="m_connection">
                    {foreach from=$aBlocks key=sUrl item=aModule}
                    <option {if $sUrl == $sConnection}selected{/if} value="{if $sUrl=='site_wide'}site_wide{else}{$sUrl}{/if}">
                    {if $sUrl=='site_wide'}
                        {_p var='site_wide'}
                    {else}
                        {if isset($aModule.title)}
                            {$aModule.title}
                        {else}
                            {_p var='controller_'$sUrl}
                        {/if} ({$sUrl})
                    {/if}
                    </option>
                    {/foreach}
                </select>
            </div>
        </div>
    </div>
</form>
<div id="js_setting_block">
    <form method="post" class="form" action="{url link='admincp.user.group.add'}" onsubmit="$('#js_setting_saved').html($.ajaxProcess('Saving')).show(); $(this).ajaxCall('user.updateSettings'); return false;">
        {foreach from=$aModules key=iBlock item=aSubBlocks}
            <div class="panel panel-default">
                <div class="panel-heading">
                    {_p var='block_block_number' block_number=$iBlock}
                </div>
                <div class="table-responsive">
                    <table class="table table-admin js_drag_drop">
                        <thead>
                           <tr>
                               <th class="w60">{_p var='id'}</th>
                               <th class="w30"></th>
                               <th class="">{_p var='title'}</th>
                               <th class="w200">{_p var='apps'}</th>
                               <th class="w100 text-center">{_p var='active'}</th>
                               <th class="w80 t_center">{_p var='settings'}</th>
                           </tr>
                        </thead>
                        <tbody>
                        {foreach from=$aSubBlocks key=iKey item=aBlock}
                        <tr>
                            <td>{$aBlock.block_id}</td>
                            <td class="drag_handle">
                                <input type="hidden" name="val[ordering][{$aBlock.block_id}]" value="{$aBlock.ordering}" />
                            </td>
                            <td>
                                <a href="{url link='admincp.block.add' id=$aBlock.block_id m_connection=$sConnection}">
                                {if !empty($aBlock.title)}
                                {$aBlock.title}
                                {else}
                                {if $aBlock.type_id > 0}
                                {if $aBlock.type_id == 1}
                                {_p var='php_code'}
                                {else}
                                {_p var='html_code'}
                                {/if}
                                {else}
                                {$aBlock.module_name}::{$aBlock.component}
                                {/if}
                                {/if}
                                </a>
                            </td>
                            <td class="w200">
                                {$aBlock.module_name|translate:'module'}
                            </td>
                            <td class="on_off w100">
                                <div class="js_item_is_active"{if !$aBlock.is_active} style="display:none;"{/if}>
                                    <a href="#?call=admincp.updateBlockActivity&amp;id={$aBlock.block_id}&amp;active=0" class="js_item_active_link" title="{_p var='deactivate'}"></a>
                                </div>
                                <div class="js_item_is_not_active"{if $aBlock.is_active} style="display:none;"{/if}>
                                    <a href="#?call=admincp.updateBlockActivity&amp;id={$aBlock.block_id}&amp;active=1" class="js_item_active_link" title="{_p var='activate'}"></a>
                                </div>
                            </td>
                            <td class="text-center">
                                <a role="button" class="js_drop_down_link" title="{_p var='manage'}"></a>
                                <div class="link_menu">
                                    <ul class="dropdown-menu dropdown-menu-right">
                                        <li><a href="{url link='admincp.block.add.' id=$aBlock.block_id m_connection=$sConnection}">{_p var='edit'}</a></li>
                                        <li><a href="{url link='admincp.block.setting.' id=$aBlock.block_id m_connection=$sConnection}">{_p var='settings'}</a></li>
                                        <li><a href="{url link='admincp.block.' delete=$aBlock.block_id m_connection=$sConnection}" data-message="{_p var='are_you_sure' phpfox_squote=true}" class="sJsConfirm">{_p var='delete'}</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        {/foreach}
    </form>
</div>