<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');
define('PHPFOX_AJAX_CALL_PROCESS', true);

/**
 * Handles AJAX Requests and returns data outputed
 * by the specified call which connects to a modules ajax
 * component. Many of the methods within this class returns the
 * classes own object thus allow calls like:
 * <code>
 * $this->hide('#foo')->show('#bar')->html('Test Content')->call('init();');
 * </code>
 *
 * @method Phpfox_Ajax removeClass($element, $param = '')
 * @method Phpfox_Ajax addClass($element, $param = '')
 * @method Phpfox_Ajax val($element, $value)
 * @method Phpfox_Ajax focus($element)
 * @method Phpfox_Ajax show($element)
 * @method Phpfox_Ajax remove($element)
 * @method Phpfox_Ajax hide($element)
 * @method Phpfox_Ajax slideDown($element)
 * @method Phpfox_Ajax slideUp($element)
 * @method Phpfox_Ajax submit()
 * @method Phpfox_Ajax attr($element, $second, $third)
 * @method Phpfox_Ajax height($element, $value = '')
 * @method Phpfox_Ajax width($element)
 * @method Phpfox_Ajax after($element)
 * @method Phpfox_Ajax before($element)
 * @method Phpfox_Ajax fadeOut($element)
 * @copyright        [PHPFOX_COPYRIGHT]
 * @author            phpFox LLC
 * @package        Phpfox
 * @version        $Id: ajax.class.php 6626 2013-09-12 07:49:28Z Fern $
 */
class Phpfox_Ajax
{
    /**
     * Stores all the AJAX calls.
     *
     * @static
     * @var array
     */
    private static $_aCalls = [];

    /**
     * Holds all the $_POST data when sending information via AJAX.
     *
     * @static
     * @var array
     */
    private static $_aParams = [];

    /**
     * During an AJAX call you can use "Phpfox_Error::set();" to set error messages and
     * by default the AJAX class will pick up on these errors and return the error message
     * to the user. However, in some cases you may not want to use the default error reporting
     * routine and create your own and by setting this to false will disable to error reporting
     * routine.
     *
     * @var bool
     */
    private static $_bShowErrors = true;

    /**
     * This is the HTML div ID that holds error messages
     *
     * @var string
     */
    private static $_sErrorHolder = null;

    /**
     * These are jQuery functions we support, which can be called via this class
     *
     * @example $this->hide('#sample'); This in jQuery would be $('#sample').hide();
     * @var array
     */
    private $_aJquery = [
        'addClass',
        'removeClass',
        'val',
        'focus',
        'show',
        'remove',
        'hide',
        'slideDown',
        'slideUp',
        'submit',
        'attr',
        'height',
        'width',
        'after',
        'before',
        'fadeOut'
    ];

    /**
     * Holds the Request object
     *
     * @var object
     */
    private $_oReq;

    /**
     * Holds the default 'phpfox' parameters being passed by the
     * post request.
     *
     * @var array
     */
    private $_aRequest;

    public $bIsModeration = false;

    public $sPopupMessage = '';

    /**
     * Class Constructor
     *
     */
    public function __construct()
    {
        $this->_oReq = Phpfox_Request::instance();
        $this->_aRequest = $this->_oReq->getArray(Phpfox::getTokenName());

        (($sPlugin = Phpfox_Plugin::get('ajax__construct')) ? eval($sPlugin) : false);
    }

    /**
     * @return $this
     */
    public static function instance()
    {
        return Phpfox::getLib('ajax');
    }

