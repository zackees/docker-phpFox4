<?php
/**
 * [PHPFOX_HEADER]
 *
 * @copyright        [PHPFOX_COPYRIGHT]
 * @author        phpFox LLC
 * @package        Phpfox
 * @version        $Id: gmap-card-item.html.php 3326 2019-08-12 09:12:45Z phpFox LLC $
 */

defined('PHPFOX') or exit('NO DICE!');

?>
{if !empty($aItem)}
    <div class="item-outer">
        {plugin call='core.gmap_card_item_start'}
        <div class="item-outer-wrapper">
            {if !empty($aItem.item_image)}
                <div class="item-media-wrapper">
                    <div class="item-media">
                        <a href="{$aItem.item_link}">
                            <span class="item-media-src" style="background-image:url('{$aItem.item_image}')"></span>
                        </a>
                    </div>
                </div>
            {/if}
            {if !empty($aItem.item_is_featured) || !empty($aItem.item_is_sponsor)}
                <div class="item-flag-icon">
                    {if !empty($aItem.item_is_featured)}
                        <div class="sticky-label-icon sticky-featured-icon">
                            <span class="flag-style-arrow"></span>
                            <i class="ico ico-diamond"></i>
                        </div>
                    {/if}
                    {if !empty($aItem.item_is_sponsor)}
                        <div class="sticky-label-icon sticky-sponsored-icon">
                            <span class="flag-style-arrow"></span>
                           <i class="ico ico-sponsor"></i>
                        </div>
                    {/if}
                    {plugin call='core.gmap_card_item_flag'}
                </div>
            {/if}
            <div class="item-inner">
                {plugin call='core.gmap_card_item_info_start'}
                <div class="item-title-wrapper">
                    <div class="item-title">
                        <a href="{$aItem.item_link}">
                            {$aItem.item_title|clean}
                        </a>
                    </div>
                    <!-- main actions -->
                    {if !empty($aItem.item_actions)}
                        <div class="item-main-action">
                            <div class="dropdown">
                                <a type="button" class="btn dropdown-toggle" data-toggle="dropdown">
                                    <i class="ico ico-gear-o"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-right">
                                    {$aItem.item_actions}
                                </ul>
                            </div>
                        </div>
                    {/if}
                </div>
                {if !empty($aItem.item_price)}
                    <div class="item-price">
                        {$aItem.item_price}
                    </div>
                {/if}
                {if !empty($aItem.item_author)}
                    <div class="item-minor-info">
                        {_p var='By'} {$aItem.item_author|user}
                    </div>
                {/if}
                <!-- label status and date -->
                {if !empty($aItem.item_date) || !empty($aItem.item_label)}
                    <div class="item-info-row">
                        {if !empty($aItem.item_label)}
                            <div class="item-status text-uppercase">
                                <span class="core-label-status solid {if !empty($aItem.item_label_class)}{$aItem.item_label_class}{/if}">{$aItem.item_label}</span>
                            </div>
                        {/if}
                        {if !empty($aItem.item_date)}
                            <div class="item-minor-info">
                                {$aItem.item_date}
                            </div>
                        {/if}
                    </div>
                {/if}
                <!-- statistics -->
                {if !empty($aItem.item_statistics)}
                    <div class="item-info-row with-dots-separate">
                        {foreach from=$aItem.item_statistics name=statistic item=statistic}
                            <div class="item-minor-info">
                                {if !empty($statistic.label)}{$statistic.label} {/if}<span class="item-info-highlight fw-bold">{$statistic.value}</span>
                            </div>
                        {/foreach}
                    </div>
                {/if}
                <!-- rating -->
                {if !empty($aItem.item_rating)}
                    <div class="core-outer-rating core-outer-rating-row mini">
                        <div class="core-outer-rating-row">
                            <div class="core-rating-count-star">{$aItem.item_rating.number}</div>
                             <div class="core-rating-star">
                                 {for $i = 0; $i < 5; $i++}
                                    {if ($i < (int)$aItem.item_rating.number)}
                                        <i class="ico ico-star"></i>
                                    {elseif ((round($aItem.item_rating.number) - $aItem.item_rating.number) > 0) && ($aItem.item_rating.number - $i) > 0}
                                        <i class="ico ico-star half-star"></i>
                                    {else}
                                        <i class="ico ico-star disable"></i>
                                    {/if}
                                 {/for}
                            </div>
                        </div>
                        {if !empty($aItem.item_rating.count)}
                            <div class="core-rating-count-review-wrapper">
                                <span class="core-rating-count-review">
                                    <span class="item-number">{$aItem.item_rating.count}</span>
                                </span>
                            </div>
                        {/if}
                    </div>
                {/if}
                <!-- minor info description, location ... -->
                <div class="item-minor-info">
                    {if !empty($aItem.item_first_minor_info)}
                        <div class="item-info">{$aItem.item_first_minor_info}</div>
                    {/if}
                    {if !empty($aItem.item_second_minor_info)}
                        <div class="item-info">{$aItem.item_second_minor_info}</div>
                    {/if}
                    {plugin call='core.gmap_card_item_minor_info'}
                </div>
                <!-- specific action and members -->
                {if !empty($aItem.item_action_specific) || !empty($aItem.item_members)}
                    <div class="item-action-specific">
                        {plugin call='core.gmap_card_item_action_specific'}
                        {if !empty($aItem.item_action_specific)}
                            {$aItem.item_action_specific}
                        {/if}
                        {if !empty($aItem.item_members)}
                            <div class="item-member-list">
                                {foreach from=$aItem.item_members.list name=member item=member key=key}
                                    <div class="item-member">
                                        {img user=$member suffix='_120_square'}
                                    </div>
                                {/foreach}
                                {if $aItem.item_members.total_remaining > 0}
                                    <div class="item-count">
                                        <a onclick="{$aItem.item_members.all_click}" href="{$aItem.item_members.all_href}">
                                            +{$aItem.item_members.total_remaining|short_number}
                                        </a>
                                    </div>
                                {/if}
                            </div>
                        {/if}
                    </div>
                {/if}
                {plugin call='core.gmap_card_item_info_end'}
            </div>
        </div>
        {plugin call='core.gmap_card_item_end'}
    </div>
{/if}
