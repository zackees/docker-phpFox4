var $Core_Logger = {
    initLogViewer: function() {
        let parent = $('#js_admincp_logger_view');
        if (parent.length) {
            let chooseService = parent.find('#js_choose_log_service'),
                chooseChannel = parent.find('#js_choose_log_channel');

            chooseService.off().on('change', function() {
               let service = $(this).val();
               $Core.ajax('admincp.setting.getLogChannels', {
                  type: 'POST',
                  params: {
                      service: service,
                  }, success: function(response) {
                      let output = $.parseJSON(response),
                        channelSelectize = (chooseChannel.selectize())[0].selectize;
                       channelSelectize.clear();
                       channelSelectize.clearOptions();
                      if (isset(output.channels)) {
                          $.each(output.channels, function(index, value) {
                              channelSelectize.addOption(value);
                          });
                          channelSelectize.setValue(output.default_channel);
                      }
                   }
               });
            });
        }
    }
}