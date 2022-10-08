<?php 
defined('PHPFOX') or exit('NO DICE!');
?>
{if !empty($aMenus)}
<form method="post" action="{url link='admincp.menu' parent=$iParentId}" class="form">
    {foreach from=$aMenus key=sType item=aMenusSub}
        <div class="panel panel-default">
            <div class="panel-heading">
            {if !empty($aParentMenu)}
                {_p var='children_of_menu'}: <strong>{_p var=$aParentMenu.var_name}</strong>
            {else}
                {_p var='menu'}: <strong>{$sType}</strong>
            {/if}
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-admin" id="js_drag_drop_{$sType}">
                    <thead>
                    <tr>
                        <th></th>
                        <th>{_p var="name"}</th>
                        <th>{_p var="url"}</th>
                        {if $iParentId === 0}
                            <th>{_p var="sub_menu"}</th>
                        {/if}
                        <th>{_p var="active"}</th>
                        <th class="t_center">{_p var='settings'}</th>
                    </tr>
                    </thead>
                    <tbody>
                        {foreach from=$aMenusSub key=iKey item=aMenu}
                            {template file='admincp.block.menu.entry'}
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    {/foreach}
</form>
{else}
    <div class="text-center">{_p var='no_menus_found'}</div>
{/if}