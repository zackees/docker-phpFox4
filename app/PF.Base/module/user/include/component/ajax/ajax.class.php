<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Ajax_Ajax
 */
class User_Component_Ajax_Ajax extends Phpfox_Ajax
{
    public function showImportLog()
    {
        Phpfox::getUserParam('admincp.has_admin_access', true);
        Phpfox::getBlock('user.admincp.importlog', [
            'params' => [
                'row'   => $this->get('row'),
                'field' => $this->get('field'),
                'log'   => $this->get('log')
            ]
        ]);
    }

    public function deleteProcessingImport()
    {
        Phpfox::getUserParam('admincp.has_admin_access', true);
        if (Phpfox::getService('user.import')->deleteProcessingImport($this->get('import_id'))) {
            $this->call('$("#js_import_item_' . $this->get('import_id') . '").find(".js_import_status").html("' . _p('stopped') . '");');
            $this->call('$("#js_import_item_' . $this->get('import_id') . '").find(".js_import_action").html("");');
        }
    }

    public function selectImportField()
    {
        Phpfox::getUserParam('admincp.has_admin_access', true);
        $aFields = $this->get('import_fields');
        Phpfox::getBlock('user.admincp.importusers', [
            'field' => json_decode($aFields, true)
        ]);
    }

    public function importUsers()
    {
        Phpfox::getUserParam('admincp.has_admin_access', true);
        $this->setTitle(_p('import_settings'));
        Phpfox::getBlock('user.admincp.importusers');
    }

    public function exportUsers()
    {
        Phpfox::getUserParam('admincp.has_admin_access', true);
        $this->setTitle(_p('exports_settings'));
        Phpfox::getBlock('user.admincp.exportusers');
    }

    public function updateFeedSort()
    {
        Phpfox::isUser(true);
        if (Phpfox::getService('user.process')->updateFeedSort($this->get('order'))) {
            $this->call('window.location.href = \'\';');
        }
    }

    /**
     * @deprecated from 4.8.8
     */
    public function confirmEmail()
    {
        $aVals = $this->get('val');

        $bFailed = false;
        $bSkipEmail = false;
        if (Phpfox::getParam('core.enable_register_with_phone_number')) {
            $oPhone = Phpfox::getLib('phone');
            if (!empty($aVals['email']) && $oPhone->setRawPhone($aVals['email']) && $oPhone->isValidPhone()) {
                $sPhone = $oPhone->getPhoneE164();
            }
            $oPhone->reset();
            if (!empty($aVals['confirm_email']) && $oPhone->setRawPhone($aVals['confirm_email']) && $oPhone->isValidPhone()) {
                $sConfirmPhone = $oPhone->getPhoneE164();
            }
            if (!empty($sPhone) && !empty($sConfirmPhone)) {
                $bSkipEmail = true;
                $bFailed = $sPhone != $sConfirmPhone;
            }
        }
        if (!$bSkipEmail) {
            if (empty($aVals['email']) || empty($aVals['confirm_email'])) {
                $bFailed = true;
            } else {
                if ($aVals['email'] != $aVals['confirm_email']) {
                    $bFailed = true;
                }
            }
        }

        if ($bFailed) {
            $this->show('#js_confirm_email_error');
        } else {
            $this->hide('#js_confirm_email_error');
        }
    }

    public function setCoverPhoto()
    {
        Phpfox::isUser(true);
        Phpfox::getService('user.process')->updateCoverPhoto($this->get('photo_id'));
        if (Phpfox::isAppActive('Core_Photos') && Phpfox::getUserParam('photo.photo_must_be_approved')) {
            Phpfox::addMessage(_p('the_cover_photo_is_pending_please_waiting_until_the_approval_process_is_done'));
        }
        $this->call('window.location.href = \'' . Phpfox_Url::instance()->makeUrl('profile', ['coverupdate' => '1']) . '\';');
    }

    public function removeLogo()
    {
        Phpfox::isUser(true);
        Phpfox::getService('user.process')->removeLogo($this->get('user_id'));
        $this->reload();
    }

    public function updateCoverPosition()
    {
        Phpfox::isUser(true);
        Phpfox::getService('user.process')->updateCoverPosition($this->get('position'));
        $this->call('window.location.href = \'' . Phpfox_Url::instance()->makeUrl('profile', ['newcoverphoto' => '1']) . '\';');
    }

    public function uploadTempCover()
    {
        Phpfox::isUser(true);

        if (!isset($_FILES['image']) || !Phpfox::getUserParam('photo.can_upload_photos')) {
            exit;
        }

        if (($iFlood = Phpfox::getUserParam('photo.flood_control_photos')) !== 0) {
            $aFlood = [
                'action' => 'last_post', // The SPAM action
                'params' => [
                    'field'      => 'time_stamp', // The time stamp field
                    'table'      => Phpfox::getT('photo'), // Database table we plan to check
                    'condition'  => 'user_id = ' . Phpfox::getUserId(), // Database WHERE query
                    'time_stamp' => $iFlood * 60 // Seconds);
                ]
            ];

            // actually check if flooding
            if (Phpfox::getLib('spam')->check($aFlood)) {
                Phpfox_Error::set(_p('uploading_photos_a_little_too_soon') . ' ' . Phpfox::getLib('spam')->getWaitTime());
            }
        }

        $oFile = Phpfox::getLib('file');
        if ($_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $aImage = $oFile->load('image', ['jpg', 'gif', 'png'], (Phpfox::getUserParam('photo.photo_max_upload_size') === 0 ? null : (Phpfox::getUserParam('photo.photo_max_upload_size') / 1024)));
        } else {
            switch ($_FILES['image']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $sErrorMessage = _p('the_uploaded_file_exceeds_the_upload_max_filesize_max_file_size_directive_in_php_ini',
                        ['upload_max_filesize' => ini_get('upload_max_filesize')]);
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $sErrorMessage = "the_uploaded_file_exceeds_the_MAX_FILE_SIZE_directive_that_was_specified_in_the_HTML_form";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $sErrorMessage = "the_uploaded_file_was_only_partially_uploaded";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $sErrorMessage = "no_file_was_uploaded";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $sErrorMessage = "missing_a_temporary_folder";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $sErrorMessage = "failed_to_write_file_to_disk";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $sErrorMessage = "file_upload_stopped_by_extension";
                    break;
                default:
                    $sErrorMessage = "unknown_upload_error";
                    break;
            }

            Phpfox_Error::set($sErrorMessage);
        }

        if (!Phpfox_Error::isPassed()) {
            $errorMessages = Phpfox_Error::get();
            echo json_encode([
                'error' => html_entity_decode(array_shift($errorMessages)),
                'error_title' => html_entity_decode(_p('notice')),
            ]);
            exit;
        }

        $sType = 'user_cover';
        $aParams = Phpfox::callback($sType . '.getUploadParams');
        $aFile = Phpfox::getService('user.file')->upload($aParams['param_name'], $aParams, false, false);
        $aVals = $this->get('val');

        if (!$aFile || !empty($aFile['error'])) {
            echo json_encode([
                'error' => !empty($aFile['error']) ? $aFile['error'] : _p('upload_fail_please_try_again_later'),
                'error_title' => html_entity_decode(_p('notice')),
            ]);
            exit;
        }

        $iTempFileId = Phpfox::getService('core.temp-file')->add([
            'type' => $sType,
            'size' => $aFile['size'],
            'path' => $aFile['name'],
            'server_id' => 0,
        ]);


        $sImage = Phpfox::getLib('image.helper')->display([
            'server_id'  => 0,
            'path'       => 'photo.url_photo',
            'file'       =>  $aFile['name'],
            'suffix'     => '',
            'no_default' => true,
            'return_url' => true,
        ]);

        if (!empty($aVals['page_id'])) {
            $moduleId = 'pages';
            $itemId = $aVals['page_id'];
        } elseif (!empty($aVals['groups_id'])) {
            $moduleId = 'groups';
            $itemId = $aVals['groups_id'];
        } else {
            $moduleId = 'user';
            $itemId = Phpfox::getUserId();
        }

        $responseParams = [
            'imagePath' => $sImage,
            'tempFileId' => $iTempFileId,
            'imageInfo' => $aImage,
            'moduleId' => $moduleId,
            'itemId' => $itemId,
        ];

        (($sPlugin = Phpfox_Plugin::get('user.component_ajax_uploadtempcover')) ? eval($sPlugin) : false);

        echo json_encode([
            'eval' => '$Core.CoverPhoto.processAfterUploading(' . json_encode($responseParams) . ');',
        ]);

        exit;
    }

