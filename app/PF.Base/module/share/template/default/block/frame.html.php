<?php 
/**
 * [PHPFOX_HEADER]
 * 
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		phpFox LLC
 * @package  		Module_Share
 * @version 		$Id: frame.html.php 6769 2013-10-11 09:08:02Z phpFox LLC $
 */
 
defined('PHPFOX') or exit('NO DICE!'); 

?>
{if Phpfox::isUser() && $iFeedId > 0}
    {module name='feed.share' type=$sBookmarkType url=$sBookmarkUrl}
{else}
    {module name='share.friend' type=$sBookmarkType url=$sBookmarkUrl title=$sBookmarkTitle}
    <script type="text/javascript">$Core.loadInit();</script>
{/if}