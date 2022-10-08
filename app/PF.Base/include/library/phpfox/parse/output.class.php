<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 * Cleans text added to the database and already parsed/cleaned via Parse_Input.
 * This is the last attempt to clean out any invalid tags, usually fix invalid HTML tags.
 * Anything that needs to be removed should have already been removed with Parse_Input to save
 * on running such a heavy routine for each item. We also parse naughty words here as
 * this information needs to be checked each time in case new words are added by an Admin.
 *
 * @property mixed disable_all_external_emails
 * @property mixed disable_all_external_urls
 * @property mixed warn_on_external_links
 * @property mixed no_follow_on_external_links
 * @property mixed allow_html
 * @property mixed enable_hashtag_support
 * @see              Parse_Input
 * @see              Parse_Bbcode
 * @copyright        [PHPFOX_COPYRIGHT]
 * @package          Phpfox
 * @version          $Id: output.class.php 7308 2014-05-08 14:55:48Z Fern $
 */
class Phpfox_Parse_Output
{
    private $_regex = [
        'hash_tags' => '(#[^\s!@#$%^&*()=\-+.\/,\[{\]};:\'"?><\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]+)(?![^<]*(\'|")[^>]*>)',
        'mentions'  => '^\[user=(.*?)\]^'
    ];

    /**
     * Parsing settings for images.
     *
     * @var array
     */
    private $_aImageParams = [];

    /**
     * Defines if the string has been shortened or not.
     *
     * @var bool
     */
    private $_bIsShortened = false;

    /**
     * Defines if the string has reached the maximum break lines or not
     *
     * @var bool
     */
    private $_bIsMaxLine = false;

    /**
     * Class Constructor.
     *
     */
    public function __construct()
    {
        $this->disable_all_external_emails = Phpfox::getParam('core.disable_all_external_emails');
        $this->disable_all_external_urls = Phpfox::getParam('core.disable_all_external_urls');
        $this->warn_on_external_links = Phpfox::getParam('core.warn_on_external_links');
        $this->no_follow_on_external_links = Phpfox::getParam('core.no_follow_on_external_links');
        $this->allow_html = Phpfox::getParam('core.allow_html');
        $this->enable_hashtag_support = Phpfox::getParam('tag.enable_hashtag_support');
    }

    /**
     * @return $this;
     */
    public static function instance()
    {
        return Phpfox::getLib('parse.output');
    }

    /**
     * Shorten string within user/page/group tags
     * @param $sText
     * @param $iMaxLength
     * @param null $sSuffix
     * @param false $bStripTags
     * @return false|string
     */
    public function shortenResourceTags($sText, $iMaxLength, $sSuffix = null, $bStripTags = false)
    {
        if (!isset($sText) || $sText == '' || empty($iMaxLength) || (int)$iMaxLength <= 0) {
            return $sText;
        }

        $sText = str_replace("&nbsp;", ' ', $sText);
        $iCurrentLength = $iMaxLength;
        $iRealTagLength = 0;
        $bHasTags = false;

        preg_replace_callback('/<span[\s]+class="user_profile_link_span"[^>]+>(?:<a(?:[^>]+)?>)?(.+?)(?:<\/a>)?<\/span>/u', function($matches) use ($sText, &$iMaxLength, &$iCurrentLength, &$iRealTagLength, &$bHasTags) {
            if (!empty($matches[1])) {
                $iPos = mb_strpos($sText, $matches[0]);
                $iFullNameLength = mb_strlen($matches[1]);
                $iTotalMatchLength = mb_strlen($matches[0]);
                if (($iPos - $iRealTagLength + $iFullNameLength) < $iMaxLength) {
                    $iCurrentLength = $iPos + $iTotalMatchLength;
                    $iRealTagLength += $iTotalMatchLength - $iFullNameLength;
                    $bHasTags = true;
                }
            }
        }, $sText);

        if ($bStripTags) {
            $sShortenText = strip_tags($sText);
        } else {
            $sShortenText = $sText;
        }

        if ($bHasTags && !$bStripTags) {
            $sShortenText = mb_substr($sText, 0, $iCurrentLength, 'UTF-8');
            if ($iCurrentLength < mb_strlen($sText) && isset($sSuffix) && $sSuffix != '') {
                $sShortenText .= $sSuffix;
            }
        } else {
            $sShortenText = $this->shorten($sShortenText, $iMaxLength, $sSuffix);
        }

        return $sShortenText;
    }

