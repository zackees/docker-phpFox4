<?php
defined('PHPFOX') or exit('NO DICE!');
?>
<div class="panel panel-default table-responsive">
    {if count($aQuestions)}
        <form onsubmit="return $Core.User.Spam.deleteQuestions(this);">
            <table class="table table-admin">
                <tr class="tbl_questions_header">
                    <th class="w30">
                        <div class="custom-checkbox-wrapper">
                            <label>
                                <input type="checkbox" id="js_check_box_all" class="main_checkbox" />
                                <span class="custom-checkbox"></span>
                            </label>
                        </div>
                    </th>
                    <th>{_p var='image'}</th>
                    <th>{_p var='question'}</th>
                    <th>{_p var='answers'}</th>
                    <th class="t_center">{_p var='case_sensitive'}</th>
                    <th class="t_center">{_p var='active'}</th>
                    <th class="t_center w80">{_p var='settings'}</th>
                </tr>
                {foreach from=$aQuestions item=aQuestion key=index}
                    <tr id="tr_new_question_{$aQuestion.question_id}" class="{if $index % 2}checkRow{else}tr{/if}" style="display: table-row;">
                        <td>
                            <div class="custom-checkbox-wrapper">
                                <label>
                                    <input type="checkbox" name="ids[]" value="{$aQuestion.question_id}" class="checkbox" />
                                    <span class="custom-checkbox"></span>
                                </label>
                            </div>
                        </td>
                        <td class="question_image w220">
                            {img server_id=$aQuestion.server_id path='user.url_user_spam' file=$aQuestion.image_path}
                        </td>
                        <td class="question_question">
                            {$aQuestion.question_phrase}
                        </td>
                        <td class="question_answers">
                            <ul>
                                {foreach from=$aQuestion.answers_phrases item=sAnswer}
                                <li>{$sAnswer}</li>
                                {/foreach}
                            </ul>
                        </td>
                        <td class="on_off w120">
                            <div class="js_item_is_active"{if !$aQuestion.case_sensitive} style="display:none;"{/if}>
                                <a href="#?call=user.toggleCaseSensitiveSpamQuestion&amp;id={$aQuestion.question_id}&amp;active=0" class="js_item_active_link" title="{_p var='case_insensitive'}"></a>
                            </div>
                            <div class="js_item_is_not_active"{if $aQuestion.case_sensitive} style="display:none;"{/if}>
                                <a href="#?call=user.toggleCaseSensitiveSpamQuestion&amp;id={$aQuestion.question_id}&amp;active=1" class="js_item_active_link" title="{_p var='case_sensitive'}"></a>
                            </div>
                        </td>
                        <td class="on_off w120">
                            <div class="js_item_is_active"{if !$aQuestion.is_active} style="display:none;"{/if}>
                                <a href="#?call=user.toggleActiveSpamQuestion&amp;id={$aQuestion.question_id}&amp;active=0" class="js_item_active_link" title="{_p var='Deactivate'}"></a>
                            </div>
                            <div class="js_item_is_not_active"{if $aQuestion.is_active} style="display:none;"{/if}>
                                <a href="#?call=user.toggleActiveSpamQuestion&amp;id={$aQuestion.question_id}&amp;active=1" class="js_item_active_link" title="{_p var='Activate'}"></a>
                            </div>
                        </td>
                        <td class="on_off question_actions">
                            <a href="#" class="js_drop_down_link" title="{_p var='manage'}"></a>
                            <div class="link_menu">
                                <ul class="dropdown-menu dropdown-menu-right">
                                    <li><a href="{url link='admincp.user.spams.add' id=$aQuestion.question_id}">{_p var='edit'}</a></li>
                                    <li><a href="{url link='admincp.user.spam' delete={$aQuestion.question_id}" class="sJsConfirm" data-message="{_p var='are_you_sure'}">{_p var='delete'}</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                {/foreach}
            </table>
            <div class="table_hover_action hidden">
                <button class="btn btn-danger sJsCheckBoxButton disabled js_admincp_spam_question_delete_btn" disabled="disabled" data-confirm-message="{_p var='are_you_sure_you_want_to_delete_selected_questions'}">{_p var='delete'}</button>
            </div>
        </form>
    {else}
        <div class="panel-body">
            <div class="alert alert-info">{_p var='there_is_no_question'}</div>
        </div>
    {/if}
</div>