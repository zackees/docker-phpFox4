<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Controller_Admincp_Promotion_Add
 */
class User_Component_Controller_Admincp_Promotion_Add extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $bIsEdit = false;
        $processService = Phpfox::getService('user.promotion.process');
        if (($iId = $this->request()->getInt('id')) && ($aPromotion = Phpfox::getService('user.promotion')->getPromotion($iId))) {
            $bIsEdit = true;
            $this->template()->assign([
                    'aForms' => $aPromotion
                ]
            );
        }

        $this->template()
            ->setSectionTitle(_p('promotions'))
            ->setActiveMenu('admincp.member.promotion')
            ->setTitle(($bIsEdit ? _p('editing_promotion') : _p('add_promotion')))
            ->setBreadCrumb(($bIsEdit ? _p('editing_promotion') : _p('add_promotion')), null, true)
            ->assign([
                    'bIsEdit' => $bIsEdit,
                    'aUserGroups' => Phpfox::getService('user.group')->get(),
                    'sEnableOptionLink' => $this->url()->makeUrl('admincp.setting.edit', ['module-id' => 'user']) . '#check_promotion_system'
                ]
            );

        if (($aVals = $this->request()->getArray('val'))) {
            if (!$aVals['user_group_id'] || !$aVals['upgrade_user_group_id']) {
                return Phpfox_Error::set(_p('promotion_required_fields'));
            }

            if (empty($aVals['total_activity']) || $aVals['total_activity'] < 1) {
                Phpfox_Error::set(_p('promotion_activity_points_must_be_greater_than_or_equal_to_number', ['number' => 1]));
            } elseif ((int)$aVals['total_activity'] > 4294967295) {
                Phpfox_Error::set(_p('promotion_total_activity_points_invalid_number'));
            }

            if (empty($aVals['total_day']) || $aVals['total_day'] < 1) {
                Phpfox_Error::set(_p('promotion_day_registered_must_be_greater_than_or_equal_to_number', ['number' => 1]));
            }

            if ($aVals['user_group_id'] == $aVals['upgrade_user_group_id']) {
                Phpfox_Error::set(_p('user_group_must_be_different_than_upgraded_user_group'));
            }

            if (!Phpfox_Error::isPassed()) {
                return Phpfox_Error::getDisplay();
            }

            if ($bIsEdit) {
                if ($processService->update($aPromotion['promotion_id'], $aVals)) {
                    $this->url()->send('admincp.user.promotion', null, _p('promotion_successfully_update'));
                }
            } else {
                if ($processService->add($aVals)) {
                    $this->url()->send('admincp.user.promotion', null, _p('promotion_successfully_added'));
                }
            }
        }
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('user.component_controller_admincp_promotion_add_clean')) ? eval($sPlugin) : false);
    }
}
