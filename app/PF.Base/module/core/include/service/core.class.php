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
 * @package          Phpfox_Service
 * @version          $Id: core.class.php 7272 2014-04-15 13:25:27Z Fern $
 */
class Core_Service_Core extends Phpfox_Service
{
    /**
     * @var array
     */
    private $timezones;

    /**
     * Class constructor
     */
    public function __construct()
    {
    }

    /**
     * Get All Timezone Settings
     * @return mixed
     */
    public function getTimezoneSettings()
    {
        return get_from_cache('admincp_timezone_settings', function () {
            $aSettings = db()->select('*')
                ->from(Phpfox::getT('timezone_setting'))
                ->execute('getSlaveRows');
            return $aSettings;
        });
    }

    /**
     * Get Disabled Timezone Settings
     * @return mixed
     */
    public function getDisabledTimezones()
    {
        return get_from_cache('admincp_disabled_timezones_settings', function () {
            $aDisabledTimezones = db()->select('timezone_key, disable')
                ->from(Phpfox::getT('timezone_setting'))
                ->where('disable = 1')
                ->execute('getSlaveRows');
            if (!empty($aDisabledTimezones)) {
                $aParsed = array_combine(array_column($aDisabledTimezones, 'timezone_key'), array_column($aDisabledTimezones, 'disable'));
                return $aParsed;
            }
            return [];
        });
    }


    /**
     * This function returns an array where the indexes are the names of the
     * active modules and the keys are the names of the blocks.
     * It is firstly used in the theme.addDnDBlock block
     *
     * @return array
     */
    public function getBlocksByModule()
    {
        $aBlocks = $this->database()->select('component, module_id')
            ->from(Phpfox::getT('component'))
            ->where('is_active = 1 AND is_block = 1')
            ->order('module_id ASC')
            ->execute('getSlaveRows');
        $aOut = [];
        foreach ($aBlocks as $aBlock) {
            $aOut[$aBlock['module_id']][] = $aBlock['component'];
        }

        return $aOut;
    }

    /**
     * @param bool $bReturnArray
     * @param bool $bForceGenerate
     *
     * @return array|bool|mixed
     * @throws Exception
     */
    public function generateTimeZones($bReturnArray = false, $bForceGenerate = false)
    {
        $sPathFile = PHPFOX_DIR_SETTINGS . 'timezones.sett.php';
        if (file_exists($sPathFile) && !$bForceGenerate) {
            return ($bReturnArray ? $this->getTimeZones(true) : true);
        }
        if (PHPFOX_USE_DATE_TIME) {
            $aTimeZones = DateTimeZone::listIdentifiers();
            sort($aTimeZones);
            foreach ($aTimeZones as $iKey => $sTimeZone) {
                $aTimeZones['z' . $iKey] = $sTimeZone;
                unset($aTimeZones[$iKey]);
            }
        } else {
            $aTimeZones = [
                '-12'  => '(GMT -12:00) Eniwetok, Kwajalein',
                '-11'  => '(GMT -11:00) Midway Island, Samoa',
                '-10'  => '(GMT -10:00) Hawaii',
                '-9'   => '(GMT -9:00) Alaska',
                '-8'   => '(GMT -8:00) Pacific Time (US &amp; Canada)',
                '-7'   => '(GMT -7:00) Mountain Time (US &amp; Canada)',
                '-6'   => '(GMT -6:00) Central Time (US &amp; Canada), Mexico City',
                '-5'   => '(GMT -5:00) Eastern Time (US &amp; Canada), Bogota, Lima',
                '-4.5' => '(GMT -4:30) Caracas',
                '-4'   => '(GMT -4:00) Atlantic Time (Canada), La Paz, Santiago',
                '-3.5' => '(GMT -3:30) Newfoundland',
                '-3'   => '(GMT -3:00) Brazil, Buenos Aires, Georgetown',
                '-2'   => '(GMT -2:00) Mid-Atlantic',
                '-1'   => '(GMT -1:00 hour) Azores, Cape Verde Islands',
                '0'    => '(GMT) Western Europe Time, London, Lisbon, Casablanca',
                '1'    => '(GMT +1:00 hour) Brussels, Copenhagen, Madrid, Paris',
                '2'    => '(GMT +2:00) Kaliningrad, South Africa',
                '3'    => '(GMT +3:00) Baghdad, Riyadh, Moscow, St. Petersburg',
                '3.5'  => '(GMT +3:30) Tehran',
                '4'    => '(GMT +4:00) Abu Dhabi, Muscat, Baku, Tbilisi',
                '4.5'  => '(GMT +4:30) Kabul',
                '5'    => '(GMT +5:00) Ekaterinburg, Islamabad, Karachi, Tashkent',
                '5.5'  => '(GMT +5:30) Bombay, Calcutta, Madras, New Delhi',
                '5.75' => '(GMT +5:45) Kathmandu',
                '6'    => '(GMT +6:00) Almaty, Dhaka, Colombo',
                '6.5'  => '(GMT +6:30) Yangon, Cocos Islands',
                '7'    => '(GMT +7:00) Bangkok, Hanoi, Jakarta',
                '8'    => '(GMT +8:00) Beijing, Perth, Singapore, Hong Kong',
                '9'    => '(GMT +9:00) Tokyo, Seoul, Osaka, Sapporo, Yakutsk',
                '9.5'  => '(GMT +9:30) Adelaide, Darwin',
                '10'   => '(GMT +10:00) Eastern Australia, Guam, Vladivostok',
                '11'   => '(GMT +11:00) Magadan, Solomon Islands, New Caledonia',
                '12'   => '(GMT +12:00) Auckland, Wellington, Fiji, Kamchatka'
            ];
        }

        file_put_contents($sPathFile, "<?php\n return " . var_export($aTimeZones, true) . ";\n");

        return ($bReturnArray ? $aTimeZones : true);
    }

