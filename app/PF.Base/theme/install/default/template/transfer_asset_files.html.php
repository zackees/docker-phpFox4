<?php
defined('PHPFOX') or exit('NO DICE!');
?>
{literal}
    <style>
        #btn_ok {
            display: none !important;
        }
    </style>
    <script>
        {/literal}
            var aTransferFileData = {$aTransferFileData};
            var iTotalFile   = {$iTotalFile};
            var iUploaded    = 0;
            var sProgressUrl = '{$progressUrl}';
            {literal};
            function completed(){
                $('#js_transfer_file_progress').hide();
                $('#js_transfer_file_success').show();
                setTimeout(function() {
                    installer.continue();
                }, 2000);
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

                $.ajax({
                    type: 'POST',
                    dataType: 'html',
                    url: sProgressUrl,
                    data: {
                        files: files.join(';'),
                        storage_id: storage_id,
                    },
                    success: function (response) {
                        send();
                        successful();
                    },
                });
            }
            send();
            send();
            send();
            send();
    </script>
{/literal}
<div>
    <div id="js_transfer_file_progress">
        <label>{_p var='progress'}</label>
        <div class="progress" style="margin-bottom: 0 !important;">
            <div id="progress_bar" class="progress-bar progress-bar-success progress-bar-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width: {$transferedProgress.percentage}%;"></div>
        </div>
        <div><span id="total_success">{$totalTransferedFile}</span>/<span id="total_transfered">{$iTotalFile}</span></div>
    </div>
    <div id="js_transfer_file_success" class="alert alert-success" style="display: none;">
        {_p var='files_successfully_transferred'}
    </div>
</div>
<div style="margin-top: 16px;">
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