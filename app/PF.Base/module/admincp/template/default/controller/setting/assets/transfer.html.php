<?php
defined('PHPFOX') or exit('NO DICE!');
?>
{if $aTransferFileData}
    {literal}
        <script>
          $Ready(function(){
            {/literal}
              var aTransferFileData = {$aTransferFileData};
              var iTotalFile   = {$iTotalFile};
              var iUploaded    = 0;

              {literal};
              if(window.transferAssets){
                return ;
              }

              window.transferAssets =  true;

              function completed(){
                window.location.href = PF.url.make('/admincp/setting/assets/transfer', {'transfer_direct_done': 1});
              }


              // how to pair with many or single threads.
              function send(){
                var $data = aTransferFileData.shift();

                if(!$data){
                  return ;
                }
                var files  = $data.files;
                var storage_id = $data.storage_id;
                $.each(files,function(i,x){
                  $('li[data-id="'+x+'"]').html("<b>Transferring: " + x  + '</b>');
                });

                function successful(){
                  $.each(files,function(i,x){
                    $('li[data-id="'+x+'"]').html("<i>Transferred: " + x  + '</i>');
                  });
                  iUploaded += files.length;
                  $('#total_success').html(iUploaded);
                  $('#progress_bar').css({width: (iUploaded/iTotalFile*100)+'%'})
                  if(iUploaded == iTotalFile){
                    completed();
                  }
                }

                $Core.ajax('admincp.setting.transferAssets',{
                  type: 'POST',
                  params: {
                    files: files.join(';'),
                    storage_id: storage_id,
                  },
                  success: function(e) {
                    send();
                    successful();

                  }
                });
              }
              send();
              send();
              send();
              send();
            });
        </script>
    {/literal}
{/if}
<div class="core-assets-transfer-file" id="js_core_assets_tranfer_file" data-transfered="{if $bIsTransferred}1{/if}">
    {if count($aItems) > 0}
        <form method="post" class="form" action="{url link='current'}" id="js_form">
            <p>{_p var='assets_transfer_description'}</p>
            <div id="client_details" class="panel panel-default">
                <div class="panel-body">
                    <div>
                        <div class="form-group">
                            {template file='admincp.block.transfer-file-configuration-notice'}
                        </div>
                        {if $bIsTransferredDirectly}
                        <div class="alert alert-warning">
                            <div>{_p var='do_not_leave_this_page_until_transferring_process_is_finished'}</div>
                        </div>
                        {else}
                        <div class="form-group">
                            <label class="required" for="storage_id">{_p var='storage'}</label>
                            {foreach from=$aItems item=aItem}
                            <div>
                                <label style="font-weight: normal !important;">
                                    <input type="radio" value="{$aItem.storage_id}" name="transfer_storage_id" {if $sTransferStorageId == $aItem.storage_id}checked{/if}/>
                                    &nbsp;{if $aItem.storage_name}{$aItem.storage_name}{else}{$aItem.service_id}:{$aItem.storage_id}{/if}
                                </label>
                            </div>
                            {/foreach}
                        </div>
                        {/if}
                        {if !empty($transferedProgress) || $bIsTransferredDirectly}
                        <div id="js_core_assets_transfer_file_progress" class="form-group">
                            <label>{_p var='progress'}</label>
                            <div class="progress" style="margin-bottom: 0 !important;">
                                <div id="progress_bar" class="progress-bar progress-bar-success progress-bar-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width: {$transferedProgress.percentage}%;"></div>
                            </div>
                            <div class=""><span id="total_success">{$totalTransferedFile}</span>/<span id="total_transfered">{$iTotalFile}</span></div>
                        </div>
                        <div id="js_core_assets_transfer_file_success" class="form-group alert alert-success" style="display: none;">
                            {_p var='files_successfully_transferred'}
                        </div>
                        {/if}

                        <div class="form-group">
                            <h4>
                                Total {$iTotalFile} asset files.
                            </h4>
                            <div style="height: 200px; overflow-y: auto">
                                <ol>
                                    {foreach from=$aAssetFiles item=sFilename}
                                    <li data-id="{$sFilename}">{$sFilename}</li>
                                    {/foreach}
                                </ol>
                            </div>
                        </div>

                        {if !$bIsTransferredDirectly}
                            <div class="form-group">
                                {if $bIsTransferred}
                                    <button class="btn btn-primary" type="submit" role="button" name="stop" value="1">{_p var='stop'}</button>
                                {else}
                                    <button class="btn btn-primary" type="submit" role="button" name="transfer_files_directly" value="1">{_p var='transfer_files_directly'}</button>
                                    <button class="btn btn-warning" type="submit" role="button" name="transfer" value="1">{_p var='transfer_files'}</button>
                                {/if}
                                <a class="btn btn-default" role="button" href="{url link='admincp.setting.assets.manage'}">{_p var='cancel'}</a>
                            </div>
                        {/if}
                    </div>
                </div>
        </form>
    {else}
        <div class="alert alert-danger">
            {_p var='there_are_no_external_storage'}
        </div>
    {/if}
</div>