    /**
     * @param bool $getAll
     * @param bool $getInForm
     *
     * @return array|bool|mixed
     * @throws Exception
     */
    public function getTimeZones($getAll = false, $getInForm = true)
    {
        if (!$this->timezones) {
            $sPathFile = PHPFOX_DIR_SETTINGS . 'timezones.sett.php';
            if (file_exists($sPathFile)) {
                $this->timezones = require($sPathFile);
            }
            if (empty($this->timezones)) {
                $this->timezones = $this->generateTimeZones(true);
            }
        }
        $timezones = $this->timezones;

        // check and get GMT for DateTimeZone
        if (count($timezones) > 50) {
            $sCacheId = $this->cache()->set('timezones_format' . (!$getInForm ? '_no_form' : ''));
            if (($timezones_format = $this->cache()->get($sCacheId, 43200)) === false) { // expire in 1 month
                $timezones_format = [];
                foreach ($timezones as $key => $sTimeZone) {
                    if ($getInForm) {
                        $target_time_zone = new DateTimeZone($sTimeZone);
                        $date_time = new DateTime('now', $target_time_zone);
                        $gmt = 'GMT' . ($date_time->format('P') == '+00:00' ? '' : ' ' . $date_time->format('P'));
                        $timezones_format[$key] = $sTimeZone . ' (' . $gmt . ')';
                    } else {
                        $timezones_format[$key] = $sTimeZone;
                    }
                }
                $this->cache()->save($sCacheId, $timezones_format);
            }
            $timezones = $timezones_format;
        }

        $disableTimezones = Phpfox::getService('core')->getDisabledTimezones();
        if (!$getAll && !empty($disableTimezones)) {
            foreach ($disableTimezones as $key => $disableTimezone) {
                if ($disableTimezone) {
                    unset($timezones[$key]);
                }
            }
        }
        return $timezones;
    }

