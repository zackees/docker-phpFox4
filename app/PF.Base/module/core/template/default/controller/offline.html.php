<?php 
/**
 * [PHPFOX_HEADER]
 * 
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		phpFox LLC
 * @package 		Phpfox
 * @version 		$Id: offline.html.php 681 2009-06-15 20:24:37Z phpFox LLC $
 */
 
defined('PHPFOX') or exit('NO DICE!'); 

?>
{if !empty($aStaticPage.page_id)}
    <div class="offline_static_page">
        {if $aStaticPage.parse_php}{$aStaticPage.text_parsed|eval}{else}{$aStaticPage.text_parsed}{/if}
    </div>
{else}
    <div class="offline_message">
        {$sOfflineMessage}
    </div>
{/if}