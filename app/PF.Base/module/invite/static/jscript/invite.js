$Behavior.initEmailInviteWithTokenField = function() {
  setTimeout(function() {
    if ($('#js_invite_form').length) {
      let tokenfields = $('#js_invite_form').find('input[data-component="tokenfield"]');
      if (tokenfields.length) {
        tokenfields.each(function() {
          let _this = $(this);
          if (_this.closest('.tokenfield').length) {
            _this.closest('.tokenfield').find('.token-input').on('blur', function() {
              let tokenInput = $(this),
                  currentText = tokenInput.val();
              if (typeof currentText === "string" && currentText !== '') {
                _this.tokenfield('createToken', currentText);
                tokenInput.val('');
              }
            });
          }
        });
      }
    }
  }, 500);
  $Behavior.initEmailInviteWithTokenField = null;
}