    /**
     * @param $sConnection
     * @param $iItemId
     * @param $sTypeId
     *
     * @return array|bool
     * @throws Exception
     */
    public function getBlocks($sConnection, $iItemId, $sTypeId)
    {
        $aRows = $this->database()->select('b.block_id, b.title, b.disallow_access, b.module_id, b.component, b.location, b.can_move')
            ->from(Phpfox::getT('block'), 'b')
            ->join(Phpfox::getT('module'), 'm', 'm.module_id = b.module_id AND m.is_active = 1')
            ->join(Phpfox::getT('product'), 'p', 'p.product_id = b.product_id AND p.is_active = 1')
            ->where('b.m_connection = \'' . $this->database()->escape($sConnection) . '\' AND b.is_active = 1')
            ->execute('getSlaveRows');

        $aParts = explode('.', $sConnection);
        $sModule = $aParts[0];

        if (!Phpfox::isModule($sModule)) {
            return Phpfox_Error::set(_p('module_is_not_a_valid_module', ['module' => $sModule]));
        }

        unset($aParts[0]);

        $aCallback = Phpfox::hasCallback($sModule, 'getBlocks' . str_replace('-', '', implode('.', $aParts))) ? Phpfox::callback($sModule . '.getBlocks' . str_replace('-', '', implode('.', $aParts))) : [];

        $aItems = [];

        if (!isset($aItemDatas) && $aCallback) {
            $aItemDatas = $this->database()->select('cache_id, is_hidden')
                ->from(Phpfox::getT($aCallback['table']))
                ->where($aCallback['field'] . ' = ' . (int)$iItemId)
                ->execute('getSlaveRows');
            // We don't cache this one because this query does not include all the fields
            // This caching occurs in the module library instead
        }

        foreach ($aItemDatas as $aItemData) {
            $aItems[$aItemData['cache_id']] = $aItemData;
        }

        $aBlocks = [];
        foreach ($aRows as $aRow) {
            if (!in_array($aRow['location'], [1, 2, 3])) {
                continue;
            }
            if (!$aRow['can_move']) {
                continue;
            }

            if (!empty($aRow['disallow_access'])) {
                if (in_array(Phpfox::getUserBy('user_group_id'), unserialize($aRow['disallow_access']))) {
                    continue;
                }
            }

            $aRow['component'] = str_replace('.', '_', $aRow['component']);
            $aRow['component_call'] = str_replace(['-', '_'], '', $aRow['component']);


            $aRow['is_installed'] = true;
            if (isset($aItems['js_block_border_' . $aRow['module_id'] . '_' . $aRow['component']]) && $aItems['js_block_border_' . $aRow['module_id'] . '_' . $aRow['component']]['is_hidden']) {
                $aRow['is_installed'] = false;
            }

            $aRow['cache_id'] = $aRow['module_id'] . '_' . $aRow['component'];

            $sTitle = $aRow['title'];
            if (empty($sTitle)) {
                $sTitle = ucfirst($aRow['module_id']) . ' ' . ucfirst($aRow['component']);
            }
            $aBlocks[] = array_merge($aRow, ['title' => $sTitle]);
        }

        return $aBlocks;
    }

    /**
     * @param bool $bForEdit
     *
     * @return array
     */
    public function getNewMenu($bForEdit = false)
    {
        $sCacheId = $this->cache()->set('core_new_menu');

        $sModuleBlock = '';
        if (false === ($aSubMenus = $this->cache()->get($sCacheId))) {
            $aMenus = Phpfox::massCallback('getWhatsNew');

            $aSubMenus = [];
            foreach ($aMenus as $sModule => $aMenu) {
                $aKey = array_keys($aMenu);
                $aValue = array_values($aMenu);

                $aSubMenus[$aKey[0]] = $aValue[0];
            }

            $this->cache()->save($sCacheId, $aSubMenus);
            Phpfox::getLib('cache')->group('core', $sCacheId);
        }
        if ($aSubMenus === true) {
            $aSubMenus = [];
        }
        if ($bForEdit === true) {
            $sUserSettings = Phpfox::getComponentSetting(Phpfox::getUserId(), 'core.whats_new_blocks', null);
            if ($sUserSettings !== null) {
                $aUserSettings = unserialize($sUserSettings);
            }

            foreach ($aSubMenus as $sName => $aSubMenu) {
                $aSubMenus[$sName]['name'] = _p($sName);
                $aSubMenus[$sName]['is_used'] = (isset($aUserSettings['m']) && in_array($aSubMenu['id'], $aUserSettings['m']) ? true : ($sUserSettings === null ? true : false));
            }

            return $aSubMenus;
        } else {
            $sUserSettings = Phpfox::getComponentSetting(Phpfox::getUserId(), 'core.whats_new_blocks', null);
            if ($sUserSettings !== null) {
                $aUserSettings = unserialize($sUserSettings);
            }

            $iCnt = 0;
            $aFinalMenu = [];
            foreach ($aSubMenus as $sName => $aSubMenu) {
                if (isset($aUserSettings['m']) && !in_array($aSubMenu['id'], $aUserSettings['m'])) {
                    continue;
                }

                $iCnt++;

                if ($iCnt === 1) {
                    $sModuleBlock = $aSubMenu['block'];
                }

                $aFinalMenu[_p($sName)] = $aSubMenu['ajax'];
            }

            return [$aFinalMenu, $sModuleBlock];
        }
    }

