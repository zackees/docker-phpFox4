<?php 
defined('PHPFOX') or exit('NO DICE!');

?>
<div class="table-responsive" style="margin: 0px">
    <table class="table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th class="w140">{_p var='user'}</th>
                <th class="w140">{_p var='category'}</th>
                <th>{_p var='feedback'}</th>
                <th class="w140">{_p var='date'}</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$aReports item=aReport}
                <tr>
                    <td>{$aReport|user}</td>
                    <td>{_p var=$aReport.message}</td>
                    <td>{$aReport.feedback}</td>
                    <td>{$aReport.added|date:'core.global_update_time'}</td>
                </tr>
            {/foreach}
        </tbody>
    </table>
</div>