<?php
/**
 * [PHPFOX_HEADER]
 */


defined('PHPFOX') or exit('NO DICE!');


class Core_Component_Block_Iconfont_Input extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $aIconList = Phpfox::getService('core')->loadLineficon();
        $sIconInputName = $this->getParam('name');
        $aForms = $this->template()->getVar('aForms');
        $sCurrentIcon = isset($aForms[$sIconInputName]) ? $aForms[$sIconInputName] : '';
        $bIsFontAws = false;
        if (!empty($sCurrentIcon) && strpos($sCurrentIcon, 'ico-') === false) {
            $bIsFontAws = true;
        }
        $this->template()->assign([
            'aIcons'          => $aIconList,
            'sIconInputName'  => $sIconInputName,
            'sIconInputId'    => $this->getParam('id', 'js_selected_icon_val'),
            'sIconInputClass' => $this->getParam('class'),
            'sIconString'     => json_encode($aIconList),
            'sValue'          => isset($aForms[$sIconInputName]) ? $aForms[$sIconInputName] : '',
            'bIsFontAws'      => $bIsFontAws,
            'bIsFaText'       => $this->getParam('check_fa', false) && $bIsFontAws
        ]);
        return 'block';
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('core.block_iconfont_input_block_clean')) ? eval($sPlugin) : false);
    }
}