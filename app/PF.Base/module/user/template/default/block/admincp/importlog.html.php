<?php
defined('PHPFOX') or exit('NO DICE!');
?>
<div class="user-admincp-block-import-log">
    {if $bIsValid}
    <div class="error_log_text"><span class="error_log_title">{_p var='row'}</span>: {$iRow}</div>
    <div class="error_log_text"><span class="error_log_title">{_p var='field'}</span>: {$sField}</div>
    <div class="error_log_text">
        <div class="error_log_title">{_p var='error_log'}:</div>
        <ul class="error_log_content">
            {foreach from=$aLogs item=log}
            <li>{$log}</li>
            {/foreach}
        </ul>
    </div>
    {else}
    {/if}
</div>
