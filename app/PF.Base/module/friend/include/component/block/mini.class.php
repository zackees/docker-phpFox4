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
 * @package         Module_Friend
 * @version         $Id: mini.class.php 5844 2013-05-09 08:00:59Z phpFox LLC $
 */
class Friend_Component_Block_Mini extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        (($sPlugin = Phpfox_Plugin::get('friend.component_block_mini_process')) ? eval($sPlugin) : false);

        $iLimit = $this->getParam('limit', 7);

        if ($iLimit == 0 || Phpfox::getUserBy('profile_page_id') || !Phpfox::isUser()) {
            return false;
        }

        (($sPlugin = Phpfox_Plugin::get('friend.component_block_mini_process_query')) ? eval($sPlugin) : false);

        if (!isset($iCnt) || !isset($aRows)) {
            list($iCnt, $aRows) = Phpfox::getService('friend')->get('friend.is_page = 0 AND friend.user_id = ' . Phpfox::getUserId(),
                'ls.last_activity DESC', 0, $iLimit, true, false, true);
        }

        $this->template()->assign(array(
                'sHeader' => _p('friends_online'),
                'aFriends' => $aRows,
                'iTotalFriendsOnline' => $iCnt,
                'redis_enabled' => false,
                'iRemainCount' => $iCnt - count($aRows)
            )
        );

        if ($iCnt > count($aRows)) {
            $this->template()->assign('aFooter', [
                _p('more') => [
                    'link' => 'javascript:void(0)',
                    'attr' => 'onclick="$Core.box(\'friend.browseOnline\', 400);"'
                ]
            ]);
        }

        return 'block';
    }

    /**
     * Settings of block
     * @return array
     */
    public function getSettings()
    {
        return [
            [
                'info' => _p('friend_block_mini_limit_info'),
                'description' => _p('friend_block_mini_limit_description'),
                'value' => 7,
                'var_name' => 'limit',
                'type' => 'integer',
            ]
        ];
    }

    public function getValidation(){
        return [
            'limit'=> [
                'def' => 'int:required',
                'min' => 0,
                'title'=>'Invalid number'
            ]
        ];
    }
}