<?php
defined('PHPFOX') or exit('NO DICE!');
?>
<div class="wrapper-items item-container user-listing">
    {foreach from=$aUsers name=users item=aUser}
    <article class="user-item js_user_item_{$aUser.user_id}">
        {template file="user.block.rows_wide"}
    </article>
    {/foreach}
</div>
