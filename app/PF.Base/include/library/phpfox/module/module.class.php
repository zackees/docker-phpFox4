<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 * Module Handler
 * This class is used to call and interact with all the modules. Modules are
 * used to power all the pages and blocks found on those pages.
 *
 * @copyright         [PHPFOX_COPYRIGHT]
 * @author            phpFox LLC
 * @package           Phpfox
 * @version           $Id: module.class.php 7075 2014-01-28 16:04:34Z Fern $
 */
class Phpfox_Module
{
    /**
     * @var array
     */
    public $_aServiceNames = [];

    /**
     * @var array
     */
    private $_aliasNames = [];

    /**
     * @var array
     */
    private $_aliasForwards = [];

    private $_loadedCallback = false;

    private $_aCallbacks = [];

    /**
     * @var array
     */
    public $_aComponentNames = [];

    /**
     * List of all the active modules.
     *
     * @var array
     */
    public $_aModules = [];

    /**
     * List of all the active services part of active modules.
     *
     * @var array
     */
    private $_aServices = [];

    /**
     * List of all the active blocks part of active modules.
     *
     * @var array
     */
    private $_aModuleBlocks = [];

    /**
     * Holds the value of the default controller to execute.
     *
     * @var string
     */
    private $_sController = 'index';

    /**
     * Holds the value of the default module to execute.
     *
     * @var string
     */
    private $_sModule = PHPFOX_MODULE_CORE;

    /**
     * List of all the active components part of active modules.
     *
     * @var array
     */
    private $_aComponent = [];

    /**
     * Object of the class loaded by the current controller being used.
     *
     * @var object
     */
    private $_oController = null;

    /**
     * List of controllers that are within iFrames and require special rules to be loaded.
     *
     * @var array
     */
    private $_aFrames = [
        'attachment-frame',
        'photo-frame',
    ];

    /**
     * Defines if a template should not be loaded when calling a controller.
     *
     * @var bool
     */
    private $_bNoTemplate = false;

    /**
     * Cache and store all the return values from components being loaded.
     *
     * @var array
     */
    private $_aReturn = [];

    /**
     * Holds all the HTML output of a controller so it can later be displayed in a specific position on the site.
     * This allows the ability to drag/drop blocks.
     *
     * @var array
     */
    private $_aCachedData = [];

    /**
     * Cached array of all the components. Only stored during debug mode.
     *
     * @var array
     */
    private $_aComponents = [];

    /**
     * Cache ARRAY of all the custom pages the site has, which are created by admins.
     *
     * @var array
     */
    private $_aPages = [];

    /**
     * ARRAY can add extra blocks that are not loaded by normal means (via AdminCP).
     *
     * @var array
     */
    private $_aCallbackBlock = [];

    /**
     * If a user has dragged/dropped blocks this variable will store the blocks information in an ARRAY.
     *
     * @var array
     */
    private $_aItemDataCache = [];

    /**
     * Holds an ARRAY of all the blocks that were moved by the end user.
     *
     * @var array
     */
    private $_aMoveBlocks = [];

    /**
     * ARRAY of blocks that has source code in the database, instead of PHP files.
     *
     * @var array
     */
    private $_aBlockWithSource = [];

    private $_block_app = [];

    /**
     * List of cached block IDs.
     *
     * @var array
     */
    private $_aCacheBlockId = [];

    public $sFinalModuleCallback = '';

    private $_class = '';

    public $blocks = [];

    /**
     * Class constructor that caches all the modules, components (blocks/controllers) and drag/drop information.
     *
     */
    public function __construct()
    {
        if (!defined('PHPFOX_INSTALLER') || (defined('PHPFOX_INSTALLER') && !defined('PHPFOX_SCRIPT_CONFIG'))) {
            $this->_cacheModules();
        }

        // No modules found because its a fresh install
        if (Phpfox::getParam('core.is_installed') && !count($this->_aModules)) {
            $oDb = Phpfox_Database::instance();
            if (is_object($oDb)) {
                $this->_cacheModules();
            }
        }
    }

    /**
     * @return $this
     */
    public static function instance()
    {
        return Phpfox::getLib('module');
    }

    public function all()
    {
        return $this->_aModules;
    }

    public function block($controller, $location, $html, \Core\Block $object = null)
    {
        if ($object !== null) {
            $cache = $html;
            $html = [
                'callback' => $cache,
                'object' => $object,
            ];

            $this->blocks[$controller][$location][] = $html;
        } else {
            $this->blocks[$controller][$location][] = [$html];
        }
    }

    public function get($sModule)
    {
        if (!isset($this->_aModules[$sModule])) {
            Phpfox_Error::toss('Not a valid module.');
        }

        return Phpfox_Database::instance()->select('*')
            ->from(Phpfox::getT('module'))
            ->where(['module_id' => $sModule])
            ->execute('getRow');
    }

    /**
     * Load all module blocks based on the theme being used.
     *
     */
    public function loadBlocks()
    {
        Core\Event::trigger('lib_module_get_blocks', $this);

        $this->_cacheModuleBlocks();
    }

    /**
     * Checks if a module is valid or not (IF EXISTS OR IF EXISTS AND IS VALUE)
     *
     * @param string $sModule Module name.
     *
     * @return bool TRUE if it exists, FALSE if it does not.
     */
    public function isModule($sModule)
    {
        if (in_array($sModule, ['input'])) {
            return false;
        }
        $sModule = strtolower($sModule);
        if (isset($this->_aModules[$sModule])) {
            return true;
        }

        return false;
    }

    /**
     * Check is module or apps
     *
     * @param string $sName
     * @param boolean $bReturnId
     * @param boolean $bNoCheckModule
     *
     * @return mixed
     */
    public function isApps($sName, $bReturnId = false, $bNoCheckModule = false)
    {
        if (!$bNoCheckModule && $this->isModule($sName)) {
            return ($bReturnId) ? $sName : true;
        }
        if ($return = Phpfox::getCoreApp()->exists($sName, $bReturnId)) {
            return $return;
        } else {
            return false;
        }
    }

    /**
     * Returns the module ID. Since Alpha versions of phpFox we changed modules to have unique string IDs
     * so this function basically returns the same ID you are passing with the first argument.
     *
     * @param string $sName Module name.
     *
     * @return string Returns the unique module ID.
     */
    public function getModuleId($sName)
    {
        $sModule = strtolower($sName);
        if (isset($this->_aModules[$sModule])) {
            return $this->_aModules[$sModule];
        }

        return 'core';
    }

    public function resetBlocks($sController = '')
    {
        if (!empty($sController)) {
            unset($this->_aModuleBlocks[$sController]);
        } else {
            $this->_aModuleBlocks = [];
        }
    }

    /**
     * @param $sController
     */
    public function dispatch($sController)
    {
        $aParts = explode('.', $sController, 2);
        $this->_sModule = $aParts[0];
        $this->_sController = $aParts[1];

        (($sPlugin = Phpfox_Plugin::get('set_defined_controller')) ? eval($sPlugin) : false);
    }

