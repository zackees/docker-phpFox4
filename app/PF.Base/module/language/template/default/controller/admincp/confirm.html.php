<?php
defined('PHPFOX') or exit('NO DICE!');
?>

<form method="post" action="{url link='admincp.language.import' }" enctype="multipart/form-data">
    <input type="hidden" name="is_confirm" value="1"/>
    <input type="hidden" name="dir" value="{$dir}"/>
    <input type="hidden" name="page" value="{$iPage}"/>
    <div class="form-group">
        <label>{_p var='do_u_want_to_override_existing_phrases'}</label>
        <div class="custom-radio-wrapper">
            <label class="radio-inline">
                <input type="radio" name="is_override" value="0" checked class="v_middle checkbox">
                <span class="custom-radio"></span>{_p var='no'}
            </label>
            <label class="radio-inline">
                <input type="radio" name="is_override" value="1" class="v_middle checkbox">
                <span class="custom-radio"></span>{_p var='yes'}
            </label>
        </div>
    </div>
    <div class="form-group">
        <input type="submit" class="btn btn-primary" value="{_p var='upgrade'}"/>
    </div>
</form>
