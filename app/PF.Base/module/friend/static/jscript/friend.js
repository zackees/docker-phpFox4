$Behavior.manageFriends = function () {
    $('.friend_list_change_order').click(function () {
        if ($('.js_friend_edit_order_submit').hasClass('is_active')) {
            $('.js_friend_edit_order').hide();
            $('.js_friend_edit_order_submit').removeClass('is_active');
            $('.friend_action_holder').show();
            $('.js_friend_sort_handler').hide();
        }
        else {
            $('.js_friend_edit_order').show();
            $('.js_friend_edit_order_submit').addClass('is_active');
            $('.friend_action_holder').hide();
            $('.js_friend_sort_handler').show();

            $('#js_friend_sort_holder').sortable({
                items: '.friend_row_holder',
                opacity: 0.4,
                cursor: 'move',
                helper: 'clone',
                handle: '.js_friend_sort_handler',
            });
        }
        return false;
    });

    $('.friend_action_delete,.friend_action_remove').unbind().click(function () {
        var id = $(this).attr('rel');
        $Core.jsConfirm({}, function () {
            $.ajaxCall('friend.delete', 'test=1&id=' + id);
        }, function () {
        });
        return false;
    });

    $('#js_friend_list_order_form').submit(function () {
        $Core.processForm(this);
        $(this).ajaxCall('friend.updateListOrder');
        return false;
    });

    $('.friend_list_display_profile').click(function () {
        $.ajaxCall('friend.setProfileList', 'list_id=' + $(this).attr('rel') + '&type=add', 'GET');
        return false;
    });

    $('.friend_list_remove_profile').click(function () {
        $.ajaxCall('friend.setProfileList', 'list_id=' + $(this).attr('rel') + '&type=remove', 'GET');
        return false;
    });


    $('.js_core_menu_friend_add_list').click(function () {

        $Core.box('friend.addNewList', 400);

        return false;
    });

    $('.js_friend_list_edit_name').click(function () {
        var id = $(this).attr('rel');
        $Core.box('friend.editName', 400, 'id=' + id);

        return false;
    });

    $('[data-dropdown-type="friend_action"] li.add_to_list:not(.divider) a').off('click').on('click', function () {
        var sRel = $(this).attr('rel');
        var sType = '';
        var aParts = explode('|', sRel);

        if ($(this).hasClass('active')) {
            $(this).removeClass('active');
            sType = 'remove';
            if ($(this).hasClass('selected')) {
                $(this).closest('.friend_row_holder').remove();
            }
        }
        else {
            $(this).addClass('active');
            sType = 'add';
        }
        $.ajaxCall('friend.manageList', 'list_id=' + aParts[0] + '&friend_id=' + aParts[1] + '&type=' + sType, 'GET');
        return false;
    });

    friend_AddFriendsToList.init();
}

var friend_AddFriendsToList = {
    init: function () {
        $('.js_core_menu_add_friend_to_list').off('click').on('click', function () {
            var list_id = $('.js_friend_list_edit_name').attr('rel');
            tb_show(oTranslations['menu_add_friend_to_list'], $.ajaxBox('friend.addFriendToList', 'width=600&list_id=' + list_id));
            return false;
        });

        $('#js_add_friend_to_list_btn').off('click').on('click', function () {
            if ($('#js_selected_friends').length) {
                var sUserIds = '';
                $('#js_selected_friends .js_cached_friend_name input[name="val[invite][]"]').each(function () {
                    sUserIds += $(this).val() + ',';
                })
                sUserIds = !empty(sUserIds) ? trim(sUserIds, ',') : '';
                $.ajaxCall('friend.executeAddFriendToList', 'list_id=' + $('#js_friend_list_id').val() + '&user_id_list=' + sUserIds);
            }
        });
    },
    initMembers: function (aMembers) {
        if (!empty(aMembers) && Array.isArray(aMembers)) {
            $Core.searchFriend.showOverflow = true;
            $(aMembers).each(function (index, value) {
                var oObject = $("#js_friends_checkbox_" + value);
                oObject.prop("checked", true);
                oObject.closest('.item-outer').addClass('active');
                $Core.searchFriend.addFriendToSelectList($("#js_friends_checkbox_" + value), value, $("#js_friends_checkbox_" + value).prop('checked'));
            });
        }
    }
}