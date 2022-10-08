<?php

defined('PHPFOX') or exit('NO DICE!');

?>
<div class="breadcrumbs">
	{if empty($aBreadCrumbTitle) && isset($aBreadCrumbs)}
	{foreach from=$aBreadCrumbs key=sLink item=sCrumb name=link}
		{if $phpfox.iteration.link == 1}
		{if count($aBreadCrumbTitle)}<div class="h1">{else}<h1>{/if}{if !empty($sLink)}<a href="{$sLink}" class="ajax_link">{/if}{$sCrumb|clean}{if !empty($sLink)}</a>{/if}{if count($aBreadCrumbTitle)}</div>{else}</h1>{/if}
		{/if}
	{/foreach}
	{/if}
	{if isset($aBreadCrumbs)}
	{breadcrumb_list}
	{/if}
	{menu_sub}
	{if isset($bIsUsersProfilePage)}
	{breadcrumb_menu}
	{/if}
</div>