<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Custom_Component_Block_Panel
 */
class Custom_Component_Block_Panel extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $typeId = $this->getParam('type_id', 'user_panel');
        if ($typeId == 'user_main' && !empty($this->getParam('ignore_field'))) {
            $ignoredFields = Phpfox::getService('custom')->getIgnoredFieldsByLocation();
            if (!empty($ignoredFields)) {
                $this->template()->assign('ignoredFields', $ignoredFields);
            }
        }

        $this->template()->assign([
            'customFieldTypeId' => $typeId,
            'customFieldTemplate' => $typeId == 'user_panel' ? 'info' : 'content',
        ]);
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('custom.component_block_panel_clean')) ? eval($sPlugin) : false);
    }
}