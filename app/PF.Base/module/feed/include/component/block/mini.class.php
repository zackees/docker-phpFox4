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
 * @package        Module_Feed
 * @version        $Id: mini.class.php 4545 2012-07-20 10:40:35Z phpFox LLC $
 */
class Feed_Component_Block_Mini extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $iParentFeedId = (int)$this->getParam('parent_feed_id');
        $sParentModuleId = $this->getParam('parent_module_id');
        if (!$iParentFeedId) {
            return false;
        }

        //Get Real Module_id
        if (Phpfox::isModule($sParentModuleId) || Phpfox::isApps($sParentModuleId)) {
            $sModule = $sParentModuleId;
        } else {
            $aModuleData = explode('_', $sParentModuleId);
            if (isset($aModuleData[0]) && Phpfox::isModule($aModuleData[0])) {
                $sModule = $aModuleData[0];
            } else {
                return false;
            }
        }

        if (!Phpfox::hasCallback($sModule, 'canShareItemOnFeed') || Phpfox::hasCallback($sParentModuleId, 'disableShare')) {
            return false;
        }
        $aParentFeedItem = Phpfox::getService('feed')->getParentFeedItem($sParentModuleId, $iParentFeedId);

        if (empty($aParentFeedItem)) {
            $aParentFeedItem = [
                'feed_id' => $iParentFeedId,
                'item_id' => $iParentFeedId
            ];
        }

        $aParentFeed = Phpfox::hasCallback($sParentModuleId, 'getActivityFeed') ? Phpfox::callback($sParentModuleId . '.getActivityFeed', $aParentFeedItem, null, true) : $aParentFeedItem;

        $showMap = false;

        if ($aParentFeed) {
            if (empty($aParentFeed['user_id']) && isset($aParentFeed['custom_data_cache']['user_id']) && isset($aParentFeed['custom_data_cache']['full_name'])) {
                $aParentFeed['user_id'] = $aParentFeed['custom_data_cache']['user_id'];
                $aParentFeed['user_name'] = $aParentFeed['custom_data_cache']['user_name'];
                $aParentFeed['full_name'] = $aParentFeed['custom_data_cache']['full_name'];
            }

            if (!isset($aParentFeed['type_id'])) {
                $aParentFeed['type_id'] = $sParentModuleId;
            }

            if (isset($aParentFeed['privacy'])) {
                $sIconClass = 'ico ';
                switch ((int)$aParentFeed['privacy']) {
                    case 0:
                        $sIconClass .= 'ico-globe';
                        break;
                    case 1:
                        $sIconClass .= 'ico-user3-two';
                        break;
                    case 2:
                        $sIconClass .= 'ico-user-man-three';
                        break;
                    case 3:
                        $sIconClass .= 'ico-lock';
                        break;
                    case 4:
                        $sIconClass .= 'ico-gear-o';
                        break;
                    case 6:
                        $sIconClass .= 'ico-user-circle-alt-o';
                        break;
                }
                $aParentFeed['privacy_icon_class'] = $sIconClass;
            }

            if (Phpfox::isAppActive('P_StatusBg')) {
                $parentItemId = !empty($aParentFeed['item_id']) ? $aParentFeed['item_id'] : $iParentFeedId;
                $aParentFeed['status_background'] = Phpfox::getService('pstatusbg')->getFeedStatusBackground($parentItemId, $aParentFeed['type_id'], $aParentFeed['user_id']);
            }
            $showMap = (Phpfox::getParam('feed.enable_check_in') && Phpfox::getParam('core.google_api_key') != '' && isset($aParentFeed['location_latlng']) && isset($aParentFeed['location_latlng']['latitude']));
            if (in_array($sParentModuleId, ['photo', 'v']) || !empty($aParentFeed['status_background'])) {
                $showMap = false;
            }
            if (!empty($aParentFeed['privacy']) && !Phpfox::getService('privacy')->check($aParentFeed['type_id'], $aParentFeed['item_id'], $aParentFeed['user_id'], $aParentFeed['privacy'], null, true)) {
                $aParentFeed = [];
            }
        }

        $this->template()->assign([
                'aParentFeed' => $aParentFeed,
                'showMap' => $showMap
            ]
        );
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('feed.component_block_mini_clean')) ? eval($sPlugin) : false);
    }
}