<?php
defined('PHPFOX') or exit('NO DICE!');
?>
{if $bIsSearch || !empty($aFeedbacks)}
<form method="get" class="form-search" action="{url link='admincp.user.cancellations.feedback'}">
    {if !empty($sCurrentSort)}
    <input type="hidden" name="sort" value="{$sCurrentSort}">
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
            </div>
            <div class="form-btn-group">
                <div class="pull-left">
                    <button type="submit" class="btn btn-primary" name="search[submit]"><i class="fa fa-search" aria-hidden="true"></i> {_p var='search'}</button>
                    <a class="btn btn-default" href="{url link='admincp.user.cancellations.feedback'}">{_p var='reset'}</a>
                </div>
            </div>
        </div>
    </div>
</form>
{/if}
{if empty($aFeedbacks)}
<div class="alert alert-info">
    {if $bIsSearch}
    {_p var='no_results_found'}
    {else}
    {_p var='no_feedback_to_review'}
    {/if}
</div>
{else}
<div class="table-responsive">
    <table class="table table-admin">
        <thead>
        <tr>
            <th {table_sort class="w200" asc="udf.full_name asc" desc="udf.full_name desc" query="sort" current=$sCurrent}> {_p var='full_name'} </th>
            <th {table_sort class="w100" asc="udf.user_email asc" desc="udf.user_email desc" query="sort" current=$sCurrent}> {_p var='e_mail'} </th>
            {if Phpfox::getParam('core.enable_register_with_phone_number')}
                <th {table_sort class="w130" asc="udf.user_phone asc" desc="udf.user_phone desc" query="sort" current=$sCurrent}>{_p var='phone_number'}</th>
            {/if}
            <th {table_sort class="w150" asc="ug.title asc" desc="ug.title desc" query="sort" current=$sCurrent}> {_p var='user_group'} </th>
            <th class=""> {_p var='reasons_given'} </th>
            <th {table_sort class="w200" asc="udf.feedback_text asc" desc="udf.feedback_text desc" query="sort" current=$sCurrent}> {_p var='feedback_text'} </th>
            <th {table_sort class="w200" asc="udf.time_stamp asc" desc="udf.time_stamp desc" query="sort" current=$sCurrent}> {_p var='deleted_on'} </th>
            <th class="w50">{_p var='options'}</th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$aFeedbacks item=aFeedback key=iKey name=feedback}
        <tr id="js_feedback_{$aFeedback.feedback_id}">
            <td>
                {$aFeedback.full_name}
            </td>
            <td>
                {if !empty($aFeedback.user_email)}{$aFeedback.user_email}{/if}
            </td>
            {if Phpfox::getParam('core.enable_register_with_phone_number')}
                <td>{if !empty($aFeedback.user_phone)}{$aFeedback.user_phone|phone}{/if}</th>
            {/if}
            <td>
                {_p var=$aFeedback.user_group_title}
            </td>
            <td>
                {if isset($aFeedback.reasons)}
                    {foreach from=$aFeedback.reasons item=phrase_var}
                        {$phrase_var} <br>
                    {/foreach}
                {/if}
            </td>
            <td>
                {$aFeedback.feedback_text|clean|shorten:'15':'View More':true|split:30}
            </td>
            <td>
                {$aFeedback.time_stamp|date:'core.global_update_time'}
            </td>
            <td>
                <a href="#" onclick="$.ajaxCall('user.deleteFeedback', 'iFeedback={$aFeedback.feedback_id}')">{_p var='delete'}</a>
            </td>
        </tr>
        {/foreach}
        </tbody>
    </table>
</div>
{/if}