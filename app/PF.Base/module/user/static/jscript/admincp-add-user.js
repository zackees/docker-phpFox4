$Core.initAdminCPAddUser = function() {
  if ($('.user-admincp-add').length) {
    if ($('#phone_number').length && typeof intlTelInput === "function" && $('#phone_number').val()) {
      setTimeout(function () {
        $('#phone_number').trigger('change');
      }, 1000);
    }
  }
}

PF.event.on('on_page_change_end', function() {
  $Core.initAdminCPAddUser();
});

PF.event.on('on_document_ready_end', function() {
  $Core.initAdminCPAddUser();
});