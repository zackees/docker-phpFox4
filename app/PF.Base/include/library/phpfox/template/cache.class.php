<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 * Template Cache
 * Class handles the caching of a template file and converts any
 * custom code into PHP code. Class is only loaded if the template
 * cache file does not exist.
 *
 * @copyright        [PHPFOX_COPYRIGHT]
 * @author            phpFox LLC
 * @package        Phpfox
 * @subpackage        Template
 * @version        $Id: cache.class.php 6361 2013-07-25 08:37:06Z phpFox LLC $
 */
class Phpfox_Template_Cache extends Phpfox_Template
{
    /**
     * Foreach stack.
     *
     * @var array
     */
    private $_aForeachElseStack = array();

    /**
     * Require stack.
     *
     * @var array
     */
    private $_aRequireStack = array();

    /**
     * PHP blocks. {php}{/php}
     *
     * @var array
     */
    private $_aPhpBlocks = array();

    /**
     * Section blocks. {section}{/section}
     *
     * @var array
     */
    private $_aSectionelseStack = array();

    /**
     * Module blocks.
     *
     * @var array
     */
    private $_aModuleBlocks = array();

    /**
     * Literal blocks. {literal}{/literal}
     *
     * @var array
     */
    private $_aLiterals = array();

    /**
     * String regex.
     *
     * @var string
     */
    private $_sDbQstrRegexp = '"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"';

    /**
     * String regex.
     *
     * @var string
     */
    private $_sSiQstrRegexp = '\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'';

    /**
     * Bracket regex.
     *
     * @var string
     */
    private $_sVarBracketRegexp = '\[[\$|\#]?\w+\#?\]';

    /**
     * Variable regex.
     *
     * @var string
     */
    private $_sSvarRegexp = '\%\w+\.\w+\%';

    /**
     * Function regex.
     *
     * @var string
     */
    private $_sFuncRegexp = '[a-zA-Z_]+';

    private $_aBlocklets = [];

    private $_sCurrentFile = '';

    /**
     * Class constructor. Build all the regex we will be using
     * with this class.
     */
    public function __construct()
    {
        $this->_sQstrRegexp = '(?:' . $this->_sDbQstrRegexp . '|' . $this->_sSiQstrRegexp . ')';

        $this->_sDvarRegexp = '\$[a-zA-Z0-9_]{1,}(?:' . $this->_sVarBracketRegexp . ')*(?:\.\$?\w+(?:' . $this->_sVarBracketRegexp . ')*)*';

        $this->_sCvarRegexp = '\#[a-zA-Z0-9_]{1,}(?:' . $this->_sVarBracketRegexp . ')*(?:' . $this->_sVarBracketRegexp . ')*\#';

        $this->_sVarRegexp = '(?:(?:' . $this->_sDvarRegexp . '|' . $this->_sCvarRegexp . ')|' . $this->_sQstrRegexp . ')';

        $this->_sModRegexp = '(?:\|@?[0-9a-zA-Z_]+(?::(?>-?\w+|' . $this->_sDvarRegexp . '|' . $this->_sQstrRegexp . '))*)';
    }

    /**
     * Compile a template file and cache it to a PHP flat file.
     *
     * @param string $sName Name of the template.
     * @param string $sData Contents of the template.
     * @param bool $bRemoveHeader TRUE to remove the time stamp we added to the header of each cache file.
     * @param bool $bSkipDbCheck TRUE to skip checks on the database to see if the cache file exists there as well.
     * @return mixed We only return the templates content if the installer does not have a writable directory.
     */
    public function compile($sName, $sData = null, $bRemoveHeader = false, $bSkipDbCheck = false)
    {
        $this->_sCurrentFile = $sName;
        $sData = $this->_parse($sData, $bRemoveHeader);

        if (defined('PHPFOX_INSTALLER_NO_TMP')) {
            return $sData;
        }

        $sContent = '';
        $aLines = explode("\n", $sData);

        foreach ($aLines as $sLine) {
            if (preg_match("/<\?php(.*?)\?>/i", $sLine)) {
                if (substr(trim($sLine), 0, 5) == '<?php') {
                    $sContent .= trim($sLine) . "\n";
                } else {
                    $sContent .= $sLine . "\n";
                }
            } else {
                $sContent .= $sLine . "\n";
            }
        }

        $sContent = preg_replace("/defined\('PHPFOX'\) or exit\('NO DICE!'\);/is", "", $sContent);
        $sContent = "<?php defined('PHPFOX') or exit('NO DICE!'); ?>\n" . $sContent;
        $sContent = str_replace('<body>', '<body id="page_<?php echo Phpfox::getLib(\'module\')->getPageId(); ?>">', $sContent);

        if ($sPlugin = Phpfox_Plugin::get('library_template_cache_compile__1')) {
            eval($sPlugin);
            if (isset($aPluginReturn)) {
                return $aPluginReturn;
            }
        }

        if (defined('PHPFOX_IS_HOSTED_SCRIPT')) {
            $oCache = Phpfox::getLib('cache');
            $sId = $oCache->set(md5($sName));
            $oCache->save($sId, $sContent);
            Phpfox::getLib('cache')->group('template', $sId);
            return null;
        }
        if ($rFile = @fopen($sName, 'w+')) {
            fwrite($rFile, $sContent);
            fclose($rFile);
        } else {
            return Phpfox_Error::trigger('Unable to cache template file: ' . $sName, E_USER_NOTICE);
        }
    }

    /**
     * Get all the template files that have been cached.
     *
     * @return array List of cached files.
     */
    public function getAll()
    {
        if ($hDir = @opendir(PHPFOX_DIR_CACHE)) {
            $aFiles = array();
            while ($sFile = readdir($hDir)) {
                if (substr($sFile, 0, 9) != 'template_') {
                    continue;
                }

                $aFiles[] = array(
                    'id' => md5($sFile),
                    'name' => $sFile,
                    'size' => filesize(PHPFOX_DIR_CACHE . $sFile),
                    'date' => filemtime((PHPFOX_DIR_CACHE . $sFile)),
                    'type' => 'Template'
                );
            }
            closedir($hDir);

            return $aFiles;
        }

        return array();
    }

    /**
     * Remove a cache file or the entire directory.
     *
     * @param mixed $sName Pass nothing to remove all cached files, or the STRING name of the file to just remove that file.
     * @return bool TRUE if removed, FALSE if not.
     */
    public function remove($sName = null)
    {
        if ($sName === null) {
            Phpfox::getLib('file')->removeDirectory(PHPFOX_DIR_CACHE . 'template');
            Phpfox::getLib('file')->removeDirectory(PHPFOX_DIR_CACHE . 'twig');
            return true;
        }

        if (file_exists(PHPFOX_DIR_CACHE . $sName)) {
            @unlink(PHPFOX_DIR_CACHE . $sName);
            return true;
        }

        return false;
    }

    /**
     * Parse a templates content and convert it into PHP.
     *
     * @see self::_parse()
     * @param string $sData Content of the template.
     * @param bool $bRemoveHeader TRUE to remove cache headers in the template.
     * @param bool $bKeepOriginalOnError , keep original data when template do not have that function
     * @return string Parsed and converted content.
     */
    public function parse($sData, $bRemoveHeader = false, $bKeepOriginalOnError = false)
    {
        $sData = $this->_parse($sData, $bRemoveHeader, $bKeepOriginalOnError);

        return $sData;
    }

    /**
     * Parse a templates content and convert it into PHP.
     *
     * @param string $sData Content of the template.
     * @param bool $bRemoveHeader TRUE to remove cache headers in the template.
     * @param bool $bKeepOriginalOnError , keep original data when template do not have that function
     * @return string Parsed and converted content.
     */
    private function _parse($sData, $bRemoveHeader = false, $bKeepOriginalOnError = false)
    {
        if ($sPlugin = Phpfox_Plugin::get('library_template_cache_parse__1')) {
            eval($sPlugin);
            if (isset($aPluginReturn)) {
                return $aPluginReturn;
            }
        }

        $sLdq = preg_quote($this->sLeftDelim);
        $sRdq = preg_quote($this->sRightDelim);
        $aText = array();
        $sCompiledText = '';

        // Remove phpFox SVN headers
        $sData = preg_replace("/\<\!phpfox(.*?)\>/is", "", $sData);

        // Add a security token in a form
        $sData = preg_replace_callback("/<form(.*?)>(.*?)<\/form>/is", array($this, '_parseForm'), $sData);

        // remove all comments
        $sData = preg_replace("/{$sLdq}\*(.*?)\*{$sRdq}/s", "", $sData);

        // remove literal blocks
        preg_match_all("!{$sLdq}\s*literal\s*{$sRdq}(.*?){$sLdq}\s*/literal\s*{$sRdq}!s", $sData, $aMatches);
        $this->_aLiterals = $aMatches[1];
        $sData = preg_replace("!{$sLdq}\s*literal\s*{$sRdq}(.*?){$sLdq}\s*/literal\s*{$sRdq}!s", stripslashes($sLdq . "literal" . $sRdq), $sData);

        // remove php blocks
        preg_match_all("!{$sLdq}\s*php\s*{$sRdq}(.*?){$sLdq}\s*/php\s*{$sRdq}!s", $sData, $aMatches);
        $this->_aPhpBlocks = $aMatches[1];
        $sData = preg_replace("!{$sLdq}\s*php\s*{$sRdq}(.*?){$sLdq}\s*/php\s*{$sRdq}!s", stripslashes($sLdq . "php" . $sRdq), $sData);

        // remove blocklets
        preg_match_all("!{$sLdq}\s*blocklet location=([0-9]+){$sRdq}(.*?){$sLdq}\s*/blocklet\s*{$sRdq}!s", $sData, $aMatches);
        $this->_aBlocklets = $aMatches;
        $sData = preg_replace("!{$sLdq}\s*blocklet location=([0-9]+){$sRdq}(.*?){$sLdq}\s*/blocklet\s*{$sRdq}!s", stripslashes($sLdq . "blocklet" . $sRdq), $sData);
        $sData = preg_replace_callback("!\{_p\('(.*?)'\)\}!s", function ($matches) {
            return '<?php echo _p(\'' . $matches[1] . '\'); ?>';
        }, $sData);

        $aText = preg_split("!{$sLdq}.*?{$sRdq}!s", $sData);

        preg_match_all("!{$sLdq}\s*(.*?)\s*{$sRdq}!s", $sData, $aMatches);
        $aTags = $aMatches[1];

        $aCompiledTags = array();
        $iCompiledTags = count($aTags);
        for ($i = 0, $iForMax = $iCompiledTags; $i < $iForMax; $i++) {
            $compile = $this->_compileTag($aTags[$i], $bKeepOriginalOnError);
            if ($bKeepOriginalOnError && $compile === false) {
                $aCompiledTags[] = $this->sLeftDelim . $aTags[$i] . $this->sRightDelim;
            } else {
                $aCompiledTags[] = $compile;
            }
        }

        $iCountCompiledTags = count($aCompiledTags);
        for ($i = 0, $iForMax = $iCountCompiledTags; $i < $iForMax; $i++) {
            if ($aCompiledTags[$i] == '') {
                $aText[$i + 1] = preg_replace('~^(\r\n|\r|\n)~', '', $aText[$i + 1]);
            }
            $sCompiledText .= $aText[$i] . $aCompiledTags[$i];
        }
        $sCompiledText .= $aText[$i];

        foreach ($this->_aRequireStack as $mKey => $mValue) {
            $sCompiledText = '<?php require_once(\'' . PHPFOX_DIR_TPL_PLUGIN . $mKey . '\'); $this->_register("' . $mValue[0] . '", "' . $mValue[1] . '", "' . $mValue[2] . '"); ?>' . $sCompiledText;
        }

        $sCompiledText = preg_replace('!\?>\n?<\?php!', '', $sCompiledText);

        $sCompiledText = '<?php /* Cached: ' . date("F j, Y, g:i a", time()) . ' */ ?>' . "\n" . $sCompiledText;

        return $sCompiledText;
    }

