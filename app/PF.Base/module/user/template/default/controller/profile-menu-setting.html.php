<?php
defined('PHPFOX') or exit('NO DICE!');
?>
{if !empty($aProfileMenuPrivacies)}
<div id="profile_menu_settings">
    <form id="js_form_profile_menus" method="post" action="{url link='user.profile-menu-setting'}">
        <div class="privacy-block-content">
            {foreach from=$aProfileMenuPrivacies key=sPrivacy item=aProfile}
            <div class="item-outer">
                {template file='user.block.privacy-profile'}
            </div>
            {/foreach}
        </div>
        <div class="form-group-button mt-1">
            <input type="submit" value="{_p var='save_changes'}" class="btn btn-primary" />
        </div>
    </form>
</div>
{else}
<div class="alert alert-empty">
    {_p var='profile_menu_setting_unavailable'}
</div>
{/if}