    /**
     * @param bool $bReturnPhrase
     *
     * @return array
     */
    public function getGenders($bReturnPhrase = false)
    {
        $aGenders = [];
        if (!defined('PHPFOX_INSTALLER')) {
            foreach ((array)Phpfox::getParam('user.global_genders') as $iKey => $aGender) {
                $aGenders[$iKey] = ($bReturnPhrase ? $aGender[1] : _p($aGender[1]));
            }
        }

        // Fallback in case something went wrong
        if (!count($aGenders)) {
            $aGenders[1] = ($bReturnPhrase ? 'profile.male' : _p('male'));
            $aGenders[2] = ($bReturnPhrase ? 'profile.female' : _p('female'));
        }

        (($sPlugin = Phpfox_Plugin::get('core.service_core_getgenders__end')) ? eval($sPlugin) : false);

        return $aGenders;
    }

    /**
     * @param array $aParams
     *
     * @return array|bool
     */
    public function getLegacyUrl($aParams)
    {
        $bAddUserName = true;
        if (isset($aParams['user_id']) && !$aParams['user_id']) {
            $bAddUserName = false;
        }

        if ($bAddUserName === true) {
            $this->database()->join(Phpfox::getT('user'), 'u', 'u.user_id = i.user_id');
        }

        $aItem = $this->database()->select('i.' . $aParams['url_field'] . (isset($aParams['select']) ? ', ' . implode(', ', $aParams['select']) : '') . ($bAddUserName ? ', u.user_name' : ''))
            ->from(Phpfox::getT($aParams['table']), 'i')
            ->where('i.' . $aParams['field'] . ' = ' . (int)$aParams['id'])
            ->execute('getSlaveRow');

        if (!isset($aItem[$aParams['url_field']])) {
            return false;
        }

        return $aItem;
    }

    /**
     * @param string $sSearch
     *
     * @return array|string
     */
    public function ipSearch($sSearch)
    {
        $sSearch = str_replace('-', '.', $sSearch);

        if (!Phpfox_Request::instance()->isIP($sSearch)) {
            return Phpfox_Error::set(_p('not_a_valid_ip_address'));
        }

        $aResults = [];
        if (Phpfox::getParam('core.ip_infodb_api_key') != '') {
            $sUrl = 'http://api.ipinfodb.com/v3/ip-city/?ip=' . $sSearch . '&key=' . Phpfox::getParam('core.ip_infodb_api_key') . '&format=xml';
            if (function_exists('file_get_contents') && ini_get('allow_url_fopen')) {
                $sXML = fox_get_contents($sUrl);
            } else {
                $sXML = Phpfox_Request::instance()->send($sUrl, [], 'GET');
            }
            $aCallback = Phpfox::getLib('xml.parser')->parse($sXML, 'UTF-8');
            $aInfo = [
                _p('host_address') => gethostbyaddr($sSearch),
                _p('country')      => (isset($aCallback['countryName']) ? $aCallback['countryName'] : 'Unknown')
            ];

            if (!empty($aCallback['regionName'])) {
                $aInfo[_p('region')] = $aCallback['regionName'];
            }

            if (!empty($aCallback['cityName'])) {
                $aInfo[_p('city')] = $aCallback['cityName'];
            }

            if (!empty($aCallback['zipCode'])) {
                $aInfo[_p('zip_postal_code')] = $aCallback['zipCode'];
            }

            if (!empty($aCallback['latitude'])) {
                $aInfo[_p('latitude')] = $aCallback['latitude'];
            }

            if (!empty($aCallback['longitude'])) {
                $aInfo[_p('longitude')] = $aCallback['longitude'];
            }

            if (!empty($aCallback['timeZone'])) {
                $aInfo[_p('time_zone')] = $aCallback['timeZone'];
            }

            $aResults[] = [
                'table'   => _p('ip_information'),
                'results' => $aInfo
            ];
        } else {
            $aResults[] = [
                'table'   => _p('ip_information'),
                'results' => [
                    _p('missing_api_key') => _p('enter_your_api_key', [
                        'link' => Phpfox_Url::instance()->makeUrl('admincp.setting.edit', ['module-id' => 'core']) . "#ip_infodb_api_key"
                    ])
                ]
            ];
        }

        $aMassCallback = Phpfox::massCallback('ipSearch', $sSearch);

        $aResults = array_merge($aResults, $aMassCallback);

        return $aResults;
    }

