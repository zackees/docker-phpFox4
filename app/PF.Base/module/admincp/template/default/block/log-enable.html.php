<label>{_p var='active'}</label>
<div>
    <div>
        <label style="font-weight: normal !important;">
            <input type="radio" value="1" name="val[is_active]" {if $aForms.is_active}checked{/if}/>
            &nbsp;{_p var='use_this_adapter_to_save_log'}
        </label>
    </div>
    <div>
        <label style="font-weight: normal !important;">
            <input type="radio" value="0" name="val[is_active]" {if !$aForms.is_active}checked{/if}/>
            &nbsp;{_p var='core.no'}
        </label>
    </div>
</div>