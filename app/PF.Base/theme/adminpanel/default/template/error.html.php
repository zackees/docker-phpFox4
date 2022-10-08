{if !empty($sPublicMessage)}
<div class="public_message {if $sPublicMessageType != 'success'}public_message_{$sPublicMessageType}{/if}" id="public_message" data-auto-close="{$sPublicMessageAutoClose}">
    <span>{$sPublicMessage}</span>
    <span class="ico ico-close-circle-o" onclick="$Core.publicMessageSlideDown();"></span>
</div>
<script type="text/javascript">
	$Behavior.theme_admincp_error = function()
	{l}
		$('#public_message').show();
	{r};
</script>
{/if}
<div id="core_js_messages">
{if count($aErrors)}
{foreach from=$aErrors item=sErrorMessage}
	<div class="error_message">{$sErrorMessage}</div>
{/foreach}
{unset var=$sErrorMessage var2=$sample}
{/if}
</div>

{if defined('PHPFOX_TRIAL_MODE')}
    {template file='core.block.template-trial-mode'}
{/if}

{if setting('core.site_is_offline')}
    {module name='core.template-site-offline'}
{/if}