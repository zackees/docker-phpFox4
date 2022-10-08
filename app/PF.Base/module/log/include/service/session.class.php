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
 * @package          Module_Log
 * @version          $Id: session.class.php 7244 2014-03-31 17:41:12Z Fern $
 */
class Log_Service_Session extends Phpfox_Service
{
    private $_aSession = [];

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('log_session');
    }

    public function getSessionId()
    {
        return (isset($this->_aSession['session_hash']) ? $this->_aSession['session_hash'] : 0);
    }

    public function get($sName, $mDef = '')
    {
        return (isset($this->_aSession[$sName]) ? $this->_aSession[$sName] : $mDef);
    }

    public function verifyToken()
    {

    }

    public function getToken()
    {
        if (defined('PHPFOX_INSTALLER')) {
            return false;
        }

        static $sToken;

        if ($sToken) {
            return $sToken;
        }

        $sToken = (md5(Phpfox_Request::instance()->getIdHash() . md5(Phpfox::getParam('core.salt'))));

        return $sToken;
    }

    public function getActiveTime()
    {
        return (PHPFOX_TIME - (Phpfox::getParam('log.active_session') * 60));
    }

    public function setUserSession()
    {
        $oSession = Phpfox::getLib('session');
        $sSessionHash = $oSession->get('session');
        $oRequest = Phpfox_Request::instance();
        if ($sSessionHash) {
            $this->_aSession = Phpfox::getService('user.auth')->getUserSession();
        }
        if (!isset($this->_aSession['session_hash'])) {
            $this->_aSession = $this->database()->select('s.session_hash, s.id_hash, s.captcha_hash, s.user_id as ls_user_id, s.last_activity as ls_last_activity')
                ->from($this->_sTable, 's')
                ->where(!Phpfox::isUser() ? "s.user_id = 0" : ("s.session_hash = '" . $this->database()->escape($sSessionHash) . "' AND s.id_hash = '" . $this->database()->escape($oRequest->getIdHash()) . "'"))
                ->execute('getSlaveRow');
        }

        $sLocation = $oRequest->get(PHPFOX_GET_METHOD);
        $sLocation = substr($sLocation, 0, 244);
        $sBrowser = substr(Phpfox_Request::instance()->getBrowser(), 0, 99);
        $sIp = Phpfox_Request::instance()->getIp();

        $aDisAllow = [
            'captcha/image'
        ];

        // Don't log a session into the DB if we disallow it
        if (Phpfox_Url::instance()->isUrl($aDisAllow)) {
            return null;
        }

        $bIsForum = (bool)strstr($sLocation, 'forum');
        $iForumId = 0;
        if ($bIsForum) {
            $aForumIds = explode('-', $oRequest->get('req2'));
            if (isset($aForumIds[(count($aForumIds) - 1)])) {
                $iForumId = (int)$aForumIds[(count($aForumIds) - 1)];
            }
        }

        $iIsHidden = 0;
        if (!isset($this->_aSession['session_hash'])) {
            $sSessionHash = $oRequest->getSessionHash();
            if (Phpfox::getUserId()) {
                $this->database()->delete($this->_sTable, 'user_id = ' . Phpfox::getUserId());
            } else {
                $this->database()->delete($this->_sTable, 'id_hash = \'' . $oRequest->getIdHash() . '\'');
            }
            $this->database()->insert($this->_sTable, [
                    'session_hash'  => $sSessionHash,
                    'id_hash'       => $oRequest->getIdHash(),
                    'user_id'       => Phpfox::getUserId(),
                    'last_activity' => PHPFOX_TIME,
                    'location'      => $sLocation,
                    'is_forum'      => ($bIsForum ? '1' : '0'),
                    'forum_id'      => $iForumId,
                    'im_hide'       => $iIsHidden,
                    'ip_address'    => $sIp,
                    'user_agent'    => $sBrowser
                ]
            );
            $oSession->set('session', $sSessionHash);
        } else if (isset($this->_aSession['session_hash'])) {
            if ((isset($this->_aSession['ls_last_activity']) && $this->_aSession['ls_last_activity'] < $this->getActiveTime())
                || (isset($this->_aSession['ls_user_id']) && $this->_aSession['ls_user_id'] == 0 && Phpfox::getUserId())) {
                $this->database()->update($this->_sTable, [
                    'last_activity' => PHPFOX_TIME,
                    'user_id'       => Phpfox::getUserId(),
                    "location"      => $sLocation,
                    "is_forum"      => ($bIsForum ? "1" : "0"),
                    "forum_id"      => $iForumId,
                    'im_hide'       => $iIsHidden,
                    "ip_address"    => $sIp,
                    "user_agent"    => $sBrowser
                ], "session_hash = '" . $this->_aSession["session_hash"] . "'");
            }
        }

        if (!Phpfox::getCookie('visit')) {
            Phpfox::setCookie('visit', PHPFOX_TIME);
        }

        if (Phpfox::isUser()) {
            if (!Phpfox::getParam('user.disable_store_last_user')) {
                if ((Phpfox::getUserBy('last_activity') < $this->getActiveTime() || (isset($this->_aSession['ls_user_id']) && $this->_aSession['ls_user_id'] == 0))) {
                    $this->database()->insert(Phpfox::getT('user_ip'), [
                            'user_id'    => Phpfox::getUserId(),
                            'type_id'    => 'session_login',
                            'ip_address' => Phpfox::getIp(),
                            'time_stamp' => PHPFOX_TIME
                        ]
                    );
                }

                if ((Phpfox::getUserBy('last_activity') < (PHPFOX_TIME - 60) || (isset($this->_aSession['ls_user_id']) && $this->_aSession['ls_user_id'] == 0))) {
                    $this->database()->update(Phpfox::getT('user'), [
                        'last_activity'   => PHPFOX_TIME,
                        'last_ip_address' => Phpfox::getIp()
                    ],
                        'user_id = ' . Phpfox::getUserId());
                }
            }
        }
    }

    public function getActiveLocation($sLocation)
    {
        $sLocation = trim($sLocation, '/');

        switch ($sLocation) {
            case 'admincp':
                if ($sLocation == 'admincp') {
                    $sLocation = _p('admincp_dashboard');
                }
                break;
            default:
                $sLocation = _p('site_index');
                break;
        }

        return $sLocation;
    }

    public function getOnlineStats()
    {
        $sCacheId = $this->cache()->set('log_online_stats');
        if (($sOnlineMembers = Phpfox::getLib('cache')->get($sCacheId, 1)) === false) {
            $sOnlineMembers = $this->database()->select('COUNT(DISTINCT user_id)')
                ->from(Phpfox::getT('log_session'))
                ->where('user_id > 0 AND last_activity > ' . $this->getActiveTime())
                ->execute('getSlaveField');
            $this->cache()->save($sCacheId, $sOnlineMembers);
        }
        $sOnlineGuests = 0;

        (($sPlugin = Phpfox_Plugin::get('log.service_session_get_online_stats')) ? eval($sPlugin) : false);

        return [
            'members' => (int)$sOnlineMembers,
            'guests'  => (int)$sOnlineGuests
        ];
    }

    public function getOnlineMembers()
    {
        static $iTotal = null;

        if ($iTotal === null) {
            $iTotal = $this->database()->select('COUNT(DISTINCT user_id)')
                ->from(Phpfox::getT('log_session'))
                ->where('user_id > 0 AND last_activity > ' . $this->getActiveTime())
                ->execute('getSlaveField');
        }

        return $iTotal;
    }

    /**
     * If a call is made to an unknown method attempt to connect
     * it to a specific plug-in with the same name thus allowing
     * plug-in developers the ability to extend classes.
     *
     * @param string $sMethod    is the name of the method
     * @param array  $aArguments is the array of arguments of being passed
     */
    public function __call($sMethod, $aArguments)
    {
        /**
         * Check if such a plug-in exists and if it does call it.
         */
        if ($sPlugin = Phpfox_Plugin::get('log.service_session___call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }

    private function _log($sMessage)
    {
        if (PHPFOX_DEBUG) {
            Phpfox_Error::trigger($sMessage, E_USER_ERROR);
        }
        exit($sMessage);
    }
}