    public function repositionCoverPhoto()
    {
        Phpfox::isUser(true);

        $photoId = $this->get('photo_id');
        if (!empty($photoId) && Phpfox::getUserParam('photo.photo_must_be_approved')) {
            storage()->set('photo_cover_reposition_' . $photoId, $this->get('position'));
        } else {
            Phpfox::getService('user.process')->updateCoverPosition($this->get('position'));
        }
    }

    public function login()
    {
        $sMainUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        if (!empty($sMainUrl)) {
            Phpfox::getLib('session')->set('redirect', $sMainUrl);
        }
        Phpfox::getBlock('user.login-ajax');
    }

    public function search()
    {
        $sId = $this->get('id');
        $aValue = $this->get('val');
        if (!isset($aValue[$sId])) {
            return false;
        }
        $sValue = $aValue[$sId][0];
        $sOld = $this->get('old');

        if (strpos($sValue, ',')) {
            $aValues = explode(',', $sValue);
            $iCnt = (count($aValues) - 1);
            $sValue = trim($aValues[$iCnt]);
        }

        if (trim($sValue) == '') {
            $this->call("oInlineSearch.close('" . $this->get('id') . "');");
            return false;
        }

        $aRows = Phpfox::getService('user')->getInlineSearch($sValue, $sOld);

        if (count($aRows)) {
            Phpfox_Template::instance()->assign([
                    'aRows'   => $aRows,
                    'sJsId'   => $this->get('id'),
                    'sSearch' => $this->get('value'),
                    'bIsUser' => true
                ]
            )->getLayout('inline-search');

            $this->call("oInlineSearch.display('" . $this->get('id') . "', '" . $this->getContent() . "');");
        }
        return null;
    }

    public function clearStatus()
    {
        Phpfox::isUser(true);
        Phpfox::getService('user.process')->clearStatus(Phpfox::getUserId());

        $this->call('var sParsed = $("<div/>").html(\'' . _p('what_is_on_your_mind') . '\').text();$("#js_global_status_input").val(sParsed);$("#js_status_input").val(sParsed);');
        $this->hide('#js_update_user_status_button')
            ->show('#js_current_user_status')
            ->hide('.user_status_update_ajax')
            ->html('.js_actual_user_status_' . Phpfox::getUserId(), '')
            ->html('.js_actual_user_status_bar_' . Phpfox::getUserId(), '');
    }

    public function updateStatus()
    {
        Phpfox::isUser(true);
        $aVals = (array)$this->get('val');
        $bHasTaggedFriends = false;
        $bCanLoadNewFeedContent = true;

        //Check if the tagged user is removed from feed in their profile
        if (!empty($iProfileUserId = Phpfox::getService('profile')->getProfileUserId())
            && !empty($aVals['feed_id'])
            && !empty($aFeed = Phpfox::getService('feed')->getFeed($aVals['feed_id']))) {
            $bHasTaggedFriends = in_array($iProfileUserId, Phpfox::getService('feed.tag')->getTaggedUserIds($aFeed['item_id'], $aFeed['type_id']));
        }

        $bConfirmSchedule = isset($aVals['confirm_scheduled']) && (int)$aVals['confirm_scheduled'];
        $aVals['user_id'] = Phpfox::getUserId();

        if (isset($aVals['user_status']) &&
            (!$bConfirmSchedule && ($iId = Phpfox::getService('user.process')->updateStatus($aVals))
                || $bConfirmSchedule && Phpfox::getService('core.schedule')->scheduleItem(Phpfox::getUserId(), 'user_status', 'user', $aVals))
        ) {
            if (isset($aVals['feed_id'])) {
                if ($bHasTaggedFriends) {
                    $aCurrentTaggedFriends = !empty($aVals['tagged_friends']) ? array_map(function($value) {
                        return trim($value);
                    }, explode(',', $aVals['tagged_friends'])) : [];
                    $bCanLoadNewFeedContent = in_array($iProfileUserId, $aCurrentTaggedFriends);
                }
                if ($bCanLoadNewFeedContent) {
                    //Mean edit already status
                    Phpfox::getService('feed')->processUpdateAjax($aVals['feed_id']);
                } elseif (!empty($aVals['feed_id'])) {
                    $this->slideUp('#js_item_feed_' . $aVals['feed_id']);
                    $this->call("tb_remove();");
                    $this->call('setTimeout(function(){$Core.resetActivityFeedForm();$Core.loadInit();}, 500);');
                }
            } else {
                //Mean add new status
                (($sPlugin = Phpfox_Plugin::get('user.component_ajax_updatestatus')) ? eval($sPlugin) : false);
                if (empty($bConfirmSchedule)) {
                    Phpfox::getService('feed')->processAjax($iId);
                } else {
                    $iScheduleTime = Phpfox::getLib('date')->mktime($aVals['schedule_hour'], $aVals['schedule_minute'], 0, $aVals['schedule_month'], $aVals['schedule_day'], $aVals['schedule_year']);
                    $this->call('$Core.resetActivityFeedForm();');
                    $this->call('$Core.loadInit();');
                    $this->alert(_p('your_status_will_be_sent_on_time', ['time' => Phpfox::getTime(Phpfox::getParam('feed.feed_display_time_stamp'), Phpfox::getLib('date')->convertToGmt((int)$iScheduleTime))]), null, 300, 150, true);
                }
            }
        } else {
            $this->call('$Core.activityFeedProcess(false);');
        }
    }

