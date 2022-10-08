<?php
defined('PHPFOX') or exit('NO DICE!');
?>

{foreach from=$aCustomMain item=aCustom}
    {if $sTemplate == 'info'}
        {module name='custom.block' data=$aCustom template=$sTemplate edit_user_id=$aUser.user_id}
    {else}
        {module name='custom.block' data=$aCustom template=$sTemplate edit_user_id=$aUser.user_id}
    {/if}
{/foreach}
