<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 *
 *
 * @copyright        [PHPFOX_COPYRIGHT]
 * @author           phpFox LLC
 * @package          Module_Language
 * @version          $Id: process.class.php 4961 2012-10-29 07:11:34Z phpFox LLC $
 */
class Language_Service_Process extends Phpfox_Service
{
    private $_aFile = [];

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('language');
    }

    /**
     * Import language pack
     * @return bool|string
     * @throws Exception
     */
    public function uploadPack()
    {
        $sMessage = null;
        $bAlreadyInstalled = false;

        if (empty($_FILES['file'])) {
            $sMessage = _p('upload_fail_please_try_again_later');
        } elseif ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            switch ($_FILES['file']['error']) {
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
                    $sMessage = _p('upload_fail_please_try_again_later');
                    break;
            }
        }

        if (!isset($sMessage)) {
            $oFile = Phpfox::getLib('file');
            $sClonedFolder = PHPFOX_DIR_FILE . 'static' . PHPFOX_DS . md5('package_' . PHPFOX_TIME . Phpfox::getUserId()) . PHPFOX_DS;

            if (!is_dir($sClonedFolder)) {
                @mkdir($sClonedFolder, 0777, true);
                @chmod($sClonedFolder, 0777);
            }

            if (!@move_uploaded_file($_FILES['file']['tmp_name'], $sClonedFolder . $_FILES['file']['name'])) {
                $sMessage = _p('upload_fail_please_try_again_later');
            } else {
                $sFilePath = $sClonedFolder . $_FILES['file']['name'];
                $oZip = new ZipArchive;
                if($oZip->open($sFilePath) != "true") {
                    $sMessage = _p("Could not open {$_FILES['file']['name']}");
                } else {
                    $oZip->extractTo($sClonedFolder);
                    $oZip->close();

                    $sLanguageFolder = $sClonedFolder . implode(PHPFOX_DS, [
                            'upload', 'include', 'xml', 'language'
                        ]) . PHPFOX_DS;

                    if (!is_dir($sLanguageFolder)) {
                        $sMessage = _p('invalid_package');
                    } else {
                        $sPhpfoxLanguageFolder = PHPFOX_DIR_XML . 'language' . PHPFOX_DS;
                        if (!is_dir($sPhpfoxLanguageFolder)) {
                            @mkdir($sPhpfoxLanguageFolder, 0777, true);
                            @chmod($sPhpfoxLanguageFolder, 0777);
                        }

                        $aScannedDirs = scandir($sLanguageFolder);
                        $bSuccess = false;
                        foreach ($aScannedDirs as $sScannedDir) {
                            if (!in_array($sScannedDir, ['.', '..'])) {
                                if (is_dir($sPhpfoxLanguageFolder . $sScannedDir)) {
                                    $oFile->removeDirectory($sPhpfoxLanguageFolder . $sScannedDir);
                                }
                                if ($bSuccess = @rename($sLanguageFolder . $sScannedDir, $sPhpfoxLanguageFolder . $sScannedDir)) {
                                    $bAlreadyInstalled = !!db()->select('language_id')
                                        ->from(':language')
                                        ->where([
                                            'language_id' => $sScannedDir
                                        ])->executeField(false);
                                    if ($bAlreadyInstalled) {
                                        $bAlreadyInstalled = $sPhpfoxLanguageFolder . $sScannedDir . PHPFOX_DS;
                                    }
                                }
                                break;
                            }
                        }

                        if (!$bSuccess) {
                            $sMessage = _p('Can not move Language folder');
                        }
                    }
                }
            }
            $oFile->removeDirectory($sClonedFolder);
        }

        if (isset($sMessage)) {
            return Phpfox_Error::set($sMessage);
        }

        return $bAlreadyInstalled ? $bAlreadyInstalled : true;
    }

    /**
     * @param array $aVals
     * @param null  $sProductName
     * @param bool  $bMissingOnly
     * @param bool  $bIsInstall
     *
     * @return bool
     */
    public function import($aVals, $sProductName = null, $bMissingOnly = false, $bIsInstall = false)
    {
        if ($bMissingOnly) {
            $aLang = Phpfox::getService('language')->getLanguageByName($aVals['settings']['title']);

            if (!isset($aLang['language_id'])) {
                return Phpfox_Error::set(_p('cannot_import'));
            }

            $aSql = [];
            foreach ($aVals['phrases']['phrase'] as $aValue) {
                $sPhrase = $aValue['module_id'] . '.' . $aValue['var_name'];
                $bPassed = true;
                if (!$bIsInstall && !Core\Lib::phrase()->isPhrase($sPhrase)) {
                    $bPassed = false;
                }

                if ($bPassed) {
                    $iModuleId = Phpfox_Module::instance()->getModuleId($aValue['module_id']);
                    $aSql[] = [
                        $aLang['language_id'],
                        $aValue['var_name'],
                        $aValue['value'],
                        $aValue['value'],
                        $aValue['added']
                    ];
                }
            }

            if ($aSql) {
                $this->database()->multiInsert(Phpfox::getT('language_phrase'), [
                    'language_id',
                    'var_name',
                    'text',
                    'text_default',
                    'added'
                ], $aSql);

                Phpfox::getLib('cache')->remove();
            }

            return true;
        } else {
            $this->add([
                    'title'         => $aVals['settings']['title'],
                    'user_select'   => $aVals['settings']['user_select'],
                    'language_code' => $aVals['settings']['language_code'],
                    'charset'       => $aVals['settings']['charset'],
                    'direction'     => $aVals['settings']['direction'],
                    'time_stamp'    => $aVals['settings']['time_stamp'],
                    'created'       => $aVals['settings']['created'],
                    'site'          => $aVals['settings']['site'],
                    'is_default'    => ($bIsInstall ? 1 : 0),
                    'is_master'     => ($bIsInstall ? 1 : 0)
                ]
            );

            $aSql = [];
            $iLength = 0;
            $iLanguageId = $aVals['settings']['language_code'];
            foreach ($aVals['phrases']['phrase'] as $aValue) {
                $aSql[] = [
                    $iLanguageId,
                    $aValue['var_name'],
                    $aValue['value'],
                    $aValue['value'],
                    $aValue['added']
                ];

                $iLength += strlen($aValue['value']);

                if ($iLength > 102400) {
                    $this->database()->multiInsert(Phpfox::getT('language_phrase'), [
                        'language_id',
                        'var_name',
                        'text',
                        'text_default',
                        'added'
                    ], $aSql);

                    $aSql = [];
                    $iLength = 0;
                }
            }

            if ($aSql) {
                $this->database()->multiInsert(Phpfox::getT('language_phrase'), [
                    'language_id',
                    'var_name',
                    'text',
                    'text_default',
                    'added'
                ], $aSql);
            }

            unset($aSql, $iLength);
            Phpfox::getLib('cache')->remove();
        }

        return true;
    }
    
    /**
     * Add a language package
     *
     * @param array $aVals
     *
     * @return bool|string
     */
    public function add($aVals)
    {
        $oFilter = Phpfox_Parse_Input::instance();

        $aCheck = [
            'parent_id'     => [
                'type'    => 'string:required',
                'message' => _p('select_a_language_package_to_clone')
            ],
            'title'         => [
                'type'    => 'string:required',
                'message' => _p('provide_a_name_for_your_language_package')
            ],
            'language_code' => [
                'type'    => 'string:required',
                'message' => _p('provide_an_abbreviation_code')
            ],
            'direction'     => [
                'type'    => 'string:required',
                'message' => _p('provide_the_text_direction')
            ],
            'user_select'   => [
                'type' => 'int:required'
            ],
            'created'       => [
                'type' => 'string'
            ],
            'site'          => [
                'type' => 'string'
            ]
        ];

        $aVals = $this->validator()->process($aCheck, $aVals);

        if (!Phpfox_Error::isPassed()) {
            return false;
        }

        if (!$this->_checkImage()) {
            return false;
        }

        $aOlds = $this->database()->select('title')
            ->from($this->_sTable)
            ->where("title LIKE '%" . $this->database()->escape($aVals['title']) . "%'")
            ->execute('getSlaveRows');

        $iTotal = 0;
        foreach ($aOlds as $aOld) {
            if (preg_replace("/(.*?)\([0-9]\)/i", "$1", $aOld['title']) === $aVals['title']) {
                $iTotal++;
            }
        }

        $iOldsId = $this->database()->select('language_id')
            ->from($this->_sTable)
            ->where("language_id=\"" . $this->database()->escape($aVals['language_code']) . "\"")
            ->executeField();

        if ($iOldsId) {
            return Phpfox_Error::set(_p("Language code is exist."));
        }

        $sLanguageId = $aVals['language_code'];

        if (!empty($aVals['site'])) {
            if ($this->validator()->check($aVals['site'], 'url')) {
                return Phpfox_Error::set(_p('not_a_valid_url'));
            }

            if (!preg_match('/(http|https):\/\/(.*?)/i', $aVals['site'])) {
                $aVals['site'] = 'http://' . $aVals['site'];
            }
        }

        $this->database()->insert($this->_sTable, [
                'language_id'   => $sLanguageId,
                'parent_id'     => $aVals['parent_id'],
                'title'         => $oFilter->convert($aVals['title']) . ($iTotal > 0 ? '(' . ($iTotal + 1) . ')' : ''),
                'user_select'   => (int)$aVals['user_select'],
                'language_code' => $aVals['language_code'],
                'charset'       => 'UTF-8',
                'direction'     => $aVals['direction'],
                'time_stamp'    => (isset($aVals['time_stamp']) ? (int)$aVals['time_stamp'] : PHPFOX_TIME),
                'created'       => (empty($aVals['created']) ? null : $oFilter->clean($aVals['created'])),
                'site'          => (empty($aVals['site']) ? null : $oFilter->clean($aVals['site'])),
                'is_default'    => 0,
                'is_master'     => 0
            ]
        );

        Phpfox::getLib('cache')->removeGroup('locale');

        $this->_uploadImage($sLanguageId);

        return $sLanguageId;
    }
    
    /**
     * @param string $sPack
     * @param string $sDir
     *
     * @return bool
     */
    public function installPackFromFolder($sPack, $sDir)
    {

        if (!is_dir($sDir)) {
            throw error(_p('not_a_valid_language_package_to_install'));
        }

        if (!file_exists($sDir . 'phpfox-language-import.xml')) {
            throw error(_p('not_a_valid_language_package_to_install_missing_the_xml_file'));
        }

        $aData = Phpfox::getLib('xml.parser')->parse($sDir . 'phpfox-language-import.xml', 'UTF-8');

        $aCheck = [
            'title' => [
                'type' => 'string:required',
                'message' => _p('provide_a_name_for_your_language_package')
            ],
            'language_code' => [
                'type' => 'string:required',
                'message' => _p('provide_an_abbreviation_code')
            ],
            'direction' => [
                'type' => 'string:required',
                'message' => _p('provide_the_text_direction')
            ],
            'user_select' => [
                'type' => 'int:required'
            ],
            'created' => [
                'type' => 'string'
            ],
            'site' => [
                'type' => 'string'
            ],
            'flag_id' => [
                'type' => 'string'
            ],
            'image' => [
                'type' => 'string'
            ],
            'charset' => [
                'type' => 'string'
            ],
            'is_default' => [
                'type' => 'int'
            ],
            'is_master' => [
                'type' => 'int'
            ],
            'version' => [
                'type' => 'string'
            ],
            'store_id' => [
                'type' => 'int'
            ]
        ];

        $aData['settings'] = $this->validator()->process($aCheck, $aData['settings']);

        if (!Phpfox_Error::isPassed()) {
            return false;
        }

        $aData['settings']['title'] = Phpfox_Parse_Input::instance()->convert($aData['settings']['title']);
        $aData['settings']['language_id'] = $sPack;
        $aData['settings']['time_stamp'] = PHPFOX_TIME;

        // convert base64 to image
        $sFlagDir = Phpfox::getParam('core.dir_pic') . 'flag' . PHPFOX_DS;
        if (!is_dir($sFlagDir)) {
            @mkdir($sFlagDir, 0777);
        }
        file_put_contents($sFlagDir . $sPack . '.' . $aData['settings']['flag_id'],
            base64_decode($aData['settings']['image']));
        unset($aData['settings']['image']);

        if (empty(Phpfox::getService('language')->get(['l.language_id' => $sPack]))) {
            $this->database()->insert(Phpfox::getT('language'), $aData['settings']);
        }
        else {
            unset($aData['settings']['time_stamp']);
            unset($aData['settings']['language_id']);
            $sCacheId = Phpfox::getLib('cache')->set('import_language_update');
            Phpfox::getLib('cache')->save($sCacheId, $aData['settings']);
        }

        return true;
    }
    
    /**
     * @param string $sLangId
     * @param array  $aVals
     *
     * @return bool
     */
    public function update($sLangId, $aVals)
    {
        $aCheck = [
            'title' => [
                'type' => 'string:required',
                'message' => _p('provide_a_name_for_your_language_package')
            ],
            'language_code' => [
                'type' => 'string:required',
                'message' => _p('provide_an_abbreviation_code')
            ],
            'direction' => [
                'type' => 'string:required',
                'message' => _p('provide_the_text_direction')
            ],
            'user_select' => [
                'type' => 'int:required'
            ],
            'created' => [
                'type' => 'string'
            ],
            'site' => [
                'type' => 'string'
            ],
            'version' => [
                'type' => 'string'
            ],
            'store_id' => [
                'type' => 'int'
            ]
        ];

        $aVals = $this->validator()->process($aCheck, $aVals);

        if (!Phpfox_Error::isPassed()) {
            return false;
        }

        if (!$this->_checkImage()) {
            return false;
        }

        if (!empty($aVals['site'])) {
            if ($this->validator()->check($aVals['site'], 'url')) {
                return Phpfox_Error::set(_p('not_a_valid_url'));
            }

            if (!preg_match('/(http|https):\/\/(.*?)/i', $aVals['site'])) {
                $aVals['site'] = 'http://' . $aVals['site'];
            }
        }

        $aVals['title'] = $this->preParse()->clean($aVals['title']);

        $this->database()->update(Phpfox::getT('language'), $aVals, 'language_id = \'' . $this->database()->escape($sLangId) . '\'');

        $this->_uploadImage($sLangId);

        Phpfox::getLib('cache')->removeGroup('locale');

        return true;
    }
    
    /**
     * @param int $iId
     *
     * @return bool
     */
    public function delete($iId)
    {
        $aLanguage = Phpfox::getService('language')->getLanguage($iId);

        $this->database()->delete($this->_sTable, "language_id = '" . $this->database()->escape($iId) . "'");
        $this->database()->delete(Phpfox::getT('language_phrase'), "language_id = '" . $this->database()->escape($iId) . "'");

        if (file_exists(Phpfox::getParam('core.dir_pic') . 'flag' . PHPFOX_DS . $iId . '.' . $aLanguage['flag_id'])) {
            unlink(Phpfox::getParam('core.dir_pic') . 'flag' . PHPFOX_DS . $iId . '.' . $aLanguage['flag_id']);
        }

        Phpfox::getLib('cache')->removeGroup('locale');

        return true;
    }
    
    /**
     * @param int $iId
     *
     * @return bool
     */
    public function setDefault($iId, $iType)
    {
        Phpfox::isAdmin(true);
        $isActive = (int) ($iType == '1' ? 1 : 0);

        $aUpdate = [
            'is_default' => $isActive,
        ];
        $iSettingVal = $iId;
        if ($isActive) {
            $this->database()->update($this->_sTable, ['is_default' => 0], 'is_default = 1');
            $aUpdate['is_active'] = 1; //Default language must be activated
        } else {
            //If disabled default, master language will be set as default
            $iSettingVal = $this->database()->select('language_id')->from($this->_sTable)->where('is_master = 1')->executeField();
            if ($iSettingVal == $iId) {
                return true;
            }
            $this->database()->update($this->_sTable, ['is_default' => 1], 'is_master = 1');
        }
        $this->database()->update($this->_sTable, $aUpdate, "language_id = '" . $this->database()->escape($iId) . "'");
        $this->database()->update(Phpfox::getT('setting'), ['value_actual' => $iSettingVal], 'module_id = \'core\' AND var_name = \'default_lang_id\'');

        Phpfox::getLib('cache')->removeGroup(['locale', 'setting']);
        return true;
    }
    
    /**
     * @param string $sLanguageId
     *
     * @return bool
     */
    public function useLanguage($sLanguageId)
    {
        if (Phpfox::isUser()) {
            $this->database()->update(Phpfox::getT('user'), ['language_id' => $sLanguageId], 'user_id = ' . Phpfox::getUserId());
        } else {
            Phpfox::getLib('session')->set('language_id', $sLanguageId);
        }

        return true;
    }
    
    /**
     * If a call is made to an unknown method attempt to connect
     * it to a specific plug-in with the same name thus allowing
     * plug-in developers the ability to extend classes.
     *
     * @param string $sMethod    is the name of the method
     * @param array  $aArguments is the array of arguments of being passed
     *
     * @return null
     */
    public function __call($sMethod, $aArguments)
    {
        /**
         * Check if such a plug-in exists and if it does call it.
         */
        if ($sPlugin = Phpfox_Plugin::get('language.service_process__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
    
    /**
     * @return bool
     */
    private function _checkImage()
    {
        if (!empty($_FILES['icon']['name'])) {
            if (!($this->_aFile = Phpfox_File::instance()->load('icon', ['jpg', 'gif', 'png']))) {
                return false;
            }
        }

        return true;
    }
    
    /**
     * @param string $sLanguageId
     */
    private function _uploadImage($sLanguageId)
    {
        if (!empty($this->_aFile['name'])) {
            $sFlagDir = Phpfox::getParam('core.dir_pic') . 'flag' . PHPFOX_DS;

            if (file_exists($sFlagDir . $sLanguageId . '.' . $this->_aFile['ext'])) {
                unlink($sFlagDir . $sLanguageId . '.' . $this->_aFile['ext']);
            }

            // check and create directory
            is_dir($sFlagDir) or Phpfox_File::instance()->mkdir($sFlagDir);

            if (Phpfox_File::instance()->upload('icon', $sFlagDir, $sLanguageId, false, 0644, false)) {
                $iServerId = Phpfox::getLib('cdn')->getServerId();
                $this->database()->update($this->_sTable, [
                    'flag_id' => $this->_aFile['ext'],
                    'server_id' => $iServerId ? $iServerId : 0
                ], 'language_id = \'' . $this->database()->escape($sLanguageId) . '\'');
            }
        }
    }

    /**
     * @param int $iId
     * @param int $iType
     * @return bool|resource
     */
    public function updateActivity($iId, $iType)
    {
        Phpfox::isAdmin(true);

        $isActive = (int) ($iType == '1' ? 1 : 0);
        $sId = $this->database()->escape($iId);
        $isDefault = $this->database()->select('language_id')->from($this->_sTable)->where('language_id = \'' . $sId . '\' AND (is_default = 1 OR is_master = 1)')->executeField();

        if (!empty($isDefault) && !$isActive) {
            return Phpfox_Error::set(_p('cannot_deactivate_default_language_package'));
        }
        $result = $this->database()->update($this->_sTable, array('is_active' => $isActive), 'language_id = \'' . $sId . '\'');

        $this->cache()->removeGroup(['locale', 'setting']);

        return $result;
    }
}