<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 * Template
 * Loads all templates and converts it into PHP code and then caches it.
 *
 * Class is also able to:
 * - Assign variables to templates.
 * - Identify a pages title.
 * - Identify a pages breadcrumb structure.
 * - Create meta tags.
 * - Load CSS and JavaScript files.
 *
 * @property mixed $_path_file
 * @property mixed $_url_static_script
 * @copyright         [PHPFOX_COPYRIGHT]
 * @author            phpFox LLC
 * @package           Phpfox
 * @version           $Id: template.class.php 7317 2014-05-09 17:38:54Z Fern $
 */
class Phpfox_Template
{

    const BUNDLE_CSS_FILES = 'PHPFOX_BUNDLE_CSS_FILES';

    /**
     * Default template name.
     *
     * @var string
     */
    public $sDisplayLayout = 'template';

    /**
     * Check to see if we are displaying a sample page.
     *
     * @var bool
     */
    public $bIsSample = false;

    /**
     * Theme ID#
     *
     * @var int
     */
    public $iThemeId = 0;

    /**
     * Reserved variable name. Which is $phpfox.
     *
     * @var string
     */
    protected $sReservedVarname = 'phpfox';

    /**
     * Left delimiter for custom functions. It is: {
     *
     * @var string
     */
    protected $sLeftDelim = '{';

    /**
     * Right delimiter for custom functions. It is: }
     *
     * @var string
     */
    protected $sRightDelim = '}';

    /**
     * List of plugins.
     *
     * @var array
     */
    protected $_aPlugins = [];

    /**
     * List of sections.
     *
     * @var array
     */
    private $_aSections = [];

    /**
     * List of all the variables assigned to templates.
     *
     * @var array
     */
    private $_aVars = ['bUseFullSite' => false];

    /**
     * List of titles assigned to a page.
     *
     * @var array
     */
    private $_aTitles = [];

    /**
     * List of data to add within the templates HTML <head></head>.
     *
     * @var array
     */
    private $_aHeaders = [];

    /**
     * List of breadcrumbs.
     *
     * @var array
     */
    private $_aBreadCrumbs = [];

    /**
     * Information about the title of the current page, which is part of the breadcrumb.
     *
     * @var array
     */
    private $_aBreadCrumbTitle = [];

    /**
     * @var array
     */
    private $_aCustomMenus = [];

    /**
     * Default file cache time.
     *
     * @var int
     */
    private $_iCacheTime = 60;

    /**
     * Override the layout of the current theme being used.
     *
     * @var bool
     */
    private $_sSetLayout = false;

    /**
     * Check to see if a template is part of the AdminCP.
     *
     * @var bool
     */
    private $_bIsAdminCp = false;

    /**
     * Folder of the theme being used.
     *
     * @var string
     */
    private $_sThemeFolder;

    /**
     * Theme layout to load.
     *
     * @var string
     */
    private $_sThemeLayout;

    /**
     * Folder of the style being used.
     *
     * @var string
     */
    private $_sStyleFolder;

    /**
     * List of meta data.
     *
     * @var array
     */
    private $_aMeta = [];

    /**
     * List of phrases to load and create JavaScript variables for.
     *
     * @var array
     */
    private $_aPhrases = [];

    /**
     * Information about the text editor.
     *
     * @var array
     */
    private $_aEditor = [];

    /**
     * URL of the current page we are on.
     *
     * @var string
     */
    private $_sUrl = null;

    /**
     * Information about the current theme we are using.
     *
     * @var array
     */
    private $_aTheme = [
        'theme_parent_id' => 0
    ];

    /**
     * Rebuild URL brought from cache.
     *
     * @var array
     */
    private $_aNewUrl = [];

    /**
     * Remove URL brought from cache.
     *
     * @var array
     */
    private $_aRemoveUrl = [];

    /**
     * List of images to be loaded and converted into a JavaScript object.
     *
     * @var array
     */
    private $_aImages = [];

    /**
     * Cache of all the <head></head> content being loaded.
     *
     * @var array
     */
    private $_aCacheHeaders = [];

    /**
     * Check to see if we are currently in test mode.
     *
     * @var bool
     */
    private $_bIsTestMode = false;

    /**
     * Mobile headers.
     *
     * @var array
     */
    private $_aMobileHeaders = [];

    /**
     * Holds section menu information
     *
     * @var array
     */
    private $_aSectionMenu = [];


    private $_sFooter = '';

    /**
     * @var array
     */
    private $_aDirectoryNames = [];

    /**
     * Static variable of the current theme folder.
     *
     * @static
     * @var string
     */
    protected static $_sStaticThemeFolder = null;

    /**
     * @var array
     */
    private $_aBundleScripts = [];

    /**
     * @var string
     */
    private $_sAddScripts = '';

    private $_theme;
    private $_meta;
    private $_keepBody = false;
    private $_subMenu = [];
    private $_loadedHeader = false;

    private $_sTemplateGetTemplatePassPlugin;

    public $delayedHeaders = [];

    /**
     * Class constructor we use to build the current theme and style
     * we are using.
     *
     */
    public function __construct()
    {
        $this->_sThemeLayout = 'frontend';
        $this->_sThemeFolder = 'default';
        $this->_sStyleFolder = 'default';
        $this->_path_file = 'PF.Base/';
        $this->_url_static_script = 'PF.Base/static/jscript/';


        if (defined('PHPFOX_INSTALLER')) {
            $this->_sThemeLayout = 'install';
        } else {
            $this->_theme = new Core\Theme();
            $this->_bIsAdminCp = (strtolower(Phpfox_Request::instance()->get('req1')) == 'admincp');

            $theme = $this->_theme->get();

            if (!empty($theme->folder)) {
                $this->_sThemeFolder = $theme->folder;
            }

            if ($this->_bIsAdminCp) {
                $this->_sThemeLayout = 'adminpanel';
                $this->_sThemeFolder = 'default';
                $this->_sStyleFolder = 'default';
            }
        }

        self::$_sStaticThemeFolder = $this->_sThemeFolder;
        $this->_sTemplateGetTemplatePassPlugin = Phpfox_Plugin::get('template_gettemplate_pass');
    }

    /**
     * @return Phpfox_Template
     */
    public static function instance()
    {
        return Phpfox::getLib('template');
    }

    /**
     * @param array $dirs add dirs
     *
     * @return $this
     */
    public function addDirectoryNames($dirs)
    {
        foreach ($dirs as $name => $dir) {
            $this->_aDirectoryNames[$name] = $dir;
        }

        return $this;
    }

    public function getDirectoryNames()
    {
        return $this->_aDirectoryNames;
    }

