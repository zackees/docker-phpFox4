<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 * GD Image Layer
 * Class is used to create/manipulate images using GD
 *
 * @link http://php.net/manual/en/book.image.php
 * @copyright        [PHPFOX_COPYRIGHT]
 * @author            phpFox LLC
 * @package        Phpfox
 * @version        $Id: gd.class.php 7174 2014-03-10 14:14:10Z Fern $
 */
class Phpfox_Image_Library_Gd extends Phpfox_Image_Abstract
{
    /**
     * Check to identify if a thumbnail is larger then the actual image being uploaded
     *
     * @var bool
     */
    public $thumbLargeThenPic = false;

    /**
     * Resource for the image we are creating
     *
     * @var resource
     */
    private $_hImg;

    /**
     * Class constructor
     *
     */
    public function __construct()
    {
    }

    /**
     * Set unlimited condition for long processing. Etc: Rotate/Crop gif image
     */
    public function setUnlimitedProcessTime()
    {
        if (function_exists('ini_set')) {
            ini_set('memory_limit', '-1');
            ini_set('max_execution_time', 0);
        }
        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }
    }

    /**
     * Crop an image
     *
     * @param string $sImage Full path to the image we are working with
     * @param string $sDestination Full path where the new image will be placed
     * @param int $iWidth Width of the working image
     * @param int $iHeight Height of the working image
     * @param int $iStartWidth Starting point of where we are cropping the image (X)
     * @param int $iStartHeight Starting point of where we are cropping the image (Y)
     * @param int $iScale Width/Height of what the image should be scalled to
     * @return bool FALSE on failure, NULL on success
     */
    public function cropImage($sImage, $sDestination, $iWidth, $iHeight, $iStartWidth, $iStartHeight, $iScale)
    {
        if (!$this->_load($sImage)) {
            return false;
        }

        $iScale = ($iScale / $iWidth);

        $iNewImageWidth = ceil($iWidth * $iScale);
        $iNewImageHeight = ceil($iHeight * $iScale);

        switch ($this->_aInfo[2]) {
            case 1:
                $hFrm = @imagecreatefromgif($this->sPath);
                break;
            case 3:
                $hFrm = @imagecreatefrompng($this->sPath);
                break;
            default:
                $hFrm = @imagecreatefromjpeg($this->sPath);
                break;
        }

        $hTo = imagecreatetruecolor($iNewImageWidth, $iNewImageHeight);

        switch ($this->sType) {
            case 'gif':
                $iBlack = imagecolorallocate($hTo, 0, 0, 0);
                imagecolortransparent($hTo, $iBlack);
                break;
            case 'jpeg':
            case 'jpg':
            case 'jpe':
                imagealphablending($hTo, true);
                break;
            case 'png':
                imagealphablending($hTo, false);
                imagesavealpha($hTo, true);
                break;
        }

        imagecopyresampled($hTo, $hFrm, 0, 0, $iStartWidth, $iStartHeight, $iNewImageWidth, $iNewImageHeight, $iWidth, $iHeight);

        switch ($this->sType) {
            case 'gif':
                if (!$hTo) {
                    @copy($this->sPath, $sDestination);
                } else {
                    @imagegif($hTo, $sDestination);
                }
                break;
            case 'png':
                @imagepng($hTo, $sDestination);
                imagealphablending($hTo, false);
                imagesavealpha($hTo, true);
                break;
            default:
                @imagejpeg($hTo, $sDestination);
                break;
        }

        @imagedestroy($hTo);
        @imagedestroy($hFrm);

        Phpfox::getLib('cdn')->put($sDestination);
    }


    public function fixOrientation($sImage, $bUseCdn = true)
    {
        @getimagesize($sImage, $aInfo);
        if (function_exists('exif_read_data') && isset($aInfo['APP1']) && preg_match('/exif/i', $aInfo['APP1'])) {
            $exif = @exif_read_data($sImage);
            if (!empty($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 1:
                    case 2:
                        break;
                    case 3:
                    case 4:
                        // 90 degrees
                        $this->rotate($sImage, 'right', null, $bUseCdn);
                        // 180 degrees
                        $this->rotate($sImage, 'right', null, $bUseCdn);
                        break;
                    case 5:
                    case 6:
                        // 90 degrees right
                        $this->rotate($sImage, 'right', null, $bUseCdn);
                        break;
                    case 7:
                    case 8:
                        // 90 degrees left
                        $this->rotate($sImage, 'left', null, $bUseCdn);
                        break;
                    default:
                        break;
                }
            }
        }
    }

    /**
     * Create a thumbnail for an image
     *
     * @param string $sImage Full path of the original image
     * @param string $sDestination Full path for the newly created thumbnail
     * @param int $nMaxW Max width of the thumbnail
     * @param int $nMaxH Max height of the thumbnail
     * @param bool $bRatio TRUE to keep the aspect ratio and FALSE to not keep it
     * @param bool $bSkipCdn Skip the CDN routine
     * @return mixed FALSE on failure, TRUE or NULL on success
     */
    public function createThumbnail($sImage, $sDestination, $nMaxW, $nMaxH, $bRatio = true, $bSkipCdn = false)
    {
        if (!$this->_load($sImage)) {
            return false;
        }

        if ($bRatio) {
            list($nNewW, $nNewH) = $this->_calcSize($nMaxW, $nMaxH);
        } else {
            return $this->createSquareThumbnail($sImage, $sDestination, $nMaxW, $nMaxH, $bSkipCdn);
        }

        $bCanSkipCDN = $bSkipCdn === true && $nNewW > 150 || $bSkipCdn === 'force_skip';

        if (($this->_aInfo[2] == 1 && !$this->isSupportNextGenImg())
            || $this->nW < $nNewW
            || $this->nH < $nNewH
            || ($this->nW == $nNewW && $this->nH == $nNewH)) {
            if (($success = @copy($this->sPath, $sDestination)) && !$bCanSkipCDN) {
                Phpfox::getLib('cdn')->put($sDestination);
            }
            return $success;
        }

        if ((int)$nNewH === 0) {
            $nNewH = 1;
        }

        if ((int)$nNewW === 0) {
            $nNewW = 1;
        }

        if ($this->_aInfo[2] === 1) {
            $this->setUnlimitedProcessTime();
            $frameImages = new Imagick($this->sPath);
            $frameImages = $frameImages->coalesceImages();

            foreach ($frameImages as $frameImage) {
                $frameImage->thumbnailImage($nNewW, $nNewH);
                $frameImage->setImagePage($nNewW, $nNewH, 0, 0);
            }

            $frameImages = $frameImages->deconstructImages();
            $frameImages->writeImages($sDestination, true);
            $frameImages->clear();
            $frameImages->destroy();
        } else {
            switch ($this->_aInfo[2]) {
                case 3:
                    $hFrm = @imagecreatefrompng($this->sPath);
                    break;
                default:
                    $hFrm = @imagecreatefromjpeg($this->sPath);
                    break;
            }

            $hTo = imagecreatetruecolor($nNewW, $nNewH);

            switch ($this->sType) {
                case 'jpeg':
                case 'jpg':
                case 'jpe':
                    imagealphablending($hTo, true);
                    break;
                case 'png':
                    imagealphablending($hTo, false);
                    imagesavealpha($hTo, true);
                    break;
            }

            if ($this->thumbLargeThenPic === false && $this->nH <= $nNewH && $this->nW <= $nNewW) {
                $hTo = $hFrm;
            } else {
                if ($hFrm) {
                    imagecopyresampled($hTo, $hFrm, 0, 0, 0, 0, $nNewW, $nNewH, $this->nW, $this->nH);
                }
            }

            switch ($this->sType) {
                case 'png':
                    imagepng($hTo, $sDestination);
                    imagealphablending($hTo, false);
                    imagesavealpha($hTo, true);
                    break;
                default:
                    @imagejpeg($hTo, $sDestination);
                    break;
            }

            @imagedestroy($hTo);
            @imagedestroy($hFrm);
        }

        if (in_array($this->sType, ['jpg', 'jpeg']) && function_exists('exif_read_data')) {
            @getimagesize($sImage, $aInfo);
            if (isset($aInfo['APP1']) && preg_match('/exif/i', $aInfo['APP1'])) {
                $exif = @exif_read_data($sImage);
                if (!empty($exif['Orientation'])) {
                    switch ($exif['Orientation']) {
                        case 1:
                        case 2:
                            break;
                        case 3:
                        case 4:
                            // 90 degrees
                            $this->rotate($sDestination, 'right');
                            // 180 degrees
                            $this->rotate($sDestination, 'right');
                            break;
                        case 5:
                        case 6:
                            // 90 degrees right
                            $this->rotate($sDestination, 'right');
                            break;
                        case 7:
                        case 8:
                            // 90 degrees left
                            $this->rotate($sDestination, 'left');
                            break;
                        default:
                            break;
                    }
                }
            }
        }

        if (!$bCanSkipCDN) {
            Phpfox::getLib('cdn')->put($sDestination);
        }
    }

    public function createSquareThumbnail($sSrc, $sDestination, $iNewWIdth = 0, $iNewHeight = 0, $bSkipCdn = false, $iZoom = 1, $iQuality = 100)
    {
        $bCanSkipCDN = $bSkipCdn === true && $iNewWIdth > 150 || $bSkipCdn === 'force_skip';

        if ($this->sType == 'gif' && !$this->isSupportNextGenImg()) {
            if (($success = @copy($sSrc, $sDestination)) && !$bCanSkipCDN) {
                Phpfox::getLib('cdn')->put($sDestination);
            }
            return $success;
        }

        $bIsGifImage = $this->sType == 'gif';

        if ($iNewWIdth == 0 && $iNewHeight == 0) {
            $iNewWIdth = 100;
            $iNewHeight = 100;
        }

        switch ($this->sType) {
            case 'jpg':
                $hImage = @imagecreatefromjpeg($sSrc);
                break;
            case 'png':
                $hImage = @imagecreatefrompng($sSrc);
                break;
            case 'gif':
                $hImage = @imagecreatefromgif($sSrc);
                break;
        }

        $iWidth = imagesx($hImage);
        $iHeight = imagesy($hImage);
        $origin_x = 0;
        $origin_y = 0;

        if ($iNewWIdth && !$iNewHeight) {
            $iNewHeight = floor($iHeight * ($iNewWIdth / $iWidth));
        } elseif ($iNewHeight && !$iNewWIdth) {
            $iNewWIdth = floor($iWidth * ($iNewHeight / $iHeight));
        }

        if ($iZoom == 3) {
            $final_height = $iHeight * ($iNewWIdth / $iWidth);

            if ($final_height > $iNewHeight) {
                $iNewWIdth = $iWidth * ($iNewHeight / $iHeight);
            } else {
                $iNewHeight = $final_height;
            }
        }

        if (!$bIsGifImage) {
            $hNewImage = imagecreatetruecolor($iNewWIdth, $iNewHeight);
            imagealphablending($hNewImage, false);

            $color = imagecolorallocatealpha($hNewImage, 0, 0, 0, 127);

            imagefill($hNewImage, 0, 0, $color);
        }

        if ($iZoom == 2) {
            $final_height = $iHeight * ($iNewWIdth / $iWidth);

            if ($final_height > $iNewHeight) {
                $origin_x = $iNewWIdth / 2;
                $iNewWIdth = $iWidth * ($iNewHeight / $iHeight);
                $origin_x = round($origin_x - ($iNewWIdth / 2));
            } else {
                $origin_y = $iNewHeight / 2;
                $iNewHeight = $final_height;
                $origin_y = round($origin_y - ($iNewHeight / 2));
            }
        }

        if (!$bIsGifImage) {
            imagesavealpha($hNewImage, true);
        }

        if ($iZoom > 0) {
            $sSrc_x = $sSrc_y = 0;
            $sSrc_w = $iWidth;
            $sSrc_h = $iHeight;

            $cmp_x = $iWidth / $iNewWIdth;
            $cmp_y = $iHeight / $iNewHeight;

            if ($cmp_x > $cmp_y) {
                $sSrc_w = round($iWidth / $cmp_x * $cmp_y);
                $sSrc_x = round(($iWidth - ($iWidth / $cmp_x * $cmp_y)) / 2);

            } elseif ($cmp_y > $cmp_x) {
                $sSrc_h = round($iHeight / $cmp_y * $cmp_x);
                $sSrc_y = round(($iHeight - ($iHeight / $cmp_y * $cmp_x)) / 2);
            }

            if (!$bIsGifImage) {
                imagecopyresampled($hNewImage, $hImage, $origin_x, $origin_y, $sSrc_x, $sSrc_y, $iNewWIdth, $iNewHeight, $sSrc_w, $sSrc_h);
            }

        } elseif (!$bIsGifImage) {
            imagecopyresampled($hNewImage, $hImage, 0, 0, 0, 0, $iNewWIdth, $iNewHeight, $iWidth, $iHeight);
        }

        if (file_exists($sDestination)) {
            if (@unlink($sDestination) != true) {
                @rename($sDestination, $sDestination . '_' . rand(10, 99));
            }
        }

        if (!$bIsGifImage) {
            switch ($this->sType) {
                case 'png':
                    imagepng($hNewImage, $sDestination);
                    imagealphablending($hNewImage, false);
                    imagesavealpha($hNewImage, true);
                    break;
                default:
                    @imagejpeg($hNewImage, $sDestination);
                    break;
            }
        } else {
            $this->setUnlimitedProcessTime();
            $frameImages = new Imagick($sSrc);
            $frameImages = $frameImages->coalesceImages();

            foreach ($frameImages as $frameImage) {
                $frameImage->thumbnailImage($iNewWIdth, $iNewHeight);
                $frameImage->setImagePage($iNewWIdth, $iNewHeight, 0, 0);
            }

            $frameImages = $frameImages->deconstructImages();
            $frameImages->writeImages($sDestination, true);
            $frameImages->clear();
            $frameImages->destroy();
        }

        if (!$bIsGifImage) {
            if (in_array($this->sType, ['jpg', 'jpeg']) && function_exists('exif_read_data')) {
                $exif = @exif_read_data($sSrc);
                if (!empty($exif['Orientation'])) {
                    switch ($exif['Orientation']) {
                        case 1:
                        case 2:
                            break;
                        case 3:
                        case 4:
                            // 90 degrees
                            $this->rotate($sDestination, 'right');
                            // 180 degrees
                            $this->rotate($sDestination, 'right');
                            break;
                        case 5:
                        case 6:
                            // 90 degrees right
                            $this->rotate($sDestination, 'right');
                            break;
                        case 7:
                        case 8:
                            // 90 degrees left
                            $this->rotate($sDestination, 'left');
                            break;
                        default:
                            break;
                    }
                }
            }

            @imagedestroy($hNewImage);
            @imagedestroy($hImage);
        }

        if (!$bCanSkipCDN) {
            Phpfox::getLib('cdn')->put($sDestination);
        }
    }

    /**
     * Rotate an image (left or right)
     *
     * @param string $sImage Full path to the image
     * @param string $sCmd Command to perform. Must be "left" or "right" (without quotes)
     * @param null $sActualFile
     * @param bool $bInCdn If image in cdn, set it to true
     * @return mixed FALSE on failure, NULL on success
     */
    public function rotate($sImage, $sCmd, $sActualFile = null, $bInCdn = true)
    {
        if (!$this->_load($sImage)) {
            return false;
        }

        if ($this->_aInfo[2] == 1) {
            if (!$this->isSupportNextGenImg()) {
                return false;
            }

            try {
                $this->setUnlimitedProcessTime();
                $frameImages = new Imagick($this->sPath);
                $frameImages = $frameImages->coalesceImages();

                foreach ($frameImages as $frameImage) {
                    if (is_numeric($sCmd)) {
                        switch ((int)$sCmd) {
                            case 90:
                            case -270:
                                $degree = 90;
                                break;
                            case 180:
                                $degree = 180;
                                break;
                            case 270:
                            case -90:
                                $degree = 270;
                                break;
                            default:
                                $degree = 0;
                                break;
                        }

                        if ($degree) {
                            $frameImage->rotateImage('#000', $degree);
                        }
                    } else {
                        if ($sCmd == 'left') {
                            $frameImage->rotateImage('#000', 270);
                        } else {
                            $frameImage->rotateImage('#000', 90);
                        }
                    }
                }

                @unlink($this->sPath);
                $frameImages = $frameImages->deconstructImages();
                $frameImages->writeImages($this->sPath, true);
                $frameImages->clear();
                $frameImages->destroy();
            } catch (Exception $exception) {
                return Phpfox_Error::set($exception->getMessage());
            }
        } else {
            switch ($this->_aInfo[2]) {
                case 3:
                    $hFrm = @imagecreatefrompng($this->sPath);
                    break;
                default:
                    $hFrm = @imagecreatefromjpeg($this->sPath);
                    break;
            }

            if (substr($this->sPath, 0, 7) != 'http://') {
                @unlink($this->sPath);
            }

            if (function_exists('imagerotate')) {
                if ($sCmd == 'left') {
                    $im2 = imagerotate($hFrm, 90, 0);
                } else {
                    $im2 = imagerotate($hFrm, 270, 0);
                }
            } else {
                $wid = imagesx($hFrm);
                $hei = imagesy($hFrm);
                $im2 = imagecreatetruecolor($hei, $wid);

                switch ($this->sType) {
                    case 'jpeg':
                    case 'jpg':
                    case 'jpe':
                        imagealphablending($im2, true);
                        break;
                    case 'png':
                        break;
                }

                for ($i = 0; $i < $wid; $i++) {
                    for ($j = 0; $j < $hei; $j++) {
                        $ref = imagecolorat($hFrm, $i, $j);
                        if ($sCmd == 'right') {
                            imagesetpixel($im2, ($hei - 1) - $j, $i, $ref);
                        } else {
                            imagesetpixel($im2, $j, $wid - $i, $ref);
                        }
                    }
                }
            }

            switch ($this->sType) {
                case 'png':
                    imagealphablending($im2, false);
                    imagesavealpha($im2, true);
                    @imagepng($im2, $this->sPath);
                    break;
                default:
                    @imagejpeg($im2, $this->sPath);
                    break;
            }

            imagedestroy($hFrm);
            imagedestroy($im2);
        }

        // only run below code if image uploaded to cdn place
        if ($bInCdn) {
            Phpfox::getLib('cdn')->put($this->sPath, $sActualFile);
        }
    }

    /**
     * @deprecated 4.7.0
     * Adds a image or text watermark depending on the settings provided by admins.
     *
     * @see self::addText()
     * @see self::addWatermark()
     * @param string $sImage Full path to the image
     * @return bool TRUE on success, FALSE on failure
     */
    public function addMark($sImage)
    {
        return false;
    }

    /**
     * @deprecated 4.7.0
     * Adds a watermark text on an image.
     *
     * @param string $sImage Full path to the image
     * @return bool TRUE on success, FALSE on failure
     */
    public function addText($sImage)
    {
        return true;
    }

    /**
     * @deprecated 4.7.0
     * Adds an image watermark on an image.
     *
     * @param string $sImage Full path to the image
     * @return bool TRUE on success, FALSE on failure
     */
    public function addWatermark($sImage)
    {
        return true;
    }
}