    /**
     * Process the AJAX request
     *
     * @return bool If we can load the component we return true, false on failure
     */
    public function process()
    {
        Phpfox::getService('log.session')->verifyToken();

        if (empty($this->_aRequest)) {
            return false;
        }
        $aParts = explode('.', $this->_aRequest['call']);
        $sModule = $aParts[0];
        if (isset($aParts[2])) {
            $sModule .= '.' . $aParts[1] . '.ajax';
            $sMethod = $aParts[2];
        } else {
            $sMethod = $aParts[1];
        }

        foreach ($this->_oReq->getRequests() as $sKey => $mValue) {
            self::$_aParams[$sKey] = $mValue;
            if (isset($mValue['call']) && strpos($mValue['call'], '.') !== false) {
                $aParts = explode('.', $mValue['call']);
                if (isset($aParts[1]) && $aParts[1] == 'moderation') {
                    $this->bIsModeration = true;
                }
            }
        }

        if ($sModule == 'im' && ($sMethod == 'getRooms' || $sMethod == 'getMessages')) {
            $sMethod = 'getUpdate';
        }
        (($sPlugin = Phpfox_Plugin::get('ajax_process')) ? eval($sPlugin) : false);

        if (Phpfox::demoMode($sModule, $sMethod)) {
            return true;
        }

        $this->template()->assign('aGlobalUser', (Phpfox::isUser() ? Phpfox::getUserBy(null) : []));
        $this->template()->assign('aGlobalProfilePageLogin', (Phpfox::getUserBy('profile_page_id') ? Phpfox::getService('pages')->getLastLogin() : false));

        if (isset($this->_aRequest['is_user_profile']) && $this->_aRequest['is_user_profile'] && !defined('PHPFOX_IS_USER_PROFILE') && isset($this->_aRequest['profile_user_id'])) {
            define('PHPFOX_IS_USER_PROFILE', true);
            Phpfox::getService('profile')->setUserId($this->_aRequest['profile_user_id']);
        }

        $bCache = false;
        // Should we cache the data?
        if (isset(self::$_aParams[Phpfox::getTokenName()]['cache']) && self::$_aParams[Phpfox::getTokenName()]['cache']) {
            $bCache = true;
            $oCache = Phpfox::getLib('cache');
            $sCacheId = $oCache->set('ajax_' . strtolower($sModule) . '_' . strtolower($sMethod));
            if ($sContent = $oCache->get($sCacheId)) {
                echo $sContent;
                return true;
            }
        }

        // Lets get the Ajax component for this module
        if ($oObject = Phpfox::getComponent($sModule, [], 'ajax')) {
            // Call the method for this component
            $oObject->$sMethod();

            if ($bCache && isset($oCache) && isset($sCacheId)) {
                $oCache->save($sCacheId, ob_get_contents());
            }

            return true;
        }

        // Since this is now an invalid call lets clear out the error message
        ob_clean();

        // Lets tell the dev that its an invalid call
        // $this->debug('Invalid Component: ' . $sModule . 'ajax');

        return false;
    }

    /**
     * Used to get $_POST requests send via an AJAX request
     *
     * @param string $sVar Name of the post param
     * @param mixed $mDef You can pass a default value in case the $_POST data was not sent
     * @return string|array
     */
    public function get($sVar, $mDef = null)
    {
        return isset(self::$_aParams[$sVar]) ? self::$_aParams[$sVar] : $mDef;
    }

    /**
     * Get all the $_POST params sent over via an AJAX request
     *
     * @return array Array of all the $_POST params
     */
    public function getAll()
    {
        return self::$_aParams;
    }

    /**
     * Manually set $_POST information if it was not sent via an AJAX request
     *
     * @example $this->set('foo', 'bar') or $this->set(array('foo' => 'bar'))
     * @param mixed $mVar It can be a string, which will hold the param var or an array of values
     * @param mixed $mDef This is the default value of the param you are creating
     */
    public function set($mVar, $mDef = '')
    {
        if (!is_array($mVar)) {
            $mVar = [$mVar => $mDef];
        }

        foreach ($mVar as $sKey => $mValue) {
            self::$_aParams[$sKey] = $mValue;
        }
    }

    /**
     * jQuery has support for innerHTML and we use that method, however we add a little extra
     * protection with our routine.
     *
     * @example $this->html('#sample', 'This is a test');
     * @param string $sId HTML id for the element
     * @param string $sContent Content to place inside the HTML element
     * @param string $sExtra Optional jQuery functions you may want to execute
     * @return $this
     */
    public function html($sId, $sContent, $sExtra = '')
    {
        return $this->execElement($sId, 'html', $sContent, $sExtra);
    }

    /**
     * @param        $sId
     * @param        $sContent
     * @param string $sExtra
     *
     * @return \Phpfox_Ajax
     */
    public function insertAfter($sId, $sContent, $sExtra = '')
    {
        return $this->execToElement($sId, 'insertAfter', $sContent, $sExtra);
    }

    /**
     * @param        $sId
     * @param        $sContent
     * @param string $sExtra
     *
     * @return \Phpfox_Ajax
     */
    public function insertBefore($sId, $sContent, $sExtra = '')
    {
        return $this->execToElement($sId, 'insertBefore', $sContent, $sExtra);
    }