    public function mainBrowse()
    {
        Phpfox::getComponent('user.browse', [], 'controller');

        $this->remove('.js_pager_view_more_link');
        $this->call("if($('#js_view_more_users').length == 0) { $('#delayed_block').append('<div id=\"js_view_more_users\"></div>'); } ");
        $this->append('#js_view_more_users', $this->getContent(false));
        $this->call('$Core.loadInit();');
    }

    public function browse()
    {
        if ($this->get('bIsAdminCp')) {
            Phpfox::setAdminPanel();
        }
        Phpfox::getBlock('user.browse', ['input' => $this->get('input'), 'bIsAdminCp' => $this->get('bIsAdminCp'), 'bOnlyUser' => $this->get('bOnlyUser')]);
        $this->call('<script type="text/javascript">$(\'#TB_ajaxWindowTitle\').html(\'' . _p('search_for_members', ['phpfox_squote' => true]) . '\');</script>');
    }

    public function browseAjax()
    {
        if ($this->get('bIsAdminCp')) {
            Phpfox::setAdminPanel();
        }
        Phpfox::getBlock('user.browse', ['page' => $this->get('page'), 'find' => $this->get('find'), 'input' => $this->get('input'), 'is_search' => true, 'bIsAdminCp' => $this->get('bIsAdminCp'), 'bOnlyUser' => $this->get('bOnlyUser')]);

        $this->call('$(\'#js_user_search_content\').html(\'' . $this->getContent() . '\'); updateCheckBoxes();');
    }

    /**
     * Shows the deleteUser block, does not perform  the actual delete
     */
    public function deleteUser()
    {
        Phpfox::isUser(true);
        Phpfox::getUserParam('admincp.has_admin_access', true);
        Phpfox::getUserParam('user.can_delete_others_account', true);
        $iUser = (int)$this->get('iUser');
        Phpfox::getBlock('user.admincp.deleteUser', ['iUser' => $iUser]);
    }

    /**
     * Deletes a feedback from the admin panel
     */
    public function deleteFeedback()
    {
        Phpfox::isAdmin(true);
        $iFeedback = (int)$this->get('iFeedback');
        if (Phpfox::getService('user.cancellations.process')->deleteFeedback($iFeedback)) {
            $this->call('$("#js_feedback_' . $iFeedback . '").remove();');
        } else {
            $this->alert(_p('we_found_a_problem_with_your_request_please_try_again'));
        }
    }

    public function confirmedDelete()
    {
        Phpfox::isUser(true);
        Phpfox::getUserParam('admincp.has_admin_access', true);
        $iUser = (int)$this->get('iUser');

        if (!Phpfox::getService('user')->isAdminUser($iUser)) {
            define('PHPFOX_CANCEL_ACCOUNT', true);
            Phpfox::getService('user.auth')->setUserId($iUser);
            Phpfox::massCallback('onDeleteUser', $iUser);
            Phpfox::getService('user.auth')->setUserId(null);
            $this->call('$("#js_user_' . $iUser . '").remove();');
            $this->setMessage('User ' . $iUser . ' deleted.');
        } else {
            Phpfox_Error::set(_p('you_are_unable_to_delete_a_site_administrator'));
        }
    }

    public function getRegistrationStep()
    {
        $this->error(false);

        $aVals = $this->get('val');
        if (Phpfox::isAppActive('Core_Subscriptions') && isset($aVals['package_id'])) {
            $aPackageInfo = Phpfox::getService('subscribe')->getPackage($aVals['package_id']);
            $iUserGroupId = $aPackageInfo['user_group_id'];
        } else {
            $iUserGroupId = null;
        }
        $aValidateParams = Phpfox::getService('user.register')->getValidation($this->get('step'), false, $iUserGroupId, true);
        if ($this->get('step') == '1') {
            if (Phpfox::getParam('user.split_full_name') && Phpfox::getParam('user.disable_username_on_sign_up') != 'username') {
                if (empty($aVals['first_name']) || empty($aVals['last_name'])) {
                    unset($aValidateParams['full_name']);
                } else {
                    $aVals['full_name'] = $aVals['first_name'] . ' ' . $aVals['last_name'];
                }
            }

            if (empty($aVals['full_name']) && (Phpfox::getParam('user.disable_username_on_sign_up') == 'username')) {
                $aVals['full_name'] = $aVals['user_name'];
            }

            if (isset($aVals['full_name']) && $aVals['full_name'] == '&#173;') {
                Phpfox_Error::set(_p('not_a_valid_name'));
            }

            if (Phpfox::getParam('core.enable_register_with_phone_number') && !filter_var($aVals['email'], FILTER_VALIDATE_EMAIL)) {
                Phpfox::getService('user.validate')->phone($aVals['email'], false, true, null, true);
            } else {
                Phpfox::getService('user.validate')->email($aVals['email']);
            }

            // ban check
            $oBan = Phpfox::getService('ban');
            if (!$oBan->check('email', $aVals['email']) || !$oBan->check('email', $aVals['email'], false, 'phone_number')) {
                Phpfox_Error::set(_p('global_ban_message'));
            }

            if (!$oBan->check('ip', Phpfox_Request::instance()->getIp())) {
                Phpfox_Error::set(_p('not_allowed_ip_address'));
            }
            $aBanned = Phpfox::getService('ban')->isUserBanned($aVals);
            if ($aBanned['is_banned']) {
                if (isset($aBanned['reason']) && !empty($aBanned['reason'])) {
                    $aBanned['reason'] = str_replace('&#039;', "'", Phpfox::getLib('parse.output')->parse($aBanned['reason']));
                    $sReason = Phpfox::getLib('parse.output')->cleanPhrases($aBanned['reason']);
                    Phpfox_Error::set($sReason);
                } else {
                    Phpfox_Error::set(_p('global_ban_message'));
                }
            }
            //End check ban
        }

        $oValid = Phpfox_Validator::instance()->set(['sFormName' => 'js_form', 'aParams' => $aValidateParams]);

        $aCustom = $this->get('custom');
        if (is_array($aCustom)) {
            foreach ($aCustom as $keyCustom => $value) {
                $aVals['custom[' . $keyCustom . ']'] = $value;
            }
        }

        if (Phpfox_Error::isPassed() && $oValid->isValid($aVals)) {
            $aFields = Phpfox::getService('custom')->getForEdit(['user_main', 'user_panel', 'profile_panel'], null, $iUserGroupId, true);
            if ($aCustom && $aFields) {
                foreach ($aFields AS $aField) {
                    if ($aField['on_signup'] && $aField['is_required'] && $aField['var_type'] == 'date') {
                        if (empty($aCustom[$aField['field_id']]['custom_' . $aField['field_id'] . '_month'])
                            || empty($aCustom[$aField['field_id']]['custom_' . $aField['field_id'] . '_day'])
                            || empty($aCustom[$aField['field_id']]['custom_' . $aField['field_id'] . '_year']))
                            Phpfox_Error::set(_p('the_field_field_is_required', ['field' => Phpfox::getLib('parse.output')->clean(_p($aField['phrase_var_name']))]));
                    }
                }
            }

            if (Phpfox_Error::isPassed()) {
                if (Phpfox::isAppActive('Core_Subscriptions') && isset($aVals['package_id'])) {
                    $aPackageInfo = Phpfox::getService('subscribe')->getPackage($aVals['package_id']);
                    $iUserGroupId = $aPackageInfo['user_group_id'];
                } else {
                    $iUserGroupId = false;
                }
                if ($this->get('last')) {
                    $this->call('$(\'#js_form\').submit();');
                } else {
                    (($sPlugin = Phpfox_Plugin::get('user.component_ajax_getregistrationstep_pass')) ? eval($sPlugin) : false);

                    if (!isset($bSkipAjaxProcess)) {
                        $this->template()->assign([
                                'aTimeZones'     => Phpfox::getService('core')->getTimeZones(),
                                'aPackages'      => Phpfox::isAppActive('Core_Subscriptions') ? Phpfox::getService('subscribe')->getPackages(true) : null,
                                'aSettings'      => Phpfox::getService('custom')->getForEdit(['user_main', 'user_panel', 'profile_panel'], null, $iUserGroupId, true),
                                'sDobStart'      => Phpfox::getParam('user.date_of_birth_start'),
                                'sDobEnd'        => Phpfox::getParam('user.date_of_birth_end'),
                                'bIsBlockSignUp' => (isset($aVals['block_signup']) ? true : false)
                            ]
                        )->getTemplate('user.block.register.step' . ($this->get('step') + 1));

                        $this->val('#js_registration_submit', html_entity_decode(_p('continue'), null, 'UTF-8'));
                        $this->call('$Core.registration.updateForm(\'' . $this->getContent() . '\');');
                        if ($this->get('next')) {
                            $this->call('$Core.registration.showCaptcha();');
                        }
                    }
                }
            }
        }

        if (!Phpfox_Error::isPassed()) {
            $sErrors = '';
            foreach (Phpfox_Error::get() as $sError) {
                $sErrors .= '<div class="error_message">' . $sError . '</div>';
            }

            if ($this->get('step') == '1') {
                $this->call('$(\'#js_register_accept\').show();');
            }

            $this->call('$(\'#js_registration_process\').hide();$(\'#js_registration_holder\').show();')->html('#js_signup_error_message', $sErrors);
        }

        $this->call('$Core.loadInit();');
    }

