<?php

defined('PHPFOX') or exit('NO DICE!');
?>
{if isset($aBreadCrumbs) && count($aBreadCrumbs) > 0}
<div class="row breadcrumbs-holder">
	{if isset($aBreadCrumbs) && count($aBreadCrumbs) > 0}
	<div class="clearfix breadcrumbs-top">
		<div class="pull-left">
			<div class="breadcrumbs-list">
				{if isset($aBreadCrumbs)}
                <ol class="breadcrumb" data-component="breadcrumb">
                    {foreach from=$aBreadCrumbs key=sLink item=sCrumb name=link}
                    <li>
                        <a {if !empty($sLink)}href="{$sLink}" {/if} class="ajax_link">
                            {$sCrumb|clean}
                        </a>
                    </li>
                    {/foreach}
                    {if !$bIsDetailPage && !defined('PHPFOX_APP_DETAIL_PAGE') && !empty($aBreadCrumbTitle)}
                    <li><a href="{ $aBreadCrumbTitle[1] }" class="ajax_link">{ $aBreadCrumbTitle[0] }</a></li>
                    {/if}
                </ol>
				{/if}
			</div>
		</div>
		<div class="pull-right breadcrumbs_right_section">
			{breadcrumb_menu}
		</div>
	</div>
	{/if}
	{if ($bIsDetailPage || defined('PHPFOX_APP_DETAIL_PAGE')) && !empty($aBreadCrumbTitle)}
        <h1 class="breadcrumbs-bottom {if empty($aBreadCrumbTitle[2])}item-title{/if} {if isset($aTitleLabel.total_label) && $aTitleLabel.total_label > 0}header-has-label-{$aTitleLabel.total_label}{/if}">
            <a href="{ $aBreadCrumbTitle[1] }" class="ajax_link">{ $aBreadCrumbTitle[0] }</a>
            {if isset($aTitleLabel) && isset($aTitleLabel.type_id) && isset($aTitleLabel.label) && count($aTitleLabel.label)}
            <div class="{$aTitleLabel.type_id}-icon general-flag-icon-wrapper">
                {foreach from=$aTitleLabel.label key=sKey item=aLabel}
                <div class="sticky-label-icon title-label sticky-{$sKey}-icon" title="{_p var=$sKey}">
                    <span class="ico ico-{$aLabel.icon_class}"></span>
                    <span class="{if isset($aLabel.title_class)}{$aLabel.title_class}{/if}">{$aLabel.title}</span>
                </div>
                {/foreach}
            </div>
            {/if}
            {if !empty($aPageExtraLink)}
            <div class="view_item_link">
                <a href="{$aPageExtraLink.link}" class="page_section_menu_link" title="{$aPageExtraLink.phrase}">
                    <span>{$aPageExtraLink.phrase}</span>
                </a>
            </div>
            {/if}
        </h1>
	{/if}
</div>
{/if}