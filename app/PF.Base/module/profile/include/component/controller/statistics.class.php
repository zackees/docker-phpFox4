<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Profile_Component_Controller_Points
 */
class Profile_Component_Controller_Statistics extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $userName = Phpfox::getUserBy('user_name');
        if ($userName != $this->request()->get('req1')) {
            $this->url()->send($userName);
        }
        $aUser = Phpfox::getService('user')->get(Phpfox::getUserId(), true);
        $aModules = Phpfox::massCallback('getDashboardActivity');
        $aActivites = [
            _p('total_items') => $aUser['activity_total'],
        ];
        foreach ($aModules as $aModule) {
            foreach ($aModule as $sPhrase => $sLink) {
                $aActivites[$sPhrase] = $sLink;
            }
        }
        if (!defined('PHPFOX_IS_USER_STATISTICS')) {
            define('PHPFOX_IS_USER_STATISTICS', true);
        }
        $this->template()
            ->setBreadCrumb(_p('activity_statistics'))
            ->setTitle(_p('activity_statistics'))
            ->assign([
                'aActivites' => $aActivites,
            ]);

        (($sPlugin = Phpfox_Plugin::get('profile.component_controller_statistics_process_end')) ? eval($sPlugin) : false);
    }

    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('profile.component_controller_statistics_clean')) ? eval($sPlugin) : false);
    }
}
