<?php 
defined('PHPFOX') or exit('NO DICE!');

?>
<form class="form" method="post" action="{url link='admincp.report.category'}">
	{if count($aCategories)}
    <div class="table-responsive">
        <table class="table table-admin has-drag" id="js_drag_drop">
            <thead>
                <tr>
                    <th style="width:10px;"></th>
                    <th style="width:10px;">
                        <div class="custom-checkbox-wrapper">
                            <label>
                                <input type="checkbox" name="val[id]" value="" id="js_check_box_all" class="main_checkbox" />
                                <span class="custom-checkbox"></span>
                            </label>
                        </div>
                    </th>
                    <th>{_p var='category'}</th>
                    <th>{_p var='module'}</th>
                    <th class="w80">{_p var='settings'}</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$aCategories key=iKey item=aCategory}
                    <tr id="js_row{$aCategory.report_id}" class="checkRow{if is_int($iKey/2)} tr{else}{/if}">
                        <td class="drag_handle">
                            <input type="hidden" name="val[ordering][{$aCategory.report_id}]" value="{$aCategory.ordering}" />
                        </td>
                        <td>
                            <div class="custom-checkbox-wrapper">
                                <label>
                                    <input type="checkbox" name="id[]" class="checkbox" value="{$aCategory.report_id}" id="js_id_row{$aCategory.report_id}" />
                                    <span class="custom-checkbox"></span>
                                </label>
                            </div>
                        </td>
                        <td>{_p var=$aCategory.message}</td>
                        <td>{$aCategory.module_id|translate:'module'}</td>
                        <td class="t_center">
                            <a href="#" class="js_drop_down_link" title="{_p var='Manage'}"></a>
                            <div class="link_menu">
                                <ul class="dropdown-menu dropdown-menu-right">
                                    <li><a href="#" onclick="tb_show(oTranslations['delete_category'], $.ajaxBox('report.deleteCategory', 'height=400&width=550&report_id={$aCategory.report_id}')); return false;">{_p var='delete'}</a></li>
                                    <li>
                                        <a href={url link='admincp.report.add' id=$aCategory.report_id}"">
                                            {_p var='edit'}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
	<div class="table_bottom">
		<input type="submit" name="delete" value="{_p var='delete_selected'}" class="sJsConfirm delete btn btn-danger sJsCheckBoxButton disabled" disabled="true" />
	</div>
	{else}
	<p class="alert alert-empty">
		{_p var='no_categories'}
	</p>
	{/if}
</form>