    public function getBackRegistrationStep()
    {
        $iStep = (int)$this->get('step');
        if ($iStep > 1) {
            $this->val('#js_registration_submit', html_entity_decode(_p('next_step'), null, 'UTF-8'));
            $this->call('$Core.registration.updateForm(\'' . $this->getContent() . '\', true);');
        } else {
           $this->call("$('#js_registration_back_previous').parent().hide();");
        }
    }

    public function getNew()
    {
        Phpfox::getBlock('user.new');

        $this->html('#' . $this->get('id'), $this->getContent(false));
        $this->call('$(\'#' . $this->get('id') . '\').parents(\'.block:first\').find(\'.bottom li a\').attr(\'href\', \'' . Phpfox_Url::instance()->makeUrl('user.browse', ['sort' => 'joined']) . '\');');
    }

    public function getAccountSettings()
    {
        Phpfox::getBlock('user.setting');

        $this->hide('#js_basic_info_data')
            ->hide('#js_user_basic_info')
            ->show('#js_user_basic_edit_link')
            ->html('#js_basic_info_form', $this->getContent(false))
            ->show('#js_basic_info_form');
    }

    public function updateAccountSettings()
    {
        $aValidation = [
            'country_iso' => _p('select_current_location')
        ];

        if (Phpfox::getUserParam('user.can_edit_gender_setting')) {
            $aValidation['gender'] = _p('select_your_gender');
        }

        if (Phpfox::getUserParam('user.can_edit_dob')) {
            $aValidation['month'] = _p('select_month_of_birth');
            $aValidation['day'] = _p('select_day_of_birth');
            $aValidation['year'] = _p('select_year_of_birth');
        }

        $oValid = Phpfox_Validator::instance()->set(['sFormName' => 'js_form', 'aParams' => $aValidation]);

        if (!$oValid->isValid($this->get('val'))) {
            $this->hide('#js_updating_basic_info_load')->show('#js_updating_basic_info');

            return false;
        }

        if (Phpfox::getService('user.process')->updateSimple(Phpfox::getUserId(), $this->get('val'))) {

        }

        if (Phpfox::getService('custom.process')->updateFields(Phpfox::getUserId(), Phpfox::getUserId(), $this->get('custom'))) {

        }

        Phpfox::getBlock('profile.info');

        $this->hide('#js_updating_basic_info_load')->show('#js_updating_basic_info');
        $this->hide('#js_basic_info_form')
            ->html('#js_basic_info_data', $this->getContent(false))
            ->show('#js_basic_info_data');
    }

    public function updateFooterBar()
    {
        Phpfox::isUser(true);
        Phpfox::getService('user.process')->updateFooterBar(Phpfox::getUserId(), $this->get('type_id'));
    }

    public function hideBlock()
    {
        Phpfox::isUser(true);
        Phpfox::getService('user.process')->hideBlock($this->get('block_id'));
    }

    public function loadCustomField()
    {
        Phpfox::getBlock('user.custom');

        $this->html('#js_custom_field_holder', $this->getContent(false));
    }

    public function changePicture()
    {
        Phpfox::getBlock('user.photo');
    }

    public function block()
    {
        Phpfox::getBlock('user.block');
    }

    public function processBlock()
    {
        if (Phpfox::getService('user.block.process')->add($this->get('user_id'))) {
            $this->setMessage(_p('user_successfully_blocked'));
            $this->call('window.location.href = \'' . Phpfox_Url::instance()->makeUrl('user.privacy', ['tab' => 'blocked']) . '\';');
        }
    }

    public function unBlock()
    {
        if (Phpfox::getService('user.block.process')->delete($this->get('user_id'))) {
            if ($this->get('remove_button', false)) {
                $this->remove('#unblock_user_' . $this->get('user_id'));
            }
            Phpfox::addMessage(_p('user_successfully_unblocked'));
            $this->reload();
        }
    }

