<?php 
 
defined('PHPFOX') or exit('NO DICE!'); 

?>
{if isset($aPager) && $aPager.totalPages > 1}

	<div class="pager_outer">
        <ul class="pagination">
            {if !isset($bIsMiniPager)}
            <li class="pager_total hide">{_p var='page_x_of_x' current=$aPager.current total=$aPager.totalPages}</li>
            {/if}

            {if isset($aPager.firstUrl)}
            <li class="pager-first">
                <a {if $sAjax}href="{$aPager.firstUrl}" onclick="$(this).parent().parent().parent().parent().find('.sJsPagerDisplayCount').html($.ajaxProcess('{_p var='loading'}')); $.ajaxCall('{$sAjax}', 'page={$aPager.firstUrl}{$aPager.sParams}'); $Core.addUrlPager(this); return false;"{else}href="{$aPager.firstUrl}"{/if}>
                    <span class="text-uppercase">{_p var='first'}</span>
                </a>
            </li>
            {/if}

            {if isset($aPager.prevUrl)}
            <li class="pager-prev">
                <a {if $sAjax}href="{$aPager.prevUrl}" onclick="$(this).parent().parent().parent().parent().find('.sJsPagerDisplayCount').html($.ajaxProcess('{_p var='loading'}')); $.ajaxCall('{$sAjax}', 'page={$aPager.prevAjaxUrl}{$aPager.sParams}'); $Core.addUrlPager(this); return false;"{else}href="{$aPager.prevUrl}"{/if}>
                    <span class="ico ico-arrow-left"></span>
                </a>
            </li>
            {/if}

			{foreach from=$aPager.urls key=sLink name=pager item=sPage}
				<li {if !isset($aPager.firstUrl) && $phpfox.iteration.pager == 1} class="first {if $aPager.current == $sPage}active{/if}"{else}{if $aPager.current == $sPage} class="active"{/if}{/if}>
                    <a {if $sAjax}href="{$sLink}" onclick="{if $sLink}$(this).parent().parent().parent().parent().find('.sJsPagerDisplayCount').html($.ajaxProcess('{_p var='loading'}')); $.ajaxCall('{$sAjax}', 'page={$sPage}{$aPager.sParams}'); $Core.addUrlPager(this);{/if} return false;{else}href="{if $sLink}{$sLink}{else}javascript:void(0);{/if}{/if}">{$sPage}</a>
                </li>
			{/foreach}

            {if isset($aPager.nextUrl)}
            <li class="pager-next">
                <a {if $sAjax}href="{$aPager.nextUrl}" onclick="$(this).parent().parent().parent().parent().find('.sJsPagerDisplayCount').html($.ajaxProcess('{_p var='loading'}')); $.ajaxCall('{$sAjax}', 'page={$aPager.nextAjaxUrl}{$aPager.sParams}'); $Core.addUrlPager(this); return false;"{else}href="{$aPager.nextUrl}"{/if}>
                    <span class="ico ico-arrow-right"></span>
                </a>
            </li>
            {/if}

            {if isset($aPager.lastUrl)}
            <li class="pager-last">
                <a {if $sAjax}href="{$aPager.lastUrl}" onclick="$(this).parent().parent().parent().parent().find('.sJsPagerDisplayCount').html($.ajaxProcess('{_p var='loading'}')); $.ajaxCall('{$sAjax}', 'page={$aPager.lastUrl}{$aPager.sParams}'); $Core.addUrlPager(this); return false;"{else}href="{$aPager.lastUrl}"{/if}>
                <span class="text-uppercase">{_p var='last'}</span>
                </a>
            </li>
            {/if}
        </ul>
	</div>
{/if}