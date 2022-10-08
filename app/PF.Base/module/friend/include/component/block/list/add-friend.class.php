<?php
defined('PHPFOX') or exit('NO DICE!');

class Friend_Component_Block_List_Add_Friend extends Phpfox_Component
{
    public function process()
    {
        Phpfox::isUser(true);
        $iListId = $this->getParam('list_id');

        if(!($aList = Phpfox::getService('friend.list')->getList($iListId, Phpfox::getUserId())))
        {
            return Phpfox_Error::display(_p('invalid_friend_list'));
        }

        $aFriendsInList = Phpfox::getService('friend.list')->getUsersByListId($iListId);

        $aIds = !empty($aFriendsInList) ? array_column($aFriendsInList,'user_id') : [];
        $this->template()->assign([
            'aFriendListMembers' => json_encode($aIds),
            'aSelectedIds' => $aIds,
            'sSelectedIds' => implode(',', $aIds),
            'list_id' => $iListId
        ]);

        return 'block';

    }
}