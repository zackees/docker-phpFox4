var $Core_Storage_System = {
  initTransferFiles: function () {
    var transferEle = $('#js_storage_transfer_file_form');
    if (!transferEle.length) {
      return false;
    }
    var removeLocal = transferEle.find('input[name="val[remove_file]"]');
    removeLocal.on('change', function () {
      var checked = $(this).prop('checked'), updateDB = transferEle.find('#js_storage_update_database');
      if (checked) {
          updateDB.addClass('hide');
      } else {
        updateDB.removeClass('hide');
      }
    });
  }
}

PF.event.on('on_document_ready_end', function () {
  $Core_Storage_System.initTransferFiles();
});

PF.event.on('on_page_change_end', function () {
  $Core_Storage_System.initTransferFiles();
});