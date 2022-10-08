<?php
defined('PHPFOX') or exit('NO DICE!');
?>

<div class="user-admincp-block-exportusers">
    <div class="panel panel-default">
        <div class="panel-body">
            <div class="form-group" id="js_user_export_content">
                <form id="js_block_admincp_export_users" method="POST" action="{url link='current'}">
                    <div class="pull-left">
                        {foreach from=$aFields key=sFieldKey item=aField}
                        <div class="form-group export-field">
                            <label>
                                <input type="checkbox" name="{if $aField.is_main_field}val[field][]{else}val[custom_field][]{/if}" value="{$sFieldKey}" {if $aField.is_main_field}checked{/if}>
                                {$aField.text}
                            </label>
                        </div>
                        {/foreach}
                    </div>
                    <div>
                        <button class="btn btn-primary" onclick="$Core.AdminCP.processUsers.exportUsers(); return false;" id="js_user_export_btn">{_p var='save'}</button>
                        <button class="btn btn-cancel" onclick="js_box_remove(this); return false;">{_p var='close'}</button>
                    </div>
                </form>
            </div>
            <div class="js_user_export_result hide">
            </div>
        </div>
    </div>
</div>
