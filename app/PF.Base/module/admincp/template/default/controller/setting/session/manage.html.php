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
        <th class="w120">{_p var='id'}</th>
        <th>{_p var='type'}</th>
        {if !$useEnvFile}
        <th class="w120 text-center">{_p var='is_default'}</th>
        <th class="w80 text-center">{_p var='settings'}</th>
        </thead>
        {/if}
        <tbody>
        {foreach from=$aItems item=aItem}
        <tr>
            <td>
                {$aItem.service_id}
            </td> <td>
                {_p var=$aItem.service_phrase_name}
            </td>
            {if !$useEnvFile}
            <td class="text-center">
                {if $aItem.is_default}{_p var='core.yes'}{else}{_p var='core.no'}{/if}
            </td>
            <td class="text-center">
                {if $aItem.edit_link}
                <a href="{url link=$aItem.edit_link}">{_p var='core.edit'}</a>
                {/if}
            </td>
            {/if}
        </tr>
        {/foreach}
        </tbody>
    </table>
</div>