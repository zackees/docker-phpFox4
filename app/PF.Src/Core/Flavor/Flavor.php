<?php

namespace Core\Flavor;

use Core\Installation\FileHelper;
use Core\Phrase;
use Phpfox;
use Phpfox_Image;

class Objects
{
    public $id;
    public $name;
    public $vars;
    public $path;
    public $url;
    public $icon = '';
    public $legacy = ['theme' => 'bootstrap', 'flavor' => 'bootstrap'];
    public $blocks = [];
    public $store_id;
    public $version;
    public $revision_path;

    public function __construct($path)
    {
        $this->path = $path;
        $this->revision_path = $path . 'revision' . PHPFOX_DS;
        $this->url = str_replace(PHPFOX_PARENT_DIR, '', $this->path);
        if (file_exists($this->path . 'theme.png')) {
            if (defined('PHPFOX_INSTALLER') || defined('PHPFOX_IS_UPGRADE')) {
                $this->icon = $this->url . 'theme.png';
            } else {
                $this->icon = Phpfox::getLib('assets')->getAssetUrl($this->url . 'theme.png');
            }

        }
        $this->legacy = (object)$this->legacy;
        $this->blocks = (object)$this->blocks;

        $json = json_decode(file_get_contents($path . 'theme.json'));
        foreach ($json as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function save($type, $values, $sub_type = '')
    {
        $dir = $this->path;

        // update revision before save
        $this->update_revision($type, $sub_type, true);
        $oAssets = Phpfox::getLib('assets');
        // Update new value
        switch ($type) {
            case 'settings':
                $file = $dir . 'theme.json';
                file_put_contents($file, $values);

                break;
            case 'icon':
                $file = $dir . 'theme.png';
                $url = $this->url . 'theme.png';
                list($file) = $this->save_upload_file($values, $file);

                if ($file) {
                    $oAssets->putAssetContents(str_replace(PHPFOX_PARENT_DIR, '', $file), file_get_contents($file));
                    return $oAssets->getAssetUrl($url);
                } else {
                    return false;
                }
            case 'content':
                $key = 'flavor/content/' . $this->id;
                $varname = 'flavor_content_' . md5($this->id);
                $values = isset($values['content']) ? $values['content'] : [];
                if (\Core\Lib::phrase()->isPhrase($varname)) {
                    foreach ($values as $lang => $value) {
                        Phpfox::getService('language.phrase.process')->updateVarName($lang, $varname, $value);
                    }
                } else {
                    Phpfox::getService('language.phrase.process')->add([
                        'var_name' => $varname,
                        'text' => $values
                    ]);
                }
                storage()->del($key);
                storage()->set($key, $varname);

                \Phpfox::getLib('cache')->remove($key);
                foreach (Phpfox::getService('language')->get() as $language) {
                    \Phpfox::getLib('cache')->remove($key . '/' . $language['language_id']);
                }
                break;
            case 'html':
                $path = $dir . 'html' . PHPFOX_DS . 'layout.html';
                file_put_contents($path, $values);

                break;
            case 'js':
                $path = $dir . 'assets' . PHPFOX_DS . 'autoload.js';
                file_put_contents($path, $values);
                Phpfox::getLib('assets')->putAssetContents(str_replace(PHPFOX_PARENT_DIR, '', $path), $values);
                break;
            case 'banners':
                $dir = $dir . 'assets' . PHPFOX_DS . 'banners' . PHPFOX_DS;
                $hash = preg_replace('/[@\$%&\/:*?\"\'<>|~`#^+={}\[\]]/', '_', $values['name']);
                $path = $dir . $hash;
                if (!is_dir($dir)) {
                    mkdir($dir);
                }
                list ($path) = $this->save_upload_file($values, $path, 'jpg');
                if ($path) {
                    $oAssets->putAssetContents(str_replace(PHPFOX_PARENT_DIR, '', $path), file_get_contents($path));
                    return $oAssets->getAssetUrlWithFilename($path);
                } else {
                    return false;
                }
            case 'logos':
                $dir = $dir . 'assets' . PHPFOX_DS . $sub_type . PHPFOX_DS;
                $ext = \Phpfox_File::instance()->getFileExt($values['name']);
                $hash = md5(uniqid()) . '.' . $ext;
                $id = 'flavor/' . $sub_type . '/' . $this->id;

                $path = $dir . $hash;
                if (!is_dir($dir)) {
                    mkdir($dir);
                }
                list ($path) = $this->save_upload_file($values, $path, 'jpg', $ext);

                if ($path) {
                    $oAssets->putAssetContents(str_replace(PHPFOX_PARENT_DIR, '', $path), file_get_contents($path));
                    storage()->del($id);
                    storage()->set($id, $hash);
                } else {
                    return false;
                }
                break;
            case 'default_photo':
                $dir = $dir . 'assets' . PHPFOX_DS . 'defaults' . PHPFOX_DS;
                $ext = \Phpfox_File::instance()->getFileExt($values['name']);
                $name = $sub_type . '.' . $ext;
                $id = 'flavor/defaults/' . $this->id;

                $path = $dir . $name;
                if (!is_dir($dir)) {
                    mkdir($dir);
                }
                list ($path, $ext) = $this->save_upload_file($values, $path, 'jpg', $ext);

                if ($path) {
                    //In case ext changed
                    $name = $sub_type . '.' . $ext;
                    $data = (storage()->get($id)) ? json_decode(json_encode(storage()->get($id)->value), true) : [];
                    if (isset($data[$sub_type]) && $data[$sub_type] != $name && file_exists($dir . $data[$sub_type])) {
                        //unlink old file if ext changed
                        unlink($dir . $data[$sub_type]);
                        Phpfox::getLib('storage')->get($oAssets->getAssetStorageId())->remove(str_replace(PHPFOX_PARENT_DIR, '', $dir . $data[$sub_type]));
                    }
                    $data[$sub_type] = $name;

                    $oAssets->putAssetContents(str_replace(PHPFOX_PARENT_DIR, '', $path), file_get_contents($path));

                    storage()->del($id);
                    storage()->set($id, $data);
                } else {
                    return false;
                }
                break;
            case 'remove_default':
                $dir = $dir . 'assets' . PHPFOX_DS . 'defaults' . PHPFOX_DS;
                $id = 'flavor/defaults/' . $this->id;

                $data = (storage()->get($id)) ? json_decode(json_encode(storage()->get($id)->value), true) : [];
                if (isset($data[$sub_type])) {
                    $path = $dir . $data[$sub_type];
                    if (file_exists($path)) {
                        unlink($path);
                        unset($data[$sub_type]);
                    }
                    Phpfox::getLib('storage')->get($oAssets->getAssetStorageId())->remove(str_replace(PHPFOX_PARENT_DIR, '', $path));
                }
                storage()->del($id);
                storage()->set($id, $data);
                break;
            case 'css':
                $less_file = $dir . 'assets' . PHPFOX_DS . 'autoload.less';
                $css_file = $dir . 'assets' . PHPFOX_DS . 'autoload.css';

                file_put_contents($less_file, $values);

                $lessc = new \lessc();
                $lessc->addImportDir($dir . 'assets' . PHPFOX_DS);
                $lessc->compileFile($less_file, $css_file);
                Phpfox::getLib('assets')->putAssetContents(str_replace(PHPFOX_PARENT_DIR, '', $css_file), file_get_contents($css_file));
                break;
            case 'design':
                $id = 'flavor/variables/' . $this->id;
                $theme_suffix = request()->get('theme_suffix');
                if ($theme_suffix) {
                    $values['skin'] = $theme_suffix;
                }
                storage()->del($id);
                storage()->set($id, $values);

                $needRebuild = false;
                flavor()->update_variables($dir, $values, $needRebuild);

                if ($needRebuild) {
                    try {
                        flavor()->rebuild_bootstrap(true);
                        Phpfox::getLib('cache')->remove();
                    } catch (\Exception $ex) {
                    }
                }
                break;
        }
        return true;
    }

    public function save_upload_file($temp_file, $destination, $toExt = 'png', $ext = null)
    {
        if ($ext === null) {
            $ext = \Phpfox_File::instance()->getFileExt($temp_file['name']);
        }
        $imageObj = Phpfox_Image::instance();
        $support = ['jpg', 'jpeg', 'gif', 'png'];
        $isSupportNextGen = $imageObj->isSupportNextGenImg();
        if (!in_array($ext, $support) && (!$isSupportNextGen || !$imageObj->isNextGenImage($ext))) {
            $supportNextGent = $imageObj->getNextGenImgString(['WEBP', 'JP2', 'TIFF', 'XBM']);
            $supportExt = strtoupper(implode(', ', $support)) . ($supportNextGent ? (', ' . $supportNextGent) : '');
            \Phpfox_Error::set(_p('not_a_valid_file_extension_we_only_allow_ext', ['ext' => $supportExt]));
            return [false, false];
        }
        if ($isSupportNextGen && $imageObj->isNextGenImage($ext)) {
            try {
                $nextGenImage = new \Imagick($temp_file['tmp_name']);
                $nextGenImage->setImageFormat($toExt);
                $nextGenImage->setImageCompressionQuality(90);
                $formItem = PHPFOX_DIR_FILE . 'static' . PHPFOX_DS . md5($temp_file['tmp_name'] . PHPFOX_TIME) . '.' . $toExt;
                $nextGenImage->writeImage($formItem);
                $destination = str_replace('.' . $ext, '.' . $toExt, $destination);
                $ext = $toExt;
                @copy($formItem, $destination);
                @unlink($formItem);
            } catch (\ImagickException $e) {
                \Phpfox_Error::set(_p('opps_something_went_wrong'));
                return [false, false];
            }
        } else {
            move_uploaded_file($temp_file['tmp_name'], $destination);
        }
        return [$destination, $ext];
    }

    public function update_revision($type, $sub_type, $save_revision = false)
    {
        // make dir for revision data
        if ($save_revision) {
            $source_dir = $this->path;
            $dest_dir = $this->revision_path;
            if (!is_dir($dest_dir)) {
                mkdir($dest_dir);
                file_put_contents($dest_dir . '.htaccess', 'Options -Indexes');
            }

            // assets dir
            if (!is_dir($dest_dir . 'assets' . PHPFOX_DS)) {
                mkdir($dest_dir . 'assets' . PHPFOX_DS);
            }

            // logos dir
            if ($sub_type == 'logos' && !is_dir($dest_dir . 'assets' . PHPFOX_DS . 'logos' . PHPFOX_DS)) {
                mkdir($dest_dir . 'assets' . PHPFOX_DS . 'logos' . PHPFOX_DS);
            }

            // favicons dir
            if ($sub_type == 'favicons' && !is_dir($dest_dir . 'assets' . PHPFOX_DS . 'favicons' . PHPFOX_DS)) {
                mkdir($dest_dir . 'assets' . PHPFOX_DS . 'favicons' . PHPFOX_DS);
            }

            // banners dir
            if (in_array($type, ['banners', 'delete_banner']) && !is_dir($dest_dir . 'assets' . PHPFOX_DS . 'banners' . PHPFOX_DS)) {
                mkdir($dest_dir . 'assets' . PHPFOX_DS . 'banners' . PHPFOX_DS);
            }

            // defaults dir
            if (in_array($type, ['default_photo', 'remove_default']) && !is_dir($dest_dir . 'assets' . PHPFOX_DS . 'defaults' . PHPFOX_DS)) {
                mkdir($dest_dir . 'assets' . PHPFOX_DS . 'defaults' . PHPFOX_DS);
            }

            // html dir
            if ($type == 'html' && !is_dir($dest_dir . 'html' . PHPFOX_DS)) {
                mkdir($dest_dir . 'html' . PHPFOX_DS);
            }
        } else {
            $source_dir = $this->revision_path;
            $dest_dir = $this->path;
        }

        switch ($type) {
            case 'logos':
                $key = 'flavor/' . $sub_type . '/' . $this->id;
                $file_name = storage()->get($key)->value;
                $source_path = $source_dir . 'assets' . PHPFOX_DS . $sub_type . PHPFOX_DS;
                $dest_path = $dest_dir . 'assets' . PHPFOX_DS . $sub_type . PHPFOX_DS;

                // remove old sub type (logo/favicon)
                foreach (scandir($dest_path) as $file) {
                    if (preg_match('/^(.*)\.([jpg|png|gif|jpeg]+)$/i', $file)) {
                        unlink($dest_path . $file);
                    }
                }
                if ($save_revision) {
                    $source_file = $source_path . $file_name;
                    $dest_file = $dest_path . $file_name;
                    if (file_exists($source_file)) {
                        copy($source_file, $dest_file);
                    }
                } else {
                    if (is_dir($source_path)) {
                        // update new sub type (logo/favicon)
                        foreach (scandir($source_path) as $file) {
                            if (preg_match('/^(.*)\.([jpg|png|gif|jpeg]+)$/i', $file)) {
                                $source_file = $source_path . $file;
                                $dest_file = $dest_path . $file;
                                if (file_exists($source_file)) {
                                    copy($source_file, $dest_file);
                                    unlink($source_file);
                                }
                                storage()->del($key);
                                storage()->set($key, $file);
                                break;
                            }
                        }
                        @rmdir($source_path);
                        \Phpfox::getLib('cache')->remove($key);
                    }
                }
                break;
            case 'content':
                $key = 'flavor/content/' . $this->id;
                $key_revision = $key . '/revision';
                if ($save_revision) {
                    storage()->del($key_revision);
                    storage()->set($key_revision, storage()->get($key)->value);
                } else {
                    storage()->del($key);
                    storage()->set($key, storage()->get($key_revision)->value);
                    storage()->del($key_revision);
                    \Phpfox::getLib('cache')->remove($key);
                }
                break;
            case 'banners':
            case 'delete_banner':
                $source_path = $source_dir . 'assets' . PHPFOX_DS . 'banners' . PHPFOX_DS;
                $dest_path = $dest_dir . 'assets' . PHPFOX_DS . 'banners' . PHPFOX_DS;
                if (is_dir($source_path)) {
                    // remove old banners
                    foreach (scandir($dest_path) as $file) {
                        if (preg_match('/^(.*)\.([jpg|png|gif|jpeg]+)$/i', $file)) {
                            unlink($dest_path . $file);
                        }
                    }
                    // update new banners
                    foreach (scandir($source_path) as $file) {
                        if (preg_match('/^(.*)\.([jpg|png|gif|jpeg]+)$/i', $file)) {
                            copy($source_path . $file, $dest_path . $file);
                            if (!$save_revision) {
                                unlink($source_path . $file);
                            }
                        }
                    }
                    if (!$save_revision) {
                        @rmdir($source_path);
                    }
                }
                break;
            case 'default_photo':
            case 'remove_default':
                $key = 'flavor/defaults/' . $this->id;
                $key_revision = $key . '/revision';
                if ($save_revision) {
                    storage()->del($key_revision);
                    storage()->set($key_revision, storage()->get($key)->value);
                } else {
                    storage()->del($key);
                    storage()->set($key, storage()->get($key_revision)->value);
                    storage()->del($key_revision);
                    \Phpfox::getLib('cache')->remove($key);
                }

                $source_path = $source_dir . 'assets' . PHPFOX_DS . 'defaults' . PHPFOX_DS;
                $dest_path = $dest_dir . 'assets' . PHPFOX_DS . 'defaults' . PHPFOX_DS;
                if (is_dir($source_path)) {
                    // remove old defaults
                    foreach (scandir($dest_path) as $file) {
                        if (preg_match('/^(.*)\.([jpg|png|gif|jpeg]+)$/i', $file)) {
                            unlink($dest_path . $file);
                        }
                    }

                    // update new defaults
                    foreach (scandir($source_path) as $file) {
                        if (preg_match('/^(.*)\.([jpg|png|gif|jpeg]+)$/i', $file)) {
                            copy($source_path . $file, $dest_path . $file);
                            if (!$save_revision) {
                                unlink($source_path . $file);
                            }
                        }
                    }
                    if (!$save_revision) {
                        @rmdir($source_path);
                    }
                }
                break;
            case 'design':
                $key = 'flavor/variables/' . $this->id;
                $key_revision = $key . '/revision';
                if ($save_revision) {
                    $values = (storage()->get($key)) ? json_decode(json_encode(storage()->get($key)->value), true) : ['skin' => 'default'];
                    if (isset($values['skin'])) {
                        $source_file = $source_dir . 'assets' . PHPFOX_DS . 'variables.less';
                        $dest_file = $dest_dir . 'assets' . PHPFOX_DS . 'variables.less';
                        if (file_exists($source_file)) {
                            copy($source_file, $dest_file);
                        }
                    }
                    storage()->del($key_revision);
                    storage()->set($key_revision, $values);
                } else {
                    $values = (storage()->get($key_revision)) ? json_decode(json_encode(storage()->get($key_revision)->value), true) : [];
                    storage()->del($key_revision);
                    if ($values) {
                        $needRebuild = false;
                        if (isset($values['skin'])) {
                            $source_file = $source_dir . 'assets' . PHPFOX_DS . 'variables.less';
                            $dest_file = $dest_dir . 'assets' . PHPFOX_DS . 'variables.less';
                            if (file_exists($source_file)) {
                                copy($source_file, $dest_file);
                                unlink($source_file);
                            }
                            $needRebuild = true;
                            flavor()->update_variables($dest_dir, $values, $needRebuild, true, true);
                        } else {
                            flavor()->update_variables($dest_dir, $values, $needRebuild);
                        }
                        storage()->del($key);
                        storage()->set($key, $values);

                        if ($needRebuild) {
                            try {
                                flavor()->rebuild_bootstrap(true);
                                \Phpfox::getLib('cache')->remove();
                            } catch (\Exception $ex) {
                            }
                        }
                    }
                }
                break;
            case 'css':
                $source_file = $source_dir . 'assets' . PHPFOX_DS . 'autoload.less';
                $dest_file = $dest_dir . 'assets' . PHPFOX_DS . 'autoload.less';
                if (file_exists($source_file)) {
                    copy($source_file, $dest_file);
                    if (!$save_revision) {
                        unlink($source_file);
                        $css_file = $dest_dir . 'assets' . PHPFOX_DS . 'autoload.css';
                        $lessc = new \lessc();
                        $lessc->addImportDir($dest_dir . 'assets' . PHPFOX_DS);
                        $lessc->compileFile($dest_file, $css_file);
                    }
                }

                break;
            case 'js':
                $source_file = $source_dir . 'assets' . PHPFOX_DS . 'autoload.js';
                $dest_file = $dest_dir . 'assets' . PHPFOX_DS . 'autoload.js';
                if (file_exists($source_file)) {
                    copy($source_file, $dest_file);
                    if (!$save_revision) {
                        unlink($source_file);
                    }
                }
                break;
            case 'html':
                $source_file = $source_dir . 'html' . PHPFOX_DS . 'layout.html';
                $dest_file = $dest_dir . 'html' . PHPFOX_DS . 'layout.html';
                if (file_exists($source_file)) {
                    copy($source_file, $dest_file);
                    if (!$save_revision) {
                        unlink($source_file);
                    }
                }
                break;
        }
    }

    public function html($exist = false)
    {
        return file_get_contents($this->html_path($exist));
    }

    public function html_path($exist = false)
    {
        $file = $this->path . 'html/layout.html';
        if (!file_exists($file)) {
            if ($exist) {
                $o = $file;
            }

            $file = str_replace($this->id . '/html/', 'bootstrap/html/', $file);

            if ($exist) {
                if (!is_dir($this->path . 'html/')) {
                    mkdir($this->path . 'html/');
                }
                copy($file, $o);
                $file = $o;
            }
        }

        return $file;
    }

    public function css()
    {
        $file = $this->path . 'assets/autoload.less';
        if (!file_exists($file)) {
            $file = $this->path . 'assets/autoload.css';
        }

        return file_get_contents($file);
    }

    public function json()
    {
        $file = $this->path . 'theme.json';

        return file_get_contents($file);
    }

    public function has_js()
    {
        return (file_exists($this->path . 'assets' . PHPFOX_DS . 'autoload.js') ? true : false);
    }

    public function has_logo_revision()
    {
        return (is_dir($this->revision_path . 'assets' . PHPFOX_DS . 'logos') ? true : false);
    }

    public function has_favicon_revision()
    {
        $favicon_re_path = $this->revision_path . 'assets' . PHPFOX_DS . 'favicons';
        return is_dir($favicon_re_path) ? true : false;
    }

    public function has_banner_revision()
    {
        return (is_dir($this->revision_path . 'assets' . PHPFOX_DS . 'banners') ? true : false);
    }

    public function has_content_revision()
    {
        $key = 'flavor/content/' . $this->id . '/revision';
        return (storage()->get($key)) ? true : false;
    }

    public function has_defaults_revision()
    {
        return (is_dir($this->revision_path . 'assets' . PHPFOX_DS . 'defaults') ? true : false);
    }

    public function has_js_revision()
    {
        return (file_exists($this->revision_path . 'assets' . PHPFOX_DS . 'autoload.js') ? true : false);
    }

    public function has_css_revision()
    {
        return (file_exists($this->revision_path . 'assets' . PHPFOX_DS . 'autoload.less') ? true : false);
    }

    public function has_html_revision()
    {
        return (file_exists($this->revision_path . 'html' . PHPFOX_DS . 'layout.html') ? true : false);
    }

    public function has_design_revision()
    {
        $key = 'flavor/variables/' . $this->id . '/revision';
        return (storage()->get($key)) ? true : false;
    }

    public function has_pre_version()
    {
        return $this->has_html_revision() || $this->has_css_revision() || $this->has_defaults_revision() || $this->has_banner_revision() ||
            $this->has_content_revision() || $this->has_favicon_revision() || $this->has_logo_revision() || $this->has_design_revision() ||
            $this->has_js_revision();
    }

    public function js()
    {
        $file = $this->path . 'assets/autoload.js';

        return file_get_contents($file);
    }

    private $logoCache = [];

    public function logo_url($type = 'logos', $bSkipCache = false)
    {
        /* Reduce cache re-call by cache in memory of current request */
        if (isset($this->logoCache[$type]) && !$bSkipCache) {
            return $this->logoCache[$type];
        }

        $logo = get_from_cache('flavor/' . $type . '/' . $this->id, function () use ($type) {
            $logo = $this->logo($type);
            if (!$logo) {
                return '';
            }

            $logo = Phpfox::getLib('assets')->getAssetUrlWithFilename($logo);

            //This is url, if on window, We have to fix the link
            return str_replace(PHPFOX_DS, '/', $logo);
        }, $bSkipCache ? 0.0001 : 0);

        if (request()->get('force-flavor')) {
            $logo = $logo . '?v=' . uniqid();
        }

        $this->logoCache[$type] = $logo;

        return $logo;
    }

    public function logo($type = 'logos')
    {
        $id = 'flavor/' . $type . '/' . $this->id;
        $dir = $this->path . 'assets' . PHPFOX_DS . $type . PHPFOX_DS;

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }


        $logo = storage()->get($id);
        if (isset($logo->value)) {
            $file = $dir . $logo->value;
            if (file_exists($file)) {
                return $file;
            }
        }
        if ($type == 'logos') {
            foreach (['logo.png', 'logo.jpg'] as $value) {
                if (file_exists($file = $dir . $value)) {
                    storage()->set($id, $value);
                    return $file;
                }
            }
            if (file_exists($file = PHPFOX_ROOT . 'PF.Base/theme/frontend/default/style/default/image/layout/phpfox_bootstraptemplate_logo.png')) {
                if (@copy($file, $dir . ($value = 'logo_default.png'))) {
                    storage()->set($id, $value);
                    return $dir . $value;
                }
            }
        }
        return null;
    }

    public function default_photo($type = null, $bUrl = false)
    {
        if (!$type) {
            return false;
        }
        $id = 'flavor/defaults/' . $this->id;
        $dir = $this->path . 'assets' . PHPFOX_DS . 'defaults' . PHPFOX_DS;
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        $url_dir = $this->url . 'assets' . PHPFOX_DS . 'defaults' . PHPFOX_DS;
        $photos = storage()->get($id);
        $return = null;
        if (isset($photos->value) && is_array($defaults = json_decode(json_encode($photos->value), true)) && isset($defaults[$type])) {
            $file = $url_dir . $defaults[$type];
            if (file_exists($file)) {
                $return = $file;
            }
        }

        if ($bUrl && $return) {
            if (request()->get('force-flavor')) {
                return Phpfox::getLib('assets')->getAssetUrl($return, uniqid(), false);
            } else {
                return Phpfox::getLib('assets')->getAssetUrl($return);
            }
        }
        return $return;
    }

    public function favicon()
    {
        return $this->logo('favicons');
    }

    public function favicon_url($bSkipCache = false)
    {
        return $this->logo_url('favicons', $bSkipCache);
    }

    public function banners()
    {
        $banners = [];
        $dir = $this->path . 'assets' . PHPFOX_DS . 'banners' . PHPFOX_DS;
        $oAssets = Phpfox::getLib('assets');
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        foreach (scandir($dir) as $file) {
            if (preg_match('/^(.*)\.([jpg|png|gif|jpeg]+)$/i', $file)) {
                $banners[] = $oAssets->getAssetUrlWithFilename($dir . $file);
            }
        }

        return $banners;
    }

    public function content($translate = true)
    {
        return get_from_cache('flavor/content/' . $this->id . ($translate ? ('/' . Phpfox::getLanguageId()) : ''), function () use ($translate) {
            $content = storage()->get('flavor/content/' . $this->id);
            if (isset($content->value)) {
                return !$translate ? $content->value : _p($content->value);
            }
            return '';
        });
    }

    public function export()
    {
        $dir = $this->path;

        // build checksum
        $fileHelper = new FileHelper();
        $fileHelper->createChecksum($dir, [$dir], ['phrase.json', 'theme.json', 'flavor/bootstrap.css', 'flavor/root.less']);

        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
            \RecursiveIteratorIterator::CATCH_GET_CHILD
        );
        $zip_file = PHPFOX_DIR_FILE . 'static' . PHPFOX_DS . 'theme-' . $this->id . '.zip';
        $Zip = new \ZipArchive();
        $Zip->open($zip_file, \ZipArchive::CREATE);

        $paths = [];
        foreach ($iter as $path => $dir) {
            if ($dir instanceof \SplFileInfo) {
                if ($dir->isFile() && strpos($path, $this->path . 'flavor') === false && strpos($path, '.git') === false && basename($dir) != '.DS_Store') {
                    if (preg_match('/^(.*)\.(json)$/i', $path)) {
                        $content = file_get_contents($path);
                        $paths[str_replace($this->path, '', $path)] = $content;
                    } else {
                        $paths['files_path'][] = str_replace($this->path, '', $path);
                        $Zip->addFile($dir->getPathName(), str_replace($this->path, '', $path));
                    }
                } else if ($dir->isDir() && strpos($path, $this->path . 'flavor') === false && strpos($path, '.git') === false) {
                    $Zip->addEmptyDir(str_replace($this->path, '', $path));
                }
            }
        }
        $paths = json_encode($paths, JSON_PRETTY_PRINT);

        $json_file = PHPFOX_DIR_FILE . 'static' . PHPFOX_DS . 'theme-' . $this->id . '.json';

        file_put_contents($json_file, $paths);

        $Zip->addFile($json_file, 'theme-' . $this->id . '.json');
        $Zip->close();

        unlink($json_file);

        \Phpfox_File::instance()->forceDownload($zip_file, 'phpfox-theme-' . $this->id . '.zip');
    }

