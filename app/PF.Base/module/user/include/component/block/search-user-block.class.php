<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Block_Search_User_Block
 */
class  User_Component_Block_Search_User_Block extends Phpfox_Component
{
    public function process()
    {
        Phpfox::isUser(true);
        $sQuerySearch = $this->getParam('query_search');

        $iLimit = 20;
        $iPage = 1;

        $aUsers = Phpfox::getService('user')->getSearchUsersToBlock($sQuerySearch, $iPage, $iLimit);

        $this->template()->assign([
            'aUsers'    => $aUsers,
            'sHelpText' => _p('list_results_for_people_includes_words_similar', ['query' => $sQuerySearch])
        ]);
    }
}