    /**
     * Parse HTML forms. This is where we automatically add our security token.
     *
     * @param string $aMatches ARRAY of regex matches
     * @return string Converted form.
     */
    private function _parseForm($aMatches)
    {
        $sForm = $aMatches[1];
        $sData = $aMatches[2];

        $sForm = '<form' . stripslashes($sForm) . ">";
        $sForm .= stripslashes($sData) . "\n";
        $sForm .= '</form>' . "\n";

        return $sForm;
    }

    /**
     * Compile custom tags. (eg. {literal})
     *
     * @param string $sTag Name of the tag to parse.
     * @param bool $bKeepOriginalOnError , keep original data when template do not have that function
     * @return string Converted block of code based on the tag.
     */
    private function _compileTag($sTag, $bKeepOriginalOnError = false)
    {
        preg_match_all('/(?:(' . $this->_sVarRegexp . '|' . $this->_sSvarRegexp . '|\/?' . $this->_sFuncRegexp . ')(' . $this->_sModRegexp . '*)(?:\s*[,\.]\s*)?)(?:\s+(.*))?/xs', $sTag, $aMatches);

        if ($aMatches[1][0][0] == '$' || $aMatches[1][0][0] == "'" || $aMatches[1][0][0] == '"') {
            return "<?php echo " . $this->_parseVariables($aMatches[1], $aMatches[2]) . "; ?>";
        }

        $sTagCommand = $aMatches[1][0];
        $sTagModifiers = !empty($aMatches[2][0]) ? $aMatches[2][0] : null;
        $sTagArguments = !empty($aMatches[3][0]) ? $aMatches[3][0] : null;

        $result = $this->_parseFunction($sTagCommand, $sTagModifiers, $sTagArguments, $bKeepOriginalOnError);
        if ($bKeepOriginalOnError && $result === false) {
            return false;
        }

        return $result;
    }

    public function parseFunction($sFunction, $sModifiers, $sArguments, $bKeepOriginalOnError = false)
    {
        return $this->_parseFunction($sFunction, $sModifiers, $sArguments, $bKeepOriginalOnError);
    }