    public function revert()
    {
        $dir = $this->path;
        $bootstrap = json_decode(file_get_contents(PHPFOX_DIR_SITE . '/Apps/core-flavors/flavors/bootstrap.json'));
        foreach ($bootstrap as $file => $content) {
            if (preg_match('/^(.*)\.(gif|jpg|jpeg|png)$/i', $file)) {
                $content = base64_decode($content);
            }

            file_put_contents($dir . ltrim($file, '/'), $content);
        }

        $json = json_decode(file_get_contents($dir . 'theme.json'));
        $json->id = strtolower($this->id);
        $json->name = $this->name;
        file_put_contents($dir . 'theme.json', json_encode($json, JSON_PRETTY_PRINT));

        return true;
    }

    public function revert_pre_version($values)
    {
        if (!empty($values['types'])) {
            foreach ($values['types'] as $type => $value) {
                $sub_type = '';
                if ($type == 'logo') {
                    $type = 'logos';
                    $sub_type = 'logos';
                }
                if ($type == 'favicon') {
                    $type = 'logos';
                    $sub_type = 'favicons';
                }
                $this->update_revision($type, $sub_type);
            }
        }
    }

    public function delete()
    {
        $dirs = [];
        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
            \RecursiveIteratorIterator::CATCH_GET_CHILD
        );
        foreach ($iter as $path => $dir) {
            if ($dir instanceof \SplFileInfo && $dir->isDir()) {
                $dirs[] = $path;
            }
        }

