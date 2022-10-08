<?php
defined('PHPFOX') or exit('NO DICE!');
?>

<form class="form" method="get">
    <div class="panel panel-default">
        <div class="panel-body">
            <div>
                <label><input onchange="this.form.submit()" type="checkbox" name="show_all" value="1" {if
                              $showAll== "1"}checked{/if}/>
                    &nbsp;{_p var='show_all_apps'}</label>
            </div>
        </div>
    </div>
</form>
{if isset($warning) && $warning}
<section class="apps">
    <div class="text-danger text-center">{$warning}</div>
</section>
{/if}
{if isset($vendorCreated)}
<i class="fa fa-spin fa-circle-o-notch"></i>
{else}
<div class="admincp_apps_holder">
    <table class="table table-admin">
        <thead>
        <tr>
            <th>
                {_p var='id'}
            </th>
            <th>
                {_p var='directory'}
            </th>
            <th>
                {_p var='version'}
            </th>
            <th>
                {_p var='re_validate'}
            </th>
            <th>
                {_p var='options'}
            </th>

        </tr>
        </thead>
        <tbody>
        {foreach from=$uploadedApps item=uploadedApp}
        <tr>
            <td>{$uploadedApp.apps_id}</td>
            <td>PF.Sites/Apps/{$uploadedApp.apps_dir}</td>
            <td>{$uploadedApp.version}</td>
            <td>
                {if !$uploadedApp.can_install}
                <a href="?cmd=re_validate&apps_id={$uploadedApp.apps_id}&apps_dir={$uploadedApp.apps_dir}&show_all={$showAll}">
                    {_p var='re_validate'}</a>
                {/if}
            </td>
            <td>
                {if $uploadedApp.can_upgrade}
                <a href="?cmd=upgrade&apps_id={$uploadedApp.apps_id}&apps_dir={$uploadedApp.apps_dir}&show_all={$showAll}">
                    {_p var='upgrade'}</a>
                {/if}
                {if $uploadedApp.can_install}
                <a href="?cmd=install&apps_id={$uploadedApp.apps_id}&apps_dir={$uploadedApp.apps_dir}&show_all={$showAll}">
                    {_p var='install'}</a>
                {/if}
            </td>
        </tr>
        {/foreach}
        </tbody>
    </table>
</div>
{/if}