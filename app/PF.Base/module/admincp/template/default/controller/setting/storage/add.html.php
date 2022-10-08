<?php
defined('PHPFOX') or exit('NO DICE!');
?>

<form method="post" class="form" action="{url link='current'}" id="js_form">
    <p>
        {_p var='add_storage_form_description'}
    </p>
    {if $sError}
    <div class="alert alert-danger">
        {$sError}
    </div>
    {/if}
    <div id="client_details" class="panel panel-default">
        <div class="panel-body">
            <div>
                <div class="form-group">
                    <label>{_p var='select_storage_type'}</label>
                    {foreach from=$aItems item=aItem}
                    <div>
                        <label>
                            <input type="radio" name="service_id" value="{$aItem.service_id}" {if $aItem.service_id==$sServiceId}checked{/if}>
                            &nbsp; {_p var=$aItem.service_phrase_name}
                        </label>
                    </div>
                    {/foreach}
                </div>
                <div class="form-group">
                    <button class="btn btn-primary" type="submit" role="button">{_p var='continue'}</button>
                    <a class="btn btn-default" role="button" href="{url link='admincp.setting.storage.manage'}">{_p var='cancel'}</a>
                </div>
            </div>
        </div>
</form>
