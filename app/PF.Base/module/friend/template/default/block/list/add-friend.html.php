<?php
defined('PHPFOX') or exit('NO DICE!');
?>
<form>
    <div class="js_add_friend_to_list_block">
        <input type="hidden" id="js_friend_list_id" value="{$list_id}">
        <div class="form-group">
            <div id="js_selected_friends" class="hide_it"></div>
            <input type="hidden" name="selected_friends" class="close_warning" id="js_selected_friends_for_warning" value="{$sSelectedIds}">
            {module name='friend.search' input='invite' hide=true in_form=true selected_friends=$aSelectedIds}
        </div>
        <div class="form-group">
            <button id="js_add_friend_to_list_btn" class="btn btn-primary">{_p var='save'}</button>
        </div>
    </div>
</form>

{literal}
<script type="text/javascript">
    if ($Core.searchFriend.initialized) {
      friend_AddFriendsToList.initMembers({/literal}{$aFriendListMembers}{literal});
    }
    PF.event.on('on_search_friend_init_completed', function () {
        friend_AddFriendsToList.initMembers({/literal}{$aFriendListMembers}{literal});
    })
    plugin_addFriendToSelectList = function (id) {
        var aUserIds = [id.toString()];
        $('#js_selected_friends .js_cached_friend_name input[name="val[invite][]"]').each(function () {
          aUserIds.push($(this).val());
        })
        aUserIds.sort();
        $('#js_selected_friends_for_warning').val(aUserIds.join(',')).trigger('change');
    }
    plugin_removeFriendToSelectList = function (id) {
      var aUserIds = [];
      $('#js_selected_friends .js_cached_friend_name input[name="val[invite][]"]').each(function () {
        if ($(this).val() !== id) {
          aUserIds.push($(this).val());
        }
      })
      aUserIds.sort();
      $('#js_selected_friends_for_warning').val(aUserIds.join(',')).trigger('change');
    }
</script>
{/literal}
