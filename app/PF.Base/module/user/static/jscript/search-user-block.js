$Behavior.search_user_block = function () {
    var wrap_search = $('.js_wrap_search_block_users');
    if (wrap_search.length) {
        var input_search = wrap_search.find('input[type="text"]'),
            btn_search = wrap_search.find('input[type="button"]'),
            ele_search_error = wrap_search.find('.js_search_error'),
            min_characters = 3;

        input_search.on('keyup keydown', function (event) {
            var keyCode = event.keyCode || event.which;
            if (keyCode === 13) {
                btn_search.trigger('click');
                return false;
            }
        });

        btn_search.on('click', function () {
            ele_search_error.addClass('hide');
            if (input_search.val().length >= min_characters) {
                tb_show(oTranslations['block_people'], $.ajaxBox('user.getUsersToBlock', $.param({
                    height: 400,
                    width: 500,
                    query_search: input_search.val()
                })));
            } else {
                ele_search_error.html(oTranslations['please_try_to_search_with_at_latest_min_characters'].replace('{min}', min_characters)).removeClass('hide');
            }
        });
    }
}