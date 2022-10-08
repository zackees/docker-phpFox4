<?php
defined('PHPFOX') or exit('NO DICE!');
?>
{if is_array($aResults) && count($aResults)}
    {foreach from=$aResults item=aResult}
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="panel-title">{$aResult.table}</div>
        </div>
        <div class="panel-body">
            {if isset($aResult.th)}
            <div class="table-responsive">
                <table class="table table-admin">
                    <thead>
                    <tr>
                        {foreach from=$aResult.th item=sTh}
                        <th>{$sTh}</th>
                        {/foreach}
                    </tr>
                    </thead>
                    <tbody>
                    {foreach from=$aResult.results key=iKey item=aValues}
                    <tr{if is_int($iKey/2)} class="tr"{/if}>
                        {foreach from=$aValues item=sValue}
                        <td>{$sValue}</td>
                        {/foreach}
                    </tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>
            {else}
                {if isset($aResult.results)}
                    {foreach from=$aResult.results key=sKey item=sValue}
                    <div class="form-group flex-row col-md-6">
                        <div class="table_left">
                            {$sKey}:
                        </div>
                        <div class="table_right">
                            {$sValue}
                        </div>
                    </div>
                    {/foreach}
                {/if}
            {/if}
        </div>
    </div>
    {/foreach}
{else}
<form method="post" action="{url link='admincp.core.ip'}" class="">
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="panel-title">{_p var='search'}</div>
        </div>
        <div class="panel-body">
            <div class="form-group">
                <label for="search">{_p var='ip_address'}</label>
                <input type="text" name="search" value="" class="form-control" size="40" id="search"/>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary"><i class="fa fa-search" aria-hidden="true"></i> {_p var='search'}</button>
            </div>
        </div>
    </div>
</form>
{/if}