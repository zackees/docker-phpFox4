<?php
defined('PHPFOX') or exit('NO DICE!');
?>
<div class="table-responsive">
    <table class="table table-admin" id="js_drag_drop">
        <thead>
            <tr class="nodrop">
                <th class="w30"></th>
                <th class="w30"><input type="checkbox" class="js_active_multiple_item"></th>
                <th class="w50">{_p var='iso'}</th>
                <th>{_p var='name'}</th>
                <th class="w100">{_p var='states_provinces'}</th>
                <th class="w60">{_p var='active'}</th>
                <th class="w80 text-center">{_p var='settings'}</th>
            </tr>
        </thead>
        <tbody>
        {foreach from=$aCountries name=countries item=aCountry}
            <tr>
                <td class="drag_handle">
                    <input type="hidden" name="val[ordering][{$aCountry.country_iso}]" value="{$aCountry.ordering}" />
                </td>
                <td class="t_center">
                    <input type="checkbox" class="js_active_item" value="{$aCountry.country_iso}">
                </td>
                <td class="t_center">{$aCountry.country_iso}</td>
                <td>{$aCountry.name}</td>
                <td class="t_center">{if $aCountry.total_children > 0}<a href="{url link='admincp.core.country.child' id={$aCountry.country_iso}">{/if}{$aCountry.total_children}{if $aCountry.total_children > 0}</a>{/if}</td>
                <td class="on_off">
                    <div class="js_item_is_active {if empty($aCountry.is_active)}hide{/if}">
                        <a href="#?call=core.activateCountry&amp;country_iso={$aCountry.country_iso}&amp;active=0" class="js_item_active_link" title="{_p var='deactivate'}"></a>
                    </div>
                    <div class="js_item_is_not_active {if !empty($aCountry.is_active)}hide{/if}">
                        <a href="#?call=core.activateCountry&amp;country_iso={$aCountry.country_iso}&amp;active=1" class="js_item_active_link" title="{_p var='activate'}"></a>
                    </div>
                </td>
                <td class="t_center">
                    <a role="button" class="js_drop_down_link" title="{_p var='manage'}"></a>
                    <div class="link_menu">
                        <ul class="dropdown-menu dropdown-menu-right">
                            <li><a class="popup" href="{url link='admincp.core.country.add' id={$aCountry.country_iso}">{_p var='edit'}</a></li>
                            <li><a href="{url link='admincp.core.country.child.add' iso={$aCountry.country_iso}" class="popup">{_p var='add_state_province'}</a></li>
                            {if $aCountry.total_children > 0}
                            <li><a href="{url link='admincp.core.country.child' id={$aCountry.country_iso}">{_p var='manage_states_provinces'}</a></li>
                            <li><a href="{url link='admincp.core.country' export={$aCountry.country_iso}">{_p var='export'}</a></li>
                            {/if}
                            <li><a href="#" onclick="$(this).parents('.link_menu:first').hide(); tb_show('{_p var='translate' phpfox_squote=true}', $.ajaxBox('core.admincp.countryTranslate', 'height=410&amp;width=550&country_iso={$aCountry.country_iso}')); return false;">{_p var='translate'}</a></li>
                            <li><a href="{url link='admincp.core.country' delete={$aCountry.country_iso}" class="sJsConfirm">{_p var='delete'}</a></li>
                        </ul>
                    </div>
                </td>
            </tr>
        {/foreach}
        </tbody>
    </table>
</div>
<div class="form-group lines form-group-save-changes" style="z-index: 99;">
    <div class="pull-left">
        <span class="js_count_selected_item hide_it" style="color: #d6e1e5;font-size: 14px;"></span>
        <button class="btn btn-danger js_active_multiple_item" data-type="btn">{_p var='select_all'}</button>
    </div>
    <div class="pull-right">
        <button class="btn btn-success js_active_multiple_item_btn" data-type="0">{_p var='deactivate'}</button>
        <button class="btn btn-success js_active_multiple_item_btn" data-type="1">{_p var='activate'}</button>
    </div>
</div>