    /**
     * Text we need to parse, usually text added via a <textarea>.
     *
     * @param string $sTxt is the string we need to parse
     * @param bool   $bParseNewLine
     *
     * @return mixed|null|string|string[]
     */
    public function parse($sTxt, $bParseNewLine = true, $aParams = [])
    {
        if (empty($sTxt)) {
            return $sTxt;
        }

        $sTxt = ' ' . $sTxt;

        (($sPlugin = Phpfox_Plugin::get('parse_output_parse')) ? eval($sPlugin) : null);

        // clean phrases
        $sTxt = $this->cleanPhrases($sTxt);

        if (isset($override) && is_callable($override)) {
            $sTxt = call_user_func($override, $sTxt);
        } else if (!$this->allow_html) {
            $sTxt = $this->htmlspecialchars($sTxt);
        } else {
            $sTxt = $this->cleanScriptTag($sTxt);
            $sTxt = $this->cleanStyleTag($sTxt);
            $sTxt = $this->cleanRedundantClosingTags($sTxt);
        }

        $sTxt = Phpfox::getService('ban.word')->clean($sTxt);

        if ($bParseNewLine && !preg_match("/<[^<]+>/", $sTxt, $m)) { // no html
            $sTxt = str_replace("\n", "<div class=\"newline\"></div>", $sTxt);
        } else {
            $sTxt = str_replace("\n\r\n\r", "", $sTxt);
            $sTxt = str_replace("\n\r", "", $sTxt);
        }

        $sTxt = Phpfox::getLib('parse.bbcode')->parse($sTxt);
        $sTxt = $this->parseUrls($sTxt, isset($aParams['parse_url_options']) ? $aParams['parse_url_options'] : []);

        $sTxt = ' ' . $sTxt;

        if ($this->enable_hashtag_support) {
            $sTxt = $this->replaceHashTags($sTxt);
        }

        //support responsive table
        $sTxt = preg_replace("/<table([^\>]*)>/uim", "<div class=\"table-wrapper table-responsive\"><table $1>", $sTxt);
        $sTxt = preg_replace("/<\/table>/uim", "</table></div>", $sTxt);

        $sTxt = $this->replaceUserTag($sTxt);
        $sTxt = $this->replaceObjectMention($sTxt);

        $sTxt = trim($sTxt);

        return $sTxt;
    }

    /**
     * @param $str
     *
     * @return \Api\User\Objects[]
     */
    public function mentionsRegex($str)
    {
        $return = [];
        preg_match_all($this->_regex['mentions'], $str, $matches);
        $users = implode(',', array_unique($matches[1]));
        if ($users) {
            $search = Phpfox_Database::instance()->select('*')->from(':user')->where(['user_id' => ['in' => $users]])->execute('getRows');
            foreach ($search as $user) {
                $return[] = new \Api\User\Objects($user);
            }
        }

        return $return;
    }

    private function _clean($str)
    {
        $str = strip_tags($str);
        $str = str_replace(['"', "'", ' '], '', $str);

        return $str;
    }

    private function _replaceHashTags($aMatches)
    {
        // check user tagged
        preg_match("/\[user=(\d+)\].+?\[\/user\]/iu", $aMatches[0], $aMatcheUserTaggeds);
        if (is_array($aMatcheUserTaggeds) && count($aMatcheUserTaggeds)) {
            return $aMatches[0];
        }
        $sHashTag = $aMatches[0];
        $sApostrophe = preg_replace("/" . $this->_regex['hash_tags'] . "/iu", '', $sHashTag);

        if ($sApostrophe) {
            $sHashTag = str_replace($sApostrophe, '', $sHashTag);
        }
        $sTagSearch = substr_replace(strip_tags($sHashTag), '', 0, 1);
        $sTagSearch = preg_replace('/\[UNICODE\]([0-9]+)\[\/UNICODE\]/', '&#\\1;', $sTagSearch);
        $sTagSearch = html_entity_decode($sTagSearch, null, 'UTF-8');
        $sTagSearch = urlencode($sTagSearch);

        $sTxt = '<a href="' . Phpfox_Url::instance()->makeUrl('hashtag', [$sTagSearch]) . '" class="site_hash_tag">' . strip_tags($sHashTag) . '</a>';

        if ($sApostrophe) {
            $sTxt = '<span class="content_hash_tag">' . $sTxt . $sApostrophe . '</span>'; // add span element to fix apostrophe breaks
        }

        return $sTxt;
    }

    private function _replaceHexColor($aMatches)
    {
        // after change to check whether "color" is in the string or not, this if is incorrect
        if (strlen($aMatches[2]) == 3) {
            $r = hexdec(substr($aMatches[2], 0, 1) . substr($aMatches[2], 0, 1));
            $g = hexdec(substr($aMatches[2], 1, 1) . substr($aMatches[2], 1, 1));
            $b = hexdec(substr($aMatches[2], 2, 1) . substr($aMatches[2], 2, 1));
        } else {
            $r = hexdec(substr($aMatches[2], 0, 2));
            $g = hexdec(substr($aMatches[2], 2, 2));
            $b = hexdec(substr($aMatches[2], 4, 2));
        }
        $sRGB = "rgb(" . $r . "," . $g . "," . $b . ")";

        return $aMatches[1] . $sRGB;
    }

    public function _replaceUnicode($aMatches)
    {
        return '[UNICODE]' . (int)str_replace(['&#', ';'], '', $aMatches[0]) . '[/UNICODE]';
    }

    public function cleanTag($sTxt)
    {
        $sTxt = strip_tags($sTxt);
        return $sTxt;
    }

    public function cleanScriptTag($sTxt)
    {
        $sTxt = preg_replace("/<script([^\>]*)>/uim", "&lt;script$1&gt;", $sTxt);
        $sTxt = preg_replace("/<\/script>/uim", "&lt;/script$1&gt;", $sTxt);

        return $sTxt;
    }

