<?php
defined('PHPFOX') or exit('NO DICE!');
?>
{if empty($aWords)}
    <div class="t_center">{_p var='no_search_word_found'}</div>
{else}
    <div class="table-responsive">
        <table class="table table-admin" id="js_core_site_stat">
            <thead>
            <tr>
                <th>{_p var='search_word'}</th>
                <th>{_p var='total'}</th>
            </tr>
            </thead>
            <tbody>
                {foreach from=$aWords item=aWord}
                    <tr>
                        <td>
                            {$aWord.search_word}
                        </td>
                        <td>
                            {$aWord.total}
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
        {pager}
    </div>
{/if}
