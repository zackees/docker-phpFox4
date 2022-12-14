<?php
/**
 * [PHPFOX_HEADER]
 */

use Core\Installation\FileHelper;

defined('PHPFOX') or exit('NO DICE!');

/**
 *
 *
 * @copyright        [PHPFOX_COPYRIGHT]
 * @author           phpFox LLC
 * @package          Module_Admincp
 * @version          $Id: product.class.php 1652 2010-06-16 08:25:59Z phpFox LLC $
 */
class Admincp_Service_Product_Product extends Phpfox_Service
{
    /**
     * @var string
     */
    protected $_sTable = '';

    private $_aProducts;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('product');

        $sCacheId = $this->cache()->set('product');

        if (false === ($this->_aProducts = $this->cache()->get($sCacheId))) {
            foreach ($this->_get() as $aRow) {
                $this->_aProducts[ $aRow['product_id'] ] = $aRow;
            }

            $this->cache()->save($sCacheId, $this->_aProducts);
            Phpfox::getLib('cache')->group('product', $sCacheId);
        }
    }
    
    /**
     * @param bool $bCache
     *
     * @return array|mixed
     */
    public function get($bCache = true)
    {
        return ($bCache ? $this->_aProducts : $this->_get());
    }
    
    /**
     * @param string $sProduct
     *
     * @return bool
     */
    public function isProduct($sProduct)
    {
        return (isset($this->_aProducts[ $sProduct ]) ? true : false);
    }
    
    /**
     * @param string $sName
     *
     * @return int
     */
    public function getId($sName)
    {
        return (isset($this->_aProducts[ $sName ]) ? $this->_aProducts[ $sName ]['product_id'] : 1);
    }
    
    /**
     * @param string $sProduct
     *
     * @return array
     */
    public function getForEdit($sProduct)
    {
        return $this->database()->select('product.*')
            ->from($this->_sTable, 'product')
            ->where("product_id = '" . $this->database()->escape($sProduct) . "'")
            ->execute('getSlaveRow');
    }
    
    /**
     * @param string $sProduct
     *
     * @return array|bool
     */
    public function export($sProduct)
    {
        define('PHPFOX_XML_SKIP_STAMP', true);

        $aRow = $this->database()->select('*')
            ->from($this->_sTable)
            ->where("product_id = '" . $this->database()->escape($sProduct) . "'")
            ->execute('getSlaveRow');

        if (!isset($aRow['product_id'])) {
            return false;
        }

	    $store_id = '';
	    $local = 'PF.Base'.PHPFOX_DS.'include'.PHPFOX_DS.'xml'.PHPFOX_DS . $aRow['product_id'] . '.xml';
	    $this_location = PHPFOX_DIR . 'include'.PHPFOX_DS.'xml'.PHPFOX_DS . $aRow['product_id'] . '.xml';
	    if (file_exists($this_location)) {
		    $xml = \Phpfox_Xml_Parser::instance()->parse($this_location);
		    if (isset($xml['data']['store_id'])) {
			    $store_id = $xml['data']['store_id'];
		    }
	    }

        $packageInformation = [
            'id'          => $aRow['product_id'],
            'type'        => 'product',
            'name'        => $aRow['title'],
            'description' => $aRow['description'],
            'version'     => $aRow['version'],
            'is_core'     => $aRow['is_core'] ? 1 : 0,
            'icon'        => $aRow['icon'],
            'vendor'        => $aRow['vendor'],
	        'store_id' => $store_id
        ];

        $oXmlBuilder = Phpfox::getLib('xml.builder');

        $oXmlBuilder->addGroup('product');
        $oXmlBuilder->addGroup('data');
	    $oXmlBuilder->addTag('store_id', $store_id);
        foreach ($aRow as $sKey => $sValue) {
            $oXmlBuilder->addTag($sKey, $sValue);
        }
        $oXmlBuilder->closeGroup();

        $aDependencies = $this->database()->select('type_id, check_id, dependency_start, dependency_end')
            ->from(Phpfox::getT('product_dependency'))
            ->where("product_id = '" . $this->database()->escape($sProduct) . "'")
            ->execute('getSlaveRows');
        if (count($aDependencies)) {
            $oXmlBuilder->addGroup('dependencies');
            foreach ($aDependencies as $aDependency) {
                $oXmlBuilder->addGroup('dependency');
                foreach ($aDependency as $sKey => $sValue) {
                    $oXmlBuilder->addTag($sKey, $sValue);
                }
                $oXmlBuilder->closeGroup();
            }
            $oXmlBuilder->closeGroup();
        }

        $aInstalls = $this->database()->select('version, install_code, uninstall_code')
            ->from(Phpfox::getT('product_install'))
            ->where("product_id = '" . $this->database()->escape($sProduct) . "'")
            ->order('version ASC')
            ->execute('getSlaveRows');
        if (count($aInstalls)) {
            $oXmlBuilder->addGroup('installs');
            foreach ($aInstalls as $aInstall) {
                $oXmlBuilder->addGroup('install');
                foreach ($aInstall as $sKey => $sValue) {
                    $oXmlBuilder->addTag($sKey, $sValue);
                }
                $oXmlBuilder->closeGroup();
            }
            $oXmlBuilder->closeGroup();
        }

        $aModules = $this->database()->select('*')
            ->from(Phpfox::getT('module'))
            ->where('product_id = \'' . $this->database()->escape($sProduct) . '\'')
            ->execute('getSlaveRows');
        if (count($aModules)) {
            $aModuleCache = [];
            $oXmlBuilder->addGroup('modules');
            foreach ($aModules as $aModule) {
                $oXmlBuilder->addTag('module_id', $aModule['module_id']);
                $aModuleCache[ $aModule['module_id'] ] = true;
                $this->_buildChecksum($aModule);
            }
            $oXmlBuilder->closeGroup();
        }

        $oXmlBuilder->closeGroup();

        $tempContents = [];
        $tempContents[ $local ] = $oXmlBuilder->output();

        $zipFile = PHPFOX_DIR_FILE . 'static'.PHPFOX_DS.'package'.PHPFOX_DS.'product-' . $aRow['product_id'] . '.zip';

        $result = Phpfox::getService('admincp.module')->exportForModules($sProduct, false, (isset($aModuleCache) ? $aModuleCache : null), $tempContents);

        $helper = new \Core\Installation\FileHelper();
        $helper->export($zipFile, $result['paths'], $result['contents'], $packageInformation);


        if ($sPlugin = Phpfox_Plugin::get('admincp.service_product_product_export')) {
            eval($sPlugin);
        }

        \Phpfox_File::instance()->forceDownload($zipFile, 'phpfox-product-' . $aRow ['product_id'] . '.zip');

        return [
            'name' => $aRow['product_id'] . (!empty($aRow['version']) ? '-' . $aRow['version'] : ''),
        ];
    }

    private function _buildChecksum($aModule)
    {
        $sDir = PHPFOX_DIR_MODULE . $aModule['module_id'] . PHPFOX_DS;
        $sExportJsonPath = $sDir . PHPFOX_DS . 'export.json';
        $helper = new FileHelper();
        if (file_exists($sExportJsonPath)) {
            $json = json_decode(file_get_contents($sExportJsonPath), true);

            // pre-process
            $json['paths'] = array_map(function ($dir) {
                return trim(str_replace('/', PHPFOX_DS, $dir), PHPFOX_DS) . PHPFOX_DS;
            }, $json['paths']);

            if (empty($json['paths']) || !in_array(str_replace(PHPFOX_ROOT, '', $sDir), $json['paths'])) {
                return;
            }

            $aDirs = array_map(function ($dir) {
                return PHPFOX_ROOT . $dir;
            }, $json['paths']);

            $helper->createChecksum($sDir, $aDirs);
        } else {
            $helper->createChecksum($sDir, [$sDir]);
        }
    }
    
    /**
     * @param int $iId
     *
     * @return array
     */
    public function getDependencies($iId)
    {
        return $this->database()->select('*')
            ->from(Phpfox::getT('product_dependency'))
            ->where("product_id = '" . $this->database()->escape($iId) . "'")
            ->execute('getSlaveRows');
    }
    
    /**
     * @param int $iId
     *
     * @return array
     */
    public function getInstalls($iId)
    {
        return $this->database()->select('*')
            ->from(Phpfox::getT('product_install'))
            ->where("product_id = '" . $this->database()->escape($iId) . "'")
            ->order('version ASC')
            ->execute('getSlaveRows');
    }
    
    /**
     * @return array
     */
    public function getNewProductsForInstall()
    {
        $aNew = [];
        $hDir = opendir(PHPFOX_DIR_XML);
        while ($sFile = readdir($hDir)) {
            if (substr($sFile, -4) == '.xml') {
                if (!$this->isProduct(substr_replace($sFile, '', -4))) {
                    $aProduct = Phpfox::getLib('xml.parser')->parse(file_get_contents(PHPFOX_DIR_XML . $sFile));
                    if (isset($aProduct['data'])) {
                        $aNew[] = $aProduct['data'];
                    }
                }
            }
        }
        closedir($hDir);

        return $aNew;
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
        if ($sPlugin = Phpfox_Plugin::get('admincp.service_product_product___call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
    
    /**
     * @return array
     */
    private function _get()
    {
        $aProducts = $this->database()->select('product.*')
            ->from($this->_sTable, 'product')
            ->order('product.is_core DESC, product.product_id ASC')
            ->execute('getSlaveRows');

        $aCache = [];
        $iCnt = 2;
        foreach ($aProducts as $aProduct) {
            if ($aProduct['product_id'] == 'phpfox') {
                $aCache[1] = $aProduct;

                continue;
            }

            if ($aProduct['product_id'] == 'phpfox_installer') {
                $aCache[2] = $aProduct;

                continue;
            }

            $iCnt++;

            $sXml = PHPFOX_DIR_INCLUDE . 'xml' . PHPFOX_DS . $aProduct['product_id'] . '.xml';
            if (file_exists($sXml)) {
                $aXml = Phpfox::getLib('xml.parser')->parse(file_get_contents($sXml));
                if (isset($aXml['data']['version']) && version_compare($aXml['data']['version'], $aProduct['version'], '>')) {
                    $aProduct['upgrade_version'] = $aXml['data']['version'];
                }
            }

            if (!empty($aProduct['latest_version']) && version_compare($aProduct['version'], $aProduct['latest_version'], '>=')) {
                $aProduct['latest_version'] = 0;
            }

            $aCache[ $iCnt ] = $aProduct;
        }

        ksort($aCache);

        return $aCache;
    }
}