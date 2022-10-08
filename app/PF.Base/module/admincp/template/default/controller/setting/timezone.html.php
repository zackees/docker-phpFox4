<?php
defined('PHPFOX') or exit('NO DICE!');
?>

<div class="panel panel-default" id="js_core_admincp_time_zone_settings">
    <div class="table-responsive">
        <form id="js_form_point_settings" method="post" action="{url link='admincp.setting.timezone'}">
            <input type="hidden" value="{$iGroupId}" name="group_id">
            <table class="table" cellpadding="0" cellspacing="0" style="margin-bottom: 0;">
                <thead>
                    <tr>
                        <th>{_p var='time_zone'}</th>
                        <th class="w200">{_p var='active'}</th>
                    </tr>
                </thead>
                <tbody>
                {foreach from=$aTimeZones key=sRegion item=aRegionTimeZones}
                    <tr class="js_core-timezone__admincp-index-settings-is-region {if !empty($aRegionTimeZones.data)}js_admincp_table_header_toggle{/if} core-table-header-toggle" data-region="{$sRegion}" data-togglecontent="{$sRegion}">
                        <td class="core-timezone__admincp-index-settings-region-name">
                            {if !empty($aRegionTimeZones.data)}
                                <span class="module-icon-collapse">
                                    <span class="ico ico-angle-down"></span>
                                </span>
                            {/if}
                            {$sRegion}
                        </td>
                        <td class=""><input type="checkbox" class="js_check_all_module" data-module="{$sRegion}" {if !empty($aRegionTimeZones.active)}checked{/if}></td>
                    </tr>
                    {foreach from=$aRegionTimeZones.data key=sTimeZoneKey item=aTimeZone}
                        <tr class="js_toggle_module_{$sRegion} core-table-content-toggle">
                            <td style="padding-left: 32px;">{$aTimeZone.text}</td>
                            <td class=""><input type="checkbox" name="val[{$sTimeZoneKey}][disable]" id="js_check_setting_{$sTimeZoneKey}" class="js_check_time_zone {if $sDefaultTimezone == $sTimeZoneKey}js_default_timezone{/if}" data-checkbox-module="{$sRegion}" {if !$aTimeZone.disable}checked{/if} {if $sDefaultTimezone == $sTimeZoneKey}disabled{/if}> {if $sDefaultTimezone == $sTimeZoneKey}( {_p var='admincp_timezone_default'} ){/if}</td>
                        </tr>
                    {/foreach}
                {/foreach}

                </tbody>
            </table>
            <div class="form-group lines form-group-save-changes" style="z-index: 99;">
                <button class="btn btn-primary">{_p var='save_changes'}</button>
                <button class="btn btn-default" onclick="$Core.reloadPage();">{_p var='cancel'}</button>
            </div>
        </form>
    </div>
</div>