    public function cleanStyleTag($sTxt)
    {
        $sTxt = preg_replace("/<style([^\>]*)>/uim", "&lt;style$1&gt;", $sTxt);
        $sTxt = preg_replace("/<\/style>/uim", "&lt;/style$1&gt;", $sTxt);

        return $sTxt;
    }

    public function cleanRedundantClosingTags($sTxt)
    {
        $aTags = ['p', 'div'];

        foreach ($aTags as $tag) {
            $aOpenTagPositions = $this->_getOpeningTagsOffset($sTxt, $tag);
            $aClosingTagPositions = $this->_getClosingTagsOffset($sTxt, $tag);

            if (count($aClosingTagPositions) <= count($aOpenTagPositions)) {
                continue;
            }

            $index = -1;
            foreach (array_reverse($aClosingTagPositions) as $closingTagPosition) {
                $index++;
                if (isset($aOpenTagPositions[$index])) {
                    continue;
                }

                $sTxt = substr_replace($sTxt, '', $closingTagPosition, strlen("</$tag>"));
            }
        }

        return $sTxt;
    }

    private function _getOpeningTagsOffset($sTxt, $sTag)
    {
        preg_match_all('/<' . $sTag . '[^>]*>/i', $sTxt, $aMatches, PREG_OFFSET_CAPTURE);

        if (!empty($aMatches[0])) {
            return array_column($aMatches[0], 1);
        }

        return [];
    }

    private function _getClosingTagsOffset($sTxt, $sTag)
    {
        preg_match_all('/<\/' . $sTag . '>/i', $sTxt, $aMatches, PREG_OFFSET_CAPTURE);

        if (!empty($aMatches[0])) {
            return array_column($aMatches[0], 1);
        }

        return [];
    }

    public function replaceHashTags($sTxt)
    {
        $sTxt = preg_replace_callback("/<a.*?<\/a>(*SKIP)(*F)|(&#+[0-9+]+;)/", [$this, '_replaceUnicode'], $sTxt);
        $sTxt = preg_replace_callback("/<a.*?<\/a>(*SKIP)(*F)|(\-?color:)\s*#([A-F0-9]{3,6})/i", [$this, '_replaceHexColor'], $sTxt);
        $sTxt = preg_replace('/\[UNICODE\]39\[\/UNICODE\]/', '\'', $sTxt); // convert &#39; to '
        $sTxt = preg_replace_callback("/<a.*?<\/a>(*SKIP)(*F)|" . $this->_regex['hash_tags'] . "/iu", [$this, '_replaceHashTags'], $sTxt);
        $sTxt = preg_replace('/\'/', '&#39;', $sTxt); // convert ' to &#39;
        $sTxt = preg_replace('/\[UNICODE\]([0-9]+)\[\/UNICODE\]/', '&#\\1;', $sTxt);
        return $sTxt;
    }

    public function getHashTags($sTxt)
    {
        $aTags = [];
        $sTxt = str_replace(['<br >', '<br />', '<p>', '</p>'], ' ', $sTxt);

        $sTxt = preg_replace_callback("/(&#+[0-9+]+;)/", [$this, '_replaceUnicode'], $sTxt);

        $sTxt = preg_replace("/#([A-F0-9]{6})/i", "", $sTxt);
        $sTxt = preg_replace("/(http[s]?:\/\/(www\.)?|ftp:\/\/(www\.)?|www\.){1}([0-9A-Za-z-\-\.@:%_\+~#=]+)+((\.[a-zA-Z]{2,3})+)(\/([0-9A-Za-z-\-\.@:%_\+~#=\?])*)*/i",
            "", $sTxt);

        preg_match_all("/" . $this->_regex['hash_tags'] . "/iu", $sTxt, $aMatches);

        if (is_array($aMatches) && count($aMatches)) {
            foreach ($aMatches as $aSubTags) {
                foreach ($aSubTags as $sTag) {
                    $sTag = preg_replace('/\[UNICODE\]([0-9]+)\[\/UNICODE\]/', '&#\\1;', $sTag);

                    // check user tagged
                    preg_match("/\[user=(\d+)\].+?\[\/user\]/iu", $sTag, $aMatcheUserTaggeds);
                    if (is_array($aMatcheUserTaggeds) && count($aMatcheUserTaggeds)) {
                        continue;
                    }

                    $aTags[] = substr_replace($sTag, '', 0, 1);
                }

                break;
            }
        }

        return $aTags;
    }

    public function parseUrls($sTxt, $options = [])
    {
        // check and remove external email
        if ($this->disable_all_external_emails) {
            $sTxt = preg_replace_callback('/([\s>])*([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})/i', [&$this, '_replaceEmails'], $sTxt);
        }

        // convert url string to link (tag <a/>)
        $linkify = Phpfox::getLib('parse.linkify');
        $sTxt = $linkify->process($sTxt, $options);

        // check and remove link if is external url
        if ($this->disable_all_external_urls) {
            $sTxt = preg_replace_callback('/<a([^>]*) href="([^"]*)"([^>]*)>/i', [&$this, '_replaceLinks'], $sTxt);
        }

        // check and add class external_warning
        if ($this->warn_on_external_links) {
            $sTxt = preg_replace_callback('/<a\s(.*?)>/i', [&$this, '_warnOnExtLink'], $sTxt);
        }

        // check external image src
        $sTxt = preg_replace_callback('/<img([^>]*) src="([^"]*)"([^>]*)>/i', [&$this, '_secureImageUrls'], $sTxt);

        // check and update external url
        $sTxt = preg_replace_callback('/<a([^>]*) href="([^"]*)"([^>]*)>/i', [&$this, '_updateExternalLinks'], $sTxt);
        $sTxt = preg_replace("/(<a( [^>]+?>|>))<a [^>]+?>([^>]+?)<\/a><\/a>/i", "$1$3</a>", $sTxt);
        $sTxt = trim($sTxt);

        return $sTxt;
    }

