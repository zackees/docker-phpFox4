<?php 
defined('PHPFOX') or exit('NO DICE!');
?>
<div class="table-responsive" id="js_admincp_user_cancellation_options">
    <form onsubmit="return $Core.UserAdmincp.deleteCancelOptions(this);">
        <table class="table table-admin" id="js_drag_drop">
            <thead>
                <tr>
                    <th class="w30"></th> <!-- Change order -->
                    <th class="w30">
                        {if !empty($aReasons)}
                            <div class="custom-checkbox-wrapper">
                                <label>
                                    <input type="checkbox" id="js_check_box_all" class="main_checkbox" />
                                    <span class="custom-checkbox"></span>
                                </label>
                            </div>
                        {/if}
                    </th>
                    <th>{_p var='cancellation_reason'}</th>
                    <th>{_p var='total'}</th>
                    <th class="text-center w60">{_p var='active'}</th>
                    <th class="w80 t_center">{_p var='settings'}</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$aReasons item=aReason key=iKey}
                <tr>
                    <td class="drag_handle">
                        <input type="hidden" name="val[ordering][{$aReason.delete_id}]" value="{$aReason.ordering}" />
                    </td>
                    <td>
                        <div class="custom-checkbox-wrapper">
                            <label>
                                <input type="checkbox" name="ids[]" value="{$aReason.delete_id}" class="checkbox" />
                                <span class="custom-checkbox"></span>
                            </label>
                        </div>
                    </td>
                    <td>{$aReason.phrase_text}</td>
                    <td>
                        <a href="{url link='admincp.user.cancellations.feedback' option_id={$aReason.phrase_var}">{$aReason.total}</a>
                    </td>
                    <td class="on_off">
                        <div class="js_item_is_active"{if !$aReason.is_active} style="display:none;"{/if}>
                             <a href="#?call=core.updateCancellationsActivity&amp;id={$aReason.delete_id}&amp;active=0" class="js_item_active_link" title="{_p var='deactivate'}"></a>
                        </div>
                        <div class="js_item_is_not_active"{if $aReason.is_active} style="display:none;"{/if}>
                             <a href="#?call=core.updateCancellationsActivity&amp;id={$aReason.delete_id}&amp;active=1" class="js_item_active_link" title="{_p var='activate'}"></a>
                        </div>
                    </td>
                    <td class="t_center">
                        <a class="js_drop_down_link" role="button"></a>
                        <div class="link_menu">
                            <ul class="dropdown-menu dropdown-menu-right">
                                <li><a href="{url link='admincp.user.cancellations.add' id={$aReason.delete_id}">{_p var='edit'}</a></li>
                                <li><a href="{url link='admincp.user.cancellations.manage' delete={$aReason.delete_id}" class="sJsConfirm" data-message="{_p var='are_you_completely_sure_you_want_to_delete_this_option'}">{_p var='delete'}</a></li>
                            </ul>
                        </div>
                    </td>
                </tr>

            {foreachelse}
            <tr>
                <td colspan="4">
                    <div class="extra_info">
                        {_p var='there_are_no_options_available'}
                        <ul>
                            <li><a href="{url link='admincp.user.cancellations.add'}">{_p var='click_here_to_add'}</a></li>
                        </ul>
                    </div>
                </td>
            </tr>
            {/foreach}
            </tbody>
        </table>

        <div class="table_hover_action hidden">
            <button class="btn btn-danger sJsCheckBoxButton disabled js_admincp_cancellation_option_delete_btn" disabled="disabled" data-confirm-message="{_p var='are_you_sure_you_want_to_delete_selected_options'}">{_p var='delete'}</button>
        </div>
    </form>
</div>