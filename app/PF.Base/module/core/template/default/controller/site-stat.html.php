<?php
defined('PHPFOX') or exit('NO DICE!');
?>
<div class="block site-stat-container">
    {foreach from=$aStats key=sKey item=aStatItem}
        <div class="site-stat-group">
            <div class="title">
                {$sKey}
            </div>
            <div class="content">
                {if !empty($aStatItem.items)}
                    {foreach from=$aStatItem.items item=aStat}
                        <div class="info">
                            <div class="info_left" style="text-transform: capitalize">
                                {$aStat.phrase}:
                            </div>
                            <div class="info_right">
                                {if isset($aStat.link)}<a href="{$aStat.link}">{/if}{$aStat.value}{if isset($aStat.link)}</a>{/if}
                            </div>
                        </div>
                    {/foreach}
                {else}
                    {foreach from=$aStatItem item=aStat}
                        <div class="info">
                            <div class="info_left" style="text-transform: capitalize">
                                {$aStat.phrase}:
                            </div>
                            <div class="info_right">
                                {if isset($aStat.link)}<a href="{$aStat.link}">{/if}{$aStat.value}{if isset($aStat.link)}</a>{/if}
                            </div>
                        </div>
                    {/foreach}
                {/if}
            </div>
            {if !empty($aStatItem.view_all_link)}
                <div class="bottom">
                    <ul>
                        <li class="first">
                            <a href="{$aStatItem.view_all_link}">
                                {if !empty($aStatItem.view_all_label)}
                                    {$aStatItem.view_all_label}
                                {else}
                                    {_p var='view_all'}
                                {/if}
                            </a>
                        </li>
                    </ul>
                </div>
            {/if}
        </div>
    {/foreach}
</div>