        $files = \Phpfox_File::instance()->getAllFiles($this->path, true);
        foreach ($files as $file) {
            unlink($file);
        }

        foreach ($dirs as $dir) {
            @rmdir($dir);
        }

        @rmdir($this->path);

        $id = 'flavor' . PHPFOX_DS . 'defaults' . PHPFOX_DS . $this->id;
        storage()->del($id);

        return true;
    }

    public function has_less()
    {
        return (file_exists($this->path . 'assets/variables.less') ? true : false);
    }

    public function getVars($less_input)
    {
        $variables = [];

        $lines = file($less_input);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            if (substr($line, 0, 1) == '@') {
                $parts = array_map('trim', explode(':', $line));
                $var = str_replace('@', '', $parts[0]);
                if (!isset($parts[1])) {
                    continue;
                }
                $value = trim(explode(';', $parts[1])[0]);
                $variables[$var] = $value;
            }
        }

        return $variables;
    }
    public function design()
    {
        $theme_suffix = !empty($_REQUEST['theme_suffix']) ? $_REQUEST['theme_suffix'] : '';
        $less_input = $this->path . 'assets' . PHPFOX_DS . 'variables.less';

        $variables = $this->getVars($less_input);
        $cur_skin = isset($variables['skin']) ? $variables['skin'] : 'default';
        $hasCustom = false;
        $cur_skin_file = $this->path . 'assets' . PHPFOX_DS . 'skins' . PHPFOX_DS . $cur_skin . '.less';
        if (file_exists($cur_skin_file)) {
            $cur_skin_variables = $this->getVars($cur_skin_file);
            foreach ($cur_skin_variables as $key => $val) {
                if (isset($variables[$key]) && $val != $variables[$key]) {
                    $hasCustom = true;
                    break;
                }
            }
        }

        $params = ['id' => $this->id, 'type' => 'design'];
        if ($theme_suffix) {
            $theme_suffix_file = $this->path . 'assets' . PHPFOX_DS . 'skins' . PHPFOX_DS . $theme_suffix . '.less';
            if (file_exists($theme_suffix_file)) {
                $theme_suffix_variables = $this->getVars($theme_suffix_file);
                $variables = array_merge($variables, $theme_suffix_variables);
            }
            $params['theme_suffix'] = $theme_suffix;
        }

        if (is_string($this->vars) && substr($this->vars, 0, 1) == '@') {
            $bootstrap = json_decode(file_get_contents(PHPFOX_DIR_SITE . 'flavors/bootstrap/theme.json'));
            $this->vars = $bootstrap->vars;
        }

        $html = '';
        if (isset($this->vars) && count((array)$this->vars)) {
            $html .= '<form class="ajax_post" method="post" action="' . url()->make('/flavors/manage', $params) . '">';
            foreach ($this->vars as $key => $value) {
                $html .= '<div class="fm_setting">';
                $html .= '<div class="fm_title">' . $value->title . '</div>';

                if (!isset($value->type)) {
                    $value->type = 'text';
                }

                if (in_array($value->attr, ['max-width', 'width', 'min-width'])) {
                    $value->type = 'size';
                }

                $class = "";
                if ($value->attr == 'mass_class' && !empty($value->mass_class)) {
                    $rules = json_encode(['mass_class' => $value->mass_class]);
                    $class = $value->mass_class;
                } else {
                    $rules = json_encode(['rule' => $value->id . '{' . $value->attr . ':[VALUE];}']);
                }

                if (isset($variables[$key])) {
                    $value->value = $variables[$key];
                }

                if (!isset($value->value)) {
                    $value->value = '';
                }

                if ($value->type == 'boolean') {
                    $value->value = $value->value === 'false' ? false : true;
                }

                switch ($value->type) {
                    case "skin":
                        $html .= '<div class="fm_skin_wrapper"><div class="dropdown fm_skin_container' . $class . '" data-key= "' . $key . '" type="text" name="var[' . $key . ']" value="' . $value->value . '" data-rules=\'' . $rules . '\' ><a role="button" data-toggle="dropdown" href="#" ><div class="item-title-skin js_flavor_skin_title_change"></div><i class="fa fa-caret-down"></i></a><ul class="dropdown-menu">';
                        foreach ($value->options as $option) {
                            $params = ['id' => $this->id, 'type' => 'design'];
                            if ($cur_skin != $option->value) {
                                $params['theme_suffix'] = $option->value;
                            }
                            $html .= strtr('<li><a class="edit_for_theme :selected" href="#" data-url=":url" value=":key" label=":label" ><span class="item-square-skin-color" style="background: :primary;"></span>:label</a></li>', [
                                ':key'      => $option->value,
                                ':label'    => isset($option->label) ? $option->label : $option->value,
                                ':primary'  => isset($option->primary) ? $option->primary : '',
                                ':selected' => ($option->value == $value->value) ? 'skin_selected' : '',
                                ':url'      => url('/flavors/manage', $params),
                            ]);
                        }
                        $html .= '</ul></div>';
                        if (($cur_skin == $theme_suffix || !$theme_suffix) && $hasCustom) {
                            $html .= strtr('<a style="" class="item-reset-skin edit_for_theme" href="#" data-url=":url" value=":key" label=":label">:reset_label</a>', [
                                ':key'         => $cur_skin,
                                ':reset_label' => _p('reset'),
                                ':url'         => url('/flavors/manage', ['id' => $this->id, 'type' => 'design', 'theme_suffix' => $cur_skin]),
                            ]);
                        }
                        $html .= '</div>';
                        break;
                    case 'size':
                        $html .= '<input autocomplete="off" class="' . $class . '"  data-key= "' . $key . '" type="text" name="var[' . $key . ']" value="' . $value->value . '" data-rules=\'' . $rules . '\'>';
                        break;
                    case 'boolean':
                        $html .= '<input type="hidden" name="var[' . $key . ']" value="false">'
                            . '<input type="checkbox" class="' . $class . '"  data-key= "' . $key . '" name="var[' . $key . ']" value="true" data-rules=\'' . $rules . '\' ' . (!empty($value->value) ? 'checked="1"' : '') . '>';
                        break;
                    default:
                        $html .= '<input class="_colorpicker" class="' . $class . '"  data-key= "' . $key . '" data-old="' . $value->value . '" autocomplete="off" type="text" name="var[' . $key . ']" value="' . $value->value . '" data-rules=\''
                            . $rules . '\'>';
                        $html .= '<div class="_colorpicker_holder"></div>';
                        break;
                }

                $html .= '</div>';
            }
            $html .= '<div class="fm_submit"><span>' . _p('publish') . '</span></div>';
            $html .= '</form>';
        }

        return $html;
    }

}

