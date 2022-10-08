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
 * @package        Phpfox_Component
 * @version        $Id: invitations.class.php 3215 2011-10-05 14:40:56Z phpFox LLC $
 */
class Invite_Component_Controller_Invitations extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        Phpfox::isUser(true);

        if ($iInvite = $this->request()->getInt('del')) {
            $bIsAjaxDelete = $this->request()->get('ajax_delete', false);
            $bDel = Phpfox::getService('invite.process')->delete($iInvite, Phpfox::getUserId());
            $sDestination = 'invite.invitations';
            if (empty(Phpfox::getService('invite')->getPendingInvitationCount())
                && !Phpfox::getUserParam('invite.can_invite_friends')) {
                $sDestination = '';
            }
            if ($bDel) {
                if ($bIsAjaxDelete) {
                    return [
                        'success' => true,
                        'message' => _p('invitation_deleted')
                    ];
                }
                $this->url()->send($sDestination, null, _p('invitation_deleted'));
            } elseif ($bIsAjaxDelete) {
                return [
                    'success' => false,
                    'message' => _p('invitation_not_found')
                ];
            }
            $this->url()->send($sDestination, null, _p('invitation_not_found'));
        } elseif ($aInvite = $this->request()->get('val')) {
            $bDel = true;
            if (is_array($aInvite) && count($aInvite)) {
                foreach ($aInvite as $iInvite) {
                    $bDel = $bDel && Phpfox::getService('invite.process')->delete($iInvite, Phpfox::getUserId());
                }
            }
            $sDestination = 'invite.invitations';
            if (empty(Phpfox::getService('invite')->getPendingInvitationCount())
                && !Phpfox::getUserParam('invite.can_invite_friends')) {
                $sDestination = '';
            }
            if ($bDel) {
                $this->url()->send($sDestination, null, _p('invitation_deleted'));
            }
            $this->url()->send($sDestination, null, _p('invitation_not_found'));
        }

        $iPage = $this->request()->getInt('page', 1);
        $iPageSize = (int)Phpfox::getParam('invite.pendings_to_show_per_page');

        list($iCnt, $aInvites) = Phpfox::getService('invite')->get(Phpfox::getUserId(), $iPage, $iPageSize);

        if (empty($iCnt)) {
            Phpfox::getUserParam('invite.can_invite_friends', true);
        }

        Phpfox_Pager::instance()->set(['page' => $iPage, 'size' => $iPageSize, 'count' => $iCnt]);

        $this->setParam('global_moderation', [
                'name' => 'invitations',
                'ajax' => 'invite.moderation',
                'menu' => [
                    [
                        'phrase' => _p('Delete'),
                        'action' => 'delete',
                        'message' => _p('are_you_sure_you_want_yo_delete_selected_invitation_permanently'),
                    ],
                ],
            ]
        );

        $sectionMenus = [
            _p('pending_invitations') => 'invite.invitations',
        ];

        if (Phpfox::getUserParam('invite.can_invite_friends')) {
            $sectionMenus = array_merge([
                _p('invite_friends') => '',
            ], $sectionMenus);
        }

        $this->template()->setTitle(_p('pending_invitations'))
            ->setBreadCrumb(_p('pending_invitations'))
            ->setPhrase(['invitation_not_found', 'error'])
            ->assign([
                    'aInvites' => $aInvites,
                    'iPage' => $iPage,
                ]
            )
            ->setHeader('cache', [
                    'pending.js' => 'module_invite',
                ]
            )->buildSectionMenu('invite', $sectionMenus);
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('invite.component_controller_invitations_clean')) ? eval($sPlugin) : false);
    }
}