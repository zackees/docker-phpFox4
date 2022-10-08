<?php
defined('PHPFOX') or exit('NO DICE!');

?>
<div class="google-btn-container js_google_button">
    <div id="{$sId}" class="google-btn {if empty($bSmallSize)}btn btn-default mt-1{/if}">
        <span class="g-icon">{img theme='misc/google.png' alt='' class='v_middle'}</span>
        {if empty($bSmallSize)}<span class="g-label">{_p var=$sPhrase}</span>{/if}
    </div>
</div>
{literal}
<script>
  setTimeout(function () {
    $Core.googleAuth.buildGoogleSignInButton('#' + '{/literal}{$sId}{literal}');
  }, 500);
</script>
{/literal}