    /**
     * Sets all the images we plan on using within JavaScript.
     *
     * PHP usage:
     * <code>
     * Phpfox_Template::instance()->setImage(array('layout_sample_image', 'layout/sample.png'));
     * </code>
     *
     * In JavaScript the above image can be accessed by:
     * <code>
     * oJsImages['layout_sample_image'];
     * </code>
     *
     * @param array $aImages
     *
     * @return object
     */
    public function setImage($aImages)
    {
        foreach ($aImages as $sKey => $sImage) {
            $this->_aImages[$sKey] = $this->getStyle('image', $sImage);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getImages()
    {
        return $this->_aImages;
    }


    /**
     * @return \Core\Theme
     */
    public function theme()
    {
        return $this->_theme;
    }

    /**
     * Get the current theme we are using.
     *
     * @return string
     */
    public function getThemeLayout()
    {
        return $this->_sThemeLayout;
    }

    /**
     * Get the cached information about the theme we are using.
     *
     * @return array
     */
    public function getThemeCache()
    {
        return $this->_aTheme;
    }

    /**
     * Override the current theme.
     *
     * @param array $aTheme ARRAY of values to override.
     */
    public function setStyle($aTheme)
    {
        $this->_sThemeFolder = $aTheme['theme_folder_name'];
        $this->_sStyleFolder = $aTheme['style_folder_name'];
        $this->_aTheme = $aTheme;

        self::$_sStaticThemeFolder = $this->_sThemeFolder;
    }

    /**
     * Get all the information regarding the theme/style we are using.
     *
     * @return array
     */
    public function getStyleInUse()
    {
        return $this->_aTheme;
    }

    /**
     * Get the total number of columns this template supports.
     *
     * @return int
     */
    public function columns()
    {
        return (int)(isset($this->_aTheme['total_column']) ? $this->_aTheme['total_column'] : 3);
    }

    /**
     * Test a style by attempting to load and display it for the user.
     * This is used when a user is trying to demo a style.
     *
     * @param int $iId ID of the style.
     *
     * @return bool TRUE if style can be loaded, FALSE if not.
     */
    public function testStyle($iId = null)
    {
        $sWhere = '';
        if ($iId === null) {
            $sWhere = 't.is_default = 1 AND s.is_default = 1';
        } else {
            $sWhere = 's.style_id = ' . (int)$iId;
        }
        $aTheme = Phpfox_Database::instance()->select('s.style_id, s.parent_id AS style_parent_id, s.folder AS style_folder_name, t.folder AS theme_folder_name, t.parent_id AS theme_parent_id')
            ->from(Phpfox::getT('theme_style'), 's')
            ->join(Phpfox::getT('theme'), 't', 't.theme_id = s.theme_id')
            ->where($sWhere)
            ->execute('getRow');

        if (!isset($aTheme['style_id'])) {
            return false;
        }

        $this->_sThemeFolder = $aTheme['theme_folder_name'];
        $this->_sStyleFolder = $aTheme['style_folder_name'];

        if ($aTheme['style_parent_id'] > 0) {
            $aStyleExtend = Phpfox_Database::instance()->select('folder AS parent_style_folder')
                ->from(Phpfox::getT('theme_style'))
                ->where('style_id = ' . $aTheme['style_parent_id'])
                ->execute('getRow');

            if (isset($aStyleExtend['parent_style_folder'])) {
                $aTheme['parent_style_folder'] = $aStyleExtend['parent_style_folder'];
            }
        }

        if ($aTheme['theme_parent_id'] > 0) {
            $aThemeExtend = Phpfox_Database::instance()->select('folder AS parent_theme_folder')
                ->from(Phpfox::getT('theme'))
                ->where('theme_id = ' . $aTheme['theme_parent_id'])
                ->execute('getRow');

            if (isset($aThemeExtend['parent_theme_folder'])) {
                $aTheme['parent_theme_folder'] = $aThemeExtend['parent_theme_folder'];
            }
        }

        $this->_aTheme = $aTheme;
        $this->_bIsTestMode = true;

        self::$_sStaticThemeFolder = $this->_sThemeFolder;

        return true;
    }

    /**
     * Get the theme folder being used.
     *
     * @return string
     */
    public function getThemeFolder()
    {
        return $this->_sThemeFolder;
    }

    /**
     * Get the parent theme folder being used.
     * Issue:  http://www.phpfox.com/tracker/view/15384/
     *
     * @return string
     */
    public function getParentThemeFolder()
    {
        if (isset($this->_aTheme['parent_theme_folder'])) {
            return $this->_aTheme['parent_theme_folder'];
        } else {
            return $this->_sThemeFolder;
        }
    }

    /**
     * Get the style folder being used.
     *
     * @return string
     */
    public function getStyleFolder()
    {
        return $this->_sStyleFolder;
    }

    /**
     * Get the logo for the site based on the style being used.
     *
     * @return string
     */
    public function getStyleLogo()
    {
        return '';
    }

    /**
     * Override the layout of the site.
     *
     * @param string $sName Layout we should load.
     */
    public function setLayout($sName)
    {
        $this->_sSetLayout = $sName;
    }

    /**
     * Sets phrases we can later use in JavaScript.
     *
     * @param array $mPhrases ARRAY of phrase to build.
     *
     * @return $this
     */
    public function setPhrase($mPhrases)
    {
        foreach ($mPhrases as $sVar) {
            //Support for setPhrase in old way.
            $aVarName = explode('.', $sVar);
            if (isset($aVarName[1])) {
                $sVarGet = $aVarName[1];
            } else {
                $sVarGet = $sVar;
            }

            $sPhrase = _p($sVarGet);
            $sPhrase = str_replace("'", '&#039;', $sPhrase);
            if (preg_match("/\n/i", $sPhrase)) {
                $aParts = explode("\n", $sPhrase);
                $sPhrase = '';
                foreach ($aParts as $sPart) {
                    $sPart = trim($sPart);
                    if (empty($sPart)) {
                        $sPhrase .= '\n ';
                        continue;
                    }
                    $sPhrase .= $sPart . ' ';
                }
                $sPhrase = trim($sPhrase);
            }
            $this->_aPhrases[$sVar] = $sPhrase;
        }

        return $this;
    }

    /**
     * Get all the phrases set by a controller
     *
     * @return array ARRAY of phrases
     */
    public function getPhrases()
    {
        $aPhrases = (array)$this->_aPhrases;
        foreach ($aPhrases as $sVar => $sPhrase) {
            $aPhrases[$sVar] = html_entity_decode($sPhrase, ENT_QUOTES, 'UTF-8');
        }
        return $aPhrases;
    }

    /**
     * Sets the breadcrumb structure for the site.
     *
     * @param string $sPhrase  Breadcrumb title.
     * @param string $sLink    Breadcrumb link.
     * @param bool   $bIsTitle TRUE if this is the title breadcrumb for the page.
     *
     * @return $this
     */
    public function setBreadCrumb($sPhrase, $sLink = '', $bIsTitle = false)
    {
        (($sPlugin = Phpfox_Plugin::get('template_template_setbreadcrump')) ? eval($sPlugin) : false);

        if (is_array($sPhrase)) {
            foreach ($sPhrase as $aPhrase) {
                if (isset($aPhrase[0])) {
                    $aPhrase[0] = _p($aPhrase[0]);
                }
                ((isset($aPhrase[2]) && $aPhrase[2]) ? $this->_aBreadCrumbTitle = [$aPhrase[0], $aPhrase[1]] : $this->_aBreadCrumbs[$aPhrase[1]] = $aPhrase[0]);
            }
            return $this;
        }

        if ($bIsTitle === true) {
            $sPhrase = _p($sPhrase);
            $this->_aBreadCrumbTitle = [Phpfox_Locale::instance()->convert($sPhrase), $sLink];
            if (!empty($sLink)) {
                $this->setMeta('og:url', $sLink);
            }
        }

        if (!defined('PHPFOX_INSTALLER')) {
            $this->_aBreadCrumbs[$sLink] = Phpfox_Locale::instance()->convert($sPhrase);
        }
        return $this;
    }

    /**
     * Get all the breadcrumbs we have loaded so far.
     *
     * @return array
     */
    public function getBreadCrumb()
    {
        if (count($this->_aBreadCrumbTitle)) {
            foreach ($this->_aBreadCrumbs as $sKey => $mValue) {
                if ($sKey === $this->_aBreadCrumbTitle[1]) {
                    unset($this->_aBreadCrumbs[$sKey]);
                }
            }

            if (isset($this->_aBreadCrumbTitle[1])) {
                $this->setMeta('canonical', $this->_aBreadCrumbTitle[1]);
            }
        }

        if (count($this->_aBreadCrumbs) === 1 && !count($this->_aBreadCrumbTitle)) {
            $sKey = array_keys($this->_aBreadCrumbs);
            $this->setMeta('canonical', $sKey[0]);
        }

        $this->assign([
            'aAdmincpBreadCrumb' => $this->_aBreadCrumbs,
            'sLastBreadcrumb'    => $this->getPageTitleFromBreadcrumbs()
        ]);

        if (isset($this->_aBreadCrumbTitle[0])) {
            $this->_aBreadCrumbTitle[0] = Phpfox::getLib('parse.output')->clean($this->_aBreadCrumbTitle[0]);
        }

        return [$this->_aBreadCrumbs, $this->_aBreadCrumbTitle];
    }

    public function getPageTitleFromBreadcrumbs()
    {
        if (!empty($this->_aBreadCrumbTitle) && is_array($this->_aBreadCrumbTitle)) {
            $sLastBreadcrumb = reset($this->_aBreadCrumbTitle);
        } else {
            $sLastBreadcrumb = end($this->_aBreadCrumbs);
        }

        return $sLastBreadcrumb;
    }

    /**
     * Clear the breadcrumb information.
     * @return $this
     */
    public function clearBreadCrumb()
    {
        $this->_aBreadCrumbs = [];
        $this->_aBreadCrumbTitle = [];
        return $this;
    }

    public function errorClearAll()
    {
        $this->clearBreadCrumb();
        $this->_aTitles = [];
    }

    /**
     * Set the page title in a public array so we can get it later
     * and display within the template.
     *
     * @param string $sTitle Title to display on a specific page
     * @param bool   $bReset
     *
     * @return Phpfox_Template
     * @see getTitle()
     *
     */
    public function setTitle($sTitle, $bReset = false)
    {
        if ($bReset) {
            $this->_aTitles = [];
        }

        $this->_aTitles[] = Phpfox::getLib('parse.output')->cleanPhrases($sTitle);

        $this->setMeta('og:site_name', Phpfox::getParam('core.site_title'));
        $this->setMeta('og:title', Phpfox::getLib('parse.output')->clean($sTitle));

        return $this;
    }

    public function setSectionTitle($sSectionTitle)
    {
        $this->assign('sSectionTitle', $sSectionTitle);

        return $this;
    }

    public function setSectionMenu($aMenu)
    {
        $this->assign('aSectionMenu', $aMenu);

        return $this;
    }

    public function setActionMenu($aMenu)
    {
        foreach ($aMenu as &$menu) {
            if (is_string($menu)) {
                $menu = [
                    'url'   => $menu,
                    'class' => 'btn-primary'
                ];
                continue;
            }

            if (!isset($menu['class'])) {
                $menu['class'] = 'btn-primary';
            } else if (strpos($menu['class'], 'btn-') === false) {
                $menu['class'] .= ' btn-primary';
            }
            $menu['dropdown_class'] = preg_replace('/btn\S*/i', '', $menu['class']);
        }

        $this->assign([
            'aActionMenu'            => $aMenu,
            'bMoreThanOneActionMenu' => count($aMenu) > 1
        ]);

        return $this;
    }

    /**
     * Set the current template for the site.
     *
     * @param string $sLayout Template name.
     *
     * @return $this
     */
    public function setTemplate($sLayout)
    {
        $this->sDisplayLayout = $sLayout;

        return $this;
    }

    /**
     * All data placed between the HTML tags <head></head> can be added with this method.
     * Since we rely on custom templates we need the header data to be custom as well. Current
     * support is for: css & JavaScript
     * All HTML added here is coded under XHTML standards.
     *
     * @access public
     *
     * @param array|string $mHeaders
     *
     * @return $this
     */
    public function setHeader($mHeaders, $mValue = null)
    {
        if (!$this->_loadedHeader) {
            $this->_loadedHeader = true;
            $this->setHeader('cache', Phpfox::getMasterFiles());
        }

        if ($mHeaders == 'cache') {
            $this->_aHeaders[] = $mValue;
        } else {
            if ($mValue !== null) {
                if ($mHeaders == 'head') {
                    $mHeaders = [$mValue];
                } else {
                    $mHeaders = [$mHeaders => $mValue];
                }
            }

            $this->_aHeaders[] = $mHeaders;
        }

        return $this;
    }

    /**
     * All data placed between the HTML tags <head></head> can be added with this method for mobile devices.
     * Since we rely on custom templates we need the header data to be custom as well. Current
     * support is for: css & JavaScript
     * All HTML added here is coded under XHTML standards.
     *
     * @access public
     *
     * @param string|array $mHeaders
     *
     * @return $this
     */
    public function setMobileHeader($mHeaders, $mValue = null)
    {
        if ($mValue !== null) {
            $mHeaders = [$mHeaders => $mValue];
        }

        $this->_aMobileHeaders[] = $mHeaders;

        return $this;
    }

    /**
     * Set settings for the text editor in use.
     *
     * @param array $aParams ARRAY of settings.
     *
     * @return $this
     */
    public function setEditor($aParams = [])
    {
        if (count($aParams)) {
            $this->_aEditor = $aParams;
        }

        $this->_aEditor['active'] = true;
        $this->_aEditor['toggle_image'] = $this->getStyle('image', 'editor/fullscreen.png');
        $this->_aEditor['toggle_phrase'] = _p('toggle_fullscreen');

        (($sPlugin = Phpfox_Plugin::get('set_editor_end')) ? eval($sPlugin) : false);

        $this->setHeader('cache', [
                'editor.js'               => 'static_script',
                'wysiwyg/default/core.js' => 'static_script'
            ]
        );

        return $this;
    }

    public function getActualTitle()
    {
        $title = '';
        foreach ($this->_aTitles as $sTitle) {
            $title .= Phpfox_Parse_Output::instance()->clean($sTitle) . ' ' . Phpfox::getParam('core.title_delim') . ' ';
        }
        $title = trim($title);
        $title = rtrim(preg_replace('/' . (Phpfox::getParam('core.title_delim')) . '$/', '', $title));

        return $title;
    }

    /**
     * Get the title for the current page behind displayed.
     * All titles are added earlier in the script using self::setTitle().
     * Each title is split with a delimiter specified from the Admin CP.
     *
     * @return string $sData Full page title including delimiter
     * @see setTitle()
     */
    public function getTitle()
    {
        $oFilterOutput = Phpfox::getLib('parse.output');

        (($sPlugin = Phpfox_Plugin::get('template_gettitle')) ? eval($sPlugin) : false);

        $sData = '';
        foreach ($this->_aTitles as $sTitle) {
            $sData .= $oFilterOutput->clean($sTitle) . ' ' . Phpfox::getParam('core.title_delim') . ' ';
        }

        if (!Phpfox::getParam('core.include_site_title_all_pages') && !Phpfox::isAdminPanel()) {
            $sData .= (defined('PHPFOX_INSTALLER') ? Phpfox::getParam('core.global_site_title') : Phpfox_Locale::instance()->convert(Phpfox::getParam('core.global_site_title')));
        } else {
            $sData = trim(rtrim(trim($sData), Phpfox::getParam('core.title_delim')));
            if (empty($sData)) {
                $sData = (defined('PHPFOX_INSTALLER') ? Phpfox::getParam('core.global_site_title') : Phpfox_Locale::instance()->convert(Phpfox::getParam('core.global_site_title')));
            }
        }

        $sSort = Phpfox_Request::instance()->get('sort');
        if (!empty($sSort)) {
            $mSortName = Phpfox_Search::instance()->getPhrase('sort', $sSort);
            if ($mSortName !== false) {
                $sData .= ' ' . Phpfox::getParam('core.title_delim') . ' ' . $mSortName[1];
            }
        }

        if (!Phpfox::getParam('core.branding')) {
            $sData .= ' - ' . Phpfox::link(false, false) . '';
        }

        return $sData;
    }

    /**
     * Gets all the keywords from a string.
     *
     * @param string $sTitle Title to parse.
     *
     * @return string Splits all the keywords from a title.
     */
    public function getKeywords($sTitle)
    {
        $sTitle = Phpfox_Locale::instance()->convert($sTitle);
        $aWords = explode(' ', $sTitle);
        $sKeywords = '';
        foreach ($aWords as $sWord) {
            if (empty($sWord)) {
                continue;
            }

            if (strlen($sWord) < 2) {
                continue;
            }

            $sKeywords .= $sWord . ',';
        }
        $sKeywords = rtrim($sKeywords, ',');

        return $sKeywords;
    }


    /**
     * Optimize: Cache per request because this function called average 14 times/ request
     * @var null
     */
    private $aSiteMetas = null;

    private function getSiteMetas()
    {
        if ($this->aSiteMetas === null) {
            $this->aSiteMetas = Phpfox::getService('admincp.seo')->getSiteMetas();
        }
        return $this->aSiteMetas;
    }

    /**
     * Set all the meta tags to be used on the site.
     *
     * @param array|string $mMeta  ARRAY of meta tags.
     * @param string       $sValue Value of meta tags in case the 1st argument is a string.
     *
     * @return $this
     */
    public function setMeta($mMeta, $sValue = null)
    {
        $sValue = Phpfox::getLib('parse.output')->cleanPhrases($sValue);
        if (!is_array($mMeta)) {
            $mMeta = [$mMeta => $sValue];
        }

        if (isset($mMeta['keywords'])) {
            // Optimize performance
            $aSiteMetas = $this->getSiteMetas();

            $sThisController = Phpfox::getService('admincp.seo')->getUrl((isset($_GET[PHPFOX_GET_METHOD]) ? $_GET[PHPFOX_GET_METHOD] : '/'));

            if (is_array($aSiteMetas) && !empty($aSiteMetas)) {
                // make sure this is the right controller
                foreach ($aSiteMetas as $iKey => $aSiteMeta) {
                    if (empty($aSiteMeta['url']) || strpos($sThisController, $aSiteMeta['url']) === false) {
                        unset($aSiteMetas[$iKey]);
                    }
                }

                if (!empty($sThisController)) {
                    // remove the general keywords
                    $mMeta['keywords'] = [str_replace(Phpfox::getParam('core.keywords'), '', $mMeta['keywords'])];
                    // check each new custom keywords
                    foreach ($aSiteMetas as $aSiteMeta) {
                        // keywords
                        if ($aSiteMeta['type_id'] == 0) {
                            $mMeta['keywords'][] = $aSiteMeta['content'];
                        }
                    }
                    $mMeta['keywords'] = join(',', $mMeta['keywords']);
                }
            }
        }
        // end

        foreach ($mMeta as $sKey => $sValue) {
            if ($sKey == 'og:url') {
                $this->_aMeta[$sKey] = $sValue;
                return $this;
            }

            if (isset($this->_aMeta[$sKey])) {
                $this->_aMeta[$sKey] .= ($sKey == 'keywords' ? ', ' : ' ') . $sValue;
            } else {
                $this->_aMeta[$sKey] = $sValue;
            }
            $this->_aMeta[$sKey] = ltrim($this->_aMeta[$sKey], ', ');
        }

        return $this;
    }

    /**
     * Clear all the meta tags to be used on the site.
     *
     * @param array|string $mMeta ARRAY of meta tags.
     *
     * @return $this
     */
    public function clearMeta($mMeta)
    {
        if (!is_array($mMeta)) {
            $mMeta = [$mMeta];
        }

        foreach ($mMeta as $sName) {
            unset($this->_aMeta[$sName]);
        }
        return $this;
    }

    /**
     * Gets any data we plan to place within the HTML tags <head></head> for mobile devices.
     * This method also groups the data to give the template a nice clean look.
     *
     * @return string $sData Returns the HTML data to be placed within <head></head>
     */
    public function getMobileHeader()
    {
        return $this->getHeader();
    }

    /**
     * Gets a 32 string character of the version of the static files
     * on the site.
     *
     * @return string 32 character MD5 sum
     */
    public function getStaticVersion()
    {
        $sVersion = md5((((defined('PHPFOX_NO_CSS_CACHE') && PHPFOX_NO_CSS_CACHE) || $this->_bIsTestMode === true) ? PHPFOX_TIME : Phpfox::getId() . Phpfox::getBuild()) . (defined('PHPFOX_INSTALLER') ? '' : '-' . Phpfox::getParam('core.css_edit_id') . Phpfox::getBuild() . '-' . $this->_sThemeFolder . '-' . $this->_sStyleFolder));

        (($sPlugin = Phpfox_Plugin::get('template_getstaticversion')) ? eval($sPlugin) : false);

        return $sVersion;
    }

    public function setPageMeta($meta)
    {
        $this->_meta = $meta;
    }

    public function getPageMeta()
    {
        return $this->_meta;
    }

    /**
     * @return string[]
     */
    public function loadBundleFiles()
    {
        $aResults = [];
        $aBundleScripts = [];
        ($sPlugin = Phpfox_Plugin::get('bundle__start')) ? eval($sPlugin) : false;
        foreach ($aBundleScripts as $mKey => $mValue) {
            if (is_string($mValue)) {
                $aResults[] = $this->_getBundleScript($mKey, $mValue);
            } else if (is_array($mValue)) {
                foreach ($mValue as $key => $value) {
                    $aResults[] = $this->_getBundleScript($key, $value);
                }
            }
        }

        $aResults = array_unique(array_filter($aResults, function ($file) {
            return file_exists(PHPFOX_PARENT_DIR . $file);
        }));

        return $aResults;
    }

    public function checkBundles()
    {
        $cache = Phpfox::getLib('cache');
        $sId = $cache->set('template_check_bundle');

        if ($this->_aBundleScripts) {
            return $this->_aBundleScripts;
        }

        $cache->remove($sId);

        if (!($this->_aBundleScripts = $cache->getLocalFirst($sId))) {

            $this->_aBundleScripts = $this->loadBundleFiles();
            $cache->saveBoth($sId, $this->_aBundleScripts);
            $cache->group('template', $sId);
        }

        return $this->_aBundleScripts;
    }

    private function _getBundleScript($mKey, $mValue)
    {
        switch ($mValue) {
            case 'style_script':
                return $this->getStyle('jscript', $mKey, null, false, false);
            case 'style_css':
                return $this->getStyle('css', $mKey, null, false, false);
            case 'static_script':
                return $this->_url_static_script . $mKey;
            case 'flavor_bootstrap':
                $Theme = $this->_theme->get();
                return 'PF.Site/flavors/' . flavor()->active->id . '/flavor/' . $Theme->flavor_folder . '.css';
            case 'flavor_boot':
                return 'PF.Base/less/version/boot.css';
            case 'flavor':
                return 'PF.Site/flavors/' . flavor()->active->id . '/assets/' . $mKey;
            default:
                if (preg_match('/app_/i', $mValue)) {
                    $aParts = explode('_', $mValue, 2);
                    if ($mKey == 'autoload.css') {
                        return 'PF.Site/Apps/' . $aParts[1] . '/assets/' . $mKey;
                    } else if ($mKey == 'autoload.js') {
                        return 'PF.Site/Apps/' . $aParts[1] . '/assets/' . $mKey;
                    } else if ('.js' == substr($mKey, -3)) {
                        return 'PF.Site/Apps/' . $aParts[1] . '/assets/' . $mKey;

                    } else if ('.css' == substr($mKey, -4)) {
                        $Theme = $this->theme()->get();
                        $sFileName = trim(str_replace(dirname($this->_path_file) . PHPFOX_DS, '', $this->getStyle('css', $mKey, $aParts[1], true, false)), '/');
                        return $Theme->getCssFileName($sFileName, 'app');
                    }
                } else if (preg_match('/flavors_/i', $mValue)) {
                    $aParts = explode('_', $mValue, 2);
                    if ($mKey == 'autoload.css') {
                        return 'PF.Site/flavors/' . $aParts[1] . '/assets/' . $mKey;
                    } else if ($mKey == 'autoload.js') {
                        return 'PF.Site/flavors/' . $aParts[1] . '/assets/' . $mKey;
                    } else if ('.js' == substr($mKey, -3)) {
                        return 'PF.Site/flavors/' . $aParts[1] . '/assets/' . $mKey;
                    } else if ('.css' == substr($mKey, -4)) {
                        $Theme = $this->theme()->get();
                        $sFileName = trim(str_replace(dirname($this->_path_file) . PHPFOX_DS, '', $this->getStyle('css', $mKey, $aParts[1], true, false)), '/');
                        return $Theme->getCssFileName($sFileName, 'app');
                    }
                } else if (preg_match('/module_/i', $mValue)) {
                    $aParts = explode('_', $mValue);
                    if (isset($aParts[1]) && Phpfox::isModule($aParts[1])) {
                        if (substr($mKey, -3) == '.js') {
                            return $this->_path_file . 'module/' . $aParts[1] . '/static/jscript/' . $mKey;
                        } else if (substr($mKey, -4) == '.css') {
                            $Theme = $this->theme()->get();
                            return $Theme->getCssFileName(str_replace($this->_path_file, '', $this->getStyle('css', $mKey, $aParts[1], false, false)));
                        }
                    }
                }
        }
    }

    public function keepBody($param = null)
    {
        if ($param === null) {
            return $this->_keepBody;
        }

        $this->_keepBody = $param;

        return $this;
    }

    public function getBundleFiles()
    {
        $bundle = $this->_bundle();

        if ($bundle)
            $this->checkBundles();

        $homeUrl = Phpfox::getLib('assets')->getAssetBaseUrl();
        $data = array_map(function ($file) use ($homeUrl) {
            $file = str_replace(PHPFOX_PARENT_DIR, $homeUrl, $file);
            $file = str_replace(PHPFOX_DS, '/', $file);
            return $file;
        }, $this->_aBundleScripts);

        return $data;
    }

    /**
     * Gets any data we plan to place within the HTML tags <head></head>.
     * This method also groups the data to give the template a nice clean look.
     *
     * @return string|array $sData Returns the HTML data to be placed within <head></head>
     */
    public function getHeader($bReturnArray = false)
    {

        $bundle = $this->_bundle();
        $oAssets = Phpfox::getLib('assets');

        (($sPlugin = Phpfox_Plugin::get('template_getheader_start')) ? eval($sPlugin) : false);

        if ($bundle)
            $this->checkBundles();

        if (Phpfox::isAdminPanel()) {
            $this->setHeader(['custom.css' => 'style_css']);
        }

        if ($this->delayedHeaders) {
            foreach ($this->delayedHeaders as $header) {
                $this->setHeader('cache', $header);
            }
        }

        if (!defined('PHPFOX_INSTALLER')) {
            Core\Event::trigger('lib_phpfox_template_getheader', $this);
            foreach (Phpfox::getCoreApp()->all() as $App) {
                if ($App->head && is_array($App->head)) {
                    foreach ($App->head as $head) {
                        $this->setHeader($head);
                    }
                }

                if ($App->js_phrases) {
                    $phrases = [];
                    foreach ($App->js_phrases as $key => $phrase) {
                        $phrases[$key] = _p($phrase);
                    }
                    $this->setHeader('<script>var ' . str_replace('-', '_', $App->apps_dir) . '_Phrases = ' . json_encode($phrases) . ';</script>');
                }

                if ($App->settings) {
                    $Setting = new Core\Setting();
                    foreach ($App->settings as $key => $setting) {
                        if (isset($setting->js_variable)) {
                            $this->setHeader('<script>var ' . $key . ' = "' . $Setting->get($key) . '";</script>');
                        }
                    }
                }
            }
            //Check Friend enable
            if (empty($bForceShowFriend) && !Phpfox::isModule('friend')) {
                $this->setHeader("<style>#hd-request{display: none;}</style>");
            }
            // Check Message and IM Message enable
            if (empty($bForceShowMessage) && !Phpfox::isAppActive('Core_Messages') && !Phpfox::isAppActive('PHPfox_IM') && !Phpfox::isAppActive('P_ChatPlus')) {
                $this->setHeader("<style>#hd-message{display: none;}</style>");
            }
            //Check Notification enable
            if (empty($bForceShowNotification) && !Phpfox::isModule('notification')) {
                $this->setHeader("<style>#hd-notification{display: none;}</style>");
            }
            if (Phpfox::getParam('core.enable_register_with_google') && !empty(Phpfox::getParam('core.google_oauth_client_id'))) {
                $this->setHeader('<script src="//accounts.google.com/gsi/client"></script>');
                if (Phpfox::isUser() && Phpfox::getUserBy('view_id') == 0 && $oCached = storage()->get('google_user_notice_' . Phpfox::getUserId())) {
                    storage()->del('google_user_notice_' . Phpfox::getUserId());
                    $sHtml = '<div>' . _p('core_you_just_signed_up_successfully_with_email_email', ['email' => $oCached->value->email]) . '</div>';
                    $sHtml .= '<div>' . _p('core_click_here_to_change_your_password', ['link' => url('user/setting')]) . '</div>';
                    $this->setHeader('<script>var Google_show_notice = false; $Behavior.onReadyAfterLoginGoogle = function(){ setTimeout(function(){if(Google_show_notice) return; Google_show_notice = true; tb_show(\'' . _p('notice') . '\',\'\',\'\',\'' . $sHtml . '\'); $(\'#\'+$sCurrentId).find(\'.js_box_close:first\').show();},200);}</script>');
                }
            }
        }

        $aArrayData = [];
        $sData = '';
        $sJs = '';
        $iVersion = $this->getStaticVersion();
        $oUrl = Phpfox_Url::instance();
        if (!defined('PHPFOX_DESIGN_DND')) {
            define('PHPFOX_DESIGN_DND', false);
        }

        if (!PHPFOX_IS_AJAX_PAGE) {
            (($sPlugin = Phpfox_Plugin::get('template_getheader')) ? eval($sPlugin) : false);

            $sJs .= "\t\t\tvar oCore = {'core.is_admincp': " . (Phpfox::isAdminPanel() ? 'true' : 'false') . ", 'core.section_module': '" . Phpfox_Module::instance()->getModuleName() . "', 'profile.is_user_profile': " . (defined('PHPFOX_IS_USER_PROFILE') && PHPFOX_IS_USER_PROFILE ? 'true' : 'false') . ", 'log.security_token': '" . Phpfox::getService('log.session')->getToken() . "', 'core.url_rewrite': '" . Phpfox::getParam('core.url_rewrite') . "', 'core.country_iso': '" . (Phpfox::isUser() ? Phpfox::getUserBy('country_iso') : '') . "', 'core.default_currency': '" . (defined('PHPFOX_INSTALLER') ? 'USD' : Phpfox::getService('core.currency')->getDefault()) . "', 'profile.user_id': " . (defined('PHPFOX_IS_USER_PROFILE') && PHPFOX_IS_USER_PROFILE ? Phpfox::getService('profile')->getProfileUserId() : 0) . "};\n";
            // You are filtering out the controllers which should not load 'content' ajaxly, finding a way for pages.view/1/info and like that
            $sProgressCssFile = $this->getStyle('css', 'progress.css', null, false, false);
            $sStylePath = str_replace($this->_path_file, '', str_replace('progress.css', '', $sProgressCssFile));

            $aJsVars = [
                'sBaseURL'            => Phpfox::getParam('core.path'),
                'sJsHome'             => Phpfox::getParam('core.path_file'),
                'sJsHostname'         => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost',
                'sSiteName'           => Phpfox::getParam('core.site_title'),
                'sJsStatic'           => $oAssets->getAssetUrl('PF.Base/static/', false),
                'sJsStaticImage'      => $oAssets->getAssetUrl('PF.Base/static/image', false),
                'sImagePath'          => $oAssets->getAssetUrl($this->getStyle('image', null, null, false, false), false),
                'sStylePath'          => $oAssets->getAssetUrl($this->getStyle('css', null, null, false, false), false),
                'sVersion'            => Phpfox::getId(),
                'sJsAjax'             => Phpfox::getParam('core.path') . '_ajax/',
                'sStaticVersion'      => $iVersion,
                'sGetMethod'          => PHPFOX_GET_METHOD,
                'sDateFormat'         => (defined('PHPFOX_INSTALLER') ? '' : Phpfox::getParam('core.date_field_order')),
                'sGlobalTokenName'    => Phpfox::getTokenName(),
                'sController'         => Phpfox_Module::instance()->getFullControllerName(),
                'bJsIsMobile'         => false,
                'sHostedVersionId'    => (defined('PHPFOX_IS_HOSTED_VERSION') ? PHPFOX_IS_HOSTED_VERSION : ''),
                'sStoreUrl'           => Core\Home::store(),
                'iLimitLoadMore'      => Phpfox::getParam('core.no_pages_for_scroll_down', 2),
                'sJsDefaultThumbnail' => $oAssets->getAssetUrl('PF.Base/static/image/misc/thumbnail.png', false),
                'sAssetBaseUrl'        => $oAssets->getAssetBaseUrl(),
                'sAssetFileUrl'        => $oAssets->getAssetBaseUrl() . 'PF.Base/',
            ];

            if (!defined('PHPFOX_INSTALLER')) {
                $aJsVars['sJsCookiePath'] = Phpfox::getParam('core.cookie_path');
                $aJsVars['sJsCookieDomain'] = Phpfox::getParam('core.cookie_domain');
                $aJsVars['sJsCookiePrefix'] = Phpfox::getParam('core.session_prefix');
                $aJsVars['bPhotoTheaterMode'] = false;
                $aJsVars['bUseHTML5Video'] = false;
                $aJsVars['bIsAdminCP'] = Phpfox::isAdminPanel();
                $aJsVars['bIsUserLogin'] = Phpfox::isUser();
                $aJsVars['sGoogleApiKey'] = Phpfox::getParam('core.google_api_key');
                $aJsVars['sGoogleOAuthId'] = Phpfox::getParam('core.google_oauth_client_id');
                $aJsVars['iMapDefaultZoom'] = Phpfox::getParam('core.map_view_default_zoom');
                $aJsVars['sLanguage'] = Phpfox_Locale::instance()->getLangId();
                if (Phpfox::isAdmin()) {
                    $aJsVars['sAdminCPLocation'] = 'admincp';
                } else {
                    $aJsVars['sAdminCPLocation'] = '';
                }
                if (Phpfox::isModule('notification')) {
                    $aJsVars['notification.notify_ajax_refresh'] = Phpfox::getParam('notification.notify_ajax_refresh');
                }

                $sLocalDatepicker = 'PF.Base/static/jscript/jquery/locale/jquery.ui.datepicker-' . strtolower(Phpfox_Locale::instance()->getLangId()) . '.js';

                if (cached_file_exists($sLocalDatepicker)) {
                    $sFile = str_replace('PF.Base/' . PHPFOX_STATIC . 'jscript/', '', $sLocalDatepicker);
                    $this->setHeader([$sFile => 'static_script']);
                }
                if (!Phpfox::isUser() && Phpfox::getParam('core.site_is_offline')) {
                    //When site if offline and not login, turn of full ajax mode
                    $bOffFullAjaxMode = true;
                } else {
                    $bOffFullAjaxMode = Phpfox::getParam('core.turn_off_full_ajax_mode');
                }
                $aJsVars['bOffFullAjaxMode'] = $bOffFullAjaxMode ? true : false;

                // check friendship direction
                if (Phpfox::isModule('friend')) {
                    $aJsVars['sFriendshipDirection'] = Phpfox::getParam('friend.friendship_direction', 'two_way_friendships');
                }
                // allow custom gender
                $aJsVars['allowCustomGender'] = Phpfox::getUserParam('user.can_add_custom_gender');
                // enable tooltip
                $aJsVars['enableUserTooltip'] = Phpfox::getParam('user.enable_user_tooltip', 0);
            }

            (($sPlugin = Phpfox_Plugin::get('template_getheader_setting')) ? eval($sPlugin) : false);

            $sJs .= "\t\t\tvar oParams = {";
            $iCnt = 0;
            foreach ($aJsVars as $sVar => $sValue) {
                $iCnt++;
                if ($iCnt != 1) {
                    $sJs .= ",";
                }

                if (is_bool($sValue)) {
                    $sJs .= "'{$sVar}': " . ($sValue ? 'true' : 'false');
                } else if (is_numeric($sValue)) {
                    $sJs .= "'{$sVar}': " . $sValue;
                } else {
                    $sJs .= "'{$sVar}': '" . str_replace("'", "\'", (string)$sValue) . "'";
                }
            }
            $sJs .= "};\n";

            if (!defined('PHPFOX_INSTALLER')) {
                $aLocaleVars = [
                    'core.are_you_sure',
                    'core.yes',
                    'core.no',
                    'core.save',
                    'core.submit',
                    'core.cancel',
                    'core.go_advanced',
                    'core.processing',
                    'attachment.attach_files',
                    'core.close',
                    'core.language_packages',
                    'core.move_this_block',
                    'core.uploading',
                    'language.loading',
                    'core.saving',
                    'core.loading_text_editor',
                    'core.quote',
                    'core.loading',
                    'core.confirm',
                    'core.dz_default_message',
                    'core.dz_fallback_message',
                    'core.dz_fallback_text',
                    'core.dz_file_too_big',
                    'core.dz_invalid_file_type',
                    'core.dz_response_error',
                    'core.dz_cancel_upload',
                    'core.dz_cancel_upload_confirmation',
                    'core.dz_remove_file',
                    'core.dz_max_files_exceeded',
                    'core.press_esc_to_cancel_edit',
                    'core.deselect_all',
                    'core.items_selected',
                    'custom',
                    'core.this_link_leads_to_an_untrusted_site_are_you_sure_you_want_to_proceed',
                    'no_results',
                    'select_audience',
                    'are_you_sure_you_want_to_cancel_this_friend_request',
                    'are_you_sure_you_want_to_unfriend_this_user',
                    'notice',
                    'are_you_sure_you_want_to_delete_all_attachment_files',
                    'order_updated',
                    'show_password',
                    'hide_password'
                ];


                (($sPlugin = Phpfox_Plugin::get('template_getheader_language')) ? eval($sPlugin) : false);

                $sJs .= "\t\t\tvar oTranslations = {";
                $iCnt = 0;
                foreach ($aLocaleVars as $sValue) {
                    $aParts = explode('.', $sValue);

                    if (isset($aParts[1])) {
                        $sValue = $aParts[1];
                    }

                    $iCnt++;
                    if ($iCnt != 1) {
                        $sJs .= ",";
                    }

                    $sJs .= "'{$sValue}': '" . html_entity_decode(str_replace("'", "\'", _p($sValue)), ENT_QUOTES, 'UTF-8') . "'";
                }
                $sJs .= "};\n";

                $aModules = Phpfox_Module::instance()->getModules();
                $sJs .= "\t\t\tvar oModules = {";
                $iCnt = 0;
                foreach ($aModules as $sModule => $iModuleId) {
                    $iCnt++;
                    if ($iCnt != 1) {
                        $sJs .= ",";
                    }
                    $sJs .= "'{$sModule}': true";
                }
                $sJs .= "};\n";
            }

            if (count($this->_aImages)) {
                $sJs .= "\t\t\tvar oJsImages = {";
                foreach ($this->_aImages as $sKey => $sImage) {
                    $sJs .= $sKey . ': \'' . $sImage . '\',';
                }
                $sJs = rtrim($sJs, ',');
                $sJs .= "};\n";
            }

            $aEditorButtons = Phpfox::getLib('editor')->getButtons();

            $iCnt = 0;
            $sJs .= "\t\t\tvar oEditor = {";

            if (count($this->_aEditor) && isset($this->_aEditor['active']) && $this->_aEditor['active']) {
                foreach ($this->_aEditor as $sVar => $mValue) {
                    $iCnt++;
                    if ($iCnt != 1) {
                        $sJs .= ",";
                    }
                    $sJs .= "'{$sVar}': " . (is_bool($mValue) ? ($mValue === true ? 'true' : 'false') : "'{$mValue}'") . "";
                }

                $sJs .= ", ";
            }
            $sJs .= "images:[";
            foreach ($aEditorButtons as $mEditorButtonKey => $aEditorButton) {
                $sJs .= "{";
                foreach ($aEditorButton as $sEditorButtonKey => $sEditorButtonValue) {
                    $sJs .= "" . $sEditorButtonKey . ": '" . $sEditorButtonValue . "',";
                }
                $sJs = rtrim($sJs, ',') . "},";
            }
            $sJs = rtrim($sJs, ',') . "]";

            $sJs .= "};\n";
        }

        if (PHPFOX_IS_AJAX_PAGE) {
            $this->_aCacheHeaders = [];
        }

        $bIsHttpsPage = false;
        if (!defined('PHPFOX_INSTALLER') && Phpfox::getParam('core.force_https_secure_pages')) {
            if (in_array(str_replace('mobile.', '', Phpfox_Module::instance()->getFullControllerName()), Phpfox::getService('core')->getSecurePages())
                && (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
            ) {
                $bIsHttpsPage = true;
            }
        }

        $aSubCache = [];
        $sStyleCacheData = '';
        $sJsCacheData = '';
        $aCacheJs = [];
        $aCacheCSS = [];
        $sQmark = '?';
        $this->_sFooter = '';
        if (!defined('PHPFOX_INSTALLER')) {
            // do not load async script user/boot because function on document ready does not work.
            $this->_sFooter .= '<script>' . $this->getUserBootScript() . '</script>';
        }
        $sJs .= "\t\t\t" . 'var $Behavior = {}, $Ready = $Ready = function(callback) {$Behavior[callback.toString().length] = callback;}, $Events = {}, $Event = function(callback) {$Events[callback.toString().length] = callback;};' . "\n";
        $sJs .= "\t\t\t" . 'var $Core = {};' . "\n";
        foreach ($this->_aHeaders as $aHeaders) {
            if (!is_array($aHeaders)) {
                $aHeaders = [$aHeaders];
            }

            foreach ($aHeaders as $mKey => $mValue) {
                $sQmark = (strpos($mKey, '?') ? '&amp;' : '?');
                if (is_numeric($mKey)) {
                    if ($mValue === null) {
                        continue;
                    }

                    if ($bReturnArray) {
                        $aArrayData[] = $mValue;
                    } else {
                        if (is_string($mValue) && (strpos($mValue, '.js') !== false || strpos($mValue, 'javascript') !== false)) {
                            if (strpos($mValue, 'RecaptchaOptions')) {
                                $sData .= "\t\t" . $mValue . "\n";
                            } else {
                                $this->_sFooter .= "\t\t" . $mValue;
                            }
                        } else if (is_string($mValue)) {
                            $sData .= "\t\t" . $mValue . "\n";
                        } else {
                            $sData .= "\t\t" . implode($mValue) . "\n";
                        }
                    }

                } else if (filter_var($mKey, FILTER_VALIDATE_URL) !== false) {
                    if (strpos($mKey, '.css') !== false) {
                        $aCacheCSS[] = $mKey;
                    } else if (strpos($mKey, '.js') !== false) {
                        $aCacheJs[] = $mKey;
                    }
                } else if ($mKey == 'master') {
                    $aMaster = ['css' => [], 'jscript' => []];
                    foreach ($mValue as $sValKey => $sValVal) {
                        if (strpos($sValKey, '.css') !== false) {
                            if ($sValVal == 'style_css') {
                                $aMaster['css'][] = 'theme/frontend/' . $this->getThemeFolder() . '/style/' . $this->getStyleFolder() . '/css/' . $sValKey;
                            } else if (strpos($sValVal, 'module_') !== false) {
                                $aMaster['css'][] = 'module/' . (str_replace('module_', '', $sValVal)) . '/static/css/' . $this->getThemeFolder() . '/' . $this->getStyleFolder() . '/' . $sValKey;
                            }
                        } else if (strpos($sValKey, '.js') !== false) {
                            if ($sValVal == 'static_script') {
                                $aMaster['jscript'][] = 'static/jscript/' . $sValKey;
                            } else if (strpos($sValVal, 'module_') !== false) {
                                $aMaster['jscript'][] = 'module/' . (str_replace('module_', '', $sValVal)) . '/static/jscript/' . $sValKey;
                            }
                        }
                    }
                    unset($this->_aHeaders[$mKey]); // just to avoid confusions
                    $this->_aHeaders['master'] = $aMaster;
                } else {
                    $bToHead = false;
                    // This happens when the developer needs something to go to <head>
                    if (is_array($mValue)) {
                        $aKeyHead = array_keys($mValue);
                        $aKeyValue = array_values($mValue);
                        $bToHead = ($mKey == 'head');
                        $mKey = array_pop($aKeyHead);
                        $mValue = array_pop($aKeyValue);
                    }

                    if (isset($aSubCache[$mKey][$mValue])) {
                        continue;
                    }

                    switch ($mValue) {
                        case 'style_script':
                            if ($bReturnArray) {
                                $aArrayData[] = $this->getStyle('jscript', $mKey, null, false, false);
                            } else {
                                if ($bToHead == 'head') {
                                    $aCacheCSS[] = str_replace($this->_path_file, '', $this->getStyle('jscript', $mKey, null, false, false));
                                } else {
                                    $aCacheJs[] = str_replace($this->_path_file, '', $this->getStyle('jscript', $mKey, null, false, false));
                                }

                            }
                            break;
                        case 'style_css':
                            if ($bReturnArray) {
                                $aArrayData[] = $this->getStyle('css', $mKey, null, false, false);
                            } else {
                                $aCacheCSS[] = str_replace($this->_path_file, '', $this->getStyle('css', $mKey, null, false, false));
                            }

                            break;
                        case 'static_script':
                            if ($bReturnArray) {
                                $aArrayData[] = $this->_url_static_script . $mKey;
                            } else {
                                if (isset($this->_aCacheHeaders[$mKey])) {
                                    if ($bToHead == 'head') {
                                        $aCacheCSS[] = 'static/jscript/' . $mKey;
                                    } else {
                                        $aCacheJs[] = 'static/jscript/' . $mKey;
                                    }
                                } else {
                                    if ($bToHead == 'head') {
                                        $aCacheCSS[] = 'static/jscript/' . $mKey;
                                    } else {
                                        $aCacheJs[] = 'static/jscript/' . $mKey;
                                    }
                                }
                            }
                            break;

                        default:
                            if (preg_match('/app_/i', $mValue)) {
                                $aParts = explode('_', $mValue, 2);
                                if ('.js' == substr($mKey, -3)) {
                                    if ($bReturnArray) {
                                        $aArrayData[] = 'PF.Site/Apps/' . $aParts[1] . '/assets/' . $mKey;
                                    } else {
                                        if (isset($this->_aCacheHeaders[$mKey])) {
                                            $aCacheJs[] = 'PF.Site/Apps/' . $aParts[1] . '/assets/' . $mKey;
                                        } else {
                                            $aCacheJs[] = 'PF.Site/Apps/' . $aParts[1] . '/assets/' . $mKey;
                                        }
                                    }

                                } else if ('.css' == substr($mKey, -4)) {
                                    $Theme = $this->theme()->get();
                                    $sFileName = trim(str_replace(dirname($this->_path_file) . PHPFOX_DS, '', $this->getStyle('css', $mKey, $aParts[1], true, false)), '/');
                                    $sFileName = $Theme->getCssFileName($sFileName, 'app');
                                    $aCacheCSS[] = $sFileName;
                                }
                            } else if (preg_match('/module_/i', $mValue)) {
                                $aParts = explode('_', $mValue);
                                if (isset($aParts[1]) && Phpfox::isModule($aParts[1])) {
                                    if (substr($mKey, -3) == '.js') {
                                        if ($bReturnArray) {
                                            $aArrayData[] = $this->_path_file . 'module/' . $aParts[1] . '/static/jscript/' . $mKey;
                                        } else {
                                            if (isset($this->_aCacheHeaders[$mKey])) {
                                                $aCacheJs[] = 'module/' . $aParts[1] . '/static/jscript/' . $mKey;
                                            } else {
                                                $aCacheJs[] = 'module/' . $aParts[1] . '/static/jscript/' . $mKey;
                                            }
                                        }
                                    } else if (substr($mKey, -4) == '.css') {
                                        $Theme = $this->theme()->get();
                                        $sFileName = $Theme->getCssFileName(str_replace($this->_path_file, '', $this->getStyle('css', $mKey, $aParts[1], false, false)));
                                        $aCacheCSS[] = $sFileName;
                                    }
                                }
                            }
                            break;
                    }

                    $aSubCache[$mKey][$mValue] = true;
                }
            }
        }

        $sCacheData = '';
        $sCacheData .= "\n\t\t<script type=\"text/javascript\">\n";
        $sCacheData .= $sJs;
        $sCacheData .= "\t\t</script>";

        if (!empty($sStyleCacheData)) {
            $sCacheData .= "\n\t\t" . '<link rel="stylesheet" type="text/css" href="' . Phpfox::getParam('core.url_static') . 'gzip.php?t=css&amp;s=' . $sStylePath . '&amp;f=' . rtrim($sStyleCacheData, ',') . '&amp;v=' . $iVersion . '" />';
        }

        if (!empty($sJsCacheData)) {
            $sCacheData .= "\n\t\t" . '<script type="text/javascript" src="' . Phpfox::getParam('core.url_static') . 'gzip.php?t=js&amp;f=' . rtrim($sJsCacheData, ',') . '&amp;v=' . $iVersion . '"></script>';
        }

        if (!empty($sCacheData)) {
            $sData = preg_replace('/<link rel="shortcut icon" type="image\/x-icon" href="(.*?)" \/>/i', '<link rel="shortcut icon" type="image/x-icon" href="\\1" />' . $sCacheData, $sData);
        }

        if ($bReturnArray) {
            $sData = '';
        }
        $aCacheJs = array_unique($aCacheJs);

        $aSubCacheCheck = [];

        //Variable use for plugin call - don't remove it
        $sBaseUrl = $oAssets->getAssetBaseUrl();

        foreach ($aCacheCSS as $sFile) {
            if (defined('PHPFOX_INSTALLER')) {
            } else {
                if (isset($aSubCacheCheck[$sFile])) {
                    continue;
                }

                $aSubCacheCheck[$sFile] = true;
                if (filter_var($sFile, FILTER_VALIDATE_URL) !== false) {
                    $sData .= "\t\t" . '<link href="' . $sFile . $sQmark . 'v=' . $iVersion . '" rel="stylesheet" type="text/css" />' . "\n";
                } else if (strpos($sFile, 'PF.Site') === false) {
                    $sData .= "\t\t" . '<link href="' . $oAssets->getAssetUrl('PF.Base/' . $sFile) . '" rel="stylesheet" type="text/css" />' . "\n";
                } else {
                    $sData .= "\t\t" . '<link href="' . $oAssets->getAssetUrl($sFile) . '" rel="stylesheet" type="text/css" />' . "\n";
                }


            }
        }

        if ($bundle) {
            $this->_sFooter .= '<!-- autoload -->';
        }


        $aExcludeBundles = [
            'jquery.mosaicflow.min.js',
            'masterslider.min.js',
        ];

        (($sPlugin = Phpfox_Plugin::get('template_getheader_exclude_bundle_js')) ? eval($sPlugin) : false);


        foreach ($aCacheJs as $sFile) {
            if (!defined('PHPFOX_INSTALLER')) {

                $async = '';

                foreach ($aExcludeBundles as $tmp) {
                    if (false !== strpos($sFile, $tmp)) {
                        $async = 'type="text/javascript" ';
                    }
                }

                if (filter_var($sFile, FILTER_VALIDATE_URL) !== false) {
                    $this->_sFooter .= "\t\t" . '<script ' . $async . 'src="' . $sFile . $sQmark . 'v=' . $iVersion . '"></script>' . "\n";
                } else if (strpos($sFile, 'PF.Site') === false) {
                    $this->_sFooter .= "\t\t" . '<script ' . $async . 'src="' . $oAssets->getAssetUrl('PF.Base/' . $sFile) . '"></script>' . "\n";
                } else {
                    $this->_sFooter .= "\t\t" . '<script ' . $async . 'src="' . $oAssets->getAssetUrl($sFile) . '"></script>' . "\n";
                }
            }
        }

        $aPhrases = $this->getPhrases();
        if (count($aPhrases)) {
            $sData .= "\n\t\t<script type=\"text/javascript\">\n\t\t";
            foreach ($aPhrases as $sVar => $sPhrase) {
                $sPhrase = html_entity_decode($sPhrase, ENT_QUOTES, 'UTF-8');
                $sData .= "\t\t\toTranslations['{$sVar}'] = '" . str_replace("'", "\'", $sPhrase) . "';\n";
            }
            $sData .= "\t\t</script>\n";
        }

        if (!defined('PHPFOX_INSTALLER') && !Phpfox::isAdminPanel()) {
            $Request = \Phpfox_Request::instance();
            if ($Request->segment(1) == 'theme' && $Request->segment(2) == 'demo') {
                $sData .= '<link href="' . $oAssets->getAssetUrl('PF.Base/theme/default/flavor/default.css') . '" rel="stylesheet">';
            } else {
                $Theme = $this->_theme->get();
                (($sPlugin = Phpfox_Plugin::get('template_getheader_css')) ? eval($sPlugin) : false);
                if (request()->get('force-flavor') && !cached_file_exists(PHPFOX_DIR_SITE . 'flavors/' . flavor()->active->id . '/flavor/' . $Theme->flavor_folder . '.css')) {
                    $sData .= '<link href="' . $oAssets->getAssetUrl('PF.Base/theme/bootstrap/flavor/default.css') . '" rel="stylesheet">';
                } else {
                    $sData .= '<link href="' . $oAssets->getAssetUrl('PF.Site/flavors/' . flavor()->active->id . '/flavor/' . $Theme->flavor_folder . '.css') . '" rel="stylesheet">';
                }

                $sData .= '<link href="' . $oAssets->getAssetUrl('PF.Base/less/version/boot.css') . '" rel="stylesheet">';
            }
        }

        if (!defined('PHPFOX_INSTALLER')) {
            $Apps = Phpfox::getCoreApp();
            foreach ($Apps->all() as $App) {
                $assets = $App->path . 'assets/';
                if (cached_file_exists($assets . 'autoload.js')) {
                    $this->_sFooter .= '<script src="' . $oAssets->getAssetUrlWithFilename($assets . 'autoload.js') . '"></script>';
                }

                /*
                 * Neil: All autoload.css will load and compile to 1 file when rebuild css
                 * Ray: I've added this back because in 4.4 we have a feature to compile all CSS files
                 */
                if (cached_file_exists($assets . 'autoload.css')) {
                    $sData .= '<link href="' . $oAssets->getAssetUrlWithFilename("{$assets}autoload.css", true) . '" rel="stylesheet">';
                }
            }

            if (CSS_DEVELOPMENT_MODE) {
                $Theme = $this->_theme->get();
                $css = new Core\Theme\CSS($Theme);
                $css->buildFile('developing.less', 'developing');
                $sData .= '<link href="' . $oAssets->getAssetUrl('PF.Site/flavors/' . flavor()->active->id . '/flavor/developing.css') . '" rel="stylesheet">';
            }

            if (!Phpfox::isAdminPanel() && is_object($this->_theme)) {
                $asset = $this->_theme->get()->getPath() . 'assets/autoload.js';
                if (cached_file_exists($asset)) {
                    $this->_sFooter .= '<script src="' . $oAssets->getAssetUrlWithFilename($asset) . '"></script>';
                }
            }
        }

        if (!defined('PHPFOX_INSTALLER') && !$bundle) {
            $this->_sFooter .= "\t\t" . '<script type="text/javascript"> $Core.init(); </script>' . "\n";
        }

        if (isset($this->_meta['head'])) {
            $sData .= $this->_meta['head'];
            if (Phpfox::isAdmin()) {
                $this->_sFooter .= '<script>var page_editor_meta = ' . json_encode(['head' => $this->_meta['head']]) . ';</script>';
            }
        }

        (($sPlugin = Phpfox_Plugin::get('template_getheader_end')) ? eval($sPlugin) : false);

        if ($bReturnArray) {
            $aArrayData[] = $sData;

            return $aArrayData;
        }

        // Convert meta data
        $bHasNoDescription = false;
        if (count($this->_aMeta) && !PHPFOX_IS_AJAX_PAGE && !defined('PHPFOX_INSTALLER')) {
            $oPhpfoxParseOutput = Phpfox::getLib('parse.output');
            $aFind = [
                '&lt;',
                '&gt;',
            ];
            $aReplace = [
                '<',
                '>'
            ];

            foreach ($this->_aMeta as $sMeta => $sMetaValue) {
                $sMetaValue = str_replace($aFind, $aReplace, $sMetaValue);
                $sMetaValue = strip_tags($sMetaValue);
                $sMetaValue = str_replace(["\n", "\r"], "", $sMetaValue);
                $bIsCustomMeta = false;

                if (isset($this->_meta[$sMeta])) {
                    continue;
                }

                switch ($sMeta) {
                    case 'keywords':
                        $sKeywordSearch = Phpfox::getParam('core.words_remove_in_keywords');
                        if (!empty($sKeywordSearch)) {
                            $aKeywordsSearch = array_map('trim', explode(',', $sKeywordSearch));
                        }
                        $sMetaValue = $oPhpfoxParseOutput->clean($sMetaValue);

                        $sMetaValue = trim(rtrim(trim($sMetaValue), ','));
                        $aParts = explode(',', $sMetaValue);
                        $sMetaValue = '';
                        $aKeywordCache = [];
                        foreach ($aParts as $sPart) {
                            $sPart = trim($sPart);

                            if (isset($aKeywordCache[$sPart])) {
                                continue;
                            }

                            if (isset($aKeywordsSearch) && in_array(strtolower($sPart), array_map('strtolower', $aKeywordsSearch))) {
                                continue;
                            }

                            $sMetaValue .= $sPart . ', ';

                            $aKeywordCache[$sPart] = true;
                        }
                        $sMetaValue = rtrim(trim($sMetaValue), ',');
                        break;
                    case 'description':
                        $bHasNoDescription = true;
                        $sMetaValue = $oPhpfoxParseOutput->clean($sMetaValue);
                        break;
                    case 'robots':
                        $bIsCustomMeta = false;
                        break;
                    default:
                        $bIsCustomMeta = true;
                        break;
                }
                $sMetaValue = str_replace('"', '\"', $sMetaValue);
                $sMetaValue = Phpfox_Locale::instance()->convert($sMetaValue);
                $sMetaValue = html_entity_decode($sMetaValue, ENT_QUOTES, 'UTF-8');
                $sMetaValue = str_replace(['<', '>'], '', $sMetaValue);
                $iLimitLength = 0;

                if (in_array($sMeta, ['description', 'og:description'])
                    && !empty($limitDescription = Phpfox::getParam('core.max_character_length_for_description_meta'))
                    && mb_strlen($sMetaValue) > $limitDescription) {
                    $iLimitLength = $limitDescription;
                } elseif (in_array($sMeta, ['title', 'og:title'])
                    && !empty($limitTitle = Phpfox::getParam('core.max_character_length_for_title_meta'))
                    && mb_strlen($sMetaValue) > $limitTitle) {
                    $iLimitLength = $limitTitle;
                }

                if ($iLimitLength > 0) {
                    $sMetaValue = trim(Phpfox::getLib('parse.output')->shorten($sMetaValue, $iLimitLength));
                }

                if ($bIsCustomMeta) {
                    if ($sMeta == 'og:description') {
                        $sMetaValue = $oPhpfoxParseOutput->clean($sMetaValue);
                    }

                    switch ($sMeta) {
                        case 'canonical':
                            $sCanonical = $sMetaValue;
                            $sCanonical = preg_replace('/\/when\_([a-zA-Z0-9\-]+)\//i', '/', $sCanonical);
                            $sCanonical = preg_replace('/\/show\_([a-zA-Z0-9\-]+)\//i', '/', $sCanonical);
                            $sCanonical = preg_replace('/\/view\_\//i', '/', $sCanonical);

                            $sData .= "\t\t<link rel=\"canonical\" href=\"{$sCanonical}\" />\n";

                            break;
                        default:
                            $sData .= "\t\t<meta property=\"{$sMeta}\" content=\"{$sMetaValue}\" />\n";
                            break;
                    }

                } else {
                    if (strpos($sData, 'meta name="' . $sMeta . '"') !== false) {
                        $sData = preg_replace("/<meta name=\"{$sMeta}\" content=\"(.*?)\" \/>\n\t/i", "<meta" . ($sMeta == 'description' ? ' property="og:description" ' : '') . " name=\"{$sMeta}\" content=\"" . $sMetaValue . "\" />\n\t", $sData);

                    } else {
                        $sData = preg_replace('/<meta/', '<meta name="' . $sMeta . '" content="' . $sMetaValue . '" />' . "\n\t\t" . '<meta', $sData, 1);
                    }
                }

            }

            if (!$bHasNoDescription) {
                $sData .= "\t\t" . '<meta name="description" content="' . $oPhpfoxParseOutput->clean(Phpfox_Locale::instance()->convert(Phpfox::getParam('core.description'))) . '" />' . "\n";
            }
        }


        if ($bundle) {
            $the_name = 'autoload-' . Phpfox::getFullVersion() . '.css';
            $path = PHPFOX_DIR_FILE . 'static' . PHPFOX_DS . $the_name;
            $flavor = '';
            $rootUrl = $oAssets->getAssetBaseUrl(true);
            $aExcludeCss = [
                'PF.Base/theme/frontend/default/style/default/css/icofont.css'
            ];

            $files = array_filter($this->_aBundleScripts, function ($file) {
                return strpos($file, '.css');
            });

            $callback = function ($file = null) use (&$files, &$flavor, &$aExcludeCss, $rootUrl) {
                if ($file == 'flavor') {
                    return $flavor;
                }

                $file = $file[1];
                $the_file = explode('?', (strpos($file, $rootUrl) === false ? $file : str_replace($rootUrl, '', $file)))[0];
                if (!in_array($the_file, $files)) {
                    $aExcludeCss[] = $the_file;
                }
            };

            $sData = preg_replace_callback('/<link href="(.*?)"(.*?)>/is', $callback, $sData);

            if (!file_exists($path)) {
                $oAssets->bundleCssFile($path);
            }

            $sData .= '<link href="' . $oAssets->getAssetUrl('PF.Base/file/static/' . $the_name) . '" rel="stylesheet" type="text/css">';
            foreach ($aExcludeCss as $sCss) {
                if (filter_var($sCss, FILTER_VALIDATE_URL) !== false) {
                    $sData .= '<link href="' . $sCss . '" rel="stylesheet" type="text/css">';
                } else {
                    $sData .= '<link href="' . $oAssets->getAssetUrl($sCss) . '" rel="stylesheet" type="text/css">';
                }

            }
        }

        if (!defined('PHPFOX_INSTALLER') && Phpfox::getParam('core.google_plus_page_url')) {
            $sData .= '<link href="' . Phpfox::getParam('core.google_plus_page_url') . '" rel="publisher"/>';
        }

        // Clear from memory
        $this->_aHeaders = [];
        $this->_aMeta = [];

        return $sData;
    }

    public $footer = '';

    private function _bundle()
    {
        if (defined('PHPFOX_INSTALLER')) {
            return false;
        }

        if (!setting('pf_core_bundle_js_css', false)) {
            return false;
        }

        $bundle = false;
        if (!Phpfox::isAdminPanel()) {
            $bundle = true;
        }

        return $bundle;
    }

    public function addScript($key, $value, $bReturnString = true)
    {
        $oAssets = Phpfox::getLib('assets');
        switch ($value) {
            case 'style_script':
                if ($bReturnString) {
                    return '<script src="' . $this->getStyle('jscript', $key) . '"></script>';
                }
                $this->_sAddScripts .= '<script src="' . $this->getStyle('jscript', $key) . '"></script>';
                break;
            case 'style_css':
                if ($bReturnString) {
                    return '<link type="text/css" rel="stylesheet" href="' . $this->getStyle('css', $key) . '"/>';
                }
                $this->_sAddScripts .= '<link type="text/css" rel="stylesheet" href="' . $this->getStyle('css', $key) . '"/>';
                break;
            case 'static_script':
                if ($bReturnString) {
                    return '<script src="' . $oAssets->getAssetUrl($this->_url_static_script . $key) . '"></script>';
                }
                $this->_sAddScripts .= '<script src="' . $oAssets->getAssetUrl($this->_url_static_script . $key) . '"></script>';
                break;
            default:
                if (preg_match('/app_/i', $value)) {
                    $aParts = explode('_', $value, 2);
                    if ('.js' == substr($key, -3)) {
                        if ($bReturnString) {
                            return '<script type="text/javascript" src="' . $oAssets->getAssetUrl('PF.Site/Apps/' . $aParts[1] . '/assets/' . $key) . '"></script>';
                        }
                        $this->_sAddScripts .= '<script type="text/javascript" src="' . $oAssets->getAssetUrl('PF.Site/Apps/' . $aParts[1] . '/assets/' . $key) . '"></script>';

                    } else if ('.css' == substr($key, -4)) {
                        $Theme = $this->theme()->get();
                        $sFileName = trim(str_replace(dirname($this->_path_file) . PHPFOX_DS, '', $this->getStyle('css', $key, $aParts[1], true, false)), '/');
                        $sFileName = $Theme->getCssFileName($sFileName, 'app');

                        if ($bReturnString) {
                            return '<link type="text/css" rel="stylesheet" href="' . $oAssets->getAssetUrl($sFileName) . '"/>';
                        }
                        $this->_sAddScripts .= '<link type="text/css" rel="stylesheet" href="' . $oAssets->getAssetUrl($sFileName) . '"/>';
                    }
                } else if (preg_match('/module_/i', $value)) {
                    $aParts = explode('_', $value);
                    if (isset($aParts[1]) && Phpfox::isModule($aParts[1])) {
                        if (substr($key, -3) == '.js') {
                            if ($bReturnString) {
                                return '<script src="' . $oAssets->getAssetUrl($this->_path_file . 'module/' . $aParts[1] . '/static/jscript/' . $key) . '"></script>';
                            }
                            $this->_sAddScripts .= '<script src="' . $oAssets->getAssetUrl($this->_path_file . 'module/' . $aParts[1] . '/static/jscript/' . $key) . '"></script>';

                        } else if (substr($key, -4) == '.css') {
                            $Theme = $this->theme()->get();
                            $sFileName = $Theme->getCssFileName(str_replace($this->_path_file, '', $this->getStyle('css', $key, $aParts[1], false, false)));

                            if ($bReturnString) {
                                return '<link type="text/css" rel="stylesheet" href="' . $oAssets->getAssetUrl($sFileName) . '"/>';
                            }
                            $this->_sAddScripts .= '<link type="text/css" rel="stylesheet" href="' . $oAssets->getAssetUrl($sFileName) . '"/>';
                        }
                    }
                }
        }
    }

    public function verifyBundleFiles($aFiles)
    {
        $bundle = $this->_bundle();

        if (!$bundle)
            return $aFiles;

        if (empty($this->_aBundleScripts)) {
            $this->checkBundles();
        }
        $aBundleScript = $this->_aBundleScripts;

        return array_filter($aFiles, function ($file) use (&$aBundleScript) {
            return !in_array($file, $aBundleScript);
        });

    }

    public function getFooter($bReturnArray = false)
    {
        $oAssets = Phpfox::getLib('assets');
        $bundle = $this->_bundle();

        $this->checkBundles();

        $this->_sFooter .= '<div id="show-side-panel"><span></span></div>';

        Core\Event::trigger('lib_phpfox_template_getfooter', $this);


        $this->_sFooter .= $this->_sAddScripts . $this->footer;

        if (!defined('PHPFOX_INSTALLER')) {
            foreach (Phpfox::getCoreApp()->all() as $App) {
                if ($App->footer && is_array($App->footer)) {
                    foreach ($App->footer as $footer) {
                        $this->_sFooter .= $footer;
                    }
                }

                if ($App->js && is_array($App->js)) {
                    foreach ($App->js as $js) {
                        $this->_sFooter .= '<script src="' . $js . '"></script>';
                    }
                }

                if ($App->js && is_object($App->js)) {
                    foreach ($App->js as $js => $setting) {
                        if (setting($setting)) {
                            $this->_sFooter .= '<script src="' . $js . '"></script>';
                        }
                    }
                }
            }
        }

        $this->_sFooter .= '<script src="' . $oAssets->getAssetUrl('PF.Base/static/jscript/bootstrap.js') . '"></script>';

        (($sPlugin = Phpfox_Plugin::get('template_getfooter_end')) ? eval($sPlugin) : false);

        if ($bundle) {
            $the_name = 'autoload-' . Phpfox::getFullVersion() . '.js';
            $path = PHPFOX_DIR_FILE . 'static' . PHPFOX_DS . $the_name;
            $module_files = [];
            $homeUrl = $oAssets->getAssetBaseUrl();
            $callback = function ($file = null) use (&$module_files, $homeUrl) {
                $script = $file[0];
                $file = $file[1];

                if (substr($file, -11) == '/user/boot/') {
                    return $script;
                }

                $the_file = explode('?', str_replace($homeUrl, '', $file))[0];

                if (substr($the_file, 0, 4) == 'http' or substr($the_file, 0, 2) == '//') {
                    return $script;
                }

                if (in_array($the_file, $this->_aBundleScripts)) {
                    return '';
                }

                $module_files[] = $the_file;

                return '';
            };

            // merged bundle scripts.

            $this->_sFooter = preg_replace_callback('/<script src="(.*?)"><\/script>/is', $callback, $this->_sFooter);

            $fnPathToUrl = function ($file) use ($homeUrl) {
                $file = str_replace(PHPFOX_PARENT_DIR, $homeUrl, $file);
                $file = str_replace(PHPFOX_DS, '/', $file);
                return $file;
            };

            $aBundleFiles = array_map($fnPathToUrl, $this->_aBundleScripts);

            if (!file_exists($path)) {
                $oAssets->bundleJsFile($path);
            }

            $module_urls = array_map($fnPathToUrl, $module_files);

            $this->_sFooter = str_replace('<!-- autoload -->', '<script src="' . $oAssets->getAssetUrl('PF.Base/file/static/' . $the_name) . '"></script>', $this->_sFooter);
            foreach ($module_urls as $module_url) {
                if (filter_var($module_url, FILTER_VALIDATE_URL) !== false) {
                    $this->_sFooter .= '<script src="' . $module_url . '?v=' . $this->getStaticVersion() . '"></script>';
                } else {
                    $this->_sFooter .= '<script src="' . $oAssets->getAssetUrl($module_url) . '"></script>';
                }

            }
            $this->_sFooter .= '<script>$Core.init();</script>';
        }

        if ($bReturnArray) {
            $aReturnArray = [];
            if (preg_match_all('/src="([^\>]+)"/', $this->_sAddScripts, $matches)) {
                foreach ($matches[1] as $v) {
                    if (strpos($v, '.js') > 0) {
                        $aReturnArray[] = preg_replace('/\?v=(.)+$/', '', $v);
                    }
                }
            }
            if (preg_match_all('/href="([^\>]+)"/', $this->_sAddScripts, $matches)) {
                foreach ($matches[1] as $v) {
                    if (strpos($v, '.css') > 0) {
                        $aReturnArray[] = preg_replace('/\?v=(.)+$/', '', $v);
                    }
                }
            }

            if (!isset($aBundleFiles))
                return $aReturnArray;

            return array_filter($aReturnArray, function ($file) use (&$aBundleFiles) {
                return !in_array($file, $aBundleFiles);
            });
        }

        return $this->_sFooter;
    }

    /**
     * Get the template header file if it exists.
     *
     * @return mixed File path if it exists, otherwise FALSE.
     */
    public function getHeaderFile()
    {
        $sFile = $this->getStyle('php', 'header.php', null, false, false);
        $sFile = str_replace($this->_path_file, PHPFOX_DIR, $sFile);

        if (file_exists($sFile)) {
            return $sFile;
        }

        return false;
    }

    /**
     * Gets the full path of a file based on the current style being used.
     *
     * @param string $sType   Type of file we are working with.
     * @param string $sValue  File name.
     * @param string $sModule Module name. Only if its part of a module.
     * @param bool   $isApp
     * @param bool   $getByUrl
     *
     * @return string Returns the full path to the item.
     */
    public function getStyle($sType = 'css', $sValue = null, $sModule = null, $isApp = false, $getByUrl = true)
    {
        $pathFile = $this->_path_file;
        if ($isApp) {
            $sUrl = dirname($pathFile) . PHPFOX_DS . 'PF.Site' . PHPFOX_DS . 'Apps' . PHPFOX_DS . $sModule . PHPFOX_DS . 'assets' . PHPFOX_DS;
            return $sUrl . $sValue;
        }
        if ($sModule !== null) {
            if ($sType == 'static_script') {
                $sType = 'jscript';
            }

            $sUrl = $pathFile . 'module' . PHPFOX_DS . $sModule . PHPFOX_DS . 'static' . PHPFOX_DS . $sType . PHPFOX_DS;
            $sDir = PHPFOX_DIR_MODULE . $sModule . PHPFOX_DS . 'static' . PHPFOX_DS . $sType . PHPFOX_DS;

            if ($sType == 'jscript') {
                $sPath = $sUrl . $sValue;
            } else {
                $bPass = false;
                if (cached_file_exists($sDir . $this->_sThemeFolder . PHPFOX_DS . $this->_sStyleFolder . PHPFOX_DS . $sValue)) {
                    $bPass = true;
                    $sPath = $sUrl . $this->_sThemeFolder . '/' . $this->_sStyleFolder . '/' . $sValue;
                }

                if ($bPass === false) {
                    $sPath = $sUrl . 'default/default/' . $sValue;
                }
            }

            return isset($sPath) ? $sPath : '';
        }

        if ($sType == 'static_script') {
            $sPath = $this->_url_static_script . $sValue;
        } else {
            $sPath = (defined('PHPFOX_INSTALLER') ? Phpfox_Installer::getHostPath() : $pathFile) . 'theme/' . $this->_sThemeLayout . '/' . $this->_sThemeFolder . '/style/' . $this->_sStyleFolder . '/' . $sType . '/';
            if ($sPlugin = Phpfox_Plugin::get('library_template_getstyle_1')) {
                eval($sPlugin);
                if (isset($bReturnFromPlugin)) return $bReturnFromPlugin;
            }
            if ($sValue !== null) {
                $bPass = false;

                if (isset($this->_aTheme['style_parent_id']) && $this->_aTheme['style_parent_id'] > 0) {
                    $bPass = false;
                    if (cached_file_exists(PHPFOX_DIR . 'theme' . PHPFOX_DS . $this->_sThemeLayout . PHPFOX_DS . $this->_sThemeFolder . PHPFOX_DS . 'style' . PHPFOX_DS . $this->_sStyleFolder . PHPFOX_DS . $sType . PHPFOX_DS . $sValue)) {
                        $bPass = true;
                        $sPath = $pathFile . 'theme' . '/' . $this->_sThemeLayout . '/' . $this->_sThemeFolder . '/' . 'style' . '/' . $this->_sStyleFolder . '/' . $sType . '/' . $sValue;
                    }

                    if (isset($this->_aTheme['parent_theme_folder'])) {
                        if ($bPass === false && cached_file_exists(PHPFOX_DIR . 'theme' . PHPFOX_DS . $this->_sThemeLayout . PHPFOX_DS . $this->_aTheme['parent_theme_folder'] . PHPFOX_DS . 'style' . PHPFOX_DS . $this->_aTheme['parent_style_folder'] . PHPFOX_DS . $sType . PHPFOX_DS . $sValue)) {
                            $bPass = true;
                            $sPath = $pathFile . 'theme' . '/' . $this->_sThemeLayout . '/' . $this->_aTheme['parent_theme_folder'] . '/' . 'style' . '/' . $this->_aTheme['parent_style_folder'] . '/' . $sType . '/' . $sValue;
                        }
                    } else {
                        if ($bPass === false && cached_file_exists(PHPFOX_DIR . 'theme' . PHPFOX_DS . $this->_sThemeLayout . PHPFOX_DS . $this->_sThemeFolder . PHPFOX_DS . 'style' . PHPFOX_DS . $this->_aTheme['parent_style_folder'] . PHPFOX_DS . $sType . PHPFOX_DS . $sValue)) {
                            $bPass = true;
                            $sPath = $pathFile . 'theme' . '/' . $this->_sThemeLayout . '/' . $this->_sThemeFolder . '/' . 'style' . '/' . $this->_aTheme['parent_style_folder'] . '/' . $sType . '/' . $sValue;
                        }
                    }
                } else {
                    if (!defined('PHPFOX_INSTALLER')) {
                        if (cached_file_exists(PHPFOX_DIR . 'theme' . PHPFOX_DS . $this->_sThemeLayout . PHPFOX_DS . $this->_sThemeFolder . PHPFOX_DS . 'style' . PHPFOX_DS . $this->_sStyleFolder . PHPFOX_DS . $sType . PHPFOX_DS . $sValue)) {
                            $bPass = true;
                            $sPath = $pathFile . 'theme' . '/' . $this->_sThemeLayout . '/' . $this->_sThemeFolder . '/' . 'style' . '/' . $this->_sStyleFolder . '/' . $sType . '/' . $sValue;
                        }
                    }
                }

                if ($bPass === false) {
                    if (defined('PHPFOX_INSTALLER')) {
                        $sPath = (defined('PHPFOX_INSTALLER') ? Phpfox_Installer::getHostPath() : $pathFile) . 'theme' . '/' . $this->_sThemeLayout . '/' . 'default' . '/' . 'style' . '/' . 'default' . '/' . $sType . '/' . $sValue;
                    } else {
                        if (cached_file_exists(PHPFOX_DIR . 'theme' . '/' . $this->_sThemeLayout . '/' . 'default' . '/' . 'style' . '/' . 'default' . '/' . $sType . '/' . $sValue)) {
                            $sPath = $pathFile . 'theme' . '/' . $this->_sThemeLayout . '/' . 'default' . '/' . 'style' . '/' . 'default' . '/' . $sType . '/' . $sValue;
                        } else {
                            if (cached_file_exists(PHPFOX_DIR . 'theme' . '/frontend/' . $this->_sThemeFolder . '/' . 'style' . '/' . $this->_sStyleFolder . '/' . $sType . '/' . $sValue)) {
                                $sPath = $pathFile . 'theme' . '/frontend/' . $this->_sThemeFolder . '/' . 'style' . '/' . $this->_sStyleFolder . '/' . $sType . '/' . $sValue;
                            } else {
                                $sPath = $pathFile . 'theme' . '/frontend/' . 'default' . '/' . 'style' . '/' . 'default' . '/' . $sType . '/' . $sValue;
                            }
                        }
                    }
                }
            }
        }

        if (!defined('PHPFOX_INSTALLER') && $getByUrl) {
            $sPath = Phpfox::getLib('assets')->getAssetUrl($sPath);
        }

        return $sPath;
    }

    /**
     * Assign a variable so we can use it within an HTML template.
     *
     * PHP assign:
     * <code>
     * Phpfox_Template::instance()->assign('foo', 'bar');
     * </code>
     *
     * HTML usage:
     * <code>
     * {$foo}
     * // Above will output: bar
     * </code>
     *
     * @param mixed  $mVars  STRING variable name or ARRAY of variables to assign with both keys and values.
     * @param string $sValue Variable value, only if the 1st argument is a STRING.
     *
     * @return $this
     */
    public function assign($mVars, $sValue = '')
    {
        if (!is_array($mVars)) {
            $mVars = [$mVars => $sValue];
        }

        foreach ($mVars as $sVar => $sValue) {
            if (is_array($sValue) && count($sValue)) {
                $first = reset($sValue);
                if (is_object($first) && method_exists($first, '__toArray')) {
                    $sValue = array_map(function ($val) {

                        return (array)$val;
                    }, $sValue);
                }
            }
            if (is_object($sValue) && method_exists($sValue, '__toArray')) {
                $sValue = (array)$sValue;
            }

            $this->_aVars[$sVar] = $sValue;
        }

        return $this;
    }

    /**
     * Get a variable we assigned with the method assign().
     *
     * @param string $sName Variable name.
     *
     * @return string Variable value.
     * @see self::assign()
     */
    public function getVar($sName = null)
    {
        if ($sName === null) {
            return $this->_aVars;
        }

        if (isset($this->_aVars[$sName]) && is_object($this->_aVars[$sName])) {
            $this->_aVars[$sName] = (array)$this->_aVars[$sName];
        }

        return (isset($this->_aVars[$sName]) ? $this->_aVars[$sName] : '');
    }

    /**
     * Clean all or a specific variable from memory.
     *
     * @param mixed $mName Variable name to destroy, or leave blank to destory all variables or pass an ARRAY of variables to destroy.
     */
    public function clean($mName = '')
    {
        if ($mName) {
            if (!is_array($mName)) {
                $mName = [$mName];
            }

            foreach ($mName as $sName) {
                unset($this->_aVars[$sName]);
            }

            return;
        }

        unset($this->_aVars);
    }

    /**
     * Loads the current template.
     *
     * @param string $sName   Layout name.
     * @param bool   $bReturn TRUE to return the template code, FALSE will echo it.
     *
     * @return mixed STRING if 2nd argument is TRUE. Otherwise NULL.
     */
    public function getLayout($sName, $bReturn = false)
    {
        $this->_getFromCache($this->getLayoutFile($sName));

        if ($bReturn) {
            return $this->_returnLayout();
        }
    }

    /**
     * Get the full path of the current layout file.
     *
     * @param string $sName Name of the layout file.
     *
     * @return string Full path to the layout file.
     */
    public function getLayoutFile($sName)
    {
        $sFile = PHPFOX_DIR_THEME . $this->_sThemeLayout . PHPFOX_DS . $this->_sThemeFolder . PHPFOX_DS . 'template' . PHPFOX_DS . $sName . PHPFOX_TPL_SUFFIX;
        if ($sPlugin = Phpfox_Plugin::get('library_template_getlayoutfile_1')) {
            eval($sPlugin);
            if (isset($bReturnFromPlugin)) return $bReturnFromPlugin;
        }
        if (!file_exists($sFile)) {
            if ($this->_aTheme['theme_parent_id'] > 0 && !empty($this->_aTheme['parent_theme_folder'])) {
                $sFile = PHPFOX_DIR_THEME . $this->_sThemeLayout . PHPFOX_DS . $this->_aTheme['parent_theme_folder'] . PHPFOX_DS . 'template' . PHPFOX_DS . $sName . PHPFOX_TPL_SUFFIX;
            }
        }

        if (!file_exists($sFile)) {
            $sFile = PHPFOX_DIR_THEME . $this->_sThemeLayout . PHPFOX_DS . 'default' . PHPFOX_DS . 'template' . PHPFOX_DS . $sName . PHPFOX_TPL_SUFFIX;
        }

        if ($this->_sThemeLayout == 'mobile' && !file_exists($sFile)) {
            $sFile = PHPFOX_DIR_THEME . 'frontend' . PHPFOX_DS . 'default' . PHPFOX_DS . 'template' . PHPFOX_DS . $sName . PHPFOX_TPL_SUFFIX;
        }

        return $sFile;
    }

    private $_aSavedBuiltFiles = [];

    public function getBuiltFile($sTemplate, $feed_id = null)
    {
        $sCacheName = 'block_' . $sTemplate;

        if (!isset($this->_aSavedBuiltFiles[$sCacheName])) {
            if ($this->_sTemplateGetTemplatePassPlugin) {
                eval($this->_sTemplateGetTemplatePassPlugin);
            }

            if (isset($skip_layout)) {

            } else {
                $path = $this->_getCachedName($sCacheName);
            }
            if (isset($path))
                $this->_aSavedBuiltFiles[$sCacheName] = $path;
        }

        if (isset($this->_aSavedBuiltFiles[$sCacheName])) {
            $path = $this->_aSavedBuiltFiles[$sCacheName];
        }

        if (!isset($skip_layout) && isset($path)) {
            $bGetTemplateCacheSucceeded = false;
            if (!defined('PHPFOX_NO_TEMPLATE_CACHE')) {
                try {
                    $bGetTemplateCacheSucceeded = $this->_requireFile($sCacheName);
                } catch (Exception $ex) {
                    $bGetTemplateCacheSucceeded = false;
                }
            }
            if (!$bGetTemplateCacheSucceeded) {
                $mContent = Phpfox_Template::instance()->getTemplateFile($sTemplate, true);
                if (is_array($mContent)) {
                    $mContent = $mContent[0];
                } else {
                    $mContent = file_get_contents($mContent);
                }
                $oTplCache = Phpfox::getLib('template.cache');
                $oTplCache->compile($this->_getCachedName($sCacheName), $mContent, true);
                $this->_requireFile($sCacheName);
            }
        }
    }

    /**
     * Get the full path to the modular template file we are loading.
     *
     * @param string $sTemplate Name of the file.
     * @param bool   $bCheckDb  TRUE to check the database if the file exists there.
     *
     * @return string Full path to the file we are loading.
     */
    public function getTemplateFile($sTemplate, $bCheckDb = false)
    {
        (($sPlugin = Phpfox_Plugin::get('template_gettemplatefile')) ? eval($sPlugin) : false);

        if (!isset($sFile)) {
            $aParts = explode('.', $sTemplate);
            $sModule = $aParts[0];
            $sFolder = ($this->_sThemeFolder == 'bootstrap') ? 'default' : $this->_sThemeFolder;

            if (defined('PHPFOX_INSTALLER')) {
                return ['', ''];
            }
            unset($aParts[0]);
            $sName = implode('.', $aParts);
            $sName = str_replace('.', PHPFOX_DS, $sName);
            $bPass = false;

            if (!empty($this->_aDirectoryNames[$sModule])) {
                $filename = $this->_aDirectoryNames[$sModule] . PHPFOX_DS . implode(PHPFOX_DS, $aParts) . '.html.php';
                if (cached_file_exists($filename)) {
                    $sFile = $filename;
                    $bPass = true;
                }
            }

            if (!$bPass && cached_file_exists(PHPFOX_DIR_MODULE . $sModule . PHPFOX_DIR_MODULE_TPL . PHPFOX_DS . $sFolder . PHPFOX_DS . $sName . PHPFOX_TPL_SUFFIX)) {
                $sFile = PHPFOX_DIR_MODULE . $sModule . PHPFOX_DIR_MODULE_TPL . PHPFOX_DS . $sFolder . PHPFOX_DS . $sName . PHPFOX_TPL_SUFFIX;
                $bPass = true;
            }

            if ($bPass === false && cached_file_exists(PHPFOX_DIR_MODULE . $sModule . PHPFOX_DIR_MODULE_TPL . PHPFOX_DS . $sFolder . PHPFOX_DS . $sName . PHPFOX_TPL_SUFFIX)) {
                $sFile = PHPFOX_DIR_MODULE . $sModule . PHPFOX_DIR_MODULE_TPL . PHPFOX_DS . $sFolder . PHPFOX_DS . $sName . PHPFOX_TPL_SUFFIX;
                $bPass = true;
            }

            if ($bPass === false && isset($aParts[2]) && cached_file_exists(PHPFOX_DIR_MODULE . $sModule . PHPFOX_DIR_MODULE_TPL . PHPFOX_DS . $sFolder . PHPFOX_DS . $sName . PHPFOX_DS . $aParts[2] . PHPFOX_TPL_SUFFIX)) {
                $sFile = PHPFOX_DIR_MODULE . $sModule . PHPFOX_DIR_MODULE_TPL . PHPFOX_DS . $sFolder . PHPFOX_DS . $sName . PHPFOX_DS . $aParts[2] . PHPFOX_TPL_SUFFIX;
                $bPass = true;
            }

            if (isset($this->_aTheme['theme_parent_id']) && $this->_aTheme['theme_parent_id'] > 0 && !empty($this->_aTheme['parent_theme_folder'])) {
                if ($bPass === false && cached_file_exists(PHPFOX_DIR_MODULE . $sModule . PHPFOX_DIR_MODULE_TPL . PHPFOX_DS . $this->_aTheme['parent_theme_folder'] . PHPFOX_DS . $sName . PHPFOX_TPL_SUFFIX)) {
                    $sFile = PHPFOX_DIR_MODULE . $sModule . PHPFOX_DIR_MODULE_TPL . PHPFOX_DS . $this->_aTheme['parent_theme_folder'] . PHPFOX_DS . $sName . PHPFOX_TPL_SUFFIX;
                    $bPass = true;
                }

                if ($bPass === false && cached_file_exists(PHPFOX_DIR_MODULE . $sModule . PHPFOX_DIR_MODULE_TPL . PHPFOX_DS . $this->_aTheme['parent_theme_folder'] . PHPFOX_DS . $sName . PHPFOX_TPL_SUFFIX)) {
                    $sFile = PHPFOX_DIR_MODULE . $sModule . PHPFOX_DIR_MODULE_TPL . PHPFOX_DS . $this->_aTheme['parent_theme_folder'] . PHPFOX_DS . $sName . PHPFOX_TPL_SUFFIX;
                    $bPass = true;
                }

                if ($bPass === false && isset($aParts[2]) && cached_file_exists(PHPFOX_DIR_MODULE . $sModule . PHPFOX_DIR_MODULE_TPL . PHPFOX_DS . $this->_aTheme['parent_theme_folder'] . PHPFOX_DS . $sName . PHPFOX_DS . $aParts[2] . PHPFOX_TPL_SUFFIX)) {
                    $sFile = PHPFOX_DIR_MODULE . $sModule . PHPFOX_DIR_MODULE_TPL . PHPFOX_DS . $this->_aTheme['parent_theme_folder'] . PHPFOX_DS . $sName . PHPFOX_DS . $aParts[2] . PHPFOX_TPL_SUFFIX;
                    $bPass = false;
                }
            }

            if (!isset($sFile)) {
                $sFile = PHPFOX_DIR_MODULE . $sModule . PHPFOX_DIR_MODULE_TPL . PHPFOX_DS . 'default' . PHPFOX_DS . $sName . PHPFOX_TPL_SUFFIX;
            }
        }

        if (!cached_file_exists($sFile)) {
            Phpfox_Error::trigger('Unable to load module template: ' . $sModule . '->' . $sName, E_USER_ERROR);
        }

        return $sFile;
    }

    /**
     * Get a template that has already been built.
     *
     * @param string $sLayout    Template name.
     * @param string $sCacheName Cache name of the file.
     *
     * @return string HTML layout of the file.
     */
    public function getBuiltTemplate($sLayout, $sCacheName)
    {
        $sOriginalContent = ob_get_contents();
        ob_clean();
        if (defined('PHPFOX_NO_TEMPLATE_CACHE') || (include $this->_getCachedName($sCacheName)) == false) {
            $sLayoutContent = file_get_contents($this->getLayoutFile($sLayout));
            $sLayoutContent = str_replace('{layout_content}', '<?php echo $this->_aVars[\'sContent\']; ?>', $sLayoutContent);
            $oTplCache = Phpfox::getLib('template.cache');
            $oTplCache->compile($this->_getCachedName($sCacheName), $sLayoutContent, true);
            $this->_requireFile($sCacheName);
        }
        $sCurrentContent = $this->_returnLayout();
        echo $sOriginalContent;
        return $sCurrentContent;
    }

    /**
     * Get the current template data.
     *
     * @param string $sTemplate Template name.
     * @param bool   $bReturn   TRUE to return its content or FALSE to just echo it.
     *
     * @return mixed STRING content only if the 2nd argument is TRUE.
     */
    public function getTemplate($sTemplate, $bReturn = false)
    {
        (($sPlugin = Phpfox_Plugin::get('template_gettemplate')) ? eval($sPlugin) : false);

        $sFile = $this->getTemplateFile($sTemplate);

        if ($bReturn) {
            $sOriginalContent = ob_get_contents();
            ob_clean();
        }

        (($sPlugin = Phpfox_Plugin::get('template_gettemplate_pass')) ? eval($sPlugin) : false);

        if (isset($skip_layout)) {

        } else {
            if ($this->_sSetLayout) {
                $tmpLayout = $this->_sSetLayout;
                $bGetTemplateCacheSucceeded = false;
                if (!defined('PHPFOX_NO_TEMPLATE_CACHE')) {
                    try {
                        $this->_sSetLayout = '';
                        $bGetTemplateCacheSucceeded = $this->_requireFile($sFile);
                        $this->_sSetLayout = '';
                    } catch (Exception $ex) {
                        $bGetTemplateCacheSucceeded = false;
                    }
                }
                if (!$bGetTemplateCacheSucceeded) {
                    $this->_sSetLayout = $tmpLayout;
                    $sLayoutContent = file_get_contents($this->getLayoutFile($this->_sSetLayout));
                    $sLayoutContent = str_replace('{layout_content}', file_get_contents($sFile), $sLayoutContent);
                    $oTplCache = Phpfox::getLib('template.cache');
                    $oTplCache->compile($this->_getCachedName($sFile), $sLayoutContent, true, (isset($aSubTemplate['html_data']) ? true : false));
                    $this->_sSetLayout = '';
                    $this->_requireFile($sFile);
                    $this->_sSetLayout = '';
                }
            } else {
                $this->_getFromCache($sFile, $sTemplate);
            }
        }

        if ($bReturn) {
            $sReturn = $this->_returnLayout();
            echo isset($sOriginalContent) ? $sOriginalContent : '';
            return $sReturn;
        }
        return null;
    }

    /**
     * Rebuild a cached menu.
     *
     * @param string $sConnection Menu connection.
     * @param array  $aNewUrl     ARRAY of the new values.
     *
     * @return object Return self.
     */
    public function rebuildMenu($sConnection, $aNewUrl)
    {
        $this->_aNewUrl[$sConnection] = $aNewUrl;

        return $this;
    }

    /**
     * Remove a URL from a built cached menu.
     *
     * @param string $sConnection Menu connection.
     * @param string $sUrl        URL value to identify what menu to remove.
     *
     * @return object Return self.
     */
    public function removeUrl($sConnection, $sUrl)
    {
        $this->_aRemoveUrl[$sConnection][$sUrl] = true;

        return $this;
    }

    private $_aMenus = [];

    public function setMenu($menus)
    {
        foreach ($menus as $connection => $menu) {
            $this->_aMenus[$connection] = $menu;
        }
    }

    /**
     * Gets all the sites custom menus, such as the Main, Header, Footer and Sub menus.
     * Since information is stored in the database we cache the information so we only run
     * the query once.
     *
     * @param string $sConnection Current page we are viewing (Example: account/login)
     *
     * @return array $aMenus Is an array of the menus data
     */
    public function getMenu($sConnection = null)
    {
        $oCache = Phpfox::getLib('cache');
        $oReq = Phpfox_Request::instance();

        (($sPlugin = Phpfox_Plugin::get('template_template_getmenu_1')) ? eval($sPlugin) : false);
        if ($sConnection === null) {
            $sConnection = Phpfox_Module::instance()->getFullControllerName();
            $sConnection = preg_replace('/(.*)\.profile/i', '\\1.index', $sConnection);
            if (($sConnection == 'user.photo' && $oReq->get('req3') == 'register') || ($sConnection == 'invite.index' && $oReq->get('req2') == 'register')) {
                return [];
            }
        }
        $sConnection = strtolower(str_replace('/', '.', $sConnection));
        if ($sConnection == 'profile.private' || $sConnection == 'profile.points' || $sConnection == 'profile.app') {
            return [];
        }

        $sCachedId = $oCache->set(['theme', 'menu_' . str_replace(['/', '\\'], '_', $sConnection) . (Phpfox::isUser() ? Phpfox::getUserBy('user_group_id') : 0)]);

        if ((!($aMenus = $oCache->get($sCachedId))) && is_bool($aMenus) && !$aMenus) {
            $aParts = explode('.', $sConnection);
            $aMenus1 = $this->_getMenu($sConnection);
            $aCached = [];
            foreach ($aMenus1 as $aMenu1) {
                $aCached[] = $aMenu1['menu_id'];
            }

            $aMenus2 = $this->_getMenu($aParts[0]);
            foreach ($aMenus2 as $iKey => $aMenu2) {
                if (in_array($aMenu2['menu_id'], $aCached)) {
                    unset($aMenus2[$iKey]);
                }
            }

            $aFinal = array_merge($aMenus1, $aMenus2);

            $aMenus = [];
            foreach ($aFinal as $aMenu) {
                if ($aMenu['parent_id'] > 0) {
                    continue;
                }
                // test if this menu points to a real location
                $aMenu['is_url'] = isset($aMenu['url']) && !empty($aMenu['url']) && strpos($aMenu['url'], 'http') !== false;
                if ($aMenu['is_url'] && preg_match('/^' . str_replace('/', '\/', Phpfox::getBaseUrl()) . '/', $aMenu['url']) === false) {
                    $aMenu['external'] = true;

                } else if (isset($aMenu['url']) && $aMenu['url'] == '#') {
                    $aMenu['no_link'] = true;
                }
                $aMenus[$aMenu['menu_id']] = $aMenu;
            }
            $aParents = Phpfox_Database::instance()->select('m.menu_id, m.parent_id, m.m_connection, m.var_name, m.disallow_access, mo.module_id AS module, m.url_value AS url, mo.is_active AS module_is_active, m.mobile_icon')
                ->from(Phpfox::getT('menu'), 'm')
                ->join(Phpfox::getT('module'), 'mo', 'mo.module_id = m.module_id AND mo.is_active = 1')
                ->join(Phpfox::getT('product'), 'p', 'm.product_id = p.product_id AND p.is_active = 1')
                ->where("m.parent_id > 0 AND m.is_active = 1")
                ->order('m.ordering ASC')
                ->execute('getRows');

            if (count($aParents)) {
                foreach ($aParents as $aParent) {
                    if (!isset($aMenus[$aParent['parent_id']])) {
                        continue;
                    }
                    $aParent['is_url'] = isset($aParent['url']) && !empty($aParent['url']) && strpos($aParent['url'], 'http') !== false;
                    if ($aParent['is_url'] && preg_match('/^' . str_replace('/', '\/', Phpfox::getBaseUrl()) . '/', $aParent['url']) === false) {
                        $aParent['external'] = true;

                    } else if (isset($aParent['url']) && $aParent['url'] == '#') {
                        $aParent['no_link'] = true;
                    }
                    $aMenus[$aParent['parent_id']]['children'][] = $aParent;
                }
            }

            if ($sPlugin = Phpfox_Plugin::get('template_template_getmenu_2')) {
                eval($sPlugin);
            }
            $oCache->save($sCachedId, $aMenus);
            Phpfox::getLib('cache')->group('theme', $sCachedId);
            Phpfox::getLib('cache')->group('menu', $sCachedId);
        }

        if (isset($this->_aMenus[$sConnection])) {
            $aMenus = [$this->_aMenus[$sConnection]];
        }

        if ($sPlugin = Phpfox_Plugin::get('template_template_getmenu_3')) {
            eval($sPlugin);
        }

        if (!is_array($aMenus)) {
            return [];
        }

        $aMenus = $this->_processMenu($aMenus, isset($aUserMenusCache) ? $aUserMenusCache : [], isset($bMenuIsSelected) ? $bMenuIsSelected : false, $sConnection);

        return $aMenus;
    }

    public function menu($title, $url, $extra = '')
    {
        if (substr($url, 0, 5) != 'http:' && $url != '#') {
            $url = \Phpfox_Url::instance()->makeUrl($url);
        }
        $css_class = '';
        $icon_class = '';
        if (is_array($extra)) {
            $css_class = isset($extra['css_class']) ? $extra['css_class'] : '';
            $icon_class = isset($extra['icon']) ? $extra['icon'] : '';
            $extra = isset($extra['extra']) ? $extra['extra'] : '';
        }

        $this->_aCustomMenus[] = [
            'title'      => $title,
            'url'        => $url,
            'extra'      => $extra,
            'css_class'  => $css_class,
            'icon_class' => $icon_class
        ];

        $this->assign('aCustomMenus', $this->_aCustomMenus);

        return $this;
    }

    /**
     * Set the current URL for the site.
     *
     * @param string $sUrl URL value.
     *
     * @return object Return self.
     */
    public function setUrl($sUrl)
    {
        $this->_sUrl = $sUrl;

        return $this;
    }

    /**
     * Load and get the XML information about the theme used when custom designing a profile.
     *
     * @param string $sXml XML id.
     *
     * @return string ARRAY of XML data.
     */
    public function getXml($sXml)
    {
        static $aXml = [];

        if (isset($aXml[$sXml])) {
            return $aXml[$sXml];
        }

        $oCache = Phpfox::getLib('cache');
        $sCacheId = $oCache->set(['theme', 'theme_xml_' . $this->_sThemeLayout]);

        if (!($aXml = $oCache->get($sCacheId))) {
            $sFile = PHPFOX_DIR_THEME . $this->_sThemeLayout . PHPFOX_DS . $this->_sThemeFolder . PHPFOX_DS . 'xml' . PHPFOX_DS . 'phpfox.xml.php';
            if (!empty($this->_aTheme['parent_theme_folder']) && !file_exists($sFile)) {
                $sFile = PHPFOX_DIR_THEME . $this->_sThemeLayout . PHPFOX_DS . $this->_aTheme['parent_theme_folder'] . PHPFOX_DS . 'xml' . PHPFOX_DS . 'phpfox.xml.php';
            }

            if (!file_exists($sFile)) {
                $sFile = PHPFOX_DIR_THEME . $this->_sThemeLayout . PHPFOX_DS . 'default' . PHPFOX_DS . 'xml' . PHPFOX_DS . 'phpfox.xml.php';
            }

            $aXml = Phpfox::getLib('xml.parser')->parse(file_get_contents($sFile));

            $oCache->save($sCacheId, $aXml);
            Phpfox::getLib('cache')->group('theme', $sCacheId);
        }

        return $aXml[$sXml];
    }

    /**
     * Build subsection menu. Also assigns all variables to the template for us.
     *
     * @param string $sSection    Internal section URL string.
     * @param array  $aFilterMenu Array of menu.
     * @param bool   $is_app      Array of menu.
     *
     * @return bool
     */
    public function buildSectionMenu($sSection, $aFilterMenu, $is_app = false)
    {
        //Hide this menu if login as page
        if (Phpfox::getUserBy('profile_page_id') > 0) {
            return false;
        }
        // Add a hook with return here
        $sView = Phpfox_Request::instance()->get('view');
        $aFilterMenuCache = [];
        $iFilterCount = 0;
        $bHasMenu = false;

        foreach ($aFilterMenu as $sMenuName => $sMenuLink) {
            if (is_numeric($sMenuName)) {
                $aFilterMenuCache[] = [];

                continue;
            }
            $sMenuName = str_replace('phpfox_numeric_friend_list_', '', $sMenuName);
            $bForceActive = false;
            $bIsView = true;
            if (strpos($sMenuLink, '.')) {
                $bIsView = false;
            }

            $iFilterCount++;

            if ($is_app) {
                $u = parse_url($sMenuLink);
                if (isset($u['query'])) {
                    parse_str($u['query'], $p);
                    if (isset($p['view']) && $p['view'] == $sView) {
                        $bForceActive = true;
                        $bHasMenu = true;
                    }
                }
                if ($sMenuLink == Phpfox_Url::instance()->current()) {
                    $bForceActive = true;
                    $bHasMenu = true;
                }
            } else if ($bIsView) {
                if ($sView == $sMenuLink && $bHasMenu === false) {
                    $bHasMenu = true;
                }

                if (!empty($sView) && $sView == $sMenuLink) {
                    $this->setTitle(preg_replace('/<span(.*)>(.*)<\/span>/i', '', $sMenuName));
                    $this->setBreadCrumb(preg_replace('/<span(.*)>(.*)<\/span>/i', '', $sMenuName), Phpfox_Url::instance()->makeUrl($sSection, (empty($sMenuLink) ? [] : ['view' => $sMenuLink])), true);
                }
            } else {
                $sFullUrl = Phpfox_Url::instance()->getFullUrl();
                $sTimeRequest = Phpfox_Request::instance()->get('_');
                if ($sTimeRequest) {
                    $sFullUrl = str_replace('&_=' . $sTimeRequest, '', $sFullUrl);
                }
                if ((empty($sView) && str_replace('/', '.', Phpfox_Url::instance()->getUrl()) == $sMenuLink)
                    || (!empty($sView) && str_replace('/', '.', Phpfox_Url::instance()->getUrl()) . '.view_' . $sView == $sMenuLink)
                    || (!empty($sView) && Phpfox_Url::instance()->getUrl() . '.view_' . $sView . '.id_' . Phpfox_Request::instance()->getInt('id') == $sMenuLink)
                    || (!empty($sView) && @preg_match('/\/view_' . $sView . '\//i', $sMenuLink))
                    || (urldecode($sFullUrl) == urldecode($sMenuLink))
                ) {
                    $bHasMenu = true;
                    $bForceActive = true;

                    foreach ($aFilterMenuCache as $iSubKey => $aFilterMenuCacheRow) {
                        if (isset($aFilterMenuCache[$iSubKey]['active']) && $aFilterMenuCache[$iSubKey]['active'] === true) {
                            $aFilterMenuCache[$iSubKey]['active'] = false;
                            break;
                        }
                    }
                }
            }

            $aFilterMenuCache[] = [
                'name'   => $sMenuName,
                'link'   => (!$bIsView ? Phpfox_Url::instance()->makeUrl($sMenuLink) : Phpfox_Url::instance()->makeUrl($sSection, (empty($sMenuLink) ? [] : ['view' => $sMenuLink]))),
                'active' => ($bForceActive ? true : ($sView == $sMenuLink ? true : false)),
                'last'   => (count($aFilterMenu) === $iFilterCount ? true : false)
            ];
        }

        if (!$bHasMenu && isset($aFilterMenuCache[0])) {
            $aFilterMenuCache[0]['active'] = true;
        }

        if (Phpfox::getParam('user.hide_main_menu') && !Phpfox::getUserId()) {
            $aFilterMenuCache = [];
        }
        $this->assign([
                'aFilterMenus' => $aFilterMenuCache,
            ]
        );
    }

    public function setSubMenu($menu)
    {
        $this->_subMenu = $menu;
    }

    public function getSubMenu()
    {
        if (!$this->_subMenu) {
            return '';
        }

        $current = trim(Phpfox_Request::instance()->uri(), '/');
        if (is_string($this->_subMenu)) {
            $current = Phpfox_Url::instance()->makeUrl($current);
            $this->_subMenu = preg_replace('/href\=\"' . preg_quote($current, '/') . '\"/i', 'href="' . $current . '" class="active"', $this->_subMenu);

            return $this->_subMenu;
        }

        $html = '<div class="section_menu"><ul>';
        foreach ($this->_subMenu as $name => $url) {
            $active = '';
            $check = trim($url, '/');
            if ($check == $current) {
                $active = ' class="active"';
            }

            $html .= '<li><a href="' . Phpfox_Url::instance()->makeUrl($url) . '"' . $active . '>' . $name . '</a></li>';
        }
        $html .= '</ul></div>';

        return $html;
    }

    /**
     * Gets the JavaScript code needed for section menus built with self::buildPageMenu()
     *
     * @return string Returns JS code
     * @see self::buildPageMenu()
     */
    public function getSectionMenuJavaScript()
    {
        if (!isset($this->_aSectionMenu['name'])) {
            return '<script type="text/javascript">$Behavior.pageSectionMenuRequest = function() { }</script>';
        }

        if ($this->_aSectionMenu['bIsFullLink']) {
            return '<script type="text/javascript">$Behavior.pageSectionMenuRequest = function() { }</script>';
        }

        return '';
    }

    /**
     * Builds a section menu for adding/editing items
     *
     * @param string $sName Name of the menu
     * @param array  $aMenu ARRAY of menus
     * @param mixed  $aLink ARRAY for custom view link, NULL to do nothing
     * @param bool   $bIsFullLink
     */
    public function buildPageMenu($sName, $aMenu, $aLink = null, $bIsFullLink = false)
    {
        $this->_aSectionMenu = [
            'name'        => $sName,
            'menu'        => $aMenu,
            'link'        => $aLink,
            'bIsFullLink' => $bIsFullLink
        ];

        // current url
        $sPageCurrentUrl = Phpfox_Url::instance()->makeUrl('current');
        // current tab
        $sCurrentTab = Phpfox_Request::instance()->get('tab');
        // check active tab
        foreach ($aMenu as $sTabId => $sTabName) {
            if (($bIsFullLink && ($sTabId == $sPageCurrentUrl)) ||
                (!$bIsFullLink && $sCurrentTab && $sTabId == $sCurrentTab)
            ) {
                $sActiveTab = $sTabId;
            }
        }

        if (!isset($sActiveTab) && !$bIsFullLink) {
            // set first menu as active
            $sActiveTab = key($aMenu);
        }

        $this->assign([
                'sPageSectionMenuName' => $sName,
                'aPageSectionMenu'     => $aMenu,
                'aPageExtraLink'       => $aLink,
                'bPageIsFullLink'      => $bIsFullLink,
                'sActiveTab'           => $sActiveTab
            ]
        );
    }

    /**
     * @deprecated 4.7.0
     * This function controls if we should load `content` delayed. It is called from the template.cache library
     */
    public function shouldLoadDelayed($sController)
    {
        return false;
    }

    public function getCacheName($sName)
    {
        return $this->_getCachedName($this->getTemplateFile($sName));
    }

    /**
     * Get a menu.
     *
     * @param string $sConnection Connection for the menu.
     * @param int    $iParent     Parent ID# number for the menu.
     *
     * @return array ARRAY of menus.
     */
    private function _getMenu($sConnection = null, $iParent = 0)
    {
        return Phpfox_Database::instance()->select('m.menu_id, m.parent_id, m.m_connection, m.var_name, m.disallow_access, mo.module_id AS module, m.url_value AS url, mo.is_active AS module_is_active, m.mobile_icon')
            ->from(Phpfox::getT('menu'), 'm')
            ->join(Phpfox::getT('module'), 'mo', 'mo.module_id = m.module_id AND mo.is_active = 1')
            ->join(Phpfox::getT('product'), 'p', 'm.product_id = p.product_id AND p.is_active = 1')
            ->where("m.parent_id = " . (int)$iParent . " AND m.m_connection = '" . Phpfox_Database::instance()->escape($sConnection) . "' AND m.is_active = 1")
            ->group('m.menu_id', true)
            ->order('m.ordering ASC')
            ->execute('getRows');
    }

    /**
     * Returns the content of a template that has already been echoed.
     *
     * @return string
     */
    private function _returnLayout()
    {
        $sContent = ob_get_contents();

        ob_clean();

        return $sContent;
    }

    /**
     * Gets a template file from cache. If the file does not exist we re-cache the template.
     *
     * @param string $sFile Full path of the template we are loading.
     */
    private function _getFromCache($sFile, $sTemplate = null)
    {
        if (is_array($sFile)) {
            $sContent = $sFile[0];
            $sFile = $sTemplate;
        }

        $bGetTemplateCacheSucceeded = false;
        if (!defined('PHPFOX_NO_TEMPLATE_CACHE') && !defined('PHPFOX_INSTALLER_NO_TMP')) {
            try {
                (PHPFOX_DEBUG ? Phpfox_Debug::start('template') : false);
                $bGetTemplateCacheSucceeded = $this->_requireFile($sFile);
                (PHPFOX_DEBUG ? Phpfox_Debug::end('template', ['name' => $sFile]) : false);
            } catch (Exception $ex) {
                $bGetTemplateCacheSucceeded = false;
            }
        }
        if (!$bGetTemplateCacheSucceeded) {
            /** @var Phpfox_Template_Cache $oTplCache */
            $oTplCache = Phpfox::getLib('template.cache');
            if (!isset($sContent)) {
                $sContent = (file_exists($sFile) ? file_get_contents($sFile) : null);
            }
            $mData = $oTplCache->compile($this->_getCachedName($sFile), $sContent);
            // No cache directory so we must
            if (defined('PHPFOX_INSTALLER_NO_TMP')) {
                eval(' ?>' . $mData . '<?php ');
                return;
            }
            (PHPFOX_DEBUG ? Phpfox_Debug::start('template') : false);
            $this->_requireFile($sFile);
            (PHPFOX_DEBUG ? Phpfox_Debug::end('template', ['name' => $sFile]) : false);
        }
    }

    /**
     * @param $sFile
     *
     * @return bool
     */
    private function _requireFile($sFile)
    {
        if (defined('PHPFOX_IS_HOSTED_SCRIPT')) {
            $oCache = Phpfox::getLib('cache');
            $sId = $oCache->set(md5($this->_getCachedName($sFile)));
            $contentCache = $oCache->get($sId);
            if ($contentCache === false) {
                return false;
            }
            eval('?>' . $contentCache . '<?php ');
            return true;
        } else {
            if ((@include $this->_getCachedName($sFile)) == false) {
                throw new RuntimeException('File not found. ' . $sFile);
            }
            return true;
        }
    }

    public function setActiveMenu($tags)
    {
        Phpfox::getService('admincp.sidebar')->setActive($tags);
        return $this;
    }

    /**
     * Gets the full path of the cached template file
     *
     * @param string $sName Name of the template
     *
     * @return string Full path to cached template
     */
    private function _getCachedName($sName)
    {
        if (!defined('PHPFOX_INSTALLER')) {
            if (!is_dir(PHPFOX_DIR_CACHE . 'template' . PHPFOX_DS . flavor()->active->id . PHPFOX_DS)) {
                mkdir(PHPFOX_DIR_CACHE . 'template' . PHPFOX_DS . flavor()->active->id . PHPFOX_DS, 0777, true);
            }
        } else {
            return $sName;
        }

        return (defined('PHPFOX_TMP_DIR') ? PHPFOX_TMP_DIR : PHPFOX_DIR_CACHE) . ((defined('PHPFOX_TMP_DIR') || PHPFOX_SAFE_MODE) ? 'template_' : 'template' . PHPFOX_DS . flavor()->active->id . PHPFOX_DS) . str_replace([PHPFOX_DIR_SITE, PHPFOX_DIR_SITE_APPS, PHPFOX_DIR_THEME, PHPFOX_DIR_MODULE, '/', '\\'], ['', '', '', '', '_', '_'], $sName) . (Phpfox::isAdminPanel() ? '_admincp' : '') . (PHPFOX_IS_AJAX ? '_ajax' : '') . '.php';
    }

    public function getDirectoryName($name)
    {
        return isset($this->_aDirectoryNames[$name]) ? $this->_aDirectoryNames[$name] : null;
    }

    private function _register($sType, $sFunction, $sImplementation)
    {
        $this->_aPlugins[$sType][$sFunction] = $sImplementation;
    }

    private function getUserBootScript()
    {
        ob_start();
        Phpfox::getBlock('core.template-notification');
        $sticky_bar = ob_get_contents();
        ob_clean();

        if (auth()->isLoggedIn()) {
            $image = Phpfox_Image_Helper::instance()->display([
                'user'   => Phpfox::getUserBy(),
                'suffix' => '_120_square'
            ]);

            $imageUrl = Phpfox_Image_Helper::instance()->display([
                'user'       => Phpfox::getUserBy(),
                'suffix'     => '_50_square',
                'return_url' => true
            ]);

            $image = htmlspecialchars($image);
            $image = str_replace(['<', '>'], ['&lt;', '&gt;'], $image);

            $sticky_bar .= '<div id="auth-user" data-image-url="' . str_replace("\"", '\'', $imageUrl) . '" data-user-name="' . Phpfox::getUserBy('user_name') . '" data-id="' . Phpfox::getUserId() . '" data-name="' . Phpfox::getUserBy('full_name') . '" data-image="' . $image . '"></div>';

        }
        if (Phpfox::isUser()) {
            Phpfox::massCallback('getGlobalNotifications');
        }

        echo 'var user_boot = ' . json_encode(['sticky_bar' => $sticky_bar]) . ';';
        echo 'var user_obj = document.getElementById(\'user_sticky_bar\');';
        echo 'if (user_obj !== null) { document.getElementById(\'user_sticky_bar\').innerHTML = user_boot.sticky_bar;';

        $notifications = Phpfox_Ajax::instance()->returnCalls();
        echo '$Event(function() {';
        if ($notifications) {
            foreach ($notifications as $call) {
                echo $call;
            }
        }

        if (Phpfox::isModule('notification') && Phpfox::isUser() && Phpfox::getParam('notification.notify_on_new_request')) {
            echo 'if (typeof $Core.notification !== \'undefined\') $Core.notification.setTitle();';
        }

        if ($sPlugin = Phpfox_Plugin::get('notification.component_ajax_update_1')) {
            $sPlugin = str_replace('$this->call(', 'print(', $sPlugin);
            eval($sPlugin);
        }
        echo '});';
        echo '}';

        return ob_get_clean();
    }

    /**
     * Override the main layout of the site (change layout.html to whatever you like).
     * Your custom layout should be placed inside current theme folder respectively to layout.html
     *
     * @param string $sName Layout we should load.
     *
     * @since 4.7.0
     */
    public function setTemplateLayout($sName)
    {
        \Core\View::$template = $sName;
    }

    /**
     * @param array $aMenus
     * @param $aUserMenusCache
     * @param $bMenuIsSelected
     * @param array $aActiveMenus
     * @param string $sConnection
     * @return array
     */
    private function _processMenu($aMenus, $aUserMenusCache, $bMenuIsSelected, $sConnection)
    {
        $aActiveMenus = [];
        $oReq = Phpfox_Request::instance();
        foreach ($aMenus as $iKey => $aMenu) {
            if (substr($aMenu['url'], 0, 1) == '#') {
                $aMenus[$iKey]['css_name'] = 'js_core_menu_' . str_replace('#', '', str_replace('-', '_', $aMenu['url']));
            }

            if (($aMenu['url'] == 'ad' || $aMenu['url'] == 'ad.index') && !Phpfox::getUserParam('better_can_create_ad_campaigns')) {
                unset($aMenus[$iKey]);
                continue;
            }

            if ($aMenu['url'] == 'mail.compose' && Phpfox::getUserParam('mail.restrict_message_to_friends') && !Phpfox::isModule('friend')) {
                unset($aMenus[$iKey]);
                continue;
            }

            if (isset($aUserMenusCache[$aMenu['menu_id']])) {
                $aMenus[$iKey]['is_force_hidden'] = true;
            }
            if (defined('PHPFOX_IS_PAGES_VIEW')) {
                if (Phpfox::isAppActive('Core_Pages') && $aMenu['url'] == 'blog.add') {
                    $iPage = $this->_aVars['aPage']['page_id'];

                    $aMenus[$iKey]['url'] = 'blog.add.module_pages.item_' . $iPage;
                }
                if (Phpfox::isAppActive('Core_Pages') && $aMenu['url'] == 'event.add') {
                    $iPage = $this->_aVars['aPage']['page_id'];

                    $aMenus[$iKey]['url'] = 'event.add.module_pages.item_' . $iPage;
                }
                if (Phpfox::isAppActive('Core_Pages') && $aMenu['url'] == 'music.upload') {
                    $iPage = $this->_aVars['aPage']['page_id'];

                    $aMenus[$iKey]['url'] = 'music.upload.module_pages.item_' . $iPage;
                }
                if (Phpfox::isAppActive('Core_Pages') && $aMenu['url'] == 'photo.add') {
                    $iPage = $this->_aVars['aPage']['page_id'];

                    $aMenus[$iKey]['url'] = 'photo.add.module_pages.item_' . $iPage;
                }

                if (defined('PHPFOX_PAGES_ITEM_TYPE') && PHPFOX_PAGES_ITEM_TYPE == $aMenu['module'] && !$aMenu['is_url'] && !$aMenu['parent_id']) {
                    $aMenus[$iKey]['is_selected'] = true;
                }

                (($sPlugin = Phpfox_Plugin::get('template_template_getmenu_in_pages')) ? eval($sPlugin) : false);
            }
            $sCurrentUrl = urldecode(Phpfox::getLib('url')->makeUrl('current'));
            $sCurrentUrl = trim($sCurrentUrl, '/');
            $sCurrentUrl = str_replace('/?', '?', $sCurrentUrl);
            $sShortUrl = $aMenu['url'];
            $sCurrentUrlByDot = str_replace(Phpfox::getLib('url')->makeUrl(''), '', $sCurrentUrl);
            $sCurrentUrlByDot = str_replace('/', '.', trim($sCurrentUrlByDot, '/'));
            if (empty($aMenu['external']) && preg_match('/^(http|https):\/\//i', trim($aMenu['url']))) {
                $sShortUrl = str_replace(Phpfox::getLib('url')->makeUrl(''), '', $aMenu['url']);
            }
            $sShortUrl = str_replace('/?', '?', $sShortUrl);
            $sShortUrl = str_replace('/', '.', trim($sShortUrl, '/'));
            $sShortUrl = urldecode($sShortUrl);
            if ((trim($aMenu['url'], '/') == $oReq->get('req1')) ||
                (empty($aMenu['url']) && $oReq->get('req1') == PHPFOX_MODULE_CORE) ||
                ($this->_sUrl !== null && $this->_sUrl == $aMenu['url']) ||
                (trim($aMenu['url'], '/') == $sCurrentUrl) ||
                ($sShortUrl == $sCurrentUrlByDot) ||
                (str_replace('/', '.', $oReq->get('req1') . $oReq->get('req2')) == str_replace('.', '', $aMenu['url'])) ||
                !empty($bMenuIsSelected)
            ) {
                $aMenus[$iKey]['is_selected'] = true;
            }

            if (!empty($aMenus[$iKey]['is_selected'])) {
                $iTotalDot = substr_count($sShortUrl, '.');
                if (count($aActiveMenus)) {
                    foreach ($aActiveMenus as $sActiveKey => $iIndexKey) {
                        $iTotalDotActive = substr_count($sActiveKey, '.');
                        if ($iTotalDot > $iTotalDotActive) {
                            $aMenus[$iIndexKey]['is_selected'] = strpos($sShortUrl, $sActiveKey . '.') === false;
                        } elseif ($iTotalDot < $iTotalDotActive) {
                            $aMenus[$iKey]['is_selected'] = strpos($sActiveKey, $sShortUrl . '.') === false;
                        } else {
                            if (!empty($aMenus[$iIndexKey]['is_url'])) {
                                $aMenus[$iKey]['is_selected'] = false;
                            } else {
                                $aMenus[$iIndexKey]['is_selected'] = $aMenus[$iIndexKey]['module'] != $aMenus[$iKey]['module'] || empty($aMenus[$iKey]['is_url']);
                            }
                        }
                    }
                }
                if ($aMenus[$iKey]['is_selected']) {
                    $aActiveMenus[$sShortUrl] = $iKey;
                }
            }

            if ($aMenu['url'] == 'admincp') {
                if (!Phpfox::isAdmin()) {
                    unset($aMenus[$iKey]);

                    continue;
                }
            } else {
                if (!empty($aMenu['disallow_access'])) {
                    $aUserGroups = unserialize($aMenu['disallow_access']);
                    if (in_array(Phpfox::getUserBy('user_group_id'), $aUserGroups)) {
                        unset($aMenus[$iKey]);

                        continue;
                    }
                }
                if (isset($aMenu['children']) && is_array($aMenu['children'])) {
                    $aMenus[$iKey]['children'] = $this->_processMenu($aMenu['children'], $aUserMenusCache, $bMenuIsSelected, $sConnection);
                }
            }

            if (isset($this->_aNewUrl[$sConnection])) {
                $aMenus[$iKey]['url'] = $this->_aNewUrl[$sConnection][0] . '.' . implode('.', $this->_aNewUrl[$sConnection][1]) . '.' . $aMenu['url'];
            }

            if (isset($this->_aRemoveUrl[$sConnection][$aMenu['url']])) {
                unset($aMenus[$iKey]);

                continue;
            }

            if ($sPlugin = Phpfox_Plugin::get('template_template_getmenu_process_menu')) {
                eval($sPlugin);
            }
        }
        return $aMenus;
    }
}