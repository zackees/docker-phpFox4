<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Controller_Photo
 */
class User_Component_Controller_Photo extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        Phpfox::isUser(true);

        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        list($bIsRegistration, $sNextUrl) = $this->url()->isRegistration(3);
        (($sPlugin = Phpfox_Plugin::get('user.component_controller_photo_1')) ? eval($sPlugin) : false);

        $iUserId = Phpfox::getUserId();
        $sImage = '';
        $bAjaxUpload = isset($_SERVER['HTTP_X_FILE_NAME']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');

        if ($bAjaxUpload) {
            define('PHPFOX_HTML5_PHOTO_UPLOAD', true);
        }

        (($sPlugin = Phpfox_Plugin::get('user.component_controller_photo_2')) ? eval($sPlugin) : false);

        if (($aVals = $this->request()->getArray('val')) || $bAjaxUpload) {
            $sUserDir = Phpfox::getParam('core.dir_user');

            if (isset($aVals['crop-data'])) {
                $bIsUploadNew = $bIsPendingUpload = false;
                $sFileName = base64_decode($this->request()->get('token'));
                if (empty($sFileName) && !empty($aVals['temp_file'])) {
                    $tempFile = Phpfox::getService('core.temp-file')->getByFields($aVals['temp_file'], 'path, server_id');
                    if (empty($tempFile['path'])) {
                        $this->url()->send('profile', _p('upload_failed'));
                    }
                    $sFileName = $sUserDir . sprintf($tempFile['path'], '');
                }
                if (!empty($sFileName)) {
                    if (isset($aVals['temp_file'])) {
                        $sImage = $sFileName;
                    } else {
                        $sImage = $sUserDir . sprintf($sFileName, '');
                        if (!file_exists($sImage)) {
                            $sImage = null;
                        }
                    }

                    if (isset($sImage)) {
                        $aUserImage = Phpfox::getService('user.process')->uploadImage($iUserId, true, $sImage);
                        if ($aUserImage === false) {
                            $sErrorMessage = null;
                            if (!Phpfox_Error::isPassed()) {
                                $sErrorMessage = Phpfox_Error::get();
                                $sErrorMessage = array_shift($sErrorMessage);
                            }
                            $this->url()->send('profile', null, $sErrorMessage);
                        }
                        $bIsUploadNew = true;
                        if (isset($aUserImage['pending_photo'])) {
                            $bIsPendingUpload = true;
                        }
                    }
                }

                if (empty($aVals['crop-data'])) {
                    $this->url()->send('profile');
                }

                if ($bIsUploadNew) {
                    if ($sFileName) {
                        $sTempProfileImage = $sFileName;
                    }
                } else {
                    if (empty($userInfo = Phpfox::getService('user')->getUser(Phpfox::getUserId(), 'u.user_image, u.server_id'))) {
                        $this->url()->send('profile');
                    }
                    if ($userInfo['server_id']) {
                        $sTempProfileImage = Phpfox::getParam('core.dir_file_temp') . sprintf(Phpfox::getService('core.temp-file')->getProfile(), '');
                    } else {
                        if ($oAvatar = storage()->get('user/avatar/' . Phpfox::getUserId())) {
                            $aProfileImage = Phpfox::getService('photo')->getPhoto($oAvatar->value);
                        }
                        if (empty($aProfileImage)) {
                            $this->url()->send('profile');
                        }
                        $sTempProfileImage = Phpfox::getParam('photo.dir_photo') . sprintf($aProfileImage['destination'], '');
                    }
                }

                if ($sTempProfileImage) {
                    $sTempExtension = pathinfo($sTempProfileImage, PATHINFO_EXTENSION);
                } else {
                    $sTempExtension = 'png';
                }

                $oImage = Phpfox_Image::instance();
                $oFile = Phpfox_File::instance();

                if ($sTempExtension == 'gif' && !$oImage->isSupportNextGenImg()) {
                    $this->url()->send('profile');
                }

                $sTempPath = PHPFOX_DIR_CACHE . md5('user_avatar' . Phpfox::getUserId()) . '.' . $sTempExtension;

                if ($sTempExtension == 'gif') {
                    $oFile->copy($sTempProfileImage, $sTempPath);
                    if (!empty($aVals['rotation'])) {
                        $oImage->rotate($sTempPath, $aVals['rotation'], null, false);
                    }
                    if (isset($aVals['zoom']) && isset($aVals['crop_coordinate']) && isset($aVals['preview_size'])) {
                        Phpfox::getService('user.file')->cropGifImage($sTempPath, $aVals['zoom'], $aVals['crop_coordinate'], $aVals['preview_size']);
                    }
                } else {
                    list(, $data) = explode(';', $aVals['crop-data']);
                    list(, $data) = explode(',', $data);
                    $data = base64_decode($data);
                    file_put_contents($sTempPath, $data);
                }

                if (isset($aUserImage) && isset($aUserImage['user_image'])) {
                    $sOldUserImage = $aUserImage['user_image'];
                } else {
                    $sOldUserImage = Phpfox::getUserBy('user_image');
                }

                if ($bIsPendingUpload) {
                    $sNewUserImage = $aUserImage['user_image'];
                } else {
                    $sBuiltUserDir = Phpfox::getLib('file')->getBuiltDir($sUserDir);
                    $sNewUserImage = str_replace($sUserDir, '', $sBuiltUserDir . md5(Phpfox::getUserId() . PHPFOX_TIME . uniqid()) . '%s.' . pathinfo($sOldUserImage, PATHINFO_EXTENSION));
                }

                $iOldServerId = $bIsPendingUpload ? (int)$aUserImage['server_id'] : Phpfox::getUserBy('server_id');

                foreach (Phpfox::getService('user')->getUserThumbnailSizes() as $iSize) {
                    if (Phpfox::getParam('core.keep_non_square_images')) {
                        $oFile->unlink($sUserDir . sprintf($sOldUserImage, '_' . $iSize), $iOldServerId);
                        $oImage->createThumbnail($sTempPath,
                            $sUserDir . sprintf($sNewUserImage, '_' . $iSize), $iSize, $iSize);
                    }
                    $oFile->unlink($sUserDir . sprintf($sOldUserImage, '_' . $iSize . '_square'), $iOldServerId);
                    $oImage->createThumbnail($sTempPath,
                        $sUserDir . sprintf($sNewUserImage, '_' . $iSize . '_square'), $iSize,
                        $iSize, false);
                }

                //Update original image
                $oFile->unlink($sUserDir . sprintf($sOldUserImage, ''), $iOldServerId);
                $sOriginalUserImagePath = $sUserDir . sprintf($sNewUserImage, '');
                $oFile->copy($sTempPath, $sOriginalUserImagePath);
                Phpfox::getLib('cdn')->put($sOriginalUserImagePath);
                @register_shutdown_function(function() use($sOriginalUserImagePath, $sTempPath, $oFile) {
                    if (!Phpfox::getParam('core.keep_files_in_server')) {
                        $oFile->unlink($sOriginalUserImagePath);
                    }
                    @unlink($sTempPath);
                });

                if (!$bIsUploadNew || !$bIsPendingUpload) {
                    Phpfox::getService('user.process')->updateUserFields($iUserId, [
                        'server_id' => (int)Phpfox::getLib('request')->getServer('PHPFOX_SERVER_ID'),
                        'user_image' => $sNewUserImage,
                    ]);
                }

                if (!$bIsPendingUpload) {
                    Phpfox::getService('user.process')->clearFriendCacheOfFriends();
                } elseif (isset($aVals['temp_file'])) {
                    Phpfox::getService('core.temp-file')->delete($aVals['temp_file'], true);
                }

                $this->url()->send('profile', null, !empty($sFileName) && $bIsPendingUpload ? _p('the_profile_photo_is_pending_please_waiting_until_the_approval_process_is_done') : null);
            } else {
                foreach ($_FILES as $file) {
                    if ($file['error'] === UPLOAD_ERR_OK) {
                        continue;
                    }

                    switch ($file['error']) {
                        case UPLOAD_ERR_INI_SIZE:
                            $sMessage = _p('the_uploaded_file_exceeds_the_upload_max_filesize_max_file_size_directive_in_php_ini',
                                ['upload_max_filesize' => ini_get('upload_max_filesize')]);
                            break;
                        case UPLOAD_ERR_FORM_SIZE:
                            $sMessage = _p('the_uploaded_file_exceeds_the_max_file_size_directive_that_was_specified_in_the_html_form');
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $sMessage = _p('the_uploaded_file_was_only_partially_uploaded');
                            break;
                        case UPLOAD_ERR_NO_FILE:
                            $sMessage = _p('no_file_was_uploaded');
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            $sMessage = _p('missing_a_temporary_folder');
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            $sMessage = _p('failed_to_write_file_to_disk');
                            break;
                        case UPLOAD_ERR_EXTENSION:
                            $sMessage = _p('file_upload_stopped_by_extension');
                            break;
                        default:
                            $sMessage = _p('upload_failed');
                            break;
                    }
                }

                if (isset($sMessage)) {
                    Phpfox_Error::set($sMessage);
                } else {
                    $aImage = Phpfox_File::instance()->load('image', ['jpg', 'gif', 'png'],
                        (Phpfox::getUserParam('user.max_upload_size_profile_photo') === 0 ? null : (Phpfox::getUserParam('user.max_upload_size_profile_photo') / 1024)));
                }
            }

            if ($bAjaxUpload && !Phpfox_Error::isPassed()) {
                $sErrors = '';
                foreach (Phpfox_Error::get() as $sError) {
                    $sErrors .= $sError;
                }

                return [
                    'run' => "\$Core.ProfilePhoto.showError('$sErrors');"
                ];
            }

            if (isset($aImage['name']) && !empty($aImage['name'])) {
                if (isset($aVals['is_iframe']) && Phpfox::isAdmin()) {
                    $iUserId = (int)$aVals['user_id'];
                }

                if ($this->request()->get('is_profile_photo')) {
                    if ($bAjaxUpload) {
                        $sType = 'user';
                        $aParams = array_merge(Phpfox::callback($sType . '.getUploadParams', ['is_profile_photo' => 1]), [
                            'update_space' => false,
                            'type' => $sType,
                            'thumbnail_sizes' => [],
                        ]);

                        $aFile = Phpfox::getService('user.file')->upload($aParams['param_name'], $aParams, false, false);

                        if (!$aFile || !empty($aFile['error'])) {
                            echo json_encode([
                                'error' => !empty($aFile['error']) ? $aFile['error'] : _p('upload_fail_please_try_again_later'),
                            ]);
                            exit;
                        }

                        $iServerId = 0; //Storage temp photo in local in case setting keep file in server is turned off

                        $iTempFileId = Phpfox::getService('core.temp-file')->add([
                            'type' => $sType,
                            'size' => $aFile['size'],
                            'path' => $aFile['name'],
                            'server_id' => $iServerId,
                        ]);


                        $sImage = Phpfox::getLib('image.helper')->display([
                            'server_id'  => $iServerId,
                            'title'      => Phpfox::getUserBy('full_name'),
                            'path'       => 'core.url_user',
                            'file'       =>  $aFile['name'],
                            'suffix'     => '',
                            'no_default' => true,
                            'return_url' => true,
                        ]);

                        $jsonParams = [
                            'imagePath' => $sImage,
                            'serverId' => $iServerId,
                            'tempFileId' => $iTempFileId,
                        ];

                        echo json_encode($jsonParams);

                        exit;
                    }
                } elseif (($aImage = Phpfox::getService('user.process')->uploadImage($iUserId)) !== false) {
                    if (!isset($aImage['pending_photo'])) {
                        Phpfox::getService('user.process')->clearFriendCacheOfFriends();
                    }
                    if (isset($aVals['is_iframe'])) {
                        $sImage = Phpfox::getLib('image.helper')->display([
                            'server_id'  => isset($aImage['pending_photo']) ? Phpfox::getUserBy('server_id') : $aImage['server_id'],
                            'path'       => 'core.url_user',
                            'file'       => isset($aImage['pending_photo']) ? Phpfox::getUserBy('user_image') : $aImage['user_image'],
                            'suffix'     => '_50_square',
                            'max_width'  => 50,
                            'max_height' => 50,
                            'thickbox'   => true,
                            'time_stamp' => true
                        ]);

                        echo "<script type=\"text/javascript\">window.parent.document.getElementById('js_user_photo_" . $iUserId . "').innerHTML = '{$sImage}'; window.parent.tb_remove(); window.parent.\$Core.loadInit();</script>";
                        exit;
                    } else {
                        if ($bAjaxUpload) {
                            $sImage = Phpfox::getLib('image.helper')->display([
                                'server_id'  => isset($aImage['pending_photo']) ? Phpfox::getUserBy('server_id') : $aImage['server_id'],
                                'title'      => Phpfox::getUserBy('full_name'),
                                'path'       => 'core.url_user',
                                'file'       =>  isset($aImage['pending_photo']) ? Phpfox::getUserBy('user_image') : $aImage['user_image'],
                                'suffix'     => '',
                                'no_default' => true,
                                'return_url' => true,
                            ]);
                            $jsonParams = [
                                'imagePath' => $sImage,
                                'serverId' => isset($aImage['pending_photo']) ? Phpfox::getUserBy('server_id') : $aImage['server_id'],
                            ];
                            if (isset($aImage['pending_photo'])) {
                                $jsonParams = array_merge($jsonParams, [
                                    'pendingPhoto' => 1,
                                    'warningTitle' => html_entity_decode(_p('notice')),
                                    'warningMessage' => html_entity_decode(_p('the_profile_photo_is_pending_please_waiting_until_the_approval_process_is_done')),
                                ]);
                            }

                            echo json_encode($jsonParams);

                            exit;
                        }
                        $this->url()->send('profile', null, isset($aImage['pending_photo']) ? _p('the_profile_photo_is_pending_please_waiting_until_the_approval_process_is_done') : null);
                    }
                }
            }
        }

        if (isset($aVals['is_iframe'])) {
            exit;
        }
        $sFileName = base64_decode($this->request()->get('token'));
        if (empty($sFileName)) {
            $sFileName = Phpfox::getUserBy('user_image');
        }

        $aUserImage = \Phpfox::getService('user')->getUser($iUserId, 'u.user_image');
        if (!empty($aUserImage['user_image'])) {
            $sImage = Phpfox_Image_Helper::instance()->display([
                    'server_id'  => Phpfox::getUserBy('server_id'),
                    'title'      => Phpfox::getUserBy('full_name'),
                    'path'       => 'core.url_user',
                    'file'       => $sFileName,
                    'suffix'     => '',
                    'no_default' => true,
                    'time_stamp' => true,
                    'id'         => 'user_profile_photo',
                    'class'      => 'border',
                    'return_url' => true,
                ]
            );

            if (!empty($sImage)) {
                list($newHeight, $newWidth) = getimagesize($sImage);
                $this->template()->assign([
                        'iImageHeight' => $newHeight,
                        'iImageWidth'  => $newWidth
                    ]
                );
            }
        }

        $sPageTitle = ($bIsRegistration ? _p('upload_profile_picture') : _p('edit_profile_picture'));
        (($sPlugin = Phpfox_Plugin::get('user.component_controller_photo_3')) ? eval($sPlugin) : false);
        $this->template()->setTitle($sPageTitle)
            ->setBreadCrumb($sPageTitle)
            ->setPhrase([
                    'select_a_file_to_upload'
                ]
            )
            ->setHeader([
                    'jquery.cropit.js' => 'module_user',
                    'progress.js'      => 'static_script',
                    '<script type="text/javascript">$Behavior.changeUserPhoto = function(){ if ($Core.exists(\'#js_photo_form_holder\')) { oProgressBar = {holder: \'#js_photo_form_holder\', progress_id: \'#js_progress_bar\', uploader: \'#js_progress_uploader\', add_more: false, max_upload: 1, total: 1, frame_id: \'js_upload_frame\', file_id: \'image\'}; $Core.progressBarInit(); } }</script>'
                ]
            )
            ->assign([
                    'sProfileImage'   => $sImage,
                    'bIsRegistration' => $bIsRegistration,
                    'sNextUrl'        => $this->url()->makeUrl($sNextUrl),
                    'iMinWidth'       => 248,
                    'iMaxFileSize'    => (Phpfox::getUserParam('user.max_upload_size_profile_photo') === 0 ? null : ((Phpfox::getUserParam('user.max_upload_size_profile_photo') / 1024) * 1048576))
                ]
            );

        return null;
    }
}