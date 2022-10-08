<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Profile_Component_Block_Info
 */
class Profile_Component_Block_Info extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $aUser = $this->getParam('aUser');
        $templateObject = $this->template();
        if ($hasAccess = Phpfox::getService('user.privacy')->hasAccess($aUser['user_id'], 'profile.basic_info')) {
            $aUser['bRelationshipHeader'] = true;
            $sRelationship = Phpfox::getService('custom')->getRelationshipPhrase($aUser, [], [], '', true);
            $aUserDetails = [];
            if (!empty($aUser['gender'])) {
                if (empty($aUser['custom_gender'])) {
                    $aUserDetails[_p('gender')] = '<a href="' . $this->url()->makeUrl('user.browse',
                            ['gender' => $aUser['gender']]) . '">' . Phpfox::getLib('parse.output')->clean($aUser['gender_name']) . '</a>';
                } else {
                    $aCustomGenders = Phpfox::getLib('parse.format')->isSerialized($aUser['custom_gender']) ? unserialize($aUser['custom_gender']) : $aUser['custom_gender'];
                    $sGender = '';
                    if (is_array($aCustomGenders)) {
                        if (count($aCustomGenders) > 2) {
                            $sLastGender = $aCustomGenders[count($aCustomGenders) - 1];
                            unset($aCustomGenders[count($aCustomGenders) - 1]);
                            $sGender = implode(', ', $aCustomGenders) . ' ' . _p('and') . ' ' . $sLastGender;
                        } else {
                            $sGender = implode(' ' . _p('and') . ' ', $aCustomGenders);
                        }
                    }
                    $aUserDetails[_p('gender')] = Phpfox::getLib('parse.output')->clean($sGender);
                }
            }

            $aUserDetails = array_merge($aUserDetails, $aUser['birthdate_display']);

            $sExtraLocation = '';

            if (!empty($aUser['city_location'])) {
                $sExtraLocation .= '<a href="' . $this->url()->makeUrl('user.browse', [
                        'location' => $aUser['country_iso'],
                        'state' => $aUser['country_child_id'],
                        'city-name' => $aUser['city_location']
                    ]) . '">' . Phpfox::getLib('parse.output')->clean($aUser['city_location']) . '</a> &raquo;';
            }

            if ($aUser['country_child_id'] > 0 && $sChild = Phpfox::getService('core.country')->getChild($aUser['country_child_id'])) {
                $sExtraLocation .= ' <a href="' . $this->url()->makeUrl('user.browse', [
                        'location' => $aUser['country_iso'],
                        'state' => $aUser['country_child_id']
                    ]) . '">' . $sChild . '</a> &raquo;';
            }

            if (!empty($aUser['country_iso']) && Phpfox::getService('user.privacy')->hasAccess($aUser['user_id'],
                    'profile.view_location')) {
                $aUserDetails[_p('location')] = $sExtraLocation . ' <a href="' . $this->url()->makeUrl('user.browse',
                        ['location' => $aUser['country_iso']]) . '">' . Phpfox::getPhraseT($aUser['location'],
                        'country') . '</a>';
            }

            if ((int)$aUser['last_login'] > 0 && ((!$aUser['is_invisible']) || (Phpfox::getUserParam('user.can_view_if_a_user_is_invisible') && $aUser['is_invisible']))) {
                $aUserDetails[_p('last_login')] = Phpfox::getLib('date')->convertTime($aUser['last_login'],
                    'core.global_update_time');
            }

            if ((int)$aUser['joined'] > 0) {
                $aUserDetails[_p('member_since')] = Phpfox::getLib('date')->convertTime($aUser['joined'],
                    'core.global_update_time');
            }

            if (Phpfox::getUserGroupParam($aUser['user_group_id'], 'profile.display_membership_info')) {
                $aUserDetails[_p('membership')] = (empty($aUser['icon_ext']) ? '' : '<img src="' . Phpfox::getParam('core.url_icon') . $aUser['icon_ext'] . '" class="v_middle" alt="' . Phpfox_Locale::instance()->convert($aUser['title']) . '" title="' . Phpfox_Locale::instance()->convert($aUser['title']) . '" /> ') . $aUser['prefix'] . Phpfox_Locale::instance()->convert($aUser['title']) . $aUser['suffix'];
            }

            $aUserDetails[_p('profile_views')] = $aUser['total_view'];

            if (Phpfox::isAppActive('Core_RSS') && Phpfox::getParam('rss.display_rss_count_on_profile') && Phpfox::getService('user.privacy')->hasAccess($aUser['user_id'],
                    'rss.display_on_profile')) {
                $aUserDetails[_p('rss_subscribers')] = (Phpfox::getUserId() == $aUser['user_id']) ? '<a href="#" onclick="tb_show(\'' . _p('rss_subscribers_log') . '\', $.ajaxBox(\'rss.log\', \'height=500&amp;width=500&amp\')); return false;">' . $aUser['rss_count'] . '</a>' : $aUser['rss_count'];
            }

            $sEditLink = '';
            if ($aUser['user_id'] == Phpfox::getUserId()) {
                $sEditLink = '<div class="js_edit_header_bar">';
                $sEditLink .= '<span id="js_user_basic_info" style="display:none;"><img src="' . $templateObject->getStyle('image',
                        'ajax/small.gif') . '" alt="" class="v_middle" /></span>';
                $sEditLink .= '<a href="' . Phpfox_Url::instance()->makeUrl('user.profile') . '" id="js_user_basic_edit_link" class="btn btn-primary">';
                $sEditLink .= '<i class="ico ico-textedit mr-1"></i>' . _p('update_profile_info');
                $sEditLink .= '</a>';
                $sEditLink .= '</div>';
            }

            // Get user info
            $aInfo = [];
            if ($aUser['user_id'] == Phpfox::getUserId()) {
                $totalUploadSpace = Phpfox::getUserParam('user.total_upload_space') * 1048576;
                $totalSpaceUsed = $aUser['space_total'];
                if ($totalUploadSpace > 0 && $totalSpaceUsed > $totalUploadSpace) {
                    $totalSpaceUsed = $totalUploadSpace;
                }
                $aInfo = [_p('space_used') => ($totalUploadSpace === 0 ? _p('space_total_out_of_unlimited', ['space_total' => Phpfox_File::instance()->filesize($totalSpaceUsed)]) : _p('space_total_out_of_total_unit', ['space_total' => Phpfox_File::instance()->filesize($totalSpaceUsed), 'total_unit' => Phpfox_File::instance()->filesize($totalUploadSpace)]))];
            }

            // Get the Smoker and Drinker details
            $bShowCustomFields = $this->getParam('show_custom_fields', true);
            $templateObject->assign([
                    'aUserDetails' => $aUserDetails,
                    'sBlockJsId' => 'profile_basic_info',
                    'sRelationship' => trim($sRelationship),
                    'bShowCustomFields' => $bShowCustomFields,
                    'aInfos' => $aInfo
                ]
            );

            (($sPlugin = Phpfox_Plugin::get('profile.component_block_info')) ? eval($sPlugin) : false);

            $templateObject->assign([
                    'sHeader' => $sEditLink . _p('basic_info'),
                    'sEditLink' => $sEditLink
                ]
            );
        }

        $templateObject->assign('hasAccess', $hasAccess);

        return 'block';
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('profile.component_block_info_clean')) ? eval($sPlugin) : false);
    }
}