    /**
     * Handles featuring and un-featuring a user, permissions are checked on the service itself
     */
    public function feature()
    {
        $iUser = intval($this->get('user_id'));
        $bFeature = $this->get('feature');
        if ($bFeature == 1 || $bFeature == 0) {
            if ($bFeature == 1 && (Phpfox::getService('user.featured.process')->feature($iUser))) // trying to feature
            {
                $sMessage = _p('user_successfully_featured');
                if ($this->get('type') != '1') {
                    $sNewHtml = '<a href=\"#\" onclick=\"$.ajaxCall(\'user.feature\', \'user_id=' . $iUser . '&feature=0\'); return false;\">' . _p('unfeature_user');
                    $this->call('$(".js_feature_' . $iUser . '").html("' . $sNewHtml . '");');
                }

                if ($this->get('reload', false)) {
                    Phpfox::addMessage($sMessage);
                    $this->call('$Core.reloadPage();');
                    return true;
                }
                $this->alert($sMessage, _p('Notice'), 400, 150, true);

                return true;
            } else if ($bFeature == 0 && (Phpfox::getService('user.featured.process')->unfeature($iUser))) {
                $sMessage = _p('user_successfully_unfeatured');
                if ($this->get('type') != '1') {
                    $sNewHtml = '<a href=\"#\" onclick=\"$.ajaxCall(\'user.feature\', \'user_id=' . $iUser . '&feature=1\'); return false;\">' . _p('feature_user');
                    $this->call('$(".js_feature_' . $iUser . '").html("' . $sNewHtml . '");');
                }

                if ($this->get('reload', false)) {
                    Phpfox::addMessage($sMessage);
                    $this->call('$Core.reloadPage();');
                    return true;
                }

                $this->alert($sMessage, _p('Notice'), 400, 150, true);

                return true;
            }


        }// else potential hack attempt

        $this->alert(_p('an_error_occured_and_this_operation_was_not_completed')); // potential hack attempt

        return false;
    }

    /**
     * Changes the order of a  member
     */
    public function setFeaturedOrder()
    {
        Phpfox::isAdmin(true);
        if (Phpfox::getService('user.featured.process')->updateOrder($this->get('val'))) {

        }
    }

    /**
     * Verifies a username so the user can log in.
     */
    public function verifyEmail()
    {
        $iUser = $this->get('iUser');
        $bVerified = Phpfox::getService('user.verify.process')->adminVerify($iUser);
        if ($bVerified == true) {
            $this->call('$(".js_verify_email_' . $iUser . '").hide("slow", function(){$(this).remove();});');
        } else {
            $this->alert(_p('an_error_occured_and_this_user_could_not_be_verified'));
        }
    }

    /**
     * Sends an email to the user_id with with the verification  link
     */
    public function verifySendEmail()
    {
        $iUser = $this->get('iUser');
        $delayResendVerificationEmail = Phpfox::getParam('user.resend_verification_email_delay_time', 15);
        $time = (int)Phpfox::getService('user.verify')->getVerificationTimeByUserId($iUser);
        if (!$time) {
            $this->alert(_p('an_error_occured_and_the_email_could_not_be_sent'), null, 300, 150, true);
            return false;
        }
        if ((PHPFOX_TIME - $delayResendVerificationEmail * 60) <= $time) {
            $this->alert(_p('resend_verification_email_notice_time', [
                'time_text' => $delayResendVerificationEmail
            ]));
            return false;
        }
        $bSent = Phpfox::getService('user.verify.process')->sendMail($iUser, true);
        if ($bSent) {
            $this->alert(_p('verification_email_sent'), null, 300, 150, true);
            return true;
        }
        $this->alert(_p('an_error_occured_and_the_email_could_not_be_sent'), null, 300, 150, true);
        return false;
    }

    /**
     * Sends an email to the user_id with with the verification  link
     */
    public function verifySendSms()
    {
        $iUser = $this->get('iUser');
        if (Phpfox::getService('user.verify.process')->resendSMS($iUser)) {
            $this->alert(_p('verification_passcode_sent'), null, 300, 150, true);
            return true;
        }
        $this->alert(_p('an_error_occurred_and_the_passcode_could_not_be_sent'), null, 300, 150, true);
        return false;
    }

    public function cropPhoto()
    {
        if ($this->get('crop')) {
            $this->call('window.location.href = \'' . Phpfox_Url::instance()->makeUrl('profile') . '\';');
            return true;
        }

        Phpfox::isUser(true);

        if ($this->get('in_process')) {
            $oImage = Phpfox_Image::instance();
            $sFileName = $this->get('in_process');
            $aImages = [];
            if (($sPhotos = $this->get('photos'))) {
                $aImages = unserialize(base64_decode(urldecode($this->get('photos'))));
            }

            $iNotCompleted = 0;

            foreach (Phpfox::getService('user')->getUserThumbnailSizes() as $iSize) {
                if (isset($aImages[sprintf($sFileName, '_' . $iSize)])) {
                    continue;
                }

                if (Phpfox::getParam('core.keep_non_square_images')) {
                    $oImage->createThumbnail(Phpfox::getParam('core.dir_user') . sprintf($sFileName, ''), Phpfox::getParam('core.dir_user') . sprintf($sFileName, '_' . $iSize), $iSize, $iSize);
                }
                $oImage->createThumbnail(Phpfox::getParam('core.dir_user') . sprintf($sFileName, ''), Phpfox::getParam('core.dir_user') . sprintf($sFileName, '_' . $iSize . '_square'), $iSize, $iSize, false);

                $aImages[sprintf($sFileName, '_' . $iSize)] = true;

                $iNotCompleted++;

                $this->call('p(\'Processing photo: ' . sprintf($sFileName, '_' . $iSize) . '\');');

                break;
            }

            $sValues = '';
            foreach ($this->get('val') as $sKey => $mValue) {
                $sValues .= '&val[' . $sKey . ']=' . urlencode($mValue);
            }

            if ($iNotCompleted) {
                $this->call('$.ajaxCall(\'user.cropPhoto\', \'js_disable_ajax_restart=true&photos=' . urlencode(base64_encode(serialize($aImages))) . '&in_process=' . $this->get('in_process') . '&file=' . $this->get('in_process') . '' . $sValues . '\');');
            } else {
                $oFile = Phpfox_File::instance();

                $iServerId = Phpfox_Request::instance()->getServer('PHPFOX_SERVER_ID');

                $this->call('p(\'Completed resizing photos.\');');

                if (Phpfox::getUserBy('user_image') != '') {
                    if (file_exists(Phpfox::getParam('core.dir_user') . sprintf(Phpfox::getUserBy('user_image'), ''))) {
                        $oFile->unlink(Phpfox::getParam('core.dir_user') . sprintf(Phpfox::getUserBy('user_image'), ''));
                        foreach (Phpfox::getService('user')->getUserThumbnailSizes() as $iSize) {
                            if (file_exists(Phpfox::getParam('core.dir_user') . sprintf(Phpfox::getUserBy('user_image'), '_' . $iSize))) {
                                $oFile->unlink(Phpfox::getParam('core.dir_user') . sprintf(Phpfox::getUserBy('user_image'), '_' . $iSize));
                            }

                            if (file_exists(Phpfox::getParam('core.dir_user') . sprintf(Phpfox::getUserBy('user_image'), '_' . $iSize . '_square'))) {
                                $oFile->unlink(Phpfox::getParam('core.dir_user') . sprintf(Phpfox::getUserBy('user_image'), '_' . $iSize . '_square'));
                            }
                        }
                    }
                }

                $sFileName = $this->get('file');

                Phpfox_Database::instance()->update(Phpfox::getT('user'), ['user_image' => $sFileName, 'server_id' => $iServerId], 'user_id = ' . Phpfox::getUserId());

                (Phpfox::isModule('feed') ? Phpfox::getService('feed.process')->delete('user_photo', Phpfox::getUserId()) : null);
                (Phpfox::isModule('feed') && Phpfox::getParam('photo.photo_allow_posting_user_photo_feed', 1) && Phpfox::getUserParam('photo.can_view_photos')
                    ? Phpfox::getService('feed.process')->add('user_photo', Phpfox::getUserId(), serialize(['destination' => $sFileName, 'server_id' => $iServerId])) : null);

                $this->call('$.ajaxCall(\'user.cropPhoto\', \'crop=true&js_disable_ajax_restart=true' . $sValues . '\');');
                if (Phpfox::isAppActive('Core_Photos')) {
                    Phpfox::getService('photo.album')->getForProfileView(Phpfox::getUserId(), true);
                }
            }

            return null;
        }

        $this->call('p(\'Cropping photo.\');');
        if (Phpfox::getService('user.process')->cropPhoto($this->get('val'))) {
            Phpfox::addMessage(_p('profile_photo_successfully_updated'));

            Phpfox::setCookie('recache_image', 'yes', (PHPFOX_TIME + 600));

            $this->call('window.location.href = \'' . Phpfox_Url::instance()->makeUrl('profile') . '\';');
        } else {
            $this->show('#js_photo_preview_ajax')->html('#js_photo_preview_ajax', '');
        }
        return null;
    }