    private function _secureImageUrls($aMatches)
    {
        $imageUrl = $aMatches[2];
        $isExternal = preg_match('/' . preg_quote(Phpfox::getParam('core.host')) . '/i', $imageUrl) ? false : true;
        if ($isExternal) {
            $imageUrl = Phpfox::getLib('url')->secureUrl($imageUrl);
        }

        return strtr("<img {attributes} src=\"{src}\" {attributes2}>", [
            '{attributes}'  => $aMatches[1],
            '{src}'         => $imageUrl,
            '{attributes2}' => $aMatches[3],
        ]);
    }

    private function _warnOnExtLink($aMatches)
    {
        if (!empty($aMatches[1]) && strpos($aMatches[1], 'href="#"') !== false) {
            return '<a ' . $aMatches[1] . '>';
        }

        if (!isset($aMatches[1])) {
            return '';
        }

        $aParts = explode(' ', $aMatches[1]);
        foreach ($aParts as $sPart) {
            if (substr($sPart, 0, 5) == 'href=' && strpos($sPart, 'mailto:') === false) {
                $isExternal = preg_match('/' . preg_quote(Phpfox::getParam('core.host')) . '/i', $sPart) ? false : true;
                if ($isExternal) {
                    if (strpos($aMatches[1], 'class="')) {
                        $aMatches[1] = str_replace('class="', 'class="external_link_warning ', $aMatches[1]);
                        return '<a ' . $aMatches[1] . '>';
                    } else {
                        return '<a class="external_link_warning" ' . $aMatches[1] . '>';
                    }
                } else {
                    return '<a ' . $aMatches[1] . '>';
                }
            }
        }
        return '<a ' . $aMatches[1] . '>';
    }

    public function parseUserTagged($iUser)
    {
        return $this->_parseUserTagged($iUser);
    }

    /**
     * Parses users from tags by querying the DB and getting their full name.
     *
     * @param $iUser
     *
     * @return mixed|string
     */
    private function _parseUserTagged($iUser)
    {
        $sName = '';
        if (is_array($iUser)) {
            $sName = isset($iUser[2]) ? $iUser[2] : _p('unknown_user');
            $iUser = $iUser[1];
        }

        static $aCache = [];

        if (!isset($aCache[$iUser])) {
            $oDb = Phpfox_Database::instance();

            $aUser = $oDb->select('up.user_value, u.full_name, user_name')
                ->from(Phpfox::getT('user'), 'u')
                ->leftJoin(Phpfox::getT('user_privacy'), 'up',
                    'up.user_id = u.user_id AND up.user_privacy = \'user.can_i_be_tagged\'')
                ->where('u.user_id = ' . (int)$iUser)
                ->execute('getSlaveRow');
            $sOut = '';
            if (empty($aUser)) {
                $sOut = $sName;
            } else {
                $aUser['full_name'] = Phpfox::getLib('parse.output')->clean($aUser['full_name']);
                if (isset($aUser['user_value']) && !empty($aUser['user_value']) && $aUser['user_value'] > 2) {
                    $sOut = $aUser['full_name'];
                } else {
                    if (isset($aUser['user_name'])) {
                        $sOut = '<span class="user_profile_link_span" id="js_user_name_link_' . $aUser['user_name'] . '"><a class="status_user_tag" id="' . (int)$iUser . '" href="' . Phpfox_Url::instance()->makeUrl($aUser['user_name']) . '">' . $aUser['full_name'] . '</a></span>';
                    }
                }
            }

            $aCache[$iUser] = $sOut;

            return $sOut;
        } else {
            return $aCache[$iUser];
        }
    }

    private function _parsePageMentioned($iPageId)
    {
        $sName = '';
        if (is_array($iPageId)) {
            $sName = isset($iPageId[2]) ? $iPageId[2] : _p('unknown_page');
            $iPageId = $iPageId[1];
        }
        $sOut = '';
        if (!Phpfox::isAppActive('Core_Pages')) {
            return $sOut;
        }

        $oService = Phpfox::getService('pages');
        $aPage = $oService->getPage($iPageId);
        if (empty($aPage['page_id'])) {
            $sOut = $sName;
        } else {
            $sUrl = $oService->getUrl($iPageId, $aPage['title'], $aPage['vanity_url']);
            $iUserId = $oService->getUserId($iPageId);
            if (isset($aPage['title'])) {
                $sOut = '<span class="user_profile_link_span" id="js_user_name_link_' . $iUserId . '"><a class="status_user_tag" href="' . $sUrl . '">' . $aPage['title'] . '</a></span>';
            }
        }
        return $sOut;
    }

