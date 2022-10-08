<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Custom_Component_Controller_Admincp_Group_Add
 */
class Custom_Component_Controller_Admincp_Group_Add extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $bIsEdit = false;

        if (($iEditId = $this->request()->getInt('id'))) {
            Phpfox::getUserParam('custom.can_manage_custom_fields', true);

            if (($aGroup = Phpfox::getService('custom.group')->getForEdit($iEditId)) && isset($aGroup['group_id'])) {
                $bIsEdit = true;
                $this->template()->assign([
                        'aForms' => $aGroup,
                    ]
                );
            }
        } else {
            Phpfox::getUserParam('custom.can_add_custom_fields_group', true);
        }

        $aGroupValidation = [
            'type_id' => _p('select_where_this_custom_field_should_be_located'),
        ];

        if (Phpfox::isTechie()) {
            if (Phpfox::getUserParam('admincp.can_view_product_options')) {
                $aGroupValidation['product_id'] = _p('select_a_product_this_custom_field_will_belong_to');
            }
            $aGroupValidation['module_id'] = _p('select_a_module_this_custom_field_will_belong_to');
        }

        $oGroupValidator = Phpfox_Validator::instance()->set([
                'sFormName' => 'js_group_field',
                'aParams' => $aGroupValidation,
                'bParent' => true,
            ]
        );

        $aGroupTypes = [];
        foreach (Phpfox::massCallback('getCustomGroups') as $sModule => $aCustomGroups) {
            foreach ($aCustomGroups as $sKey => $sPhrase) {
                $aGroupTypes[$sKey] = $sPhrase;
            }
        }

        if (($aVals = $this->request()->getArray('val'))) {
            if ($oGroupValidator->isValid($aVals)) {
                if ($bIsEdit === true) {
                    if (Phpfox::getService('custom.group.process')->update($aGroup['group_id'], $aVals)) {
                        $this->url()->send('admincp.custom.group.add', ['id' => $aGroup['group_id']],
                            _p('group_successfully_updated'));
                    }
                } else {
                    if (Phpfox::getService('custom.group.process')->add($aVals)) {
                        $this->url()->send('admincp.custom.group.add', null, _p('group_successfully_added'));
                    }
                }
            }
        }

        $aUserGroups = Phpfox::getService('user.group')->get();
        foreach ($aUserGroups as $iKey => $aUserGroup) {
            if (!Phpfox::getUserGroupParam($aUserGroup['user_group_id'], 'custom.has_special_custom_fields')) {
                unset($aUserGroups[$iKey]);
            }
        }

        $sTitle = _p($bIsEdit ? 'edit_custom_group' : 'add_a_new_custom_group');

        $this->template()->setTitle($sTitle)
            ->setBreadCrumb(_p('apps'), $this->url()->makeUrl('admincp.apps'))
            ->setBreadCrumb(_p('admincp_custom_fields'), $this->url()->makeUrl('admincp.custom'))
            ->setBreadCrumb($sTitle)
            ->setActiveMenu('admincp.member.custom')
            ->assign([
                    'sGroupCreateJs' => $oGroupValidator->createJS(),
                    'sGroupGetJsForm' => $oGroupValidator->getJsForm(),
                    'aGroupTypes' => $aGroupTypes,
                    'bIsEdit' => $bIsEdit,
                    'aUserGroups' => $aUserGroups,
                    'bIsAddGroupPage' => true
                ]
            );
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('custom.component_controller_admincp_group_add_clean')) ? eval($sPlugin) : false);
    }
}