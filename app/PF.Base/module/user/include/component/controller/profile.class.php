<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Controller_Profile
 */
class User_Component_Controller_Profile extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {

        Phpfox::isUser(true);
        $bIsEdit = false;

        $iUserId = Phpfox::getUserId();
        $iUserGroupId = Phpfox::getUserBy('user_group_id');
        if (($iId = $this->request()->getInt('id')) && Phpfox::getUserParam('custom.can_edit_other_custom_fields') && $iId != Phpfox::getUserId()) {
            if (($aUser = Phpfox::getService('user')->getUser($iId, 'u.user_id, u.user_name, u.full_name')) && isset($aUser['user_id'])) {
                $iUserId = $aUser['user_id'];
                $iUserGroupId = $aUser['user_group_id'];
                $this->template()->assign('iUserId', $iUserId);
                $bIsEdit = true;

                if ($aVals = $this->request()->getArray('custom')) {
                    if (Phpfox::getService('custom.process')->updateFields($iUserId, $iUserId, $aVals)) {
                        $this->url()->send($aUser['user_name'], null, _p('successfully_updated_full_name_profile', array('full_name' => $aUser['full_name'])));
                    }
                }
            }
        }

        if (Phpfox::getUserParam('custom.can_edit_own_custom_field')) {
            $aCustomGroups = Phpfox::getService('custom.group')->getGroups('user_profile', $iUserGroupId);
            $aCustomFields = Phpfox::getService('custom')->getForEdit(array('user_main', 'user_panel', 'profile_panel'), $iUserId, $iUserGroupId, false, Phpfox::getUserId());
        } else {
            $aCustomGroups = [];
            $aCustomFields = [];
        }

        $aGroupCache = array();
        foreach ($aCustomFields as $aFields) {
            $aGroupCache[$aFields['group_id']] = true;
        }

        if ($sPlugin = Phpfox_Plugin::get('user.component_controller_profile__1')) {
            eval($sPlugin);
            if (isset($aPluginReturn)) {
                return $aPluginReturn;
            }
        }

        if (!empty($aCustomGroups)) {
            foreach ($aCustomGroups as $iKey => &$aCustomGroup) {
                if ($sPlugin = Phpfox_Plugin::get('user.component_controller_profile__2')) {
                    eval($sPlugin);
                    if (isset($aPluginReturn)) {
                        return $aPluginReturn;
                    }
                }
                if (!isset($aGroupCache[$aCustomGroup['group_id']])) {
                    unset($aCustomGroups[$iKey]);
                }

                if ($aCustomGroup['phrase_var_name'] == 'user.custom_group_about_me') {
                    $aCustomGroup['ico'] = 'ico-user-circle-o';
                } else {
                    $aCustomGroup['ico'] = 'ico-merge-file-o';
                }

                $aCustomGroup['phrase_var_name'] = _p($aCustomGroup['phrase_var_name']);
            }

            $aRebuildKeys = $aCustomGroups;
            $aCustomGroups = array();
            foreach ($aRebuildKeys as $aRebuildKey) {
                $aCustomGroups[] = $aRebuildKey;
            }
        }

        if (!empty($aGroupCache[0])) {
            $aCustomGroups = array_merge($aCustomGroups, [
                [
                    'phrase_var_name' => 'general',
                    'group_id' => 0,
                    'ico' => 'ico-merge-file-o',
                ]
            ]);
        }

        $aTimeZones = Phpfox::getService('core')->getTimeZones();
        if (count($aTimeZones) > 100) // we are using the php 5.3 way
        {
            $this->template()->setHeader('cache', array('setting.js' => 'module_user'))
                ->setHeader('cache', array(
                        '<script type="text/javascript">sSetTimeZone = "' . Phpfox::getUserBy('time_zone') . '";</script>'
                    )
                );
        }

        $aForms = Phpfox::getService('user')->get(Phpfox::getUserId(), true);
        if (!empty($aForms['country_iso'])) {
            $this->setParam('country_child_value', $aForms['country_iso']);
        }
        
        /* we could put this part inside get but I fear its being wrongly used */
        $aRelation = Phpfox::getService('custom.relation')->getLatestForUser(Phpfox::getUserId(), null, true);
        if (isset($aRelation['status_id'])) {
            $sameRelationship = Phpfox::getService('custom.relation')->hasSameRelationship($aRelation['with_user_id'], $aRelation['relation_data_id']);
            if ($aRelation['status_id'] != 1 && !empty($aRelation['with_user']) && !$sameRelationship) {
                unset($aRelation['with_user']);
            }
            $aForms = array_merge($aForms, $aRelation);
        }

        $sJsArray = '{';
        $aRelations = Phpfox::getService('custom.relation')->getAll();
        foreach ($aRelations as $aItem) {
            if ($aItem['confirmation'] == 1) {
                $sJsArray .= $aItem['relation_id'] . ':' . $aItem['confirmation'] . ',';
            }
        }
        $sJsArray = rtrim($sJsArray, ',') . '}';

        $aForms['month'] = substr($aForms['birthday'], 0, 2);
        $aForms['day'] = substr($aForms['birthday'], 2, 2);
        $aForms['year'] = substr($aForms['birthday'], 4);

        if (Phpfox::getUserParam('user.can_add_custom_gender')) {
            $aCustomGenders = Phpfox::getService('user')->getCustomGenders($aForms);
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
        }

        if (Phpfox::isModule('friend')) {
            $this->template()->setPhrase(['show_more_results_for_search_term']);
        }

        if ($sPlugin = Phpfox_Plugin::get('user.component_controller_profile__3')) {
            eval($sPlugin);
            if (isset($aPluginReturn)) {
                return $aPluginReturn;
            }
        }

        $this->template()->setTitle(_p('edit_profile'))
            ->setBreadCrumb(_p('edit_profile'))
            ->setHeader(array(
                    'country.js' => 'module_core',
                    'custom.js' => 'module_custom',
                    'search.js' => 'module_friend',
                    'edit-profile.css' => 'module_user'
                )
            )
            ->assign(array(
                    'aGroups' => $aCustomGroups,
                    'aSettings' => $aCustomFields,
                    'bIsEdit' => $bIsEdit,
                    'sDobStart' => Phpfox::getParam('user.date_of_birth_start'),
                    'sDobEnd' => Phpfox::getParam('user.date_of_birth_end'),
                    'aTimeZones' => $aTimeZones,
                    'aForms' => $aForms,
                    'sJsArray' => $sJsArray,
                    'aRelations' => Phpfox::getService('custom')->getRelations()
                )
            );
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('user.component_controller_profile_clean')) ? eval($sPlugin) : false);
    }
}
