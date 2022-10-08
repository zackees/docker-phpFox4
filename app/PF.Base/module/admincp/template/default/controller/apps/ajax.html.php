<?php
defined('PHPFOX') or exit('NO DICE!');
?>
<table class="table table-admin" id="list_apps">
    <thead>
    <tr>
        <th class="w30"></th>
        <th id="app_column_index" class="sortable" onclick="$Core.sortTable(this, 'list_apps');">
            {_p var="name"}
        </th>
        <th class="w120 sortable" onclick="$Core.sortTable(this, 'list_apps');">{_p var="version"}</th>
        <th class="w120 sortable" onclick="$Core.sortTable(this, 'list_apps');">{_p var="latest"}</th>
        <th class="sortable" onclick="$Core.sortTable(this, 'list_apps');">{_p var="author"}</th>
        <th class="w80 text-center">{_p var="Active"}</th>
        <th class="w80 text-center">{_p var='settings'}</th>
    </tr>
    </thead>
    <tbody>
        {foreach from=$apps item=app}
            <tr>
                <td>{if $app.is_active}<a href="{url link='admincp.app' id=$app.id}">{/if}{$app.icon}{if $app.is_active}</a>{/if}
                </td>
                <td>
                    {if $app.is_active}<a href="{url link='admincp.app' id=$app.id}">{/if}
                        {$app.name|clean}
                        {if $app.is_active}</a>{/if}
                </td>
                <td>
                    {if $app.is_phpfox_default}
                        {_p var='core'}
                    {else}
                        {$app.version}
                    {/if}
                </td>
                <td>
                    {if $app.is_phpfox_default}
                        {_p var='core'}
                    {elseif !empty($app.latest_version)}
                        {$app.latest_version}
                    {/if}
                    {if isset($app.have_new_version) && $app.have_new_version}
                    <br />
                    <a href="{$app.have_new_version}">
                        {_p var='upgrade_now'}
                    </a>
                    {/if}
                </td>
                <td>
                    {if !empty($app.publisher_url)}
                    <a href="{$app.publisher_url}" target="_blank">
                        {/if}
                        {$app.publisher}
                        {if !empty($app.publisher_url)}
                    </a>
                    {/if}
                </td>
                <td class="on_off">
                    {if $app.allow_disable}
                        <div class="js_item_is_active {if !$app.is_active}hide{/if}">
                            <a href="#?call=admincp.updateModuleActivity&amp;id={$app.id}&amp;active=0" class="js_item_active_link" title="{_p var='deactivate'}"></a>
                        </div>
                        <div class="js_item_is_not_active {if $app.is_active}hide{/if}">
                            <a href="#?call=admincp.updateModuleActivity&amp;id={$app.id}&amp;active=1" class="js_item_active_link" title="{_p var='activate'}"></a>
                        </div>
                    {/if}
                </td>
                <td class="text-center">
                    {if (!$app.is_module && $bIsTechie) || (!$app.is_core && $app.allow_disable)}
                        <a class="js_drop_down_link" role="button"></a>
                        <div class="link_menu">
                            <ul class="dropdown-menu dropdown-menu-right">
                                {if !$app.is_module && $bIsTechie}
                                    <li><a href="{url link='admincp.app' id=$app.id verify=1 home=1}">{_p var="re-validation"}</a></li>
                                    <li><a href="{url link='admincp.app' id=$app.id export=1}">{_p var="Export"}</a></li>
                                {/if}
                                {if !$app.is_core && $app.allow_disable}
                                    <li><a href="{url link='admincp.app' id=$app.id uninstall='yes'}" data-message="{_p var='are_you_sure' phpfox_squote=true}" class="sJsConfirm">{_p var='uninstall'}</a></li>
                                {/if}
                            </ul>
                        </div>
                    {/if}
                </td>
            </tr>
        {/foreach}
    </tbody>
</table>
