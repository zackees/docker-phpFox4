<?php
defined('PHPFOX') or exit('NO DICE!');
?>

<div>
    {if count($aUsers)}
    <p class="block-help mb-2">{$sHelpText}</p>
    <div class="">
        {foreach from=$aUsers item=aUser}
            <div class="item-block-user">
                <div class="item-outer">
                    <div class="item-avatar" >
                        {img user=$aUser max_width=50 max_height=50 suffix='_120_square'}
                    </div>
                    <div class="item-inner">
                        <div class="item-info mb-1">{$aUser|user}</div>
                        <a href="#?call=user.block&amp;height=120&amp;width=400&amp;user_id={$aUser.user_id}" class="inlinePopup btn btn-danger btn-sm" title="{_p var='block_actual'}">
                            {_p var='block_actual'}
                        </a>
                    </div>
                </div>
            </div>
        {/foreach}
    </div>
    {else}
        <div>{_p var='no_results_found'}</div>
    {/if}
</div>
