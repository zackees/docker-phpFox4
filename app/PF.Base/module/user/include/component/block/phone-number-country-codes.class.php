<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Block_Images
 */
class User_Component_Block_Phone_Number_Country_Codes extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $sId = $this->getParam('phone_field_id');
        if (!$sId) {
            return false;
        }
        if (strpos($sId, '#') !== 0) {
            $sId = '.' . $sId;
        }
        $this->template()->assign([
            'sPhoneFieldId' => $sId,
            'sDefaultNumber' => $this->getParam('default_phone_number', ''),
            'bInitOnChange' => $this->getParam('init_onchange', 0),
            'sUniqueKey' => preg_replace('/[#.]/', '', $sId) . PHPFOX_TIME
        ]);
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('user.component_block_phone_number_country_codes_clean')) ? eval($sPlugin) : false);
    }
}