class Flavor
{
    /**
     * @var Object
     */
    public $active;

    private static $_active = null;

    protected static $instance;

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        if (self::$_active === null) {
            if (!is_dir(PHPFOX_DIR_SITE . 'flavors/')) {
                mkdir(PHPFOX_DIR_SITE . 'flavors/');
            }
            $default_dir = PHPFOX_DIR_SITE . 'flavors/bootstrap/';
            if (!is_dir($default_dir) and is_writable(PHPFOX_DIR_SITE . 'flavors')) {
                $this->make([
                    'name' => 'bootstrap',
                ]);
            }

            $flavor = 'bootstrap';
            $default = storage()->get('flavor/default');
            if (isset($default->value)) {
                $flavor = $default->value;
            }

            $cookie = \Phpfox::getCookie('flavors_id');
            if ($cookie) {
                $flavor = $cookie;
            }

            self::$_active = $this->get($flavor);
            if (self::$_active === false) {
                self::$_active = $this->get('bootstrap');
            }
        }

        $this->active = self::$_active;
    }

    public function checkPermissionBeforeRebuild()
    {
        return is_writable($this->active->path);
    }

    public function set_active($flavor)
    {
        self::$_active = $this->get($flavor);
        $this->active = self::$_active;
    }

    public function make($val, $file = null)
    {
        $bIsCloned = true;
        $path = '';
        if ($file !== null) {
            $path = PHPFOX_DIR_FILE . 'static' . PHPFOX_DS . uniqid() . '/';
            $zip_file = $path . 'theme.zip';
            $json = null;

            mkdir($path);
            if (isset($file['is_local'])) {
                copy($file['tmp_name'], $zip_file);
            } else {
                move_uploaded_file($file['tmp_name'], $zip_file);
            }
            // remove tmp file
            unlink($file['tmp_name']);

            $zip = new \ZipArchive();
            $zip->open($zip_file);
            $zip->extractTo($path);
            $zip->close();

            // remove zip file
            unlink($zip_file);

            foreach (scandir($path) as $file) {
                if (substr($file, -5) == '.json') {
                    $json = json_decode(file_get_contents($path . $file), true);
                    break;
                }
            }

            if ($json === null) {
                error('JSON file missing for this theme.');
            }

            $j = json_decode($json['theme.json']);
            $bIsCloned = false;
            $themeId = $j->id;
            $themeName = isset($j->name) ? $j->name : $themeId;
        } else {
            if (empty($val['name'])) {
                error(_p('Provide a name for your theme.'));
            }
            $themeName = $val['name'];
            $themeId = preg_replace('/\s+/', '', strtolower($themeName)); // convert theme name to them id
        }
        // validate theme id
        if (!preg_match('/^[^\W_]+$/', $themeId)) {
            error(_p('Alphanumeric characters only for the theme name.'));
        }

        $dir = PHPFOX_DIR_SITE . 'flavors' . PHPFOX_DS . strtolower($themeId) . PHPFOX_DS;
        if (is_dir($dir)) {
            if ($bIsCloned) {
                error(_p('Theme already exists.'));
            }
        } else {
            mkdir($dir);
            if ($bIsCloned) {
                mkdir($dir . 'assets/');
                mkdir($dir . 'html/');
                mkdir($dir . 'flavor/');
            }
        }

        if (isset($val['clone'])) {
            if ($val['clone'] == '__blank') {
                file_put_contents($dir . 'assets/autoload.css', '');
                $bootstrap = json_decode(file_get_contents(PHPFOX_DIR_SITE . '/Apps/core-flavors/flavors/bootstrap.json'), true);
                file_put_contents($dir . 'html/layout.html', $bootstrap['/html/layout.html']);
                file_put_contents($dir . 'theme.json', json_encode(['id' => $themeId, 'name' => $themeName], JSON_PRETTY_PRINT));
            } else {
                $files = [];
                $object = $this->get($val['clone']);
                $dirs = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($object->path, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST,
                    \RecursiveIteratorIterator::CATCH_GET_CHILD
                );
                foreach ($dirs as $file_name => $o) {
                    if ($o instanceof \SplFileInfo) {
                        if ($o->isDir()) {
                            $file_name = str_replace('flavors/' . $val['clone'] . '/', 'flavors/' . $themeId . '/', $file_name);
                            \Phpfox_File::instance()->mkdir($file_name, true);
                        } else {
                            $new_path = str_replace('flavors/' . $val['clone'] . '/', 'flavors/' . $themeId . '/', $file_name);
                            $files[$file_name] = $new_path;
                        }
                    }
                }

                foreach ($files as $copy => $file) {
                    copy($copy, $file);
                }
            }

        } else {
            if (isset($json)) {
                $dirs = [];
                foreach ($json as $file => $content) {
                    $parts = explode('/', $file);
                    unset($parts[count($parts) - 1]);
                    $this_dir = implode('/', $parts);
                    if (empty($this_dir)) {
                        continue;
                    }
                    $dirs[$this_dir . '/'] = $this_dir . '/';
                }

                foreach ($dirs as $new_dir) {
                    $this_dir = $dir . $new_dir;
                    if (!is_dir($this_dir)) {
                        \Phpfox_File::instance()->mkdir($this_dir, true);
                    }
                }

                // check and update variable when upgrade
                $needRebuild = false;
                if (file_exists($dir . 'assets' . PHPFOX_DS . 'variables.less')) { // is upgrade
                    $this->restore_variables($path, $dir, $needRebuild);
                }

                foreach ($json as $file => $content) {
                    if ($file == 'files_path') {
                        foreach ($content as $sPath) {
                            $to_filename = $dir . $sPath;

                            if (!is_dir($sDir = dirname($to_filename))) {
                                mkdir($sDir, 0777, 1);
                                chmod($sDir, 0777);
                            }

                            if (!@copy($path . $sPath, $to_filename)) {
                                throw new \RuntimeException(sprintf('Can not copy from "%s" to "%s"', $path . $sPath, $to_filename));
                            }
                        }
                    } else {
                        if (preg_match('/^(.*)\.(gif|jpg|jpeg|png)$/i', $file)) {
                            $content = base64_decode($content);
                        }

                        file_put_contents($dir . $file, $content);
                    }
                }

                // rebuild when have change brand color
                if ($needRebuild) {
                    try {
                        $this->rebuild_bootstrap(true);
                        \Phpfox::getLib('cache')->remove();
                    } catch (\Exception $ex) {
                    }
                }

                // check and import phrase
                if (isset($json['phrase.json'])) {
                    $phrases = json_decode($json['phrase.json'], true);
                    (new Phrase())->addPhrase($phrases);
                }

                // remove static tmp folder
                \Phpfox_File::instance()->removeDirectory($path);

            } else {
                $bootstrap = json_decode(file_get_contents(PHPFOX_DIR_SITE . '/Apps/core-flavors/flavors/bootstrap.json'));
                foreach ($bootstrap as $file => $content) {
                    if (preg_match('/^(.*)\.(gif|jpg|jpeg|png)$/i', $file)) {
                        $content = base64_decode($content);
                    }

                    file_put_contents($dir . ltrim($file, '/'), $content);
                }
            }
        }

        if ($bIsCloned) {
            $json = json_decode(file_get_contents($dir . 'theme.json'));
            $json->id = $themeId;
            $json->name = $themeName;
            unset($json->store_id);
            file_put_contents($dir . 'theme.json', json_encode($json, JSON_PRETTY_PRINT));
        }

        //build default photos
        flavor()->build_default_photos($themeId);

        if (!defined('PHPFOX_INSTALLER')) {
            Phpfox::getLib('assets')->putAssetFilesForParentFolder($dir);
        }

        return $this->get($themeId);
    }

    public function get($flavor)
    {
        $dir = PHPFOX_DIR_SITE . 'flavors' . PHPFOX_DS;
        $path = $dir . $flavor . PHPFOX_DS;

        if (file_exists($path . 'theme.json')) {
            return new Objects($path);
        }

        return false;
    }

    /**
     * @return array
     */
    public function all()
    {
        $dir = PHPFOX_DIR_SITE . 'flavors';
        if (!is_dir($dir)) {
            error('Flavor folder does not exist.');
        }

        $flavors = [];
        foreach (scandir($dir) as $flavor) {
            if (($flavor = $this->get($flavor))) {
                $flavors[] = $flavor;
            }
        }

        return $flavors;
    }

    public function rebuild_bootstrap($less = false)
    {
        // unlimited time & memory when rebuild bootstrap core.
        if (function_exists('ini_set')) {
            ini_set('memory_limit', '-1');
            ini_set('max_execution_time', 3000);
        }
        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }

        $theme = new \Core\Theme('bootstrap');
        $theme->get()->delete();

        $theme = new \Core\Theme();
        $new_theme = $theme->make(['name' => 'Bootstrap'], null, false, 'bootstrap');
        db()->update(':theme', ['is_default' => 1], ['theme_id' => $new_theme->theme_id]);
        if ($less === true) {
            $theme = new \Core\Theme();
            $theme->get()->rebuild();

            if (Phpfox::isTechie()) {
                @copy(PHPFOX_DIR_SITE . 'flavors' . PHPFOX_DS . flavor()->active->id . PHPFOX_DS . 'flavor' . PHPFOX_DS . 'bootstrap.css',
                    PHPFOX_DIR . 'theme' . PHPFOX_DS . 'bootstrap' . PHPFOX_DS . 'flavor' . PHPFOX_DS . 'default.css');
            }
        }
    }

    public function rebuild_material($less = false)
    {
        // unlimited time & memory when rebuild bootstrap core.
        if (function_exists('ini_set')) {
            ini_set('memory_limit', '-1');
            ini_set('max_execution_time', 3000);
        }
        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }

        $theme = new \Core\Theme();
        $new_theme = $theme->make(['name' => 'Bootstrap'], null, false, 'bootstrap');
        db()->update(':theme', ['is_default' => 1], ['theme_id' => $new_theme->theme_id]);
        if ($less === true) {
            $theme = new \Core\Theme();
            $theme->get()->rebuild();

            if (Phpfox::isTechie()) {
                @copy(PHPFOX_DIR_SITE . 'flavors' . PHPFOX_DS . flavor()->active->id . PHPFOX_DS . 'flavor' . PHPFOX_DS . 'bootstrap.css',
                    PHPFOX_DIR . 'theme' . PHPFOX_DS . 'bootstrap' . PHPFOX_DS . 'flavor' . PHPFOX_DS . 'default.css');
            }
        }
    }

    public function build_default_photos($name = null)
    {
        if ($name === null) {
            $name = $this->active->id;
        }
        $path = PHPFOX_DIR_SITE . 'flavors' . PHPFOX_DS . $name . PHPFOX_DS . 'assets' . PHPFOX_DS . 'defaults' . PHPFOX_DS;
        if (!is_dir($path)) {
            return true;
        }
        $id = 'flavor' . PHPFOX_DS . 'defaults' . PHPFOX_DS . $name;
        $data = [];

        foreach (scandir($path) as $file) {
            $ext = \Phpfox_File::instance()->getFileExt($file);
            if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif'])) {
                continue;
            }
            $type = pathinfo($file)['filename'];
            $data[$type] = $file;
        }

        storage()->del($id);
        storage()->set($id, $data);
    }

    public function restore_variables($path, $dir, &$needRebuild = false)
    {
        $old_variable_file = $dir . 'assets' . PHPFOX_DS . 'variables.less';
        $lines = file($old_variable_file); // get old variables
        $values = []; // old values
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            if (substr($line, 0, 1) == '@') {
                $parts = array_map('trim', explode(':', $line));
                $var = str_replace('@', '', $parts[0]);
                if (!isset($parts[1])) {
                    continue;
                }
                $values[$var] = trim(explode(';', $parts[1])[0]);
            }
        }
        $this->update_variables($path, $values, $needRebuild);
    }

    public function update_variables($path, $values, &$needRebuild, $hasCustomBrand = false, $hasChange = false)
    {
        $brandVariables = ['brand-primary', 'brand-success', 'brand-info', 'brand-warning', 'brand-danger'];
        $assets_variable_file = $path . 'assets' . PHPFOX_DS . 'variables.less';
        if (!file_exists($assets_variable_file)) {
            $less = '';
            foreach ($values as $var => $value) {
                if (in_array($var, $brandVariables)) {
                    $hasCustomBrand = true;
                }
                $less .= "@{$var}: {$value};\n";
            }
            file_put_contents($assets_variable_file, $less);
            $hasChange = true;
        } else {
            $less = '';
            $lines = file($assets_variable_file);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                if (substr($line, 0, 1) == '@') {
                    $parts = array_map('trim', explode(':', $line));
                    $var = str_replace('@', '', $parts[0]);
                    if (!isset($parts[1])) {
                        continue;
                    }
                    $value = trim(explode(';', $parts[1])[0]);
                    if (isset($values[$var]) && $value != $values[$var]) {
                        if (in_array($var, $brandVariables)) {
                            $hasCustomBrand = true;
                        }
                        $hasChange = true;
                    }

                    if (isset($values[$var])) {
                        $value = $values[$var];
                    }

                    $less .= "@{$var}: {$value};\n";
                }
            }
            file_put_contents($assets_variable_file, $less);
        }

        // convert less to css for overwrite css
        if ($hasChange) {
            $lessc = new \lessc();
            $less_input = $path . 'assets/autoload.less';
            $css_output = $path . 'assets/autoload.css';
            if (!file_exists($less_input)) {
                file_put_contents($less_input, "\n@import \"variables\";\n\n@import \"../less/mt_includes/snap-section\";\n" . file_get_contents($css_output));
            }
            try {
                if ($compiled_css = $lessc->compileFile($less_input, $css_output)) {
                    Phpfox::getLib('assets')->putAssetContents(str_replace(PHPFOX_PARENT_DIR, '', $css_output), file_get_contents($css_output));
                }
            } catch (\Exception $e) {
            }
        }

        // update less variables
        if ($hasCustomBrand) {
            $less_variable_file = $path . 'less' . PHPFOX_DS . 'variables.less';
            if (file_exists($less_variable_file)) {
                $less = '';
                $lines = file($less_variable_file);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) {
                        $less .= $line . "\n";
                        continue;
                    }
                    if (substr($line, 0, 1) == '@') {
                        $parts = array_map('trim', explode(':', $line));
                        $var = str_replace('@', '', $parts[0]);
                        if (!isset($parts[1]) || strpos($parts[0], '@media') !== false || !in_array($var, $brandVariables)) {
                            $less .= $line . "\n";
                            continue;
                        }
                        $value = trim(explode(';', $parts[1])[0]);
                        if (isset($values[$var])) {
                            $value = $values[$var];
                        }
                        $less .= "@{$var}: {$value};\n";
                    } else {
                        $less .= $line . "\n";
                    }
                }
                file_put_contents($less_variable_file, $less);
                $needRebuild = true;
            }
        }
    }

    public function merge_from_material($themeId, $vals)
    {
        $id = 'material';
        $files = [];
        $object = $this->get($id);
        if (empty($object->path)) {
            return false;
        }
        $path = $object->path;
        $dirs = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
            \RecursiveIteratorIterator::CATCH_GET_CHILD
        );
        $cloneThemePath =  str_replace($id, $themeId, $path);
        $cloneThemeAssetPath = $cloneThemePath . 'assets' . PHPFOX_DS;
        $assetPath = $path . 'assets' . PHPFOX_DS;
        $includePath = $valuesVariable = [];
        $css_file = $less_file = $valuesJS = '';
        if (!empty($vals['merge_design'])) {
            $valuesVariable = $object->getVars($assetPath . 'variables.less');
            $includePath[] = $assetPath . 'variables.less';
        }
        if (!empty($vals['merge_css'])) {
            $includePath[] = $assetPath . 'autoload.css';
            $includePath[] = $assetPath . 'autoload.less';
            $less_file = $cloneThemeAssetPath . 'autoload.less';
            $css_file = $cloneThemeAssetPath . 'autoload.css';
        }
        if (!empty($vals['merge_js'])) {
            $includePath[] = $assetPath . 'autoload.js';
            $valuesJS = file_get_contents($assetPath . 'autoload.js');
        }
        if (!empty($vals['merge_html'])) {
            $includePath[] = $path . 'html' . PHPFOX_DS . 'layout.html';
        }

        foreach ($dirs as $file_name => $o) {
            if (!in_array($file_name, $includePath)
                && (strpos($file_name, $path . 'theme.json') !== false
                    || strpos($file_name, $path . 'theme.png') !== false
                    || strpos($file_name, $path . 'revision') !== false
                    || strpos($file_name, $path . 'flavor') !== false
                    || strpos($file_name, $path . 'assets') !== false
                    || strpos($file_name, $path . 'html' . PHPFOX_DS . 'layout.html') !== false)) {
                continue;
            }
            if ($o instanceof \SplFileInfo) {
                if ($o->isDir()) {
                    $file_name = str_replace('flavors/' . $id . '/', 'flavors/' . $themeId . '/', $file_name);
                    \Phpfox_File::instance()->mkdir($file_name, true);
                } else {
                    $new_path = str_replace('flavors/' . $id . '/', 'flavors/' . $themeId . '/', $file_name);
                    $files[$file_name] = $new_path;
                }
            }
        }
        foreach ($files as $copy => $file) {
            if ($copy == $assetPath . 'variables.less' && !empty($valuesVariable)) {
                flavor()->update_variables($cloneThemePath, $valuesVariable, $needRebuild, true, true);
            } else {
                copy($copy, $file);
                if ($copy == $assetPath . 'autoload.less' && $less_file && $css_file) {
                    $lessc = new \lessc();
                    $lessc->addImportDir($cloneThemeAssetPath);
                    $lessc->compileFile($less_file, $css_file);
                    Phpfox::getLib('assets')->putAssetContents(str_replace(PHPFOX_PARENT_DIR, '', $css_file), file_get_contents($css_file));
                } elseif ($copy == $assetPath . 'autoload.js') {
                    Phpfox::getLib('assets')->putAssetContents(str_replace(PHPFOX_PARENT_DIR, '', $cloneThemeAssetPath . 'autoload.js'), $valuesJS);
                }
            }
        }
        return true;
    }
}
