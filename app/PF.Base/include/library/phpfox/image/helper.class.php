<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 * Image Helper
 * Displays all the images we see on a phpFox site. Each image runs thru this class where
 * we perform many sanity and file size checks before they are displayed on a site.
 *
 * @copyright        [PHPFOX_COPYRIGHT]
 * @author            phpFox LLC
 * @package        Phpfox
 * @version        $Id: helper.class.php 7287 2014-04-28 16:29:52Z Fern $
 */
class Phpfox_Image_Helper
{

    /**
     * @return Phpfox_Image_Helper
     */
    public static function instance()
    {
        return Phpfox::getLib('image.helper');
    }

    private $preventProfilePhotoCache;
    private $globalGenders;
    private $srcPathBase;

    public function __construct()
    {
        $this->preventProfilePhotoCache = Phpfox::getParam('user.prevent_profile_photo_cache');
        $this->globalGenders = (array)Phpfox::getParam('user.global_genders');
        $this->srcPathBase = Phpfox::getParam('core.path_actual');
    }

    /**
     * Returns a new width/height for an image based on the max arguments passed
     *
     * @param string|array $sImage Full path to the image
     * @param int $iMaxHeight Max height of the image
     * @param int $iMaxWidth Max width of the image
     * @param int $iWidth Actual width of the image (optional)
     * @param int $iHeight Actual height of the image (optional)
     * @return array Returns an ARRAY, where argument 1 is the new height and argument 2 is the new width
     */
    public function getNewSize($sImage, $iMaxHeight, $iMaxWidth, $iWidth = 0, $iHeight = 0)
    {
        if (is_array($sImage)) {
            if (!Phpfox::getParam('core.keep_files_in_server') && isset($sImage[1]) && isset($sImage[2])) {
                $iWidth = $sImage[1];
                $iHeight = $sImage[2];
            }
            $sImage = $sImage[0];
        } else {
            if ($sImage !== null && (!file_exists($sImage) || filesize($sImage) < 1)) {
                return array(0, 0);
            }
        }

        if (!$iWidth && !$iHeight) {
            if (file_exists($sImage)) {
                list($iWidth, $iHeight) = getimagesize($sImage);
            } else {
                $iWidth = 0;
                $iHeight = 0;
            }
        }

        $k = "";
        //get scaling factor
        if ($iMaxWidth && $iMaxHeight && $iWidth && $iHeight) {
            $kX = $iMaxWidth / $iWidth;
            $kY = $iMaxHeight / $iHeight;
            $k = min($kX, $kY);
        } elseif ($iMaxHeight && $iHeight) {
            $k = $iMaxHeight / $iHeight;
        } elseif ($iMaxWidth && $iWidth) {
            $k = $iMaxWidth / $iWidth;
        }

        //correct scaling factor
        if (((0 >= $k) || ($k > 1))) {
            $k = 1;
        }

        $iHeight *= $k;
        $iWidth *= $k;

        if ($iHeight < 1) {
            $iHeight = 1;
        }
        if ($iWidth < 1) {
            $iWidth = 1;
        }

        return array(round($iHeight), round($iWidth));
    }