    /**
     * @param        $sId
     * @param        $sMethod
     * @param        $sContent
     * @param string $sExtra
     *
     * @return $this
     */
    public function execElement($sId, $sMethod, $sContent, $sExtra = '')
    {
        $sContent = str_replace('\\', '\\\\', $sContent);
        $sContent = str_replace('"', '\"', $sContent);

        (($sPlugin = Phpfox_Plugin::get('ajax_html')) ? eval($sPlugin) : false);

        $this->call("$('" . $sId . "').{$sMethod}(\"" . $sContent . "\")" . $sExtra . ";");

        return $this;
    }

    /**
     * @param        $sId
     * @param        $sMethod
     * @param        $sContent
     * @param string $sExtra
     *
     * @return $this
     */
    public function execToElement($sId, $sMethod, $sContent, $sExtra = '')
    {
        $sContent = str_replace('\\', '\\\\', $sContent);
        $sContent = str_replace('"', '\"', $sContent);

        (($sPlugin = Phpfox_Plugin::get('ajax_html')) ? eval($sPlugin) : false);

        $this->call("$(\"" . $sContent . "\").{$sMethod}('" . $sId . "')" . $sExtra . ";");

        return $this;
    }

    /**
     * jQuery has support for prepend() and we use that method, however we add a little extra
     * protection with our routine.
     *
     * @example $this->prepend('#sample', 'This is a test');
     * @param string $sId HTML id for the element
     * @param string $sContent Content to prepend
     * @param string $sExtra Optional jQuery functions you may want to execute
     * @return $this
     */
    public function prepend($sId, $sContent, $sExtra = '')
    {
        $sContent = str_replace(["\n", "\t"], '', $sContent);
        $sContent = str_replace('\\', '\\\\', $sContent);
        $sContent = str_replace('"', '\"', $sContent);

        (($sPlugin = Phpfox_Plugin::get('ajax_prepend')) ? eval($sPlugin) : false);

        $this->call("$('" . $sId . "').prepend(\"" . $sContent . "\")" . $sExtra . ";");

        return $this;
    }

    /**
     * jQuery has support for append() and we use that method, however we add a little extra
     * protection with our routine.
     *
     * @example $this->append('#sample', 'This is a test');
     * @param string $sId HTML id for the element
     * @param string $sContent Content to append
     * @param string $sExtra Optional jQuery functions you may want to execute
     * @param bool $bReplaceNewline
     * @return $this
     */
    public function append($sId, $sContent, $sExtra = '', $bReplaceNewline = true)
    {
        if ($bReplaceNewline) {
            $sContent = str_replace('\n', '', $sContent);
        }

        $sContent = str_replace('\t', '', $sContent);
        $sContent = str_replace('\\', '\\\\', $sContent);
        $sContent = str_replace('"', '\"', $sContent);

        (($sPlugin = Phpfox_Plugin::get('ajax_append')) ? eval($sPlugin) : false);

        $this->call("$('" . $sId . "').append(\"" . $sContent . "\")" . $sExtra . ";");

        return $this;
    }

    /**
     * jQuery has support for append() and we use that method, however we add a little extra
     * protection with our routine.
     *
     * @example $this->append('#sample', 'This is a test');
     * @param string $sId HTML id for the element
     * @param string $sContent Content to append
     * @param string $sExtra Optional jQuery functions you may want to execute
     * @return $this
     */
    public function replaceWith($sId, $sContent, $sExtra = '')
    {
        $sContent = str_replace(["\n", "\t"], '', $sContent);
        $sContent = str_replace('\\', '\\\\', $sContent);
        $sContent = str_replace('"', '\"', $sContent);

        (($sPlugin = Phpfox_Plugin::get('ajax_append')) ? eval($sPlugin) : false);

        $this->call("$('" . $sId . "').replaceWith(\"" . $sContent . "\")" . $sExtra . ";");

        return $this;
    }

    /**
     * Used to call any JavaScript back to the browser once the AJAX routine is complete.
     *
     * @example $this->call("document.getElementById('test').style.display = 'none';"); or $this->call('$("#test").hide();');
     * @param string $sCall JavaScript that you plan to execute back to the browser
     * @return $this
     */
    public function call($sCall)
    {
        $sCall = str_replace('im.getMessages', 'im.getUpdate', $sCall);
        $sCall = str_replace('im.getRooms', 'im.getUpdate', $sCall);
        self::$_aCalls[] = $sCall;

        return $this;
    }

