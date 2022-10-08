<?php 
defined('PHPFOX') or exit('NO DICE!');
?>
<div class="t_center error_message ">
{if $iStatus == 1}
    {if Phpfox::getParam('core.enable_register_with_phone_number')}
        {_p var='this_site_is_very_concerned_about_security_with_phone_number'}
    {else}
        {_p var='this_site_is_very_concerned_about_security'}
    {/if}
{else}
    {if $iViewId == 1}
        {_p var='your_account_is_pending_approval'}
    {else}
        {_p var='your_account_has_been_dennied'}
    {/if}
{/if}
</div>