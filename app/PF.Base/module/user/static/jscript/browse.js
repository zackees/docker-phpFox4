$Core.isIntUserWidth = function (input) {
    return typeof(input) == 'number' && parseInt(input) == input;
}

$Behavior.setUserWidth = function () {
    var bShow = false;
    if ($('#right').length >= 1) {
        var sInnerHtml = trim($('#right').html());
        if (empty(sInnerHtml)) {
            bShow = true;
        }
    }

    if ($('#right').length <= 0 || bShow) {
        var iCnt = 0;
        $('.js_parent_user').each(function () {
            iCnt++;
            if ($Core.isIntUserWidth(iCnt / 7)) {
                $(this).after('<div class="clear"></div>');
            }
        });
    }
}

var advSearchUserBrowse = {
    enableAdvSearch: function () {
        var oAdvSearchWrapper = $('#js_user_browse_adv_search_wrapper');
        if ($('#form_main_search').find('#js_user_browse_adv_search_wrapper').length == 0 || $('#form_main_search').find('#js_user_browse_adv_search_wrapper').hasClass('init')) {
            oAdvSearchWrapper.detach().insertBefore('#js_search_input_holder');
            $('#js_user_browse_enable_adv_search_btn').addClass('active');
            oAdvSearchWrapper.slideDown();
            if (oAdvSearchWrapper.hasClass('init')) {
                oAdvSearchWrapper.removeClass('init');
            }
        }
        else {
            oAdvSearchWrapper.slideUp();
            $('#js_user_browse_enable_adv_search_btn').removeClass('active');
            oAdvSearchWrapper.detach().insertAfter('#form_main_search');
        }
    },
    resetForm: function () {
        if ($('#js_user_browse_adv_search_wrapper').length) {
            $('#js_adv_search_user_browse_from, #js_adv_search_user_browse_to, #country_iso').selectize().each(function () {
                this.selectize.setValue('');
            });
            $('input[type="search"][name="search[search]"], input[type="text"][name="search[city]"], input[type="text"][name="search[zip]"], #js_adv_search_about_me').val('');
            $('input[name="search[gender]"]').prop('checked', false);
            $('input[name="search[gender]"][value=""]').prop('checked', true);
            $('select[name="search[sort]"]').val('u.full_name');
            $('.js_custom_search:not(.selectize-control, .selectize-dropdown)').each(function () {
                var type = this.type || this.tagName.toLowerCase();
                if (type === 'select-one' || type === 'select-multiple') {
                    $(this).selectize().each(function () {
                        this.selectize.setValue('');
                    });
                }
                else if (type === 'radio' || type === 'checkbox') {
                    $(this).prop('checked', false);
                }
                else {
                    $(this).val('');
                }
            });
            $('.js_date_picker').each(function () {
                $.datepicker._clearDate(this);
            });
        }
    }
}

$Behavior.user_browse_advanced_search = function () {
    if ($('#page_user_browse').length) {
        var oAdvancedSearch = $('#js_search_user_browse_content');
        if (oAdvancedSearch.length) {
            if ($('#form_main_search') && $('#form_main_search').find('#js_search_user_browse_wrapper').length == 0) {
                oAdvancedSearch.insertBefore('#form_main_search .header_bar_search_inner .input-group .form-control-feedback');
                setTimeout(function () {
                    $('#js_search_user_browse_content').removeClass('hide');
                }, 100);
            }
        }
    }
}


PF.event.on('on_page_column_init_end', function () {
    if ($('#page_user_browse').length) {
        $('#form_main_search .hidden input[type="hidden"]').each(function () {
            if (this.name !== 's') {
                this.remove();
            }
        });
    }
});