<?php
defined('PHPFOX') or exit('NO DICE!');
?>
<form method="post" class="form" action="{url link='admincp.user.promotion.add'}">
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="panel-title">{_p var='promotion_details'}</div>
        </div>
        <div class="panel-body">
            {if $bIsEdit}
            <input type="hidden" name="id" value="{$aForms.promotion_id}" />
            {/if}
            <div class="form-group">
                <label for="user_group">{required}{_p var='user_group'}</label>
                <select name="val[user_group_id]" class="form-control close_warning">
                    <option value="">{_p var='select'}:</option>
                    {foreach from=$aUserGroups item=aUserGroup}
                    <option value="{$aUserGroup.user_group_id}"{value id='user_group_id' type='select' default=$aUserGroup.user_group_id}>{$aUserGroup.title}</option>
                    {/foreach}
                </select>
            </div>
            <div class="form-group">
                <label for="total_activity">{required}{_p var='total_activity_points'}</label>
                <input id="total_activity" class="form-control close_warning" type="number" min="1" name="val[total_activity]" value="{value id='total_activity' type='input'}" size="10" />
            </div>
            <div class="form-group">
                <label for="total_day">{required}{_p var='total_days_registered'}</label>
                <input class="form-control close_warning" type="number" min="1" id="total_day" name="val[total_day]" value="{value id='total_day' type='input'}" size="5" />
            </div>
            <div class="form-group">
                <label for="promotion_rule">{required}{_p var='rule_to_check_promotion'}</label>
                <div class="custom-radio-wrapper">
                    <label><input name="val[rule]" type="radio" value="0" class="close_warning" {if empty($aForms) ||( isset($aForms) && !$aForms.rule)}checked{/if}><span class="custom-radio"></span>{_p var='promotion_rule_or'}</label>
                </div>
                <div class="custom-radio-wrapper">
                    <label><input name="val[rule]" type="radio" value="1" class="close_warning" {if isset($aForms) && $aForms.rule}checked{/if}><span class="custom-radio"></span>{_p var='promotion_rule_and'}</label>
                </div>
            </div>
            <div class="form-group">
                <label for="move_to">{required}{_p var='upgraded_user_group'}</label>
                <select name="val[upgrade_user_group_id]" class="form-control close_warning">
                    <option value="">{_p var='select'}:</option>
                    {foreach from=$aUserGroups item=aUserGroup}
                    <option value="{$aUserGroup.user_group_id}"{value id='upgrade_user_group_id' type='select' default=$aUserGroup.user_group_id}>{$aUserGroup.title}</option>
                    {/foreach}
                </select>
                <p class="help-block promotion-user-group-description" style="font-size: 14px;">{_p var='upgraded_user_group_description'}</p>
            </div>
        </div>
        <div class="panel-footer">
            <input type="submit" value="{_p var='submit'}" class="btn btn-primary" />
        </div>
    </div>
</form>