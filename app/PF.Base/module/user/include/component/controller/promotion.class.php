<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Controller_Promotion
 */
class User_Component_Controller_Promotion extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        Phpfox::isUser(true);
        $aUser = Phpfox::getService('user')->get(Phpfox::getUserId(), true);
        $aUserGroup = Phpfox::getService('user.group')->getGroup($aUser['user_group_id']);
        $aPromotions = Phpfox::getService('user.promotion')->getPromotionsByUserGroup();
        $this->template()
            ->setTitle(_p('promotions'))
            ->setBreadCrumb(_p('promotions'))
            ->assign(array(
                    'aUserGroup' => $aUserGroup,
                    'iTotalPoints' => $aUser['activity_points'],
                    'iTotalDays' => (int)((PHPFOX_TIME - $aUser['joined']) / 86400),
                    'aPromotions'=> $aPromotions
                )
            );
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('user.component_controller_promotion_clean')) ? eval($sPlugin) : false);
    }
}
