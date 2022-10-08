<?php 
defined('PHPFOX') or exit('NO DICE!');
?>

<div id="js_custom_friend_list">
	<div class="alert alert-danger" style="display:none;" id="js_temp_privacy_error_message">
		{_p var='select_a_custom_friends_list_if_you_want_to_add_privacy_to_your_item'}
	</div>
	{if $iNewListId > 0}
        <div class="message">
            {_p var='custom_friends_list_successfully_created'}
        </div>
	{/if}
	<div id="js_custom_list_actual_holder" class="privacy-custom-popup share-with" {if !count($aLists)}style="display:none"{/if}>
		<form class="form" method="post" action="#" onsubmit="return $Core.Privacy.updateCustomList();">
			<label class="share-with-title">{_p var='share_with'}</label>
            <label id="custom-list-checkbox-template" class="hide share-with-item">
                <input type="checkbox" data-component="custom-list-checkbox" class="mr-1">
                <span></span>
            </label>
			<div data-component="custom-list" class="share-with-list">
                {foreach from=$aLists item=aList}
                    <label class="share-with-item">
                        <input type="checkbox" value="{$aList.list_id}" data-component="custom-list-checkbox" class="mr-1">
                        <span>{$aList.name|clean}</span>
                    </label>
                {/foreach}
			</div>
            <div class="fz-12">
                <a role="button" onclick="$Core.Privacy.showCreateListForm()">{_p var='create_new_list_no_dots'}</a>
                <span class="text-gray-dark">{_p var='or'}</span>
                <a href="{url link='friend'}">{_p var='manage_lists'}</a>
            </div>
			<div class="privacy-custom-button-wapper mt-2">
                <input type="submit" class="btn btn-primary mr-1" value="{_p var='save'}" /><button role="button" class="btn btn-default" onclick="js_box_remove(this)">{_p var='cancel'}</button>
			</div>
		</form>
	</div>

    <div id="custom-list-empty" class="privacy-custom-popup no-friend" {if count($aLists)}style="display:none"{/if}>
        <div class="text-center">
            <span class="ico ico-user2-three-o no-friend-icon"></span>
            <div class="mt-2">
                <span class="no-friend-text">{_p var='no_friends_list_found'}</span>
                <a role="button" onclick="$Core.Privacy.showCreateListForm()">{_p var='create_new_list_no_dots'}</a>
            </div>
        </div>
        <div class="privacy-custom-button-wapper mt-2">
            <button class="btn btn-default" role="button" onclick="js_box_remove(this)">{_p var='cancel'}</button>
        </div>
    </div>

	<div id="js_create_custom_friend_list_holder" style="display:none;">
		<div id="js_create_custom_friend_list" class="privacy-custom-popup friend-list">
			<div>
                <div class="alert alert-danger" id="js_friend_list_add_error" style="display: none;"></div>
				<form class="form" method="post" action="#" onsubmit="$Core.Privacy.addList(this, event);">
					<div class="form-group">
                        <label for="js_add_new_list">{_p var='list_name'}</label>
						<input type="text" name="name" class="form-control" placeholder="{_p var='enter_friends_list_name'}" maxlength="255" size="15" id="js_add_new_list" required/>
                    </div>
                    <div class="form-group">
                        <label>{_p var='add_friends'}</label>
                        {module name='friend.search-small' input_name='friends'}
                    </div>
					<div class="privacy-custom-button-wapper">
                        <input type="submit" value="{_p var='create'}" class="btn btn-primary mr-1" /><button role="button" class="btn btn-default" onclick="$Core.Privacy.hideCreateListForm()">{_p var='back'}</button>
                    </div>
				</form>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
  {if empty($sCustomPrivacyId)}
    $Core.Privacy.sCustomPrivacySelector = '#js_custom_privacy_input_holder';
  {else}
    $Core.Privacy.sCustomPrivacySelector = '#{$sCustomPrivacyId}';
  {/if}

    $Core.Privacy.sWrapperSelector = '#js_custom_friend_list';
    $Core.Privacy.sPrivacyArray = '{$sPrivacyArray}';
    $Core.Privacy.phrases.custom_privacy = '{$sPhraseCustomPrivacy}';
    $Core.Privacy.phrases.create_friends_list = '{$sPhraseCreateFriendsList}';
    $Core.Privacy.populateCustomListCheckbox();
</script>