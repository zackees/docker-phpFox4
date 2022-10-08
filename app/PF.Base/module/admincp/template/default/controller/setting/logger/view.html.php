<?php
defined('PHPFOX') or exit('NO DICE!');
?>
<div class="p-admincp-logger-view dont-unbind-children" id="js_admincp_logger_view">
    <div class="panel panel-default">
        <div class="panel-body">
            <div class="col-sm-12">
                <form action="{url link='admincp.setting.logger.view'}" method="GET">
                    <div class="form-group js_core_init_selectize_form_group col-sm-6">
                        <label>{_p var='choose_service'}</label>
                        <select class="form-control" name="service" id="js_choose_log_service">
                            {foreach from=$supportedServices item=supportedService}
                                <option value="{$supportedService.value}" {if $supportedService.value == $selectedService}selected="true"{/if}>{$supportedService.title}</option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="form-group js_core_init_selectize_form_group col-sm-6">
                        <label>{_p var='choose_channel'}</label>
                        <select class="form-control" name="channel" id="js_choose_log_channel">
                            {foreach from=$supportedChannels item=supportedChannel}
                                <option value="{$supportedChannel}" {if $supportedChannel == $selectedChannel}selected="true"{/if}>{$supportedChannel}</option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="form-group" style="padding-left: 15px;">
                        <button type="submit" class="btn btn-primary">{_p var='view'}</button>
                        {if !empty($selectedChannel)}
                            <button class="btn btn-default" style="margin-left: 8px;" onclick="return deleteChannel(this);">{_p var='delete_this_channel'}</button>
                        {/if}
                    </div>
                </form>
            </div>
        </div>
    </div>

    {if empty($logItems)}
    <div class="alert alert-danger">{_p var='no_logs_found'}</div>
    {else}
    <div class="panel panel-default">
        <div class="table-responsive">
            <table class="table table-admin">
                <thead>
                <tr>
                    <th class="w160">{_p var='date_time'}</th>
                    <th class="w100">{_p var='level'}</th>
                    <th>{_p var='message'}</th>
                </tr>
                </thead>
                <tbody>
                {foreach from=$logItems item=logItem}
                <tr>
                    <td>{$logItem.datetime}</td>
                    <td>{$logItem.level}</td>
                    <td>{$logItem.message}</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    </div>
    {pager}
    {/if}
</div>

{literal}
<script type="text/javascript">
    function deleteChannel(obj) {
      let _this = $(obj),
        _form = _this.closest('form');
      if (_form.length) {
        let serviceEle = _form.find('#js_choose_log_service'),
          channelEle = _form.find('#js_choose_log_channel');
        if (serviceEle.length && channelEle.length && serviceEle.val() && channelEle.val()) {
          _form.find('button').prop('disabled', true);
          $.fn.ajaxCall('admincp.setting.deleteChannel', $.param({
            service: serviceEle.val(),
            channel: channelEle.val(),
          }), null, null, function() {
            _form.find('button').prop('disabled', false);
          });
        }
      }

      return false;
    }

    $Behavior.initLogViewer = function() {
        $Core_Logger.initLogViewer();
    }
</script>
{/literal}
