<?php
defined('PHPFOX') or exit('NO DICE!');
?>
<option value="">{_p var='select'}:</option>
{foreach from=$aComponents key=sName item=aComponent}
    <optgroup label="{$sName|translate:'module'}">
        {foreach from=$aComponent item=aComp}
        <option value="{$sName}|{$aComp.component}"{value type='select' id='component' default=''$sName'|'$aComp.component''}>-- {$aComp.component}</option>
        {/foreach}
    </optgroup>
{/foreach}
