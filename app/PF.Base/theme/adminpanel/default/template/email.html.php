<?php 
 
defined('PHPFOX') or exit('NO DICE!'); 

?>
{if $bHtml}	
	{if isset($sName)}
	    {_p var='hello_name_comma' name=$sName}
	{else}
	    {_p var='hello_comma'}
	{/if}
	<br />
	<br />
	{$sMessage}
	<br />
	<br />
	{$sEmailSig}	
{else}	
	{if isset($sName)}
	    {_p var='hello_name_comma' name=$sName}
	{else}
	    {_p var='hello_comma'}
	{/if}
	{$sMessage}

	{$sEmailSig}	
{/if}