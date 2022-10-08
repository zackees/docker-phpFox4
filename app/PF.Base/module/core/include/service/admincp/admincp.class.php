<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 *
 *
 * @copyright        [PHPFOX_COPYRIGHT]
 * @author        phpFox LLC
 * @package        Phpfox_Service
 * @version        $Id: admincp.class.php 6668 2013-09-24 13:05:06Z Fern $
 */
class Core_Service_Admincp_Admincp extends Phpfox_Service
{
    /**
     * Class constructor
     */
    public function __construct()
    {
    }

    /**
     * Get adminCP note
     *
     * @return string
     */
    public function getNote()
    {
        $sCacheId = $this->cache()->set('admincp_note');

        if (false === ($sNote = $this->cache()->get($sCacheId))) {
            $sNote = $this->database()->select('value_actual')
                ->from(Phpfox::getT('setting'))
                ->where('module_id = \'core\' AND var_name = \'global_admincp_note\'')
                ->execute('getSlaveField');

            $this->cache()->save($sCacheId, $sNote);
            Phpfox::getLib('cache')->group('admincp', $sCacheId);
        }

        return $sNote;
    }

    /**
     * Get only Admin user
     *
     * @return array
     */
    public function getActiveAdmins()
    {
        $iActiveAdminCp = (PHPFOX_TIME - (Phpfox::getParam('core.admincp_timeout') * 60));

        $aUsers = $this->database()->select('uf.in_admincp, ls.location, ls.ip_address, ' . Phpfox::getUserField())
            ->from(Phpfox::getT('user_field'), 'uf')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = uf.user_id')
            ->join(Phpfox::getT('log_session'), 'ls', 'ls.user_id = u.user_id')
            ->where('uf.in_admincp > \'' . $iActiveAdminCp . '\'')
            ->group('u.user_id, ls.location, ls.ip_address', true)
            ->execute('getSlaveRows');

        foreach ($aUsers as $iKey => $aUser) {
            if (!isset($aUser['location'])) {
                if ($aUser['user_id'] == Phpfox::getUserId()) {
                    $aUser['location'] = 'admincp';
                } else {
                    $aUser['location'] = '';
                }
            }

            $aUsers[$iKey]['location'] = Phpfox::getService('log.session')->getActiveLocation($aUser['location']);

        }

        return $aUsers;
    }

    /**
     * get News from phpfox
     * @param bool $bSlide
     * @return array
     */
    public function getNews($bSlide = false)
    {
        $bSlideIsActive = $this->newsSlideIsActive();
        $sSlideCacheId = $this->cache()->set('phpfox_news_slide');
        $sMoreCacheId = $this->cache()->set('phpfox_news_more');

        if (false === ($aCache = $this->cache()->get($bSlide ? $sSlideCacheId : $sMoreCacheId, 60))) {
            try {
                $aNews = Phpfox::getLib('xml.parser')->parse(Phpfox_Request::instance()->send('http://feeds.feedburner.com/phpfox', [], 'GET'), 'UTF-8');
            } catch (Exception $exception) {
                return [];
            }
            $aSlideCache = $aMoreCache = [];
            $iCnt = 0;

            if (is_array($aNews) && !empty($aNews['channel']['item'])) {
                foreach ($aNews['channel']['item'] as $aItem) {
                    $iCnt++;

                    if ($bSlideIsActive && $iCnt <= 3) {
                        $aSlideCache[] = $this->_processNews($aItem);
                    } else {
                        $aMoreCache[] = $this->_processNews($aItem);
                    }

                    if ($iCnt == 6) {
                        break;
                    }
                }
            }

            $this->cache()->save($sSlideCacheId, $aSlideCache);
            $this->cache()->save($sMoreCacheId, $aMoreCache);
            Phpfox::getLib('cache')->group('core', $sSlideCacheId);
            Phpfox::getLib('cache')->group('core', $sMoreCacheId);

            if ($bSlide) {
                return $aSlideCache;
            } else {
                return $aMoreCache;
            }
        }

        if (!is_array($aCache)) {
            $aCache = [];
        }

        return $aCache;
    }

    private function _processNews($aItem)
    {
        if (!empty($aItem['content:encoded'])) {
            preg_match('/<*img[^>]*src*=*["\']([^"\']*)?["\']/i', $aItem['content:encoded'], $aMatch);
            $sImage = !empty($aMatch[1]) ? $aMatch[1] : '';
        } else {
            $aLinkInfo = (Phpfox::isModule('link') ? Phpfox::getService('link')->getLink($aItem['link']) : []);
            $sImage = isset($aLinkInfo['default_image']) ? $aLinkInfo['default_image'] : '';
        }
        return [
            'title' => $aItem['title'],
            'link' => $aItem['link'],
            'description' => $aItem['description'],
            'image' => $sImage,
            'creator' => $aItem['dc:creator'],
            'time_stamp' => Phpfox::getTime(Phpfox::getParam('core.global_update_time'), strtotime($aItem['pubDate']))
        ];
    }

