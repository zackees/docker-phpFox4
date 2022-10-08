<?php
defined('PHPFOX') or exit('NO DICE!');
?>

<div class="search-friend-component">
    {if $inputType == 'single'}
    <input type="hidden" name="{$inputName}" id="search_member_single_input" {if !empty($userIds)}value="{$userIds}"{/if}>
    {/if}
    <span id="js_custom_search_friend_placement">
        {if !empty($currentValues)}
            {foreach from=$currentValues item=currentValue}
            <span id="js_friend_search_row_{$currentValue.user_id}" class="item-user-selected js_selected">
                <span class="item-name">{$currentValue.full_name}</span>
                <a role="button" class="friend_search_remove" data-search-id="js_custom_search_friend" title="{_p var='remove'}"
                   onclick="$Core.searchMembersInput.removeSelected(this, {$currentValue.user_id});  return false;">
                    <i class="ico ico-close"></i>
                </a>
                {if $inputType == 'multiple'}
                    <input class="js_selected_id" type="hidden" name="{$inputName}[]" value="{$currentValue.user_id}">
                {/if}
            </span>
            {/foreach}
        {/if}
    </span>
    <span id="js_custom_search_friend"></span>
</div>
<script type="text/javascript">
  $Core.searchMembersParams = {l}
    'id': '#js_custom_search_friend',
    'ajax_build': '{$ajaxBuild}',
    'item_id': '{$targetItemId}',
    'placement': '#js_custom_search_friend_placement',
    'width': '100%',
    'max_search': 10,
    'input_name': '{$inputName}',
    'default_value': '{$inputPlaceholder}',
    'input_type': '{$inputType}',
    'single_input': '#search_member_single_input',
    'include_current_user' : {if !empty($includeCurrentUser)}{$includeCurrentUser}{else}false{/if}
  {r};
</script>