<?php
/**
 * [PHPFOX_HEADER]
 * 
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		phpFox LLC
 * @package 		Phpfox
 * @version 		$Id: form.html.php 2655 2011-06-03 11:40:56Z phpFox LLC $
 */
 
defined('PHPFOX') or exit('NO DICE!'); 

?>
<div class="lang_table">
{foreach from=$aLanguages item=aLanguage}
{if $sType == 'text'}
    <div class="form-group">
        <label>{$aLanguage.title}</label>
        <input required type="text" name="val[{$sId}]{if isset($aLanguage.phrase_var_name)}[{$aLanguage.phrase_var_name}]{/if}[{$aLanguage.language_id}]{if isset($sMode)}[{$sMode}]{/if}" value="{$aLanguage.post_value|htmlspecialchars}" placeholder="{$aLanguage.title}" class="form-control {if !empty($bCloseWarning)}close_warning{/if}"/>
    </div>
{elseif $sType == 'label'}
    {if $aLanguage.post_value != ''}
        <div class="lang_title">
            {$aLanguage.post_value|htmlspecialchars} <small>({$aLanguage.title})</small>
        </div>
    {/if}
{else}
    <div class="form-group">
        <label>{$aLanguage.title}</label>
        <textarea required class="form-control {if !empty($bCloseWarning)}close_warning{/if}" cols="50" rows="5" name="val[{$sId}]{if isset($aLanguage.phrase_var_name)}[{$aLanguage.phrase_var_name}]{/if}[{$aLanguage.language_id}]{if isset($sMode)}[{$sMode}]{/if}">{$aLanguage.post_value|htmlspecialchars}</textarea>
    </div>
{/if}
    <div class="clear"></div>
{/foreach}
</div>