    /**
     * Sets the controller for the page we are on. This method controls what component to load, which
     * will be used to display the content on that page.
     *
     * @param string $sController (Optional) We find the controller by default, however you can override our default
     *                            findings by passing the name of the controller with this argument.
     * @return array|bool|\Core\Route\Controller|mixed|null
     * @throws Exception
     */
    public function setController($sController = '')
    {
        if ($sController) {
            $aParts = explode('.', $sController);
            $this->_sModule = $aParts[0];
            $this->_sController = substr_replace($sController, '', 0, strlen($this->_sModule . '_'));

            (($sPlugin = Phpfox_Plugin::get('set_defined_controller')) ? eval($sPlugin) : false);

            $this->getController();

            return null;
        }

        $View = (new Core\Route\Controller())->get();
        $Return = false;

        if ($View instanceof \Core\Route\Module) {
            $this->_sModule = $View->module;
            $this->_sController = $View->controller;
            $Return = null;
        } else if ($View) {
            $Return = $View;
        }
        if ($View && $Return !== false) {
            if (Phpfox::getParam('core.site_is_offline')
                && !Phpfox::getUserParam('core.can_view_site_offline')
                && !request()->get('force-flavor')
            ) {
                $this->_sModule = 'core';
                $this->_sController = 'offline';
                define('PHPFOX_SITE_IS_OFFLINE', true);
            }
            return $Return;
        }

        (($sPlugin = Phpfox_Plugin::get('module_setcontroller_start')) ? eval($sPlugin) : false);

        $oReq = Phpfox_Request::instance();
        $oPage = Phpfox::getService('page');

        $this->_sModule = (($sReq1 = $oReq->get('req1')) ? strtolower($sReq1) : 'core');

        if (($sFrame = $oReq->get('frame')) && in_array($sFrame, $this->_aFrames)) {
            $aFrameParts = explode('-', $sFrame);
            $this->_sModule = strtolower($aFrameParts[0]);
            $this->_sController = strtolower($aFrameParts[1]);
        }

        $this->_aPages = $oPage->getCache();
        if (isset($this->_aPages[$oReq->get('req1')])) {
            $this->_sModule = 'page';
            $this->_sController = 'view';
        }

        $sDir = PHPFOX_DIR_MODULE . $this->_sModule . PHPFOX_DS;

        if ($oReq->get('req2') == 'admincp') {
            Phpfox_Url::instance()->send($oReq->get('req2') . '.' . $oReq->get('req1'));
        }

        if ($oReq->get('req2') && file_exists($sDir . PHPFOX_DIR_MODULE_COMPONENT . PHPFOX_DS . 'controller' . PHPFOX_DS . strtolower($oReq->get('req2')) . '.class.php')) {
            $this->_sController = strtolower($oReq->get('req2'));
        } elseif (strtolower($this->_sModule) != 'admincp' && $oReq->get('req3') && file_exists($sDir . PHPFOX_DIR_MODULE_COMPONENT . PHPFOX_DS . 'controller' . PHPFOX_DS . strtolower($oReq->get('req2')) . PHPFOX_DS . strtolower($oReq->get('req3')) . '.class.php')) {
            $this->_sController = strtolower($oReq->get('req2') . '.' . $oReq->get('req3'));
        } elseif (strtolower($this->_sModule) != 'admincp' && $oReq->get('req2') && file_exists($sDir . PHPFOX_DIR_MODULE_COMPONENT . PHPFOX_DS . 'controller' . PHPFOX_DS . strtolower($oReq->get('req2')) . PHPFOX_DS . 'index.class.php')) {
            $this->_sController = strtolower($oReq->get('req2')) . '.index';
        } else {
            // Over-ride the index page to display the content for guests or members
            if ($this->_sModule == 'core' && $this->_sController == 'index') {
                $this->_sController = (Phpfox::isUser() ? 'index-member' : 'index-visitor');
            }

            if (!file_exists($sDir . PHPFOX_DIR_MODULE_COMPONENT . PHPFOX_DS . 'controller' . PHPFOX_DS . $this->_sController . '.class.php')) {
                $this->_sModule = 'profile';
            }

            (($sPlugin = Phpfox_Plugin::get('set_controller_else_end')) ? eval($sPlugin) : false);
        }

        if ($this->_sModule == 'theme') {
            if (preg_match('/^(.*?)\.(jpg|jpeg|gif|png|css|js)$/i', $_GET[PHPFOX_GET_METHOD])) {
                $this->_sModule = 'error';
                $this->_sController = '404';
            }
        }

        if ($this->_sModule != 'profile' && !isset($this->_aModules[$this->_sModule])) {
            $this->_sModule = 'error';
            $this->_sController = '404';
        }

        if ($oReq->segment(1) == 'hashtag') {
            $this->_sModule = 'core';
            $this->_sController = (Phpfox::isUser() ? 'index-member' : 'index-visitor');
        }

        (($sPlugin = Phpfox_Plugin::get('module_setcontroller_end')) ? eval($sPlugin) : false);

        $bCookie = (Phpfox::getCookie('page_login') && Phpfox::getUserBy('profile_page_id') > 0);

        if (Phpfox::isUser() && $bCookie != 1 && Phpfox::getUserParam('user.require_profile_image')
            && Phpfox::getUserBy('user_image') == '' && !Phpfox::getService('user')->hasPendingProfilePhoto() &&
            !(
                ($this->_sModule == 'user' && $this->_sController == 'photo') ||
                ($this->_sModule == 'user' && $this->_sController == 'logout') ||
                ($this->_sModule == 'subscribe')
            )
        ) {
            Phpfox_Url::instance()->send('user.photo', null, _p('you_are_required_to_upload_a_profile_image'));
        }

        if (Phpfox::getParam('core.force_https_secure_pages')) {
            $sController = str_replace('mobile.', '', $this->getFullControllerName());
            if ($sController == 'core.index-member' || $sController == 'core.index-visitor') {
                $sController = '';
            }

            if (in_array(str_replace('mobile.', '', $this->getFullControllerName()), Phpfox::getService('core')->getSecurePages())) {
                if (!defined('PHPFOX_IS_HTTPS') || !PHPFOX_IS_HTTPS) {
                    Phpfox_Url::instance()->send($sController);
                }
            } else {
                if (Phpfox::getParam('core.force_https_secure_pages')) {
                    if (!defined('PHPFOX_IS_HTTPS') || !PHPFOX_IS_HTTPS) {
                        Phpfox_Url::instance()->send($sController);
                    }
                } else {
                    if (defined('PHPFOX_IS_HTTPS') && PHPFOX_IS_HTTPS && !PHPFOX_IS_AJAX) {
                        $url = url($sController);
                        $url = str_replace('https://', 'http://', $url);
                        url()->send($url);
                    }
                }
            }
        }

        if (Phpfox::getParam('core.site_is_offline')
            && !Phpfox::getUserParam('core.can_view_site_offline')
            && !($this->_sModule == 'user' && in_array($this->_sController, ['login', 'logout']))
            && $this->_sModule != 'captcha'
            && !request()->get('force-flavor')) {
            $this->_sModule = 'core';
            $this->_sController = 'offline';
            define('PHPFOX_SITE_IS_OFFLINE', true);
        }
    }

    public function appendPageClass($class)
    {
        $this->_class .= ' ' . $class . ' ';
    }

    public function getPageClass()
    {
        $class = 'theme_' . flavor()->active->id;
        if (defined('PHPFOX_IS_PAGES_VIEW')) {
            if (defined('PHPFOX_PAGES_ITEM_TYPE')) {
                $class .= ' _is_' . PHPFOX_PAGES_ITEM_TYPE . '_view ';
            } else {
                $class .= ' _is_pages_view ';
            }
        }
        if (defined('PHPFOX_IS_USER_PROFILE')) {
            $class .= ' _is_profile_view ';
        }

        if (!Phpfox::isUser()) {
            $class .= ' _is_guest_user ';
        } elseif (Phpfox::getUserBy('profile_page_id')) {
            $class .= ' _is_login_page ';
        }

        $class .= $this->_class;

        if (Phpfox::isAdminPanel()) {
            $class .= ' admincp-fixed-menu ';
            $class .= str_replace(['.', '/'], '-', Phpfox_Module::instance()->getFullControllerName());
            if (Phpfox::getCookie('admincp-toggle-nav-cookie')) {
                $class .= ' collapse-nav-active';
            }
        }

        $object = new stdClass();
        $object->cssClass = $class;
        Core\Event::trigger('lib_module_page_class', $object);

        if (Phpfox::getParam('core.site_is_offline')
            && !preg_match('/is-site-offline/', $object->cssClass)) {
            $object->cssClass .= ' is-site-offline';
        }
        if (defined('PHPFOX_TRIAL_MODE')
            && !preg_match('/is-site-trial/', $object->cssClass)) {
            $object->cssClass .= ' is-site-trial';
        }

        return $object->cssClass;
    }

    public function getPageId()
    {
        $id = $this->getFullControllerName();
        if (Core\Route\Controller::$name && $id == 'core.index') {
            $route = Core\Route\Controller::$name;
            if (isset($route['route'])) {
                $id = 'route_' . $route['route'];
            }
        }
        $id = str_replace(['/', '.'], '_', $id);

        $object = new stdClass();
        $object->id = $id;
        Core\Event::trigger('lib_module_page_id', $object);

        return $object->id;
    }

    /**
     * Loads and outputs the current page based on the controller we loaded with the method setController().
     *
     * @see self::setController()
     */
    public function getController()
    {
        // Get the component
        $this->_oController = $this->getComponent($this->_sModule . '.' . $this->_sController, ['bNoTemplate' => true], 'controller');
    }

    /**
     * Gets the full name of the controller we are using including the module prefix.
     *
     * @return string
     */
    public function getFullControllerName()
    {
        return $this->_sModule . '.' . str_replace('\\', '/', $this->_sController);
    }

    /**
     * Gets the controllers template. We do this automatically since each controller has a specific template that it
     * loads to output data to the site.
     *
     * @return mixed NULL if we were able to load a template and FALSE if such a template does not exist.
     */
    public function getControllerTemplate()
    {
        $sClass = $this->_sModule . '.controller.' . $this->_sController;
        if (isset($this->_aReturn[$sClass]) && $this->_aReturn[$sClass] === false) {
            return false;
        }

        (($sPlugin = Phpfox_Plugin::get('module_getcontrollertemplate')) ? eval($sPlugin) : false);

        // Get the template and display its content for the specific controller component
        Phpfox_Template::instance()->getTemplate($sClass);

        // Check if the component we have loaded has the clean() method
        if (is_object($this->_oController) && method_exists($this->_oController, 'clean')) {
            // This method is used to clean out any garbage we don't need later on in the script. In most cases Template assigns.
            $this->_oController->clean();
        }
    }

