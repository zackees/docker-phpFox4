$Core.invite = {
    iEnabled: 0,
    localSelector: function (sValue) {
        $('.checkbox').each(function () {
            if (sValue == "none") {
                $(this).attr('checked', false);
                $('#js_action_selector').attr('disabled', 'disabled');
            }
            if (sValue == "all") {
                $(this).attr('checked', true);
                $('#js_action_selector').attr('disabled', '');
            }
        });
    },

    enableDelete: function (oObj) {
        if ($(oObj).attr('checked') == true) {
            $('#js_action_selector').attr('disabled', '');
            $Core.invite.iEnabled++;
        } else {
            $Core.invite.iEnabled--;
            if ($Core.invite.iEnabled < 1) {
                $('#js_action_selector').attr('disabled', 'disabled');
            }
        }
    },

    doAction: function (sAction) {
        if (sAction == "delete") {
            $('#js_form').submit();
        }
        return true;
    },
    action: {
        delete: function (ele, message) {
            var oEle = $(ele), id = oEle.data('id'), _this = this;
            if (!id) {
                return false;
            }
            $Core.jsConfirm({message: oEle.data('message') || message || getPhrase('are_you_sure')}, function () {
                $.ajax({
                    type: 'GET',
                    url: PF.url.make('invite/invitations'),
                    data: {
                        'del': id,
                        'ajax_delete': true
                    },
                    beforeSend: function () {
                        _this.toggleLoading(id);
                    },
                    error: function () {
                        window.parent.sCustomMessageString = getPhrase('invitation_not_found');
                        tb_show(getPhrase('error'), $.ajaxBox('core.message', 'height=150&width=300'));
                        setTimeout('tb_remove();', 2000);
                        _this.toggleLoading(id, true);
                    },
                    success: function (data) {
                        var title = getPhrase('notice');
                        if (!data.success) {
                            title = getPhrase('error');
                            _this.toggleLoading(id, true);
                        }
                        _this.updateView(id);
                        window.parent.sCustomMessageString = $Core.htmlEntityEncode(data.message);
                        tb_show(title, $.ajaxBox('core.message', 'height=150&width=300'));
                        setTimeout('tb_remove();', 2000);
                    }
                })
            });
        },
        updateView: function (id) {
            var oEle = $('#js_invite_' + id);
            if (!oEle.length) {
                return false;
            }
            if ($('.invitation-container .invitation-item').length === 1) {
                oEle.remove();
                var viewMore = $('.js_pager_view_more_link');
                if (viewMore.length) {
                    if (viewMore.hasClass('show_load_more')) {
                        viewMore.find('a').trigger('click');
                    } else {
                        $(window).trigger('scroll');
                    }
                }
            } else {
                oEle.remove();
            }
            //Update counter
            $('.js-invitation-count').each(function (index, element) {
                $(this).text(index + 1);
            })
            return true;
        },
        initModerator: function () {
            $('#page_invite_invitations [data-cmd][rel="invite.moderation"]').on('mouseup keyup', function (e) {
                var oChecked = $('.js_global_item_moderate:checked');
                if (!oChecked.length || e.keyCode === $.ui.keyCode.ESCAPE || e.key === 'Escape') {
                    return false;
                }
                if ($(this).attr('href') === '#delete') {
                    setTimeout(function () {
                        $('#js-confirm-popup-wrapper').find('.js_box').on('dialogclose', function () {
                            setTimeout(function () {
                                if ($('.moderation_process').css('display') !== 'none') {
                                    oChecked.each(function () {
                                        $Core.invite.action.toggleLoading($(this).val());
                                    });
                                }
                            }, 100);
                        })
                    }, 100);
                } else {
                    oChecked.each(function () {
                        $Core.invite.action.toggleLoading($(this).val());
                    });
                }
            });
        },
        toggleLoading: function (id, remove) {
            var oEle = $('#js_invite_' + id);
            if (!oEle.length) {
                return false;
            }
            if (!remove) {
                oEle.prepend('<div class="loading-overlay"><i class="fa fa-spin fa-circle-o-notch"></i></div>');
            } else {
                oEle.find('.loading-overlay').remove();
            }
        }
    }
}

$Ready(function () {
    $Core.invite.action.initModerator();
})

