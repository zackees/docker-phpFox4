<?php
defined('PHPFOX') or exit('NO DICE!');
?>
{if $useEnvFile}
<div class="alert alert-warning">
    {_p var='this_configuration_is_set_in_a_configuration_file'}
</div>
{/if}
<div class="table-responsive">
    <table class="table table-admin">
        <thead>
        <th class="w50">{_p var='id'}</th>
        <th>{_p var='name'}</th>
        <th>{_p var='type'}</th>
        {if !$useEnvFile}
        <th class="w120 text-center">{_p var='is_active'}</th>
        <th class="w80 text-center">{_p var='settings'}</th>
        {/if}
        </thead>
        <tbody>
        {foreach from=$aItems item=aItem}
        <tr>
            <td>
                {$aItem.queue_id}
            </td>
            <td>
                {$aItem.queue_name}
            </td>
            <td>
                {_p var=$aItem.service_phrase_name}
            </td>
            {if !$useEnvFile}
            <td class="text-center">
                {if $aItem.is_active}{_p var='core.yes'}{else}{_p var='core.no'}{/if}
            </td>
            <td class="text-center">
                <a role="button" class="js_drop_down_link" title="Manage">
                </a>
                <div class="link_menu">
                    <ul class="dropdown-menu dropdown-menu-right">
                        {if $aItem.edit_link}
                        <li>
                            <a href="{url link=$aItem.edit_link}?queue_id={$aItem.queue_id}">{_p var='core.edit'}</a>
                        </li>
                        {/if}
                        <li>
                            <a href="{url link='admincp.setting.queue.transfer'}?queue_id={$aItem.queue_id}">
                                {_p var='change_queue_type'}
                            </a>
                        </li>
                        {if $aItem.queue_id != 1 }
                        <li class="text-danger">
                            <a href="{url link=$aItem.edit_link}?queue_id={$aItem.storage_id}">
                                {_p var='core.delete'}
                            </a>
                        </li>
                        {/if}
                    </ul>
                </div>
            </td>
            {/if}
        </tr>
        {/foreach}
        </tbody>
    </table>
</div>