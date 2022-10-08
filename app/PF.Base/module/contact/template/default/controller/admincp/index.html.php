<?php
/**
 * [PHPFOX_HEADER]
 *
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		Miguel Espinoza
 * @package  		Module_Contact
 * @version 		$Id: index.html.php 1802 2010-09-08 12:52:12Z phpFox LLC $
 */

defined('PHPFOX') or exit('NO DICE!');

?>

<form method="post" action="{url link='admincp.contact'}" id="admincp_contact_form_add" class="form">
    <input type="hidden" name="action" value="{if isset($aForms)}edit{else}add{/if}"/>
    {if isset($aForms)}
    <input type="hidden" name="iEdit" value="{$aForms.category_id}">
    {/if}

    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="panel-title">{if isset($aForms)}{_p('Edit Category')}{else}{_p var='add_a_new_category'}{/if}</div>
        </div>
        <div class="panel-body">
            {field_language phrase='title' label='name' format='val[name_' field='name' required=true}
        </div>
        <div class="panel-footer">
            <button class="btn btn-primary" type="submit">{if isset($aForms)}{_p var='update'}{else}{_p var='add'}{/if}</button>
        </div>
    </div>
</form>

{if !empty($aCategories)}
    <form method="post" id="admincp_contact_form_edit" action="{url link='admincp.contact'}">
        <div class="table-responsive">
            <table class="table table-admin" id="js_drag_drop">
                <thead>
                    <tr>
                        <th class="w20">{_p var='order'}</th>
                        <th class="w20">
                            <div class="custom-checkbox-wrapper">
                                <label>
                                    <input type="checkbox" name="val[id]" value="" id="js_check_box_all" class="main_checkbox" />
                                    <span class="custom-checkbox"></span>
                                </label>
                            </div>
                        </th>
                        <th>{_p var='category'}</th>
                        <th class="w80">{_p var='settings'}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$aCategories key=iKey item=aCategory}
                    <tr>
                        <td class="drag_handle"><input type="hidden" name="val[ordering][{$aCategory.category_id}]" value="{$aCategory.ordering}" /></td>
                        <td class="t_center">
                            <div class="custom-checkbox-wrapper">
                                <label>
                                    <input type="checkbox" name="id[]" class="checkbox" value="{$aCategory.category_id}" id="js_id_row" />
                                    <span class="custom-checkbox"></span>
                                </label>
                            </div>
                        </td>
                        <td>{_p var=$aCategory.title}</td>
                        <td class="t_center">
                            <a href="#" class="js_drop_down_link" title="Manage"></a>
                            <div class="link_menu">
                                <ul class="dropdown-menu dropdown-menu-right">
                                    <li><a href="{url link='admincp.contact' edit=$aCategory.category_id}">{_p var='Edit'}</a></li>
                                    <li><a href="{url link='admincp.contact' delete=1}&id%5B%5D={$aCategory.category_id}" class="sJsConfirm" data-message="{_p var='are_you_sure_you_want_to_delete_this_category_permanently'}">{_p var='Delete'}</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        <div class="table_bottom">
            <input type="submit" name="delete" value="{_p var='delete_selected'}" class="sJsConfirm delete btn btn-default sJsCheckBoxButton disabled" disabled="true" data-message="{_p var='are_you_sure_you_want_to_delete_selected_categories_permanently'}"/>
        </div>
    </form>
{else}
    <div class="alert alert-empty">{_p var='no_categories_found'}</div>
{/if}