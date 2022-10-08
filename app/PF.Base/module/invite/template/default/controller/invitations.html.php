<?php
/**
 * [PHPFOX_HEADER]
 *
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		phpFox LLC
 * @package 		Phpfox
 * @version 		$Id: invitations.html.php 3215 2011-10-05 14:40:56Z phpFox LLC $
 */

defined('PHPFOX') or exit('NO DICE!');

?>
{if count($aInvites)}
    {if !PHPFOX_IS_AJAX}
    <form class="form" method="post" action="{url link='current'}" id="js_form">
        <div class="invitation-container">
    {/if}
        {foreach from=$aInvites name=invite item=aInvite}
        <div id="js_invite_{$aInvite.invite_id}" class="invitation-item js_selector_class_{$aInvite.invite_id}">
            {item name="Invitation"}
            <div class="moderation_row">
                <label class="item-checkbox">
                    <input type="checkbox" class="js_global_item_moderate" name="item_moderate[]" value="{$aInvite.invite_id}" id="check{$aInvite.invite_id}" />
                    <i class="ico ico-square-o"></i>
                </label>
            </div>
            <div class="item-title">
                <span class="js-invitation-count">{$aInvite.count}</span>. {$aInvite.email}
            </div>
            <div class="item-delete">
                <a href="javascript:void(0)" data-message="{_p var='are_you_sure_you_want_to_delete_this_pending_invitation_permanently'}"
                   data-id="{$aInvite.invite_id}" onclick="$Core.invite.action.delete(this);"><span class="ico ico-trash-o"></span></a>
            </div>
            {/item}
        </div>
        {/foreach}
    {if Phpfox::getParam('invite.pendings_to_show_per_page') > 0}
        {pager}
    {/if}
    {if !PHPFOX_IS_AJAX}
        </div>
    </form>
    {/if}
    {moderation}
{else}
    {if !PHPFOX_IS_AJAX}
        <div class="extra_info">
            {_p var='there_are_no_pending_invitations'}
            {if Phpfox::getParam('user.allow_user_registration')}
                <ul class="action">
                    <li><a href="{url link='invite'}">{_p var='invite_your_friends'}</a></li>
                </ul>
            {/if}
        </div>
    {/if}
{/if}