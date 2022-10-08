<?php 
defined('PHPFOX') or exit('NO DICE!');
?>
{add_script key='share.js' value='module_attachment'}
<div class="attachment-holder js_attachment_holder" id="{$holderId}" data-element-id="{$id}" data-default-location="{$defaultAttachmentLocation}">
    <div class="global_attachment global_attachment__has_file">
        <div class="global_attachment_header">
            <div class="global_attachment_manage">
                <a class="border_radius_4" role="button" onclick="$Core.Attachment.toggleAttachmentForm(this)" id="attachment-toggle-button">
                    <span class="ico ico-paperclip-alt"></span> <span class="item-label-text">{_p var='attachments'}</span> <span class="attachment-counter">{if !empty($totalAttachment)}({$totalAttachment}){else}(0){/if}</span>
                    <span class="ico ico-angle-down"></span>
                </a>
            </div>
            <ul class="global_attachment_list" data-id="{$id}">
                <li>
                    <a role="button" onclick="$Core.Attachment.attachPhoto(this)" class="js_global_position_photo js_hover_title">
                        <span class="ico ico-photo-plus-o"></span>
                        <span class="js_hover_info">{_p var='insert_a_photo'}</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <div class="attachment-form-holder" style="display: none;">
        <div id="attachment_params" class="js_attachment_params">
            <input type="hidden" name="category_name" value="{$aAttachmentShare.type}">
            <input type="hidden" name="attachment_obj_id" value="{$aAttachmentShare.id}">
            <input type="hidden" name="upload_id" value="js_new_temp_form_0_{$aAttachmentShare.type}">
            <input type="hidden" name="custom_attachment" value="">
            <input type="hidden" name="holder_id" value="{$holderId}">
            <input type="hidden" name="textarea_id" value="{$id}">
            <input type="hidden" name="has_attachment" value="0">
            {if isset($defaultAttachmentLocation)}
                <input type="hidden" name="default_location" value="{$defaultAttachmentLocation}">
            {/if}
        </div>
        {if empty($id)}
            {module name='core.upload-form' type='attachment' current_photo=''}
        {else}
            {module name='core.upload-form' type='attachment' current_photo='' id=$id}
        {/if}
        <div class="js_attachment_list">
            <div class="js_attachment_list_holder">
                <div class="attachment_holder">
                    <div class="attachment_list">
                        {if !empty($aForms.total_attachment) && isset($aAttachmentShare.edit_id)}
                            {module name='attachment.list' sType=$aAttachmentShare.type iItemId=$aAttachmentShare.edit_id attachment_no_header=true bGetAttachmentList=true editorId=$id defaultLocation=$defaultAttachmentLocation}
                        {/if}
                        <span class="no-attachment {if !empty($aForms.total_attachment)}hide{/if}">{_p var='no_attachments_available'}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="attachment-form-action">
            <a role="button" onclick="$Core.Attachment.deleteAll(this);" class="attachment-delete-all {if empty($aForms.total_attachment)}hide{/if}">
                {_p var='delete_all'}
            </a>
            <a role="button" class="btn attachment-close js_hover_title" onclick="$('#attachment-toggle-button', '#{$holderId}').trigger('click')">
                <span class="ico ico-close"></span>
                <span class="js_hover_info">{_p var='close_attachment'}</span>
            </a>
        </div>
    </div>
</div>

{literal}
<script type="text/javascript">
    $Behavior.reinitAttachmentsAfterReloadPage = function() {
        $Core.Attachment.initReload({
            holder_id: '{/literal}{if !empty($holderId)}{$holderId}{/if}{literal}',
            id: '{/literal}{if !empty($id)}{$id}{/if}{literal}',
        });
        $Behavior.reinitAttachmentsAfterReloadPage = null;
    }
</script>
{/literal}
