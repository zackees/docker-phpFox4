<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Service_File
 */
class User_Service_File extends Phpfox_Service
{
	
	/**
	 * Class constructor
	 */	
	public function __construct() {

	}

    /**
     * @param $sTempPath
     * @param $zoom
     * @param $coordinate
     * @param $previewSize
     * @return false|void
     * @throws ImagickException
     */
    public function cropGifImage($sTempPath, $zoom, $coordinate, $previewSize)
    {
        $oImage = Phpfox_Image::instance();
        if (!$oImage->isSupportNextGenImg()
            || empty($sTempPath) || !file_exists($sTempPath) || !isset($zoom) || empty($coordinate) || empty($previewSize)) {
            return false;
        }

        try {
            $oImage->setUnlimitedProcessTime();
            $zoom = floatval($zoom);
            $coordinateX = round(floatval($coordinate['x']));
            $coordinateY = round(floatval($coordinate['y']));
            list($oldWidth, $oldHeight) = getimagesize($sTempPath);
            $frameImages = new Imagick($sTempPath);
            $newHeight = round(floatval($oldHeight) * $zoom);
            $newWidth = round(floatval($oldWidth) * $zoom);
            $frameImages = $frameImages->coalesceImages();

            foreach ($frameImages as $frameImage) {
                //Resize image to the new size that is suitable with coordinate
                $frameImage->resizeImage($newWidth, $newHeight, Imagick::FILTER_BOX, 0.9);
                //Crop image with width and height with coordinate X + Y that user zoom
                $frameImage->cropImage($previewSize['width'], $previewSize['height'], $coordinateX <0 ? $coordinateX * -1 : $coordinateX, $coordinateY < 0 ? $coordinateY * -1 : $coordinateY);
                //Using to ignore blank page
                $frameImage->setImagePage(0, 0, 0, 0);
            }

            $frameImages = $frameImages->deconstructImages();
            $frameImages->writeImages($sTempPath, true);
            $frameImages->clear();
            $frameImages->destroy();
        } catch (Exception $exception) {
            Phpfox_Error::set($exception->getMessage());
        }
    }

    /**
     * @param string $sName
     * @param array $aParams
     * @return array|bool|mixed
     */
	public function load($sName = '', $aParams = [])
    {
        $iUserId = isset($aParams['user_id']) ? $aParams['user_id'] : Phpfox::getUserId();
        if (empty($sName) || empty($aParams['type']) || empty($aParams['upload_dir']) || (empty($aParams['force_upload']) && empty($iUserId))) {
            return false;
        }

        $oFile = Phpfox_File::instance();
        $sErrorMessage = '';
        $aImage = [];
        $bUpdateSpace = isset($aParams['update_space']) ? $aParams['update_space'] : true;
        if ($_FILES[$sName]['error'] == UPLOAD_ERR_OK) {
            $aImage = $oFile->load($sName, $aParams['type_list'], $aParams['max_size'], $bUpdateSpace);
            if (!$aImage) {
                $sErrorMessage = implode(', ', \Phpfox_Error::get());
            }
        }  else {
            // check file error
            switch ($_FILES[$sName]['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $sErrorMessage = _p('the_uploaded_file_exceeds_the_upload_max_filesize_max_file_size_directive_in_php_ini',
                        ['upload_max_filesize' => ini_get('upload_max_filesize')]);
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $sErrorMessage = _p("the_uploaded_file_exceeds_the_max_file_size_directive_that_was_specified_in_the_html_form");
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $sErrorMessage = _p("the_uploaded_file_was_only_partially_uploaded");
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $sErrorMessage = _p("no_file_was_uploaded");
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $sErrorMessage = _p("missing_a_temporary_folder");
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $sErrorMessage = _p("failed_to_write_file_to_disk");
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $sErrorMessage = _p("file_upload_stopped_by_extension");
                    break;
                default:
                    $sErrorMessage = _p("unknown_upload_error");
                    break;
            }
        }

        if (!empty($sErrorMessage)) {
            return [
                'error' => $sErrorMessage
            ];
        }

        return $aImage;
    }
    /**
     * @param string $sName
     * @param array $aParams
     * @param bool $bIsLoaded
     * @return array|bool
     */
    public function upload($sName = '', $aParams = [], $bIsLoaded = false, $bUseCdn = true)
    {
        $iUserId = isset($aParams['user_id']) ? $aParams['user_id'] : Phpfox::getUserId();
        if (empty($sName) || empty($aParams['type']) || empty($aParams['upload_dir']) || (empty($aParams['force_upload']) && empty($iUserId))) {
            return false;
        }

        if (empty($bIsLoaded)) {
            $aImage = $this->load($sName, $aParams);
            if (empty($aImage) || !empty($aImage['error'])) {
                return $aImage;
            }
        }

        if(!is_dir($aParams['upload_dir'])) {
            @mkdir($aParams['upload_dir']);
        }

        $oImage = Phpfox_Image::instance();
        $oFile = Phpfox_File::instance();
        $bUpdateSpace = isset($aParams['update_space']) ? $aParams['update_space'] : true;
        $bModifyName = isset($aParams['modify_name']) ? $aParams['modify_name'] : true;
        $bNoSquare = isset($aParams['no_square']) ? $aParams['no_square'] : false;
        $sFileName = $oFile->upload($sName, $aParams['upload_dir'], !empty($aParams['file_name']) ? $aParams['file_name'] : uniqid(), $bModifyName, 0644, true, $bUseCdn);
        $sFilePath = $aParams['upload_dir'] . sprintf($sFileName, '');

        // crop max width
        if (Phpfox::isAppActive('Core_Photos')) {
            Phpfox::getService('photo')->cropMaxWidth($sFilePath, $bUseCdn);
        }
        $iFileSize = filesize($sFilePath);

        if (!empty($aParams['thumbnail_sizes'])) {
            foreach ($aParams['thumbnail_sizes'] as $iSize) {
                if (Phpfox::getParam('core.keep_non_square_images') || $bNoSquare) {
                    $oImage->createThumbnail($aParams['upload_dir'] . sprintf($sFileName, ''),
                        $aParams['upload_dir'] . sprintf($sFileName, '_' . $iSize), $iSize, $iSize, true, !$bUseCdn);
                }
                if (!$bNoSquare) {
                    $oImage->createThumbnail($aParams['upload_dir'] . sprintf($sFileName, ''),
                        $aParams['upload_dir'] . sprintf($sFileName, '_' . $iSize . '_square'), $iSize, $iSize,
                        false, !$bUseCdn);
                }

            }
        }
        // Update user space usage
        if ($bUpdateSpace && $iFileSize) {
            Phpfox::getService('user.space')->update($iUserId, $aParams['type'], $iFileSize);
        }

        return [
            'name' => $sFileName,
            'size' => $iFileSize
        ];
    }

