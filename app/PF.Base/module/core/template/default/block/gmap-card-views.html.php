<?php
/**
 * [PHPFOX_HEADER]
 *
 * @copyright        [PHPFOX_COPYRIGHT]
 * @author        phpFox LLC
 * @package        Phpfox
 * @version        $Id: gmap-card-views.html.php 3326 2019-08-12 09:12:45Z phpFox LLC $
 */

defined('PHPFOX') or exit('NO DICE!');
?>
<div class="item-block-outer">
    {plugin call='core.gmap_card_views_block_start'}
    <div class="item-header-title">
        {plugin call='core.gmap_card_views_header_start'}
        <div id="js-back-btn">
            <a class="item-btn-back" href="{if !empty($aMapSearchTools.search.action)}{$aMapSearchTools.search.action|clean}{else}{url link=$sType}{/if}" title="{_p var='back'}"><i class="ico ico-arrow-left"></i></a>
        </div>
        <div class="item-title">
            {$aParams.title}
        </div>
        <div class="js_core_map_button_toggle_collapse core-map-button-collapse-desktop dont-unbind">
            <div class="item-button-collapse show-list" title="{_p var='expand_side_panel'}">
                <i class="ico ico-angle-down"></i>
            </div>
            <div class="item-button-collapse show-map" title="{_p var='collapse_side_panel'}">
                <i class="ico ico-angle-up"></i>
            </div>
        </div>
        {plugin call='core.gmap_card_views_header_end'}
    </div>
    <div class="item-header-filter">
        {plugin call='core.gmap_card_views_header_filter_start'}
        {if isset($aMapSearchTools.search)}
            <div class="core-map-search-bar">
                <div class="gmap-header-bar-search">
                    <form id="form_main_search" class="" method="GET" action="{$aMapSearchTools.search.action|clean}"
                          onbeforesubmit="$Core.Search.checkDefaultValue(this,\'{$aMapSearchTools.search.default_value}\');"
                          onsubmit="$Core.Gmap.searchItemsOnMap(true, 'search[search]=' + $(this).find('#js-gmap_search_text').val(), true); return false;"
                    >
                        <div class="hidden">
                            {if (isset($aMapSearchTools.search.hidden))}
                            {$aMapSearchTools.search.hidden}
                            {/if}
                        </div>
                        <div class="header_bar_search_holder form-group has-feedback">
                            <div class="header_bar_search_inner">
                                <div class="input-group" style="width: 100%">
                                    <input type="search" id="js-gmap_search_text" class="form-control" name="search[{$aMapSearchTools.search.name}]" value="{if isset($aMapSearchTools.search.actual_value)}{$aMapSearchTools.search.actual_value|clean}{/if}" placeholder="{$aMapSearchTools.search.default_value}" />
                                    <a class="form-control-feedback" data-cmd="core.search_items">
                                        <i class="ico ico-search-o"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div id="js_search_input_holder">
                            <div id="js_search_input_content">
                                {if isset($sModuleForInput)}
                                {module name='input.add' module=$sModuleForInput bAjaxSearch=true}
                                {/if}
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        {/if}
        {if isset($aMapSearchTools.filters) && count($aMapSearchTools.filters)}
            <div class="gmap-header-filter-holder">
                {foreach from=$aMapSearchTools.filters key=sSearchFilterName item=aSearchFilters name=fkey}
                    {if !isset($aSearchFilters.is_input) && count($aSearchFilters.data)}
                    <div class="filter-options">
                        <a class="dropdown-toggle" data-toggle="dropdown">
                            <span>{if isset($aSearchFilters.active_phrase)}{$aSearchFilters.active_phrase}{else}{$aSearchFilters.default_phrase}{/if}</span>
                            <span class="ico ico-caret-down"></span>
                        </a>
                        <ul class="dropdown-menu {if $phpfox.iteration.fkey < 2}{else}dropdown-menu-left{/if} dropdown-menu-limit dropdown-line">
                            {foreach from=$aSearchFilters.data item=aSearchFilter}
                            <li>
                                <a href="javascript:void(0)" onclick="$Core.Gmap.searchItemsOnMap(true, '{$aSearchFilter.query}')" class="ajax_link {if isset($aSearchFilter.is_active)}active{/if}" rel="nofollow">
                                    {$aSearchFilter.phrase}
                                </a>
                            </li>
                            {/foreach}
                        </ul>
                    </div>
                    {/if}
                {/foreach}
            </div>
        {/if}
        {plugin call='core.gmap_card_views_header_filter_end'}
    </div>
    <div class="item-loading">
        <i class="ico ico-loading-icon"></i>
    </div>
    {plugin call='core.gmap_card_views_before_listing'}

    {if isset($aItems) && count($aItems)}
        <div class="core-map-item-listing js_core_map_item_listing">
            {plugin call='core.gmap_card_views_listing_start'}
            <div class="item-listing-wrapper">
                {foreach from=$aItems item=aItem}
                    <div id="js-map_item_{$aItem.id}" data-lat="{$aItem.latitude}" data-lng="{$aItem.longitude}" onmousemove="$Core.Gmap.showInfoWindow({$aItem.id});"
                         class="core-map-item js-gmap_item_card_view js-gmap_item_card_view_{$aItem.id}">
                        {if !empty($aParams.template_name)}
                            {template file=$aParams.template_name}
                        {else}
                            {template file=core.block.gmap-card-item}
                        {/if}
                    </div>
                {/foreach}
            </div>
            {plugin call='core.gmap_card_views_listing_end'}
        </div>

        {plugin call='core.gmap_card_views_listing'}

        {if !empty($aPagers) && $iTotalPagerItems = count($aPagers)}
            <div class="core-map-pager">
                <div class="js_pager_buttons">
                    <ul class="pagination items-{$iTotalPagerItems} mb-0">
                        {foreach from=$aPagers item=aPager}
                        <li class="page-item{if !empty($aPager.attr)} {$aPager.attr}{/if}{if !empty($aPager.rel)} {$aPager.rel}{/if}">
                            {if !empty($aPager.attr) && ($aPager.attr == 'disabled')}
                                <a class="page-link" href="javascript:void(0);" {if !empty($aPager.rel)}rel="{$aPager.rel}"{/if}>{$aPager.label}</a>
                            {else}
                                <a onclick="$.ajaxCall('{$sAjax}', '{$aPager.params}', 'GET'); return false;" class="page-link" {if !empty($aPager.rel)}rel="{$aPager.rel}"{/if} href="javascript:void(0);" >{$aPager.label}</a>
                            {/if}
                        </li>
                        {/foreach}
                    </ul>
                </div>
            </div>
        {/if}
    {else}
        {plugin call='core.gmap_card_views_empty_listing'}
        <div class="core-map-item-listing empty-listing">
            <div>{if isset($aParams.no_item_message)} {$aParams.no_item_message} {else} {_p var='no_items_found'} {/if}</div>
        </div>
    {/if}
    {plugin call='core.gmap_card_views_block_end'}
</div>
