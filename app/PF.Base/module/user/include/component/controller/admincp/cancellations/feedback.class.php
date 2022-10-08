<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Controller_Admincp_Cancellations_Feedback
 */
class User_Component_Controller_Admincp_Cancellations_Feedback extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $this->_setMenuName('admincp.user.cancellations.feedback');

        // process sort params
        $iOptionId  = $this->request()->get('option_id');
        $sSort = str_replace('+', ' ', $this->request()->get('sort', ''));
        $aUserGroups = [];
        foreach (Phpfox::getService('user.group')->get() as $aUserGroup) {
            $aUserGroups[$aUserGroup['user_group_id']] = Phpfox_Locale::instance()->convert($aUserGroup['title']);
        }
        if (!Phpfox::getParam('core.enable_register_with_phone_number')) {
            $aTypeFilter = [
                '0' => [_p('email_name'), 'AND ((udf.full_name LIKE \'%[VALUE]%\' OR (udf.user_email LIKE \'%[VALUE]@%\' OR udf.user_email = \'[VALUE]\')) OR udf.user_email LIKE \'%[VALUE]%\')'],
                '1' => [_p('email'), 'AND ((udf.user_email LIKE \'%[VALUE]@%\' OR udf.user_email = \'[VALUE]\' OR udf.user_email LIKE \'%[VALUE]%\'))'],
                '2' => [_p('name'), 'AND (udf.full_name LIKE \'%[VALUE]%\')']
            ];
        } else {
            $aTypeFilter = [
                '0' => [_p('email_name_phone'), 'AND ((udf.full_name LIKE \'%[VALUE]%\' OR (udf.user_email LIKE \'%[VALUE]@%\' OR udf.user_email = \'[VALUE]\')) OR udf.user_email LIKE \'%[VALUE]%\' OR udf.user_phone LIKE \'[VALUE_PHONE]\')'],
                '1' => [_p('email'), 'AND ((udf.user_email LIKE \'%[VALUE]@%\' OR udf.user_email = \'[VALUE]\' OR udf.user_email LIKE \'%[VALUE]%\'))'],
                '2' => [_p('name'), 'AND (udf.full_name LIKE \'%[VALUE]%\')'],
                '3' => [_p('phone_number'), 'AND (udf.user_phone LIKE \'[VALUE_PHONE]\')'],
            ];
        }
        $aFilters = [
            'keyword' => [
                'type' => 'input:text',
                'size' => 15,
                'class' => 'txt_input'
            ],
            'type' => [
                'type' => 'select',
                'options' => $aTypeFilter,
                'depend' => 'keyword'
            ],
            'group' => [
                'type' => 'select',
                'options' => $aUserGroups,
                'add_any' => true,
                'search' => 'AND udf.user_group_id = \'[VALUE]\''
            ],
        ];
        $aSearchParams = [
            'filters'       => $aFilters,
        ];
        $oFilter = Phpfox_Search::instance()->set($aSearchParams);
        $aFeedbacks = Phpfox::getService('user.cancellations')->getFeedback($sSort, $iOptionId, $oFilter->getConditions());

        foreach ($aFeedbacks as $iKey => $aFeedback) {
            if (!empty($aFeedback['reasons'])) {
                foreach ($aFeedback['reasons'] as $iReasonKey => $sReason) {
                    $aFeedbacks[$iKey]['reasons'][$iReasonKey] = Phpfox::getLib('parse.output')->clean(\Core\Lib::phrase()->isPhrase($sReason) ? _p($sReason) : $sReason);
                }
            }
        }

        $this->template()->setTitle(_p('view_feedback_on_cancellations'))
            ->setBreadCrumb(_p('cancelled_members'), $this->url()->makeUrl('admincp.user.cancellations.feedback'))
            ->setActiveMenu('admincp.member.cancellations')
            ->setActionMenu([
                _p('add_new_option') => [
                    'url' => $this->url()->makeUrl('admincp.user.cancellations.add'),
                    'class'=>'popup',
                ],
            ])
            ->assign(array(
                    'aFeedbacks' => $aFeedbacks,
                    'sCurrent' => $sSort,
                    'aSectionAppMenus' => [
                        _p('manage_cancellation_options') => [
                            'url' => $this->url()->makeUrl('admincp.user.cancellations.manage'),
                        ],
                        _p('cancelled_members')=>[
                            'url'=> $this->url()->makeUrl('admincp.user.cancellations.feedback'),
                            'is_active' => true
                        ],
                    ],
                    'bIsSearch' => $oFilter->isSearch(),
                    'sCurrentSort' => $this->request()->get('sort'),
                )
            );
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('user.component_controller_admincp_add_clean')) ? eval($sPlugin) : false);
    }
}
