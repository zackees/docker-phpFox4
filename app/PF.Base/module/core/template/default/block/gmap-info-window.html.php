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
    <div class="core-map-popup-item js-info_window js-gmap_item_info_window_{$aItem.id}" data-item-id="{$aItem.id}">
        <div class="item-inner">
            {plugin call='core.gmap_item_info_window_start'}
            <div class="item-title">
                <a href="{$aItem.item_link}">
                    {$aItem.item_title|clean}
                </a>
            </div>
            {plugin call='core.gmap_item_info_window_end'}
        </div>
        {plugin call='core.gmap_item_info_window_extra'}
    </div>
{/if}