    public function changePassword()
    {
        Phpfox::getBlock('user.password');
    }

    public function updatePassword()
    {
        $this->error(false);

        if (Phpfox::getService('user.process')->updatePassword($this->get('val'))) {
            Phpfox::addMessage(_p('password_successfully_updated'));

            $this->call('window.location.href = \'' . Phpfox_Url::instance()->makeUrl('user.setting') . '\';');
        } else {
            $this->html('#js_progress_cache_loader', '<div class="error_message">' . implode('', Phpfox_Error::get()) . '</div>');
        }
    }

    public function checkSpaceUsage()
    {
        $this->error(false);
        Phpfox::isUser(true);
        if (Phpfox::getService('user.space')->isAllowedToUpload(Phpfox::getUserId())) {

        } else {
            $this->html('#js_progress_cache_loader', '<div class="error_message">' . implode('', Phpfox_Error::get()) . '</div>');
            $this->hide('#' . $this->get('holder'));
            $this->show('#js_progress_cache_loader');
        }
    }

    public function browseMethod()
    {
        if ($this->get('type') == 'advanced') {
            $this->show('#js_user_browse_advanced');
        } else {
            $this->hide('#js_user_browse_advanced')->val('.js_custom_search', '');
        }
    }

    public function ban()
    {
        if (Phpfox::getService('user.process')->ban($this->get('user_id'), $this->get('type'))) {
            if ($this->get('type') == 1) {
                Phpfox::addMessage(_p('this_user_has_been_banned'));
            } else {
                Phpfox::addMessage(_p('this_user_has_been_unbanned'));
            }
            $this->reload();
        }
    }

    public function getSettings()
    {
        Phpfox::isUser(true);
        Phpfox::getUserParam('admincp.has_admin_access', true);
        Phpfox::getBlock('user.admincp.setting');

        $this->html('#js_module_title', $this->get('module_id'));
        $this->html('#js_setting_block', $this->getContent(false));
        $this->show('#content_editor_text');

        $this->addClass('.table_clear', 'table_hover_action');
        $this->call('$.scrollTo(0);');
        $this->call('$Core.loadInit();');
    }

    public function updateSettings()
    {
        Phpfox::isUser(true);
        Phpfox::getUserParam('admincp.has_admin_access', true);

        $aVals = $this->get('val');
        foreach ($this->get('param') as $iId => $sVar) {
            $aVals['param'][$iId] = $sVar;
        }
        if (Phpfox::getService('user.group.setting.process')->update($this->get('id'), $aVals)) {
            $this->call('$Core.closeAjaxMessage();');
        }
    }

    /**
     * @dedicated from 4.8.6
     */
    public function deleteGroupIcon()
    {
        Phpfox::isUser(true);
        Phpfox::getUserParam('admincp.has_admin_access', true);

        if (Phpfox::getService('user.group.setting.process')->deleteIcon($this->get('group_id'))) {

        }
    }

    public function processUploadedImage()
    {
    }

    public function userPending()
    {
        Phpfox::isAdmin(true);

        if (($aUser = Phpfox::getService('user.process')->userPending($this->get('user_id'), $this->get('type')))) {
            $this->remove('.js_user_pending_' . $this->get('user_id'));
            if ($this->get('type') == '1') {
                $this->html('#js_user_pending_group_' . $this->get('user_id'), _p($aUser['user_group_title']));
                $this->call('tb_remove();');
                if ($this->get('return') == true) { // early return
                    return true;
                }
                $this->alert(_p('user_successfully_approved'));
            } else {
                $this->html('#js_user_pending_group_' . $this->get('user_id'), _p('not_approved'));
                if (!$this->get('no_remove_popup')) {
                    $this->call('tb_remove();');
                }
                if ($this->get('return') == true) { // early return
                    return true;
                }
                $this->alert(_p('user_successfully_denied'));
            }
            return true;
        }
        return false;
    }

    /**
     * Shows the "pop up" when denying a user from the adminCP
     */
    public function showDenyUser()
    {
        Phpfox::isAdmin(true);
        $iUser = (int)$this->get('iUser');
        Phpfox::getBlock('user.admincp.denyUser', ['iUser' => $iUser]);
    }

    public function denyUser()
    {
        $sMessage = $this->get('sMessage');
        $sSubject = $this->get('sSubject');
        $iUser = $this->get('iUser');
        $bReturn = (bool)$this->get('doReturn');

        $this->set(['user_id' => $iUser, 'type' => 2]);
        if (!empty($bReturn) && $bReturn == true) {
            $this->set('return', true);

            $this->userPending();
            return true;
        }

        // send the email
        Phpfox::getLib('mail')->to($iUser)
            ->subject($sSubject)
            ->message($sMessage)
            ->send();

        $this->set([
            'no_remove_popup' => true,
            'return' => true,
        ]);

        if ($this->userPending()) {
            $this->call('$("#sFeedbackDeny").html("' . _p('user_successfully_denied') . '").show();');
            $this->call('js_box_remove($("#js_admincp_deny_user"));');
            $this->alert(_p('user_successfully_denied'), null, 300, 150, true   );
        }
    }

