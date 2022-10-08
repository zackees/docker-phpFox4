<?php 
 
defined('PHPFOX') or exit('NO DICE!'); 

?>
{if $bHtml}
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>
{if $bMessageHeader}
    {if isset($sMessageHello)}
        {$sMessageHello}
    {else}
        {_p var='hello_comma'}
    {/if}
    <br />
    <br />
{/if}
    {$sMessage}
    <br />
    <br />
    {$sEmailSig}
</body>
</html>
{else}	
{if $bMessageHeader}
	{if isset($sMessageHello)}
	    {$sMessageHello}
	{else}
	    {_p var='hello_comma'}
	{/if}
{/if}	
	{$sMessage}

	{$sEmailSig}	
{/if}