    /**
     * Our product is designed to automatically echo data from components such as blocks and controllers
     * and within an AJAX call we need to get that from the output buffer so we could possible place it
     * within a specific HTML element.
     *
     * @param bool $bClean Set to true if we should attempt to clean out the content depending on how you plan to return it.
     * @return string Returns the output thus allowing you to use it in any way you want.
     */
    public function getContent($bClean = true)
    {
        $sContent = ob_get_contents();

        ob_clean();

        if ($bClean) {
            $sContent = str_replace('\\', '\\\\', $sContent);
            $sContent = str_replace("'", "\\'", $sContent);
            $sContent = str_replace('"', '\"', $sContent);
        }

        (($sPlugin = Phpfox_Plugin::get('ajax_getcontent')) ? eval($sPlugin) : false);

        return $sContent;
    }

    /**
     * Controls if you want to use our default error system or create your own.
     *
     * @param bool $bShowErrors True by default and will use our error system | False to create your own.
     */
    public function error($bShowErrors)
    {
        self::$_bShowErrors = $bShowErrors;
    }

    /**
     * We have our own default error div with a specific ID, however you can use this with this function.
     *
     * @param string $sDiv ID of the HTML element
     */
    public function errorSet($sDiv)
    {
        self::$_sErrorHolder = $sDiv;
    }

    /**
     * This is the final output to the browser once the AJAX request is complete.
     *
     * @return string Data to return back to the browser. It must be JavaScript code.
     */
    public function getData()
    {
        if ($this->get('js_block_click_lis_cache')) {
            $this->remove('.js_block_click_lis_cache');
        }

        if ($this->get('global_ajax_message')) {
            $this->hide('#global_ajax_message');
        }

        if (empty($this->_aRequest)) {
            return '';
        }
        if (isset($this->_aRequest['call']) && $this->_aRequest['call'] != 'im.getRooms' && $this->_aRequest['call'] != 'im.getMessages' && !isset(self::$_aParams['js_disable_ajax_restart'])) {
            if (isset($this->_aRequest['last_call'])) {
                if ($this->_aRequest['call'] != 'im.load'
                    && $this->_aRequest['call'] != 'im.open'
                    && $this->_aRequest['call'] != 'im.chat'
                    && $this->_aRequest['call'] != 'im.close'
                    && $this->_aRequest['call'] != 'im.getRooms'
                    && $this->_aRequest['call'] != 'im.getMessages'
                ) {
                    switch ($this->_aRequest['last_call']) {
                        case 'im.getRooms':
                            $this->call("$.ajaxCall('im.getRooms','','GET');");
                            break;
                        case 'im.getMessages':
                            $this->call("$.ajaxCall('im.getMessages', 'im_id=" . $this->_aRequest['last_param'] . "','GET');");
                            break;
                    }
                }
            }
        }

        $sXml = '';
        foreach (self::$_aCalls as $sCall) {
            $sXml .= $this->_ajaxSafe($sCall);
        }

        (($sPlugin = Phpfox_Plugin::get('ajax_getdata')) ? eval($sPlugin) : false);

        if (self::$_bShowErrors && !Phpfox_Error::isPassed()) {
            $sErrors = '';
            foreach (Phpfox_Error::get() as $sError) {
                $sErrors .= '<div class="error_message">' . $sError . '</div>';
            }

            echo $sXml;

            if (self::$_sErrorHolder !== null) {
                self::$_aCalls = [];

                $this->show(self::$_sErrorHolder)->html(self::$_sErrorHolder, $sErrors);
                return implode('', self::$_aCalls);
            } else {
                $this->alert($sErrors, (empty($this->sPopupMessage) ? _p('error') : $this->sPopupMessage));
            }

            return '';
        }

        return $sXml;
    }

    public function returnCalls()
    {
        return self::$_aCalls;
    }

