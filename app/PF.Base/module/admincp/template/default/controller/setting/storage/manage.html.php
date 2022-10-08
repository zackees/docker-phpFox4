<?php
defined('PHPFOX') or exit('NO DICE!');
?>
<div class="table-responsive">
    <table class="table table-admin">
        <thead>
            <th>{_p var='id'}</th>
            <th>{_p var='name'}</th>
            <th>{_p var='type'}</th>
            <th class="w60 text-center">{_p var='active'}</th>
            <th class="w120 text-center">{_p var='is_default'}</th>
            <th class="w120 text-center">{_p var='settings'}</th>
            </thead>
        <tbody>
        {foreach from=$aItems item=aItem}
            <tr>
                <td>
                    {$aItem.storage_id}
                </td>

                <td>
                    #{$aItem.storage_id} {if $aItem.storage_name}{$aItem.storage_name}{else}{$aItem.service_phrase_name|convert}{/if}
                </td>

                <td>
                    {if !$aItem.service_phrase_name}{$aItem.service_name}{else}{_p var=$aItem.service_phrase_name}{/if}
                </td>

                {if !$aItem.storage_id}
                    <td class="t_center">
                        {_p var='yes'}
                    </td>
                {elseif !empty($aItem.is_configured)}
                    <td class="on_off">
                        {if $aItem.is_active}{_p var='yes'}{else}{_p var='no'}{/if}
                    </td>
                {else}
                    <td class="on_off">
                        <div class="js_item_is_active {if !$aItem.is_active}hide{/if}">
                            <a href="#?call=admincp.setting.updateStorageActive&amp;storage_id={$aItem.storage_id}&amp;active=0" class="js_item_active_link" title="{_p var='deactivate'}"></a>
                        </div>
                        <div class="js_item_is_not_active {if $aItem.is_active}hide{/if}">
                            <a href="#?call=admincp.setting.updateStorageActive&amp;storage_id={$aItem.storage_id}&amp;active=1" class="js_item_active_link" title="{_p var='activate'}"></a>
                        </div>
                    </td>
                {/if}

                {if !$aItem.storage_id}
                    <td class="t_center">
                        {if $aItem.is_default}{_p var='yes'}{else}{_p var='no'}{/if}
                    </td>
                {else}
                    <td class="on_off">
                        <div class="js_item_is_active {if !$aItem.is_default}hide{/if}">
                            <a href="#?call=admincp.setting.updateStorageDefault&amp;storage_id={$aItem.storage_id}&amp;active=0" class="js_item_active_link" title="{_p var='deactivate'}"></a>
                        </div>
                        <div class="js_item_is_not_active {if $aItem.is_default}hide{/if}">
                            <a href="#?call=admincp.setting.updateStorageDefault&amp;storage_id={$aItem.storage_id}&amp;active=1" class="js_item_active_link" title="{_p var='activate'}"></a>
                        </div>
                    </td>
                {/if}

                <td class="text-center">
                    {if empty($aItem.is_configured) || (!$aItem.storage_id && count($aItems)>1)}
                        <a role="button" class="js_drop_down_link" title="Manage"></a>
                        <div class="link_menu">
                            <ul class="dropdown-menu dropdown-menu-right">
                                {if empty($aItem.is_configured)}
                                    {if !empty($aItem.edit_link)}
                                        <li>
                                            <a class="with-dots-separate"
                                               href="{url link=$aItem.edit_link}?storage_id={$aItem.storage_id}">
                                                {_p var='core.edit'}
                                            </a>
                                        </li>
                                    {/if}

                                    {if $aItem.storage_id}
                                        <li class="text-danger">
                                            <a class="sJsConfirm" href="{url link='admincp.setting.storage.manage' delete_id=$aItem.storage_id}">
                                                {_p var='core.delete'}
                                            </a>
                                        </li>
                                    {/if}
                                {/if}

                                {if $aItem.is_active && $aItem.storage_id == 0 && count($aItems) > 1 }
                                    <li>
                                        <a class="text-danger" href="{url link='admincp.setting.storage.transfer'}">
                                            {_p var='transfer_files'}
                                        </a>
                                    </li>
                                {/if}
                            </ul>
                        </div>
                    {/if}
                </td>
            </tr>
        {/foreach}
        </tbody>
    </table>
</div>