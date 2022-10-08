<?php 
/**
 * [PHPFOX_HEADER]
 * 
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		phpFox LLC
 * @package 		Phpfox
 * @version 		$Id: index.html.php 1558 2010-05-04 12:51:22Z phpFox LLC $
 */
 
defined('PHPFOX') or exit('NO DICE!'); 

?>
<div class="table-responsive">
    <table class="table table-admin" id="js_drag_drop">
        <thead>
            <tr class="nodrop">
                <th class="w20"></th>
                <th class="t_center w60">{_p var='id'}</th>
                <th class="t_center w60">{_p var='symbol'}</th>
                <th>{_p var='currency'}</th>
                <th class="w140">{_p var='format_uppercase'}</th>
                <th class="w60">{_p var='default_manage'}</th>
                <th class="w60">{_p var='active'}</th>
                <th class="w80 text-center">{_p var='settings'}</th>
            </tr>
        </thead>
        <tbody>
        {foreach from=$aCurrencies name=currencies item=aCurrency}
            <tr>
                <td class="drag_handle">
                    <input type="hidden" name="val[ordering][{$aCurrency.currency_id}]" value="{$aCurrency.ordering}" />
                </td>
                <td class="t_center">{$aCurrency.currency_id}</td>
                <td class="t_center">
                    <strong class="text-danger">{$aCurrency.symbol}</strong>
                </td>
                <td>{_p var=$aCurrency.phrase_var}</td>
                <td class="w140">{_p var=$aCurrency.format}</td>
                <td class="on_off">
                    {if $aCurrency.is_active}
                    <div class="js_item_is_active {if (!$aCurrency.is_default)}hide{/if}">
                        <a href="#?call=core.updateCurrencyDefault&amp;id={$aCurrency.currency_id}&amp;active=0" class="js_item_active_link js_remove_default" title="{_p var='set_as_default'}"></a>
                    </div>
                    <div class="js_item_is_not_active {if $aCurrency.is_default}hide{/if}">
                        <a href="#?call=core.updateCurrencyDefault&amp;id={$aCurrency.currency_id}&amp;active=1" class="js_item_active_link js_remove_default" title="{_p var='set_as_default'}"></a>
                    </div>
                    {/if}
                </td>
                <td class="on_off">
                    {if !$aCurrency.is_default}
                    <div class="js_item_is_active {if !$aCurrency.is_active}hide{/if}">
                        <a href="#?call=core.updateCurrencyActivity&amp;id={$aCurrency.currency_id}&amp;active=0" class="js_item_active_link" title="{_p var='deactivate'}"></a>
                    </div>
                    <div class="js_item_is_not_active {if $aCurrency.is_active}hide{/if}">
                        <a href="#?call=core.updateCurrencyActivity&amp;id={$aCurrency.currency_id}&amp;active=1" class="js_item_active_link" title="{_p var='activate'}"></a>
                    </div>
                    {/if}
                </td>
                <td class="text-center">
                    <a class="js_drop_down_link" title="{_p var='manage'}" role="button"></a>
                    <div class="link_menu">
                        <ul class="dropdown-menu dropdown-menu-right">
                            <li><a href="{url link='admincp.core.currency.add' id={$aCurrency.currency_id}">{_p var='edit'}</a></li>
                            {if !$aCurrency.is_default}
                                <li><a href="{url link='admincp.core.currency' delete={$aCurrency.currency_id}" class="sJsConfirm" data-message="{_p var='are_you_sure' phpfox_squote=true}">{_p var='delete'}</a></li>
                            {/if}
                        </ul>
                    </div>
                </td>
            </tr>
        {/foreach}
        </tbody>
    </table>
</div>