<?php
defined('PHPFOX') or exit('NO DICE');
?>
<div id="thumbnail" class="tab-pane fade in active">
    <div id="profile_crop_me">
        <div class="image-editor">
            <div class="image-editor-bg"></div>
            <form method="post" action="{url link='user.photo.process'}" dir="ltr" id="update-profile-image-form">
                {module name='core.upload-form' type='user' params=$aUploadParams}
                <div class="cropit-preview"></div>
                <div class="cropit-drag-info"><span>{_p var='drag_to_reposition_photo'}</span></div>
                <div class="cropit-btn-edit">
                    <input type="range" class="cropit-image-zoom-input"/>
                    <button type="button" class="rotate-ccw"><i class="ico ico-rotate-left-alt"></i></button>
                    <button type="button" class="rotate-cw"><i class="ico ico-rotate-right-alt"></i></button>
                </div>
                <input type="hidden" name="image-data" class="hidden-image-data" />
                <div><input type="hidden" name="val[crop-data]" value="" id="crop_it_form_image_data" /></div>
            </form>
            <div class="fa-4x hide"><i class="fa fa-spinner fa-spin"></i></div>
        </div>
        <div class="rotate_button">
            <button class="btn btn-default" onclick="$Core.ProfilePhoto.update(false)">{_p var='change_photo'}</button>
            <button class="btn btn-primary" onclick="$Core.ProfilePhoto.save()">{_p var="Save"}</button>
        </div>
    </div>
</div>

{literal}
<script>
    $Behavior.checkIE = function() {
      if ($Core.getIEVersion() > 0) {
        $('.cropit-btn-edit input').addClass('ie');
      }
      if ($('#profile_photo_form').length) {
        $('#profile_photo_form').addClass('no_delete');
      }
      $Behavior.checkIE = function() {};
    }
</script>
{/literal}
