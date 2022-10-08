<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 *
 *
 * @copyright        [PHPFOX_COPYRIGHT]
 * @author        phpFox LLC
 * @package        Module_Invite
 * @version        $Id: ajax.class.php 3342 2011-10-21 12:59:32Z phpFox LLC $
 */
class Invite_Component_Ajax_Ajax extends Phpfox_Ajax
{
    public function moderation()
    {
        $aInvite = $this->get('item_moderate');
        $iSuccessCount = 0;
        if (is_array($aInvite) && count($aInvite)) {
            foreach ($aInvite as $iInvite) {
                if (Phpfox::getService('invite.process')->delete($iInvite, Phpfox::getUserId())) {
                    $iSuccessCount++;
                }
                $this->call('$Core.invite.action.updateView(' . $iInvite . ');');
            }
        }

        if (empty(Phpfox::getService('invite')->getPendingInvitationCount()) && !Phpfox::getUserParam('invite.can_invite_friends')) {
            Phpfox::addMessage(_p('invitations_successfully_deleted'));
            $this->call('window.location.href = "' . Phpfox::getLib('url')->makeUrl('') . '";');
        } else {
            $this->alert(_p($iSuccessCount ? 'invitations_successfully_deleted' : 'unable_to_find_invitations_you_plan_to_delete'), _p('moderation'), 300, 150, true);
            $this->hide('.moderation_process');
        }
    }
}