    /**
     * Quick function that can be used to identify if a user is logged it or not and if not
     * they will not be able to use the specific feature and display a login form.
     *
     * @return mixed Returns true if they are logged in or simply exisits the script and returns JavaScript to display the login form.
     */
    public function isUser()
    {
        if (!Phpfox::isUser()) {
            if (isset(self::$_aParams['width'])) // && isset(self::$_aParams['height']))
            {
                echo '<script type="text/javascript">$(\'.js_box_title\').html(\'' . _p('login_ajax') . '\');</script>';
                Phpfox::getBlock('user.login-ajax');
            } else {
                if (Phpfox_Request::instance()->get('do') != '') {
                    Phpfox::getLib('session')->set('redirect', Phpfox_Request::instance()->get('do'));
                }

                echo "tb_show('" . _p('sign_in') . "', \$.ajaxBox('user.login', 'height=250&width=400" . ((isset(self::$_aParams[Phpfox::getTokenName()]['is_admincp']) && self::$_aParams[Phpfox::getTokenName()]['is_admincp']) ? '&' . Phpfox::getTokenName() . '[is_admincp]=1' : '') . "'));";
                echo "$('body').css('cursor', 'auto');";
            }
            if (!PHPFOX_UNIT_TEST) exit;
        }
        return true;
    }

    /**
     * Sets the title of the AJAX modal
     *
     * @param string $sTitle Title to set
     * @return $this
     */
    public function setTitle($sTitle)
    {
        $this->call('<div class="js_box_title_store">' . $sTitle . '</div>');

        return $this;
    }

    /**
     * This is a small function to show soft notices, for example when several ajax interactions are
     * expected to happen in a short amount of time. Primarily used in the designDnD feature
     * so when moving a block it gives a quick feedback that the change was saved.
     * @param string $sText Message to show
     * @param string $sType relates to the css class to use, this class must exist in common.css
     * @return $this
     */
    public function softNotice($sText, $sType = 'positive', $iTimeout = 2000)
    {
        $sId = 'softNotice' . rand(10, 20) . '';
        $sDiv = '<div id="' . $sId . '" class="softNotice' . ucwords($sType) . '">' . $sText . '</div>';
        $this->call('$("body").append(\'' . $sDiv . '\'); $("#' . $sId . '").slideDown("slow");setTimeout(function(){$("#' . $sId . '").slideUp("slow", function(){	$(this).remove();});				},' . $iTimeout . ');');

        return $this;
    }

    /**
     * Set debug information which can be picked up with Firebug.
     *
     * @param string $sMsg Debug information you want to pass to Firebug
     */
    public static function debug($sMsg)
    {
        self::call('debug("' . $sMsg . '");');
    }

