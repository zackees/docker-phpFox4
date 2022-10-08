<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Like_Component_Block_Link extends Phpfox_Component
{
    public function process()
    {
        $sModule = $sItemTypeId = Phpfox_Module::instance()->getModuleName();
        if ($sModule == 'apps' && Phpfox::isAppActive('Core_Pages')) {
            $sModule = 'pages';
        }
        if ($sModule == 'core') {
            $sModule = $this->getParam('like_type_id');
            $sModule = explode('_', $sModule);
            $sModule = $sModule[0];
        } else if ($sModule == 'profile') {
            $sModule = $sItemTypeId = $this->getParam('like_type_id');
            $sModule = explode('_', $sModule);
            $sModule = $sModule[0];
        } else if ($sModule == 'profile' && ($this->getParam('like_type_id') == 'feed_comment' || $this->getParam('like_type_id') == 'feed_mini')) {
            $sModule = 'feed';
        }
        if (!$this->getParam('aFeed') && ($aVals = $this->request()->getArray('val')) && isset($aVals['is_via_feed'])) {
            $this->template()->assign(array('aFeed' => array('feed_id' => $aVals['is_via_feed'])));
        }

        if ($iOwnerId = $this->getParam('like_owner_id', null)) {
            if (Phpfox::isUser() && Phpfox::getService('user.block')->isBlocked(null, $iOwnerId)) {
                return false;
            }
        }
        $sType = $this->getParam('like_type_id');
        $iItemId = $this->getParam('like_item_id');
        $bIsLike = $this->getParam('like_is_liked');
        $bIsCustom = $this->getParam('like_is_custom');

        $aReactions = $aDefaultLike = $aReacted = [];
        if (Phpfox::isAppActive('P_Reaction')) { // check and support reaction app
            $aReactions = Phpfox::getService('preaction')->getReactions();
            $aDefaultLike = Phpfox::getService('preaction')->getDefaultLike();
            if ($bIsLike) {
                $aReacted = Phpfox::getService('preaction')->getReactedDetail($iItemId, $sType, Phpfox::getUserId());
            }
        }

        (($sPlugin = Phpfox_Plugin::get('like.component_block_link')) ? eval($sPlugin) : false);

        $this->template()->assign(array(
                'sParentModuleName' => $sModule,
                'aLike' => array(
                    'like_type_id' => $sType,
                    'like_item_id' => $iItemId,
                    'like_is_liked' => $bIsLike,
                    'like_is_custom' => $bIsCustom
                ),
                'aReactions' => $aReactions,
                'aDefaultLike' => $aDefaultLike,
                'aUserReacted' => $aReacted
            )
        );

        $this->clearParam([
            'like_type_id', 'like_item_id', 'like_is_liked', 'like_is_custom'
        ]);
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('like.component_block_link_clean')) ? eval($sPlugin) : false);

        $this->template()->clean('aLike');
    }
}