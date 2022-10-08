<?php
    defined('PHPFOX') or exit('NO DICE!');
?>
<style type="text/css">
    .profiles_banner_bg .cover img.cover_photo
        {l}
        position: relative;
        left: 0;
        top: {$iCoverPhotoPosition}px;
    {r}
</style>
{literal}
<script>
    $Core.coverPhotoPositionTop = {/literal}{$iCoverPhotoPosition}{literal};
</script>
{/literal}
<div class="profiles_banner {if isset($aCoverPhoto.server_id)}has_cover{/if}" {if $sCoverDefaultUrl}style="background-image:url({$sCoverDefaultUrl})"{/if}>
    <div class="profiles_banner_bg">
        <div class="cover_bg"></div>
        <div class="cover-reposition-message"><i class="ico ico-arrows-move"></i>&nbsp;{_p var='drag_to_reposition_your_photo'}</div>
        {if !empty($sCoverPhotoLink)}
            <a href="{$sCoverPhotoLink}">
        {/if}
        <div class="cover" id="cover_bg_container">
            <div id="uploading-cover" style="display: none;">
                <div class="progress">
                    <div class="progress-bar" role="progressbar" aria-valuenow="0"
                         aria-valuemin="0" aria-valuemax="100" style="width:0"></div>
                </div>
                <div>{_p var='uploading_your_photo_three_dot'}</div>
            </div>
            {if !empty($aCoverPhoto.destination)}
                {img server_id=$aCoverPhoto.server_id path='photo.url_photo' file=$aCoverPhoto.destination suffix='' class="visible-md visible-lg cover_photo js_background_image"}
            {else}
                <img class="_image_ image_deferred visible-md visible-lg cover_photo has_image js_background_image" style="display: none !important;">
            {/if}
            {if !empty($aCoverPhoto.destination)}
                {img server_id=$aCoverPhoto.server_id path='photo.url_photo' file=$aCoverPhoto.destination suffix='' class="hidden-md hidden-lg js_background_image is_responsive"}
            {else}
                <img class="_image_ image_deferred hidden-md hidden-lg has_image js_background_image is_responsive" style="display: none !important;">
            {/if}
        </div>
        {if !empty($aCoverPhoto.photo_id)}
            </a>
        {/if}
        <div class="cover-reposition-actions" id="js_cover_reposition_actions">
            <button role="button" class="btn btn-default" onclick="$Core.CoverPhoto.reposition.cancel()">{_p var='cancel'}</button>
            <button id="save_reposition_cover" class="btn btn-primary" onclick="$Core.CoverPhoto.reposition.save();">{_p var='save'}</button>
        </div>
    </div>
	<div class="cover_shadown"></div>
	<div class="profiles_info">
        <h1 {if Phpfox::getParam('user.display_user_online_status')}class="has-status-online"{/if}>
            <a href="{if isset($aUser.link) && !empty($aUser.link)}{url link=$aUser.link}{else}{url link=$aUser.user_name}{/if}" title="{$aUser.full_name|clean} {if Phpfox::getUserParam('profile.display_membership_info')} &middot; {_p var=$aUser.title}{/if}">
                {$aUser.full_name|clean}
            </a>
            {if Phpfox::getParam('user.display_user_online_status')}
                {if $aUser.is_online}
                    <span class="user_is_online" title="{_p var='online'}"><i class="fa fa-circle js_hover_title"></i></span>
                {else}
                    <span class="user_is_offline" title="{_p var='offline'}"><i class="fa fa-circle js_hover_title"></i></span>
                {/if}
            {/if}
        </h1>
		<div class="profiles_extra_info">
            {if (!empty($aUser.gender_name))}<span>{$aUser.gender_name}</span>{/if}
			{if Phpfox::getService('user.privacy')->hasAccess('' . $aUser.user_id . '', 'profile.view_location') && (!empty($aUser.city_location) || !empty($aUser.country_child_id) || !empty($aUser.location))}
                <span>
                    {_p var='lives_in'}
                    {if !empty($aUser.city_location)}&nbsp;{$aUser.city_location}{/if}
                    {if !empty($aUser.city_location) && (!empty($aUser.country_child_id) || !empty($aUser.location))},{/if}
                    {if !empty($aUser.country_child_id)}&nbsp;{$aUser.country_child_id|location_child},{/if}
                    {if !empty($aUser.location)}&nbsp;{$aUser.location}{/if}
                </span>
			{/if}
			{if isset($aUser.birthdate_display) && is_array($aUser.birthdate_display) && count($aUser.birthdate_display)}
                <span>
                    {foreach from=$aUser.birthdate_display key=sAgeType item=sBirthDisplay}
                        {if $aUser.dob_setting == '2'}
                            {if $sBirthDisplay == 1}
                                {_p var='1_year_old'}
                            {else}
                                {_p var='age_years_old' age=$sBirthDisplay}
                            {/if}
                        {else}
                            {_p var='born_on_birthday' birthday=$sBirthDisplay}
                        {/if}
                    {/foreach}
                </span>
			{/if}
			{if Phpfox::getParam('user.enable_relationship_status') && isset($sRelationship) && $sRelationship != ''}<span>{$sRelationship}</span>{/if}
			{if isset($aUser.category_name)}<span>{$aUser.category_name|convert}</span>{/if}
            {if (isset($aUser.is_friend_request) && $aUser.is_friend_request == 2)}
                <div>
                    <span class="pending-friend-request"><span class="ico ico-clock-o mr-1"></span>{_p var='pending_friend_request'}</span>&nbsp;
                    <span class="cancel-friend-request">
                        <a href="javascript:void(0)" class="friend_action_remove" onclick="$.ajaxCall('friend.removePendingRequest', 'id={$aUser.is_friend_request_id}','GET');">
                            {_p var='Cancel request'}
                        </a>
                    </span>
                </div>
            {/if}
		</div>

        {plugin call='profile.template_block_pic_info'}

	</div>
	<div class="profile_image">
		<div class="profile_image_holder">
		    {if Phpfox::isAppActive('Core_Photos') && $aProfileImage}
                <a href="{url link='photo'}{$aProfileImage.photo_id}{if Phpfox::getParam('photo.photo_show_title', 1)}/{$aProfileImage.title}{/if}">
                    {$sProfileImage}
                </a>
		    {else}
			    {$sProfileImage}
		    {/if}
		</div>
		{if Phpfox::getUserId() == $aUser.user_id}
		<div class="p_4">
			<span href="{url link='user.photo'}" title="{_p var='change_picture'}" onclick="$Core.ProfilePhoto.update({if $sPhotoUrl}'{$sPhotoUrl}'{else}false{/if}, {$iServerId})">{_p var='change_picture'}</span>
		</div>
		{/if}
	</div>
	{if Phpfox::getUserId() == $aUser.user_id}
        <div class="profiles_owner_actions">
            <div class="dropdown">
                <a class="icon_btn" role="button" data-toggle="dropdown">
                    <i class="fa fa-cog"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-right">
                    <li><a role="link" href="{url link='user.profile'}">{_p var='edit_profile'}</a></li>
                    {if Phpfox::getUserParam('profile.can_change_cover_photo')}
                        {if empty($aUser.cover_photo)}
                            <li>
                                <a href="#" onclick="$(this).closest('ul').find('.cover_section_menu_item').toggleClass('hidden'); event.cancelBubble = true; if (event.stopPropagation) event.stopPropagation();return false;">
                                    {_p var='add_a_cover'}
                                </a>
                            </li>
                            {if Phpfox::getUserParam('photo.can_view_photos')}
                                <li class="cover_section_menu_item hidden" role="presentation">
                                    <a href="{url link=$aUser.user_name'.photo'}">
                                        {_p var='choose_from_photos'}
                                    </a>
                                </li>
                            {/if}
                            <li class="cover_section_menu_item hidden" role="presentation">
                                <a role="button" id="js_change_cover_photo" onclick="return $Core.CoverPhoto.openUploadImage();">
                                    {_p var='upload_photo'}
                                </a>
                            </li>
                        {else}
                            <li>
                                <a href="#" onclick="$(this).closest('ul').find('.cover_section_menu_item').toggleClass('hidden'); event.cancelBubble = true; if (event.stopPropagation) event.stopPropagation();return false;">
                                    {_p var='change_cover'}
                                </a>
                            </li>
                            {if Phpfox::getUserParam('photo.can_view_photos')}
                                <li class="cover_section_menu_item hidden" role="presentation">
                                    <a href="{url link=$aUser.user_name'.photo'}">
                                        {_p var='choose_from_photos'}
                                    </a>
                                </li>
                            {/if}
                            <li class="cover_section_menu_item hidden" role="presentation">
                                <a role="button" id="js_change_cover_photo" onclick="return $Core.CoverPhoto.openUploadImage();">
                                    {_p var='upload_photo'}
                                </a>
                            </li>
                            <li class="reposition" role="presentation">
                                <a role="button" onclick="$Core.CoverPhoto.reposition.init('user', {$aUser.user_id}); return false;">{_p var='reposition'}</a>
                            </li>
                            <li role="presentation">
                                <a role="button" onclick="$Core.jsConfirm({l}message: oTranslations['are_you_sure_you_want_to_remove_this_cover_photo']{r}, function(){l}$('#cover_section_menu_drop').hide(); $.ajaxCall('user.removeLogo', 'user_id={$aUser.user_id}'); return false;{r}, function(){l}{r})">{_p var='remove_cover_photo'}</a>
                            </li>
                        {/if}
                    {/if}
                </ul>
            </div>
        </div>
    {elseif Phpfox::isAdmin() && !empty($aUser.cover_photo) && Phpfox::getUserParam('profile.can_change_cover_photo')}
        <div class="profiles_owner_actions">
            <div class="dropdown">
                <a class="icon_btn" role="button" data-toggle="dropdown">
                    <i class="fa fa-cog"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-right">
                    <li role="presentation">
                        <a role="button" onclick="$Core.jsConfirm({l}message: oTranslations['are_you_sure_you_want_to_remove_this_cover_photo']{r}, function(){l}$('#cover_section_menu_drop').hide(); $.ajaxCall('user.removeLogo', 'user_id={$aUser.user_id}'); return false;{r}, function(){l}{r})">{_p var='remove_cover_photo'}</a>
                    </li>
                </ul>
            </div>
        </div>
	{/if}

	{if Phpfox::getUserId() != $aUser.user_id}
        <div class="profile_viewer_actions dropdown">
            {if Phpfox::isUser() && Phpfox::isModule('friend') && !$aUser.is_friend && $aUser.is_friend_request !== 2}
                {if $aUser.is_friend_request === 3}
                    <a class="btn btn-success add_as_friend_button" href="#" onclick="return $Core.addAsFriend('{$aUser.user_id}');" title="{_p var='add_to_friends'}">
                        <i class="fa fa-user-plus"></i>
                        <span class="visible-lg-inline-block">{_p var='confirm_friend_request'}</span>
                    </a>
                {elseif empty($aUser.is_ignore_request) && Phpfox::getUserParam('friend.can_add_friends') && Phpfox::getService('user.privacy')->hasAccess('' . $aUser.user_id . '', 'friend.send_request')}
                    <a class="btn btn-success add_as_friend_button" href="#" onclick="return $Core.addAsFriend('{$aUser.user_id}');" title="{_p var='add_to_friends'}">
                        <i class="fa fa-user-plus"></i>
                        <span class="visible-lg-inline-block">{_p var='add_to_friends'}</span>
                    </a>
                {/if}
            {/if}

            {if $bCanSendMessage}
                {if Phpfox::isUser()}
                <a class="btn btn-default" href="#" onclick="$Core.composeMessage({left_curly}user_id: {$aUser.user_id}{right_curly}); return false;">
                {else}
                <a class="btn btn-default" href="#" onclick="tb_show('{_p var='sign_in' phpfox_squote=true}', $.ajaxBox('user.login', 'height=240&amp;width=400')); return false;">
                {/if}
                    <i class="fa fa-envelope"></i>
                    <span class="visible-lg-inline-block">{_p var='send_message'}</span>
                </a>
            {/if}

            {if $bCanPoke}
            <a class="btn btn-default" href="#" id="section_poke" onclick="$Core.box('poke.poke', 400, 'user_id={$aUser.user_id}'); return false;">
                <i class="ico ico-smile-o"></i>
                <span class="visible-lg-inline-block" >{_p var='poke' full_name=''}</span>
            </a>
            {/if}

            {plugin call='profile.template_block_menu_more'}

            {if (Phpfox::getUserBy('profile_page_id') <= 0)
                && ((Phpfox::getUserParam('user.can_block_other_members') && isset($aUser.user_group_id) && Phpfox::getUserGroupParam('' . $aUser.user_group_id . '', 'user.can_be_blocked_by_others'))
                    || (Phpfox::getUserParam('user.can_feature'))
                    || (Phpfox::isAppActive('Core_Activity_Points') && Phpfox::getUserParam('activitypoint.can_gift_activity_points'))
                    || (Phpfox::isModule('friend') && Phpfox::getUserParam('friend.link_to_remove_friend_on_profile') && isset($aUser.is_friend) && $aUser.is_friend === true)
                )
            }
            <a class="btn btn-default" title="{_p var='more'}" data-toggle="dropdown">
                <i class="fa fa-caret-down" aria-hidden="true"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-right">
                {if Phpfox::getUserParam('user.can_block_other_members') && isset($aUser.user_group_id) && Phpfox::getUserGroupParam('' . $aUser.user_group_id . '', 'user.can_be_blocked_by_others')}
                    <li><a href="#?call=user.block&amp;height=120&amp;width=400&amp;user_id={$aUser.user_id}" class="inlinePopup js_block_this_user" title="{if $bIsBlocked}{_p var='unblock_this_user'}{else}{_p var='block_this_user'}{/if}">{if $bIsBlocked}{_p var='unblock_this_user'}{else}{_p var='block_this_user'}{/if}</a></li>
                {/if}
                {if Phpfox::getUserParam('user.can_feature')}
                    <li {if !isset($aUser.is_featured) || (isset($aUser.is_featured) && !$aUser.is_featured)} style="display:none;" {/if} class="user_unfeature_member">
                    <a href="#" title="{_p var='un_feature_this_member'}" onclick="$(this).parent().hide(); $(this).parents('.dropdown-menu').find('.user_feature_member:first').show(); $.ajaxCall('user.feature', 'user_id={$aUser.user_id}&amp;feature=0&amp;type=1'); return false;">{_p var='unfeature'}</a></li>
                    <li {if isset($aUser.is_featured) && $aUser.is_featured} style="display:none;" {/if} class="user_feature_member">
                    <a href="#" title="{_p var='feature_this_member'}" onclick="$(this).parent().hide(); $(this).parents('.dropdown-menu').find('.user_unfeature_member:first').show(); $.ajaxCall('user.feature', 'user_id={$aUser.user_id}&amp;feature=1&amp;type=1'); return false;">{_p var='feature'}</a></li>
                {/if}
                {if Phpfox::isAppActive('Core_Activity_Points') && Phpfox::getUserParam('activitypoint.can_gift_activity_points')}
                    <li>
                        <a href="#?call=core.showGiftPoints&amp;height=120&amp;width=400&amp;user_id={$aUser.user_id}" class="inlinePopup js_gift_points" title="{_p var='gift_points'}">
                            {_p var='gift_points'}
                        </a>
                    </li>
                {/if}
                {if Phpfox::isModule('friend') && Phpfox::getUserParam('friend.link_to_remove_friend_on_profile') && isset($aUser.is_friend) && $aUser.is_friend === true}
                    <li>
                        <a href="#" onclick="$Core.jsConfirm({l}{r}, function(){l}$.ajaxCall('friend.delete', 'friend_user_id={$aUser.user_id}&reload=1');{r}, function(){l}{r}); return false;">
                            {_p var='remove_friend'}
                        </a>
                    </li>
                {/if}
                {if Phpfox::isUser() && $aUser.user_id != Phpfox::getUserId()}
                    <li><a href="#?call=report.add&amp;height=220&amp;width=400&amp;type=user&amp;id={$aUser.user_id}" class="inlinePopup" title="{_p var='report_this_user'}">{_p var='report_this_user'}</a></li>
                {/if}
                {if isset($bShowRssFeedForUser)}
                    <li>
                        <a href="{url link=''$aUser.user_name'.rss'}" class="no_ajax_link">
                            {_p var='subscribe_via_rss'}
                        </a>
                    </li>
                {/if}
                {plugin call='profile.template_block_menu'}
            </ul>
            {/if}
        </div>
	{/if}