    /**
     * @param string $sType
     * @param int    $iPage
     * @param int    $iLimit
     *
     * @return array|bool
     */
    public function getModulePager($sType, $iPage, $iLimit = 5)
    {
        $iGroup = (($iPage * $iLimit) + 1);

        $aCache = [];
        $hDir = opendir(PHPFOX_DIR_MODULE);
        while ($sModule = readdir($hDir)) {
            if ($sModule == '.' || $sModule == '..') {
                continue;
            }

            if (!file_exists(PHPFOX_DIR_MODULE . $sModule . PHPFOX_DS . 'install' . PHPFOX_DS . 'phpfox.xml.php')) {
                if (!file_exists(PHPFOX_DIR_MODULE . $sModule . PHPFOX_DS . 'phpfox.xml')) {
                    continue;
                }
            }

            $aCache[] = $sModule;
        }
        closedir($hDir);

        unset($sModule);

        if ($iGroup > count($aCache)) {
            return false;
        }

        sort($aCache);

        $aXml = [];
        $iCnt = 0;
        $iActualCount = 0;
        foreach ($aCache as $sModule) {
            $iActualCount++;

            if ($iActualCount < $iGroup) {
                continue;
            }

            if (file_exists(PHPFOX_DIR_MODULE . $sModule . PHPFOX_DS . 'phpfox.xml')) {
                $aData = Phpfox::getLib('xml.parser')->parse(PHPFOX_DIR_MODULE . $sModule . PHPFOX_DS . 'phpfox.xml');
            } else {
                $aData = Phpfox::getLib('xml.parser')->parse(file_get_contents(PHPFOX_DIR_MODULE . $sModule . PHPFOX_DS . 'install' . PHPFOX_DS . 'phpfox.xml.php'));
            }

            if (isset($aData[$sType])) {
                $aXml[$sModule] = $aData[$sType];
            }

            $iCnt++;

            if ($iCnt === (int)$iLimit) {
                break;
            }
        }

        return $aXml;
    }

    /**
     * @return array
     */
    public function getSecurePages()
    {
        if (defined('PHPFOX_DISABLE_SECURE_PAGES')) {
            return [];
        }

        $aSecurePages = [
            'user/boot',
            'user.boot',
            'login',
            'logout',
            'user.logout',
            'user.setting',
            'user.privacy',
            'user.login',
            'user.register',
            'captcha.image'
        ];

        if ($sPlugin = Phpfox_Plugin::get('core.service_core_getsecurepages')) {
            eval($sPlugin);
        }

        return (array)$aSecurePages;
    }

    /**
     * Refreshes and returns the hash that allows SWFU file uploads. This is used
     * together with the auth service to allow the massuploader
     *
     * @return string
     */
    public function getHashForUpload()
    {
        if (Phpfox::isUser()) {
            Phpfox_Database::instance()->delete(Phpfox::getT('upload_track'), 'user_id = ' . Phpfox::getUserId());
        }
        $sHash = md5(uniqid() . Phpfox::getUserBy('email') . uniqid() . Phpfox::getUserBy('password_salt'));

        $aCookieNames = Phpfox::getService('user.auth')->getCookieNames();
        Phpfox::getLib('session')->set('flashuploadhash', Phpfox::getCookie($aCookieNames[1]));

        $sCacheId = $this->cache()->set(['uagent', $sHash]);
        $this->cache()->remove($sCacheId);
        $this->cache()->save($sCacheId, $_SERVER['HTTP_USER_AGENT']);

        Phpfox_Database::instance()->insert(Phpfox::getT('upload_track'), [
            'user_id'    => Phpfox::getUserId(),
            'hash'       => $sHash,
            'user_hash'  => Phpfox::getLib('parse.input')->clean(Phpfox::getCookie($aCookieNames[1])),
            'ip_address' => Phpfox::getIp()
        ]);
        return $sHash;
    }

