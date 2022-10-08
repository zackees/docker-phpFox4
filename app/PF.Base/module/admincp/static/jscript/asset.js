var $Core_Assets = {
    reinitTransferProgress: function() {
        let container = $('#js_core_assets_tranfer_file');
        if (container.length && container.data('transfered')) {
            let intervalTime = 5000,
                totalFileNeedToTransfer = 0,
                progressContainer = container.find('#js_core_assets_transfer_file_progress'),
                successContainer = container.find('#js_core_assets_transfer_file_success');
            let intervalId = setInterval(function() {
                $Core.ajax('admincp.setting.getTransferAssetFileProgress', {
                    type: 'POST',
                    params: {
                        total: totalFileNeedToTransfer,
                    },
                    success: function(response) {
                        let output = $.parseJSON(response),
                            acceptableStatuses = ['completed', 'in_process'],
                            canClearInterval = true;
                        if (output.hasOwnProperty('status') && acceptableStatuses.includes(output.status)) {
                            let progressBar = container.find('#progress_bar'),
                                successNumber = container.find('#total_success'),
                                totalNumber = container.find('#total_transfered');
                            progressBar.css('width', parseInt(output.percentage) + '%');
                            successNumber.html(output.transfered);
                            totalNumber.html(output.total);
                            totalFileNeedToTransfer = parseInt(output.total);
                            canClearInterval = output.status !== 'in_process';
                        }

                        if (canClearInterval) {
                            clearInterval(intervalId);
                            if (output.status === 'completed') {
                                if (progressContainer.length) {
                                    progressContainer.hide();
                                }
                                if (successContainer.length) {
                                    successContainer.show();
                                }
                            }
                        }
                    },
                    error: function () {
                        clearInterval(intervalId);
                    }
                })
            }, intervalTime);
        }
    }
}

PF.event.on('on_document_ready_end', function () {
    $Core_Assets.reinitTransferProgress();
});

PF.event.on('on_page_change_end', function () {
    $Core_Assets.reinitTransferProgress();
});