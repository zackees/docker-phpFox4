<?php
/**
 * [PHPFOX_HEADER]
 *
 * @copyright        [PHPFOX_COPYRIGHT]
 * @author        phpFox LLC
 * @package        Phpfox
 * @version        $Id: gmap-entry-view.html.php 3326 2019-08-14 09:12:45Z phpFox LLC $
 */

defined('PHPFOX') or exit('NO DICE!');

?>
{if !empty($lat) && !empty($lng)}
    {plugin call='core.gmap_entry_view_start'}
    <div class="{if !empty($class_name)}{$class_name}{else}js-entry_map_view{/if}"
         data-lat="{$lat}"
         data-lng="{$lng}"
         data-height="{if !empty($map_height)}{$map_height}{else}400px{/if}"
         data-width="{if !empty($map_width)}{$map_width}{else}100%{/if}"
    ></div>
    {plugin call='core.gmap_entry_view_end'}
{literal}
    <script>
      $Ready(function () {
        if ($Core.Gmap && typeof $Core.Gmap.initGoogle == 'function' && typeof $Core.Gmap.addEntryMapView == 'function') {
          $Core.Gmap.initGoogle('addEntryMapView', {eleId: '.' + '{/literal}{if !empty($class_name)}{$class_name}{else}js-entry_map_view{/if}{literal}'});
        }
      });
    </script>
{/literal}
{/if}

