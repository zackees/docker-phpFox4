<?php
defined('PHPFOX') or exit('NO DICE!');
?>

{if empty($alreadyHasForm)}
<form method="get" class="form-search" action="{if !empty($searchLink)}{$searchLink}{else}{url link='admincp.user.browse'}{/if}">
{/if}
    <div class="panel panel-default">
        <div class="panel-body">
            <div class="clearfix row">
                <div class="form-group col-sm-3">
                    <label >{_p var='search'}</label>
                    {filter key='keyword' placeholder='search'}
                </div>
                <div class="form-group col-sm-3">
                    <label>{_p var='within'}</label>
                    {filter key='type'}
                </div>
                <div class="form-group col-sm-3">
                    <label >{_p var='group'}</label>
                    {filter key='group'}
                </div>
                <div class="form-group col-sm-3">
                    <label >{_p var='gender'}</label>
                    {filter key='gender'}
                </div>
                <div id="js_admincp_search_options" class="hide">
                    <div class="form-group col-sm-3 js_core_init_selectize_form_group">
                        <label >{_p var='location'}</label>
                        {filter key='country'}
                        {module name='core.country-child' admin_search=1 country_child_filter=true country_child_type='browse'}
                    </div>
                    <div class="form-group col-sm-3">
                        <label >{_p var='city'}</label>
                        {filter key='city'}
                    </div>
                    <div class="form-group col-sm-3">
                        <label >{_p var='zip_postal_code'}</label>
                        {filter key='zip'}
                    </div>
                    <div class="form-group col-sm-3">
                        <label >{_p var='last_ip_address'}</label>
                        {filter key='ip'}
                    </div>
                    <div class="form-group col-sm-3 js_core_init_selectize_form_group">
                        <label >{_p var='age_group'}</label>
                        {filter key='from'}
                    </div>
                    <div class="form-group col-sm-3 js_core_init_selectize_form_group">
                        <label>&nbsp;</label>
                        {filter key='to'}
                    </div>
                    <div class="form-group col-sm-3">
                        <label >{_p var='show_members'}</label>
                        {filter key='status'}
                    </div>
                    <div class="form-group col-sm-3">
                        <label >{_p var='sort_results_by'}</label>
                        {filter key='sort'}
                    </div>
                    <div class="form-group col-sm-3">
                        <label >{_p var='display'}</label>
                        {filter key='display'}
                    </div>
                    {assign var='sFormGroupClass' value='col-sm-3'}
                    {foreach from=$aCustomFields item=aCustomField}
                    {template file='custom.block.foreachcustom'}
                    {/foreach}
                </div>
            </div>
            <div class="form-btn-group">
                <div class="pull-left">
                    {if empty($noUseSearchBtn)}
                        <button type="submit" class="btn btn-primary" name="search[submit]"><i class="fa fa-search" aria-hidden="true"></i> {_p var='search'}</button>
                        <a class="btn btn-default" href="{if !empty($searchLink)}{$searchLink}{else}{url link='admincp.user.browse'}{/if}">{_p var='reset'}</a>
                    {/if}
                    <button type="button" class="btn btn-link" rel="{_p var='view_less_search_options'}" onclick="$('#js_admincp_search_options').toggleClass('hide'); var text = $(this).text(); $(this).text($(this).attr('rel')); $(this).attr('rel', text)" {if !empty($noUseSearchBtn)}style="padding: 0;"{/if}>
                    {_p var='view_more_search_options'}
                    </button>
                </div>
                {if empty($noUseUserFeatures)}
                <div class="pull-right">
                        <span class="dropdown">
                            <a role="button" data-toggle="dropdown" class="btn btn-success">
                                {_p var='import_users'}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <li>
                                    <a href="javascript:void(0);" onclick="tb_show('', $.ajaxBox('user.importUsers', 'height=300&width=600')); return false;">{_p var='import_user_from_csv'}</a>
                                </li>
                                <li>
                                    <a href="{url link='admincp.user.importhistory'}">{_p var='import_user_history'}</a>
                                </li>
                            </ul>
                        </span>
                    <button class="btn btn-info" onclick="tb_show('', $.ajaxBox('user.exportUsers', 'height=300&width=600')); return false;">{_p var='export_users'}</button>
                </div>
                {/if}
            </div>
        </div>
    </div>
{if empty($alreadyHasForm)}
</form>
{/if}