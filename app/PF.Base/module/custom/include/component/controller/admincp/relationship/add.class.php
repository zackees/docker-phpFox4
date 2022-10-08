<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Custom_Component_Controller_Admincp_Relationship_Add
 */
class Custom_Component_Controller_Admincp_Relationship_Add extends Phpfox_Component
{
    public function process()
    {
        if ($iEdit = $this->request()->getInt('id')) {
            $aEdit = array();

            $aStatuses = Phpfox::getService('custom.relation')->getAll();

            foreach ($aStatuses as $aStatus) {
                if ($aStatus['relation_id'] == $iEdit) {
                    $aEdit = $aStatus;
                    break;
                }
            }
            if (empty($aEdit)) {
                Phpfox_Error::display(_p('not_found'));
            } else {
                $this->template()->assign(array('aEdit' => $aEdit));
            }
        }

        $this->template()
            ->setTitle($iEdit ? _p('edit_relationship') : _p('add_relationship'))
            ->setBreadCrumb(_p('Members'),'#')
            ->setBreadCrumb(_p('relationship_statues'),$this->url()->makeUrl('admincp.custom.relationships'))
            ->setBreadCrumb($iEdit ? _p('edit_relationship') : _p('add_relationship'))
            ->setActiveMenu('admincp.member.relationships')
            ->setActionMenu([
                _p('relationship_statues') => $this->url()->makeUrl('admincp.custom.relationships')
            ]);
    }

    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('custom.component_controller_admincp_relationships_add_clean')) ? eval($sPlugin) : false);
    }
}
