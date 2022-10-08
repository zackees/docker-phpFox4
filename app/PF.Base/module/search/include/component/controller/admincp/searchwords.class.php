<?php
defined('PHPFOX') or exit('NO DICE!');

class Search_Component_Controller_Admincp_Searchwords extends Phpfox_Component
{
    public function process()
    {
        Phpfox::isAdmin(true);
        $iLimit = 10;
        $iPage = $this->request()->getInt('page', 1);


        list($iCnt, $aWords) = Phpfox::getService('search')->getSearchWords($iPage, $iLimit);

        // Set pager
        $aParamsPager = [
            'page'        => $iPage,
            'size'        => $iLimit,
            'count'       => $iCnt,
        ];

        $oPager = Phpfox::getLib('pager');
        $oPager->set($aParamsPager);

        $this->template()->setTitle(_p('search_words'))
            ->setBreadCrumb(_p('search_words'))
            ->setActiveMenu('admincp.maintain.searchwords')
            ->assign([
                'aWords' => $aWords,
            ]);
    }
}