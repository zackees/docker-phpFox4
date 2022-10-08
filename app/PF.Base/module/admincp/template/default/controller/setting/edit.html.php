<?php 
defined('PHPFOX') or exit('NO DICE!');
?>
<script>
    var set_active = false, setting_group_class = '';
    {if ($sGroupClass)}
    setting_group_class = '{$sGroupClass}';
    {/if}
    {literal}
    $Ready(function() {
        if (set_active) {
            return;
        }
        set_active = true;
        $('._is_app_settings').show();
        $('.js-acp-header-section a[href*="admincp/setting/edit"]').addClass('active');
        if (setting_group_class) {
            $('.' + setting_group_class + ':not(.is_option_class)').show();
            $('.' + setting_group_class + '.is_option_class').each(function() {
                var option_class = $(this).data('option-class').split('='),
                    s_key = option_class[0],
                    s_value = option_class[1],
                    i = $(this),
                    t = $('.__data_option_' + s_key + '');
                if (t.length) {
                    if (t.val() == s_value) {
                        i.show();
                    } else {
                        i.hide();
                    }
                }
            });
        }
    });
    {/literal}
</script>
{if count($aSettings)}
    <form method="post" action="{url link='current'}" enctype="multipart/form-data" class="on_change_submit">
        <div class="panel panel-default global-settings _is_app_settings">
            <div class="panel-heading">
                <div class="panel-title">
                    {_p var='manage_settings'}
                    {if ($admincp_help)} <a href="{$admincp_help}" target="_blank" class="pull-right" style="font-size: 20px;"><i class="fa fa-info-circle"></i></a>{/if}
                </div>
            </div>
            <div class="panel-body">
                {if count($aAppGroupSettings)}
                    <div class="form-group">
                        <input class="form-control js_admincp_search_app_group_settings" placeholder="{_p var='search_settings_dot'}"/>
                    </div>
                    <table class="table table-bordered admin-setting-container" id="settings_container">
                        {foreach from=$aAppGroupSettings item=aSettings key=sModuleId}
                            <tr class="js_admincp_table_header_toggle core-table-header-toggle js_setting_holder" data-module="{$sModuleId}" data-togglecontent="{$sModuleId}" data-action="{if $aSettings.app_active}close{else}open{/if}">
                                <td>
                                    <span class="module-icon-collapse">
                                        <span class="ico {if !$aSettings.app_active}ico-angle-down{else}ico-angle-up{/if}"></span>
                                    </span>
                                    {$aSettings.app_name|clean}
                                </td>
                            </tr>
                            {foreach from=$aSettings.settings item=aSetting key=var}
                                <tr class="js_toggle_module_{$sModuleId} {if $aSettings.app_active}open{/if} core-table-content-toggle js_settings">
                                    <td>
                                        {template file='admincp.block.setting-entry'}
                                    </td>
                                </tr>
                            {/foreach}
                        {/foreach}
                    </table>
                {else}
                    {foreach from=$aSettings item=aSetting key=var}
                        {template file='admincp.block.setting-entry'}
                    {/foreach}
                {/if}
                {if $sGroupId == 'mail'}
                    <div class="form-group lines">
                        <label>{_p var="Send a Test Email"}</label>
                        <input class="form-control" type="text" value="" name="val[email_send_test]" placeholder="{_p var='To'}"/>
                        <p class="help-block">
                            {_p var="Type an email address here and then click Send Test to generate a test email"}
                        </p>
                    </div>
                {/if}

                {if count($aSettings)}
                    <div class="form-group lines form-group-save-changes">
                        <button type="submit" class="btn btn-primary">{_p var='Save Changes'}</button>
                        {if $sGroupId == 'mail'}
                            <button type="submit" name="test" value="test" class="btn btn-primary">{_p var='Test'}</button>
                        {/if}
                    </div>
                {/if}
            </div>
        </div>
    </form>
{else}
    <div class="alert alert-empty">{_p var='setting_group_avaliable_settings'}</div>
{/if}