    public function tooltip()
    {
        Phpfox::getBlock('user.tooltip');
        $this->html('#js_user_tool_tip_cache_' . $this->get('user_name'), $this->getContent(false));
        $this->call('$Core.loadUserToolTip(\'' . $this->get('user_name') . '\');');
        $this->call('$Core.loadInit();');
    }

    public function addInactiveJob()
    {
        $iUserId = $this->get('id', 0);
        $bSendAll = $this->get('all', false);
        $iDays = $this->get('days', 0);
        if (Phpfox::getService('user.process')->addInactiveJob((array)$iUserId, $bSendAll, $iDays)) {
            $this->call('$(\'#js_id_row' . $iUserId . '\').remove();');
            $this->call('$(\'#js_user_' . $iUserId . '\').removeClass(\'checkRow\').addClass(\'process_mail\').find(\'.js_drop_down_link\').remove();');
            $this->call('$Core.closeAjaxMessage();');
            if ($bSendAll) {
                $this->alert(_p(Phpfox::getParam('core.enable_register_with_phone_number') ? 'successfully_add_mailing_sms_job_to_all_inactive_users_who_have_not_logged_in_for_days' : 'successfully_add_mailing_job_to_all_inactive_users_who_have_not_logged_in_for_days', ['days' => $iDays]));
            }
        }
    }

    public function processJob()
    {
        Phpfox::isAdmin(true);
        $aInfo = Phpfox::getService('user.process')->processInactiveJob($this->get('iJobId'));
        if (isset($aInfo['iPercentage']) && $aInfo['iPercentage'] < 100) {
            $this->call('setTimeout("processJob(' . $this->get('iJobId') . ')",3000);');
        } else {
            $this->call('jobCompleted();');
        }

        $this->html('#progress', _p('batch_number_completed_percentage', ['page_number' => $aInfo['page_number'], 'percentage' => $aInfo['iPercentage']]));
    }

    public function getInactiveMembersCount()
    {
        Phpfox::isAdmin(true);
        $iCount = Phpfox::getService('user')->getInactiveMembersCount($this->get('iDays'));
        $this->html('#progress', _p('there_are_a_total_of_icount_inactive_members', ['iCount' => $iCount]));
    }

    public function deleteSpamQuestion()
    {
        Phpfox::isAdmin(true);
        if (Phpfox::getService('user.process')->deleteSpamQuestion($this->get('iQuestionId'))) {
            $this->call("$('body').prepend('<div id=\"public_message\" class=\"public_message\" style=\"display:block;\">" . _p('question_deleted_succesfully') . "</div>');");
            $this->call('$Core.loadInit();');
            $this->remove('#tr_new_question_' . $this->get('iQuestionId'));
        }
    }

    public function saveMyLatLng()
    {
        if ($this->get('lat') == '0' && $this->get('lng') == '0') {
            return;
        }
        Phpfox::getService('user.process')->saveMyLatLng(['latitude' => $this->get('lat'), 'longitude' => $this->get('lng')]);
    }

    public function deleteProfilePicture()
    {
        if ($iId = (int)request()->get('id')) {
            Phpfox::getService('user.process')->deleteProfilePicture($iId);
            $this->call('$(".js_user_photo").remove();');
            $this->call('if ($("#js_admincp_process_user_form").length) { $("#js_admincp_process_user_form").append(\'<input type="hidden" name="val[removed_profile_photo]" value="1">\'); }');
        }
    }

    public function getUserStatistic()
    {
        Phpfox::isAdmin(true);
        $iUser = (int)$this->get('iUser');
        $aUser = Phpfox::getService('user')->get($iUser, true);
        if (empty($aUser['user_id'])) {
            return false;
        }

        $this->setTitle(_p('statistics_of_user', ['user_name' => $aUser['full_name']]));
        Phpfox::getBlock('user.admincp.statistics', ['iUser' => $iUser]);
    }

    public function moveUsersToGroup()
    {
        $iUserIds = $this->get('ids');
        $this->setTitle(_p('move_to_group'));
        Phpfox::getBlock('user.admincp.moveusers', [
            'user_ids' => $iUserIds
        ]);
    }

    /**
     * AdminCP only. Active or deactivate a spam question
     */
    public function toggleActiveSpamQuestion()
    {
        $iQuestionId = $this->get('id');
        $iActive = $this->get('active');
        Phpfox::getService('user.process')->toggleActiveSpamQuestion($iQuestionId, $iActive);
        $this->call('$Core.closeAjaxMessage();');
    }

    /**
     * AdminCP only. Sensitive or Insensitive a spam question
     */
    public function toggleCaseSensitiveSpamQuestion()
    {
        $iQuestionId = $this->get('id');
        $iActive = $this->get('active');
        Phpfox::getService('user.process')->toggleCaseSensitiveSpamQuestion($iQuestionId, $iActive);
        $this->call('$Core.closeAjaxMessage();');
    }

    public function getUsersToBlock()
    {
        Phpfox::getBlock('user.search-user-block', [
            'query_search' => $this->get('query_search')
        ]);
    }

    public function addGoogleLoginBtn()
    {
        Phpfox::getBlock('user.google-login-button', [
            'small_size' => $this->get('small_size', false)
        ]);
    }
    public function authGoogleUserLogin()
    {
        if (($iResult = Phpfox::getService('user.process')->addUserViaGoogle($this->get('token'), $this->get('val'))) && Phpfox_Error::isPassed()) {
            if (is_array($iResult)) {
                if (preg_match('/^(http|https):\/\/(.*)$/i', $iResult[0])) {
                    $sUrl = $iResult[0];
                } else {
                    $sUrl = Phpfox::getLib('url')->makeUrl($iResult[0]);
                }
                $this->call('NProgress.done();window.location.href="' . $sUrl . '"');
            } else {
                //Login if success
                $this->call('NProgress.done();window.location.reload();');
            }
        } else {
            $this->call('NProgress.done();');
            return Phpfox_Error::get() ? false : Phpfox_Error::set(_p('opps_something_went_wrong'));
        }
        return true;
    }

    public function updateStatusPrivacy()
    {
        $aVals = (array)$this->get('val');
        if (!isset($aVals['feed_id'])) {
            return false;
        }
        if (Phpfox::getService('user.process')->updateStatusPrivacy($aVals['feed_id'], $aVals)) {
            $this->call('js_box_remove($(".js_quick_edit_privacy_form"), true);');
            if (isset($aVals['privacy'])) {
                $sIconClass = 'ico ';
                switch ((int)$aVals['privacy']) {
                    case 0:
                        $sIconClass .= 'ico-globe';
                        break;
                    case 1:
                        $sIconClass .= 'ico-user3-two';
                        break;
                    case 2:
                        $sIconClass .= 'ico-user-man-three';
                        break;
                    case 3:
                        $sIconClass .= 'ico-lock';
                        break;
                    case 4:
                        $sIconClass .= 'ico-gear-o';
                        break;
                    case 6:
                        $sIconClass .= 'ico-user-circle-alt-o';
                        break;
                }
                $this->call('$("#js_edit_privacy_'. $aVals['feed_id'] .'").html(\'<span class="'. $sIconClass . '"></span>\');');
                return true;
            }
        }
        return false;
    }