    private function _parseGroupMentioned($iGroupId)
    {
        $sName = '';
        if (is_array($iGroupId)) {
            $sName = isset($iGroupId[2]) ? $iGroupId[2] : _p('unknown_group');
            $iGroupId = $iGroupId[1];
        }

        $sOut = '';
        if (!Phpfox::isAppActive('PHPfox_Groups')) {
            return $sOut;
        }

        $oService = Phpfox::getService('groups');
        $aGroup = $oService->getPage($iGroupId);
        if(empty($aGroup['page_id'])) {
            $sOut = $sName;
        } else {
            $sUrl = $oService->getUrl($iGroupId, $aGroup['title'], $aGroup['vanity_url']);
            if (isset($aGroup['title'])) {
                if ($aGroup['reg_method'] != 2
                    || Phpfox::isAdmin()
                    || !empty($aGroup['is_liked'])
                    || Phpfox::getService('groups')->getPageOwnerId($iGroupId) == Phpfox::getUserId()
                    || Phpfox::getService('groups')->checkCurrentUserInvited($iGroupId)) {
                    $sOut = '<span class="user_profile_link_span" id="js_user_name_link_' . $oService->getUserId($iGroupId) . '"><a class="status_user_tag" href="' . $sUrl . '">' . $aGroup['title'] . '</a></span>';
                } else {
                    $sOut = $aGroup['title'];
                }
            }
        }
        return $sOut;
    }

    public function feedStrip($sStr, $bParseNewLine = false)
    {
        return $this->parse(strip_tags($sStr), $bParseNewLine);
    }

    public function replaceUserTag($sStr)
    {
        $sStr = preg_replace_callback('/\[user=(\d+)\](.+?)\[\/user\]/u', [$this, '_parseUserTagged'], $sStr);

        return $sStr;
    }


    public function replaceObjectMention($sStr)
    {
        // Parse groups/pages mentions
        $sStr = preg_replace_callback('/\[group=(\d+)\](.+?)\[\/group\]/u', [$this, '_parseGroupMentioned'], $sStr);
        $sStr = preg_replace_callback('/\[page=(\d+)\](.+?)\[\/page\]/u', [$this, '_parsePageMentioned'], $sStr);

        return $sStr;
    }

    /**
     * Set image parser settings.
     *
     * @param array $aParams ARRAY of settings.
     */
    public function setImageParser($aParams)
    {
        if (isset($aParams['clear'])) {
            $this->_aImageParams = [];
        } else {
            $this->_aImageParams = $aParams;
        }
    }

    /**
     * @param array $aParams ARRAY of settings.
     *
     * @deprecated 4.7.0
     *             Set video embed settings.
     *
     */
    public function setEmbedParser($aParams = null)
    {
        return true;
    }

    /**
     * Clean input text, usually used within HTML <input>
     *
     * @param string $sTxt      is the string we need to clean
     * @param bool   $bHtmlChar TRUE to convert HTML characters or FALSE to not convert.
     *
     * @return string Cleaned string
     */
    public function clean($sTxt, $bHtmlChar = true)
    {
        if (!is_string($sTxt)) {
            $sTxt = '';
        }
        if (!defined('PHPFOX_INSTALLER')) {
            $sTxt = Phpfox::getService('ban.word')->clean($sTxt);
        }
        $sTxt = ($bHtmlChar ? $this->htmlspecialchars($sTxt) : $sTxt);
        $sTxt = str_replace('&#160;', '', $sTxt);
        $sTxt = htmlspecialchars_decode(htmlspecialchars($sTxt, ENT_SUBSTITUTE, 'UTF-8'));
        $sTxt = str_replace('�', '', $sTxt);

        return $sTxt;
    }

    public function cleanPhrases($sTxt)
    {
        $sTxt = str_replace(['&#39;', '&#039;', '\''], '[PHPFOX_QUOTES]', $sTxt);
        $sTxt = preg_replace_callback('/\[PHPFOX_PHRASE\](.*?)\[\/PHPFOX_PHRASE\]/i', [$this, '_getPhrase'], $sTxt);
        $sTxt = preg_replace_callback('/\{phrase var=\[PHPFOX_QUOTES\](.*)\[PHPFOX_QUOTES\]\}/i', [$this, '_getPhrase'], $sTxt);
        $sTxt = preg_replace_callback('/\{_p var=\[PHPFOX_QUOTES\](.*)\[PHPFOX_QUOTES\]\}/i', [$this, '_getPhrase'], $sTxt);
        $sTxt = str_replace('[PHPFOX_QUOTES]', '&#039;', $sTxt);
        return $sTxt;
    }

    /**
     * Our method of PHP htmlspecialchars().
     *
     * @param string $sTxt String to convert.
     *
     * @return string Converted string.
     * @see htmlspecialchars()
     */
    public function htmlspecialchars($sTxt)
    {
        $sTxt = preg_replace('/&(?!(#[0-9]+|[a-z]+);)/si', '&amp;', $sTxt);
        $sTxt = str_replace([
            '"',
            "'",
            '<',
            '>'
        ],
            [
                '&quot;',
                '&#039;',
                '&lt;',
                '&gt;'
            ], $sTxt);

        return $sTxt;
    }


