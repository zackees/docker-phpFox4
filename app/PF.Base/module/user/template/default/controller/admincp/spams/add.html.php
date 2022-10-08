<?php
defined('PHPFOX') or exit('NO DICE!');
?>
<form class="form" method="post" enctype="multipart/form-data">
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="panel-title">{if !empty($aQuestion.question_id)}{_p var='edit_question'}{else}{_p var='add_new_question'}{/if}</div>
        </div>
        <div class="panel-body">
            <div class="form-group">
                {field_language phrase='sPhraseTitle' label='question' field='title' format='val[title][' size=40 maxlength=250}
            </div>

            <div class="form-group">
                <label>{_p var='image'}</label>
                <input type="file" name="file" id="input_file" onchange="$Core.User.Spam.fileChanged();" class="form-control" accept="image/jpeg, image/gif, image/png"/>
                <p class="help-block">{_p var='you_can_upload_a_jpg_gif_or_png_file'}</p>
                <div id="div_edit_image">
                    {if isset($aQuestion.image_path) && $aQuestion.image_path}
                    <div id="div_edit_image_imge">
                        {img server_id=$aQuestion.server_id path='user.url_user_spam' file=$aQuestion.image_path}
                    </div>
                    <input type="button" class="btn btn-link" id="btn_edit_remove_image" value="{_p var='delete_image'}" onclick="$Core.User.Spam.deleteImage();" />
                    {/if}
                    <input type="hidden" name="val[preserve_image]" value="1" />
                </div>
            </div>

            <div class="form-group form-group-follow">
                <label for="case_sensitive">{_p var='case_sensitive'}</label>
                <div class="item_is_active_holder">
                    <span class="js_item_active item_is_active"><input type="radio" id="case_sensitive" name="val[case_sensitive]" value="1" {value type='radio' id='case_sensitive' default='1' selected='true'}/></span>
                    <span class="js_item_active item_is_not_active"><input type="radio" id="case_sensitive" name="val[case_sensitive]" value="0" {value type='radio' id='case_sensitive' default='0'}/></span>
                </div>
                <div class="help-block">{_p var='spam_question_case_sensitive_helper'}</div>
            </div>
            <div class="form-group form-group-follow">
                <label for="is_active">{_p var='active'}</label>
                <div class="item_is_active_holder">
                    <span class="js_item_active item_is_active"><input type="radio" id="is_active" name="val[is_active]" value="1" {value type='radio' id='is_active' default='1' selected='true'}/></span>
                    <span class="js_item_active item_is_not_active"><input type="radio" id="is_active" name="val[is_active]" value="0" {value type='radio' id='is_active' default='0'}/></span>
                </div>
            </div>
            <div class="form-group">
                <label>{_p var='answers'}</label>
            </div>
            {if isset($aQuestion.parsed_answers_phrases)}
            {foreach from=$aQuestion.parsed_answers_phrases item=msg}
            <div class="valid_answer form-group clearfix">
                <div class="valid_answer_text">
                    {field_language phrase=$msg allow_multiple='1' label='answer' format='val[answer][' size=40 maxlength=250}
                    <span class="item_valid_answer_delete">
                            <a class="btn btn-danger" role="button" onclick="$Core.User.Spam.deleteAnswer(this);">
                                <i class="fa fa-remove"></i>
                            </a>
                        </span>
                </div>
            </div>
            {foreachelse}
            <div class="valid_answer form-group clearfix">
                <div class="valid_answer_text ">
                    {field_language allow_multiple='1' label='answer' format='val[answer][' size=40 maxlength=250}
                    <span class="item_valid_answer_delete">
                            <a class="btn btn-danger" role="button" onclick="$Core.User.Spam.deleteAnswer(this);">
                                <i class="fa fa-remove"></i>
                            </a>
                        </span>
                </div>
            </div>
            {/foreach}
            {/if}
            <div class="form-group" id="div_add_answers">
                <span id="div_add_answer" onclick="$Core.User.Spam.addAnswer();">
                    <i class="fa fa-plus-circle"></i>{_p var='add_more_answers'}
                </span>
                <div id="div_add_answer">
                </div>
            </div>
        </div>
        <div class="panel-footer" style="display: flex;">
            {if $iQuestionId}
            <input type="submit" value="{_p var='update'}" id="btn_submit" class="btn btn-primary" />
            {else}
            <input type="submit" value="{_p var='add_question'}" id="btn_submit" class="btn btn-primary" />
            {/if}
            <a class="btn btn-link " style="margin-left: 8px;" href="{url link='admincp.user.spam'}">{_p var='cancel'}</a>
        </div>
    </div>
</form>
<!-- template for adding more answers -->
<div id="tpl_answer" style="display: none;">
    <div class="valid_answer form-group clearfix">
        <div class="valid_answer_text">
            {field_language allow_multiple='1' label='answer' format='val[answer][' size=40 maxlength=250}
            <span class="item_valid_answer_delete">
                <a class="btn btn-danger"role="button" onclick='$Core.User.Spam.deleteAnswer(this);'>
                    <i class="fa fa-remove"></i>
                </a>
            </span>
        </div>
    </div>
</div>