<?php
defined('PHPFOX') or exit('NO DICE!');
?>
<div class="core-admincp-app-settings-search">
    <div class="panel panel-default">
        <div class="panel-body row">
            <div class="form-group js_core_init_selectize_form_group col-sm-3">
                <label for="module_id">{_p var='apps'}</label>
                <select name="module-id" class="form-control" id="js_app_settings_module_id" data-url="{url link='admincp.setting.manage'}">
                    {foreach from=$aSearchModules item=aModule}
                        <option {if $aModule.module_id == $sModule}selected{/if} value="{$aModule.module_id}" {if $aModule.module_id == $sSelectedModuleId}selected{/if}>{_p var=$aModule.title'}</option>
                    {/foreach}
                </select>
            </div>
        </div>
    </div>
</div>

{template file='admincp.controller.setting.edit'}

{literal}
    <script type="text/javascript">
        $Behavior.initAppSettingEvents = function() {
          if ($('#js_app_settings_module_id').length) {
            $('#js_app_settings_module_id').off('change').on('change', function() {
                let url = rtrim($(this).data('url'), '/'),
                    moduleId = $(this).val();
                if (!moduleId) {
                    return false;
                }
                window.location.href = url + '/?module-id=' + moduleId;
            });
          }
        }
    </script>
{/literal}
