<?php 
defined('PHPFOX') or exit('NO DICE!');
?>
<div class="welcome-bg-image row_image" >
<div id="welcome_message" class="{if empty($sWelcomeContent)}hide{/if}">
    <div class="custom_flavor_content" style="white-space: pre-wrap;">{$sWelcomeContent|clean}</div>
</div>
</div>