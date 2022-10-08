<?php 
defined('PHPFOX') or exit('NO DICE!');
?>
<div class="user-promotion-container">
    <div class="item-total">
        <div class="item-total-info"><i class="ico ico-user1-clock-o"></i> <div class="item-number"><span>{$iTotalDays}</span> {_p var='days_membership'}</div></div>
        <div class="item-total-info"><i class="ico ico-star-circle-o"></i> <div class="item-number"><span>{$iTotalPoints}</span> {_p var='activity_points'}</div></div>
    </div>
    {if count($aPromotions)}
        <div class="item-detail">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{_p var='Promotion'}</th>
                        <th>{_p var='Conditions'}</th>
                    </tr>
                </thead>

                <tbody>
                    {foreach from=$aPromotions name=promotions item=aPromotion}
                    <tr>
                        <td>{$aPromotion.promotion_id}</td>
                        <td>{_p var=$aPromotion.upgrade_user_group_title}</td>
                        <td>{$aPromotion.total_day} {_p var='days_membership'} <span class="fw-bold">{if $aPromotion.rule}{_p var='and'}{else}{_p var='or'}{/if}</span> {$aPromotion.total_activity} {_p var='activity_points'}</td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    {else}
        <div class="message">
            {_p var='congratulations_you_have_been_promoted_to_the_following_user_group_title' title=$aUserGroup.title}
        </div>
    {/if}
</div>