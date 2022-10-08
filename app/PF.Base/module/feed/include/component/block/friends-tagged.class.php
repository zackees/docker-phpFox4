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
class Feed_Component_Block_Friends_Tagged extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $iPage = $this->request()->getInt('page', 1); // pagination page
        $iPerPage = Phpfox::getParam('core.items_per_page', 10); // max likes per page

        $sTypeId = $this->request()->get('type_id');
        $iItemId = $this->request()->getInt('item_id');

        $aTaggedUsers = Phpfox::getService('feed.tag')->getTaggedUsers($iItemId, $sTypeId, false, $iPage, $iPerPage);
        $iTotalTaggedUsers = Phpfox::getService('feed.tag')->getTaggedUsers($iItemId, $sTypeId, true);

        $sErrorMessage = '';
        if($iTotalTaggedUsers == 0) {
            $sErrorMessage = _p('No friends');
        }

        // Pagination configuration
        $pager = Phpfox_Pager::instance();
        $pager->set(array(
            'page' => $iPage,
            'size' => $iPerPage,
            'count' => $iTotalTaggedUsers,
            'paging_mode' => 'loadmore',
            'ajax_paging' => [
                'block' => 'feed.friends-tagged',
                'params' => [
                    'type_id' => $sTypeId,
                    'item_id' => $iItemId
                ],
                'container' => '.popup-user-with-btn-container'
            ]
        ));

        (($sPlugin = Phpfox_Plugin::get('like.component_block_browse_process')) ? eval($sPlugin) : false);

        $this->template()->assign(array(
                'aTaggedUsers' => $aTaggedUsers,
                'sItemType' => $sTypeId,
                'iItemId' => $iItemId,
                'bIsPaging' => $this->getParam('ajax_paging', 0),
                'hasPagingNext' => $iPage < $pager->getTotalPages(),
                'sErrorMessage' => $sErrorMessage,
            )
        );
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('feed.component_block_friends_tagged_clean')) ? eval($sPlugin) : false);
    }
}
