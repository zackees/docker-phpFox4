$Core.FeedSchedule = {
    inputConfirmSchedule: '[name="val[confirm_scheduled]"]',

    init: function () {
        var btnSchedule = $('.js_btn_display_with_schedule');
        var btnConfirmSchedule = $('.btn_confirm_schedule');
        var btnClearSchedule = $('.btn_clear_schedule');

        btnSchedule.off('click').on('click',function () {
            var findDiv = $(this).closest('form'),
                parentsDiv = findDiv.find('.js_feed_compose_schedule'),
                visible = parentsDiv.is(":visible");
            if (visible) {
                $(this).removeClass('is_active');
                parentsDiv.hide('fast');
            } else {
                $('.js_btn_display_with_friend').removeClass('is_active');
                $(this).addClass('is_active');
                $('.feed_compose_extra').hide('fast');
                findDiv.find('#js_location_input').hide('fast');
                parentsDiv.show('fast');
                parentsDiv.find('.js_input_tagging').focus().trigger('click');
            }
            return false;
        });

        btnConfirmSchedule.off('click').on('click',function () {
            var form = $(this).closest('form'),
                schedule_hour = form.find('[name="val[schedule_hour]"]').val(),
                schedule_minute = form.find('[name="val[schedule_minute]"]').val(),
                schedule_year = form.find('[name="val[schedule_year]"]').val(),
                schedule_month = form.find('[name="val[schedule_month]"]').val(),
                schedule_day = form.find('[name="val[schedule_day]"]').val(),
                isEdit = $(this).data('is_edit');

            form.find($Core.FeedSchedule.inputConfirmSchedule).val('1').trigger('change');

            form.find('.val_schedule_time_year').val(schedule_year).trigger('change');
            form.find('.val_schedule_time_month').val(schedule_month).trigger('change');
            form.find('.val_schedule_time_day').val(schedule_day).trigger('change');
            form.find('.val_schedule_time_hour').val(schedule_hour).trigger('change');
            form.find('.val_schedule_time_minute').val(schedule_minute).trigger('change');

            if (parseInt(form.find($Core.FeedSchedule.inputConfirmSchedule).val()) === 1) {
                if (!isEdit) {
                    form.find('.js_btn_clear_schedule_wrapper').css('display', 'block');
                    form.find('.js_btn_confirm_schedule_wrapper').hide();
                }
                form.find('.js_schedule_review')
                    .html(oTranslations['will_send_on_time'].replace('{time}', schedule_month + '/' + schedule_day + '/' + schedule_year + ' - ' + schedule_hour + ':' + schedule_minute));
                form.find('.js_btn_display_with_schedule').trigger('click');
            }
        });

        btnClearSchedule.off('click').on('click', function () {
            var form = $(this).closest('form');
            form.find($Core.FeedSchedule.inputConfirmSchedule).val('0').trigger('change');
            if (parseInt($($Core.FeedSchedule.inputConfirmSchedule).val()) === 0) {
                form.find('.js_btn_clear_schedule_wrapper').css('display', 'none');
                form.find('.js_btn_confirm_schedule_wrapper').show();
                form.find('.js_schedule_review').html('');
            }
        });

        $('[name="val[schedule_month]"], [name="val[schedule_hour]"], [name="val[schedule_minute]"]').off('change').on('change', function () {
            var ele = $(this);
            setTimeout(function() {
                var form = ele.closest('form'),
                    month = form.find('[name="val[schedule_month]"]').val(),
                    day = form.find('[name="val[schedule_day]"]').val(),
                    year = form.find('[name="val[schedule_year]"]').val(),
                    hour = form.find('[name="val[schedule_hour]"]').val(),
                    minute = form.find('[name="val[schedule_minute]"]').val();
                try {
                    form.find('.js_btn_clear_schedule_wrapper').hide();
                    $Core.ajax('core.validateScheduleTime', {
                        type: 'POST',
                        params: {
                            'hour': hour,
                            'minute': minute,
                            'day': day,
                            'month': month,
                            'year': year
                        },
                        success: function (sOutput) {
                            var data = JSON.parse(sOutput);
                            if (data.error) {
                                form.find('.js_schedule_invalid_time').show();
                                form.find('.js_btn_confirm_schedule_wrapper').hide();
                                form.find($Core.FeedSchedule.inputConfirmSchedule).val('0');
                            } else {
                                form.find('.js_schedule_invalid_time').hide();
                                form.find('.js_btn_confirm_schedule_wrapper').show();
                            }
                        }
                    });
                } catch (e) {

                }
            }, 200);
        });
    },

    emptyScheduleForm: function () {
        $('.js_feed_compose_schedule').hide();
        $('#js_activity_feed_form .js_btn_display_with_schedule').removeClass('is_active');
        $('.js_schedule_review').html('').removeClass('hide');
        $('.js_tagged_review').removeClass('hide');
        $.ajaxCall('feed.resetScheduleForm', $.param({
            'id': '#js_activity_feed_form .js_feed_schedule_container'
        }));
    },

    deleteScheduleItem: function (scheduleId, ele, success) {
        if (success) {
            $("#js_schedule_item_holder_" + scheduleId).remove();
            if (!$('#js_manage_schedule_items_container').find('.js_schedule_item').length) {
                $('#js_no_schedule_item').removeClass('hide');
            }
        } else {
            var oEle = ele ? $(ele) : {};
            $Core.jsConfirm( {message: oEle.data('message') || getPhrase('are_you_sure')}, function() {
                $('#js_schedule_item_' + scheduleId).prepend('<div class="loading-overlay"><i class="fa fa-spin fa-circle-o-notch"></i></div>');
                $.ajaxCall('core.deleteScheduleItem', $.param({
                    height: 400,
                    width: 600,
                    id: scheduleId
                }));
            }, function() {});
        }
        return true;
    },
    sendNowScheduleItem: function (scheduleId, ele, success) {
        if (success) {
            $("#js_schedule_item_holder_" + scheduleId).remove();
            if (!$('#js_manage_schedule_items_container').find('.js_schedule_item').length) {
                $('#js_no_schedule_item').removeClass('hide');
            }
        } else {
            var oEle = ele ? $(ele) : {};
            $Core.jsConfirm( {message: oEle.data('message') || getPhrase('are_you_sure')}, function() {
                $('#js_schedule_item_' + scheduleId).prepend('<div class="loading-overlay"><i class="fa fa-spin fa-circle-o-notch"></i></div>');
                $.ajaxCall('core.sendNowScheduleItem', $.param({
                    height: 400,
                    width: 600,
                    id: scheduleId
                }));
            }, function() {});
        }
    }
}