    /**
     * Displays an image on the site based on params passed
     *
     * @param array $aParams Holds an ARRAY of params about the image
     * @return string Returns the HTML <image> or the full path to the image based on the params passed with the 1st argument
     */
    public function display($aParams, $bIsLoop = false)
    {
        static $aImages = array();
        static $sPlugin;

        // Create hash for cache
        $sHash = md5(serialize($aParams));

        (($sPlugin = Phpfox_Plugin::get('library_phpfox_image_helper_display_start')) ? eval($sPlugin) : false);

        // Return cached image
        if (isset($aImages[$sHash])) {
            return $aImages[$sHash];
        }

        $isObject = false;

        if (isset($aParams['theme'])) {
            if (substr($aParams['theme'], 0, 5) == 'ajax/') {
                $type = str_replace(['ajax/', '.gif'], '', $aParams['theme']);
                $image = '';
                switch ($type) {
                    case 'large':
                        $image = '<i class="fa fa-spin fa-circle-o-notch _ajax_image_' . $type . '"></i>';
                        break;
                }

                return $image;
            }

            $sSrc = Phpfox::getLib('assets')->getAssetUrl(Phpfox_Template::instance()->getStyle('image', $aParams['theme']));

            if (isset($aParams['return_url']) && $aParams['return_url']) {
                return $sSrc;
            }

            return '<img src="' . $sSrc . '">';
        }

        if (isset($aParams['max_height']) && !is_numeric($aParams['max_height'])) {
            $aParams['max_height'] = Phpfox::getParam($aParams['max_height']);
        }

        if (isset($aParams['max_width']) && !is_numeric($aParams['max_width'])) {
            $aParams['max_width'] = Phpfox::getParam($aParams['max_width']);
        }

        // Check if this is a users profile image
        $bIsOnline = false;
        $sSuffix = '';
        $aUser = null;
        $bIsProfilePhoto = false;
        if (isset($aParams['user'])) {
            $aUser = $aParams['user'];
            $bIsProfilePhoto = true;
            if (isset($aParams['user_suffix'])) {
                $sSuffix = $aParams['user_suffix'];
            }
            // Create the local params
            $aParams['server_id'] = (isset($aUser['user_' . $sSuffix . 'server_id']) ? $aUser['user_' . $sSuffix . 'server_id'] : (isset($aUser[$sSuffix . 'server_id']) ? $aUser[$sSuffix . 'server_id'] : ''));
            $aParams['file'] = $aUser[$sSuffix . 'user_image'];
            $aParams['path'] = 'core.url_user';
            if (isset($aUser['' . $sSuffix . 'is_user_page'])) {
                $aParams['path'] = 'pages.url_image';
                $aParams['suffix'] = '_120_square';
            }
            $aParams['title'] = ($bIsOnline ?
                _p('full_name_is_online', array(
                    'full_name' => Phpfox::getLib('parse.output')->shorten(Phpfox::getLib('parse.output')->clean($aUser[$sSuffix . 'full_name']), 0)
                ))
                : Phpfox::getLib('parse.output')->shorten(Phpfox::getLib('parse.output')->clean($aUser[$sSuffix . 'full_name']), 0));

            // Create the users link
            if (!empty($aUser['profile_page_id']) && empty($aUser['user_name'])) {
                if (isset($aUser['item_type']) && $aUser['item_type'] == 1) {
                    $sLink = Phpfox_Url::instance()->makeUrl('groups', $aUser['profile_page_id']);
                } else {
                    $sLink = Phpfox_Url::instance()->makeUrl('pages', $aUser['profile_page_id']);
                }
            } else {
                $sLink = Phpfox_Url::instance()->makeUrl('profile', $aUser[$sSuffix . 'user_name']);
            }

            if (Phpfox::isUser()) {
                $aBlockedUserIds = $this->getBlockedUserIds();
                if (!empty($aBlockedUserIds) && in_array($aUser[$sSuffix . 'user_id'], $aBlockedUserIds)) {
                    unset($sLink);
                }
            }

            if (isset($aParams['href']) && filter_var($aParams['href'], FILTER_VALIDATE_URL)) {
                $sLink = $aParams['href'];
            }
            if ($this->preventProfilePhotoCache
                && isset($aUser[$sSuffix . 'user_id'])
                && $aUser[$sSuffix . 'user_id'] == Phpfox::getUserId()) {
                $aParams['time_stamp'] = true;
            }

            if (Phpfox::getCookie('recache_image')
                && isset($aUser[$sSuffix . 'user_id'])
                && $aUser[$sSuffix . 'user_id'] == Phpfox::getUserId()) {
                $aParams['time_stamp'] = true;
            }

            if (substr($aParams['file'], 0, 1) == '{') {
                $isObject = true;
                $aParams['org_file'] = $aParams['file'];
            }
        }

        if (empty($aParams['file'])) {

            if (isset($aParams['path'])
                && ($aParams['path'] == 'core.url_user' || $aParams['path'] == 'pages.url_image')
            ) {
                static $aGenders = null;

                if ($aGenders === null) {
                    $aGenders = array();
                    foreach ($this->globalGenders as $iKey => $aGender) {
                        if (isset($aGender[3])) {
                            $aGenders[$iKey] = $aGender[3];
                        }
                    }
                }

                $sGender = '';
                if (isset($aUser) && isset($aUser[$sSuffix . 'gender'])) {
                    if (isset($aGenders[$aUser[$sSuffix . 'gender']])) {
                        $sGender = $aGenders[$aUser[$sSuffix . 'gender']] . '_';
                    }
                }

                $sImageSuffix = '';
                if (!empty($aParams['suffix'])) {
                    $aParams['suffix'] = str_replace('_square', '', $aParams['suffix']);
                    $iWidth = ltrim($aParams['suffix'], '_');
                    if ((int)$iWidth >= 200) {
                    } else {
                        $sImageSuffix = $aParams['suffix'];
                    }
                }

                $sImageSize = $sImageSuffix;
                $name = Phpfox::getLib('parse.output')->clean(isset($aUser) ? $aUser[$sSuffix . 'full_name'] : (isset($aParams['title']) ? $aParams['title'] : ''));

                $parts = explode(' ', $name);
                $name = trim($name);
                $first = 'P';
                $last = 'F';
                if (strlen($name) >= 2) {
                    $first = mb_substr($name, 0, 1);
                    $last = mb_substr($name, 1, 1);
                    if (isset($parts[1])) {
                        $lastChar = trim($parts[1]);
                        if (!empty($lastChar)) {
                            $last = mb_substr($lastChar, 0, 1);
                        }
                    }
                } elseif (strlen($name) >= 1) {
                    $first = mb_substr($name, 0, 1);
                    $last = mb_substr($name, 0, 1);
                }
                if (isset($aParams['max_width'])) {
                    $sImageSize = '_' . $aParams['max_width'];
                }

                $ele = 'a';
                if (isset($aParams['no_link']) || !isset($sLink) || (isset($aUser) && isset($aUser[$sSuffix . 'no_link']))) {
                    $ele = 'span';
                }
                if (ctype_alnum($first . $last)) {
                    $namekey = preg_replace('/[^a-z]/m', 'p', strtolower($first . $last));
                } else {
                    $words = base64_encode($first . $last);
                    $words = strtolower(preg_replace("/[^a-z]+/", "", $words));
                    $namekey = mb_substr($words, 0, 2);
                    if (!ctype_alnum($namekey)) {
                        $namekey = 'no_utf8';
                    }
                }
                if (strlen($namekey) == 1) {
                    $namekey .= $namekey;
                } elseif (strlen($namekey) == 0) {
                    $namekey = 'pf';
                }


                if (isset($aParams['class']) && $aParams['class'] == 'js_hover_title') {
                    $aParams['title'] = Phpfox::getLib('parse.output')->shorten(Phpfox::getLib('parse.output')->clean($aParams['title']), 100, '...');
                }

                $image = '<' . $ele . '' . ($ele == 'a' ? ' href="' . $sLink . '"' : '') . ' class="no_image_user ' . (isset($aParams['class']) ? $aParams['class'] : '') . ' _size_' . $sImageSize . ' _gender_' . $sGender . ' _first_' . $namekey . '"' . (((empty($aParams['class']) || $aParams['class'] != 'js_hover_title') && isset($aParams['title'])) ? ' title="' . $aParams['title'] . '" ' : '') . ''. (isset($aUser) && empty($aUser['profile_page_id']) ? 'data-core-image-user="'. $aUser['user_id'] .'"' : '') . '>' . (isset($aParams['title']) ? '<span class="js_hover_info hidden">' . $aParams['title'] . '</span>' : '') . '<span>' . $first . $last . '</span></' . $ele . '>';

                return $image;
            } else {
                $sImageSize = '';
                if (isset($aParams['suffix'])) {
                    $sImageSize = $aParams['suffix'];
                }
                if (isset($aParams['max_width'])) {
                    $sImageSize = $aParams['max_width'];
                }

                if (!empty($aParams['default_photo'])) {
                    $file = flavor()->active->default_photo($aParams['default_photo'], true);
                    $image = '<img class="default_photo i_size_' . $sImageSize . '" src="' . $file . '" />';

                    return $image;
                }
                $ele = 'span';
                $image = '<' . $ele . ' class="no_image_item i_size_' . $sImageSize . '"><span></span></' . $ele . '>';

                return $image;
            }
        }

        if (isset($aParams['no_link']) && $aParams['no_link']) {
            unset($sLink);
        }

        $aParams['file'] = preg_replace('/%[^s]/', '%%', $aParams['file']);
        $sPathUrl = !empty($aParams['path']) ? Phpfox::getParam($aParams['path']) : '';
        $sSuffixNew = isset($aParams['suffix']) ? $aParams['suffix'] : '';
        if ($sSuffixNew == '_50') {
            $sSuffixNew = '_120';
        } elseif ($sSuffixNew == '_50_square') {
            $sSuffixNew = '_120_square';
        }
        $sSrc = $sPathUrl . sprintf($aParams['file'], $sSuffixNew);

        $sFallbackSuffix = isset($aParams['fallback_suffix']) ? $aParams['fallback_suffix'] : false;
        if (is_string($sFallbackSuffix)) {
            $sFallbackSrc = $sPathUrl . sprintf($aParams['file'], $sFallbackSuffix);
        }

        $sDirSrc = str_replace($this->srcPathBase . 'PF.Base/', PHPFOX_DIR, $sSrc);
        $sDirSrc = str_replace('/', PHPFOX_DS, $sDirSrc);
        if (isset($aParams['server_id']) && $aParams['server_id']) {
            $newPath = Phpfox_Cdn::instance()->getUrl($sSrc, $aParams['server_id']);
            if (!empty($newPath)) {
                $sSrc = $newPath;
            }
            if (isset($sFallbackSrc)) {
                $sFallbackSrc = Phpfox_Cdn::instance()->getUrl($sFallbackSrc, $aParams['server_id']);
            }
        }

        if (!file_exists($sDirSrc)) {
            $sDirSrc = str_replace('PF.Base' . PHPFOX_DS, '', $sDirSrc);
            if (file_exists($sDirSrc)) {
                $sSrc = str_replace('PF.Base/', '', $sSrc);
            } else {
                $aParams['file'] = '';
            }
        }

        // Use thickbox effect?
        if (isset($aParams['thickbox']) && !(isset($aParams['no_link']) && $aParams['no_link'])) {
            // Remove the image suffix (eg _thumb.jpg, _view.jpg, _75.jpg etc...).
            if (preg_match('/female\_noimage\.png/i', $sSrc)) {
                $sLink = $sSrc;
            } elseif (preg_match('/^(.*)_(.*)_square\.(.*)$/i', $sSrc, $aMatches)) {
                $sLink = $aMatches[1] . (isset($aParams['thickbox_suffix']) ? $aParams['thickbox_suffix'] : '') . '.' . $aMatches[3];
            } else {
                $sLink = preg_replace("/^(.*)_(.*)\.(.*)$/i",
                    "$1" . (isset($aParams['thickbox_suffix']) ? $aParams['thickbox_suffix'] : '') . ".$3", $sSrc);
            }
        }

        // Windows slash fix
        $sSrc = str_replace("\\", '/', $sSrc);
        $sSrc = str_replace("\"", '\'', $sSrc);

        if (isset($aParams['return_url']) && $aParams['return_url']) {
            return $sSrc . (isset($aParams['time_stamp']) ? '?t=' . uniqid() : '');
        }

        if (isset($aParams['title'])) {
            $aParams['title'] = Phpfox::getLib('parse.output')->clean(html_entity_decode($aParams['title'], null,
                'UTF-8'));
        }

        $sImage = '';
        $sAlt = '';
        if (isset($aParams['alt_phrase'])) {
            $sAlt = html_entity_decode(_p($aParams['alt_phrase']), null, 'UTF-8');
            unset($aParams['alt_phrase']);
        }

        if (isset($aParams['class']) && $aParams['class'] == 'js_hover_title') {
            $aParams['title'] = Phpfox::getLib('parse.output')->shorten($aParams['title'], 100, '...');
        }

        if (isset($sLink)) {
            $sImage .= '<a href="' . $sLink;
            if (isset($aParams['thickbox']) && isset($aParams['time_stamp'])) {
                $sImage .= '?t=' . uniqid();
            }
            $sImage .= '"';
            if (isset($aParams['title'])) {
                $sImage .= ' title="' . $aParams['title'] . '"';
            }
            if (isset($aParams['thickbox'])) {
                $sImage .= ' class="thickbox"';
            }
            if (isset($aParams['target'])) {
                $sImage .= ' target="' . $aParams['target'] . '"';
            }
            $sImage .= '>';
        }

        $bDefer = true;
        if (defined('PHPFOX_AJAX_CALL_PROCESS') && PHPFOX_AJAX_CALL_PROCESS && !$isObject) {
            $bDefer = false;
        }

        $size = (isset($aParams['suffix']) ? $aParams['suffix'] : '');
        if (isset($aParams['max_width'])) {
            $size = $aParams['max_width'];
        }

        $aParams['class'] = ' _image_' . $size . ' ' . ($isObject ? 'image_object' : 'image_deferred') . ' ' . (isset($aParams['class']) ? ' ' . $aParams['class'] : '');

        $sImage .= ($bIsProfilePhoto) ? '<div class="img-wrapper"' . (isset($aUser) && empty($aUser['profile_page_id']) ? 'data-core-image-user="'. $aUser['user_id'] .'"' : '') . '><img' : '<img';
        if ($bDefer == true) {
            if ($isObject) {
                $object = json_decode($aParams['org_file'], true);
                $sSrc = array_values($object)[0];
                $sImage .= ' data-object="' . array_keys($object)[0] . '" ';
            }
            if (!empty($aParams['no_lazy'])) {
                $sImage .= ' src="' . $sSrc . (isset($aParams['time_stamp']) ? '?t=' . uniqid() : '') . '" ';
            } else {
                $sImage .= ' data-src="' . $sSrc . (isset($aParams['time_stamp']) ? '?t=' . uniqid() : '') . '" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" ';
            }
        } else {
            $sImage .= ' src="' . $sSrc . (isset($aParams['time_stamp']) ? '?t=' . uniqid() : '') . '" ';
        }

        if (isset($aParams['title'])) {
            $sImage .= ' alt="' . $aParams['title'] . '" ';
        } else {
            $sImage .= ' alt="' . $sAlt . '" ';
        }

        if (isset($aParams['js_hover_title'])) {
            $sImage .= ' class="js_hover_title" ';
            unset($aParams['js_hover_title']);
        }

        if (isset($aParams['force_max'])) {
            $iHeight = $aParams['max_height'];
            $iWidth = $aParams['max_width'];
        }

        if (!empty($iHeight)) {
            $sImage .= 'height="' . $iHeight . '" ';
        }
        if (!empty($iWidth)) {
            $sImage .= 'width="' . $iWidth . '" ';
        }

        if (isset($sFallbackSrc)) {
            $aParams['data-fallback'] = $sFallbackSrc;
        }

        unset($aParams['server_id'],
            $aParams['force_max'],
            $aParams['org_file'],
            $aParams['src'],
            $aParams['max_height'],
            $aParams['max_width'],
            $aParams['href'],
            $aParams['user_name'],
            $aParams['file'],
            $aParams['suffix'],
            $aParams['path'],
            $aParams['thickbox'],
            $aParams['no_default'],
            $aParams['full_name'],
            $aParams['user_id'],
            $aParams['time_stamp'],
            $aParams['user'],
            $aParams['title'],
            $aParams['theme'],
            $aParams['default'],
            $aParams['user_suffix'],
            $aParams['target'],
            $aParams['alt'],
            $aParams['fallback_suffix']
        );

        foreach ($aParams as $sKey => $sValue) {
            $sImage .= ' ' . $sKey . '="' . str_replace('"', '\"', $sValue) . '" ';
        }

        $sImage .= ($bIsProfilePhoto) ? '/></div>' : '/>';
        $sImage .= (isset($sLink) ? '</a>' : '');

        (($sPlugin = Phpfox_Plugin::get('library_phpfox_image_helper_display_end')) ? eval($sPlugin) : false);

        $aImages[$sHash] = $sImage;

        return $sImage;
    }

    public function checkRemoteFileExists($url)
    {
        $id = 'url' . md5($url);

        // caching result to reduce
        return get_from_cache($id, function () use ($url) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                // don't download content
                curl_setopt($ch, CURLOPT_NOBODY, 1);
                curl_setopt($ch, CURLOPT_FAILONERROR, 1);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 2);

                return curl_exec($ch) !== false ? $url : 'null;';
            }) == $url;
    }

    /**
     * Runs a check on two variables if they are equal, less then or greater then
     *
     * @param string $a Variable 1 to check against variable 2
     * @param string $b Variable 2 to check against variable 1
     * @return int Returns an INT based on the output
     */
    private function _cmp($a, $b)
    {
        if ($a == $b) {
            return 0;
        }

        return ($a < $b) ? -1 : 1;
    }

    private $blockedUserIds;

    /**
     * @return mixed
     */
    private function getBlockedUserIds()
    {
        if ($this->blockedUserIds === null) {
            $this->blockedUserIds = Phpfox::getService('user.block')->get(null, true);
        }

        return $this->blockedUserIds;
    }
}