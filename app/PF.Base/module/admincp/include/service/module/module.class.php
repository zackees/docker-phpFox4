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
 * @package          Module_Admincp
 * @version          $Id: module.class.php 5840 2013-05-09 06:14:35Z phpFox LLC $
 */
class Admincp_Service_Module_Module extends Phpfox_Service
{


    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('module');
    }
    
    /**
     * @return array
     */
    public function getAdminMenu()
    {
        $sCacheId = $this->cache()->set('module_menu_' . Phpfox::getUserId());

        if (false === ($aRows = $this->cache()->get($sCacheId))) {
            $aPrivacyCache = [];
            $aRows = $this->database()->select('*')
                ->from(Phpfox::getT('admincp_privacy'))
                ->order('time_stamp DESC')
                ->execute('getSlaveRows');
            foreach ($aRows as $aRow) {
                foreach ((array)json_decode($aRow['user_group'], true) as $iGroup) {
                    $aPrivacyCache[ $iGroup ][ str_replace('admincp.', '', $aRow['url']) ] = ($aRow['wildcard'] ? true : false);
                }
            }

            $aRows = $this->database()->select('m.module_id, m.is_menu, m.menu')
                ->from($this->_sTable, 'm')
                ->where('m.is_active = 1 AND m.is_menu = 1')
                ->order('m.module_id ASC')
                ->execute('getSlaveRows');

            $oFilter = Phpfox::getLib('parse.input');

            foreach ($aRows as $iKey => $aRow) {
                if (!$aRow['is_menu']) {
                    unset($aRows[ $iKey ]);
                }

                if ($aRow['menu']) {
                    $aRows[ $iKey ]['menu'] = unserialize($aRow['menu']);

                    foreach ($aRows[ $iKey ]['menu'] as $iSubKey => $aSubMenu) {
                        if (isset($aPrivacyCache[ Phpfox::getUserBy('user_group_id') ][ implode('.', $aSubMenu['url']) ])) {
                            if ($aPrivacyCache[ Phpfox::getUserBy('user_group_id') ][ implode('.', $aSubMenu['url']) ]) {
                                unset($aRows[ $iKey ]);

                                break;
                            }

                            unset($aRows[ $iKey ]['menu'][ $iSubKey ]);

                            continue;
                        }

                        $aRows[ $iKey ]['menu'][ $iSubKey ]['url'] = implode('/', $aSubMenu['url']);
                    }
                }

                $aRows[ $iKey ]['url_name'] = $oFilter->cleanTitle($aRow['module_id']);

                if (file_exists(PHPFOX_DIR_MODULE . $aRow['module_id'] . PHPFOX_DS . 'static' . PHPFOX_DS . 'module.png')) {
                    $aRows[ $iKey ]['module_image'] = Phpfox::getParam('core.path') . 'module/' . $aRow['module_id'] . PHPFOX_DS . 'static' . PHPFOX_DS . 'module.png';
                }
            }

            $this->cache()->save($sCacheId, $aRows);
            Phpfox::getLib('cache')->group('module', $sCacheId);
        }

        foreach ($aRows as $iKey => $aRow) {
            if (isset($aRow['module_id']) && !Phpfox::isModule($aRow['module_id'])) {
                unset($aRows[ $iKey ]);
            }
        }

        return $aRows;
    }
    
    /**
     * @return array
     */
    public function getModules()
    {
        return Phpfox_Module::instance()->getModules();
    }
    
    /**
     * @param bool $bUninstalled
     *
     * @return array
     */
    public function get($bUninstalled = false)
    {
        $aProductCache = [];
        $aProducts = $this->database()->select('product_id, title')
            ->from(Phpfox::getT('product'))
            ->execute('getSlaveRows');
        foreach ($aProducts as $aProduct) {
            $aProductCache[ $aProduct['product_id'] ] = $aProduct['title'];
        }
        $this->database()->select('m_sub.module_id, COUNT(s.setting_id) AS total_setting')
            ->from($this->_sTable, 'm_sub')
            ->leftJoin(Phpfox::getT('setting'), 's', 's.module_id = m_sub.module_id')
            ->group('m_sub.module_id')
            ->union();

        $aRows = $this->database()->select('m.*, p.title AS product_title, m_sub.total_setting')
            ->unionFrom('m_sub')
            ->join($this->_sTable, 'm', 'm.module_id = m_sub.module_id')
            ->leftJoin(Phpfox::getT('product'), 'p', 'p.product_id = m.product_id')
            ->order('m.module_id ASC')
            ->execute('getSlaveRows');

        $aModules = [];
        $aCache = [];
        foreach ($aRows as $aRow) {
            $aCache[ $aRow['module_id'] ] = true;
            $aModules[ ($aRow['is_core'] ? 'core' : '3rdparty') ][] = $aRow;
        }

        if ($bUninstalled === true) {
            $aNotInstalled = [];
            $hDir = opendir(PHPFOX_DIR_MODULE);
            while ($sModule = readdir($hDir)) {
                if ($sModule == '.' || $sModule == '..') {
                    continue;
                }

                if (!file_exists(PHPFOX_DIR_MODULE . $sModule . PHPFOX_DS . 'install' . PHPFOX_DS . 'phpfox.xml.php')) {
                    continue;
                }

                if (isset($aCache[ $sModule ])) {
                    continue;
                }

                $aModuleDetails = Phpfox::getLib('xml.parser')->parse(file_get_contents(PHPFOX_DIR_MODULE . $sModule . PHPFOX_DS . 'install' . PHPFOX_DS . 'phpfox.xml.php'));
                if (isset($aModuleDetails['data'])) {
                    $aModuleDetails['data']['is_not_installed'] = true;
                    $aModuleDetails['data']['product_title'] = (isset($aProductCache[ $aModuleDetails['data']['product_id'] ]) ? $aProductCache[ $aModuleDetails['data']['product_id'] ] : $aModuleDetails['data']['product_id']);

                    $aNotInstalled[] = $aModuleDetails['data'];
                }
            }
            closedir($hDir);

            if (count($aNotInstalled)) {
                foreach ($aNotInstalled as $aModule) {
                    $aModules['3rdparty'][] = $aModule;
                }
            }
        }

        return $aModules;
    }
    
    /**
     * @param array $iId
     *
     * @return array
     */
    public function getForEdit($iId)
    {
        return $this->database()->select('m.*')
            ->from($this->_sTable, 'm')
            ->where("m.module_id = '" . $iId . "'")
            ->execute('getSlaveRow');
    }
    
    /**
     * Once removed from the ORDER it will still throw a fatal error because of the COUNT requires
     * the text to be in the order. Yet another Catch 22 for Microsoft. In any case created a PHP fix for the
     * time being, however need to do further testing on MSSQL as there must be a way!
     *
     * @return array
     */
    public function getModulesForSettings()
    {
        $this->database()->select('COUNT(setting.module_id) AS total_settings, ')->group('m_sub.module_id');

        $this->database()->select("m_sub.module_id")
            ->from($this->_sTable, "m_sub")
            ->leftJoin(Phpfox::getT('setting'), 'setting', [
                    'setting.module_id = m_sub.module_id AND setting.is_hidden = 0'
                ]
            )
            ->where('m_sub.is_active = 1')
            ->union();

        return $this->database()->select("m_sub.total_settings, m.module_id, language_phrase.text AS info")
            ->unionFrom('m_sub')
            ->join($this->_sTable, 'm', 'm.module_id = m_sub.module_id')
            ->leftJoin(Phpfox::getT('language_phrase'), 'language_phrase', [
                    "language_phrase.language_id = '" . Phpfox_Locale::instance()->getLangId() . "'",
                    'AND m.phrase_var_name = language_phrase.var_name'
                ]
            )
            ->order('m.module_id ASC')
            ->execute('getSlaveRows');
    }
    
    /**
     * @param string $sProductId
     * @param bool   $bIsCore
     *
     * @return bool
     */
    public function export($sProductId, $bIsCore = false)
    {
        $aRows = $this->database()->select('m.*, product.title AS product_name')
            ->from($this->_sTable, 'm')
            ->leftJoin(Phpfox::getT('product'), 'product', 'product.product_id = m.product_id')
            ->where("m.product_id = '" . $sProductId . "'" . ($bIsCore ? ' AND m.is_core = 1' : ''))
            ->execute('getSlaveRows');

        if (!isset($aRows[0]['product_name'])) {
            return Phpfox_Error::set(_p('product_does_not_have_any_settings'));
        }

        $oXmlBuilder = Phpfox::getLib('xml.builder');
        $oXmlBuilder->addGroup('modules');

        foreach ($aRows as $aRow) {
            $oXmlBuilder->addTag('module', $aRow['menu'], [
                    'module_id'       => $aRow['module_id'],
                    'is_core'         => $aRow['is_core'],
                    'is_active'       => $aRow['is_active'],
                    'is_menu'         => $aRow['is_menu'],
                    'phrase_var_name' => $aRow['phrase_var_name']
                ]
            );
        }
        $oXmlBuilder->closeGroup();

        return $oXmlBuilder->output();
    }
    
    /**
     * @param string    $sProductId
     * @param bool|true $bCore
     * @param null      $aModuleCache
     * @param array     $tempContents
     *
     * @return bool|array
     */
    public function exportForModules($sProductId = 'phpfox', $bCore = true, $aModuleCache = null, $tempContents = [])
    {
        $includePaths = [];

        $oDatabaseSupport = Phpfox::getLib('database.support');
        $oXmlBuilder = Phpfox::getLib('xml.builder');

        if (!defined('PHPFOX_XML_SKIP_STAMP')) {
            define('PHPFOX_XML_SKIP_STAMP', true);
        }

        $aRows = $this->database()->select('*')
            ->from($this->_sTable)
            ->execute('getSlaveRows');

        if (!count($aRows)) {
            return false;
        }
        foreach ($aRows as $aRow) {
            $sDir = PHPFOX_DIR_MODULE . $aRow['module_id'];

            $oXmlBuilder->addGroup('module');
            $oXmlBuilder->addGroup('data');

            foreach ($aRow as $sKey => $sValue) {
                $oXmlBuilder->addTag($sKey, $sValue);
            }

            if ($sProductId == 'phpfox') {
                $aWritableFiles = Phpfox_Module::instance()->init($aRow['module_id'], 'aInstallWritable');
                $oXmlBuilder->addTag('writable', ((count($aWritableFiles) && is_array($aWritableFiles)) ? serialize($aWritableFiles) : ''));
            }

            $oXmlBuilder->closeGroup();

            $iCnt = 0;
            $aModuleCallback = Phpfox::massCallback('exportModule', $sProductId, $aRow['module_id'], $bCore);
            foreach ($aModuleCallback as $sModuleCallback => $mReturn) {
                if ($sModuleCallback == 'language')
                {
                    continue;
                }
                if ($mReturn === true) {
                    $iCnt++;
                }
            }

            if ($bCore === true && is_dir($sDir)) {
                $aTables = Phpfox_Module::instance()->init($aRow['module_id'], 'aTables');
                if (count($aTables) > 0) {
                    $oXmlBuilder->addTag('tables', serialize($oDatabaseSupport->prepareSchema($aTables)));
                }

                $sInstallFile = $sDir . PHPFOX_DIR_MODULE_XML . PHPFOX_DS . 'version' . PHPFOX_DS . 'install' . PHPFOX_XML_SUFFIX;

                if (file_exists($sInstallFile)) {
                    $aInstallData = Phpfox::getLib('xml.parser')->parse(file_get_contents($sInstallFile));
                    if (isset($aInstallData['install'])) {
                        $oXmlBuilder->addTag('install', $aInstallData['install']);
                    }
                }
            }

            if ($bCore === false && $aModuleCache !== null && is_dir($sDir) && isset($aModuleCache[ $aRow['module_id'] ])) {
                if (file_exists($sDir . '/export.json')) {
                    $json = json_decode(file_get_contents($sDir . '/export.json'), true);
                    foreach ($json['paths'] as $path) {
                        $includePaths[] = dirname(PHPFOX_DIR) . '/' . $path;
                    }
                } else {
                    $includePaths[] = $sDir;
                }
            }


            $oXmlBuilder->closeGroup();

            if ($iCnt) {
                if ($bCore === true) {


                    $locale = 'PF.Base' . PHPFOX_DS . 'module' . PHPFOX_DS . $aRow['module_id'] . PHPFOX_DIR_MODULE_XML . PHPFOX_DS . 'phpfox' . PHPFOX_XML_SUFFIX;
                    $oXmlOuput = $oXmlBuilder->output();
                    $tempContents[$locale] = $oXmlOuput;
                    // write out to file

                    if(!empty($aModuleCache) && !empty($aModuleCache[$aRow['module_id']])) {

                        $filename = realpath(dirname(PHPFOX_DIR)) . '/' . $locale;
                        if (!is_writable($filename)) {
                            exit("can not write to file " . $filename);
                        }

                        file_put_contents($filename, $oXmlOuput);
                    }
                } else {
                    $locale = 'PF.Base'. PHPFOX_DS .'module' . PHPFOX_DS . $aRow['module_id'] . PHPFOX_DS . 'phpfox.xml';
                    $tempContents[ $locale ] = $oXmlBuilder->output();
                }
            } else {
                $oXmlBuilder->output();
            }
        }

        return [
            'paths'    => $includePaths,
            'contents' => $tempContents,
        ];
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
        if ($sPlugin = Phpfox_Plugin::get('admincp.service_module_module___call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}