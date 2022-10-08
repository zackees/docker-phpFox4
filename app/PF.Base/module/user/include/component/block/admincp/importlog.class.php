<?php
defined('PHPFOX') or exit('NO DICE!');

class User_Component_Block_Admincp_Importlog extends Phpfox_Component
{
    public function process()
    {
        $aParam = $this->getParam('params');
        $aLogs = [];
        $bIsValid = !empty($aParam['row']) && !empty($aParam['field']) && (!empty($aParam['log']) && is_array($aLogs = json_decode(base64_decode($aParam['log']), true))) ? true : false;
        $this->template()->assign([
            'bIsValid' => $bIsValid,
            'aLogs' => $aLogs,
            'iRow' => !empty($aParam['row']) ? $aParam['row'] : 0,
            'sField' => $aParam['field']
        ]);
    }
}