    /**
     * Clean text when being sent back via AJAX.
     * Usually this is used to send back to an HTML <textarea>
     *
     * @param string $sTxt is the text we need to clean
     *
     * @return string Cleaned Text
     */
    public function ajax($sTxt)
    {
        $sTxt = Phpfox::getService('ban.word')->clean($sTxt);
        $sTxt = str_replace("\r", "", $sTxt);

        return $sTxt;
    }

    public function truncate($text, $length)
    {
        $html = true;
        $ending = '';
        $exact = true;
        $openTags = [];

        if ($html) {
            if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
                return $text;
            }
            $totalLength = mb_strlen(strip_tags($ending));
            $truncate = '';

            preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
            foreach ($tags as $tag) {
                if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2])) {
                    if (preg_match('/<[\w]+[^>]*>/s', $tag[0])) {
                        array_unshift($openTags, $tag[2]);
                    } else {
                        if (preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag)) {
                            $pos = array_search($closeTag[1], $openTags);
                            if ($pos !== false) {
                                array_splice($openTags, $pos, 1);
                            }
                        }
                    }
                }
                $truncate .= $tag[1];

                $contentLength = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ',
                    $tag[3]));
                if ($contentLength + $totalLength > $length) {
                    $left = $length - $totalLength;
                    $entitiesLength = 0;
                    if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities,
                        PREG_OFFSET_CAPTURE)) {
                        foreach ($entities[0] as $entity) {
                            if ($entity[1] + 1 - $entitiesLength <= $left) {
                                $left--;
                                $entitiesLength += mb_strlen($entity[0]);
                            } else {
                                break;
                            }
                        }
                    }

                    $truncate .= mb_substr($tag[3], 0, $left + $entitiesLength);
                    break;
                } else {
                    $truncate .= $tag[3];
                    $totalLength += $contentLength;
                }
                if ($totalLength >= $length) {
                    break;
                }
            }
        } else {
            if (mb_strlen($text) <= $length) {
                return $text;
            } else {
                $truncate = mb_substr($text, 0, $length - mb_strlen($ending));
            }
        }
        if (!$exact) {
            $spacepos = mb_strrpos($truncate, ' ');
            if (isset($spacepos)) {
                if ($html) {
                    $bits = mb_substr($truncate, $spacepos);
                    preg_match_all('/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER);
                    if (!empty($droppedTags)) {
                        foreach ($droppedTags as $closingTag) {
                            if (!in_array($closingTag[1], $openTags)) {
                                array_unshift($openTags, $closingTag[1]);
                            }
                        }
                    }
                }
                $truncate = mb_substr($truncate, 0, $spacepos);
            }
        }
        $truncate .= $ending;

        if ($html) {
            foreach ($openTags as $tag) {
                $truncate .= '</' . $tag . '>';
            }
        }

        return $truncate;
    }

    public function appendSuffixForShorten($shortenHtml, $html, $suffix, $maxLength, $hide = false)
    {
        if (!$shortenHtml || !$html || !$suffix || !$maxLength) {
            return (string)$shortenHtml;
        }

        $countHtml = strip_tags($html);
        $countHtml = preg_replace('/&#?[a-zA-Z0-9]+;/i', 'A', $countHtml);

        $lines = explode("\n", $shortenHtml);
        $numLine = count($lines);

        if (mb_strlen($countHtml) > $maxLength || $numLine > 5) {
            $suffix = _p($suffix);
            $this->_bIsShortened = true;

            if ($numLine > 5) {
                $shortenHtml = implode("\n", array_slice($lines, 0, 5, true));
            }

            if ($hide === true) {
                if (defined('PHPFOX_IS_THEATER_MODE')) {
                    $shortenHtml = '<span class="js_view_more_parent"><span class="js_view_more_part">' . $this->closeAllHtmlTags($shortenHtml) . '...&nbsp;<span class="item_view_more"><a href="#" onclick="$(this).parents(\'.js_view_more_parent:first\').find(\'.js_view_more_part\').hide(); $(this).parents(\'.js_view_more_parent:first\').find(\'.js_view_more_full\').show(); return false;">' . $suffix . '</a></span></span>';
                    $shortenHtml .= '<span class="js_view_more_full" style="display:none; position:absolute; z-index:10000; background:#fff; border:1px #dfdfdf solid;">';
                    $shortenHtml .= '<div style="max-height:200px; overflow:auto; padding:5px;">' . $html . '</div>';
                    $shortenHtml .= '<div class="item_view_more" style="padding:10px; text-align:center;"><a href="#" onclick="$(this).parents(\'.js_view_more_parent:first\').find(\'.js_view_more_full\').hide(); $(this).parents(\'.js_view_more_parent:first\').find(\'.js_view_more_part\').show(); return false;">' . _p('view_less') . '</a></div>';
                    $shortenHtml .= '</span>';
                    $shortenHtml .= '</span>';
                } else {
                    $shortenHtml = '<span class="js_view_more_parent"><span class="js_view_more_part">' . $this->closeAllHtmlTags($shortenHtml) . '...&nbsp;<span class="item_view_more"><a href="#" onclick="$(this).parents(\'.js_view_more_parent:first\').find(\'.js_view_more_part\').hide(); $(this).parents(\'.js_view_more_parent:first\').find(\'.js_view_more_full\').show(); return false;">' . $suffix . '</a></span></span>';
                    $shortenHtml .= '<span class="js_view_more_full" style="display:none;">';
                    $shortenHtml .= $html;
                    $shortenHtml .= '<div class="item_view_more"><a href="#" onclick="$(this).parents(\'.js_view_more_parent:first\').find(\'.js_view_more_full\').hide(); $(this).parents(\'.js_view_more_parent:first\').find(\'.js_view_more_part\').show(); return false;">' . _p('view_less') . '</a></div>';
                    $shortenHtml .= '</span>';
                    $shortenHtml .= '</span>';
                }
            } else {
                $shortenHtml .= $suffix;
            }
        }

        return $shortenHtml;
    }

    /**
     * Shortens a string.
     *
     * @param string $html      String to shorten.
     * @param int    $maxLength Max length.
     * @param string $sSuffix   Optional suffix to add.
     * @param bool   $bHide     TRUE to hide the shortened string, FALSE to remove it.
     *
     * @return string Returns the new shortened string.
     */
    public function shorten($html, $maxLength, $sSuffix = null, $bHide = false)
    {
        $html = $this->cleanPhrases($html);

        mb_internal_encoding('UTF-8');
        if (defined('PHPFOX_LANGUAGE_SHORTEN_BYPASS') || $maxLength === 0 || $this->hasReachedMaxLine()) {
            return $html;
        }

        $sNewString = $this->truncate($html, $maxLength);

        if ($sSuffix !== null) {
            $sNewString = $this->appendSuffixForShorten($sNewString, $html, $sSuffix, $maxLength, $bHide);
        }

        return $sNewString;
    }

    /**
     * Return if the last string we checked was shortened.
     *
     * @return bool TRUE it was shortened, FALSE if was not.
     */
    public function isShortened()
    {
        $bLastCheck = $this->_bIsShortened;

        $this->_bIsShortened = false;

        return $bLastCheck;
    }

    /**
     * Return if the last string we checked reached the max number of lines.
     *
     * @return bool TRUE it was reached, FALSE if was not.
     */
    public function hasReachedMaxLine()
    {
        $bLastCheck = $this->_bIsMaxLine;

        $this->_bIsMaxLine = false;

        return $bLastCheck;
    }

    /**
     * Split a string at a specified location. This allows for browsers to
     * automatically add breaks or wrap long text strings.
     *
     * @param string $sString Text string you want to split.
     * @param int    $iCount  How many characters to wait until we need to perform the split.
     * @param bool   $bBreak  FALSE will just add a space and TRUE will add an HTML <br />.
     *
     * @return string Converted string with splits included.
     */
    public function split($sString, $iCount, $bBreak = false)
    {
        if ($sString == '0') {
            return $sString;
        }
        $sNewString = '';
        $aString = explode('>', $sString);
        $iSizeOf = sizeof($aString);
        $bHasNonAscii = false;
        for ($i = 0; $i < $iSizeOf; ++$i) {
            $aChar = explode('<', $aString[$i]);

            if (!empty($aChar[0])) {
                if (preg_match('/&#?[a-zA-Z0-9]+;/', $aChar[0])) {
                    $aChar[0] = str_replace('&lt;', '[PHPFOX_START]', $aChar[0]);
                    $aChar[0] = str_replace('&gt;', '[PHPFOX_END]', $aChar[0]);
                    $aChar[0] = html_entity_decode($aChar[0], null, 'UTF-8');

                    $bHasNonAscii = true;
                }
                if ($iCount > 9999) {
                    $iCount = 9999;
                }
                $sNewString .= preg_replace('#([^\n\r(?!PHPFOX_) ]{' . $iCount . '})#iu',
                    '\\1 ' . ($bBreak ? '<br />' : ''), $aChar[0]);
            }

            if (!empty($aChar[1])) {
                $sNewString .= '<' . $aChar[1] . '>';
            }
        }

        return ($bHasNonAscii === true ? str_replace(['[PHPFOX_START]', '[PHPFOX_END]'], ['&lt;', '&gt;'],
            Phpfox::getLib('parse.input')->convert($sNewString)) : $sNewString);
    }

    public function closeAllHtmlTags($html)
    {

        #put all opened tags into an array
        preg_match_all('#<([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $html, $result);
        $openedtags = $result[1];   #put all closed tags into an array

        preg_match_all('#</([a-z]+)>#iU', $html, $result);

        $closedtags = $result[1];

        $len_opened = count($openedtags);

        # all tags are closed
        if (count($closedtags) == $len_opened) {
            return $html;
        }

        $openedtags = array_reverse($openedtags);

        # close tags
        for ($i = 0; $i < $len_opened; $i++) {

            if (!in_array($openedtags[$i], $closedtags)) {

                $html .= '</' . $openedtags[$i] . '>';

            } else {

                unset($closedtags[array_search($openedtags[$i], $closedtags)]);
            }
        }

        return $html;
    }

    /**
     * Replace unwanted emails on the site. We also take into account emails
     * that are added into the "white" list.
     *
     * @param array $aMatches ARRAY matches from preg_match.
     *
     * @return string Returns replaced emails.
     */
    private function _replaceEmails($aMatches)
    {
        static $aEmails = null;

        if ($aEmails === null) {
            $aEmails = explode(',', trim(Phpfox::getParam('core.email_white_list')));
        }

        foreach ($aEmails as $sEmail) {
            $sEmail = trim($sEmail);
            $sEmail = str_replace(['.', '*'], ['\.', '(.*?)'], $sEmail);

            if (!empty($sEmail) && preg_match('/' . $sEmail . '/is', $aMatches[0])) {
                return $aMatches[0];
            }
        }
    }

    /**
     * check and update external link attributes
     *
     * @param array $aMatches ARRAY matches from preg_match.
     *
     * @return string Returns replaced links.
     */
    private function _updateExternalLinks($aMatches)
    {
        $sSite = trim(Phpfox::getParam('core.host'));
        $str = "<a {attributes} href=\"{href}\" {attributes2}>";
        if (!preg_match('/' . str_replace('/', '\/', $sSite) . '/is', $aMatches[2]) && strpos($aMatches[2], 'mailto:') === false) {
            $str = "<a {attributes} href=\"{href}\" {attributes2} target='_blank'>"; // is external
            if ($this->no_follow_on_external_links) {
                $str = "<a {attributes} href=\"{href}\" {attributes2} target='_blank' rel='nofollow'>"; // is external and no follow
            }
        }
        return strtr($str, [
            '{attributes}'  => $aMatches[1],
            '{href}'        => $aMatches[2],
            '{attributes2}' => $aMatches[3],
        ]);
    }

    /**
     * Replace unwanted links on the site. We also take into account links
     * that are added into the "white" list.
     *
     * @param array $aMatches ARRAY matches from preg_match.
     *
     * @return string Returns replaced links.
     */
    private function _replaceLinks($aMatches)
    {
        static $aSites = null;

        if ($aSites === null) {
            $aSites = explode(',',
                trim(Phpfox::getParam('core.url_spam_white_list')) . ',' . Phpfox::getParam('core.host'));
        }

        // process url in href attribute
        foreach ($aSites as $sSite) {
            $sSite = trim($sSite);
            $sSite = str_replace(['.', '*'], ['\.', '(.*?)'], $sSite);

            if (!empty($sSite) && (preg_match('/' . str_replace('/', '\/', $sSite) . '/is', $aMatches[2]) || strpos($aMatches[2], 'mailto:') !== false)) {
                $href = $aMatches[2];
            }
        }

        if (!isset($href)) {
            return ''; // only show href content
        }

        return strtr("<a {attributes} href=\"{href}\" {attributes2}>", [
            '{attributes}'  => $aMatches[1],
            '{href}'        => $href,
            '{attributes2}' => $aMatches[3],
        ]);
    }

    /**
     * Converts a URL into a HTML anchor.
     *
     * @param array $aMatches ARRAY matches from preg_match.
     *
     * @return string Converted URL.
     */
    private function _urlToLink($aMatches)
    {
        $aMatches[0] = trim($aMatches[0]);

        if (empty($aMatches[0])) {
            return '';
        }

        $sHref = $aMatches[0];

        if ($sHref == 'ftp.') {
            return ' ' . $sHref;
        }

        if (!preg_match("/^(http|https|ftp):\/\/(.*?)$/i", $sHref)) {
            $sHref = 'http://' . $sHref;
        }

        $sName = $aMatches[0];
        if (Phpfox::getParam('core.shorten_parsed_url_links') > 0) {
            $sName = substr($sName, 0,
                    Phpfox::getParam('core.shorten_parsed_url_links')) . (strlen($sName) > Phpfox::getParam('core.shorten_parsed_url_links') ? '...' : '');
        }

        return strtr("<a href=\":href\" target=\"_blank\" :nofollow>:name</a>", [
            ':href'     => $sHref,
            ':nofollow' => Phpfox::getParam('core.no_follow_on_external_links') ? 'rel="nofollow"' : '',
            ':name'     => $sName
        ]);
    }

    /**
     * Gets a phrase from the language package.
     *
     * @param string $aMatches ARRAY matches from preg_match.
     *
     * @return string Returns the phrase if we can find it.
     */
    private function _getPhrase($aMatches)
    {
        return (isset($aMatches[1]) ? _p($aMatches[1]) : $aMatches[0]);
    }

    /**
     * Fixes image widths.
     *
     * @param array $aMatches ARRAY of matches from a preg_match.
     *
     * @return string Returns the image with max-width and max-height included.
     */
    private function _fixImageWidth($aMatches)
    {
        $aParts = Phpfox::getLib('parse.input')->getParams($aMatches[1]);
        $iWidth = (isset($this->_aImageParams['width']) ? (int)$this->_aImageParams['width'] : 400);
        $iHeight = (isset($this->_aImageParams['height']) ? (int)$this->_aImageParams['height'] : 400);

        (($sPlugin = Phpfox_Plugin::get('parse_output_fiximagewidth')) ? eval($sPlugin) : false);

        return '<img style="max-width:' . $iWidth . 'px; max-height:' . $iHeight . '" ' . $aMatches[1] . '>';
    }
}