<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Controller_Admincp_Add
 */
class User_Component_Controller_Admincp_Add extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $this->_setMenuName('admincp.user.browse');

        $bIsEdit = false;
        if (($iId = $this->request()->getInt('id'))) {
            if (($aUser = Phpfox::getService('user')->getForEdit($iId))) {
                $bIsEdit = true;

                if (!empty($aUser['birthday'])) {
                    $aUser = array_merge($aUser, Phpfox::getService('user')->getAgeArray($aUser['birthday']));
                }
                $this->template()->assign('aForms', $aUser);

                if (!Phpfox::isAdmin() && Phpfox::getService('user')->isAdminUser($aUser['user_id'])) {
                    return Phpfox_Error::display(_p('you_are_unable_to_edit_a_site_administrators_account'));
                }
            }
        }
        if (!isset($aUser)) {
            $this->url()->send('admincp', null, 'This section requires that you select.');
        }
        if (($aVals = $this->request()->getArray('val'))) {
            if ($bIsEdit) {
                $bAllowed = true;

                if (Phpfox::getParam('core.enable_register_with_phone_number')
                    && !empty($aVals['email_check'])
                    && !empty($aVals['phone_number_check'])
                    && empty($aVals['phone_number'])
                    && !filter_var(isset($aVals['email']) ? $aVals['email'] : '',FILTER_VALIDATE_EMAIL)) {
                    Phpfox_Error::set(_p('provide_a_valid_email_address_or_phone_number'));
                    $bAllowed = false;
                }

                if ($bAllowed && Phpfox::getService('user.process')->updateAdvanced($aUser['user_id'], $aVals, true)) {
                    Phpfox::getService('custom.process')->updateFields($aUser['user_id'], Phpfox::getUserId(), $this->request()->getArray('custom'));
                    if (Phpfox::getUserParam('user.can_edit_other_user_privacy')) {
                        Phpfox::getService('user.privacy.process')->update($aVals, $aUser['user_id']);
                    }

                    $this->url()->send('admincp.user.browse', _p('user_successfully_updated'));
                }
            }
        }

        $aUserGroups = [];
        foreach (Phpfox::getService('user.group')->get() as $aUserGroup) {
            $aUserGroups[$aUserGroup['user_group_id']] = Phpfox_Locale::instance()->convert($aUserGroup['title']);
        }

        $aLanguages = [];
        foreach (Phpfox::getService('language')->get(['l.user_select = 1']) as $aLanguage) {
            $aLanguages[$aLanguage['language_id']] = Phpfox::getLib('parse.output')->clean($aLanguage['title']);
        }

        $aEditForm = [
            'basic' => [
                'title' => _p('basic_information'),
                'data' => [
                    [
                        'title' => _p('display_name'),
                        'value' => (isset($aVals['full_name']) ? $aVals['full_name'] : (isset($aUser['full_name']) ? $aUser['full_name'] : '')),
                        'type' => 'input:text:check',
                        'id' => 'full_name',
                        'required' => true
                    ],
                    [
                        'title' => _p('username'),
                        'value' => (isset($aVals['user_name']) ? $aVals['user_name'] : (isset($aUser['user_name']) ? $aUser['user_name'] : '')),
                        'type' => 'input:text:check',
                        'id' => 'user_name',
                        'required' => true
                    ],
                    [
                        'title' => _p('password'),
                        'value' => '',
                        'type' => 'input:password:check',
                        'id' => 'password',
                        'required' => true
                    ],
                    [
                        'title' => _p('email'),
                        'value' => (isset($aVals['email']) ? $aVals['email'] : (isset($aUser['email']) ? $aUser['email'] : '')),
                        'type' => 'input:text:check',
                        'id' => 'email',
                        'required' => !Phpfox::getParam('core.enable_register_with_phone_number')
                    ],
                    [
                        'title' => _p('user_group'),
                        'value' => (isset($aVals['user_group_id']) ? $aVals['user_group_id'] : (isset($aUser['user_group_id']) ? $aUser['user_group_id'] : '')),
                        'type' => 'select',
                        'id' => 'user_group_id',
                        'options' => $aUserGroups,
                        'required' => true
                    ],
                    [
                        'title' => _p('location'),
                        'value' => (isset($aVals['country_iso']) ? $aVals['country_iso'] : (isset($aUser['country_iso']) ? $aUser['country_iso'] : '')),
                        'type' => 'select',
                        'id' => 'country_iso',
                        'options' => Phpfox::getService('core.country')->get()
                    ],
                    [
                        'title' => _p('city'),
                        'value' => (isset($aVals['city_location']) ? $aVals['city_location'] : (isset($aUser['city_location']) ? $aUser['city_location'] : '')),
                        'type' => 'input:text',
                        'id' => 'city_location'
                    ],
                    [
                        'title' => _p('zip_postal_code'),
                        'value' => (isset($aVals['postal_code']) ? $aVals['postal_code'] : (isset($aUser['postal_code']) ? $aUser['postal_code'] : '')),
                        'type' => 'input:text',
                        'id' => 'postal_code'
                    ],
                    [
                        'title' => _p('gender'),
                        'value' => (isset($aVals['gender']) ? $aVals['gender'] : (isset($aUser['gender']) ? $aUser['gender'] : '')),
                        'type' => 'select',
                        'id' => 'gender',
                        'options' => Phpfox::getService('core')->getGenders(),
                        'required' => Phpfox::getParam('user.require_basic_field') ? true : false
                    ],
                    [
                        'title' => _p('date_of_birth'),
                        'type' => 'date_of_birth'
                    ],
                    [
                        'title' => _p('time_zone'),
                        'value' => (isset($aVals['time_zone']) ? $aVals['time_zone'] : (isset($aUser['time_zone']) ? $aUser['time_zone'] : '')),
                        'type' => 'select',
                        'id' => 'time_zone',
                        'options' => Phpfox::getService('core')->getTimeZones()
                    ],
                    [
                        'title' => _p('spam_count'),
                        'value' => (isset($aVals['total_spam']) ? $aVals['total_spam'] : (isset($aUser['total_spam']) ? $aUser['total_spam'] : '')),
                        'type' => 'input:text',
                        'id' => 'total_spam'
                    ],
                    [
                        'title' => _p('primary_language'),
                        'value' => (isset($aVals['language_id']) ? $aVals['language_id'] : (isset($aUser['language_id']) ? $aUser['language_id'] : '')),
                        'type' => 'select',
                        'id' => 'language_id',
                        'options' => $aLanguages
                    ]
                ]
            ]
        ];

        if (Phpfox::getParam('core.enable_register_with_phone_number')) {
            array_splice($aEditForm['basic']['data'], 4, 0, [[
                'title' => _p('phone_number'),
                'value' => (isset($aVals['phone_number']) ? $aVals['phone_number'] : (isset($aUser['phone_number']) ? $aUser['phone_number'] : '')),
                'type' => 'input:text:check',
                'id' => 'phone_number',
                'html_id' => 'phone_number'
            ]]);
        }
        (($sPlugin = Phpfox_Plugin::get('user.component_controller_admincp_add')) ? eval($sPlugin) : false);

        if (Phpfox::getUserParam('user.can_edit_other_user_privacy')) {
            list($aUserPrivacy, $aEmailNotifications, $aProfiles, , $aSmsNotifications) = Phpfox::getService('user.privacy')->get($aUser['user_id']);
            $this->template()->assign([
                'aEmailNotifications' => $aEmailNotifications,
                'aSmsNotifications' => $aSmsNotifications,
                'aProfiles' => $aProfiles,
                'aUserPrivacy' => $aUserPrivacy
            ]);
        }
        $this->setParam('aUser', $aUser);

        $aSettings = Phpfox::getService('custom')->getForEdit(['user_main', 'user_panel', 'profile_panel'], $aUser['user_id'], $aUser['user_group_id'], false, $aUser['user_id']);

        $aCustomGenders = Phpfox::getService('user')->getCustomGenders($aUser);
        if($aCustomGenders) {
            $this->template()->setHeader('cache', [
                '<script>aUserGenderCustom = ' . json_encode($aCustomGenders) . '; bIsCustomGender = true;</script>'
            ]);
        }
        else {
            $this->template()->setHeader('cache', [
                '<script>aUserGenderCustom = {}; bIsCustomGender = false;</script>'
            ]);
        }

        $this->template()
            ->setSectionTitle(_p('members'))
            ->setTitle(_p('editing_member'))
            ->setBreadCrumb(_p('browse_members'), $this->url()->makeUrl('admincp.user.browse'))
            ->setBreadCrumb(($bIsEdit ? _p('editing_member') . ': ' . $aUser['full_name'] . ' (#' . $aUser['user_id'] . ')' : _p('add_new_member')), null, true)
            ->setActiveMenu('admincp.member.browse')
            ->setPhrase([
                    'loading_custom_fields'
                ]
            )
            ->setHeader('cache', [
                    'country.js' => 'module_core',
                    'jquery/plugin/intlTelInput.js' => 'static_script',
                    'admincp-add-user.js' => 'module_user'
                ]
            )
            ->assign([
                    'bIsEdit' => $bIsEdit,
                    'iFormUserId' => ($bIsEdit ? $aUser['user_id'] : ''),
                    'aEditForm' => $aEditForm,
                    'aSettings' => $aSettings,
                    'aUser' => $aUser,
                    'sDobStart' => Phpfox::getParam('user.date_of_birth_start'),
                    'sDobEnd' => Phpfox::getParam('user.date_of_birth_end')
                ]
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