    /**
     * This function is used to get the hash for an image in the Spam Questions feature
     *
     * @param string $sUrl
     *
     * @return string
     */
    public function getHashForImage($sUrl)
    {
        $sHash = md5(rand(100, 999) . $sUrl . rand(100, 999));
        $this->database()->insert(Phpfox::getT('upload_track'), [
            'hash'       => $sUrl,
            'user_hash'  => $sHash,
            'time_stamp' => PHPFOX_TIME,
            'ip_address' => Phpfox::getIp()
        ]);

        // Delete tracks from last 15 minutes to avoid
        $this->database()->delete(Phpfox::getT('upload_track'), 'time_stamp < ' . (PHPFOX_TIME - (60 * 15)));

        return $sHash;
    }

    /**
     * This function returns an array with the most likely latitude and longitud.
     * We can get the Lat and Lng from Php's TimeZone object. We can also get it
     * from the ipSearch function here which would be more accurate.
     *
     * @return array
     */
    public function getLatLng()
    {
        // do we have an api key for the IP?
        if (Phpfox::getParam('core.ip_infodb_api_key') != '') {
            $aInfo = $this->ipSearch(Phpfox::getIp());
            if (isset($aInfo[_p('longitude')])
                && !empty($aInfo[_p('longitude')])
                && isset($aInfo[_p('latitude')])
                && !empty($aInfo[_p('latitude')])) {
                return ['latitude' => $aInfo[_p('latitude')], 'longitude' => $aInfo[_p('longitude')]];
            }
        }
        // has user set a country
        if (($sTz = Phpfox::getUserBy('time_zone')) != '' && PHPFOX_USE_DATE_TIME) {
            $aTZ = $this->getTimeZones(true, false);
            if (isset($aTZ[$sTz])) {
                $oTz = new DateTimeZone($aTZ[$sTz]);
                $aInfo = $oTz->getLocation();
                return ['latitude' => $aInfo['latitude'], 'longitude' => $aInfo['longitude']];
            }
        }
        /* return a default value (London GMT0)*/
        return ['latitude' => '51.544627', 'longitude' => '-0.184021'];
    }

    /**
     * @return int
     */
    public function getEditTitleSize()
    {
        return 60;
    }

    /**
     * @param array $aSetting
     *
     * @return bool
     */
    public function getLegacyItem($aSetting)
    {
        if (empty($aSetting['search'])) {
            $aSetting['search'] = 'title_url';
        }

        $aRow = $this->database()->select(implode(',', $aSetting['field']))
            ->from(Phpfox::getT($aSetting['table']))
            ->where($aSetting['search'] . ' = \'' . $this->database()->escape($aSetting['title']) . '\'')
            ->execute('getSlaveRow');

        if (isset($aRow[$aSetting['field'][0]])) {
            Phpfox_Url::instance()->forward(Phpfox::permalink($aSetting['redirect'], $aRow[$aSetting['field'][0]], (isset($aSetting['field'][1]) ? $aRow[$aSetting['field'][1]] : (!empty($aSetting['sub_page']) ? $aSetting['sub_page'] . '/' : ''))), '', 301);

            return true;
        }

        return false;
    }

    /**
     * @param array $aUser
     *
     * @return string
     */
    public function getForBrowse(&$aUser)
    {
        $sPrivacy = '0';
        if ($aUser['user_id'] == Phpfox::getUserId() || Phpfox::getUserParam('privacy.can_view_all_items')) {
            $sPrivacy = '0,1,2,3,4';
        } else {
            if ($aUser['is_friend']) {
                $sPrivacy = '0,1,2';
            } else if ($aUser['is_friend_of_friend']) {
                $sPrivacy = '0,2';
            }
        }

        return $sPrivacy;
    }

    public function loadLineficon()
    {
        $sCacheId = $this->cache()->set('core_lineficon_list');
        if (false === ($aIconFonts = $this->cache()->get($sCacheId))) {
            $aIconFonts = [];
            $sDemoPath = PHPFOX_DIR . 'theme/frontend/default/style/default/css/icofont.css';
            $sContent = file_get_contents($sDemoPath);
            if ($sContent !== false) {
                preg_match_all('/\.([^:]+):before {/', $sContent, $aMatch);
                if (isset($aMatch[1])) {
                    $aIconFonts = $aMatch[1];
                }
                $this->cache()->save($sCacheId, $aIconFonts);
            }
        }
        return $aIconFonts;
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
        if ($sPlugin = Phpfox_Plugin::get('core.service_core__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}