    /**
     * At times you may want to notify your users and instead of using the default JavaScript alert() we provide
     * a AJAX popup version that works well with the sites theme.
     *
     * @param string $sMessage Message to pass to your users
     * @param string $sTitle Title of the alert box
     * @param int $iWidth Width of the alert box
     * @param int $iHeight Height of the alert box
     * @param bool $bClose TRUE to remove the alert box after 2 seconds
     * @param bool $bReturn
     * @return null|string
     */
    public function alert($sMessage, $sTitle = null, $iWidth = 300, $iHeight = 150, $bClose = false, $bReturn = false)
    {
        if (!function_exists('getallheaders')) {
            function getallheaders()
            {
                $headers = [];
                foreach ($_SERVER as $name => $value) {
                    if (substr($name, 0, 5) == 'HTTP_') {
                        $headers[str_replace(' ', '-',
                            ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                    }
                }

                return $headers;
            }
        }

        $aHeaders = getallheaders();
        $bEchoScript = !$bReturn && is_array($aHeaders) && !empty($aHeaders['Accept']) && strpos($aHeaders['Accept'], 'text/html') !== false;

        if ($bEchoScript) {
            echo '<script>';
        }

        if (isset(self::$_aParams['ajax_post_photo_theater'])) {
            $sNewMessage = "<div class=\"error_message\">" . str_replace("'", "\'", $sMessage) . "</div>";
            if (!empty(self::$_aParams['val']['parent_id'])) {
                echo "$('.js_feed_comment_parent_id').each(function(){ if ($(this).val() == " . self::$_aParams['val']['parent_id'] . "){ $(this).parents('form:first').find('.js_feed_add_comment_button:first').prepend('" . $sNewMessage . "'); }});";
            } else {
                $iId = 'js_feed_comment_form_' . self::$_aParams['val']['is_via_feed'];
                echo "$('.js_feed_add_comment_button .error_message').remove(); $('#" . $iId . "').find('.js_feed_add_comment_button:first').prepend('" . $sNewMessage . "');";
            }
            return null;
        }

        if (isset(self::$_aParams['tb'])) {
            ob_clean();

            echo $sMessage;
        } else {
            $sMessage = strip_tags(str_replace("'", "\'", $sMessage));
            if (defined('PHPFOX_ERROR_FORCE_LOGOUT') && PHPFOX_ERROR_FORCE_LOGOUT) {
                $sMessage .= '<button class="button btn-primary" onclick="$Core.reloadPage();">' . _p('ok') . '</button>';
            }
            $sStr = 'window.parent.sCustomMessageString = \'' . $sMessage . '\';';
            $sStr .= "tb_show('" . str_replace("'", "\'", ($sTitle === null ? _p('notice') : $sTitle)) . "', \$.ajaxBox('core.message', 'height={$iHeight}&width={$iWidth}'));";
            if ($bClose) {
                $sStr .= 'setTimeout(\'tb_remove();\', 2000);';
            }

            if ($bReturn) {
                return $sStr;
            }
            echo $sStr;
        }

        if ($bEchoScript) {
            echo '</script>';
        }

        return null;
    }

    /**
     * Emulate jQuery calls.
     *
     * @param string $sMethod jQuery method we are trying to call.
     * @param array $aArguments Array of option arguments being passed to jQuery.
     * @return $this
     */
    public function __call($sMethod, $aArguments)
    {
        if (!in_array($sMethod, $this->_aJquery)) {
            return Phpfox_Error::trigger('Not a valid jQuery function', E_USER_ERROR);
        }

        $sArgs = '';
        foreach ($aArguments as $iKey => $sArgument) {
            if ($iKey == 0) {
                continue;
            }

            $sValue = '\'' . str_replace("'", "\'", $sArgument) . '\'';
            if (is_bool($sArgument)) {
                $sValue = ($sArgument === true ? 'true' : 'false');
            }

            $sArgs .= $sValue . ',';
        }
        $sArgs = rtrim($sArgs, ',');

        $this->call('$(\'' . $aArguments[0] . '\').' . $sMethod . '(' . $sArgs . ');');

        return $this;
    }

    /**
     * Sets a public message on the site for the user to see and closes the AJAX popup box for you.
     *
     * @param string $sMessage Message you want to display to the user.
     */
    public function setMessage($sMessage)
    {
        $this->height('#TB_ajaxContent', '35px')->html('#TB_ajaxContent', '<div class="valid_message">' . $sMessage . '</div>')->call('setTimeout("tb_remove();", 1000);');
    }

    /**
     * Updates moderation count menu
     *
     */
    public function updateCount()
    {
        $this->call('var oSubsectionCountItem = $(\'.sub_section_menu .active .pending\'); if ($(oSubsectionCountItem).length > 0) { var iSubsectionCount = parseInt(oSubsectionCountItem.html()); if (iSubsectionCount > 1) { oSubsectionCountItem.html(parseInt(iSubsectionCount - 1)); } else { $(\'.sub_section_menu .active\').remove(); } }');
    }

    /**
     * Extend template
     *
     * @example $this->template()->assign();
     * @example $this->template()->getTemplate();
     * @return object Template object
     */
    protected function template()
    {
        return Phpfox_Template::instance();
    }

    /**
     * Safe AJAX Code
     *
     * @param    string $sStr String to replace
     * @return    string    Safe string
     */
    private function _ajaxSafe($sStr)
    {
        $sStr = str_replace(["\n", "\r"], '\\n', $sStr);

        (($sPlugin = Phpfox_Plugin::get('ajax__ajaxsafe')) ? eval($sPlugin) : false);

        return $sStr;
    }

    /**
     * Show an alert message with slide down transition.
     * @param $sParentSelector
     * @param $sMessage
     * @param string $sType
     * @param string $sExtraClass
     * @return Phpfox_Ajax
     */
    public function slideAlert($sParentSelector, $sMessage, $sType = 'success', $sExtraClass = '')
    {
        $this->call('$Core.slideAlert(\'' . $sParentSelector . '\', \'' . $sMessage . '\', \'' . $sType . '\', \'' . $sExtraClass . '\');');

        return $this;
    }

    /*
     * Refresh current page
     */
    public function reload()
    {
        $this->call('window.location.reload();');

        return $this;
    }
}
