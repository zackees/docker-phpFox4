<?php

defined('PHPFOX') or exit('NO DICE!');

?>
<form class="activity-feed-status-form js_quick_edit_privacy_form" method="post" onsubmit="return $Core.feed.submitEditInlinePrivacy(this);">
    <input type="hidden" name="val[feed_id]" value="{$iFeedId}">
    <input type="hidden" name="val[module]" value="{$sModule}" id="js_edit_privacy_module">
    <input type="hidden" name="val[item_id]" value="{$iItemId}">
    <div class="form-group">
        {if Phpfox::isModule('privacy')}
            <div id="js_custom_privacy_input_holder">
                {module name='privacy.build' privacy_item_id=$aForms.item_id privacy_module_id=$aForms.type_id}
            </div>
        {/if}
        <div class="special_close_warning">
            {if !isset($bFeedIsParentItem) && (!defined('PHPFOX_IS_USER_PROFILE') || (defined('PHPFOX_IS_USER_PROFILE') && $aForms.user_id == Phpfox::getUserId() && $aForms.feed_reference == 1) || (defined('PHPFOX_IS_USER_PROFILE') && isset($aUser.user_id) && $aUser.user_id == Phpfox::getUserId() && empty($mOnOtherUserProfile))) && $aForms.type_id != 'feed_comment'}
                {module name='privacy.form' privacy_name='privacy' list_type='true' inline_privacy='true' btn_size='normal'}
            {/if}
        </div>
    </div>
    <div class="form-group t_center">
        <input type="submit" id="activity_feed_submit" class="button btn-sm btn-primary btn-block" value="{_p var='Update'}"/>
    </div>
</form>