    /**
     * Module blocks are loaded via the AdminCP, however it can manually be loaded with this method.
     *
     * @param string $sController Controller this block belongs to.
     * @param int $iId Position of where the block must be located by default.
     */
    public function addModuleBlock($sController, $iId)
    {
        $this->_aCallbackBlock[$iId] = $sController;
    }

    /**
     * @param string $sModule
     * @param        $sController
     *
     * @return string string
     */
    public function inflectFullControllerName($sModule, $sController)
    {

        if (!$sController) {
            $sController = $this->_sController;
        }

        return strtolower($sModule . '.' . str_replace(['\\', '/'], '.',
                $sController));
    }

    /**
     * Gets all the blocks for a specific location on a specific page.
     *
     * @param int $iId on the template.
     * @param bool (Optional) If   blocks are already loaded set this to TRUE to reload them anyway.
     *
     * @return array Returns a list of blocks for that page and in a specific order.
     */
    public function getModuleBlocks($iId, $bForce = false)
    {
        static $aBlocks = [];

        if (isset($aBlocks[$iId]) && $bForce === false) {
            return $aBlocks[$iId];
        }

        $aBlocks[$iId] = [];

        //Disable all block in map view
        if (defined('PHPFOX_IS_CONTROLLER_GMAP') && PHPFOX_IS_CONTROLLER_GMAP && $iId != 6) {
            return [];
        }

        if (defined('PHPFOX_IS_USER_PROFILE') && $iId == 11) {
            $aBlocks[$iId][] = ['type_id' => 0, 'ordering' => 0, 'component' => 'profile.pic', 'params' => []];
        }

        if (defined('PHPFOX_IS_USER_PROFILE_INDEX') && $iId == 1) {
            $aBlocks[$iId][] = ['type_id' => 0, 'ordering' => 0, 'component' => 'custom.panel', 'params' => ['type_id' => 'user_main', 'ignore_field' => true]];
        }

        if (defined('PHPFOX_IS_PAGES_VIEW') && PHPFOX_IS_PAGES_VIEW && $iId == 11) {
            $aBlocks[$iId][] = ['type_id' => 0, 'ordering' => 0, 'component' => PHPFOX_PAGES_ITEM_TYPE . '.photo', 'params' => []];
        }

        (($sPlugin = Phpfox_Plugin::get('get_module_blocks')) ? eval($sPlugin) : false);

        $aControllers[] = $sController = $this->inflectFullControllerName($this->_sModule, $this->_sController);

        if (\Core\Route\Controller::$name && substr(\Core\Route\Controller::$name['route'], 0, 6) != 'groups') {
            $aControllers[] = str_replace('/', '_', 'route_' . \Core\Route\Controller::$name['route']);
        }

        $iUserGroupId = Phpfox::getUserBy('user_group_id');
        $oReq = Phpfox::getLib('request');
        $sDetailPageUrl = $this->_sModule == 'page' && $this->_sController == 'view' && isset($this->_aPages[$oReq->get('req1')]) ? $oReq->get('req1') : null;

        foreach ($aControllers as $index => $sController) {
            if (isset($this->_aModuleBlocks[$sController][$iId])
                || isset($this->_aModuleBlocks[str_replace('.index', '', $sController)][$iId])
                || isset($this->_aModuleBlocks[$this->_sModule][$iId])
                || isset($this->_aModuleBlocks[''][$iId])
                || (isset($sDetailPageUrl) && isset($this->_aModuleBlocks['page.' . $sDetailPageUrl][$iId]))
            ) {
                $aCachedBlocks = [];

                if (isset($sDetailPageUrl) && isset($this->_aModuleBlocks['page.' . $sDetailPageUrl][$iId])) {
                    $sController = 'page.' . $sDetailPageUrl;
                }

                if (isset($this->_aModuleBlocks[$sController][$iId])) {
                    foreach ($this->_aModuleBlocks[$sController][$iId] as $mKey => $mData) {
                        $aCachedBlocks[$mKey] = $mData;
                    }
                }

                if (isset($this->_aModuleBlocks[str_replace('.index', '', $sController)][$iId])) {
                    foreach ($this->_aModuleBlocks[str_replace('.index', '', $sController)][$iId] as $mKey => $mData) {
                        $aCachedBlocks[$mKey] = $mData;
                    }
                }

                if (isset($this->_aModuleBlocks[$this->_sModule][$iId])) {
                    foreach ($this->_aModuleBlocks[$this->_sModule][$iId] as $mKey => $mData) {
                        $aCachedBlocks[$mKey] = $mData;
                    }
                }

                if (!$index && isset($this->_aModuleBlocks[''][$iId]) && !Phpfox::isAdminPanel()) {
                    foreach ($this->_aModuleBlocks[''][$iId] as $mKey => $mData) {
                        $aCachedBlocks[$mKey] = $mData;
                    }
                }

                foreach ($aCachedBlocks as $sComponentName => $aComponentBlocks) {
                    foreach ($aComponentBlocks as $sKey => $sValue) {
                        $ordering = 0;
                        if (is_array($sValue) && !empty($sValue['ordering'])) {
                            $ordering = $sValue['ordering'];
                            unset($sValue['ordering']);
                        }
                        // 1. process disallow access via block settings.
                        $disallowAccess = [];

                        if (is_array($sValue) && !empty($sValue['disallow_access'])) {
                            $disallowAccess = $sValue['disallow_access'];
                        } elseif (is_string($sValue)) {
                            $disallowAccess = $sValue;
                        }

                        if (is_string($disallowAccess)) {
                            $disallowAccess = (array)@unserialize($disallowAccess);
                        }

                        if (!empty($disallowAccess) && in_array($iUserGroupId, $disallowAccess)) {
                            continue;
                        }

                        if (!empty($sValue['allowed_connections'])) {
                            if (!is_array($sValue['allowed_connections'])) {
                                $sValue['allowed_connections'] = unserialize($sValue['allowed_connections']);
                            }
                            if (!in_array($sController, $sValue['allowed_connections'])) {
                                continue;
                            }
                        }

                        // 2. check hidden on device
                        if (is_array($sValue) && !empty($sValue['hidden_device'])) {
                            $hiddenDevices = $sValue['hidden_device'];
                            $mobileDetect = Phpfox::getService('core.mobile.detect');
                            $isMobile = $mobileDetect->isMobile();
                            $isTablet = $mobileDetect->isTablet();

                            if ($isTablet) { // is Tablet
                                if(in_array('hidden-tablet', $hiddenDevices)) {
                                    continue;
                                }
                            } elseif ($isMobile) { // is Mobile
                                if(in_array('hidden-mobile', $hiddenDevices)) {
                                    continue;
                                }
                            }
                            else { // is Desktop
                                if(in_array('hidden-desktop', $hiddenDevices)) {
                                    continue;
                                }
                            }
                        }

                        // 3. check controller parts
                        $aControllerParts = [];
                        if (preg_match('/\./', $sController)) {
                            $aControllerParts = explode('.', $sController);
                        }

                        if (isset($this->_aBlockWithSource[$sController][$iId][$sKey])
                            || isset($this->_aBlockWithSource[str_replace('.index', '', $sController)][$iId][$sKey])
                            || isset($this->_aBlockWithSource[''][$iId][$sKey])
                            || (count($aControllerParts) && isset($this->_aBlockWithSource[$aControllerParts[0]][$iId][$sKey]))
                        ) {
                            $oCache = Phpfox::getLib('cache');
                            $sCacheId = $oCache->set('block_file_' . $sKey);

                            if (($aCacheData = $oCache->getLocalFirst($sCacheId)) && (isset($aCacheData['block_id']))) {
                                $this->_aCacheBlockId[md5($aCacheData['source_parsed'])] = [
                                    'block_id' => $aCacheData['block_id'],
                                    'location' => $aCacheData['location'],
                                ];
                                $fileName = PHPFOX_DIR_CACHE . 'template/' . md5($sKey) . '.php';
                                if (!file_exists($fileName)) {
                                    file_put_contents($fileName, $aCacheData['source_parsed']);
                                }
                                if ($aCacheData['type_id'] == 1) {
                                    $aCacheData['source_code'] = $fileName;
                                }
                                $aBlocks[$iId][] = [
                                    'type_id' => $aCacheData['type_id'],
                                    'component' => $aCacheData['source_code'],
                                    'ordering' => $ordering,
                                    'params' => $sValue
                                ];
                            }
                        } else {
                            if (isset($this->_block_app[$sController][$iId][$sKey])
                                || isset($this->_block_app[str_replace('.index', '', $sController)][$iId][$sKey])
                                || isset($this->_aBlockWithSource[''][$iId][$sKey])
                                || (count($aControllerParts) && isset($this->_aBlockWithSource[$aControllerParts[0]][$iId][$sKey]))
                            ) {
                                $name = $this->_block_app[$sController][$iId][$sKey];
                                if (isset(Core\Block\Group::$blocks[$name])) {
                                    $callback = Core\Block\Group::$blocks[$name];
                                    $aBlocks[$iId][] = $callback;
                                }
                            } else {
                                $aBlocks[$iId][] = [
                                    'type_id' => 0,
                                    'component' => $sComponentName,
                                    'ordering' => $ordering,
                                    'params' => $sValue
                                ];
                                if ($sPlugin = Phpfox_Plugin::get('library_module_getmoduleblocks_1')) {
                                    eval($sPlugin);
                                    if (isset($bReturnFromPlugin)) {
                                        return $bReturnFromPlugin;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (isset($this->_aCallbackBlock[$iId])) {
            $aBlocks[$iId] = array_merge($aBlocks[$iId], [$this->_aCallbackBlock[$iId]]);
        }

        // support clean template
        if ($iId == Phpfox::getParam('core.sub_menu_location') && !Phpfox::isAdminPanel() && Phpfox_Template::instance()->getThemeFolder() == 'bootstrap') {
            array_unshift($aBlocks[$iId], ['type_id' => 0, 'component' => 'core.template-menusub', 'params' => []]);
        }

        if (isset($this->blocks['*']) && isset($this->blocks['*'][$iId])) {
            $content = $this->blocks['*'][$iId];
            $aBlocks[$iId] = array_merge($aBlocks[$iId], (array)$content);
        }

        foreach ($aControllers as $sController) {
            if (isset($this->blocks[$sController]) && isset($this->blocks[$sController][$iId])) {
                $content = $this->blocks[$sController][$iId];
                $aBlocks[$iId] = array_merge($aBlocks[$iId], (array)$content);
            }
        }

        (($sPlugin = Phpfox_Plugin::get('get_module_blocks_end')) ? eval($sPlugin) : false);

        // add ordering for block no ordering field
        $ordering = 100;
        foreach ($aBlocks[$iId] as $index => $aBlock) {
            if (is_array($aBlock) && !empty($aBlock) && !isset($aBlock['ordering'])) {
                $aBlocks[$iId][$index]['ordering'] = $ordering + $index;
            }
        }

        usort($aBlocks[$iId], function ($a, $b) {
            if (is_array($a) && is_array($b)) {
                return ($a['ordering'] <= $b['ordering']) ? -1 : 1; // -1 no need re-order
            } else {
                return -1; // -1 no need re-order
            }
        });

        return $aBlocks[$iId];
    }

    /**
     * Get the module name of the current controller we are using.
     *
     * @return string
     */
    public function getModuleName()
    {
        return $this->_sModule;
    }

    /**
     * Get the name of the current controller we are using.
     *
     * @return string
     */
    public function getControllerName()
    {
        return $this->_sController;
    }

    /**
     * @var array $services
     *
     * @return $this
     */
    public function addServiceNames($services)
    {
        foreach ($services as $name => $class) {
            $this->_aServiceNames[$name] = $class;
        }

        return $this;
    }

    /**
     * @param $alias
     * @param $actual
     *
     * @return $this
     */
    public function addAliasNames($alias, $actual)
    {
        $this->_aliasNames[$alias] = $actual;
        $this->_aliasForwards[$actual] = $alias;

        return $this;
    }

    /**
     * @param array $dirs
     *
     * @return $this
     */
    public function addTemplateDirs($dirs)
    {
        Phpfox_Template::instance()->addDirectoryNames($dirs);

        return $this;
    }

    /**
     * @param  string $sType
     * @param array $aComponents
     *
     * @return $this
     *
     */
    public function addComponentNames($sType, $aComponents)
    {
        foreach ($aComponents as $name => $class) {
            $this->_aComponentNames[$sType][$name] = $class;
        }

        return $this;
    }

    /**
     * Loads a service class. Service classes are module based class that interact with the database
     * and runs general PHP logic that is not needed to be found with components.
     *
     * @param string $sClass Name of the service class to load.
     * @param array $aParams (Optional) ARRAY of params to pass to that class.
     *
     * @return mixed On success we return the class object, on failure we return FALSE.
     */
    public function getService($sClass, $aParams = [])
    {

        if (isset($this->_aServices[$sClass])) {
            return $this->_aServices[$sClass];
        }

        if (!empty($this->_aServiceNames[$sClass])) {
            $sClassName = $this->_aServiceNames[$sClass];

            return $this->_aServices[$sClass] = Phpfox::getObject($sClassName);
        }

        if (preg_match('/\./', $sClass) && ($aParts = explode('.', $sClass)) && isset($aParts[1])) {
            $sModule = $aParts[0];
            $sService = $aParts[1];
        } else {
            $sModule = $sClass;
            $sService = $sClass;
        }

        if (!defined('PHPFOX_INSTALLER') && !isset($this->_aModules[$sModule])) {
            Phpfox::getLib('cache')->removeGroup('module');
            $this->_cacheModules(); // try cache modules again.
            if (!isset($this->_aModules[$sModule])) {
                return Phpfox_Error::trigger('Calling a Service from an invalid Module. Make sure the module is valid or set to active. (' . $sModule . '::' . $sService . ')', E_USER_ERROR);
            }
        }

        if ($sClass == 'core.currency.process') {
            $sFile = PHPFOX_DIR_MODULE . 'core' . PHPFOX_DS . 'include' . PHPFOX_DS . 'service' . PHPFOX_DS . 'currency' . PHPFOX_DS . 'process.class.php';
            $sModule = 'Core';
            $sService = 'Currency_Process';
        } else {
            $sFile = PHPFOX_DIR_MODULE . $sModule . PHPFOX_DS . PHPFOX_DIR_MODULE_SERVICE . PHPFOX_DS . $sService . '.class.php';
        }

        if (!cached_file_exists($sFile)) {
            if (isset($aParts[2])) {
                $sFile = PHPFOX_DIR_MODULE . $sModule . PHPFOX_DS . PHPFOX_DIR_MODULE_SERVICE . PHPFOX_DS . $sService . PHPFOX_DS . $aParts[2] . '.class.php';

                if (!cached_file_exists($sFile)) {
                    if (isset($aParts[3]) && cached_file_exists(PHPFOX_DIR_MODULE . $sModule . PHPFOX_DS . PHPFOX_DIR_MODULE_SERVICE . PHPFOX_DS . $sService . PHPFOX_DS . $aParts[2] . PHPFOX_DS . $aParts[3] . '.class.php')) {
                        $sFile = PHPFOX_DIR_MODULE . $sModule . PHPFOX_DS . PHPFOX_DIR_MODULE_SERVICE . PHPFOX_DS . $sService . PHPFOX_DS . $aParts[2] . PHPFOX_DS . $aParts[3] . '.class.php';
                        $sService .= '_' . $aParts[2] . '_' . $aParts[3];
                    } else {
                        $sFile = PHPFOX_DIR_MODULE . $sModule . PHPFOX_DS . PHPFOX_DIR_MODULE_SERVICE . PHPFOX_DS . $sService . PHPFOX_DS . $aParts[2] . PHPFOX_DS . $aParts[2] . '.class.php';
                        $sService .= '_' . $aParts[2] . '_' . $aParts[2];
                    }
                } else {
                    $sService .= '_' . $aParts[2];
                }
            } else {
                $sFile = PHPFOX_DIR_MODULE . $sModule . PHPFOX_DS . PHPFOX_DIR_MODULE_SERVICE . PHPFOX_DS . $sService . PHPFOX_DS . $sService . '.class.php';
                $sService .= '_' . $sService;
            }
        }

        if (!cached_file_exists($sFile)) {
            (($sPlugin = Phpfox_Plugin::get('library_module_getservice_1')) ? eval($sPlugin) : false);
            if (isset($mPluginReturn)) {
                return $mPluginReturn;
            }
        }

        $className = $sModule . '_service_' . $sService;
        if (class_exists($className, false)) {
            $this->_aServices[$sClass] = Phpfox::getObject($className);

            return $this->_aServices[$sClass];
        }

        if (cached_file_exists($sFile)) {
            require($sFile);

            $this->_aServices[$sClass] = Phpfox::getObject($className);
        }

        if (!empty($this->_aServices[$sClass])) {
            return $this->_aServices[$sClass];
        }

    }

    /**
     * Loads a module component. Components are the building blocks of the site and
     * include controllers which build up the pages we see and blocks that build up the controllers.
     *
     * @param string $sClass Name of the component to load.
     * @param array $aParams (Optional) Custom params you can pass to the component.
     * @param string $sType (Optional) Identify if this component is a block or a controller.
     * @param boolean $bTemplateParams Assign $aParams to the template
     *
     * @return mixed Return the component object if it exists, otherwise FALSE.
     */
    public function getComponent($sClass, $aParams = [], $sType = 'block', $bTemplateParams = false)
    {
        (($sPlugin = Phpfox_Plugin::get('module_getcomponent_start')) ? eval($sPlugin) : false);

        if ($sType == 'ajax' && strpos($sClass, '.') === false) {
            $sClass = $sClass . '.ajax';
        }

        if (is_array($sClass)) {
            return Phpfox::getBlock('core.holder', [
                'block_location' => $this->_aCacheBlockId[md5($sClass[0])]['location'],
                'block_custom_id' => $this->_aCacheBlockId[md5($sClass[0])]['block_id'],
                'content' => $sClass[0],
            ]);
        }

        $aParts = explode('.', $sClass);
        $sModule = $aParts[0];
        $sComponent = $sType . PHPFOX_DS . substr_replace(str_replace('.', PHPFOX_DS, $sClass), '', 0, strlen($sModule . PHPFOX_DS));

        if ($sType == 'controller') {
            $this->_sModule = $sModule;
            $this->_sController = substr_replace(str_replace('.', PHPFOX_DS, $sClass), '', 0, strlen($sModule . PHPFOX_DS));
        }
        static $sBlockName = '';

        if ($sModule == 'custom') {
            if (preg_match('/block\\' . PHPFOX_DS . 'cf_(.*)/i', $sComponent, $aCustomMatch)) {
                $isUserMain = !defined('PHPFOX_IS_USER_PROFILE_INFO');
                $aParams = [
                    'type_id' => $isUserMain ? 'user_main' : 'user_panel',
                    'template' => $isUserMain ? 'content' : 'info',
                    'custom_field_id' => $aCustomMatch[1],
                ];
                $sBlockName = 'custom_cf_' . $aCustomMatch[1];
                $sComponent = 'block' . PHPFOX_DS . 'display';
                $sClass = 'custom.display';
            }
        }

        $sHash = md5($sClass . $sType);
        $validApp = false;
        $mReturn = 'blank';

        if (!empty($this->_aComponentNames[$sType][$sClass])) {
            $validApp = true;
            $sClass = $this->_aComponentNames[$sType][$sClass];
            $this->_aComponent[$sHash] = Phpfox::getObject($sClass, [
                'sModule' => $sModule,
                'sComponent' => $sComponent,
                'aParams' => $aParams,
            ]);
        }

        if (!$validApp) {
            if (!isset($this->_aModules[$sModule])) {
                return false;
            }

            if (isset($this->_aComponent[$sHash])) {
                $this->_aComponent[$sHash]->__construct([
                    'sModule' => $sModule,
                    'sComponent' => $sComponent,
                    'aParams' => $aParams,
                ]);
            } else {

                $sClassFile = PHPFOX_DIR_MODULE . $sModule . PHPFOX_DS . PHPFOX_DIR_MODULE_COMPONENT . PHPFOX_DS . $sComponent . '.class.php';

                if (!cached_file_exists($sClassFile) && isset($aParts[1])) {
                    $sClassFile = PHPFOX_DIR_MODULE . $sModule . PHPFOX_DS . PHPFOX_DIR_MODULE_COMPONENT . PHPFOX_DS . $sComponent . PHPFOX_DS . $aParts[1] . '.class.php';
                }

                (($sPlugin = Phpfox_Plugin::get('library_module_getcomponent_1')) ? eval($sPlugin) : false);
                if (isset($mPluginReturn)) {
                    return $mPluginReturn;
                }
                // Lets check if there is such a component
                if (!cached_file_exists($sClassFile)) {
                    (($sPlugin = Phpfox_Plugin::get('library_module_getcomponent_2')) ? eval($sPlugin) : false);
                    if (isset($mPluginReturn)) {
                        return $mPluginReturn;
                    }

                    if ($sType == 'block') {
                        return ''; // could not load important block
                    }

                    // Opps, for some reason we have loaded an invalid component. Lets send back info to the dev.
                    Phpfox_Error::trigger('Failed to load component: ' . $sClass . ' (' . $sClassFile . ')', E_USER_ERROR);
                }

                // Require the component
                require($sClassFile);

                // Get the object
                $this->_aComponent[$sHash] = Phpfox::getObject($sModule
                    . '_component_' . str_replace(PHPFOX_DS, '_', $sComponent),
                    [
                        'sModule' => $sModule,
                        'sComponent' => $sComponent,
                        'aParams' => $aParams,
                    ]);
            }
        }

        if ($sType != 'ajax') {
            (($sPlugin = Phpfox_Plugin::get('component_pre_process')) ? eval($sPlugin) : false);
            $mReturn = $this->_aComponent[$sHash]->process();

            if (is_object($mReturn) && $mReturn instanceof Closure) {
                ob_clean();
                echo $mReturn->__invoke();
                exit;
            }

            if ($sType == 'controller' && (is_array($mReturn) || is_object($mReturn))) {
                if ($mReturn instanceof Core\jQuery) {
                    $mReturn = [
                        'run' => (string)$mReturn,
                    ];
                }

                ob_clean();
                header('Content-type: application/json');
                echo json_encode($mReturn);
                exit;
            }

            (($sPlugin = Phpfox_Plugin::get('component_post_process')) ? eval($sPlugin) : false);
        }

        $this->_aReturn[$sClass] = $mReturn;

        // If we return the component as 'false' then there is no need to display it.
        if ((is_bool($mReturn) && !$mReturn) || $this->_bNoTemplate) {
            if ($this->_bNoTemplate) {
                $this->_bNoTemplate = false;
            }

            return $this->_aComponent[$sHash];
        }

        /* Should we pass the params to the template? */
        if ($bTemplateParams) {
            Phpfox_Template::instance()->assign($aParams);
        }

        // Check if we don't want to display a template
        if (!isset($aParams['bNoTemplate']) && $mReturn != 'blank') {
            $iBlockId = isset($aParams['id']) ? $aParams['id'] : 0;
            if ($mReturn && is_string($mReturn)) {
                $sBlockShowName = ($sModule == 'custom' && !empty($sBlockName))
                    ? $sBlockName
                    : ucwords(str_replace(['.', 'PHPfox_', 'phpFox_'], ' ', $sClass));
                $sBlockBorderJsId = ($sModule == 'custom'
                    && !empty($sBlockName))
                    ? $sBlockName
                    : strtolower(str_replace(['.', '\\', 'PHPfox_', 'phpFox_'], '_',
                        $sClass));
                $sBlockPath = $sModule . '.' . str_replace('block' . PHPFOX_DS, '', $sComponent);

                $bCanMove = (!isset($this->_aMoveBlocks[$this->_sModule . '.' . $this->_sController][$sBlockPath][$iBlockId]))
                    || (isset($this->_aMoveBlocks[$this->_sModule . '.' . $this->_sController][$sBlockPath][$iBlockId])
                        && $this->_aMoveBlocks[$this->_sModule . '.' . $this->_sController][$sBlockPath][$iBlockId]);

                $sHidden = '';
                if (isset($aParams['hidden']) && is_array($aParams['hidden'])) {
                    $sHidden = implode(' ', $aParams['hidden']);
                }

                $sToggleWidth = 'true';
                if (isset($aParams['toggle'])) {
                    if (is_array($aParams['toggle'])) {
                        $sToggleWidth = implode(',', $aParams['toggle']);
                    } else {
                        $sToggleWidth = 'false';
                    }
                }

                Phpfox_Template::instance()->assign([
                        'sHidden' => $sHidden,
                        'sToggleWidth' => $sToggleWidth,
                        'sBlockShowName' => $sBlockShowName,
                        'sBlockBorderJsId' => $sBlockBorderJsId,
                        'bCanMove' => $bCanMove,
                        'sClass' => $sClass,
                    ]
                )->setLayout($mReturn);
            }

            if (!is_array($mReturn)) {
                $sComponentTemplate = $sModule . '.' . str_replace(PHPFOX_DS, '.', $sComponent);
                (($sPlugin = Phpfox_Plugin::get('module_getcomponent_gettemplate')) ? eval($sPlugin) : false);
                Phpfox_Template::instance()->getTemplate($sComponentTemplate);
            }

            // Check if the component we have loaded has the clean() method
            if (is_object($this->_aComponent[$sHash]) && method_exists($this->_aComponent[$sHash], 'clean')) {
                // This method is used to clean out any garbage we don't need later on in the script. In most cases Template assigns.
                $this->_aComponent[$sHash]->clean();
            }
        }
        return $this->_aComponent[$sHash];
    }

    public function getBlockObject($sClass)
    {
        $sType = 'block';
        (($sPlugin = Phpfox_Plugin::get('module_getcomponent_start'))
            ? eval($sPlugin) : false);

        $aParts = explode('.', $sClass);
        $sModule = $aParts[0];
        $sComponent = $sType . PHPFOX_DS . substr_replace(str_replace('.',
                PHPFOX_DS, $sClass), '', 0, strlen($sModule . PHPFOX_DS));

        $aParams = [];
        if ($sModule == 'custom') {
            if (preg_match('/block\\' . PHPFOX_DS . 'cf_(.*)/i', $sComponent,
                $aCustomMatch)) {
                $aParams = [
                    'type_id' => 'user_main',
                    'template' => 'content',
                    'custom_field_id' => $aCustomMatch[1],
                ];
                $sComponent = 'block' . PHPFOX_DS . 'display';
                $sClass = 'custom.display';
            }
        }

        $sHash = md5($sClass . $sType);
        $validApp = false;

        if (!empty($this->_aComponentNames[$sType][$sClass])) {
            $validApp = true;
            $sClass = $this->_aComponentNames[$sType][$sClass];
            $this->_aComponent[$sHash] = Phpfox::getObject($sClass, [
                'sModule' => $sModule,
                'sComponent' => $sComponent,
                'aParams' => $aParams,
            ]);
        }

        if (!$validApp) {
            if (!isset($this->_aModules[$sModule])) {
                return false;
            }

            if (isset($this->_aComponent[$sHash])) {
                $this->_aComponent[$sHash]->__construct([
                    'sModule' => $sModule,
                    'sComponent' => $sComponent,
                    'aParams' => $aParams,
                ]);
            } else {

                $sClassFile = PHPFOX_DIR_MODULE . $sModule . PHPFOX_DS
                    . PHPFOX_DIR_MODULE_COMPONENT . PHPFOX_DS . $sComponent
                    . '.class.php';

                if (!file_exists($sClassFile) && isset($aParts[1])) {
                    $sClassFile = PHPFOX_DIR_MODULE . $sModule . PHPFOX_DS
                        . PHPFOX_DIR_MODULE_COMPONENT . PHPFOX_DS . $sComponent
                        . PHPFOX_DS . $aParts[1] . '.class.php';
                }

                (($sPlugin
                    = Phpfox_Plugin::get('library_module_getcomponent_1'))
                    ? eval($sPlugin) : false);
                if (isset($mPluginReturn)) {
                    return $mPluginReturn;
                }
                // Lets check if there is such a component
                if (!file_exists($sClassFile)) {
                    (($sPlugin
                        = Phpfox_Plugin::get('library_module_getcomponent_2'))
                        ? eval($sPlugin) : false);
                    if (isset($mPluginReturn)) {
                        return $mPluginReturn;
                    }
                    // Opps, for some reason we have loaded an invalid component. Lets send back info to the dev.
                    Phpfox_Error::trigger('Failed to load component: ' . $sClass
                        . ' (' . $sClassFile . ')', E_USER_ERROR);
                }

                // Require the component
                require_once($sClassFile);

                // Get the object
                $this->_aComponent[$sHash] = Phpfox::getObject($sModule
                    . '_component_' . str_replace(PHPFOX_DS, '_', $sComponent),
                    [
                        'sModule' => $sModule,
                        'sComponent' => $sComponent,
                        'aParams' => $aParams,
                    ]);
            }
        }

        return $this->_aComponent[$sHash];
    }

    /**
     * Identify that the controller we are loading is not to load its template.
     *
     */
    public function setNoTemplate()
    {
        $this->_bNoTemplate = true;
    }

    /**
     * Get all the active modules.
     *
     * @return array
     */
    public function getModules()
    {
        if (defined('PHPFOX_INSTALLER') && empty($this->_aModules)
            && file_exists(PHPFOX_DIR_FILE . 'log' . PHPFOX_DS
                . 'installer_modules.php')
        ) {
            require(PHPFOX_DIR_FILE . 'log' . PHPFOX_DS
                . 'installer_modules.php');
            if (isset($aModules)) {
                $this->_aModules = $aModules;
            }
        }

        return $this->_aModules;
    }

    /**
     * Get all the tables part of each of the active modules.
     *
     * @param string $sPrefix Prefix of the database table name.
     *
     * @return array ARRAY of all the tables.
     */
    public function getModuleTables($sPrefix)
    {
        $oPhpfoxXmlParser = Phpfox::getLib('xml.parser');
        $aTables = [];
        $aModules = Phpfox_File::instance()->getFiles(PHPFOX_DIR_MODULE);
        foreach ($aModules as $iKey => $sModule) {
            if (!file_exists(PHPFOX_DIR_MODULE . $sModule
                . PHPFOX_DIR_MODULE_XML . PHPFOX_DS . 'phpfox'
                . PHPFOX_XML_SUFFIX)
            ) {
                continue;
            }

            $aModule
                = $oPhpfoxXmlParser->parse(file_get_contents(PHPFOX_DIR_MODULE
                . $sModule . PHPFOX_DIR_MODULE_XML . PHPFOX_DS . 'phpfox'
                . PHPFOX_XML_SUFFIX));

            if (isset($aModule['tables'])) {
                $aCache = unserialize(trim($aModule['tables']));
                foreach ($aCache as $sKey => $aData) {
                    $sKey = preg_replace('#phpfox_#i', $sPrefix, $sKey);
                    $aTables[] = $sKey;
                }
            }
        }

        return $aTables;
    }

    /**
     * Get all the modules found within the module folder.
     *
     * @return array ARRAY of modules.
     */
    public function getModuleFiles()
    {
        // Create a cache of modules we need to skip
        $aSkip = [];
        if (defined('PHPFOX_INSTALL_MOD_IGNORE')) {
            $aParts = explode(',', PHPFOX_INSTALL_MOD_IGNORE);
            foreach ($aParts as $sPart) {
                $aSkip[] = trim($sPart);
            }
        }

        $aFolders = [];
        $iCoreId = 0;
        $aModules = Phpfox_File::instance()->getFiles(PHPFOX_DIR_MODULE);
        foreach ($aModules as $iKey => $sModule) {
            if (!file_exists(PHPFOX_DIR_MODULE . $sModule
                . PHPFOX_DIR_MODULE_XML . PHPFOX_DS . 'phpfox'
                . PHPFOX_XML_SUFFIX)
            ) {
                continue;
            }

            if (count($aSkip) && in_array($sModule, $aSkip)) {
                continue;
            }

            $sContent = file_get_contents(PHPFOX_DIR_MODULE . $sModule
                . PHPFOX_DIR_MODULE_XML . PHPFOX_DS . 'phpfox'
                . PHPFOX_XML_SUFFIX);

            $sCore = (preg_match("/<is_core>1<\/is_core>/i", $sContent) ? 'core'
                : 'plugin');

            $aFolders[$sCore][$iKey] = [
                'name' => $sModule,
            ];

            if ($sModule === 'core') {
                $iCoreId = $iKey;
            }
        }

        unset($aFolders['core'][$iCoreId]);

        array_unshift($aFolders['core'], [
                'name' => 'core',
            ]
        );

        return $aFolders;
    }

    /**
     * Execute a callback on a specific module based on the 1st argument.
     *
     * @param string $sCall Module and callback method to execute.
     * @param array $aParams ARRAY of params you can pass to the callback.
     *
     * @return mixed Returns the value the callback itself returns. FALSE if
     *               not callback was found.
     */
    public function callback($sCall, $aParams = [])
    {
        $this->loadCallbacks();

        list($sModule, $sMethod) = explode('.', $sCall, 2);

        $sModule = $this->sanitizeModuleName($sModule);

        array_shift($aParams);

        if ($this->isModule($sModule)) {
            return call_user_func_array([
                $this->getService($sModule . '.callback'),
                $sMethod,
            ], $aParams);
        }

        if (!$this->isModule($sModule) && strpos($sModule, '_')) {
            $parts = explode('_', $sModule);
            $sModule = $this->sanitizeModuleName(array_shift($parts));
            $sMethod .= implode('_', array_map(function ($str) {
                return ucfirst($str);
            }, $parts));

            return call_user_func_array([
                $this->getService($sModule . '.callback'),
                $sMethod,
            ], $aParams);
        }
    }

    /**
     * Performs a callback on all the modules that have the method being
     * executed.
     *
     * @param string $sMethod Method to execute on all modules.
     * @param array $aParams Params you can pass to your callback.
     *
     * @return bool|array Array of return values with the module ID as the
     *                    unique key.
     */
    public function massCallback($sMethod, $aParams = [])
    {
        $this->loadCallbacks();
        $aReturn = [];
        array_shift($aParams);
        if (empty($this->_aCallbacks[$sMethod])) {
            return false;
        }

        foreach ($this->_aCallbacks[$sMethod] as $sModule => $v) {
            if (!Phpfox::isModule($sModule)) {
                continue;
            }
            $key = isset($this->_aliasForwards[$sModule])
                ? $this->_aliasForwards[$sModule] : $sModule;
            $aResult = call_user_func_array([
                $this->getService($sModule . '.callback'),
                $sMethod,
            ], $aParams);

            if (is_array($aResult) && !empty($aResult['merge_result']) && !empty($aResult['result'])) {
                $aReturn = array_merge($aReturn, $aResult['result']);
            } else {
                $aReturn[$key] = $aResult;
            }
        }

        return $aReturn;
    }

    public function _loadCallbacks()
    {
        $aServices = [];
        $aCallbacks = [];

        foreach ($this->getModules() as $sModule) {
            if (!empty($this->_aliasNames[$sModule])) {
                $sClassName = 'Apps\\' . $this->_aliasNames[$sModule]
                    . '\\Service\\Callback';
            } else {
                $sClassName = ucfirst($sModule) . '_Service_Callback';
            }

            $sServiceName = $sModule . '.callback';

            if (!empty($this->_aServiceNames[$sServiceName])) {
                $sClassName = $this->_aServiceNames[$sServiceName];
            }
            if (class_exists($sClassName)) {
                $aServices[$sModule] = $sServiceName;
            }
        }

        foreach ($aServices as $sModule => $sServiceName) {
            $obj = Phpfox::getService($sServiceName);

            foreach (get_class_methods($obj) as $sMethod) {
                if (!in_array($sMethod,
                    ['__construct', '__call', 'instance'])
                ) {
                    $aCallbacks[$sMethod][$sModule] = 1;
                }
            }
        }
        return $aCallbacks;
    }

    /**
     * load callback values
     */
    public function loadCallbacks()
    {
        if ($this->_loadedCallback) {
            return;
        }

        $this->_loadedCallback = true;

        $this->_aCallbacks = get_from_cache('phpfox_load_callbacks',
            function () {
                return $this->_loadCallbacks();
            });
    }

    /**
     * @return array
     */
    public function getCallbacks()
    {
        $this->loadCallbacks();
        return $this->_aCallbacks;
    }

    /**
     * @param string $sModule
     *
     * @return string
     */
    public function sanitizeModuleName($sModule)
    {
        return isset($this->_aliasForwards[$sModule])
            ? $this->_aliasForwards[$sModule] : $sModule;
    }

    /**
     * Checks if a specific module has a specific callback method.
     *
     * @param string $sModule Module name/ID.
     * @param string $sMethod Callback method.
     * @param string $sType
     *
     * @return bool TRUE if callback exists, otherwise FALSE if not.
     */
    public function hasCallback($sModule, $sMethod, $sType = 'callback')
    {
        $this->loadCallbacks();

        $sModule = $this->sanitizeModuleName($sModule);

        if (!$this->isModule($sModule) && strpos($sModule, '_')) {
            $parts = explode('_', $sModule);
            $sModule = $this->sanitizeModuleName(array_shift($parts));
            $sMethod .= implode('_', array_map(function ($str) {
                return ucfirst($str);
            }, $parts));
        }

        if (empty($this->_aCallbacks[$sMethod])) {
            return false;
        }

        if (empty($this->_aCallbacks[$sMethod][$sModule])) {
            return false;
        }

        return true;
    }

    /**
     * Loads a init class file that each module has and returns a specified
     * property which has information about the module.
     *
     * @param string $sModule Module name/ID.
     * @param string $sProperty Property name to return.
     *
     * @return mixed FALSE if property value or module is not valid or the
     *               property value which can differ. Usually it is a STRING or
     *               ARRAY.
     */
    public function init($sModule, $sProperty)
    {
        if (!file_exists(PHPFOX_DIR_MODULE . $sModule . PHPFOX_DS . 'include'
            . PHPFOX_DS . 'phpfox.class.php')
        ) {
            return false;
        }

        require_once(PHPFOX_DIR_MODULE . $sModule . PHPFOX_DS . 'include'
            . PHPFOX_DS . 'phpfox.class.php');

        if (function_exists('property_exists')) {
            $bHasProperty = property_exists('Module_' . $sModule . '',
                $sProperty);
        } else {
            $aVars = get_class_vars('Module_' . $sModule . '');

            $bHasProperty = array_key_exists($sProperty, $aVars);
        }

        if (!$bHasProperty) {
            return false;
        }

        $mData = null;
        eval('$mData = Module_' . $sModule . '::$' . $sProperty . ';');

        return $mData;
    }

    /**
     * Loads a init class file that each module has and executes a specific
     * method.
     *
     * @param string $sModule Module name/ID.
     * @param string $sMethod Method name.
     *
     * @return mixed NULL if property value or module is not valid or the
     *               property value which can differ.
     */
    public function initMethod($sModule, $sMethod)
    {
        if (!file_exists(PHPFOX_DIR_MODULE . $sModule . PHPFOX_DS . 'include'
            . PHPFOX_DS . 'phpfox.class.php')
        ) {
            return null;
        }

        require_once(PHPFOX_DIR_MODULE . $sModule . PHPFOX_DS . 'include'
            . PHPFOX_DS . 'phpfox.class.php');

        if (!method_exists('Module_' . $sModule . '', $sMethod)) {
            return null;
        }

        $sObject = 'Module_' . $sModule . '';

        $oObject = new $sObject();

        return $oObject->$sMethod();
    }

    /**
     * Checks if a block is hidden.
     *
     * @param string $sBlockId Block name.
     *
     * @return bool TRUE is hidden, FALSE is not hidden.
     */
    public function blockIsHidden($sBlockId)
    {
        return ((isset($this->_aItemDataCache[$sBlockId]['is_hidden'])
            && $this->_aItemDataCache[$sBlockId]['is_hidden']) ? true : false);
    }

    /**
     * Gets any custom component settings for a specific user.
     *
     * @param int $iUserId User ID
     * @param string $sVarName Var name for the setting.
     * @param mixed $mDefaultValue Default value in case the setting is not
     *                              found.
     *
     * @return mixed Setting value or default value is returned.
     */
    public function getComponentSetting($iUserId, $sVarName, $mDefaultValue)
    {
        static $aSettings = null;

        if ($aSettings === null) {
            $sCacheId = Phpfox::getLib('cache')->set(['csetting', $iUserId]);

            $aSettings = [];
            if (false === ($aSettings = Phpfox::getLib('cache')->get($sCacheId))) {
                $aRows = Phpfox_Database::instance()
                    ->select('var_name, user_value')
                    ->from(Phpfox::getT('component_setting'))
                    ->where('user_id = ' . (int)$iUserId)
                    ->execute('getSlaveRows');

                foreach ($aRows as $aRow) {
                    $aSettings[$aRow['var_name']] = $aRow['user_value'];
                }

                Phpfox::getLib('cache')->save($sCacheId, $aRows);
                Phpfox::getLib('cache')->group('setting', $sCacheId);
            }
        }
        if (is_array($aSettings)) {
            foreach ($aSettings as $aSetting) {
                if (isset($aSetting['var_name'])
                    && $aSetting['var_name'] == $sVarName
                    && isset($aSetting['user_value'])
                ) {
                    return $aSetting['user_value'];
                }
            }
        }

        return (isset($aSettings[$sVarName]) ? $aSettings[$sVarName]
            : $mDefaultValue);
    }

    /**
     * Cache all the active modules based on the package the client is using.
     *
     */
    public function _cacheModules()
    {
        $oCache = Phpfox::getLib('cache');
        $iCachedId = $oCache->set('module');
        if (!($this->_aModules = $oCache->getLocalFirst($iCachedId))) {
            $this->_aModules = [];
            $aRows = Phpfox_Database::instance()->select('m.module_id')
                ->from(Phpfox::getT('module'), 'm')
                ->join(Phpfox::getT('product'), 'p', 'm.product_id = p.product_id AND p.is_active = 1')
                ->where('m.is_active = 1')
                ->order('m.module_id')
                ->execute('getRows');

            if (!defined('PHPFOX_PACKAGE_ID')) {
                if (file_exists(PHPFOX_DIR_SETTINGS . 'license.sett.php')) {
                    require_once PHPFOX_DIR_SETTINGS . 'license.sett.php';
                }
            }
            $excludeApps = [];
            if (defined('PHPFOX_PACKAGE_ID')) {
                $excludeApps = $this->getExcludedModulesAppsByPackageId(PHPFOX_PACKAGE_ID);
            }
            $portingAppIds = $this->getPortingAppIds();
            $hasDisable = false;
            foreach ($aRows as $aRow) {
                $moduleId = $aRow['module_id'];
                if (in_array($moduleId, $excludeApps)) {
                    if (!defined('PHPFOX_INSTALLER')) {
                        Phpfox_Database::instance()->update(':module', ['is_active' => '0'], 'module_id = \'' . $aRow['module_id'] . '\'');
                        Phpfox_Database::instance()->update(':apps', ['is_active' => '0'], 'apps_id=\'' . $portingAppIds[$moduleId] . '\'');
                        $hasDisable = true;
                    }
                    continue;
                }
                $this->_aModules[$moduleId] = $moduleId;
            }

            // clear cache
            if ($hasDisable) {
                Phpfox::getLib('cache')->remove();
                Phpfox::getLib('template.cache')->remove();
                Phpfox::getLib('cache')->removeStatic();
            }

            if (empty($this->_aModules) || !is_array($this->_aModules)) {
                return Phpfox_Error::trigger("Cannot load modules from database", E_USER_ERROR);
            }
            $oCache->saveBoth($iCachedId, $this->_aModules);
            $oCache->group('module', $iCachedId);
        }
    }

    /**
     * @param bool $refresh
     *
     * @return \array[]
     */
    public function getAllAppFromDatabase($refresh = false)
    {
        if ($refresh) {
            return $aRows = \Phpfox::getLib('database')
                ->select('*')
                ->from(':apps')
                ->execute('getSlaveRows');
        }
        return get_from_cache('lib_all_apps', function () {
            return $aRows = \Phpfox::getLib('database')
                ->select('*')
                ->from(':apps')
                ->execute('getSlaveRows');
        });
    }

    /**
     * @param $iPackageId
     *
     * @return array
     */
    public function getExcludedModulesAppsByPackageId($iPackageId)
    {
        $exApps = [
            'phpFox_Single_Device_Login',
            'phpFox_RESTful_API',
            'Core_MobileApi',
            'phpFox_Backup_Restore'
        ];
        if ($iPackageId == 1) {
            $exApps = array_merge($exApps, [
                'blog',
                'Core_Blogs',
                'poll',
                'Core_Polls',
                'quiz',
                'Core_Quizzes',
                'forum',
                'Core_Forums',
                'subscribe',
                'Core_Subscriptions',
                'marketplace',
                'Core_Marketplace',
                'ad',
                'Core_BetterAds',
                'PHPfox_Videos',
                'PHPfox_IM',
                'phpFox_Shoutbox'
            ]);
        } elseif ($iPackageId == 2) {
            $exApps = array_merge($exApps, [
                'subscribe',
                'Core_Subscriptions',
                'marketplace',
                'Core_Marketplace',
                'ad',
                'Core_BetterAds',
                'PHPfox_Videos',
                'PHPfox_IM',
                'phpFox_Shoutbox'
            ]);
        } else {
            $exApps = [];
        }

        return $exApps;
    }

    public function getPortingAppIds()
    {
        return [
            'activitypoint' => 'Core_Activity_Points',
            'announcement' => 'Core_Announcement',
            'ad' => 'Core_BetterAds',
            'blog' => 'Core_Blogs',
            'captcha' => 'Core_Captcha',
            'comment' => 'Core_Comments',
            'egift' => 'Core_eGifts',
            'event' => 'Core_Events',
            'facebook' => 'PHPfox_Facebook',
            'forum' => 'Core_Forums',
            'groups' => 'PHPfox_Groups',
            'marketplace' => 'Core_Marketplace',
            'mail' => 'Core_Messages',
            'music' => 'Core_Music',
            'newsletter' => 'Core_Newsletter',
            'pages' => 'Core_Pages',
            'photo' => 'Core_Photos',
            'poke' => 'Core_Poke',
            'poll' => 'Core_Polls',
            'quiz' => 'Core_Quizzes',
            'rss' => 'Core_RSS',
            'subscribe' => 'Core_Subscriptions',
            'v' => 'PHPfox_Videos',
        ];
    }

    /**
     * Cache all module blocks.
     *
     */
    private function _cacheModuleBlocks()
    {
        $oCache = Phpfox::getLib('cache');
        $aStyleInUse = Phpfox_Template::instance()->getStyleInUse();
        if (!isset($aStyleInUse['style_id'])) {
            $aStyleInUse['style_id'] = 0;
        }

        // get user group id
        $userGroupId = Phpfox::getUserBy('user_group_id');

        $iBlockCacheId = $oCache->set('block_all_' . $userGroupId);
        $sMoveBlockId = $oCache->set('block_move_' . $userGroupId);
        $sSourceCodeBlockId = $oCache->set('block_source_code_' . $userGroupId);
        $app_source_id = $oCache->set('block_app_code_' . $userGroupId);

        if ((false === ($this->_aModuleBlocks = $oCache->getLocalFirst($iBlockCacheId)))
            || (false === ($this->_aMoveBlocks = $oCache->getLocalFirst($sMoveBlockId)))
            || (false === ($this->_aBlockWithSource = $oCache->getLocalFirst($sSourceCodeBlockId)))
            || (false === ($this->_block_app = $oCache->getLocalFirst($app_source_id)))
        ) {
            $aRows = Phpfox_Database::instance()
                ->select('b.block_id, b.title, b.type_id, b.ordering, b.m_connection, b.component, b.location, b.disallow_access, b.can_move, m.module_id, bs.source_parsed, bs.source_code, b.params, c.allowed_connections')
                ->from(Phpfox::getT('block'), 'b')
                ->leftJoin(Phpfox::getT('component'), 'c', 'c.component = b.component AND c.module_id = b.module_id')
                ->leftJoin(Phpfox::getT('block_source'), 'bs', 'bs.block_id = b.block_id')
                ->leftJoin(Phpfox::getT('module'), 'm', 'b.module_id = m.module_id AND m.is_active = 1')
                ->leftJoin(Phpfox::getT('product'), 'p', 'b.product_id = p.product_id AND p.is_active = 1')
                ->where('b.is_active = 1')
                ->order('b.ordering ASC')
                ->execute('getRows');
            foreach ($aRows as $aRow) {
                if (!empty($aRow['disallow_access'])) {
                    if (in_array($userGroupId, unserialize($aRow['disallow_access']))) {
                        continue;
                    }
                }

                if (!empty($aRow['allowed_connections'])) {
                    $aRow['allowed_connections'] = unserialize($aRow['allowed_connections']);
                }

                if (Phpfox::getLib('parse.format')->isSerialized($aRow['location'])) {
                    $aLocations = unserialize($aRow['location']);
                    $aRow['location'] = $aLocations['g'];
                    if (isset($aLocations['s'][$aStyleInUse['style_id']])) {
                        $aRow['location'] = $aLocations['s'][$aStyleInUse['style_id']];
                    }
                }

                // get block id
                $blockID = $aRow['block_id'];
                if ($aRow['type_id'] > 0) {
                    if ($aRow['type_id'] == '5') {
                        $this->_block_app[$aRow['m_connection']][$aRow['location']][$blockID] = $aRow['component'];
                    } else {
                        $this->_aBlockWithSource[$aRow['m_connection']][$aRow['location']][$blockID] = true;
                    }
                    $sArrayName = $blockID;
                } else {
                    $sArrayName = $aRow['module_id'] . '.' . $aRow['component'];
                }

                $aParams = json_decode($aRow['params'], true);
                $aRowParams = array_merge((empty($aRow['params']) ? [] : $aParams) ? $aParams : [],
                    [
                        'id' => $blockID,
                        'disallow_access' => $aRow['disallow_access'],
                        'title' => Phpfox_Locale::instance()->convert($aRow['title']),
                        'location' => $aRow['location'],
                        'ordering' => $aRow['ordering'],
                        'allowed_connections' => $aRow['allowed_connections'],
                    ]);
                $this->_aModuleBlocks[$aRow['m_connection']][$aRow['location']][$sArrayName][$blockID] = $aRowParams;
                $this->_aMoveBlocks[$aRow['m_connection']][$sArrayName][$blockID] = $aRow['can_move'];

                $iCacheId = $oCache->set('block_file_' . $blockID);
                $oCache->saveBoth($iCacheId, $aRow);
            }
            $oCache->saveBoth($iBlockCacheId, $this->_aModuleBlocks);
            $oCache->saveBoth($sMoveBlockId, $this->_aMoveBlocks);
            $oCache->saveBoth($sSourceCodeBlockId, $this->_aBlockWithSource);
            $oCache->saveBoth($app_source_id, $this->_block_app);
        }
    }

    public function getMoveBlocks()
    {
        return $this->_aMoveBlocks[$this->_sModule . '.' . $this->_sController];
    }
}