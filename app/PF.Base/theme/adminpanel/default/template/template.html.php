<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr{*$sLocaleDirection*}" lang="{$sLocaleCode}">
    {if !isset($bShowClearCache)}
    {assign var='bShowClearCache' value=false}
    {/if}
	<head>
    <title>{title}</title>
	{header}
	</head>
	<body class="admincp-fixed-menu {if !empty($sBodyClass)}{$sBodyClass}{/if}" >
		<div id="admincp_base"></div>
		<div id="global_ajax_message"></div>
		<div id="header" {if !empty($flavor_id)}class="theme-{$flavor_id}"{/if}>
            <div class="admincp-toggle-nav-btn js_admincp_toggle_nav_btn">
                <i class="ico ico-navbar"></i>
            </div>
            {logo}
            <div class="js_admincp_toggle_search admincp-btn-toggle-search"onclick="$('body').toggleClass('show-search-header');"><i class="ico ico-search-o"></i></div>
            <div class="admincp_header_form admincp_search_settings">
                <span class="remove"><i class="fa fa-remove"></i></span>
                <input type="text" name="setting" placeholder="{_p var='search_settings_dot'}" autocomplete="off">
                <div class="admincp_search_settings_results hide">
                </div>
            </div>
            <div class="admincp_right_group">
                <div class="admincp_alert dropdown">
                    <a data-toggle="dropdown" role="button" id="js_admincp_alert" data-panel="#js_admincp_alert_panel">
                        <div class="ajax" data-url="{url link='admincp.alert.badge'}"></div>
                        <i class="ico ico-bell2-o"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <div class="dropdown-panel-body" id="js_admincp_alert_panel">
                            <div class="item-loading"><i class="ico ico-loading-icon"></i></div>
                        </div>
                    </div>
                </div>
                <div class="admincp_user">
                    <div class="admincp_user_image">
                        {img user=$aUserDetails suffix='_120_square'}
                    </div>
                    <div class="admincp_user_content">
                        {$aUserDetails|user}
                        {$aUserDetails.user_group_title}
                    </div>
                </div>

                {if !Phpfox::demoModeActive()}
                <div class="admincp_view_site">
                    <a target="_blank" href="{url link=''}"><span class="item-text">{_p var='view_site'}&nbsp;</span><i class="fas fa fa-external-link"></i></a>
                </div>
                {/if}
            </div>

		</div>
		<aside class="js_admincp_toggle_nav_content">
            <ul class="">
                {php}
                    $this->_aVars['aAdminMenus'] =  Phpfox::getService('admincp.sidebar')->prepare()->get();
                {/php}
                {foreach from=$aAdminMenus key=sPhrase item=sLink}
                    {if is_array($sLink)}
                        {assign var='menuId' value="id_menu_item_"$sPhrase}
                        <li id="{$menuId}" {if $sLastOpenMenuId == $menuId}class="open"{/if}>
                            <a href="{$sLink.link}" data-tags="{if isset($sLink.tags)}{$sLink.tags}{/if}"
                               {if !empty($sLink.items)}
                                   class="item-header {if isset($sLink.is_active)}is_active{/if} {if isset($sLink.class)}{$sLink.class}{/if}" data-cmd="admincp.open_sub_menu"
                               {else}
                                   class="{if isset($sLink.is_active)}is_active{/if} {if isset($sLink.class)}{$sLink.class}{/if}"
                               {/if} {if isset($sLink.event)}{$sLink.event}{/if}>
                            {if !empty($sLink.icon)}<i class="{$sLink.icon}"></i>{/if}
                            {$sLink.label}
                            {if isset($sLink.items) and !empty($sLink.items)}
                            <i class="fa fa-caret"></i>
                            {/if}
                            {if isset($sLink.badge) && $sLink.badge > 0}
                            <span class="badge">{$sLink.badge}</span>
                            {/if}
                            </a>

                            {if isset($sLink.items) and !empty($sLink.items)}
                                <ul>
                                    {foreach from=$sLink.items item=sLink2}
                                    <li>
                                        <a data-tags="{if isset($sLink2.tags)}{$sLink2.tags}{/if}" href="{$sLink2.link}"
                                           class="{if isset($sLink2.class)}{$sLink2.class}{/if}{if isset($sLink2.is_active)}is_active{/if}" {if isset($sLink2.event)}{$sLink2.event}{/if}>
                                            {if !empty($sLink2.icon)}<i class="{$sLink2.icon}"></i>{/if}{$sLink2.label}
                                        </a>
                                    </li>
                                    {/foreach}
                                </ul>
                            {/if}
                        </li>
                    {/if}
                {/foreach}
            </ul>
            <div id="global_remove_site_cache_item">
                <a href="{url link='admincp.maintain.cache' all=1 return=$sCacheReturnUrl}">
                    <i class="ico ico-trash-o"></i>
                    {_p var='clear_all_caches'}
                </a>
            </div>
            {if $bEnableBundle}
            <div id="global_remove_site_cache_item">
                <a href="{url link='admincp.maintain.bundle' all=1 return=$sCacheReturnUrl}">
                    <i class="ico ico-file-zip-o"></i>
                    {_p var='bundle_js_css'}
                </a>
            </div>
            {/if}
            <div id="copyright">
                {param var='core.site_copyright'} &middot; <a href="#" id="select_lang_pack">{if Phpfox::getParam('language.display_language_flag') && !empty($sLocaleFlagId)}<img src="{$sLocaleFlagId}" alt="{$sLocaleName}" class="v_middle" /> {/if}{$sLocaleName}</a>
            </div>
            <br/>
            <br/>
            <br/>
            <br/>
		</aside>

        <!-- end action menu-->
        <div class="main_holder">
            {if !empty($aAdmincpBreadCrumb) || !empty($sSectionTitle)}
            <div class="breadcrumbs">
                {if !empty($aAdmincpBreadCrumb)}
                    {if count($aAdmincpBreadCrumb) > 1}
                        {foreach from=$aAdmincpBreadCrumb key=sUrl item=sPhrase}
                        <a href="{if !empty($sUrl)}{$sUrl}{else}#{/if}">{$sPhrase}</a>
                        {/foreach}
                    {/if}
                {elseif !empty($sSectionTitle)}
                    <a href="#">{$sSectionTitle}</a>
                {/if}
            </div>
            {/if}

            {if !empty($sLastBreadcrumb)}
                <h1 class="page-title">{$sLastBreadcrumb}</h1>
            {elseif !empty($sSectionTitle)}
                <h1 class="page-title">{$sSectionTitle}</h1>
            {/if}

            {if !empty($aActionMenu) or !empty($aSectionAppMenus)}
                <div class="toolbar-top">
                    {if !empty($aSectionAppMenus)}
                        <div class="btn-group acp-header-section js-acp-header-section">
                            {if count($aSectionAppMenus) <= 6}
                                {foreach from=$aSectionAppMenus key=sPhrase item=aMenu}
                                    <a {if isset($aMenu.cmd)}data-cmd="{$aMenu.cmd}"{/if}  href="{if (substr($aMenu.url, 0, 1) == '#')}{$aMenu.url}{else}{url link=$aMenu.url}{/if}"
                                    class="{if isset($aMenu.is_active) && $aMenu.is_active}active{/if}">{$sPhrase}</a>
                                {/foreach}
                            {else}
                                {foreach from=$aSectionAppMenus key=sPhrase item=aMenu name=fkey}
                                    {if $phpfox.iteration.fkey < 6}
                                        <a {if isset($aMenu.cmd)}data-cmd="{$aMenu.cmd}"{/if}  href="{if (substr($aMenu.url, 0, 1) == '#')}{$aMenu.url}{else}{url link=$aMenu.url}{/if}"
                                        class="{if isset($aMenu.is_active) && $aMenu.is_active}active{/if}">{$sPhrase}</a>
                                    {/if}
                                    {if $phpfox.iteration.fkey == 6}
                                    <div class="acp-menu-dropdown"> <!-- div dropdown -->
                                        <a class="dropdown-toggle" id="dropdownMenu1" href="" data-toggle="dropdown" aria-expanded="true" aria-haspopup="true">
                                            {_p var="more"}
                                            <span class="caret"></span>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenu1">
                                            {/if}
                                            {if $phpfox.iteration.fkey >= 6}
                                            <li role="menuitem">
                                                <a {if isset($aMenu.cmd)}data-cmd="{$aMenu.cmd}"{/if}  href="{if (substr($aMenu.url, 0, 1) == '#')}{$aMenu.url}{else}{url link=$aMenu.url}{/if}"
                                                class="{if isset($aMenu.is_active) && $aMenu.is_active}active{/if}">{$sPhrase}</a>
                                            </li>
                                            {/if}
                                            {/foreach}
                                        </ul>
                                    </div> <!-- end div dropdown -->
                            {/if}
                        </div>
                    {/if}
                    {if isset($aActionMenu)}
                        <div class="btn-group acp-action-menus">
                            {if $bMoreThanOneActionMenu}
                                <a role="button" class="btn btn-primary" data-toggle="dropdown">{_p var='actions'} <span class="ico ico-caret-down"></span></a>
                                <ul class="dropdown-menu dropdown-menu-right">
                            {/if}
                            {foreach from=$aActionMenu key=sPhrase item=sUrl}
                                {if is_array($sUrl)}
                                    {if $bMoreThanOneActionMenu}
                                    <li>
                                    {/if}
                                    <a {if isset($sUrl.cmd)}data-cmd="{$sUrl.cmd}"{/if}  href="{$sUrl.url}" class="{if $bMoreThanOneActionMenu}{$sUrl.dropdown_class}{else}btn {$sUrl.class}{/if}" {if isset($sUrl.custom)} {$sUrl.custom}{/if}>{$sPhrase}</a>
                                    {if $bMoreThanOneActionMenu}
                                    </li>
                                    {/if}
                                {else}
                                    {if $bMoreThanOneActionMenu}
                                    <li>
                                    {/if}
                                    <a href="{$sUrl}">{$sPhrase}</a>
                                    {if $bMoreThanOneActionMenu}
                                    </li>
                                    {/if}
                                {/if}
                            {/foreach}
                            {if $bMoreThanOneActionMenu}
                            </ul>
                            {/if}
                        </div>
                    {/if}
                </div>
            {/if}

            {if (isset($has_upgrade) && $has_upgrade)}
                <br/>
                <div class="alert alert-danger mb-base">
                    {_p var="There is an update available for this product."} <a class="btn btn-link" href="{$store.install_url}">{_p var="Update Now"}</a>
                </div>
            {/if}
            <div id="js_content_container">
                <div id="main">
                    {if isset($aSectionAppMenus)}
                    <div class="apps_content">
                        {/if}

                        {error}
                        <div class="_block_content">
                            {content}
                        </div>

                        {if isset($aSectionAppMenus)}
                    </div>
                    {/if}
                </div>
            </div>
        </div>
		{plugin call='theme_template_body__end'}	
        {loadjs}
        <div class="admincp-nav-bg js_admincp_toggle_nav_btn"></div>
	</body>
</html>