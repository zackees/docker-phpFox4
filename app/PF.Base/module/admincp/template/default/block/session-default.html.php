{if $aForms.is_default}
<input type="hidden" name="val[is_default]" value="1"/>
{else}
<label>{_p var='is_default'}</label>
<div>
    <div>
        <label style="font-weight: normal !important;">
            <input type="radio" value="1" name="val[is_default]" {if $aForms.is_default}checked{/if}/>
            &nbsp;{_p var='use_this_adapter_to_save_session'}
        </label>
    </div>
    <div>
        <label style="font-weight: normal !important;">
            <input type="radio" value="0" name="val[is_default]" {if !$aForms.is_default}checked{/if}/>
            &nbsp;{_p var='core.no'}
        </label>
    </div>
</div>
{/if}