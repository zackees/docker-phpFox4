<?php 
defined('PHPFOX') or exit('NO DICE!');
?>
<form class="form" enctype="multipart/form-data" method="post" action="{if $bIsEdit}{url link="admincp.menu.add" id=$aForms.menu_id}{else}{url link="admincp.menu.add"}{/if}">
<div class="panel panel-default">
    <div class="panel-body">
            <input type="hidden" name="send_path" value="{url link='admincp.menu'}" />
            {if $bIsEdit}
            <input type="hidden" name="menu_id" value="{$aForms.menu_id}" />
            {/if}
            {if $bIsPage}
            <input type="hidden" name="val[page_id]" value="{$aPage.page_id}" />
            <input type="hidden" name="val[product_id]" value="{$aPage.product_id}" />
            <input type="hidden" name="val[module_id]" value="{$sModuleValue}" />
            <input type="hidden" name="val[url_value]" value="{$aPage.title_url}" />
            <input type="hidden" name="val[is_page]" value="true" />
            {/if}
            {if !$bIsPage}
                {if Phpfox::getUserParam('admincp.can_view_product_options')}
                    <div class="form-group"{if !Phpfox::isTechie()} style="display:none;"{/if}>
                        <label for="product_id">{_p var='product'}</label>
                        <select id="product_id" name="val[product_id]" class="form-control close_warning">
                            {foreach from=$aProducts item=aProduct}
                                <option value="{$aProduct.product_id}"{value type='select' id='product_id' default=$aProduct.product_id}>{$aProduct.title}</option>
                            {/foreach}
                        </select>
                        <h5 class="help-block">{_p var='menu_add_product'}</h5>
                    </div>
                {/if}
                <div class="form-group js_core_init_selectize_form_group"{if !Phpfox::isTechie()} style="display:none;"{/if}>
                    <label for="module_id">{_p var='module'}</label>
                    <select id="module_id" name="val[module_id]" class="form-control close_warning">
                        <option value="">{_p var='select'}:</option>
                        {foreach from=$aModules key=sModule item=iModuleId}
                        <option value="{$iModuleId}|{$sModule}"{value type='select' id='module_id' default=$iModuleId}>{translate var=$sModule prefix='module'}</option>
                        {/foreach}
                    </select>
                    <h5 class="help-block">{_p var='menu_add_module'}</h5>
                </div>
            {/if}
            <div class="form-group js_core_init_selectize_form_group">
                <label for="m_connection" class="required">{_p var='placement'}</label>
                <select id="m_connection" name="val[m_connection]" class="form-control close_warning" onchange="_admincp_on_change_menu_placement(this);">
                    <option value="">{_p var='select'}:</option>
                    {foreach from=$aTypes item=sType}
                    <option value="{$sType}"{value type='select' id='m_connection' default=$sType}>{_p var=''$sType'_placement''}</option>
                    {/foreach}
                </select>
                <h5 class="help-block">{_p var='menu_add_connection'}</h5>
                {if !empty($aForms.menu_id) && empty($aForms.parent_id)}
                    <h5 class="help-block">{_p var='update_menu_connection_notice'}</h5>
                {/if}
            </div>
            <div class="form-group js_core_init_selectize_form_group {if empty($aForms.parent_id)}hide{/if}" id="js_add_parent_menu">
                <label for="parent_id">{_p var='parent_menu'}</label>
                <select id="parent_id" name="val[parent_id]" class="form-control close_warning">
                    <option value="">{_p var='select'}:</option>
                    {foreach from=$aParents item=aParent}
                        <option value="{$aParent.menu_id}" {value type='select' id='parent_id' default=$aParent.menu_id}>{_p var=$aParent.var_name}</option>
                    {/foreach}
                </select>
                <h5 class="help-block">{_p var='menu_add_parent_menu'}</h5>
            </div>
            {if !$bIsPage}
            <div class="form-group">
                <label>{_p var='url'}</label>
                <input type="text" name="val[url_value]" id="url_value" value="{value type='input' id='url_value'}" size="40" maxlength="250" class="form-control" />
                {if !$bIsEdit && count($aPages)}
                <div class="p_4" style="display:none;">
                    {_p var='or_select_a_page'}
                    <select name="val[url_value_page]" onchange="$('#url_value').val(this.value);" class="form-control close_warning">
                        <option value="">{_p var='select'}:</option>
                        {foreach from=$aPages key=sPage item=iId}
                            <option value="{$sPage}"{value type='select' id='m_connection' default=$sType}>{$sPage}</option>
                        {/foreach}
                    </select>
                </div>
                {/if}
                <h5 class="help-block">{_p var='menu_add_url'}</h5>
            </div>
            {/if}

            <div class="form-group">
                <label for="mobile_icon">{_p var='menu_icon'}</label>
                {iconfont_input name="mobile_icon" class="close_warning" id="mobile_icon" check_fa=true}
                <h5 class="help-block" id="js_font_aws_helper"><a onclick="_admincp_toggle_icon_font_picker('mobile_icon');" href="javascript:void(0)">{_p var='menu_font_awesome'}</a></h5>
                <h5 class="help-block" style="display: none" id="js_font_lineficon_helper"><a onclick="_admincp_toggle_icon_font_picker('mobile_icon');" href="javascript:void(0)" s>{_p var='menu_font_lineficon'}</a></h5>
                <h5 class="help-block" id="menu_font_helper" style="display: none">
                    {_p var='font_awesome_helper_three_step' link="https://fontawesome.com/v4.7.0/icons/"}
                    {if $sThemeActive == 'material'}
                        {_p var='font_awesome_helper_extra_step' link=$sLineficonLink}
                    {/if}
                </h5>
            </div>
            <div class="form-group">
                <label class="required">{_p var='menu'}</label>
                {foreach from=$aLanguages item=aLanguage}
                <div class="form-group">
                    <label>{$aLanguage.title}</label>
                    <div class="lang_value">
                        <textarea class="form-control close_warning" cols="50" rows="5" name="val[text][{$aLanguage.language_id}]">{if isset($aLanguage.text)}{$aLanguage.text|htmlspecialchars}{/if}</textarea>
                    </div>
                </div>
                {/foreach}
            </div>

            <div class="form-group">
                <label>{_p var='allow_access'}</label>
                {foreach from=$aUserGroups item=aUserGroup}
                <div class="custom-checkbox-wrapper">
                    <label>
                        <input type="checkbox" name="val[allow_access][]" class="close_warning" value="{$aUserGroup.user_group_id}"{if isset($aAccess) && is_array($aAccess)}{if !in_array($aUserGroup.user_group_id, $aAccess)} checked="checked" {/if}{else} checked="checked" {/if}/>
                        <span class="custom-checkbox"></span>
                        {$aUserGroup.title|convert|clean}
                    </label>
                </div>
                {/foreach}
            </div>
        </div>
        <div class="panel-footer">
            {if $bIsEdit}
            <button type="submit" name="_submit" class="btn btn-primary" value="_save">{_p var="save"}</button>
            {else}
            <button type="submit" name="_submit" class="btn btn-primary" value="_save">{_p var="save"}</button>
            {/if}
        </div>
    </div>
</form>

