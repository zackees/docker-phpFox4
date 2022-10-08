<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Ban_Component_Ajax_Ajax
 */
class Ban_Component_Ajax_Ajax extends Phpfox_Ajax
{
    public function ip()
    {
        if ($this->get('active')) {
            Phpfox::getService('ban.process')->add([
                'type_id' => 'ip',
                'find_value' => $this->get('ip')
            ]);
        } else {
            Phpfox::getService('ban.process')->deleteByValue('ip', $this->get('ip'));
        }
    }

    public function massAction()
    {
        $sId = $this->get('id');
        $sType = $this->get('type');
        if(empty($sId) || empty($sType))
        {
            return Phpfox_Error::set(_p('hack_attempt'));
        }

        $iFail = 0;
        $aIds = explode(',', $sId);
        foreach($aIds as $iId)
        {
            if(!(Phpfox::getService('ban.process')->delete($iId)))
            {
                $iFail ++;
            }
        }

        if($iFail == 0)
        {
            $this->alert(_p('successfully_deleted'));
            $this->call('setTimeout(function(){$Core.reloadPage();},2000);');
            Phpfox_Cache::instance()->remove('ban_type_' . $sType);
        }
    }
}

