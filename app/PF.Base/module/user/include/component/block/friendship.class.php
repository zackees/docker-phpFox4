<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Block_Friendship
 */
class  User_Component_Block_Friendship extends Phpfox_Component
{
    public function process()
    {
        $viewer_id = Phpfox::getUserId();
        $user_id = $this->getParam('friend_user_id');
        // default value
        $is_friend = false;
        $bShowExtra = false;
        $iMutualCount = 0;
        $aMutualFriends = [];

        if (!$viewer_id) {
            return false;
        }

        if ($viewer_id == $user_id) {
            return false;
        }

        $requested = $request_id = $is_ignore_request = false;
        if (Phpfox::isModule('friend')) {
            $is_friend = Phpfox::getService('friend')->isFriend($viewer_id, $user_id);
            if (!$is_friend) {
                $is_friend = (Phpfox::getService('friend.request')->isRequested($viewer_id, $user_id, false, true) ? 2 : false);
            }
            $is_ignore_request = Phpfox::getService('friend.request')->isDenied($viewer_id, $user_id);
            if ($bShowExtra = $this->getParam('extra_info', false)) {
                list($iMutualCount, $aMutualFriends) = Phpfox::getService('friend')->getMutualFriends($user_id, 1);
            }

            $requested = Phpfox::getService('friend.request')->isRequested($user_id, $viewer_id, false, true);
            $request_id = Phpfox::getService('friend.request')->isRequested($viewer_id, $user_id, true);
        }
        $bLoginAsPage = Phpfox::getUserBy('profile_page_id') > 0;
        $iMutualRemain = $iMutualCount - count($aMutualFriends);

        $this->template()->assign([
            'bLoginAsPage'      => $bLoginAsPage,
            'user_id'           => $user_id,
            'is_friend'         => $is_friend,
            'is_ignore_request' => $is_ignore_request,
            'type'              => $this->getParam('type', 'string'),
            'show_extra'        => $bShowExtra,
            'no_button'         => $this->getParam('no_button', false),
            'no_mutual_list'    => $this->getParam('mutual_list', false),
            'mutual_count'      => $iMutualCount,
            'mutual_list'       => $aMutualFriends,
            'mutual_remain'     => $iMutualRemain,
            'is_module_friend'  => Phpfox::isModule('friend'),
            'requested'         => $requested,
            'request_id'        => $request_id
        ]);

        return null;
    }
}
