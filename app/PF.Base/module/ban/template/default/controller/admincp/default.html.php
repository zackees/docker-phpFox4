<?php 
defined('PHPFOX') or exit('NO DICE!');
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <div class="panel-title">{_p var='search_filter'}</div>
    </div>
    <form method="get">
        <div class="panel-body">
            <div class="form-group">
                {$aFilters.keyword}
            </div>
        </div>
        <div class="panel-footer">
            <button class="btn btn-primary">{_p var='search'}</button>
        </div>
    </form>
</div>
<div class="panel panel-default">
    <div class="panel-heading">
        <div class="panel-title">{_p var='add_filter'}</div>
    </div>
    <form method="post" action="{url link=$aBanFilter.url}" onsubmit="$Core.onSubmitForm(this, true);">
        <div class="panel-body">
            <div class="form-group">
                <label for="find_value">{$aBanFilter.form}:</label>
                <input type="text" name="val[find_value]" value="{$sFindValue}" size="30" class="form-control" id="find_value"/>
                <span class="help-block">{_p var='use_the_asterisk_for_wildcard_entries'}</span>
            </div>
            {if isset($aBanFilter.replace)}
                <div class="form-group">
                    <label for="replacement">{_p var='replacement'}:</label>
                    <input type="text" name="replacement" value="" size="30" class="form-control" id="replacement"/>
                </div>
            {/if}
            {module name='ban.form'}
        </div>
        <div class="panel-footer">
            <input type="submit" value="{_p var='add'}" class="btn btn-primary" />
        </div>
    </form>
</div>

<div class="block_content" id="js_admincp_ban_filters_content">
	{if count($aBanFilters)}
        <input type="hidden" id="js_ban_filters_type" value="{$aBanFilter.type}">
        <div class="table-responsive">
            <table class="table table-admin">
                <thead>
                    <tr>
                        <th style="width:20px;"><input type="checkbox" id="js_ban_checkbox_all"></th>
                        <th>{$aBanFilter.form}</th>
                        {if isset($aBanFilter.replace)}
                        <th>{_p var='replacement'}</th>
                        {/if}
                        <th style="width:150px;">{_p var='added_by'}</th>
                        <th style="width:150px;">{_p var='added_on'}</th>
                        <th class="t_center w20">{_p var='settings'}</th>
                    </tr>
                </thead>
                <tbody>
                {foreach from=$aBanFilters name=filters item=aFilter}
                    <tr{if !is_int($phpfox.iteration.filters/2)} class="tr"{/if}>
                        <td class="t_center">
                            <input type="checkbox" class="js_ban_checkbox" data-id="{$aFilter.ban_id}">
                        </td>
                        <td>{$aFilter.find_value|clean}</td>
                        {if isset($aBanFilter.replace)}
                            <td>{$aFilter.replacement|clean}</td>
                        {/if}
                        <td>{if empty($aFilter.user_id)}{_p var='n_a'}{else}{$aFilter|user}{/if}</td>
                        <td>{$aFilter.time_stamp|date}</td>
                        <td class="t_center">
                            <a role="button" class="js_drop_down_link" title="{_p var='manage'}"></a>
                            <div class="link_menu">
                                <ul class="dropdown-menu dropdown-menu-right">
                                    <li><a href="{url link=$aBanFilter.url delete={$aFilter.ban_id}"  data-message="{_p var='are_you_sure' phpfox_squote=true}" class="sJsConfirm">{_p var='delete'}</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
            <button class="btn btn-danger" id="js_ban_filters_delete_selected">{_p var='delete_selected'} </button>
            {pager}
        </div>
	{else}
        <div class="alert alert-empty">
            {_p var='no_bans_found_dot'}
        </div>
	{/if}
</div>