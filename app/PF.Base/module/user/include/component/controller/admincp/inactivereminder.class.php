<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Controller_Admincp_Inactivereminder
 */
class User_Component_Controller_Admincp_Inactivereminder extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $iPage = $this->request()->getInt('page', 1);
        $iDays = $this->request()->getInt('day', 7);
        $bError = false;
        if ($iDays < 0) {
            Phpfox_Error::set(_p('inputted_days_must_greater_or_equal_to_zero'));
            $bError = true;
        }
        if (($aIds = $this->request()->getArray('id')) && count((array)$aIds)) {
            Phpfox::getService('user.process')->addInactiveJob($aIds);
        }

        $aSorts = [
            'u.full_name' => _p('name'),
            'u.last_login' => _p('last_login'),
            'u.last_activity' => _p('last_activity'),
            'u.user_id' => _p('id'),
            'ug.title' => _p('groups'),
        ];
        $sDefaultOrderName = 'u.full_name';
        $sDefaultSort = 'ASC';
        if (Phpfox::getParam('user.user_browse_default_result') == 'last_login') {
            $sDefaultOrderName = 'u.last_login';
            $sDefaultSort = 'DESC';
        }

        $aSearch = request()->get('search');
        $aSearchSort = isset($aSearch['sort']) ? explode(' ', $aSearch['sort']) : [];
        $aUserGroups = [];

        foreach (Phpfox::getService('user.group')->get() as $aUserGroup) {
            $aUserGroups[$aUserGroup['user_group_id']] = Phpfox_Locale::instance()->convert($aUserGroup['title']);
        }

        $aGenders = Phpfox::getService('core')->getGenders();
        $aGenders[''] = _p('all_members');

        $aAge = [];
        for ($i = Phpfox::getService('user')->age(Phpfox::getService('user')->buildAge(1, 1, Phpfox::getParam('user.date_of_birth_end'))); $i <= Phpfox::getService('user')->age(Phpfox::getService('user')->buildAge(1, 1, Phpfox::getParam('user.date_of_birth_start'))); $i++) {
            $aAge[$i] = $i;
        }

        $bCustomSort = false;
        $aSearch = request()->get('search');
        if (!empty($aSearch) && isset($aSearch['sort'])) {
            $aSearchSort = explode(' ', $aSearch['sort']);
            if (in_array($aSearch['sort'], ['u.last_login', 'u.last_activity'])) {
                $sDefaultSort = 'DESC';
            }

            if (isset($aSearchSort[1])) {
                $sDefaultSort = $aSearchSort[1];
                $bCustomSort = true;
            }
        }

        $aDisplays = [];
        foreach ([12, 24, 36, 48] as $iPageCnt) {
            $aDisplays[$iPageCnt] = _p('per_page', ['total' => $iPageCnt]);
        }
        if (!Phpfox::getParam('core.enable_register_with_phone_number')) {
            $aTypeFilter = [
                '0' => [_p('email_name'), 'AND ((u.full_name LIKE \'%[VALUE]%\' OR (u.email LIKE \'%[VALUE]@%\' OR u.email = \'[VALUE]\' OR u.user_name = \'[VALUE]\')) OR u.email LIKE \'%[VALUE]%\')'],
                '1' => [_p('email'), 'AND ((u.email LIKE \'%[VALUE]@%\' OR u.email = \'[VALUE]\' OR u.email LIKE \'%[VALUE]%\'))'],
                '2' => [_p('name'), 'AND (u.full_name LIKE \'%[VALUE]%\')'],
            ];
        } else {
            $aTypeFilter = [
                '0' => [_p('email_name_phone'), 'AND ((u.full_name LIKE \'%[VALUE]%\' OR (u.email LIKE \'%[VALUE]@%\' OR u.email = \'[VALUE]\' OR u.user_name = \'[VALUE]\')) OR u.email LIKE \'%[VALUE]%\' OR u.full_phone_number LIKE \'[VALUE_PHONE]\')'],
                '1' => [_p('email'), 'AND ((u.email LIKE \'%[VALUE]@%\' OR u.email = \'[VALUE]\' OR u.email LIKE \'%[VALUE]%\'))'],
                '2' => [_p('name'), 'AND (u.full_name LIKE \'%[VALUE]%\')'],
                '3' => [_p('phone_number'), 'AND (u.full_phone_number LIKE \'[VALUE_PHONE]\')'],
            ];
        }
        $aFilters = [
            'display' => [
                'type' => 'select',
                'options' => $aDisplays,
                'default' => 12,
            ],
            'sort' => [
                'type' => 'select',
                'options' => $aSorts,
                'default' => $sDefaultOrderName,
            ],
            'sort_by' => [
                'type' => 'select',
                'options' => [
                    'DESC' => _p('descending'),
                    'ASC' => _p('ascending'),
                ],
                'default' => $sDefaultSort,
            ],
            'keyword' => [
                'type' => 'input:text',
                'size' => 15,
                'class' => 'txt_input',
            ],
            'type' => [
                'type' => 'select',
                'options' => $aTypeFilter,
                'depend' => 'keyword',
            ],
            'group' => [
                'type' => 'select',
                'options' => $aUserGroups,
                'add_any' => true,
                'search' => 'AND u.user_group_id = \'[VALUE]\'',
            ],
            'gender' => [
                'type' => 'select',
                'options' => $aGenders,
                'default_view' => '',
                'search' => 'AND u.gender = \'[VALUE]\'',
                'suffix' => '<br />',
                'id' => 'js_adv_search_user_browse_gender',
            ],
            'from' => [
                'type' => 'select',
                'options' => $aAge,
                'select_value' => _p('from'),
                'id' => 'js_adv_search_user_browse_from',
            ],
            'to' => [
                'type' => 'select',
                'options' => $aAge,
                'select_value' => _p('to'),
                'id' => 'js_adv_search_user_browse_to',
            ],
            'country' => [
                'type' => 'select',
                'options' => Phpfox::getService('core.country')->get(),
                'search' => 'AND u.country_iso = \'[VALUE]\'',
                'add_any' => true,
                'id' => 'country_iso',
            ],
            'country_child_id' => [
                'type' => 'select',
                'search' => 'AND ufield.country_child_id = \'[VALUE]\'',
                'clone' => true,
            ],
            'status' => [
                'type' => 'select',
                'options' => [
                    '2' => _p('all_members'),
                    '1' => _p('featured_members'),
                    '3' => _p('pending_verification_members'),
                    '5' => _p('pending_approval'),
                    '6' => _p('not_approved'),
                ],
                'default_view' => '2',
            ],
            'city' => [
                'type' => 'input:text',
                'size' => 15,
                'search' => 'AND ufield.city_location LIKE \'%[VALUE]%\'',
            ],
            'zip' => [
                'type' => 'input:text',
                'size' => 10,
                'search' => 'AND ufield.postal_code = \'[VALUE]\'',
            ],
            'ip' => [
                'type' => 'input:text',
                'size' => 10,
            ],
        ];

        $aSearchParams = [
            'type' => 'browse',
            'filters' => $aFilters,
            'search' => 'day',
            'custom_search' => true,
        ];

        $oFilter = Phpfox_Search::instance()->set($aSearchParams);

        if ($bCustomSort) {
            $oFilter->setSort($aSearchSort[0]);
        }

        define('PHPFOX_IS_ADMIN_SEARCH', true);
        $oFilter->setCondition('AND u.profile_page_id = 0 AND u.last_activity < ' . (PHPFOX_TIME - ($iDays * 86400)));
        if ($sIp = $oFilter->get('ip')) {
            Phpfox::getService('user.browse')->ip($sIp);
        }

        $bAgeSearch = false;
        $iYear = Phpfox::getTime('Y');
        if (($iFrom = $oFilter->get('from')) || ($iFrom = $this->request()->getInt('from'))) {
            $oFilter->setCondition('AND u.birthday_search <= \'' . Phpfox::getLib('date')->mktime(0, 0, 0, 1, 1, $iYear - $iFrom) . '\'' . ' AND ufield.dob_setting IN(0,1,2)');
            $bAgeSearch = true;
        }
        if (($iTo = $oFilter->get('to')) || ($iTo = $this->request()->getInt('to'))) {
            $oFilter->setCondition('AND u.birthday_search >= \'' . Phpfox::getLib('date')->mktime(0, 0, 0, 1, 1, $iYear - $iTo) . '\'' . ' AND ufield.dob_setting IN(0,1,2)');
            $bAgeSearch = true;
        }
        if ($bAgeSearch) {
            $oFilter->setCondition('AND u.birthday IS NOT NULL');
        }

        $sStatus = $oFilter->get('status');
        $bIsFeatured = false;
        switch ($sStatus) {
            case 1:
                $bIsFeatured = true;
                break;
            case 3:
                $oFilter->setCondition('AND u.status_id = 1');
                break;
            case 5:
                $oFilter->setCondition('AND u.view_id = 1');
                break;
            case 6:
                $oFilter->setCondition('AND u.view_id = 2');
                break;
            default:
                break;
        }

        $iPageSize = $oFilter->getDisplay();
        if ($bError) {
            $iCnt = 0;
            $aUsers = [];
        } else {
            list($iCnt, $aUsers) = Phpfox::getService('user.browse')->conditions($oFilter->getConditions())
                ->sort($oFilter->getSort())
                ->page($iPage)
                ->limit($iPageSize)
                ->extend(true)
                ->featured($bIsFeatured)
                ->gender($bAgeSearch)
                ->pendingType(true)
                ->get();
        }
        if ($aUsers) {
            $aCachedMailing = storage()->get('user_inactive_mailing_job');
            if (!empty($aCachedMailing) && !empty($aCachedMailing->value)) {
                foreach ($aUsers as $key => $aUser) {
                    if (in_array($aUser['user_id'], $aCachedMailing->value)) {
                        $aUsers[$key]['in_process'] = 1;
                    } else {
                        $aUsers[$key]['in_process'] = 0;
                    }
                }
            }
        }

        $this->template()->setHeader([
            'inactivereminder.js' => 'module_user',
            'inactivereminder.css' => 'module_user',
            'country.js' => 'module_core',
        ])
            ->setPhrase([
                'stopped',
                'enter_a_number_of_days',
                'enter_a_number_to_size_each_batch',
                'not_enough_users_to_mail',
                'are_you_sure_you_want_send_mail_to_all_inactive_members_who_have_not_logged_in_for_days_days',
                'are_you_sure_you_want_send_mail_sms_to_all_inactive_members_who_have_not_logged_in_for_days_days'
            ])
            ->assign([
                'aUsers' => $aUsers,
                'iDays' => $iDays,
                'alreadyHasForm' => true,
                'searchLink' => $this->url()->makeUrl('admincp.user.inactivereminder'),
                'noUseUserFeatures' => true,
                'noUseSearchBtn' => true,
            ])
            ->setTitle(_p('inactive_members'))
            ->setActiveMenu('admincp.member.inactivereminder')
            ->setBreadCrumb(_p('inactive_members'))
            ->setSectionTitle(_p('inactive_member_reminder'));
        Phpfox_Pager::instance()->set([
            'page' => $iPage,
            'size' => $iPageSize,
            'count' => $iCnt,
        ]);
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('user.component_controller_admincp_inactivereminder_clean')) ? eval($sPlugin) : false);
    }
}