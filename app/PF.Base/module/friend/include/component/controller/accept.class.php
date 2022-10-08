<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 *
 *
 * @copyright       [PHPFOX_COPYRIGHT]
 * @author          phpFox LLC
 * @package         Phpfox_Component
 */
class Friend_Component_Controller_Accept extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        Phpfox::isUser(true);
        $iPage = $this->request()->getInt('page');
        $iLimit = Phpfox::getParam('friend.total_requests_display');
        $iRequestId = $this->request()->getInt('id');

        list($iCnt, $aFriends) = Phpfox::getService('friend.request')->get($iPage, $iLimit, $iRequestId);
        Phpfox_Pager::instance()->set(['page' => $iPage, 'size' => $iLimit, 'count' => $iCnt]);
        Phpfox::getService('friend')->buildMenu();
        $this->setParam('global_moderation', [
                'name' => 'friend',
                'ajax' => 'friend.moderation',
                'menu' => [
                    [
                        'phrase' => _p('accept'),
                        'action' => 'accept'
                    ],
                    [
                        'phrase' => _p('deny'),
                        'action' => 'deny'
                    ]
                ]
            ]
        );

        $this->template()->setTitle(_p('incoming_requests'))
            ->setBreadCrumb(_p('incoming_requests'), $this->url()->makeUrl('friend'))
            ->assign([
                    'aFriends' => $aFriends,
                    'iRequestId' => $iRequestId,
                    'bIsFriendController' => true
                ]
            );

        return null;
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('friend.component_controller_accept_clean')) ? eval($sPlugin) : false);
    }
}
