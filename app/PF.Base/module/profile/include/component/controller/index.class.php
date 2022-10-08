<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Profile_Component_Controller_Index
 */
class Profile_Component_Controller_Index extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $mUser = $this->request()->get('req1');
        $sSection = $this->request()->get('req2');
        if (!empty($sSection)) {
            $sSection = $this->url()->reverseRewrite($sSection);
        }

        $bIsSubSection = false;
        if (!empty($sSection) && Phpfox::isModule($sSection)) {
            $bIsSubSection = true;
        } else {
            $this->template()->assign('bNotShowActionButton', true);
        }

        (($sPlugin = Phpfox_Plugin::get('profile.component_controller_index_process_after_requests')) ? eval($sPlugin) : false);

        if (!$mUser) {
            if (Phpfox::isUser()) {
                $this->url()->send('profile');
            } else {
                Phpfox::isUser(true);
            }
        }

        // If we are unable to find a user lets make sure we return a 404 page not found error
        $aRow = Phpfox::getService('user')->get($mUser, false);

        if ((!isset($aRow['user_id'])) || (isset($aRow['user_id']) && $aRow['profile_page_id'] > 0)) {
            if (empty($aRow['profile_page_id']) && $this->request()->get('req2') != '' && Phpfox::isModule($this->request()->get('req2'))) {
                if (preg_match('/profile-(.*)/i', $this->request()->get('req1'), $aProfileMatches)) {
                    if (isset($aProfileMatches[1]) && is_numeric($aProfileMatches[1])) {
                        $aActualUser = Phpfox::getService('user')->getUser($aProfileMatches[1]);
                        if (isset($aActualUser['user_id'])) {
                            $aAllRequests = $this->request()->getRequests();
                            $aActualRequests = [];
                            foreach ($aAllRequests as $mKey => $mValue) {
                                if ($mKey == PHPFOX_GET_METHOD || $mValue == $this->request()->get('req1')) {
                                    continue;
                                }

                                if (substr($mKey, 0, 3) == 'req') {
                                    $aActualRequests[] = $mValue;
                                } else {
                                    $aActualRequests[$mKey] = $mValue;
                                }
                            }

                            header('HTTP/1.1 301 Moved Permanently');

                            $this->url()->send($aActualUser['user_name'], $aActualRequests);
                        }
                    }
                }
            }

            if (Phpfox::isAppActive('Core_Pages') && Phpfox::getService('pages')->isPage($this->request()->get('req1'))) {
                return Phpfox_Module::instance()->setController('pages.view');
            }
            if (Phpfox::isAppActive('PHPfox_Groups') && Phpfox::getService('groups')->isPage($this->request()->get('req1'))) {
                return Phpfox_Module::instance()->setController('groups.view');
            }

            if ($sPlugin = Phpfox_Plugin::get('profile.component_controller_index_process_check_is_page')) {
                eval($sPlugin);
            }

            return Phpfox_Module::instance()->setController('error.404');
        }

        if (Phpfox::isUser() && Phpfox::getService('user.block')->isBlocked(null, $aRow['user_id'])) {
            return Phpfox_Module::instance()->setController('error.invalid');
        }

        if (!Phpfox::isAdmin() && $aRow['view_id'] != 0) {
            return Phpfox_Module::instance()->setController('error.404');
        }

        $oUser = Phpfox::getService('user');

        if (empty($aRow['dob_setting'])) {
            switch (Phpfox::getParam('user.default_privacy_brithdate')) {
                case 'month_day':
                    $aRow['dob_setting'] = '1';
                    break;
                case 'show_age':
                    $aRow['dob_setting'] = '2';
                    break;
                case 'hide':
                    $aRow['dob_setting'] = '3';
                    break;
            }
        }

        $sGenderName = '';
        if ($aRow['gender'] == 127) {
            $aCustomGenders = Phpfox::getService('user')->getCustomGenders($aRow);
            if (is_array($aCustomGenders)) {
                $iCount = count($aCustomGenders);
                switch ($iCount) {
                    case 1:
                        $sGenderName = $aCustomGenders[0];
                        break;
                    case 2:
                        $sGenderName = implode(' ' . _p('and') . ' ', $aCustomGenders);
                        break;
                    default:
                        if (count($aCustomGenders) > 2) {
                            $sLastGender = array_pop($aCustomGenders);
                            $sGenderName = implode(', ', $aCustomGenders);
                            $sGenderName .= ' ' . _p('and') . ' ' . $sLastGender;
                        }
                        break;
                }
            }
        } else {
            $sGenderName = $oUser->gender($aRow['gender']);
        }
        $aRow['gender_name'] = $sGenderName;

        $aRow['birthday_time_stamp'] = $aRow['birthday'];
        $aRow['birthday'] = $oUser->age($aRow['birthday']);
        $aRow['location'] = Phpfox::getPhraseT(Phpfox::getService('core.country')->getCountry($aRow['country_iso']),
            'country');
        if (isset($aRow['country_child_id']) && $aRow['country_child_id'] > 0) {
            $aRow['location_child'] = Phpfox::getService('core.country')->getChild($aRow['country_child_id']);
        }
        $aRow['birthdate_display'] = Phpfox::getService('user')->getProfileBirthDate($aRow);
        $aRow['is_user_birthday'] = empty($aRow['birthday_time_stamp']) ? false : (int)floor(Phpfox::getLib('date')->daysToDate($aRow['birthday_time_stamp'],
                null, false)) === 0;

        $this->setParam('aUser', $aRow);
        define('PHPFOX_CURRENT_TIMELINE_PROFILE', $aRow['user_id']);

        $aProfileLinks = Phpfox::getService('profile')->getProfileMenu($aRow);
        if ($aRow['user_id'] != Phpfox::getUserId()) {
            foreach ($aProfileLinks as $iKey => $aProfileLink) {
                if ($aProfileLink['actual_url'] == "profile_activity-statistics" || $aProfileLink['actual_url'] == "profile_attachment") {
                    continue;
                }

                $aModules = explode('_', $aProfileLink['actual_url']);
                $sModule = $aModules[1];
                if (!Phpfox::getService('user.privacy')->hasAccess($aRow['user_id'], $sModule . '.' . $aProfileLink['actual_url'] . '_menu')) {
                    unset($aProfileLinks[$iKey]);
                }
            }
        }

        $this->template()
            ->assign([
                    'aUser'         => $aRow,
                    'aProfileLinks' => $aProfileLinks,
                    'bIsBlocked'    => (Phpfox::isUser() ? Phpfox::getService('user.block')->isBlocked(Phpfox::getUserId(),
                        $aRow['user_id']) : false),
                    'bOwnProfile'   => $aRow['user_id'] == Phpfox::getUserId()
                ]
            );

        if (Phpfox::getService('user.block')->isBlocked($aRow['user_id'],
                Phpfox::getUserId()) && !Phpfox::getUserParam('user.can_override_user_privacy')) {
            return Phpfox_Module::instance()->setController('profile.private');
        }

        Phpfox::getUserParam('profile.can_view_users_profile', true);

        // Set it globally that we are viewing a users profile, sometimes variables don't help.
        if (!defined('PHPFOX_IS_USER_PROFILE')) {
            define('PHPFOX_IS_USER_PROFILE', true);
        }

        $this->setParam([
                'sTrackType'      => 'profile',
                'iTrackId'        => $aRow['user_id'],
                'iTrackUserId'    => $aRow['user_id'],
                'bIsProfileIndex' => true,
                'sType'           => 'profile',
                'iItemId'         => $aRow['user_id'],
                'iTotal'          => $aRow['total_comment'],
                'user_id'         => $aRow['user_id'],
                'user_group_id'   => $aRow['user_group_id'],
                'edit_user_id'    => $aRow['user_id'],
                'item_id'         => $aRow['user_id'],
                'mutual_list'     => true
            ]
        );

        $this->template()
            ->setPhrase([
                'are_you_sure_you_want_to_remove_this_cover_photo',
                'please_enter_only_numbers',
                'gift_point_must_be_positive_number',
                'upload_failed_please_try_uploading_valid_or_smaller_image',
                'close_without_save_your_changes',
                'update_profile_picture'
            ])
            ->setHeader('cache', [
                    'jquery/plugin/jquery.scrollTo.js'      => 'static_script',
                    'jquery/plugin/jquery.highlightFade.js' => 'static_script',
                    'jquery.cropit.js'                      => 'module_user',
                    '<script>$Core.serverId = ' . Phpfox_Request::instance()->getServer('PHPFOX_SERVER_ID') . ';</script>'
                ]
            );

        (($sPlugin = Phpfox_Plugin::get('profile.component_controller_index_process_is_sub_section')) ? eval($sPlugin) : false);

        if (((Phpfox::isModule('friend') && Phpfox::getParam('friend.friends_only_profile')))
            && empty($aRow['is_friend'])
            && !Phpfox::getUserParam('user.can_override_user_privacy')
            && $aRow['user_id'] != Phpfox::getUserId()
        ) {
            return Phpfox_Module::instance()->setController('profile.private');
        }
        if ($this->request()->get('req2') == 'activity-statistics') {
            return Phpfox_Module::instance()->setController('profile.statistics');
        }
        if ($bIsSubSection === true) {
            if (substr($sSection, 0, 1) == '@') {
                $this->setParam('app_section', $sSection);

                return Phpfox_Module::instance()->setController('profile.app');
            }

            if (Phpfox::hasCallback($sSection, 'getProfileLink')) {
                $this->template()->setUrl(Phpfox::callback($sSection . '.getProfileLink'));
            }

            return Phpfox_Module::instance()->setController($sSection . '.profile');
        }

        if (!Phpfox::getService('user.privacy')->hasAccess($aRow['user_id'], 'profile.view_profile')) {
            return Phpfox_Module::instance()->setController('profile.private');
        }

        Phpfox::getService('profile')->setUserId($aRow['user_id']);

        (($sPlugin = Phpfox_Plugin::get('profile.component_controller_index_process_start')) ? eval($sPlugin) : false);

        if (!isset($aRow['is_viewed'])) {
            $aRow['is_viewed'] = 0;
        }

        if (Phpfox::getParam('profile.profile_caches') != true &&
            (Phpfox::isUser() && Phpfox::getUserId() != $aRow['user_id'] &&
                (!$aRow['is_viewed']) &&
                !Phpfox::getUserBy('is_invisible'))) {
            if (Phpfox::isModule('track')) {
                Phpfox::getService('track.process')->add('profile', $aRow['user_id']);
            }
            Phpfox::getService('user.field.process')->update($aRow['user_id'], 'total_view', ($aRow['total_view'] + 1));
        }

        if (Phpfox::getParam('profile.profile_caches') != true && isset($aRow['is_viewed']) && Phpfox::isUser() && Phpfox::isModule('track') && Phpfox::getUserId() != $aRow['user_id'] && $aRow['is_viewed'] && !Phpfox::getUserBy('is_invisible')) {
            Phpfox::getService('track.process')->update('user', $aRow['user_id']);
        }


        $this->template()->assign([
                'bIsUserProfileIndexPage' => true
            ]
        );

        if (Phpfox::isAppActive('Core_RSS') && Phpfox::isModule('feed') && Phpfox::getService('user.privacy')->hasAccess($aRow['user_id'],
                'rss.can_subscribe_profile')) {
            $this->template()->setHeader('<link rel="alternate" type="application/rss+xml" title="' . _p('updates_from') . ': ' . Phpfox::getLib('parse.output')->clean($aRow['full_name']) . '" href="' . $this->url()->makeUrl($aRow['user_name'],
                    ['rss']) . '" />');
            $this->template()->assign('bShowRssFeedForUser', true);
        }

        (($sPlugin = Phpfox_Plugin::get('profile.component_controller_index_process_section')) ? eval($sPlugin) : false);

        if ($this->request()->get('req2') == 'info'
            || !Phpfox::getService('user.privacy')->hasAccess($aRow['user_id'], 'feed.view_wall')
        ) {
            if (!$this->request()->get('status-id')
                && !$this->request()->get('comment-id')
                && !$this->request()->get('link-id')
                && !$this->request()->get('poke-id')
                && !$this->request()->get('feed')
            ) {
                return Phpfox_Module::instance()->setController('profile.info');
            } else if (!Phpfox::getService('user.privacy')->hasAccess($aRow['user_id'], 'feed.view_wall')) {
                return Phpfox_Module::instance()->setController('profile.private');
            }
        }

        $sPageTitle = Phpfox::getService('profile')->getProfileTitle($aRow);

        if (!defined('PHPFOX_IS_USER_PROFILE_INDEX')) {
            define('PHPFOX_IS_USER_PROFILE_INDEX', true);
        }

        define('PHPFOX_CURRENT_USER_PROFILE', $aRow['user_id']);

        $sDescription = _p('full_name_is_on_site_title', [
                'full_name'                => $aRow['full_name'],
                'location'                 => $aRow['location'] . (empty($aRow['location_child']) ? '' : ', ' . $aRow['location_child']),
                'site_title'               => Phpfox::getParam('core.site_title'),
                'meta_description_profile' => Phpfox::getParam('core.meta_description_profile'),
                'total_friend'             => $aRow['total_friend']
            ]
        );

        if (($iLinkId = $this->request()->get('link-id')) && ($aLinkShare = Phpfox::getService('link')->getLinkById($iLinkId)) && isset($aLinkShare['link_id'])) {
            $sPageTitle = $aLinkShare['title'];
            $sDescription = $aLinkShare['description'];
            $this->template()->setMeta('og:image', $aLinkShare['image']);
        }

        $this->template()->setTitle($sPageTitle)
            ->setMeta('description', $sDescription)
            ->setUrl('profile')
            ->setHeader('cache', [
                    'places.js' => 'module_feed',
                ]
            );

        (($sPlugin = Phpfox_Plugin::get('profile.component_controller_index_set_header')) ? eval($sPlugin) : false);
        return null;
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('profile.component_controller_index_clean')) ? eval($sPlugin) : false);
    }
}
