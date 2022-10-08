<?php
defined('PHPFOX') or exit('NO DICE!');
?>
{if count($aPromotions)}
    <div class="alert alert-info">{_p var='the_first_promotion_which_satisfy_the_criteria_will_be_applied_for_user'}</div>
    <div class="table-responsive">
        <table class="table table-admin">
            <thead>
                <tr>
                    <th {table_sort asc="ug1.title asc" desc="ug1.title desc" query="sort" current=$sCurrent}>{_p var='user_group'}</th>
                    <th {table_sort asc="ug2.title asc" desc="ug2.title desc" query="sort" current=$sCurrent}>{_p var='upgraded_user_group'}</th>
                    <th {table_sort class="t_center" asc="up.total_activity asc" desc="up.total_activity desc" query="sort" current=$sCurrent}>{_p var='total_activity_points'}</th>
                    <th {table_sort class="t_center" asc="up.total_day asc" desc="up.total_day desc" query="sort" current=$sCurrent}>{_p var='total_days_registered'}</th>
                    <th {table_sort asc="up.time_stamp asc" desc="up.time_stamp desc" query="sort" current=$sCurrent}>{_p var='created_on'}</th>
                    <th class="w80 t_center">{_p var='settings'}</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$aPromotions name=promotions item=aPromotion}
                    <tr>
                        <td>{$aPromotion.user_group_title|convert}</td>
                        <td>{$aPromotion.upgrade_user_group_title|convert}</td>
                        <td class="t_center">{$aPromotion.total_activity}</td>
                        <td class="t_center">{$aPromotion.total_day}</td>
                        <td>{$aPromotion.time_stamp|date}</td>
                        <td class="t_center">
                            <a role="button" class="js_drop_down_link" title="Manage"></a>
                            <div class="link_menu">
                                <ul class="dropdown-menu dropdown-menu-right">
                                    <li><a href="{url link='admincp.user.promotion.add' id=$aPromotion.promotion_id}" class="popup">{_p var='edit'}</a></li>
                                    <li><a href="{url link='admincp.user.promotion' delete=$aPromotion.promotion_id}" class="sJsConfirm">{_p var='delete'}</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
{else}
    <div class="message">
        {_p var='no_promotions_found'}
    </div>
{/if}