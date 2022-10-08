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
class Feed_Component_Block_Inline_Privacy_Edit extends Phpfox_Component
{
    public function process()
    {
        $iFeedId = $this->request()->get('id');
        $sModule = $this->request()->get('module');
        $iItemId = $this->request()->get('item_id') ? $this->request()->get('item_id') : $this->request()->get('id');
        if (!empty($sModule) && !in_array($sModule, ['photo', 'v', 'link'])) {
            return Phpfox_Error::display(_p('you_do_not_have_permission_to_edit_this_field'));
        }

        $aFeed = Phpfox::getService('feed')->getUserStatusFeed([], $iFeedId, false);
        if (!$aFeed) {
            return false;
        }
        $this->template()->assign([
            'iFeedId' => $iFeedId,
            'sModule' => $sModule,
            'iItemId' => $iItemId,
            'aForms' => $aFeed
        ]);
        return 'block';
    }
}