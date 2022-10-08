<?php
defined('PHPFOX') or exit('NO DICE!');
?>

{if empty($cronItems)}
<div class="alert alert-warning">
    {_p var='this_configuration_is_set_in_a_configuration_file'}
</div>
{else}
<div class="table-responsive">
    <table class="table table-admin">
        <thead>
            <th>{_p var='name'}</th>
            <th>{_p var='app'}</th>
            <th>{_p var='core_next_run'}</th>
            <th>{_p var='core_recurrence'}</th>
            <th class="text-center">{_p var='active'}</th>
        </thead>
        <tbody>
            {foreach from=$cronItems item=cronItem}
            <tr {if isset($cronItem.is_error)}style="color: red;" title="{_p var='this_cron_has_not_run_with_its_recurrence_yet_maybe_you_have_not_configured_phpfox_cron_or_this_cron_does_not_work'}"{/if}>
                    <td>{$cronItem.name}</td>
                    <td>{$cronItem.app_name}</td>
                    <td>{$cronItem.next_run_text}</td>
                    <td>{_p var=$cronItem.frequency_phrase number=$cronItem.every}</td>
                    <td class="on_off">
                        <div class="js_item_is_active" {if !$cronItem.is_active}style="display:none"{/if}>
                            <a href="#?call=admincp.updateCronActivity&amp;id={$cronItem.cron_id}&amp;active=0" class="js_item_active_link" title="{_p var='deactivate'}"></a>
                        </div>
                        <div class="js_item_is_not_active" {if $cronItem.is_active}style="display:none"{/if}>
                            <a href="#?call=admincp.updateCronActivity&amp;id={$cronItem.cron_id}&amp;active=1" class="js_item_active_link" title="{_p var='activate'}"></a>
                        </div>
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>
</div>
{/if}