    public function validatePhoneNumber()
    {
        $sPhone = $this->get('phone');
        $oPhone = Phpfox::getLib('phone');
        try {
            if ($oPhone->setRawPhone($sPhone) && $oPhone->isValidPhone()) {
                echo json_encode([
                    'is_valid' => true,
                    'value' => $oPhone->getPhoneE164(),
                    'country_code' => $oPhone->getCountryCode()
                ]);
            } else {
                echo json_encode([
                    'is_valid' => false,
                    'value' => $sPhone
                ]);
            }
        } catch (\Exception $e) {
            echo json_encode([
                'is_valid' => false,
                'error_message' => $e->getMessage(),
                'value' => $sPhone
            ]);
        }
    }

    public function disableTwoStepVerification()
    {
        Phpfox::isUser(true);
        if (Phpfox::getService('user.process')->updateTwoStepVerification($this->get('password'), 0)) {
            Phpfox::addMessage(_p('two_step_verification_disabled_successfully'));
            $this->call('window.location.reload();');
            return true;
        } else {
            $this->call('$("#js_two_step_confirm_password_error").html("' . implode(' ', Phpfox_Error::get()) . '").show();');
            Phpfox_Error::reset();
        }
        $this->call('$("#js_confirm_change_tsv").removeClass("disabled").removeAttr("disabled");');
        return false;
    }

    public function enableTwoStepVerification()
    {
        Phpfox::isUser(true);
        $sPassword = $this->get('password');
        $sPasscode = $this->get('passcode');
        $oService = Phpfox::getService('user.googleauth');
        $bResult = true;
        if (isset($sPasscode)) {
            $aUser = Phpfox::getService('user')->getUser(Phpfox::getUserId());
            $sUserString = trim(implode(',', [$aUser['email'], $aUser['full_phone_number']]), ',');
            if (!$oService->authenticateUser($sUserString, $sPasscode)) {
                $this->call('$("#js_two_step_confirm_passcode_error").html("' . _p('invalid_passcode') . '").show();');
                $this->call('$("#js_two_step_confirm_passcode_submit").removeClass("disabled").removeAttr("disabled");');
                $bResult = false;
            } elseif (Phpfox::getService('user.process')->updateTwoStepVerification($sPassword, 1)) {
                Phpfox::addMessage(_p('two_step_verification_enabled_successfully'));
                $this->call('window.location.reload();');
            } else {
                $this->call('$("#js_two_step_confirm_passcode_error").html("' . implode(' ', Phpfox_Error::get()) . '").show();');
                $this->call('$("#js_two_step_confirm_passcode_submit").removeClass("disabled").removeAttr("disabled");');
                Phpfox_Error::reset();
                $bResult = false;
            }
        } elseif (!empty($this->get('is_validate'))) {
            if (!Phpfox::getService('user.process')->updateTwoStepVerification($sPassword, 1, true)) {
                $this->call('$("#js_two_step_confirm_password_error").html("' . implode(' ', Phpfox_Error::get()) . '").show();');
                Phpfox_Error::reset();
                $bResult = false;
            } else {
                $this->call('$("#js_two_step_confirm_password_error").html("").hide();');
                $this->call('tb_show("' . _p('two_step_verification') . '", $.ajaxBox(\'user.enableTwoStepVerification\', $.param({password: "' . $sPassword . '"})));');
            }
        } else {
            $aUser = Phpfox::getService('user')->getUser(Phpfox::getUserId());
            $sUserString = trim(implode(',', [$aUser['email'], $aUser['full_phone_number']]), ',');
            $oService->setUser($sUserString);
            $sTargetUrl = $oService->createUrl($sUserString);
            $sQRCodeUrl = 'https://chart.googleapis.com/chart?' . http_build_query([
                    'cht'  => 'qr',
                    'chl'  => $sTargetUrl,
                    'chs'  => '200x200',
                    'choe' => 'UTF-8',
                ]);
            Phpfox_Template::instance()->assign([
                'sEmail'     => $aUser['email'],
                'sPhone'     => $aUser['full_phone_number'],
                'sPassword'  => $sPassword,
                'sQRCodeUrl' => $sQRCodeUrl,
                'sHexKey'    => $oService->getHexkey($sUserString)
            ])->getTemplate('user.block.passcode');
        }
        if (!$bResult) {
            $this->call('$("#js_confirm_change_tsv").removeAttr("disabled").removeClass("disabled");');
        }
        return $bResult;
    }

    public function sendLoginPasscode()
    {
        if (Phpfox::getService('user.auth')->sendTwoStepPasscode($this->get('user'))) {
            $this->call('$("#js_sending_passcode").html("' . _p('passcode_successfully_sent_to_login_passcode_will_expired_after', ['login' => $this->get('login')]) . '");');
            $this->call('setTimeout(function() { js_box_remove($("#login-auth-methods")); }, 3000);');
            $this->call('$Core.timeCounter.start(\'#js_login_passcode_waiting_time\', 30, true, true); setTimeout(function() { $(\'#js_login_passcode_note\').removeClass(\'disabled\'); }, 30000);');
            return true;
        }
        $this->call('$("#js_sending_passcode").html("'. implode(' ', Phpfox_Error::get()) . '");');
        $this->call('$(\'#js_login_passcode_note\').removeClass(\'disabled\');');
        return false;
    }

    public function getAuthMethods()
    {
        return Phpfox::getBlock('user.login-auth-methods', ['user_id' => $this->get('user_id')]);
    }

    public function deleteMultipleCancelOptions()
    {
        Phpfox::isAdmin(true);

        $ids = $this->get('ids');

        if (!is_array($ids) || !$ids) {
            return false;
        }

        $success = false;

        foreach ($ids as $id) {
            if ($id) {
                $success = Phpfox::getService('user.cancellations.process')->delete($id);
            }
        }

        if ($success) {
            Phpfox::addMessage(_p('options_successfully_deleted'));
        }

        $this->call('$Core.reloadPage();');
    }

    public function deleteMultiSpamQuestions()
    {
        Phpfox::isAdmin(true);

        $ids = $this->get('ids');

        if (!is_array($ids) || !$ids) {
            return false;
        }

        $success = false;

        foreach ($ids as $id) {
            if ($id) {
                $success = Phpfox::getService('user.process')->deleteSpamQuestion($id);
            }
        }

        if ($success) {
            Phpfox::getLib('cache')->remove('spam/questions');
            Phpfox::addMessage(_p('questions_successfully_deleted'));
        }

        $this->call('$Core.reloadPage();');
    }
}