</div>
<div class="profiles_menu set_to_fixed" data-class="profile_menu_is_fixed">
	<ul class="" data-component="menu">
		<div class="overlay"></div>
		<li class="profile_menu_image_holder">
			<div class="profile_menu_image">
				{if Phpfox::isAppActive('Core_Photos')}
                    {if isset($aUser.user_name)}
                        <a href="{permalink module='photo.album.profile' id=$aUser.user_id title=$aUser.user_name}">{$sProfileImage}</a>
                    {else}
                        <a href="{permalink module='photo.album.profile' id=$aUser.user_id}">{$sProfileImage}</a>
                    {/if}
				{else}
				    {$sProfileImage}
				{/if}
			</div>
		</li>

		<li class="{if $sModule == ''}active{/if}"><a href="{url link=$aUser.user_name}">{_p var='profile'}</a></li>
        {if Phpfox::getService('user.privacy')->hasAccess($this->_aVars['aUser']['user_id'], 'profile.profile_info')}
		    <li class="{if $sModule == 'info'}active{/if}"><a href="{url link=''$aUser.user_name'.info'}">{_p var='info'}</a></li>
        {/if}
        {if Phpfox::isModule('friend')}
		    <li class="{if $sModule == 'friend'}active{/if}"><a href="{url link=''$aUser.user_name'.friend'}">{_p var='friends'}{if $aUser.total_friend > 0}<span>{$aUser.total_friend}</span>{/if}</a></li>
		{/if}
        {if $aProfileLinks}
            {foreach from=$aProfileLinks item=aProfileLink}
                <li class="{if isset($aProfileLink.is_selected)}active{/if}">
                    <a href="{url link=$aProfileLink.url}" class="ajax_link">{$aProfileLink.phrase}{if isset($aProfileLink.total)}<span class="badge_number">{$aProfileLink.total|number_format}</span>{/if}</a>
                </li>
            {/foreach}
		{/if}
		<li class="dropdown dropdown-overflow hide explorer">
			<a role="button" data-toggle="dropdown" class="explore">
				<i class="fa fa-ellipsis-h"></i>
			</a>
			<ul class="dropdown-menu dropdown-menu-right">
			</ul>
		</li>
	</ul>
</div>
<div class="clear"></div>
<div class="js_cache_check_on_content_block" style="display:none;"></div>
<div class="js_cache_profile_id" style="display:none;">{$aUser.user_id}</div>
<div class="js_cache_profile_user_name" style="display:none;">{if isset($aUser.user_name)}{$aUser.user_name}{/if}</div>

{if Phpfox::getUserParam('profile.can_change_cover_photo') && Phpfox::getUserId() == $aUser.user_id}
    {template file='profile.block.upload-cover-form'}
{/if}