    /**
     * Parse all the custom tags used within templates. In templates we
     * do not use conventional PHP code as we seperate PHP logic from the
     * template. The tags we use work similar to that off SMARTY.
     *
     * @param string $sFunction Name of the function.
     * @param string $sModifiers Modifiers.
     * @param string $sArguments Any arguments we are passing.
     * @param bool $bKeepOriginalOnError
     * @return string Converted PHP value of the function.
     */
    private function _parseFunction($sFunction, $sModifiers, $sArguments, $bKeepOriginalOnError = false)
    {
        switch ($sFunction) {
            /**
             * SMARTY
             */
            case 'ldelim':
                return $this->sLeftDelim;
            case 'rdelim':
                return $this->sRightDelim;
            case 'php':
                if (!Phpfox::getParam('core.is_auto_hosted')) {
                    $sPhpBlock = array_shift($this->_aPhpBlocks);
                    return '<?php ' . $sPhpBlock . ' ?>';
                } else {
                    return '';
                }
            case 'iterate':
                $aArgs = $this->_parseArgs($sArguments);
                return '<?php ' . $aArgs['int'] . '++; ?>';
            case 'for':
                $sArguments = preg_replace_callback("/\\$([A-Za-z0-9]+)/is", function ($matches) {
                    return $this->_parseVariable($matches[0]);
                }, $sArguments);

                return '<?php for (' . $sArguments . '): ?>';
            case '/for':
                return "<?php endfor; ?>";
            case 'left_curly':
            case 'l':
                return '{';
            case 'right_curly':
            case 'r':
                return '}';
            case 'assign':
                $aArgs = $this->_parseArgs($sArguments);
                if (!isset($aArgs['var'])) {
                    return '';
                }
                if (!isset($aArgs['value'])) {
                    return '';
                }
                return '<?php $this->assign(\'' . $this->_removeQuote($aArgs['var']) . '\', ' . $aArgs['value'] . '); ?>';
            case 'nomoreie':
            case 'blocklet':

                return '';
            case 'literal':
                $sLiteral = array_shift($this->_aLiterals);
                return "<?php echo '" . str_replace("'", "\'", $sLiteral) . "'; ?>\n";
            case 'foreach':
                array_push($this->_aForeachElseStack, false);
                $aArgs = $this->_parseArgs($sArguments);
                if (!isset($aArgs['from'])) {
                    return '';
                }
                if (!isset($aArgs['value']) && !isset($aArgs['item'])) {
                    return '';
                }
                if (isset($aArgs['value'])) {
                    $aArgs['value'] = $this->_removeQuote($aArgs['value']);
                } elseif (isset($aArgs['item'])) {
                    $aArgs['value'] = $this->_removeQuote($aArgs['item']);
                }

                (isset($aArgs['key']) ? $aArgs['key'] = "\$this->_aVars['" . $this->_removeQuote($aArgs['key']) . "'] => " : $aArgs['key'] = '');

                $bIteration = (isset($aArgs['name']) ? true : false);

                $sResult = '<?php if (count((array)' . $aArgs['from'] . ')): ?>' . "\n";
                if ($bIteration) {
                    $sResult .= '<?php $this->_aPhpfoxVars[\'iteration\'][\'' . $aArgs['name'] . '\'] = 0; ?>' . "\n";
                }
                $sResult .= '<?php foreach ((array) ' . $aArgs['from'] . ' as ' . $aArgs['key'] . '$this->_aVars[\'' . $aArgs['value'] . '\']): ?>';
                if ($bIteration) {
                    $sResult .= '<?php $this->_aPhpfoxVars[\'iteration\'][\'' . $aArgs['name'] . '\']++; ?>' . "\n";
                }
                return $sResult;
            case 'foreachelse':
                $this->_aForeachElseStack[count($this->_aForeachElseStack) - 1] = true;
                return "<?php endforeach; else: ?>";
            case '/foreach':
                if (array_pop($this->_aForeachElseStack)) {
                    return "<?php endif; ?>";
                } else {
                    return "<?php endforeach; endif; ?>";
                }
            case 'if':
                return $this->_compileIf($sArguments);
            case 'else':
                return "<?php else: ?>";
            case 'elseif':
                return $this->_compileIf($sArguments, true);
            case '/if':
                return "<?php endif; ?>";
            case 'section':
                array_push($this->_aSectionelseStack, false);
                return $this->_compileSectionStart($sArguments);
            case 'sectionelse':
                $this->_aSectionelseStack[count($this->_aSectionelseStack) - 1] = true;
                return "<?php endfor; else: ?>";
            case '/section':
                if (array_pop($this->_aSectionelseStack)) {
                    return "<?php endif; ?>";
                } else {
                    return "<?php endfor; endif; ?>";
                }
            /**
             * phpFox
             */
            case 'title':
                return '<?php echo $this->getTitle(); ?>';
            case 'header':
                return '<?php echo $this->getHeader(); ?>';
            case 'loadjs':
                return '<?php echo $this->_sFooter; ?>';
            case 'block':
                $aArgs = $this->_parseArgs($sArguments);

                $sContent = '';
                $sContent .= '<?php if (!Phpfox::isAdminPanel()): ?>';
                $sContent .= '<div class="_block" data-location="' . $this->_removeQuote($aArgs['location']) . '">';
                $sContent .= '<?php endif; ?>';

                $sContent .= '<?php if ($this->bIsSample): ?>';
                $sContent .= '<?php if (defined(\'PHPFOX_NO_WINDOW_CLICK\')): ?>';
                $sContent .= '<?php if (defined(\'PHPFOX_IS_AD_SAMPLE\')): Phpfox::getBlock(\'ad.sample\', array(\'block_id\' => ' . $this->_removeQuote($aArgs['location']) . ')); endif; ?>';
                $sContent .= '<?php else: ?>';
                $sContent .= '<div class="sample"<?php echo (!defined(\'PHPFOX_NO_WINDOW_CLICK\') ? " onclick=\"window.parent.$(\'#location\').val(' . $aArgs['location'] . '); window.parent.tb_remove();\"" : \' style="cursor:default;"\'); ?>><?php echo _p(\'block\') ; ?> ' . $this->_removeQuote($aArgs['location']) . '<?php if (defined(\'PHPFOX_IS_AD_SAMPLE\')): echo Phpfox::getService(\'ad\')->getSizeForBlock("' . $this->_removeQuote($aArgs['location']) . '"); endif; ?>';
                $sContent .= '<?php if (defined(\'PHPFOX_IS_AD_SAMPLE\')): Phpfox::getBlock(\'ad.sample\', array(\'block_id\' => ' . $this->_removeQuote($aArgs['location']) . ')); endif; ?>';
                $sContent .= '</div>';
                $sContent .= '<?php endif; ?>';
                $sContent .= '<?php else: ?>';


                $sContent .= '<?php $aBlocks = Phpfox::getLib(\'phpfox.module\')->getModuleBlocks(' . $aArgs['location'] . '); ?>';

                /* if user is designing the profile or the dashboard showing the block containers is needed */
                $sContent .= '<?php $aUrl = Phpfox::getLib(\'url\')->getParams(); ?>';

                $sContent .= '<?php if (!Phpfox::isAdminPanel() && ( (defined(\'PHPFOX_DESIGN_DND\') && PHPFOX_DESIGN_DND) || (defined("PHPFOX_IN_DESIGN_MODE") && PHPFOX_IN_DESIGN_MODE && in_array(' . $aArgs['location'] . ', array(1, 2, 3))))):?> <div class="js_can_move_blocks js_sortable_empty" id="js_can_move_blocks_' . str_replace("'", '', $aArgs['location']) . '"> <div class="block js_sortable dnd_block_info">Position ' . $aArgs['location'] . '</div></div><?php endif; ?>' . "\n";
                $sContent .= '<?php foreach ((array)$aBlocks as $sBlock): ?>' . "\n";

                $sContent .= '<?php if (!Phpfox::isAdminPanel() && ( (defined(\'PHPFOX_DESIGN_DND\') && PHPFOX_DESIGN_DND) || (defined("PHPFOX_IN_DESIGN_MODE") && PHPFOX_IN_DESIGN_MODE && in_array(' . $aArgs['location'] . ', array(1, 2, 3))))):?>' . "\n";
                $sContent .= '<div class="js_can_move_blocks" id="js_can_move_blocks_' . str_replace("'", '', $aArgs['location']) . '">' . "\n";
                $sContent .= '<?php endif; ?>' . "\n";

                $sContent .= '<?php if (is_array($sBlock) && (!defined(\'PHPFOX_CAN_MOVE_BLOCKS\') || !in_array(' . $aArgs['location'] . ', array(1, 2, 3, 4)))): ?>' . "\n";
                $sContent .= '<?php Phpfox::getBlock($sBlock); ?>' . "\n";
                $sContent .= '<?php else: ?>' . "\n";
                $sContent .= '<?php Phpfox::getBlock($sBlock, array(\'location\' => ' . $aArgs['location'] . ')); ?>' . "\n";
                $sContent .= '<?php endif; ?>' . "\n";

                $sContent .= '<?php if (!Phpfox::isAdminPanel() && ( (defined(\'PHPFOX_DESIGN_DND\') && PHPFOX_DESIGN_DND) || (defined("PHPFOX_IN_DESIGN_MODE") && PHPFOX_IN_DESIGN_MODE && in_array(' . $aArgs['location'] . ', array(1, 2, 3))))):?>';
                $sContent .= '</div>';
                $sContent .= '<?php endif; ?>' . "\n";

                $sContent .= '<?php endforeach; ?>';

                $sContent .= '<?php endif; ?>';

                $sContent .= '<?php if (!Phpfox::isAdminPanel()): ?>';
                $sContent .= '</div>';
                $sContent .= '<?php endif; ?>';

                return $sContent;
            case 'branding':
                $sContent = '
				<?php if (!Phpfox::getParam(\'core.branding\')): ?>
					<?php echo PhpFox::link(true, false); ?>
				<?php endif; ?>
				';
                return $sContent;
            case 'product_branding':
                $sContent = '<?php echo \' &middot; \' . PhpFox::link(); ?>';
                return $sContent;
            case 'image_path':
                return '<?php echo $this->getStyle(\'image\'); ?>';
            case 'module_path':
                return '<?php echo Phpfox::getParam(\'core.url_module\'); ?>';
            case 'permalink':
                $aArgs = $this->_parseArgs($sArguments);
                $aExtra = $aArgs;
                unset($aExtra['module'], $aExtra['id'], $aExtra['title']);

                return '<?php echo Phpfox::permalink(' . $aArgs['module'] . ', ' . $this->_removeQuote($aArgs['id']) . '' . (empty($aArgs['title']) ? ', null' : ', ' . $this->_removeQuote($aArgs['title'])) . ', false, null, (array) ' . var_export($aExtra, true) . '); ?>';
            case 'add_script':
                $aArgs = $this->_parseArgs($sArguments);
                return '<?php echo Phpfox::getLib(\'template\')->addScript(' . $aArgs['key'] . ', ' . $aArgs['value'] . ',true); ?>';
            case 'url':
                $aArgs = $this->_parseArgs($sArguments);
                if (!isset($aArgs['link'])) {
                    return '';
                }
                $sLink = $aArgs['link'];
                unset($aArgs['link']);
                if (isset($aArgs['keep_segment_value'])) {
                    $bKeepSegmentValue = !!$aArgs['keep_segment_value'];
                    unset($aArgs['keep_segment_value']);
                } else {
                    $bKeepSegmentValue = false;
                }
                $sArray = ', []';
                if (count($aArgs)) {
                    $sArray = ', array(';
                    foreach ($aArgs as $sKey => $sValue) {
                        $sArray .= '\'' . $sKey . '\' => ' . $sValue . ',';
                    }
                    $sArray = rtrim($sArray, ',') . ')';
                }
                return '<?php echo Phpfox::getLib(\'phpfox.url\')->makeUrl(' . $sLink . $sArray . ', false, ' . ($bKeepSegmentValue ? 'true' : 'false') . '); ?>';
            case 'phrase':
                $aArgs = $this->_parseArgs($sArguments);
                if (!isset($aArgs['var'])) {
                    return '';
                }
                $sVar = $aArgs['var'];
                unset($aArgs['var']);
                $sArray = '';
                $sLanguage = '';
                if (count($aArgs)) {
                    $sArray = ', array(';
                    foreach ($aArgs as $sKey => $sValue) {
                        if ($sKey == 'language') {
                            $sLanguage = $sValue;
                        }
                        $sArray .= '\'' . $sKey . '\' => ' . $sValue . ',';
                    }
                    $sArray = rtrim($sArray, ',') . ')';
                }
                return '<?php echo _p(' . $sVar . '' . $sArray . ($sLanguage != '' ? ',' . $sLanguage : '') . '); ?>';
            case 'softPhrase':
                $aArgs = $this->_parseArgs($sArguments);
                if (!isset($aArgs['var'])) {
                    return '';
                }
                $sVar = $aArgs['var'];
                unset($aArgs['var']);
                $sArray = '';
                $sLanguage = '';
                if (count($aArgs)) {
                    $sArray = ', array(';
                    foreach ($aArgs as $sKey => $sValue) {
                        if ($sKey == 'language') {
                            $sLanguage = $sValue;
                        }
                        $sArray .= '\'' . $sKey . '\' => ' . $sValue . ',';
                    }
                    $sArray = rtrim($sArray, ',') . ')';
                }
                return '<?php echo _p(' . $sVar . '' . $sArray . ($sLanguage != '' ? ',' . $sLanguage : '') . '); ?>';
            case '_p':
                $aArgs = $this->_parseArgs($sArguments);
                if (!isset($aArgs['var'])) {
                    return '';
                }
                $sVar = $aArgs['var'];
                unset($aArgs['var']);
                $sArray = '';
                $sLanguage = '';
                if (count($aArgs)) {
                    $sArray = ', array(';
                    foreach ($aArgs as $sKey => $sValue) {
                        if ($sKey == 'language') {
                            $sLanguage = $sValue;
                        }
                        $sArray .= '\'' . $sKey . '\' => ' . $sValue . ',';
                    }
                    $sArray = rtrim($sArray, ',') . ')';
                }
                $sVar = str_replace(['!<<', '>>!'], ['{{', '}}'], $sVar);
                if (!empty($aArgs['clean'])) {
                    return '<?php echo Phpfox::getLib(\'phpfox.parse.output\')->clean(_p(' . $sVar . $sArray . ($sLanguage != '' ? ',' . $sLanguage : '') . ')); ?>';
                }
                return '<?php echo _p(' . $sVar . $sArray . ($sLanguage != '' ? ',' . $sLanguage : '') . '); ?>';
            case 'translate':
                $aArgs = $this->_parseArgs($sArguments);
                $sPrefix = (isset($aArgs['prefix']) ? ', ' . $aArgs['prefix'] : '');
                return '<?php echo Phpfox::getLib(\'phpfox.locale\')->translate(' . $aArgs['var'] . $sPrefix . '); ?>';
            case 'error':
                $sContent = '<?php if (!$this->bIsSample): ?>';
                $sContent .= '<?php $this->getLayout(\'error\'); ?>';
                $sContent .= '<?php endif; ?>';
                return $sContent;
            case 'breadcrumb':
                $sContent = '<div class="_block_breadcrumb"><?php if (!$this->bIsSample): ?>';
                $sContent .= '<?php $this->getLayout(\'breadcrumb\'); ?>';
                $sContent .= '<?php endif; ?></div>';
                return $sContent;
            case 'search':
                $sContent = '<div class="_block_search"><?php if (!$this->bIsSample): ?>';
                $sContent .= '<?php $this->getLayout(\'search\'); ?>';
                $sContent .= '<?php endif; ?></div>';
                return $sContent;
            case 'content':
                $sContent = '<?php if (!$this->bIsSample): ?><div id="site_content">';
                $sContent .= '<?php if (isset($this->_aVars[\'bSearchFailed\'])): ?>';
                $sContent .= '<div class="message">Unable to find anything with your search criteria.</div>';
                $sContent .= '<?php else: ?>';
                // Don't do this for profiles/pages or core.index-member because those load the feed and there is a separate routine for this block
                $sContent .= '<?php $sController = "' . Phpfox_Module::instance()->getFullControllerName() . '"; ?>';
                $sContent .= '<?php Phpfox::getLib(\'phpfox.module\')->getControllerTemplate(); ?>';
                $sContent .= '<?php endif; ?></div>';
                $sContent .= '<?php endif; ?>';
                return $sContent;
            case 'layout':
                $aArgs = $this->_parseArgs($sArguments);
                return '<?php $this->getLayout(' . $aArgs['file'] . '); ?>';
            case 'pager':
                $sReturn = '<?php if (!isset($this->_aVars[\'aPager\'])): Phpfox::getLib(\'pager\')->set(array(\'page\' => Phpfox::getLib(\'request\')->getInt(\'page\'), \'size\' => Phpfox::getLib(\'search\')->getDisplay(), \'count\' => Phpfox::getLib(\'search\')->getCount())); endif; ?>';
                $sReturn .= '<?php $this->getLayout(\'pager\'); ?>';
                return $sReturn;
            case 'unset':
                $aArgs = $this->_parseArgs($sArguments);
                return '<?php unset(' . implode(', ', $aArgs) . '); ?>';
            case 'token':
                return '';
            case 'img':
                $aArgs = $this->_parseArgs($sArguments);
                $sArray = '';
                foreach ($aArgs as $sKey => $sValue) {
                    $sArray .= '\'' . $sKey . '\' => ' . $sValue . ',';
                }
                return '<?php echo Phpfox::getLib(\'phpfox.image.helper\')->display(array(' . rtrim($sArray, ',') . ')); ?>';
            case 'plugin':
                $aArgs = $this->_parseArgs($sArguments);
                if (!isset($aArgs['call'])) {
                    return '';
                }
                return '<?php (($sPlugin = Phpfox_Plugin::get(\'' . $this->_removeQuote($aArgs['call']) . '\')) ? eval($sPlugin) : false); ?>';
            case 'template':
                $aArgs = $this->_parseArgs($sArguments);
                $sFile = $this->_removeQuote($aArgs['file']);
                if (strpos($sFile, '$this->_aVars') === false) {
                    $sFile = '\'' . $sFile . '\'';
                }
                $feed_id = '';
                if (isset($aArgs['feed_id'])) {
                    $feed_id = ', ' . $this->_removeQuote($aArgs['feed_id']);
                }
                return '<?php
						Phpfox::getLib(\'template\')->getBuiltFile(' . $sFile . $feed_id . ');
						?>';
            case 'parse_image':
                $aArgs = $this->_parseArgs($sArguments);
                $sArray = '';
                foreach ($aArgs as $sKey => $sValue) {
                    $sArray .= '\'' . $sKey . '\' => ' . $sValue . ',';
                }
                return '<?php Phpfox::getLib(\'parse.output\')->setImageParser(array(' . rtrim($sArray, ',') . ')); ?>';
            case 'module':
                $aArgs = $this->_parseArgs($sArguments);
                $sModule = $aArgs['name'];
                unset($aArgs['name']);
                $sArray = '';
                foreach ($aArgs as $sKey => $sValue) {
                    if (substr($sValue, 0, 1) != '$' && $sValue !== 'true' && $sValue !== 'false') {
                        $sValue = '\'' . $this->_removeQuote($sValue) . '\'';
                    }
                    $sArray .= '\'' . $sKey . '\' => ' . $sValue . ',';
                }

                return '<?php Phpfox::getBlock(' . $sModule . ', array(' . rtrim($sArray, ',') . ')); ?>';
            case 'editor':
                $aArgs = $this->_parseArgs($sArguments);
                $aParams = array();
                foreach ($aArgs as $sKey => $mParam) {
                    $aParams[$sKey] = $this->_removeQuote($mParam);
                }

                $sReturn = '<div class="editor_holder">';
                $sReturn .= '<?php echo Phpfox::getLib(\'phpfox.editor\')->get(' . $aArgs['id'] . ', ' . var_export($aParams, true) . '); ?>';

                if (\Phpfox::isModule('attachment') && (!isset($aArgs['can_attach_file']) || $aArgs['can_attach_file'] != 'false')) {
                    $sReturn .= '<?php Phpfox::getBlock(\'attachment.share\', array(\'id\'=> ' . $aArgs['id'] . ')); ?>';
                } elseif (\Phpfox::isApps('PHPfox_Twemoji_Awesome')) {
                    $sReturn .= '<?php Phpfox::getBlock(\'PHPfox_Twemoji_Awesome.share\', array(\'id\'=> ' . $aArgs['id'] . ')); ?>';
                }
                $sReturn .= '</div>';

                return $sReturn;
            case 'value':
                $aArgs = $this->_parseArgs($sArguments);
                $aArgs = array_map(array($this, '_removeQuote'), $aArgs);
                // Accept variables in ids
                if (substr($aArgs['id'], 0, 14) == '$this->_aVars[') {
                    $aArgs['id'] = '\'.' . $aArgs['id'] . '.\'';
                }

                switch ($aArgs['type']) {
                    case 'input':
                        $sContent = '<?php $aParams = (isset($aParams) ? $aParams : Phpfox::getLib(\'phpfox.request\')->getArray(\'val\')); echo (isset($aParams[\'' . $aArgs['id'] . '\']) ? Phpfox::getLib(\'phpfox.parse.output\')->clean($aParams[\'' . $aArgs['id'] . '\']) : (isset($this->_aVars[\'aForms\'][\'' . $aArgs['id'] . '\']) ? Phpfox::getLib(\'phpfox.parse.output\')->clean($this->_aVars[\'aForms\'][\'' . $aArgs['id'] . '\']) : ' . (isset($aArgs['default']) ? '\'' . $aArgs['default'] . '\'' : '\'\'') . ')); ?>' . "\n";
                        break;
                    case 'radio':
                        $sContent = '<?php $aParams = (isset($aParams) ? $aParams : Phpfox::getLib(\'phpfox.request\')->getArray(\'val\'));';
                        $sContent .= "\n" . 'if (isset($this->_aVars[\'aForms\']) && is_numeric(\'' . $aArgs["id"] . '\') && in_array(\'' . $aArgs["id"] . '\', $this->_aVars[\'aForms\']) ){echo \' checked="checked"\';}';
                        $sContent .= "\n" . 'if ((isset($aParams[\'' . $aArgs['id'] . '\']) && $aParams[\'' . $aArgs['id'] . '\'] == \'' . $aArgs['default'] . '\'))';
                        $sContent .= "\n" . '{echo \' checked="checked" \';}';
                        $sContent .= "\n" . 'else';
                        $sContent .= "\n" . '{';
                        $sContent .= "\n" . ' if (isset($this->_aVars[\'aForms\']) && isset($this->_aVars[\'aForms\'][\'' . $aArgs['id'] . '\']) && !isset($aParams[\'' . $aArgs['id'] . '\']) && $this->_aVars[\'aForms\'][\'' . $aArgs['id'] . '\'] == \'' . $aArgs['default'] . '\')';
                        $sContent .= "\n" . ' {';
                        $sContent .= "\n" . '    echo \' checked="checked" \';}';
                        $sContent .= "\n" . ' else';
                        $sContent .= "\n" . ' {';
                        if (isset($aArgs['selected'])) {
                            $sContent .= "\n" . ' if (!isset($this->_aVars[\'aForms\']) || ((isset($this->_aVars[\'aForms\']) && !isset($this->_aVars[\'aForms\'][\'' . $aArgs['id'] . '\']) && !isset($aParams[\'' . $aArgs['id'] . '\']))))';
                            $sContent .= "\n" . '{';
                            $sContent .= "\n" . ' echo \' checked="checked"\';';
                            $sContent .= "\n" . '}';
                        }

                        $sContent .= "\n" . ' }';
                        $sContent .= "\n" . '}';
                        $sContent .= "\n" . '?>' . " \n";
                        break;
                    case 'checkbox':
                    case 'multiselect':
                    case 'select':
                        $bIsCheckbox = ($aArgs['type'] == 'checkbox' ? 'checked="checked"' : 'selected="selected"');
                        $aArgs['default'] = $this->_removeQuote($aArgs['default']);
                        if (substr($aArgs['default'], 0, 1) == '$') {
                            $sDefault = $aArgs['default'];
                        } elseif (substr($aArgs['default'], 0, 2) == ".\$") {
                            $sDefault = trim($aArgs['default'], '.');
                        } else {
                            $sDefault = "'{$aArgs['default']}'";
                        }

                        $sContent = '<?php $aParams = (isset($aParams) ? $aParams : Phpfox::getLib(\'phpfox.request\')->getArray(\'val\'));' .
                            "\n" . '';
                        $sContent .= "\n\n" . 'if (isset($this->_aVars[\'aField\']) && isset($this->_aVars[\'aForms\'][$this->_aVars[\'aField\'][\'field_id\']]) && !is_array($this->_aVars[\'aForms\'][$this->_aVars[\'aField\'][\'field_id\']]))
							{
								$this->_aVars[\'aForms\'][$this->_aVars[\'aField\'][\'field_id\']] = array($this->_aVars[\'aForms\'][$this->_aVars[\'aField\'][\'field_id\']]);
							}';
                        $sContent .= "\n\n" . 'if (isset($this->_aVars[\'aForms\']' . (isset($aArgs['parent']) ? '[\'' . $aArgs["parent"] . '\']' : '') . ')';
                        $sContent .= "\n" . ' && is_numeric(\'' . $aArgs["id"] . '\') && in_array(\'' . $aArgs["id"] . '\', $this->_aVars[\'aForms\']' . (isset($aArgs['parent']) ? '[\'' . $aArgs["parent"] . '\']' : '') . '))
							' . "\n" . '{
								echo \' ' . $bIsCheckbox . ' \';
							}' . "\n" . '
							if (isset($aParams[\'' . $aArgs['id'] . '\'])
								&& $aParams[\'' . $aArgs['id'] . '\'] == ' . $sDefault . ')' . "\n" . '
							{' . "\n" . '
								echo \' ' . $bIsCheckbox . ' \';' . "\n" . '
							}' . "\n" . '
							else' . "\n" . '
							{' . "\n" . '
								if (isset($this->_aVars[\'aForms\'][\'' . $aArgs['id'] . '\'])
									&& !isset($aParams[\'' . $aArgs['id'] . '\'])
									&& (($this->_aVars[\'aForms\'][\'' . $aArgs['id'] . '\'] == ' . $sDefault . ') || (is_array($this->_aVars[\'aForms\'][\'' . $aArgs['id'] . '\']) && in_array(' . $sDefault . ', $this->_aVars[\'aForms\'][\'' . $aArgs['id'] . '\']))))
								{
								 echo \' ' . $bIsCheckbox . ' \';
								}
								else
								{
									echo ' . (isset($aArgs['selected']) ? '" ' . str_replace('"', '\"', $bIsCheckbox) . '"' : '""') . ';
								}
							}
							?>' . "\n";
                        break;
                    case 'textarea':
                        $sContent = '<?php $aParams = (isset($aParams) ? $aParams : Phpfox::getLib(\'phpfox.request\')->getArray(\'val\')); echo (isset($aParams[\'' . $aArgs['id'] . '\']) ? Phpfox::getLib(\'phpfox.parse.output\')->clean($aParams[\'' . $aArgs['id'] . '\']) : (isset($this->_aVars[\'aForms\'][\'' . $aArgs['id'] . '\']) ? Phpfox::getLib(\'phpfox.parse.output\')->clean($this->_aVars[\'aForms\'][\'' . $aArgs['id'] . '\']) : \'\')); ?>' . "\n";
                        break;
                }
                return isset($sContent) ? $sContent : '';
            case 'help':
                $aArgs = $this->_parseArgs($sArguments);
                return '<?php Phpfox::getBlock(\'help.popup\', array(\'phrase\' => ' . $aArgs['var'] . ')); ?>';
            case 'param':
                $aArgs = $this->_parseArgs($sArguments);
                return '<?php echo Phpfox::getParam(\'' . $this->_removeQuote($aArgs['var']) . '\'); ?>';
            case 'request':
                $aArgs = $this->_parseArgs($sArguments);
                return '<?php echo urlencode(Phpfox::getLib(\'request\')->get(\'' . $this->_removeQuote($aArgs['var']) . '\')); ?>';
            case 'required':
                return '*';
            case 'select_date':
                /*
                 * @param string $aArgs['time_separator'] the variable for the language phrase
                 */
                $aArgs = $this->_parseArgs($sArguments);
                $aParams = [
                    'is_multiple'        => false,
                    'prefix'             => isset($aArgs['prefix']) ? $this->_removeQuote($aArgs['prefix']) : '',
                    'set_empty_value'    => isset($aArgs['set_empty_value']) ? $this->_removeQuote($aArgs['set_empty_value']) : '',
                    'name'               => isset($aArgs['name']) ? $this->_removeQuote($aArgs['name']) : 'val',
                    'start_hour'         => isset($aArgs['start_hour']) ? $this->_removeQuote($aArgs['start_hour']) : null,
                    'start_year'         => isset($aArgs['start_year']) ? $this->_removeQuote($aArgs['start_year']) : null,
                    'end_year'           => isset($aArgs['end_year']) ? $this->_removeQuote($aArgs['end_year']) : null,
                    'ignore_time_format' => isset($aArgs['ignore_time_format']) ? $this->_removeQuote($aArgs['ignore_time_format']) : 0
                ];

                if ( isset($aArgs['field_separator'])) {
                    $aParams['field_separator'] =  $this->_removeQuote($aArgs['field_separator']);
                }

                if (isset($aArgs['sort_years'])) {
                    $aParams['sort_years'] = $aArgs['sort_years'];
                }

                if (isset($aArgs['default_all'])) {
                    $aParams['default_all'] = $aArgs['default_all'];
                }

                if (isset($aArgs['id'])) {
                    $aParams['id'] = $aArgs['id'];
                }

                if (isset($aArgs['bUseDatepicker'])) {
                    $aParams['bUseDatepicker'] = $aArgs['bUseDatepicker'];
                }

                if (isset($aArgs['add_time'])) {
                    $aParams['add_time'] = $aArgs['add_time'];
                    $aParams['time_separator'] = isset($aArgs['time_separator']) ? $aArgs['time_separator'] : '';

                }

                $aForms = Phpfox::getLib('template')->getVar('aForms');

                $aParams['selected_values'] = [
                    $aParams['prefix'] . 'year'   => isset($aForms[$aParams['prefix'] . 'year']) ? $aForms[$aParams['prefix'] . 'year'] : null,
                    $aParams['prefix'] . 'month'  => isset($aForms[$aParams['prefix'] . 'month']) ? $aForms[$aParams['prefix'] . 'month'] : null,
                    $aParams['prefix'] . 'day'    => isset($aForms[$aParams['prefix'] . 'day']) ? $aForms[$aParams['prefix'] . 'day'] : null,
                    $aParams['prefix'] . 'hour'   => isset($aForms[$aParams['prefix'] . 'hour']) ? $aForms[$aParams['prefix'] . 'hour'] : null,
                    $aParams['prefix'] . 'minute' => isset($aForms[$aParams['prefix'] . 'minute']) ? $aForms[$aParams['prefix'] . 'minute'] : null,
                ];

                return Phpfox::generateSelectDate($aParams);
            case 'select_location':
                $aArgs = $this->_parseArgs($sArguments);
                $aParams = array();
                foreach ($aArgs as $sKey => $mParam) {
                    $aParams[$sKey] = $this->_removeQuote($mParam);
                }
                $sReturn = '<?php Phpfox::getBlock(\'core.country-build\', array(\'param\'=> ' . var_export($aParams, true) . ')); ?>';

                return $sReturn;
            case 'select_gender':
                $aArgs = $this->_parseArgs($sArguments);
                if (isset($aArgs['value_title']) && strpos($aArgs['value_title'], 'phrase var=') !== false) {
                    $aArgs['value_title'] = _p(str_replace(array('phrase var=', '"', "'"), '', $aArgs['value_title']));
                }
                $sGenders = '<select class="form-control" name="val[gender]" id="gender">' . "\n";
                $sGenders .= "\t\t" . '<option value="">' . (isset($aArgs['value_title']) ? $this->_removeQuote($aArgs['value_title']) : '<?php echo _p(\'select\'); ?>:') . '</option>' . "\n";
                foreach (Phpfox::getService('core')->getGenders(true) as $iKey => $sGender) {
                    $sGenders .= "\t\t\t" . '<option value="' . $iKey . '"' . $this->_parseFunction('value', '', "type='select' id='gender' default='{$iKey}'") . '><?php echo _p(\'' . str_replace("'", "\'", $sGender) . '\'); ?></option>' . "\n";
                }
                $sGenders .= "\t\t" . '</select>';
                return $sGenders;
            case 'checkbox_gender':
                $aArgs = $this->_parseArgs($sArguments);
                $bUseCustom = isset($aArgs['use_custom']) && $aArgs['use_custom'] == 'true';
                $sName = empty($aArgs['name']) ? 'val[gender][]' : $aArgs['name'];
                $sGenders = '';

                foreach (Phpfox::getService('core')->getGenders(true) as $iKey => $sGender) {
                    $sGenders .= strtr("<div class='{wrapper_class}'><label>{input}{custom}{label}</label></div>", [
                        '{wrapper_class}' => $bUseCustom ? 'custom-checkbox-wrapper' : 'checkbox',
                        '{input}' => '<input type="checkbox" name="' . $sName . '" value="' . $iKey . '"' . $this->_parseFunction('value', '', "type='checkbox' id='gender' default='{$iKey}'") . '>',
                        '{label}' => '<?php echo _p(\'' . $sGender . '\'); ?>',
                        '{custom}' => (Phpfox::isAdminPanel() || $bUseCustom) ? '<span class="custom-checkbox"></span>' : ''
                    ]);
                }
                return $sGenders;
            case 'inline_search':
                $aArgs = $this->_parseArgs($sArguments);
                $aParams = array();
                foreach ($aArgs as $sKey => $mParam) {
                    $aParams[$sKey] = $this->_removeQuote($mParam);
                }
                return '<?php echo Phpfox::getLib(\'phpfox.search.inline\')->get(' . var_export($aParams, true) . '); ?>';
            case 'application':
                $aArgs = $this->_parseArgs($sArguments);
                return '<?php echo Phpfox::getService(\'application\')->getForProfile(' . $aArgs['user_id'] . ', ' . $aArgs['location'] . '); ?>';
            case 'filter':
                $aArgs = $this->_parseArgs($sArguments);
                return '<?php echo $this->_aVars[\'aFilters\'][' . $aArgs['key'] . ']; ?>';
            case 'jscript':
                $aArgs = $this->_parseArgs($sArguments);
                return '<?php echo str_replace(PHPFOX_DS, "/", $this->getStyle(\'static_script\', ' . $aArgs['file'] . '' . (isset($aArgs['module']) ? ', ' . $aArgs['module'] . ', false, false' : 'null, false, false') . ')); ?>';
            case 'css':
                $aArgs = $this->_parseArgs($sArguments);
                return '<?php echo $this->getStyle(\'css\', ' . $aArgs['file'] . '' . (isset($aArgs['module']) ? ', ' . $aArgs['module'] . '' : '') . '); ?>';
            case 'moderation':
                return '<?php Phpfox::getBlock(\'core.moderation\'); ?>';
            case 'section_menu_js':
                return '<?php echo Phpfox::getLib(\'template\')->getSectionMenuJavaScript(); ?>';
            case 'body':
                return '<?php Phpfox::getBlock(\'core.template-body\'); ?>';
            case 'notification':
                return '<?php Phpfox::getBlock(\'core.template-notification\'); ?>';
            case 'menu':
                return '<?php Phpfox::getBlock(\'core.template-menu\'); ?>';
            case 'menu_sub':
                return '<div class="_block_menu_sub"><?php Phpfox::getBlock(\'core.template-menusub\'); ?></div>';
            case 'menu_footer':
                return '<?php Phpfox::getBlock(\'core.template-menufooter\'); ?>';
            case 'copyright':
                return '<?php Phpfox::getBlock(\'core.template-copyright\'); ?>';
            case 'footer':
                return '<?php Phpfox::getBlock(\'core.template-footer\'); ?>';
            case 'holder_name':
                return '<?php Phpfox::getBlock(\'core.template-holdername\'); ?>';
            case 'logo':
                return '<?php Phpfox::getBlock(\'core.template-logo\'); ?>';
            case 'breadcrumb_list':
                return '<?php Phpfox::getBlock(\'core.template-breadcrumblist\'); ?>';
            case 'breadcrumb_menu':
                return '<?php Phpfox::getBlock(\'core.template-breadcrumbmenu\'); ?>';
            case 'content_class':
                return '<?php Phpfox::getBlock(\'core.template-contentclass\'); ?>';
            case 'is_page_view':
                return '<?php echo (defined(\'PHPFOX_IS_PAGES_VIEW\') ? \'id="js_is_page"\' : \'\'); ?>';
            case 'item':
                $aArgs = $this->_parseArgs($sArguments);
                return '<article itemscope itemtype="http://schema.org/' . $this->_removeQuote($aArgs['name']) . '">';
            case '/item':
                return '</article>';
            case 'field_price':
                $aArgs = $this->_parseArgs($sArguments);
                $sCurrencyName = isset($aArgs['currency_name']) ? $aArgs['currency_name'] : 'currency_id';
                $sPriceName = isset($aArgs['price_name']) ? $aArgs['price_name'] : 'price';
                $sReturn = "<?php echo Phpfox::getService('core.currency')->getFieldPrice($sCurrencyName, $sPriceName," . (isset($aArgs['close_warning']) ? 'true' : 'false') . "); ?>";
                return $sReturn;
            case 'table_sort':
                $aArgs = $this->_parseArgs($sArguments);
                $sClass = isset($aArgs['class']) ? $aArgs['class'] : '\'\'';
                $sAsc = isset($aArgs['asc']) ? $aArgs['asc'] : '\'\'';
                $sDesc = isset($aArgs['desc']) ? $aArgs['desc'] : '\'\'';
                $sSort = isset($aArgs['current']) ? $aArgs['current'] : '\'\'';
                $sQuery = isset($aArgs['query']) ? $aArgs['query'] : '\'sort\'';
                $sFirst = isset($aArgs['first']) ? $aArgs['first'] : '\'asc\'';
                return "<?php echo Phpfox::getService('core.helper')->tableSort($sClass,$sAsc,$sDesc, $sSort, $sQuery, $sFirst); ?>";
            case 'field_language':
                $aArgs = $this->_parseArgs($sArguments);
                $field = isset($aArgs['field']) ? $aArgs['field'] : '\'name\'';
                $phrase = isset($aArgs['phrase']) ? $aArgs['phrase'] : '\'sPhraseVarName\'';
                $type = isset($aArgs['type']) ? $aArgs['type'] : '\'text\'';
                $size = isset($aArgs['size']) ? $aArgs['size'] : '\'30\'';
                $rows = isset($aArgs['rows']) ? $aArgs['rows'] : '\'5\'';
                $maxlength = isset($aArgs['maxlength']) ? $aArgs['maxlength'] : '\'200\'';
                $label = isset($aArgs['label']) ? $aArgs['label'] : '\'name\'';
                $format = isset($aArgs['format']) ? $aArgs['format'] : '\'name_\'';
                $required = isset($aArgs['required']) ? $aArgs['required'] : 'false';
                $closeWarning = isset($aArgs['close_warning']) ? !!$aArgs['close_warning'] : 'false';
                $sHelpPhrase = isset($aArgs['help_phrase']) ? $aArgs['help_phrase'] : '\'if_this_value_is_empty_it_will_have_value_the_same_with_the_default_language\'';
                $bAllowMultiple = isset($aArgs['allow_multiple']) ? $aArgs['allow_multiple'] : 'false';
                $sReturn = "<?php Phpfox::getBlock('language.admincp.multiple',['phrase'=>$phrase,'field'=>$field,'label'=>$label,'format'=>$format,'type'=>$type, 'size'=>$size, 'rows'=>$rows, 'help_phrase'=>$sHelpPhrase, 'required'=>$required,'maxlength'=>$maxlength, 'close_warning'=>$closeWarning, 'allow_multiple'=>$bAllowMultiple]); ?>";
                return $sReturn;
            case 'addthis':
                $aArgs = $this->_parseArgs($sArguments);
                $sArray = '';
                foreach ($aArgs as $key => $value) {
                    $sArray .= '\'' . $key . '\' => ' . $value . ',';
                }
                return "<?php Phpfox::getBlock('share.addthis', array(" . rtrim($sArray, ',') . ")); ?>";
            case 'location_input':
                $aArgs = $this->_parseArgs($sArguments);
                if (isset($aArgs['value_title']) && strpos($aArgs['value_title'], 'phrase var=') !== false) {
                    $aArgs['value_title'] = _p(str_replace(array('phrase var=', '"', "'"), '', $aArgs['value_title']));
                }
                $bMultiple = !empty($aArgs['multiple_field']);
                $iIndex = isset($aArgs['field_index']) ? $aArgs['field_index'] : 0;
                $sLocation = '<div class="js-location_input_section">';
                $sLocation .= '<input type="text" name="val[location]'.($bMultiple ? '[]' : '').'" class="form-control js-location_input' . (isset($aArgs['close_warning']) ? ' close_warning' : '') . '" placeholder="'.(isset($aArgs['value_title']) ? $this->_removeQuote($aArgs['value_title']) : '<?php echo _p(\'type_a_location\'); ?>').'" value="'.($bMultiple ? '<?php (isset($this->_aVars[\'aForms\'][\'location\']['.$iIndex.']) ? Phpfox::getLib(\'phpfox.parse.output\')->clean($this->_aVars[\'aForms\'][\'location\']['.$iIndex.']) : \'\'); ?>' : $this->_parseFunction('value', '', "type='input' id='location' default=''")) .'" maxlength="'.(!empty($aArgs['maxlength']) ? $aArgs['maxlength'] : '255').'">' . "\n";
                $sLocation .= "\t\t" . '<input type="hidden" name="val[location_lat]'.($bMultiple ? '[]' : '').'" class="js-location_lat" value="'. ($bMultiple ? '<?php (isset($this->_aVars[\'aForms\'][\'location_lat\']['.$iIndex.']) ? Phpfox::getLib(\'phpfox.parse.output\')->clean($this->_aVars[\'aForms\'][\'location_lat\']['.$iIndex.']) : \'\'); ?>' : $this->_parseFunction('value', '', "type='input' id='location_lat' default=''")).'">';
                $sLocation .= "\t\t" . '<input type="hidden" name="val[location_lng]'.($bMultiple ? '[]' : '').'" class="js-location_lng" value="'. ($bMultiple ? '<?php (isset($this->_aVars[\'aForms\'][\'location_lng\']['.$iIndex.']) ? Phpfox::getLib(\'phpfox.parse.output\')->clean($this->_aVars[\'aForms\'][\'location_lng\']['.$iIndex.']) : \'\'); ?>' : $this->_parseFunction('value', '', "type='input' id='location_lng' default=''")).'">';
                if (empty($aArgs['disable_country'])) {
                    $sLocation .= "\t\t" . '<input type="hidden" name="val[country_iso]'.($bMultiple ? '[]' : '').'" class="js-location_country_iso" value="'. ($bMultiple ? '<?php (isset($this->_aVars[\'aForms\'][\'country_iso\']['.$iIndex.']) ? Phpfox::getLib(\'phpfox.parse.output\')->clean($this->_aVars[\'aForms\'][\'country_iso\']['.$iIndex.']) : \'\'); ?>' : $this->_parseFunction('value', '', "type='input' id='country_iso' default=''")).'">';
                    $sLocation .= "\t\t" . '<input type="hidden" name="val[country_child_id]'.($bMultiple ? '[]' : '').'" class="js-location_country_child_id" value="'. ($bMultiple ? '<?php (isset($this->_aVars[\'aForms\'][\'country_child_id\']['.$iIndex.']) ? Phpfox::getLib(\'phpfox.parse.output\')->clean($this->_aVars[\'aForms\'][\'country_child_id\']['.$iIndex.']) : \'\'); ?>' : $this->_parseFunction('value', '', "type='input' id='country_child_id' default=''")).'">';
                }
                $sLocation .= '<div class="js-location_map"></div>';
                $sLocation .= '</div>';
                return $sLocation;
            case 'iconfont_input':
                $aArgs = $this->_parseArgs($sArguments);
                $sArray = '';
                foreach ($aArgs as $key => $value) {
                    $sArray .= '\'' . $key . '\' => ' . $value . ',';
                }
                return "<?php Phpfox::getBlock('core.iconfont-input', array(" . rtrim($sArray, ',') . ")); ?>";
            default:
                if ($this->_compileCustomFunction($sFunction, $sModifiers, $sArguments, $sResult)) {
                    return $sResult;
                } elseif ($bKeepOriginalOnError) {
                    return false;
                } else {
                    Phpfox_Error::trigger('Invalid function: ' . $sFunction);
                    return true;
                }
        }
    }

    /**
     * Parse arguments. (eg. {for bar1=sample1 bar2=sample2}
     *
     * @param string $sArguments Arguments to parse.
     * @return array ARRAY of all the arguments.
     */
    private function _parseArgs($sArguments)
    {
        $aResult = array();
        preg_match_all('/(?:' . $this->_sQstrRegexp . ' | (?>[^"\'=\s]+))+|[=]/x', $sArguments, $aMatches);

        $iState = 0;
        foreach ($aMatches[0] as $mValue) {
            switch ($iState) {
                case 0:
                    if (is_string($mValue)) {
                        $sName = $mValue;
                        $iState = 1;
                    } else {
                        Phpfox_Error::trigger("Invalid Attribute Name", E_USER_ERROR);
                    }
                    break;
                case 1:
                    if ($mValue == '=') {
                        $iState = 2;
                    } else {
                        Phpfox_Error::trigger("Expecting '=' After '{$sLastValue}'", E_USER_ERROR);
                    }
                    break;
                case 2:
                    if ($mValue != '=') {
                        if (!preg_match_all('/(?:(' . $this->_sVarRegexp . '|' . $this->_sSvarRegexp . ')(' . $this->_sModRegexp . '*))(?:\s+(.*))?/xs', $mValue, $aVariables)) {
                            $aResult[$sName] = $mValue;
                        } else {
                            $aResult[$sName] = $this->_parseVariables($aVariables[1], $aVariables[2]);
                        }
                        $iState = 0;
                    } else {
                        Phpfox_Error::trigger("'=' cannot be an attribute value", E_USER_ERROR);
                    }
                    break;
            }
            $sLastValue = $mValue;
        }

        if ($iState != 0) {
            if ($iState == 1) {
                Phpfox_Error::trigger("expecting '=' after attribute name '{$sLastValue}'", E_USER_ERROR);
            } else {
                Phpfox_Error::trigger("missing attribute value", E_USER_ERROR);
            }
        }

        return $aResult;
    }

    /**
     * Parse variables.
     *
     * @param array $aVariables ARRAY of variables.
     * @param array $aModifiers ARRAY of modifiers.
     * @return string Converted variable.
     */
    private function _parseVariables($aVariables, $aModifiers)
    {
        $sResult = "";
        foreach ($aVariables as $mKey => $mValue) {
            if (empty($aModifiers[$mKey])) {
                $sResult .= $this->_parseVariable(trim($aVariables[$mKey])) . '.';
            } else {
                $sResult .= $this->_parseModifier($this->_parseVariable(trim($aVariables[$mKey])), $aModifiers[$mKey]) . '.';
            }
        }
        return substr($sResult, 0, -1);
    }

    /**
     * Parse a specific variable.
     *
     * @param string $sVariable Name of the variable we are parsing.
     * @return string Converted variable.
     */
    private function _parseVariable($sVariable)
    {
        if ($sVariable[0] == "\$") {
            return $this->_compileVariable($sVariable);
        } else {
            return $sVariable;
        }
    }

    /**
     * Compile all variables.
     *
     * @param string $sVariable Variable name.
     * @return string Converted variable.
     */
    private function _compileVariable($sVariable)
    {
        $sResult = '';
        $sVariable = substr($sVariable, 1);

        preg_match_all('!(?:^\w+)|(?:' . $this->_sVarBracketRegexp . ')|\.\$?\w+|\S+!', $sVariable, $aMatches);
        $aVariables = $aMatches[0];
        $sVarName = array_shift($aVariables);

        if ($sVarName == $this->sReservedVarname) {
            if ($aVariables[0][0] == '[' || $aVariables[0][0] == '.') {
                $aFind = array("[", "]", ".");
                switch (strtoupper(str_replace($aFind, "", $aVariables[0]))) {
                    case 'GET':
                        $sResult = "\$_GET";
                        break;
                    case 'POST':
                        $sResult = "\$_POST";
                        break;
                    case 'COOKIE':
                        $sResult = "\$_COOKIE";
                        break;
                    case 'ENV':
                        $sResult = "\$_ENV";
                        break;
                    case 'SERVER':
                        $sResult = "\$_SERVER";
                        break;
                    case 'SESSION':
                        $sResult = "\$_SESSION";
                        break;
                    default:
                        $sVar = str_replace($aFind, "", $aVariables[0]);
                        $sResult = "\$this->_aPhpfoxVars['$sVar']";
                        break;
                }
                array_shift($aVariables);
            } else {
                Phpfox_Error::trigger('$' . $sVarName . implode('', $aVariables) . ' is an invalid $phpfox reference', E_USER_ERROR);
            }
        } else {
            $sResult = "\$this->_aVars['$sVarName']";
        }

        foreach ($aVariables as $sVar) {
            if ($sVar[0] == '[') {
                $sVar = substr($sVar, 1, -1);
                if (is_numeric($sVar)) {
                    $sResult .= "[$sVar]";
                } elseif ($sVar[0] == '$') {
                    $sResult .= "[" . $this->_compileVariable($sVar) . "]";
                } else {
                    $parts = explode('.', $sVar);
                    $section = $parts[0];
                    $section_prop = isset($parts[1]) ? $parts[1] : 'index';
                    $sResult .= "[\$this->_aSections['$section']['$section_prop']]";
                }
            } elseif ($sVar[0] == '.') {
                $sResult .= "['" . substr($sVar, 1) . "']";
            } elseif (substr($sVar, 0, 2) == '->') {
                Phpfox_Error::trigger('Call to object members is not allowed', E_USER_ERROR);
            } else {
                Phpfox_Error::trigger('$' . $sVarName . implode('', $aVariables) . ' is an invalid reference', E_USER_ERROR);
            }
        }
        return $sResult;
    }

    /**
     * Parse modifiers.
     *
     * @param string $sVariable Variable name.
     * @param string $sModifiers Modifiers.
     * @return string Converted modifier.
     */
    private function _parseModifier($sVariable, $sModifiers)
    {
        $aMods = explode('|', $sModifiers);
        unset($aMods[0]);
        foreach ($aMods as $sMod) {
            $aArgs = array();
            if (strpos($sMod, ':')) {
                $aParts = explode(':', $sMod);
                $iCnt = 0;

                foreach ($aParts as $iKey => $sPart) {
                    if ($iKey == 0) {
                        continue;
                    }

                    if ($iKey > 1) {
                        $iCnt++;
                    }

                    $aArgs[$iCnt] = $this->_parseVariable($sPart);
                }

                $sMod = $aParts[0];
            }

            if ($sMod[0] == '@') {
                $sMod = substr($sMod, 1);
            }

            $sArg = ((count($aArgs) > 0) ? ', ' . implode(', ', $aArgs) : '');

            if ($this->_plugin($sMod, 'modifier')) {
                $sVariable = "\$this->_runModifier($sVariable, '$sMod', 'plugin', $sArg)";
            } else {
                switch ($sMod) {
                    case 'htmlspecialchars':
                        $sVariable = "Phpfox::getLib('parse.output')->htmlspecialchars({$sVariable})";
                        break;
                    case 'short_number':
                        $sVariable = 'Phpfox::getService(\'core.helper\')->shortNumber(' . $sVariable . ')';
                        break;
                    case 'filesize':
                        $sVariable = 'Phpfox::getLib(\'phpfox.file\')->filesize(' . $sVariable . ')';
                        break;
                    case 'clean':
                        if (isset($aArgs[0])) {
                            $sVariable = 'Phpfox::getLib(\'phpfox.parse.output\')->clean(' . $sVariable . ',' . $aArgs[0] . ')';
                        } else {
                            $sVariable = 'Phpfox::getLib(\'phpfox.parse.output\')->clean(' . $sVariable . ')';
                        }
                        $sVariable = 'Phpfox::getLib(\'phpfox.parse.output\')->cleanPhrases(' . $sVariable . ')';
                        break;
                    case 'clean_phrase':
                        $sVariable = 'md5(' . $sVariable . ')';
                        break;
                    case 'parse':
                        if (!empty($aArgs[0])) {
                            $sVariable = 'Phpfox::getLib(\'phpfox.parse.output\')->parse(' . $sVariable . ', ' . $aArgs[0] . ')';
                        } else {
                            $sVariable = 'Phpfox::getLib(\'phpfox.parse.output\')->parse(' . $sVariable . ')';
                        }
                        break;
                    case 'sprintf':
                        $sVariable = 'sprintf(' . $sVariable . '' . $sArg . ')';
                        break;
                    case 'date':
                        $sVariable = 'Phpfox::getTime(Phpfox::getParam(\'' . (empty($aArgs[0]) ? 'core.global_update_time' : $this->_removeQuote($aArgs[0])) . '\'), ' . $sVariable . ')';
                        break;
                    case 'highlight':
                        $sVariable = 'Phpfox::getLib(\'phpfox.search\')->highlight(' . $aArgs[0] . ', ' . $sVariable . ')';
                        break;
                    case 'feed_strip':
                        $sVariable = 'Phpfox::getLib(\'parse.output\')->feedStrip(' . $sVariable . ', ' . (isset($aArgs[0]) ? $aArgs[0] : 'false') . ')';
                        break;
                    case 'max_line': // @deprecated 4.7.0
                        break;
                    case 'translate':
                        $sPrefix = (isset($aArgs[0]) ? ', ' . $aArgs[0] : '');
                        $sVariable = 'Phpfox::getLib(\'phpfox.locale\')->translate(' . $sVariable . $sPrefix . ')';
                        break;
                    case 'eval':
                        $sVariable = 'eval(\' ?>\' . ' . $sVariable . ' . \'<?php \')';
                        break;
                    case 'tag_search':
                        $sVariable = 'str_replace(' . $aArgs[0] . ', \'<u>\' . ' . $aArgs[0] . ' . \'</u>\', ' . $sVariable . ')';
                        break;
                    case 'shorten':
                        if (!empty($aArgs[0]) && is_string($aArgs[0]) && preg_match('/[a-z]+\.{1}[a-z\_]+/', $aArgs[0], $aMatches) > 0) {
                            $sArg = $this->_removeQuote(trim(ltrim($aArgs[0], ', ')));
                            $sArg = ',' . Phpfox::getParam($sArg);
                        }
                        $sVariable = 'Phpfox::getLib(\'phpfox.parse.output\')->shorten(' . $sVariable . $sArg . ')';
                        break;
                    case 'split':
                        $sVariable = 'Phpfox::getLib(\'phpfox.parse.output\')->split(' . $sVariable . ', ' . $aArgs[0] . ')';
                        break;
                    case 'first_name':
                        $sVariable = 'Phpfox::getService(\'user\')->getFirstname(' . $sVariable . ')';
                        break;
                    case 'location':
                        $sVariable = 'Phpfox::getService(\'core.country\')->getCountry(' . $sVariable . ')';
                        break;
                    case 'location_child':
                        $sVariable = 'Phpfox::getService(\'core.country\')->getChild(' . $sVariable . ')';
                        break;
                    case 'stripbb':
                        $sVariable = 'Phpfox::getLib(\'phpfox.parse.bbcode\')->stripCode(' . $sVariable . ')';
                        break;
                    case 'striptag':
                        $sVariable = 'strip_tags(' . $sVariable . ')';
                        break;
                    case 'cleanbb':
                        $sVariable = 'Phpfox::getLib(\'phpfox.parse.bbcode\')->cleanCode(' . $sVariable . ')';
                        break;
                    case 'convert_time':
                        /**
                         * Inorder to use short type of date, we must pass the first argument
                         * (Default phrase to use with the display of the time stamp) and the second as `true`
                         *
                         * Ex:
                         * {$aItem.time_stamp|convert_time:null:true}
                         *
                         */
                        $sVariable = 'Phpfox::getLib(\'date\')->convertTime(' . $sVariable . '' . $sArg . ')';
                        break;
                    case 'micro_time':
                        $sVariable = 'date(\'Y-d-m\', ' . $sVariable . ')';
                        break;
                    //This case fix for legacy data. New phrases don't work
                    case 'convert':
                        $sVariable = 'Phpfox::getLib(\'locale\')->convert(' . $sVariable . ')';
                        break;
                    case 'user':
                        $sSuffix = '';
                        $sExtra = '';

                        if (count($aArgs)) {
                            if (!empty($aArgs[0])) {
                                $sSuffix = $this->_removeQuote($aArgs[0]);
                            }
                        }

                        $bAuthor = false;
                        $sValue = '\' . Phpfox::getLib(\'phpfox.parse.output\')->shorten(Phpfox::getLib(\'parse.output\')->clean(Phpfox::getService(\'user\')->getCurrentName(' . $sVariable . '[\'' . $sSuffix . 'user_id\'], ' . $sVariable . '[\'' . $sSuffix . 'full_name\'])), 0) . \'';
                        if (count($aArgs)) {
                            if (!empty($aArgs[1])) {
                                $sExtra .= $this->_removeQuote($aArgs[1]);
                            }

                            if (!empty($aArgs[2])) {
                                if (preg_match('/[a-z]+\.{1}[a-z\_]+/', $aArgs[2], $aMatches) > 0) {
                                    $aArgs[2] = Phpfox::getParam($this->_removeQuote($aArgs[2]));
                                }
                                $sValue = '\' . Phpfox::getLib(\'phpfox.parse.output\')->shorten(Phpfox::getLib(\'phpfox.parse.output\')->clean(Phpfox::getService(\'user\')->getCurrentName(' . $sVariable . '[\'' . $sSuffix . 'user_id\'], ' . $sVariable . '[\'' . $sSuffix . 'full_name\'])), ' . $this->_removeQuote($aArgs[2]) . ', \'...\') . \'';
                            }

                            if (isset($aArgs[3])) {
                                $aArgs[3] = $this->_removeQuote($aArgs[3]);
                            }
                            if (!empty($aArgs[3])) {
                                $sValue = '\' . Phpfox::getLib(\'phpfox.parse.output\')->shorten(Phpfox::getLib(\'phpfox.parse.output\')->split(Phpfox::getLib(\'phpfox.parse.output\')->clean(Phpfox::getService(\'user\')->getCurrentName(' . $sVariable . '[\'' . $sSuffix . 'user_id\'], ' . $sVariable . '[\'' . $sSuffix . 'full_name\'])), ' . $this->_removeQuote($aArgs[3]) . '' . (empty($aArgs[3]) ? '' : ', true') . '), 0) . \'';
                            }

                            if (isset($aArgs[4])) {
                                $aArgs[4] = $this->_removeQuote($aArgs[4]);
                                if (!empty($aArgs[4])) {
                                    $bAuthor = true;
                                    $sExtra .= ' rel="author" ';
                                }
                            }
                        }
                        $sUserName = '\' . Phpfox::getService(\'user\')->getUserName(' . $sVariable . '[\'' . $sSuffix . 'user_id\'], ' . $sVariable . '[\'' . $sSuffix . 'user_name\']) . \'';
                        $sVariable = '\'<span class="user_profile_link_span" id="js_user_name_link_' . $sUserName . '"' . ($bAuthor ? ' itemprop="author"' : '') . '>' . '\' . (Phpfox::getService(\'user.block\')->isBlocked(null, ' . $sVariable . '[\'' . $sSuffix . 'user_id\']) ? \'\' : \'<a href="\' . Phpfox::getLib(\'phpfox.url\')->makeUrl(\'profile\', array(' . $sVariable . '[\'' . $sSuffix . 'user_name\'], ((empty(' . $sVariable . '[\'' . $sSuffix . 'user_name\']) && isset(' . $sVariable . '[\'' . $sSuffix . 'profile_page_id\'])) ? ' . $sVariable . '[\'' . $sSuffix . 'profile_page_id\'] : null))) . \'"' . $sExtra . '>\') . \'' . $sValue . '\' . (Phpfox::getService(\'user.block\')->isBlocked(null, ' . $sVariable . '[\'' . $sSuffix . 'user_id\']) ? \'\' : \'</a>\') . \'' . '</span>\'';
                        break;
                    case 'gender':
                        $sVariable = 'Phpfox::getService(\'user\')->gender(' . $sVariable . $sArg . ')';
                        break;
                    case 'age':
                        $sVariable = 'Phpfox::getService(\'user\')->age(' . $sVariable . ')';
                        break;
                    case 'currency_symbol':
                        $sVariable = 'Phpfox::getService(\'core.currency\')->getSymbol(' . $sVariable . ')';
                        break;
                    case 'currency':
                        $sVariable = 'Phpfox::getService(\'core.currency\')->getCurrency(' . $sVariable . $sArg . ')';
                        break;
                    case 'hide_email':
                        $sVariable = 'Phpfox::getLib(\'phpfox.parse.format\')->hideEmail(' . $sVariable . ')';
                        break;
                    case 'privacy_phrase':
                        $sVariable = 'Phpfox::getService(\'privacy\')->getPhrase(' . $sVariable . ')';
                        break;
                    case 'category_display':
                        $sVariable = 'Phpfox::getService(\'core.category\')->displayView(' . $sVariable . $sArg . ')';
                        break;
                    case 'category_links':
                        $sVariable = 'Phpfox::getService(\'core.category\')->displayLinks(' . $sVariable . $sArg . ')';
                        break;
                    case 'lower':
                        $sVariable = 'strtolower(' . $sVariable . ')';
                        break;
                    case 'phone':
                        $sVariable = 'Phpfox::getLib(\'phone\')->parsePhone(' . $sVariable . '' . (isset($aArgs[0]) ? ', ' . $aArgs[0] : '') . ')';
                        break;
                    default:
                        if (function_exists($sMod)) {
                            $sVariable = '' . $sMod . '(' . $sVariable . $sArg . ')';
                        } else {
                            $sVariable = "Phpfox_Error::trigger(\"'" . $sMod . "' modifier does not exist\", E_USER_ERROR)";
                        }
                }
            }
        }

        return $sVariable;
    }

    /**
     * Load custom plug-ins.
     * NOTE: This is not in use yet.
     *
     * @param string $sFunction Custom function name.
     * @param string $sType Type of function.
     * @return string Returns function to load.
     */
    private function _plugin($sFunction, $sType)
    {
        if (isset($this->_aPlugins[$sType][$sFunction]) && is_array($this->_aPlugins[$sType][$sFunction]) && is_object($this->_aPlugins[$sType][$sFunction][0]) && method_exists($this->_aPlugins[$sType][$sFunction][0], $this->_aPlugins[$sType][$sFunction][1])) {
            return '$this->_aPlugins[\'' . $sType . '\'][\'' . $sFunction . '\'][0]->' . $this->_aPlugins[$sType][$sFunction][1];
        }

        if (isset($this->_aPlugins[$sType][$sFunction]) && function_exists($this->_aPlugins[$sType][$sFunction])) {
            return $this->_aPlugins[$sType][$sFunction];
        }

        if (function_exists('phpfox_' . $sType . '_' . $sFunction)) {
            $this->_aRequireStack[$sType . '.' . $sFunction . '.php'] = array($sType, $sFunction, 'phpfox_' . $sType . '_' . $sFunction);

            return 'phpfox_' . $sType . '_' . $sFunction;
        }

        if (file_exists(PHPFOX_DIR_TPL_PLUGIN . $sType . '.' . $sFunction . '.php')) {
            require_once(PHPFOX_DIR_TPL_PLUGIN . $sType . '.' . $sFunction . '.php');

            if (function_exists('phpfox_' . $sType . '_' . $sFunction)) {
                $this->_aRequireStack[$sType . '.' . $sFunction . '.php'] = array($sType, $sFunction, 'phpfox_' . $sType . '_' . $sFunction);

                return 'phpfox_' . $sType . '_' . $sFunction;
            }
        }
        return false;
    }

    /**
     * Compile custom function into the template it is loaded in.
     *
     * @param string $sFunction Name of the function.
     * @param string $sModifiers Modifier to load.
     * @param string $sArguments Arguments of the function.
     * @param string $sResult Converted string of the PHP function.
     * @return bool TRUE function converted, FALSE if it didn't convert.
     */
    private function _compileCustomFunction($sFunction, $sModifiers, $sArguments, &$sResult)
    {
        if ($sFunction = $this->_plugin($sFunction, "function")) {
            $aArgs = $this->_parseArgs($sArguments);
            foreach ($aArgs as $mKey => $mValue) {
                if (is_bool($mValue)) {
                    $mValue = $mValue ? 'true' : 'false';
                }
                if (is_null($mValue)) {
                    $mValue = 'null';
                }
                $aArgs[$mKey] = "'$mKey' => $mValue";
            }
            $sResult = '<?php echo ';
            if (!empty($sModifiers)) {
                $sResult .= $this->_parseModifier($sFunction . '(array(' . implode(',', (array)$aArgs) . '), $this)', $sModifiers) . '; ';
            } else {
                $sResult .= $sFunction . '(array(' . implode(',', (array)$aArgs) . '), $this);';
            }
            $sResult .= '?>';

            return true;
        } else {
            return false;
        }
    }

    /**
     * Compile IF statments.
     *
     * @param string $sArguments If statment arguments.
     * @param bool $bElseif TRUE if this is an ELSEIF.
     * @param bool $bWhile TRUE of this is a WHILE loop.
     * @return string Returns the converted PHP if statment code.
     */
    private function _compileIf($sArguments, $bElseif = false, $bWhile = false)
    {
        $aAllowed = array(
            'defined', 'is_array', 'isset', 'empty', 'count', '=', 'PHPFOX_IS_AJAX_PAGE', 'PHPFOX_IS_USER_PROFILE', 'PHPFOX_IS_PAGES_VIEW'
        );

        $sResult = "";
        $aArgs = array();
        $aArgStack = array();

        preg_match_all('/(?>(' . $this->_sVarRegexp . '|\/?' . $this->_sSvarRegexp . '|\/?' . $this->_sFuncRegexp . ')(?:' . $this->_sModRegexp . '*)?|\-?0[xX][0-9a-fA-F]+|\-?\d+(?:\.\d+)?|\.\d+|!==|===|==|!=|<>|<<|>>|<=|>=|\&\&|\|\||\(|\)|,|\!|\^|=|\&|\~|<|>|\%|\+|\-|\/|\*|\@|\b\w+\b|\S+)/x', $sArguments, $aMatches);
        $aArgs = $aMatches[0];

        $iCountArgs = count($aArgs);
        for ($i = 0, $iForMax = $iCountArgs; $i < $iForMax; $i++) {
            $sArg = &$aArgs[$i];
            switch (strtolower($sArg)) {
                case '!':
                case '%':
                case '!==':
                case '==':
                case '===':
                case '>':
                case '<':
                case '!=':
                case '<>':
                case '<<':
                case '>>':
                case '<=':
                case '>=':
                case '&&':
                case '||':
                case '^':
                case '&':
                case '~':
                case ')':
                case ',':
                case '+':
                case '-':
                case '*':
                case '/':
                case '@':
                    break;
                case 'eq':
                    $sArg = '==';
                    break;
                case 'ne':
                case 'neq':
                    $sArg = '!=';
                    break;
                case 'lt':
                    $sArg = '<';
                    break;
                case 'le':
                case 'lte':
                    $sArg = '<=';
                    break;
                case 'gt':
                    $sArg = '>';
                    break;
                case 'ge':
                case 'gte':
                    $sArg = '>=';
                    break;
                case 'and':
                    $sArg = '&&';
                    break;
                case 'or':
                    $sArg = '||';
                    break;
                case 'not':
                    $sArg = '!';
                    break;
                case 'mod':
                    $sArg = '%';
                    break;
                case '(':
                    array_push($aArgStack, $i);
                    break;
                case 'is':
                    $iIsArgCount = count($aArgs);
                    $sIsArg = implode(' ', array_slice($aArgs, 0, $i - 0));
                    $aArgTokens = $this->_compileParseIsExpr($sIsArg, array_slice($aArgs, $i + 1));
                    array_splice($aArgs, 0, count($aArgs), $aArgTokens);
                    $i = $iIsArgCount - count($aArgs);
                    break;
                default:
                    preg_match('/(?:(' . $this->_sVarRegexp . '|' . $this->_sSvarRegexp . '|' . $this->_sFuncRegexp . ')(' . $this->_sModRegexp . '*)(?:\s*[,\.]\s*)?)(?:\s+(.*))?/xs', $sArg, $aMatches);

                    if (isset($aMatches[0][0]) && ($aMatches[0][0] == '$' || $aMatches[0][0] == "'" || $aMatches[0][0] == '"')) {
                        $sArg = $this->_parseVariables(array($aMatches[1]), array($aMatches[2]));
                    }

                    if (!defined('PHPFOX_INSTALLER') && Phpfox::getParam('core.is_auto_hosted') && preg_match('/frontend_([a-zA-Z0-9]+)_template/i', $this->_sCurrentFile)) {
                        if (strtolower($sArg) != 'phpfox'
                            && !in_array(trim($sArg, "'"), $aAllowed)
                            && substr($sArg, 0, 2) != '::'
                            && substr($sArg, 0, 5) != '$this'
                        ) {
                            if (function_exists($sArg)) {
                                $sArg = '';
                            }
                        }
                    }

                    break;
            }
        }

        if ($bWhile) {
            return implode(' ', $aArgs);
        } else {
            if ($bElseif) {
                return '<?php elseif (' . implode(' ', $aArgs) . '): ?>';
            } else {
                return '<?php if (' . implode(' ', $aArgs) . '): ?>';
            }
        }

        return $sResult;
    }

    /**
     * Compile IF statment expressions.
     *
     * @param string $sIsArg If expression arguments.
     * @param string $aArgs Arguments.
     * @return string Converted PHP code.
     */
    private function _compileParseIsExpr($sIsArg, $aArgs)
    {
        $iExprEnd = 0;
        $bNegateExpr = false;

        if (($first_arg = array_shift($aArgs)) == 'not') {
            $bNegateExpr = true;
            $sExprType = array_shift($aArgs);
        } else {
            $sExprType = $first_arg;
        }

        switch ($sExprType) {
            case 'even':
                if (isset($aArgs[$iExprEnd]) && $aArgs[$iExprEnd] == 'by') {
                    $iExprEnd++;
                    $eExprArg = $aArgs[$iExprEnd++];
                    $sExpr = "!(1 & ($sIsArg / " . $this->_parseVariable($eExprArg) . "))";
                } else {
                    $sExpr = "!(1 & $sIsArg)";
                }
                break;
            case 'odd':
                if (isset($aArgs[$iExprEnd]) && $aArgs[$iExprEnd] == 'by') {
                    $iExprEnd++;
                    $eExprArg = $aArgs[$iExprEnd++];
                    $sExpr = "(1 & ($sIsArg / " . $this->_parseVariable($eExprArg) . "))";
                } else {
                    $sExpr = "(1 & $sIsArg)";
                }
                break;
            case 'div':
                if (@$aArgs[$iExprEnd] == 'by') {
                    $iExprEnd++;
                    $eExprArg = $aArgs[$iExprEnd++];
                    $sExpr = "!($sIsArg % " . $this->_parseVariable($eExprArg) . ")";
                } else {
                    Phpfox_Error::trigger("expecting 'by' after 'div'", E_USER_ERROR);
                }
                break;
            default:
                Phpfox_Error::trigger("unknown 'is' expression - '$sExprType'", E_USER_ERROR);
                break;
        }

        if ($bNegateExpr) {
            $sExpr = "!($sExpr)";
        }

        array_splice($aArgs, 0, $iExprEnd, $sExpr);

        return $aArgs;
    }

    /**
     * Complie sections {section}{/section}
     *
     * @param string $sArguments Section arguments.
     * @return string Converted PHP foreach().
     */
    private function _compileSectionStart($sArguments)
    {
        $aAttrs = $this->_parseArgs($sArguments);

        $sOutput = '<?php ';
        $sSectionName = $aAttrs['name'];
        if (empty($sSectionName)) {
            Phpfox_Error::trigger("missing section name", E_USER_ERROR);
        }

        $sOutput .= "if (isset(\$this->_aSections['$sSectionName'])) unset(\$this->_aSections['$sSectionName']);\n";
        $sSectionProps = "\$this->_aSections['$sSectionName']";

        foreach ($aAttrs as $sAttrName => $sAttrValue) {
            switch ($sAttrName) {
                case 'loop':
                    $sOutput .= "{$sSectionProps}['loop'] = is_array($sAttrValue) ? count($sAttrValue) : max(0, (int)$sAttrValue);\n";
                    break;
                case 'show':
                    if (is_bool($sAttrValue)) {
                        $bShowAttrValue = $sAttrValue ? 'true' : 'false';
                    } else {
                        $bShowAttrValue = "(bool)$sAttrValue";
                    }
                    $sOutput .= "{$sSectionProps}['show'] = $bShowAttrValue;\n";
                    break;
                case 'name':
                    $sOutput .= "{$sSectionProps}['$sAttrName'] = '$sAttrValue';\n";
                    break;
                case 'max':
                case 'start':
                    $sOutput .= "{$sSectionProps}['$sAttrName'] = (int)$sAttrValue;\n";
                    break;
                case 'step':
                    $sOutput .= "{$sSectionProps}['$sAttrName'] = ((int)$sAttrValue) == 0 ? 1 : (int)$sAttrValue;\n";
                    break;
                default:
                    Phpfox_Error::trigger("unknown section attribute - '$sAttrName'", E_USER_ERROR);
                    break;
            }
        }

        if (!isset($aAttrs['show'])) {
            $sOutput .= "{$sSectionProps}['show'] = true;\n";
        }

        if (!isset($aAttrs['loop'])) {
            $sOutput .= "{$sSectionProps}['loop'] = 1;\n";
        }

        if (!isset($aAttrs['max'])) {
            $sOutput .= "{$sSectionProps}['max'] = {$sSectionProps}['loop'];\n";
        } else {
            $sOutput .= "if ({$sSectionProps}['max'] < 0)\n" .
                "	{$sSectionProps}['max'] = {$sSectionProps}['loop'];\n";
        }

        if (!isset($aAttrs['step'])) {
            $sOutput .= "{$sSectionProps}['step'] = 1;\n";
        }

        if (!isset($aAttrs['start'])) {
            $sOutput .= "{$sSectionProps}['start'] = {$sSectionProps}['step'] > 0 ? 0 : {$sSectionProps}['loop']-1;\n";
        } else {
            $sOutput .= "if ({$sSectionProps}['start'] < 0)\n" .
                "	{$sSectionProps}['start'] = max({$sSectionProps}['step'] > 0 ? 0 : -1, {$sSectionProps}['loop'] + {$sSectionProps}['start']);\n" .
                "else\n" .
                "	{$sSectionProps}['start'] = min({$sSectionProps}['start'], {$sSectionProps}['step'] > 0 ? {$sSectionProps}['loop'] : {$sSectionProps}['loop']-1);\n";
        }

        $sOutput .= "if ({$sSectionProps}['show']) {\n";
        if (!isset($aAttrs['start']) && !isset($aAttrs['step']) && !isset($aAttrs['max'])) {
            $sOutput .= "	{$sSectionProps}['total'] = {$sSectionProps}['loop'];\n";
        } else {
            $sOutput .= "	{$sSectionProps}['total'] = min(ceil(({$sSectionProps}['step'] > 0 ? {$sSectionProps}['loop'] - {$sSectionProps}['start'] : {$sSectionProps}['start']+1)/abs({$sSectionProps}['step'])), {$sSectionProps}['max']);\n";
        }
        $sOutput .= "	if ({$sSectionProps}['total'] == 0)\n" .
            "		{$sSectionProps}['show'] = false;\n" .
            "} else\n" .
            "	{$sSectionProps}['total'] = 0;\n";

        $sOutput .= "if ({$sSectionProps}['show']):\n";
        $sOutput .= "
			for ({$sSectionProps}['index'] = {$sSectionProps}['start'], {$sSectionProps}['iteration'] = 1;
				 {$sSectionProps}['iteration'] <= {$sSectionProps}['total'];
				 {$sSectionProps}['index'] += {$sSectionProps}['step'], {$sSectionProps}['iteration']++):\n";
        $sOutput .= "{$sSectionProps}['rownum'] = {$sSectionProps}['iteration'];\n";
        $sOutput .= "{$sSectionProps}['index_prev'] = {$sSectionProps}['index'] - {$sSectionProps}['step'];\n";
        $sOutput .= "{$sSectionProps}['index_next'] = {$sSectionProps}['index'] + {$sSectionProps}['step'];\n";
        $sOutput .= "{$sSectionProps}['first']	  = ({$sSectionProps}['iteration'] == 1);\n";
        $sOutput .= "{$sSectionProps}['last']	   = ({$sSectionProps}['iteration'] == {$sSectionProps}['total']);\n";

        $sOutput .= "?>";

        return $sOutput;
    }

    /**
     * Remove quotes from PHP variables.
     *
     * @param string $string PHP variable to work with.
     * @return string Converted PHP variable.
     */
    private function _removeQuote($string)
    {
        if (($string[0] == "'" || $string[0] == '"') && $string[strlen($string) - 1] == $string[0]) {
            return substr($string, 1, -1);
        } else {
            return $string;
        }
    }
}