$Core.UserAdmincp = {
  deleteUser: function (obj) {
    let _this = $(obj),
      id = _this.data('id'),
      text = _this.text(),
      textWidth = _this.width(),
      textHeight = _this.height()

    if (id) {
      _this.prop('disabled', true)
      _this.html('<span class="js_box_loader" style="width: ' + textWidth +
        'px; height: ' + textHeight +
        'px;"><i class="fa fa-spin fa-spinner"></i></span>')
      $.fn.ajaxCall('user.confirmedDelete', 'iUser=' + id, null, null,
        function () {
          _this.prop('disabled', false)
          _this.text(text)
        })
    }

    return false
  },
  deleteCancelOptions: function (obj) {
    let _this = $(obj),
      btn = $(
        '#table_hover_action_holder .js_admincp_cancellation_option_delete_btn'),
      btnText = btn.length ? btn.text() : null,
      textWidth = btn.length ? btn.width() : null,
      textHeight = btn.length ? btn.height() : null

    if (_this.length) {
      if (btn.length) {
        btn.prop('disabled', true)
        btn.html('<span class="js_box_loader" style="width: ' + textWidth +
          'px; height: ' + textHeight +
          'px; display: flex; justify-content: center;"><i class="fa fa-spin fa-spinner" style="margin-right: 0; font-size: 18px;"></i></span>')
      }
      $.fn.ajaxCall('user.deleteMultipleCancelOptions', _this.serialize(), null,
        null, function () {
          if (btn.length) {
            btn.prop('disabled', false)
            btn.text(btnText)
          }
        })
    }

    return false
  }
}