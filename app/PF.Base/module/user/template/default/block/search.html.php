<?php
defined('PHPFOX') or exit('NO DICE!');
?>
<div class="hide" id="js_search_user_browse_content">
    <div id="js_search_user_browse_wrapper" class="" >
        <div id="js_user_browse_search_result" class="item_is_active_holder item_selection_active advance_search_button">
            <a id="js_user_browse_enable_adv_search_btn" href="javascript:void(0)" onclick="advSearchUserBrowse.enableAdvSearch();return false;">
                <i class="ico ico-dottedmore-o"></i>
            </a>
        </div>
    </div>
    <div id="js_user_browse_adv_search_wrapper" class="advance_search_form init member_advance_search_form" style="display: none;">
            <div class="member-search-inline-wrapper js_core_init_selectize_form_group">
                {if Phpfox::getUserParam('user.can_search_user_age')}
                    <div class="form-group item-age ">
                        <label>{_p var='age'} ({_p var='from'})</label>
                        {filter key='from'}
                    </div>
                    <div class="form-group item-age">
                        <label>{_p var='age'} ({_p var='to'})</label>
                        {filter key='to'}
                    </div>
                {/if}

                <div class="form-group item-location dont-unbind-children">
                    <label>{_p var='country'}</label>
                    {filter key='country'}
                    {module name='core.country-child' country_child_filter=true country_child_type='browse'}
                </div>

                <div class="form-group item-location">
                    <label>{_p var='city'}</label>
                    {filter key='city'}
                </div>

                {if Phpfox::getUserParam('user.can_search_by_zip')}
                    <div class="form-group item-zip-post">
                        <label>{_p var='zip_postal_code'}</label>
                        {filter key='zip'}
                    </div>
                {/if}
            </div>

            {if !empty($aAboutMeCustomField)}
                <div class="form-group">
                    <label>{_p var='about_info'}</label>
                    <input id="js_adv_search_about_me" type="text" class="form-control" placeholder="{_p var='some_text_about_keyword'}" name="custom[{$aAboutMeCustomField.field_id}]" value="{value type='input' id=$aAboutMeCustomField.field_id}">
                </div>
            {/if}

            {if Phpfox::getUserParam('user.can_search_user_gender')}
                <div class="form-group item-gender-search">
                    <label>{_p var='browser_for'}</label>
                    <div class="item-group-gender">
                        {foreach from=$aGenders key=iGenderId item=sGenderName}
                            <div class="item-gender radio core-radio-custom">
                                <label >
                                    <input type="radio" name="search[gender]" value="{$iGenderId}" {if !empty($aForms.gender) && $aForms.gender == $iGenderId}checked{/if}>
                                    <i class="custom-icon"></i>
                                    <span>{_p var=$sGenderName}</span>
                                </label>
                            </div>
                        {/foreach}
                        <div class="item-gender radio core-radio-custom">
                            <label >
                                    <input type="radio" name="search[gender]" value="" {if empty($aForms.gender)}checked{/if}>
                                    <i class="custom-icon"></i>
                                <span>{_p var='any'}</span>
                            </label>
                        </div>
                    </div>
                </div>
            {/if}

            <div class="form-group">
                <label >{_p var='sort_results_by'}</label>
                {filter key='sort'}
            </div>

            <div id="js_user_browse_advanced">
                <div class="user_browse_content">
                    <div id="browse_custom_fields_popup_holder">
                        {foreach from=$aCustomFields name=customfield item=aCustomField}
                            {if isset($aCustomField.fields)}
                                {template file='custom.block.foreachcustom'}
                            {/if}
                        {/foreach}
                    </div>
                </div>
            </div>

            <div class="form-group clearfix advance_search_form_button">
                <div class="pull-left">
                    <span class="advance_search_dismiss" onclick="advSearchUserBrowse.enableAdvSearch();return false;">
                        <i class="ico ico-close"></i>
                    </span>
                </div>
                <div class="pull-right">
                    <a class="btn btn-default btn-sm" href="javascript:void(0);" onclick="advSearchUserBrowse.resetForm(); return false;">{_p var='reset'}</a>
                    <button name="search[submit]" class="btn btn-primary ml-1 btn-sm"><i class="ico ico-search-o mr-1"></i>{_p var='submit'}</button>
                </div>
            </div>

            {if isset($sCountryISO)}
                <script type="text/javascript">
                    $Behavior.loadStatesAfterBrowse = function()
                    {l}
                    sCountryISO = "{$sCountryISO}";
                    if(sCountryISO != "")
                    {l}
                    sCountryChildId = "{$sCountryChildId}";
                    $.ajaxCall('core.getChildren', 'country_child_filter=true&country_child_type=browse&country_iso=' + sCountryISO + '&country_child_id=' + sCountryChildId);
                    {r}
                    {r}
                </script>
            {/if}
    </div>
</div>
