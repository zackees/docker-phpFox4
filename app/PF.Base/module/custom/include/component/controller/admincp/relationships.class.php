<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Custom_Component_Controller_Admincp_Relationships
 */
class Custom_Component_Controller_Admincp_Relationships extends Phpfox_Component
{

    public function process()
    {
        if (($aVals = $this->request()->getArray('val'))) {
            if (Phpfox::getService('custom.relation.process')->add($aVals)) {
                $this->url()->send('admincp.custom.relationships', array(), _p('status_added'));
            }
        }

        if (($iId = $this->request()->getInt('delete'))) {
            if (Phpfox::getService('custom.relation.process')->delete($iId)) {
                $this->url()->send('admincp.custom.relationships', array(), _p('status_deleted'));
            }
        }


        $aStatuses = Phpfox::getService('custom.relation')->getAll();
        /* If we're editing lets make it easier and just find the one we're looking for here */

        $this->template()->setTitle(_p('admin_menu_manage_relationships'))
            ->setBreadCrumb(_p('admin_menu_manage_relationships'))
            ->setActiveMenu('admincp.member.relationships')
            ->setActionMenu([
                _p('add_relationship') => [
                    'url' => $this->url()->makeUrl('admincp.custom.relationship.add'),
                    'class' => 'popup'
                ]
            ])
            ->assign(array(
                'bShowClearCache' => true,
                'aStatuses' => $aStatuses
            ));
    }

}
