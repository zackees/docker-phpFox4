<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Controller_Admincp_Cancellations_Manage
 */
class User_Component_Controller_Admincp_Cancellations_Manage extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        // are we deleting a reason
        if (($iReasonId = $this->request()->get('delete'))) {
            if (Phpfox::getService('user.cancellations.process')->delete($iReasonId)) {
                $this->url()->send('admincp.user.cancellations.manage', null, _p('option_deleted_successfully'));
            }
        }
        // get all the cancellation reasons
        $aReasons = Phpfox::getService('user.cancellations')->get(null, true);

        foreach ($aReasons as $index => $aReason) {
            $phrase = $aReason['phrase_var'];
            $aReasons[$index]['total'] = Phpfox::getLib('database')
                ->select('count(*)')
                ->from(':user_delete_feedback')
                ->where("reasons_given like '%$phrase\"%'")
                ->execute('getSlaveField');
        }

        $this->template()->setTitle(_p('manage_cancellation_options'))
            ->setBreadCrumb(_p('manage_cancellation_options'), $this->url()->makeUrl('admincp.user.cancellations.manage'))
            ->assign([
                    'aReasons' => $aReasons,
                    'bShowClearCache' => true,
                    'aSectionAppMenus' => [
                        _p('manage_cancellation_options') => [
                            'url' => $this->url()->makeUrl('admincp.user.cancellations.manage'),
                            'is_active' => true
                        ],
                        _p('cancelled_members') => [
                            'url' => $this->url()->makeUrl('admincp.user.cancellations.feedback'),
                        ],
                    ]
                ]
            )
            ->setActionMenu([
                _p('add_new_option') => [
                    'url' => $this->url()->makeUrl('admincp.user.cancellations.add'),
                    'class' => 'popup',
                ],
            ])
            ->setSectionTitle(_p('cancellation_options'))
            ->setActiveMenu('admincp.user.cancellations')
            ->setHeader([
                'drag.js' => 'static_script',
                'admincp.js' => 'module_user',
                '<script type="text/javascript">$Behavior.coreDragInit = function() { Core_drag.init({table: \'#js_drag_drop\', ajax: \'core.cancellationsOrdering\'}); }</script>'
            ]);

    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('user.component_controller_admincp_browse_clean')) ? eval($sPlugin) : false);
    }
}
