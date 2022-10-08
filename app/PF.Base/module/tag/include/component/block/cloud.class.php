<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Tag_Component_Block_Cloud
 */
class Tag_Component_Block_Cloud extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        if ((defined('PHPFOX_IS_USER_PROFILE') || defined('PHPFOX_IS_PAGES_VIEW')) && (!defined('PHPFOX_SHOW_TAGS'))) {
            return false;
        }

        $sType = $this->getParam('sTagType', null);
        $bNoTagBlock = $this->getParam('bNoTagBlock', false);
        $bHashTag = $this->getParam('bShowHashTag', null);
        $sHeader = $this->getParam('sTagCloudHeader', _p('trends'));

        if ($sType === null) {
            if (!Phpfox::getParam('tag.enable_hashtag_support')) {
                return false;
            }
            $bHashTag = 1;
        }

        if (!$bHashTag && !Phpfox::getParam('tag.enable_tag_support')) {
            return false;
        }

        $aRows = Phpfox::getService('tag')->getTagCloud($sType, (($this->getParam('bIsProfile') === true && ($aUser = $this->getParam('aUser'))) ? $aUser['user_id'] : null), $this->getParam('iTagDisplayLimit', null), $bHashTag);

        if (empty($aRows)) {
            return false;
        }

        if ($this->getParam('bIsProfile') === true && !defined('TAG_ITEM_ID') && isset($aUser['user_id'])) {
            foreach ($aRows as $iKey => $aRow) {
                $aRows[$iKey]['link'] = Phpfox::getService('user')->getLink($aUser['user_id'], $aUser['user_name'], [$sType, 'tag', $aRow['url']]);
            }
        }

        if ($bNoTagBlock === false) {
            $this->template()->assign([
                    'sHeader' => $sHeader
                ]
            );
        }

        $iSince = (PHPFOX_TIME - (86400 * Phpfox::getParam('tag.tag_days_treading')));
        $sTrendingSince = Phpfox::getTime(Phpfox::getParam('core.global_update_time'), $iSince);

        $this->template()->assign([
                'aRows' => $aRows,
                'sTagGlobalType' => $sType,
                'sTrendingSince' => $sTrendingSince,
                'bHashTag' => $bHashTag
            ]
        );

        if ($sType === null) {
            $this->template()->assign([
                    'sDeleteBlock' => 'dashboard'
                ]
            );
        }

        return 'block';
    }
}
