<?php
defined('PHPFOX') or exit('NO DICE!');
?>
<form id="js_user_upload_cover_photo" onsubmit="return $Core.CoverPhoto.submitUpload(this);" style="display: none;" enctype="multipart/form-data">
    <div><input type="hidden" name="val[action]" value="upload_photo_via_share"/></div>
    <div><input type="hidden" name="val[is_cover_photo]" value="1"/></div>
    {plugin call='profile.template_block_upload-cover-form'}
    <input type="file" name="image" onchange="$(this).parents('form:first').submit();">
</form>
