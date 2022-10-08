<div id="admincp_alert_page">
    {if count($aItems)}
    <div class="panel panel-default">
        <div class="panel-heading"><div class="panel-title">{_p var='alerts'}</div></div>
        <table class="table table-admin">
            <tbody>
            {foreach from=$aItems item=aItem}
            <tr>
                <td>{$aItem.message}</td>
                <td class="w100">
                    <a class="btn btn-xs btn-success" target="{if isset($aItem.target)}{$aItem.target}{else}_blank{/if}" href="{$aItem.link}">
                        {_p var='continue'}
                    </a>
                </td>
            </tr>
            {/foreach}
            </tbody>
        </table>
    </div>
    {else}
    <div class="admincp_alert">
        <div class="alert_icon">
            <span class="ico ico-inbox-o"></span>
        </div>
        <div class="alert_message">
            {_p var="It looks like you have no alert message at this time"}
        </div>
    </div>
    {/if}
</div>