<?php

defined('PHPFOX') or exit('NO DICE!');

class User_Component_Block_Search_Members extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        if (!Phpfox::isUser() || empty($ajaxBuild = $this->getParam('ajax_build'))) {
            return false;
        }

        $currentValues = $this->getParam('current_values', []);
        $inputType = $this->getParam('input_type', 'multiple');
        $includeCurrentUser = $this->getParam('include_current_user', false);
        if ($inputType == 'single' && !empty($currentValues)) {
            $userIds = array_column($currentValues, 'user_id');
            $this->template()->assign('userIds', implode(',', $userIds));
        }

        $this->template()->assign([
            'inputType' => $inputType,
            'inputName' => $this->getParam('input_name', 'friends'),
            'currentValues' => $currentValues,
            'includeCurrentUser' => $includeCurrentUser,
            'inputPlaceholder' => _p($this->getParam('input_placeholder', 'search_members_by_their_name')),
            'ajaxBuild' => $ajaxBuild,
            'targetItemId' => $this->getParam('item_id', ''),
        ]);

        return 'block';
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('friend.component_block_search_small_clean')) ? eval($sPlugin) : false);
    }
}
