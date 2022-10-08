<?php 
/**
 * [PHPFOX_HEADER]
 * 
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		phpFox LLC
 * @package  		Module_Language
 * @version 		$Id: phrase.html.php 7195 2014-03-17 15:54:31Z Fern $
 */
 
defined('PHPFOX') or exit('NO DICE!'); 

?>
<form class="form-search" method="GET" action="{url link="admincp.language.phrase"}" id="phrase_search">
    <div class="panel panel-default">
        <div class="panel-body">
            <div class="clearfix row">
                {token}
                <div class="form-group col-sm-3">
                    <label>{_p var='search_for_text'}</label>
                    {$aFilters.search}
                </div>
                <div class="form-group col-sm-3">
                    <label for="">{_p var='language_packages'}</label>
                    {$aFilters.language_id}
                </div>
                <div class="form-group col-sm-3">
                    <label for="">{_p var='phrases'}</label>
                    {$aFilters.translate_type}
                </div>
                <div class="form-group col-sm-3">
                    <label for="">{_p var='display'}</label>
                    {$aFilters.display}
                </div>
                <div id="js_admincp_search_options" class="hide">
                    <div class="form-group col-sm-3">
                        {$aFilters.search_type}
                    </div>
                    <div class="form-group col-sm-3">
                        <label for="">{_p var='sort_by'}</label>
                        {$aFilters.sort}
                    </div>
                    <div class="form-group col-sm-3">
                        <label>&nbsp;</label>
                        {$aFilters.sort_by}
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div><button type="submit" name="search[submit]" class="btn btn-primary"><i class="fa fa-search" aria-hidden="true"></i> {_p var='search'}</button>
                    <a class="btn btn-link" href="#" rel="{_p var='view_less_search_options'}" onclick="$('#js_admincp_search_options').toggleClass('hide'); var text = $(this).text(); $(this).text($(this).attr('rel')); $(this).attr('rel', text); return false;">{_p var='view_more_search_options'}</a></div>
            </div>
        </div>
    </div>
</form>
<div class="block_content">
	{if count($aRows)}
        <form class="form" method="post" action="{if $bIsForceLanguagePackage}{url link='admincp.language.phrase' search-params=$sSearchParams page=$iPage lang-id=$iLangId}{else}{url link='admincp.language.phrase' search-params=$sSearchParams page=$iPage}{/if}">
            <div class="table-responsive">
                <table class="table table-admin">
                    <thead>
                        <tr>
                            <th class="w20">
                                <div class="custom-checkbox-wrapper">
                                    <label>
                                        <input type="checkbox" name="val[id]" value="" id="js_check_box_all" />
                                        <span class="custom-checkbox"></span>
                                    </label>
                                </div>
                            </th>
                            <th style="width:20%;">{_p var='variable'}</th>
                            {if !$iLangId}<th style="width:10%;">{_p var='language'}</th>{/if}
                            <th style="width:30%;">{_p var='original'}</th>
                            <th style="width:90%;">{_p var='text'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$aRows name=rows item=aRow}
                            <tr id="js_row{$aRow.phrase_id}" class="checkRow{if is_int($phpfox.iteration.rows/2)} tr{else}{/if}">
                                <td>
                                    <div class="custom-checkbox-wrapper">
                                        <label>
                                            <input type="checkbox" name="id[]" class="checkbox" value="{$aRow.phrase_id}" id="js_id_row{$aRow.phrase_id}" />
                                            <span class="custom-checkbox"></span>
                                        </label>
                                    </div>
                                </td>
                                <td title="{$aRow.var_name}">
                                    <input readonly type="text" name="null" value="{$aRow.var_name}" size="25" onfocus="tb_show('{_p var='phrase_variables' phpfox_squote=true}', $.ajaxBox('language.sample', 'height=240&width=550&phrase={$aRow.var_name}'));" class="form-control"/>
                                </td>
                                {if !$iLangId}<td>{$aRow.title}</td>{/if}
                                <td>{$aRow.sample_text}</td>
                                <td class="t_center{if $aRow.is_translated} is_translated{/if}"><textarea rows="6" name="text[{$aRow.phrase_id}]" class="text form-control" style="width:95%;">{$aRow.text|htmlspecialchars}</textarea></td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
            <div class="table_bottom table_hover_action hidden">
                <input type="submit" name="save_selected" value="{_p var='save_selected'}" class="btn btn-primary disabled sJsCheckBoxButton" disabled/>
                <input type="submit" name="delete" value="{_p var='delete_selected'}" class="btn btn-danger disabled sJsCheckBoxButton" disabled data-confirm-message="{_p var='are_you_sure_you_want_to_delete_selected_phrases'}" />
                <input type="submit" name="revert_selected" value="{_p var='revert_selected_default'}" class="btn btn-default disabled sJsCheckBoxButton" disabled data-confirm-message="{_p var='are_you_sure_you_want_to_reverse_selected_phrases_to_default'}" />
                <input type="submit" name="save" value="{_p var='save_all'}" class="btn btn-primary" />
            </div>
        </form>
	    {pager}
	{else}
        <div class="p_4 t_center">
            {_p var='phrases_found'}
        </div>
	{/if}
</div>
{if isset($q)}
    <script type="text/javascript">
        document.getElementsByName('search[search]')[0].value = "{$q}";
        document.getElementsByName('search[search_type]')[3].checked = true;
        document.getElementById('phrase_search').submit();
    </script>
{/if}