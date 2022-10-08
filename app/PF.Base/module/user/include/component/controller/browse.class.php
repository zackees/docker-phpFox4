<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Controller_Browse
 */
class User_Component_Controller_Browse extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $sViewParam = $this->request()->get('view');
        $aSpecialPages = [
            'online',
            'featured',
        ];
        if (in_array($sViewParam, $aSpecialPages)) {
            $bOldWay = true;
        } else {
            $bOldWay = false;
        }

        $bEmptyBlockContent = false;
        if (!$this->request()->get('s')) {
            $oCache = Phpfox::getLib('cache');
            $userGroupId = Phpfox::getUserBy('user_group_id');
            $aBlockCache = $oCache->get('block_all_' . $userGroupId);
            $bEmptyBlockContent = !isset($aBlockCache['user.browse'][2]) || count($aBlockCache['user.browse'][2]) == 0;
            if (!$bEmptyBlockContent) {
                if (isset($aBlockCache['user.browse'][2]['user.users-you-may-know']) && Phpfox::isModule('friend')) {
                    $bEmptyBlockContent = empty(Phpfox::getService('friend.suggestion')->get(true));
                }

                if (isset($aBlockCache['user.browse'][2]['user.recently-active'])) {
                    $bEmptyBlockContent = empty(Phpfox::getService('user.featured')->getRecentActiveUsers());
                }
            }
        }

        if ($sPlugin = Phpfox_Plugin::get('user.component_controller_browse__1')) {
            eval($sPlugin);
            if (isset($aPluginReturn)) {
                return $aPluginReturn;
            }
        }

        $aCallback = $this->getParam('aCallback', false);
        $bIsAdminSearch = defined('PHPFOX_IS_ADMIN_SEARCH');

        if ($bIsAdminSearch) {
            if (($aIds = $this->request()->getArray('id')) && count($aIds)) {
                $sUrl = Phpfox::getLib('session')->get('admin_user_redirect');
                if (is_bool($sUrl)) {
                    $sUrl = 'current';
                }
                if ($this->request()->get('delete')) {
                    Phpfox::getUserParam('user.can_delete_others_account', true);
                    foreach ($aIds as $iId) {
                        if (Phpfox::getService('user')->isAdminUser($iId)) {
                            $this->url()->send($sUrl, null, _p('you_are_unable_to_delete_a_site_administrator'));
                        }

                        Phpfox::getService('user.auth')->setUserId($iId);
                        Phpfox::massCallback('onDeleteUser', $iId);
                        Phpfox::getService('user.auth')->setUserId(null);
                    }
                    $this->url()->send($sUrl, null, _p('user_s_successfully_deleted'));
                } elseif ($this->request()->get('ban') || $this->request()->get('unban')) {
                    $bHasAdmin = false;
                    foreach ($aIds as $iId) {
                        if (Phpfox::getService('user')->isAdminUser($iId)) {
                            $bHasAdmin = true;
                            continue;
                        }
                        Phpfox::getService('user.process')->ban($iId, ($this->request()->get('ban') ? 1 : 0));
                    }
                    if ($bHasAdmin) {
                        $this->url()->send($sUrl, null, _p('you_are_unable_to_ban_a_site_administrator'));
                    }
                    $this->url()->send($sUrl, null, ($this->request()->get('ban') ? _p('user_s_successfully_banned') : _p('user_s_successfully_un_banned')));
                } elseif ($this->request()->get('resend-verify')) {
                    foreach ($aIds as $iId) {
                        Phpfox::getService('user.verify.process')->sendMail($iId);
                    }
                    $this->url()->send($sUrl, null, _p('email_verification_s_sent'));
                } elseif ($this->request()->get('resend-verify-code')) {
                    foreach ($aIds as $iId) {
                        Phpfox::getService('user.verify.process')->resendSMS($iId);
                    }
                    $this->url()->send($sUrl, null, _p('passcode_verification_s_sent'));
                } elseif ($this->request()->get('verify')) {
                    foreach ($aIds as $iId) {
                        Phpfox::getService('user.verify.process')->adminVerify($iId);
                    }
                    $this->url()->send($sUrl, null, _p('user_s_verified'));
                } elseif ($this->request()->get('approve')) {
                    foreach ($aIds as $iId) {
                        Phpfox::getService('user.process')->userPending($iId, '1');
                    }
                    $this->url()->send($sUrl, null, _p('user_s_successfully_approved'));
                } elseif ($this->request()->get('move-to-group')) {
                    Phpfox::getUserParam('user.can_edit_user_group_membership', true);
                    if ($iGroupId = $this->request()->getInt('user_group_id')) {
                        foreach ($aIds as $iId) {
                            Phpfox::getService('user.process')->updateUserGroup($iId, $iGroupId);
                        }
                        $this->url()->send($sUrl, null, _p('user_s_successfully_move_to_new_group'));
                    } else {
                        Phpfox_Error::set(_p('please_select_a_user_group_to_move_users'));
                    }
                }
            } else {
                // Create a session so we know where we plan to redirect the admin after do action
                Phpfox::getLib('session')->set('admin_user_redirect', Phpfox_Url::instance()->getFullUrl());
            }
        }

        $aPages = $bIsAdminSearch ? [12, 24, 36, 48] : [21, 31, 41, 51];
        $aDisplays = [];
        foreach ($aPages as $iPageCnt) {
            $aDisplays[$iPageCnt] = _p('per_page', ['total' => $iPageCnt]);
        }

        $aSorts = [
            'u.full_name' => _p('name'),
            'u.last_activity' => _p('last_activity'),
            'u.last_login' => _p('last_login'),
        ];

        if ($bIsAdminSearch) {
            $aSorts['u.joined'] = _p('joined');
            $aSorts['ug.title'] = _p('groups');
            $aSorts['u.user_id'] = _p('id');
        }

        $aAge = [];
        for ($i = Phpfox::getService('user')->age(Phpfox::getService('user')->buildAge(1, 1, Phpfox::getParam('user.date_of_birth_end'))); $i <= Phpfox::getService('user')->age(Phpfox::getService('user')->buildAge(1, 1, Phpfox::getParam('user.date_of_birth_start'))); $i++) {
            $aAge[$i] = $i;
        }

        $iYear = date('Y');
        $aUserGroups = [];
        foreach (Phpfox::getService('user.group')->get() as $aUserGroup) {
            $aUserGroups[$aUserGroup['user_group_id']] = Phpfox_Locale::instance()->convert($aUserGroup['title']);
        }

        $aGenders = Phpfox::getService('core')->getGenders();
        $aGenders[''] = _p('all_members');

        if (($sPlugin = Phpfox_Plugin::get('user.component_controller_browse_genders'))) {
            eval($sPlugin);
        }

        $sDefaultOrderName = 'u.full_name';
        $sDefaultSort = 'ASC';
        if (Phpfox::getParam('user.user_browse_default_result') == 'last_login') {
            $sDefaultOrderName = 'u.last_login';
            $sDefaultSort = 'DESC';
        }

        if (!$bIsAdminSearch) {
            $searchFields = ['u.full_name', 'u.user_name', 'u.full_phone_number'];
            if (!empty($searchValues = $this->request()->get('search')) && isset($searchValues['search']) && $searchValues['search'] != '') {
                $canUseEmail = false;
                if (function_exists('filter_var') && filter_var($searchValues['search'], FILTER_VALIDATE_EMAIL)) {
                    $canUseEmail = true;
                } elseif (preg_match('/^[0-9a-zA-Z]([\-+.\w]*[0-9a-zA-Z]?)*@([0-9a-zA-Z\-.\w]*[0-9a-zA-Z]\.)+[a-zA-Z]{2,}$/', $searchValues['search']) && strlen($searchValues['value']) <= 100) {
                    $canUseEmail = true;
                }
                if ($canUseEmail) {
                    $searchFields[] = 'u.email';
                }
            }
            $this->search()->set([
                    'type' => 'user',
                    'field' => 'u.user_id',
                    'ignore_blocked' => true,
                    'search_tool' => [
                        'table_alias' => 'u',
                        'search' => [
                            'action' => $this->url()->makeUrl('user.browse'),
                            'default_value' => _p('search_users_dot'),
                            'name' => 'search',
                            'field' => $searchFields,
                        ],
                        'no_filters' => [_p('when')],
                    ],
                ]
            );
        }

        $bCustomSort = false;
        $aSearch = request()->get('search');
        if (!empty($aSearch) && isset($aSearch['sort'])) {
            $aSearchSort = explode(' ', $aSearch['sort']);
            if (in_array($aSearch['sort'], ['u.last_login', 'u.last_activity'])) {
                $sDefaultSort = 'DESC';
            } elseif ($aSearch['sort'] == 'u.full_name') {
                $sDefaultSort = 'ASC';
            }

            if (isset($aSearchSort[1])) {
                $sDefaultSort = $aSearchSort[1];
                $bCustomSort = true;
            }
        }

        if ($bIsAdminSearch) {
            $iDisplay = 12;
        } else {
            $iDisplay = 21;
        }
        if (!Phpfox::getParam('core.enable_register_with_phone_number')) {
            $aTypeFilter = [
                '0' => [_p('email_name'), 'AND ((u.full_name LIKE \'%[VALUE]%\' OR (u.email LIKE \'%[VALUE]@%\' OR u.email = \'[VALUE]\' OR u.user_name = \'[VALUE]\'))' . ($bIsAdminSearch ? ' OR u.email LIKE \'%[VALUE]%\'' : '') . ')'],
                '1' => [_p('email'), 'AND ((u.email LIKE \'%[VALUE]@%\' OR u.email = \'[VALUE]\'' . ($bIsAdminSearch ? ' OR u.email LIKE \'%[VALUE]%\'' : '') . '))'],
                '2' => [_p('name'), 'AND (u.full_name LIKE \'%[VALUE]%\')'],
            ];
        } else {
            $aTypeFilter = [
                '0' => [_p('email_name_phone'), 'AND ((u.full_name LIKE \'%[VALUE]%\' OR (u.email LIKE \'%[VALUE]@%\' OR u.email = \'[VALUE]\' OR u.user_name = \'[VALUE]\') OR u.full_phone_number LIKE \'[VALUE_PHONE]\')' . ($bIsAdminSearch ? ' OR u.email LIKE \'%[VALUE]%\'' : '') . ')'],
                '1' => [_p('email'), 'AND ((u.email LIKE \'%[VALUE]@%\' OR u.email = \'[VALUE]\'' . ($bIsAdminSearch ? ' OR u.email LIKE \'%[VALUE]%\'' : '') . '))'],
                '2' => [_p('name'), 'AND (u.full_name LIKE \'%[VALUE]%\')'],
                '3' => [_p('phone_number'), 'AND (u.full_phone_number LIKE \'[VALUE_PHONE]\')'],
            ];
        }
        $aFilters = [
            'display' => [
                'type' => 'select',
                'options' => $aDisplays,
                'default' => $iDisplay,
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
                    '4' => _p('online'),
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

        if (!Phpfox::getUserParam('user.can_search_by_zip')) {
            unset ($aFilters['zip']);
        }

        if ($bIsAdminSearch && Phpfox::getParam('core.enable_spam_check')) {
            $aFilters['status']['options']['7'] = _p('spammers');
        }

        if ($sPlugin = Phpfox_Plugin::get('user.component_controller_browse_filter')) {
            eval($sPlugin);
        }

        $aSearchParams = [
            'type' => 'browse',
            'filters' => $aFilters,
            'search' => 'keyword',
            'custom_search' => true,
        ];

        if (!$bIsAdminSearch) {
            $aSearchParams['no_session_search'] = true;
        }

        $oFilter = Phpfox_Search::instance()->set($aSearchParams);

        $sStatus = $oFilter->get('status');
        $sView = $this->request()->get('view');
        $aCustomSearch = $oFilter->getCustom();
        $bIsOnline = false;
        $bPendingMail = false;
        $mFeatured = false;
        $bIsGender = false;

        switch ((int)$sStatus) {
            case 1:
                $mFeatured = true;
                break;
            case 3:
                if ($bIsAdminSearch) {
                    $oFilter->setCondition('AND u.status_id = 1');
                }
                break;
            case 4:
                $bIsOnline = true;
                break;
            case 5:
                if ($bIsAdminSearch) {
                    $oFilter->setCondition('AND u.view_id = 1');
                }
                break;
            case 6:
                if ($bIsAdminSearch) {
                    $oFilter->setCondition('AND u.view_id = 2');
                }
                break;
            case 7:
                if ($bIsAdminSearch && Phpfox::getParam('core.enable_spam_check', 0)) {
                    $oFilter->setCondition('AND u.total_spam > ' . Phpfox::getParam('core.auto_deny_items'));
                }
                break;
            default:

                break;
        }

        if ($bCustomSort && isset($aSearchSort[0])) {
            $oFilter->setSort($aSearchSort[0]);
        }
        $this->template()->setTitle(_p('browse_members'))->setBreadCrumb(_p('browse_members'), ($aCallback !== false ? $this->url()->makeUrl($aCallback['url_home']) : $this->url()->makeUrl(($bIsAdminSearch ? 'admincp.' : '') . 'user.browse')));

        if (!empty($sView)) {
            switch ($sView) {
                case 'online':
                    $bIsOnline = true;
                    break;
                case 'featured':
                    $mFeatured = true;
                    break;
                case 'spam':
                    $oFilter->setCondition('u.total_spam > ' . (int)Phpfox::getParam('core.auto_deny_items'));
                    break;
                case 'pending':
                    if ($bIsAdminSearch) {
                        $oFilter->setCondition('u.view_id = 1');
                    }
                    break;
                case 'top':
                    $bExtendContent = true;
                    if (($iUserGenderTop = $this->request()->getInt('topgender'))) {
                        $oFilter->setCondition('AND u.gender = ' . (int)$iUserGenderTop);
                    }

                    $iFilterCount = 0;
                    $aFilterMenuCache = [];

                    $aFilterMenu = [
                        _p('all_members') => '',
                        _p('male') => '1',
                        _p('female') => '2',
                    ];

                    if ($sPlugin = Phpfox_Plugin::get('user.component_controller_browse_genders_top_users')) {
                        eval($sPlugin);
                    }

                    foreach ($aFilterMenu as $sMenuName => $sMenuLink) {
                        $iFilterCount++;
                        $aFilterMenuCache[] = [
                            'name' => $sMenuName,
                            'link' => $this->url()->makeUrl('user.browse', ['view' => 'top', 'topgender' => $sMenuLink]),
                            'active' => $this->request()->get('topgender') == $sMenuLink,
                            'last' => count($aFilterMenu) === $iFilterCount,
                        ];

                        if ($this->request()->get('topgender') == $sMenuLink) {
                            $this->template()->setTitle($sMenuName)->setBreadCrumb($sMenuName, null, true);
                        }
                    }

                    $this->template()->assign([
                            'aFilterMenus' => $aFilterMenuCache,
                        ]
                    );

                    break;
                default:

                    break;
            }
        }

        $bIsSearch = $bEmptyBlockContent;
        $bAgeSearch = false;
        if (($iFrom = $oFilter->get('from')) || ($iFrom = $this->request()->getInt('from'))) {
            $oFilter->setCondition('AND u.birthday_search <= \'' . Phpfox::getLib('date')->mktime(0, 0, 0, 1, 1, $iYear - $iFrom) . '\'' . ' AND ufield.dob_setting IN(0,1,2)');
            $bIsGender = true;
            $bAgeSearch = true;
        }
        if (($iTo = $oFilter->get('to')) || ($iTo = $this->request()->getInt('to'))) {
            $oFilter->setCondition('AND u.birthday_search >= \'' . Phpfox::getLib('date')->mktime(0, 0, 0, 1, 1, $iYear - $iTo) . '\'' . ' AND ufield.dob_setting IN(0,1,2)');
            $bIsGender = true;
            $bAgeSearch = true;
        }
        if ($bAgeSearch) {
            $oFilter->setCondition('AND u.birthday IS NOT NULL');
        }

        if (($sLocation = $this->request()->get('location'))) {
            $oFilter->setCondition('AND u.country_iso = \'' . Phpfox_Database::instance()->escape($sLocation) . '\'');
            $bIsSearch = true;
        }

        if (($sGender = $this->request()->getInt('gender'))) {
            $oFilter->setCondition('AND u.gender = \'' . Phpfox_Database::instance()->escape($sGender) . '\'');
            $bIsSearch = true;
        }

        if (($sLocationChild = $this->request()->getInt('state'))) {
            $oFilter->setCondition('AND ufield.country_child_id = \'' . Phpfox_Database::instance()->escape($sLocationChild) . '\'');
            $bIsSearch = true;
        }

        if (($sLocationCity = $this->request()->get('city-name'))) {
            $oFilter->setCondition('AND ufield.city_location = \'' . Phpfox_Database::instance()->escape(Phpfox::getLib('parse.input')->convert($sLocationCity)) . '\'');
            $bIsSearch = true;
        }

        if (!$bIsAdminSearch) {
            $oFilter->setCondition('AND u.status_id = 0 AND u.view_id = 0');
            if (Phpfox::isUser()) {
                $aBlockedUserIds = Phpfox::getService('user.block')->get(null, true);
                if (!empty($aBlockedUserIds)) {
                    $oFilter->setCondition('AND u.user_id NOT IN (' . implode(',', $aBlockedUserIds) . ')');
                }
            }
            if ($iGender = (int)$oFilter->get('gender')) {
                $oFilter->setCondition(in_array($iGender, [1, 2]) ? 'AND u.gender = ' . (int)$iGender : 'AND u.gender NOT IN (1,2)');
                $bIsSearch = true;
            }
        } else {
            $oFilter->setCondition('AND u.profile_page_id = 0');
        }

        if ($bIsAdminSearch && ($sIp = $oFilter->get('ip'))) {
            Phpfox::getService('user.browse')->ip($sIp);
        }

        $bExtend = ($bIsAdminSearch ? true : ($oFilter->get('show') && $oFilter->get('show') == '2') || (!$oFilter->get('show')));
        $iPage = $this->request()->getInt('page');
        $iPageSize = $oFilter->getDisplay();

        if (($sPlugin = Phpfox_Plugin::get('user.component_controller_browse_filter_process'))) {
            eval($sPlugin);
        }

        $iCnt = 0;
        $aUsers = [];

        if ($oFilter->isSearch() || $bIsAdminSearch || $bIsSearch) {
            $aConditions = $oFilter->getConditions();
            $sSort = $oFilter->getSort();
            $aTempCustomSearch = $aCustomSearch;
            foreach ($aTempCustomSearch as $key => $customSearch) {
                if (!is_numeric($customSearch) && is_string($customSearch)) {
                    $aTempCustomSearch[$key] = html_entity_decode($customSearch, ENT_QUOTES, 'UTF-8');
                }
            }
            list($iCnt, $aUsers) = Phpfox::getService('user.browse')->conditions($aConditions)
                ->callback($aCallback)
                ->sort($sSort)
                ->page($oFilter->getPage())
                ->limit($iPageSize)
                ->online($bIsOnline)
                ->extend((isset($bExtendContent) ? true : $bExtend))
                ->featured($mFeatured)
                ->pending($bPendingMail)
                ->custom($aTempCustomSearch)
                ->gender($bIsGender)
                ->pendingType($bIsAdminSearch)
                ->get();

            $aUserExport = [
                'aConditions' => $aConditions,
                'sSort' => $sSort,
                'bIsOnline' => $bIsOnline,
                'mFeatured' => $mFeatured,
                'aCustomSearch' => $aCustomSearch,
                'bIsGender' => $bIsGender,
            ];

            if ($bIsAdminSearch && $sIp) {
                $aUserExport['aConditions']['ip'] = $sIp;
            }

            $this->template()->setHeader('cache', [
                '<script>window.sUserExportFilter = "' . base64_encode(json_encode($aUserExport)) . '";</script>',
            ]);
        } else {
            if ($bOldWay) {
                list($iCnt, $aUsers) = Phpfox::getService('user.browse')->conditions($oFilter->getConditions())
                    ->callback($aCallback)
                    ->sort($oFilter->getSort())
                    ->page($oFilter->getPage())
                    ->limit($iPageSize)
                    ->online($bIsOnline)
                    ->extend((isset($bExtendContent) ? true : $bExtend))
                    ->featured($mFeatured)
                    ->pending($bPendingMail)
                    ->custom($aCustomSearch)
                    ->gender($bIsGender)
                    ->get();
            }
            $this->template()->assign([
                'highlightUsers' => 1,
            ]);
        }

        $iCnt = $oFilter->getSearchTotal($iCnt);
        $aNewCustomValues = [];
        if ($aCustomValues = $this->request()->get('custom')) {
            if (is_array($aCustomValues)) {
                foreach ($aCustomValues as $iKey => $sCustomValue) {
                    $aNewCustomValues['custom[' . $iKey . ']'] = $sCustomValue;
                }
            }
        }
        if (!($bIsAdminSearch)) {
            Phpfox_Pager::instance()->set([
                'page' => $iPage,
                'size' => $iPageSize,
                'count' => $iCnt,
                'ajax' => 'user.mainBrowse',
                'aParams' => $aNewCustomValues,
            ]);
        } else {
            Phpfox_Pager::instance()->set(['page' => $iPage, 'size' => $iPageSize, 'count' => $iCnt]);
        }

        Phpfox_Url::instance()->setParam('page', $iPage);

        if ($this->request()->get('featured') == 1) {
            $this->template()->setHeader([
                    'drag.js' => 'static_script',
                    '<script type="text/javascript">$Behavior.coreDragInit = function() { Core_drag.init({table: \'#js_drag_drop\', ajax: \'user.setFeaturedOrder\'}); }</script>',
                ]
            )
                ->assign(['bShowFeatured' => 1]);
        }
        foreach ($aUsers as $iKey => $aUser) {
            if (!isset($aUser['user_group_id']) || empty($aUser['user_group_id']) || $aUser['user_group_id'] < 1) {
                $aUser['user_group_id'] = $aUsers[$iKey]['user_group_id'] = 5;
                Phpfox::getService('user.process')->updateUserGroup($aUser['user_id'], 5);
                $aUsers[$iKey]['user_group_title'] = _p('user_banned');
            }
            $aBanned = Phpfox::getService('ban')->isUserBanned($aUser);
            $aUsers[$iKey]['is_banned'] = $aBanned['is_banned'];
            $aUsers[$iKey]['is_friend_request'] = (Phpfox::isModule('friend') && Phpfox::getService('friend.request')->isRequested($aUser['user_id'], Phpfox::getUserId(), false, true)) ? 3 : 0;
        }
        list($bFieldExist, $aCustomFields) = Phpfox::getService('custom')->getForPublic('user_profile', 0, true, $aCustomSearch);

        $this->template()
            ->setHeader('cache', [
                    'country.js' => 'module_core',
                ]
            )->setPhrase(['friend_request_sent', 'request_sent', 'cancel_request', 'remove_friend', 'friend', 'add_as_friend'])
            ->assign([
                    'aUsers' => $aUsers,
                    'bExtend' => $bExtend,
                    'aCallback' => $aCallback,
                    'bIsSearch' => $oFilter->isSearch(),
                    'bIsInSearchMode' => ($this->request()->getInt('search-id') ? true : false),
                    'aForms' => $aCustomSearch,
                    'aCustomFields' => $aCustomFields,
                    'bShowAdvSearch' => $bFieldExist,
                    'sView' => $sView,
                    'sStatus' => $sStatus,
                    'bOldWay' => $bOldWay,
                ]
            );
        // add breadcrumb if its in the featured members page and not in admin
        if (!($bIsAdminSearch)) {
            Phpfox::getUserParam('user.can_browse_users_in_public', true);

            $this->template()->setHeader('cache', [
                    'browse.js' => 'module_user',
                ]
            );

            if ($this->request()->get('view') == 'featured') {
                $this->template()->setBreadCrumb(_p('featured_members'), $this->url()->makeUrl('current'), true);

                $sTitle = _p('title_featured_members');
                if (!empty($sTitle)) {
                    $this->template()->setTitle($sTitle);
                }
            } elseif ($this->request()->get('view') == 'online') {
                $this->template()->setBreadCrumb(_p('menu_who_s_online'), $this->url()->makeUrl('current'), true);
                $sTitle = _p('title_who_s_online');
                if (!empty($sTitle)) {
                    $this->template()->setTitle($sTitle);
                }
            }
        } else {
            $this->template()->setHeader('cache', [
                'admincp.js' => 'module_user'
            ]);
        }

        if ($aCallback !== false) {
            $this->template()->rebuildMenu('user.browse', $aCallback['url'])->removeUrl('user.browse', 'user.browse.view_featured');
        }

        $this->setParam('mutual_list', true);

        return null;
    }
}