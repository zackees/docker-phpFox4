<?php 
/**
 * [PHPFOX_HEADER]
 * 
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		phpFox LLC
 * @package  		Module_Core
 * @version 		$Id: index-member.html.php 2817 2011-08-08 16:59:43Z phpFox LLC $
 */
 
defined('PHPFOX') or exit('NO DICE!'); 

?>
{if count($aPhpfoxNews) }
    {if $isSlide}
        <div id="carousel-fox-news" class="carousel slide block" data-ride="carousel">
            <ol class="carousel-indicators">
                {foreach from=$aPhpfoxNews name=news item=aNewsSlide key=iKey}
                    <li data-target="#carousel-fox-news" data-slide-to="{$iKey}" class="{if $iKey==0} active {/if}"></li>
                {/foreach}
            </ol>
            <div class="carousel-inner slider-fox-news-container">
                {foreach from=$aPhpfoxNews key=iKey name=news item=aNewsSlide}
                <div class="item {if $iKey==0} active {/if}">
                    <div class="item-outer">
                        {if $aNewsSlide.image}
                        <div class="item-media">
                            <span style="background-image: url({$aNewsSlide.image})"></span>
                        </div>
                        {/if}
                        <div class="item-inner">
                            <div class="carousel-caption">
                                <div class="item-title">
                                    <a href="{$aNewsSlide.link}" target="_blank">{$aNewsSlide.title|clean}</a>
                                </div>
                                <div class="item-info">
                                    <span>{_p var='by'} {$aNewsSlide.creator}</span>
                                    <span>{$aNewsSlide.time_stamp}</span>
                                </div>
                                <div class="item-desc">
                                    {$aNewsSlide.description|striptag|stripbb}
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                {/foreach}

            </div>
            <div class="controllers">
                <!-- Controls -->
                <a class="left carousel-control" href="#carousel-fox-news" data-slide="prev">
                    <span class="ico ico-angle-left"></span>
                </a>
                <a class="right carousel-control" href="#carousel-fox-news" data-slide="next">
                    <span class="ico ico-angle-right"></span>
                </a>
            </div>
        </div>
    {else}
        <div class="block news-updates-container">
            <div class="title">
                {_p var='more_news_and_update'}
            </div>
            <div class="content">
                {foreach from=$aPhpfoxNews name=news item=aNews}
                <div class="item-separated">
                    <a href="{$aNews.link}" target="_blank">{$aNews.title|clean}</a>
                    <div class="text-muted">
                        <span>{_p var='posted_by'} {$aNews.creator}</span>
                        <span>{$aNews.time_stamp}</span>
                    </div>
                </div>
                {/foreach}
            </div>
            <div class="bottom">
                <ul>
                    <li id="js_block_bottom_1" class="first">
                        <a href="https://www.phpfox.com/blog/" target="_blank" id="js_block_bottom_link_1">
                            {_p var='view_all'}
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    {/if}
{/if}