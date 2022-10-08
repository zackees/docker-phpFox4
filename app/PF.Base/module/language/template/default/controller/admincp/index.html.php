<?php 
defined('PHPFOX') or exit('NO DICE!');
?>
<div class="panel panel-default">
    <table class="table table-admin">
        <thead>
        <tr class="nodrop">
            <th class="">{_p var='name'}</th>
            <th class="w60 t_center">{_p var='version'}</th>
            <th class="w200 t_center">{_p var='latest_version'}</th>
            <th class="w80 t_center">{_p var='default_manage'}</th>
            <th class="w80 t_center">{_p var='active'}</th>
            <th class="w80 text-center">{_p var='settings'}</th>
        </tr>
        </thead>
        <tbody>
            {foreach from=$aLanguages key=iKey item=aLanguage}
                <tr class="checkRow{if is_int($iKey/2)} tr{else}{/if}">
                    <td>{if $aLanguage.is_master}({_p var='master'}) {/if}{$aLanguage.title}</td>
                    <td class="t_center">{$aLanguage.version}</td>
                    <td class="t_center">
                        {if isset($aLanguage.latest_version_url)}
                            <a href="{$aLanguage.latest_version_url}">
                                {_p var="Upgrade to"}: {$aLanguage.latest_version}
                            </a>
                        {else}
                            {$aLanguage.latest_version}
                        {/if}
                    </td>
                    <td class="on_off">
                        {if $aLanguage.is_active}
                            <div class="js_item_is_active {if (!$aLanguage.is_default)}hide{/if}">
                                <a href="#?call=language.updateLanguageDefault&amp;id={$aLanguage.language_id}&amp;active=0" class="js_item_active_link js_remove_default" title="{_p var='set_as_default'}"></a>
                            </div>
                            <div class="js_item_is_not_active {if $aLanguage.is_default}hide{/if}">
                                <a href="#?call=language.updateLanguageDefault&amp;id={$aLanguage.language_id}&amp;active=1" class="js_item_active_link js_remove_default" title="{_p var='set_as_default'}"></a>
                            </div>
                        {/if}
                    </td>
                    <td class="on_off">
                        {if !$aLanguage.is_default && !$aLanguage.is_master}
                            <div class="js_item_is_active {if !$aLanguage.is_active}hide{/if}">
                                <a href="#?call=language.updateLanguageActivity&amp;id={$aLanguage.language_id}&amp;active=0" class="js_item_active_link" title="{_p var='deactivate'}"></a>
                            </div>
                            <div class="js_item_is_not_active {if $aLanguage.is_active}hide{/if}">
                                <a href="#?call=language.updateLanguageActivity&amp;id={$aLanguage.language_id}&amp;active=1" class="js_item_active_link" title="{_p var='activate'}"></a>
                            </div>
                        {/if}
                    </td>
                    <td class="t_center w60">
                        <a role="button" class="js_drop_down_link" title="{_p var='manage'}"></a>
                        <div class="link_menu">
                            <ul class="dropdown-menu dropdown-menu-right">
                                <li><a href="{url link="admincp.language.phrase" lang-id=""$aLanguage.language_id""}">{_p var='manage_phrases'}</a></li>
                                <li><a href="{url link="admincp.language.add" id=""$aLanguage.language_id""}">{_p var='edit_settings'}</a></li>
                                <li><a href="{url link='admincp.language.missing' id=$aLanguage.language_id}">{_p var='find_missing_phrases'}</a></li>
                                <li><a href="{url link='admincp.language' export=$aLanguage.language_id}">{_p var='export'}</a></li>
                                {if !$aLanguage.is_default && !$aLanguage.is_master}
                                    <li><a class="sJsConfirm" href="{url link="admincp.language.delete" id=""$aLanguage.language_id""}">{_p var='delete'}</a></li>
                                {/if}
                            </ul>
                        </div>
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>
</div>
<div class="admincp_apps_holder">
	<section class="preview">
		<h1>{_p var='featured_language_packs'}</h1>
		<div class="phpfox_store_featured" data-type="language" data-parent="{url link='admincp.store' load='language'}"></div>
	</section>
</div>