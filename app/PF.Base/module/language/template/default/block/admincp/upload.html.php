<?php
defined('PHPFOX') or exit('NO DICE!');
?>

<form method="POST" action="{url link='current'}" enctype="multipart/form-data">
    <div class="form-group">
        <input type="file" accept=".zip" name="file" id="js_upload_language_pack">
    </div>
</form>

{literal}
    <script type="text/javascript">
        $Behavior.initUploadLanguagePack = function() {
          $('#js_upload_language_pack').off('change').on('change', function() {
            if ($(this)[0].files.length) {
              $(this).closest('form').submit();
            }
          });
        }
    </script>
{/literal}
