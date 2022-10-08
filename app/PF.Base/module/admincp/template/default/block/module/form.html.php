<?php 
/*might not use*/
defined('PHPFOX') or exit('NO DICE!'); 

?>
<div class="form-group js_core_init_selectize_form_group"{if !Phpfox::isTechie()} style="display:none;"{/if}>
	<label for="{if !$bUseClass}{$sModuleFormId}{/if}" {if $bModuleFormRequired}class="required"{/if}>
        {$sModuleFormTitle}
    </label>
    <select name="val[{$sModuleFormId}]" {if $bUseClass}class="{$sModuleFormId} form-control"{else}id="{$sModuleFormId}" class="form-control"{/if}>
        <option value="">{$sModuleFormValue}</option>
        {foreach from=$aModules key=sModule item=iModuleId}
            <option value="{$sModule}"{value type='select' id=''$sModuleFormId'' default=$sModule}>{translate var=$sModule prefix='module'}</option>
        {/foreach}
    </select>
</div>