    /**
     * @param array $aParams
     * @return bool
     */
	public function remove($aParams = [])
    {
	    $iUserId = isset($aParams['user_id']) ? $aParams['user_id'] : Phpfox::getUserId();
	    if (empty($iUserId) || empty($aParams['type']) || empty($aParams['path']) || empty($aParams['upload_dir']) || empty($aParams['upload_path'])) {
	        return false;
        }

        $iServerId = isset($aParams['server_id']) ? $aParams['server_id'] : 0;
	    $iFileSize = 0;
        $aSizes = isset($aParams['thumbnail_sizes']) ? $aParams['thumbnail_sizes'] : [];
        $aSquareSizes = array_map(function($ele) {
            return $ele . '_square';
        }, $aSizes);
        $bUpdateSpace = isset($aParams['update_space']) ? $aParams['update_space'] : false;

        foreach (array_merge([''], $aSizes, $aSquareSizes) as $iSize) {
            $sPrefix = (empty($iSize) ? '' : '_') . $iSize;
            $sPath = $aParams['upload_dir'] . sprintf($aParams['path'], $sPrefix);
            $sUrl = Phpfox::getLib('cdn')->getUrl($aParams['upload_path'] . sprintf($aParams['path'], $sPrefix));
            $bIsCDN = $iServerId > 0;

            if ($bUpdateSpace && empty($sPrefix)) {
                if (isset($aParams['size'])) {
                    $iFileSize = $aParams['size'];
                } elseif (file_exists($sPath)) {
                    $iFileSize = filesize($sPath);
                } elseif ($bIsCDN) {
                    $aHeaders = get_headers($sUrl, true);
                    if (preg_match('/200 OK/i', $aHeaders[0])) {
                        $iFileSize = isset($aHeaders["Content-Length"]) ? (int)$aHeaders["Content-Length"] : 0;
                    }
                }
            }

            Phpfox::getLib('file')->unlink($sPath, $iServerId);
        }

        if ($bUpdateSpace && $iFileSize) {
            return  Phpfox::getService('user.space')->update($iUserId, $aParams['type'], $iFileSize, '-');
        }

        return true;
    }
	
	/**
	 * If a call is made to an unknown method attempt to connect
	 * it to a specific plug-in with the same name thus allowing 
	 * plug-in developers the ability to extend classes.
	 *
	 * @param string $sMethod is the name of the method
	 * @param array $aArguments is the array of arguments of being passed
     * @return mixed
	 */
	public function __call($sMethod, $aArguments)
	{
		/**
		 * Check if such a plug-in exists and if it does call it.
		 */
		if ($sPlugin = Phpfox_Plugin::get('user.service_file__call'))
		{
			eval($sPlugin);
            return null;
		}
			
		/**
		 * No method or plug-in found we must throw a error.
		 */
		Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
	}
}
