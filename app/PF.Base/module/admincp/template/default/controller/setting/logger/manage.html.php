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
        <th>{_p var='id'}</th>
        <th>{_p var='type'}</th>
        <th>{_p var='log_level'}</th>
        <th class="w60">{_p var='active'}</th>
        <th class="w80 text-center">{_p var='settings'}</th>
        <th class="w120"></th>
        </thead>
        <tbody>
        {foreach from=$aItems item=aItem}
        <tr>
            <td>{$aItem.service_id}</td>
            <td>
                {_p var=$aItem.service_phrase_name}
            </td>
            <td>
                {if $aItem.level_name} {$aItem.level_name} {else} DEBUG {/if}
            </td>
            <td class="on_off">
                {if $aItem.is_active}{_p var='core.yes'}{else}{_p var='core.no'}{/if}
            </td>
            <td>
                {if $aItem.edit_link && !$useEnvFile}
                <a href="{url link=$aItem.edit_link}">{_p var='core.edit'}</a>
                {/if}
            </td>
            <td>
                {if in_array($aItem.service_id,$supportedViewer)}
                    <a href="{url link='admincp.setting.logger.view' service=$aItem.service_id}" target="_blank">{_p var='view_logs'}</a>
                {/if}
            </td>
        </tr>
        {/foreach}
        </tbody>
    </table>
</div>