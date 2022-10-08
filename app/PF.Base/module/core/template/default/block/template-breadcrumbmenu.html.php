<?php
/**
 * [PHPFOX_HEADER]
 *
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		phpFox LLC
 * @package 		Phpfox
 * @version 		$Id: controller.html.php 64 2009-01-19 15:05:54Z phpFox LLC $
 */

defined('PHPFOX') or exit('NO DICE!');

?>
{if ((!empty($aSubMenus) || isset($aCustomMenus))) && Phpfox::isUser() && (!defined('PHPFOX_IS_USER_STATISTICS') || !PHPFOX_IS_USER_STATISTICS)}
    <div class="page_breadcrumbs_menu">
        {if isset($aCustomMenus) && count($aCustomMenus)}
            {foreach from=$aCustomMenus key=iKey name=menu item=aMenu}
            <a class="btn btn-success{if (isset($aMenu.css_class))} {$aMenu.css_class}{/if}" href="{$aMenu.url}" {$aMenu.extra}>
                <span></span>{$aMenu.title}
            </a>
            {/foreach}
        {/if}
        {if !empty($aSubMenus)}
            {foreach from=$aSubMenus key=iKey name=submenu item=aSubMenu}
                {if isset($aSubMenu.module) && (isset($aSubMenu.var_name) || isset($aSubMenu.text))}
                    <a href="{url link=$aSubMenu.url)}"{if (isset($aSubMenu.css_name))} class="btn btn-success {$aSubMenu.css_name} no_ajax"{else}class="btn btn-success"{/if}>
                    <span></span>
                    {if isset($aSubMenu.text)}
                        {$aSubMenu.text}
                    {else}
                        {_p var=$aSubMenu.var_name}
                    {/if}
                    </a>
                {/if}
            {/foreach}
        {/if}
    </div>
{/if}


