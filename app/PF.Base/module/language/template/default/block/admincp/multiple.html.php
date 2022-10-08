<div class="form-group">
    {assign var='value_name' value=$sField"_"$aDefaultLanguage.language_id }
    <label for="{$sField}_{$aDefaultLanguage.language_id}">{if $bRequired}{required}{/if}{_p var=$sLabel} in {$aDefaultLanguage.title}</label>
    {if $sType=='textarea'}
    <textarea {if $bRequired}required{/if} class="form-control {if $bCloseWarning}close_warning{/if}" id="{$value_name}" name="{$sFormat}{$aDefaultLanguage.language_id}]{if !empty($bAllowMultiple)}[]{/if}" rows="{$sRows}" maxlength={$sMaxLength}>{$aDefaultLanguage.phrase_value|clean}</textarea>
    {else}
    <input id="{$value_name}" {if $bRequired}required{/if} class="form-control {if $bCloseWarning}close_warning{/if}" type="text" name="{$sFormat}{$aDefaultLanguage.language_id}]{if !empty($bAllowMultiple)}[]{/if}" value="{$aDefaultLanguage.phrase_value|clean}" size="{$sSize}" maxlength={$sMaxLength} />
    {/if}
    {if count($aOtherLanguages) > 0}
    <p class="help-block"></p>
    <div class="clearfix collapse-placeholder">
        <a role="button" data-cmd="core.toggle_placeholder">{_p var='label_in_other_languages' label=$sLabel}</a>
        <div class="inner">
            <p class="help-block">{_p var=$sHelpPhrase}</p>
            {foreach from=$aOtherLanguages item=aLanguage}
            {assign var='value_name' value=$sField"_"$aLanguage.language_id}
            <div class="form-group">
                <label for="{$value_name}"><strong>{$aLanguage.title}</strong>:</label>
                {if $sType=='textarea'}
                <textarea class="form-control {if $bCloseWarning}close_warning{/if}" id="{$value_name}" name="{$sFormat}{$aLanguage.language_id}]{if !empty($bAllowMultiple)}[]{/if}" rows="{$sRows}" maxlength={$sMaxLength}>{$aLanguage.phrase_value|clean}</textarea>
                {else}
                <input class="form-control {if $bCloseWarning}close_warning{/if}" type="text" id="{$value_name}" name="{$sFormat}{$aLanguage.language_id}]{if !empty($bAllowMultiple)}[]{/if}" value="{$aLanguage.phrase_value|clean}" size="{$sSize}" maxlength={$sMaxLength} />
                {/if}
            </div>
            {/foreach}
        </div>
    </div>
    {/if}
</div>

