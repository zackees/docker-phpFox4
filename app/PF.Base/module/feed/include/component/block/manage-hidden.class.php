<?php
defined('PHPFOX') or exit('NO DICE!');

class Feed_Component_Block_Manage_Hidden extends Phpfox_Component
{
    public function process()
    {
        if (!Phpfox::isUser() || !Phpfox::getParam('feed.enable_hide_feed', 1)) {
            return false;
        }
        $bSearch = false;
        $iPage = $this->request()->get('page') ? $this->request()->get('page') : 1;
        $iLimit = 12;
        $sName = $this->request()->get('name');
        $sType = $this->request()->get('type');
        $sCond = '';
        if ($sName != '' || $sType != '') {
            $bSearch = true;
            $sCond = " AND user.full_name LIKE '%" . $sName . "%'";
            if ($sType == 'friend')
                $sCond .= ' AND user.profile_page_id = 0';
            elseif ($sType == 'page')
                $sCond .= ' AND user.profile_page_id > 0 AND page.item_type = 0';
            elseif ($sType == 'group')
                $sCond .= ' AND user.profile_page_id > 0 AND page.item_type = 1';
        }

        list($iCnt, $aHides) = Phpfox::getService('feed.hide')->getHiddenUsers(Phpfox::getUserId(), $sType, $sCond, $iPage, $iLimit);
        if ($iCnt) {
            Phpfox::getLib('pager')->set(['page' => $iPage, 'popup' => true, 'size' => $iLimit, 'count' => $iCnt, 'ajax' => 'feed.manageHidden', 'aParams' => ['name' => $sName, 'type' => $sType]]);
        }
        $this->template()->assign([
            'iCnt' => $iCnt,
            'bSearch' => $bSearch,
            'iPage' => $iPage,
            'aHides' => $aHides
        ]);
        return 'block';
    }

    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('feed.component_block_manage_hidden_clean')) ? eval($sPlugin) : false);
    }
}