    /**
     * Get last user login to AdminCP
     *
     * @return array
     */
    public function getLastAdminLogins()
    {
        $aUsers = $this->database()->select('al.login_id, al.time_stamp, al.ip_address, al.is_failed, ' . Phpfox::getUserField())
            ->from(Phpfox::getT('admincp_login'), 'al')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = al.user_id')
            ->order('al.time_stamp DESC')
            ->limit(5)
            ->execute('getSlaveRows');

        foreach ($aUsers as $iKey => $aItem) {
            $aUsers[$iKey]['attempt'] = $this->_getAdminLoginAttempt($aItem['is_failed']);
        }

        return $aUsers;
    }

    /**
     * @param array $aConds
     * @param string $sSort
     * @param string $iPage
     * @param string $iLimit
     *
     * @return array
     */
    public function getAdminLogins($aConds, $sSort = '', $iPage = '', $iLimit = '')
    {
        $iCnt = $this->database()->select('COUNT(*)')
            ->from(Phpfox::getT('admincp_login'), 'al')
            ->where($aConds)
            ->order($sSort)
            ->execute('getSlaveField');

        $aItems = [];
        if ($iCnt) {
            $aItems = $this->database()->select('al.*, ' . Phpfox::getUserField())
                ->from(Phpfox::getT('admincp_login'), 'al')
                ->join(Phpfox::getT('user'), 'u', 'u.user_id = al.user_id')
                ->where($aConds)
                ->order($sSort)
                ->limit($iPage, $iLimit, $iCnt)
                ->execute('getSlaveRows');

            foreach ($aItems as $iKey => $aItem) {
                $aItems[$iKey]['attempt'] = $this->_getAdminLoginAttempt($aItem['is_failed']);
            }
        }

        return [$iCnt, $aItems];
    }

    /**
     * @param int $iId
     *
     * @return string|array
     */
    public function getAdminLoginLog($iId)
    {
        $aLog = $this->database()->select('al.*, ' . Phpfox::getUserField())
            ->from(Phpfox::getT('admincp_login'), 'al')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = al.user_id')
            ->where('al.login_id = ' . (int)$iId)
            ->execute('getSlaveRow');

        if (!isset($aLog['login_id'])) {
            return Phpfox_Error::set(_p('not_a_valid_login_log'));
        }

        $aLog['attempt'] = $this->_getAdminLoginAttempt($aLog['is_failed']);
        $aLog['cache_data'] = unserialize($aLog['cache_data']);
        $aLog['cache_data']['request'] = unserialize($aLog['cache_data']['request']);
        $aLog['cache_data']['token'] = ((isset($aLog['cache_data']['request']['phpfox']['security_token']) ? $aLog['cache_data']['request']['phpfox']['security_token'] : isset($aLog['cache_data']['request'][Phpfox::getTokenName()]['security_token'])) ? $aLog['cache_data']['request'][Phpfox::getTokenName()]['security_token'] : '');
        $aLog['cache_data']['email'] = $aLog['cache_data']['request']['val']['email'];
        return $aLog;
    }

    /**
     * If a call is made to an unknown method attempt to connect
     * it to a specific plug-in with the same name thus allowing
     * plug-in developers the ability to extend classes.
     *
     * @param string $sMethod is the name of the method
     * @param array $aArguments is the array of arguments of being passed
     *
     * @return null
     */
    public function __call($sMethod, $aArguments)
    {
        /**
         * Check if such a plug-in exists and if it does call it.
         */
        if ($sPlugin = Phpfox_Plugin::get('core.service_admincp_admincp__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }

    /**
     * @param int $iAttempt
     *
     * @return string
     */
    private function _getAdminLoginAttempt($iAttempt)
    {
        $iAttempt = (int)$iAttempt;
        switch ($iAttempt) {
            case 1:
                $sAttempt = _p('not_a_valid_account');
                break;
            case 2:
                $sAttempt = _p('email_failure');
                break;
            case 3:
                $sAttempt = _p('password_failure');
                break;
            case 4:
                $sAttempt = _p('phone_number_failure');
                break;
            default:
                $sAttempt = _p('success');
                break;
        }

        return $sAttempt;
    }

    /**
     * Check if news slide is active
     * @return bool
     */
    public function newsSlideIsActive()
    {
        return !!db()->select('is_active')->from(':block')->where([
            'm_connection' => 'admincp.index',
            'component' => 'news-slide'
        ])->executeField();
    }
}