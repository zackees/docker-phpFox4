<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Log_Service_Process extends Phpfox_Service 
{

    /**
     * Remove old session if expired
     * @return bool
     */
    public function removeOldUserSessions()
    {
        $time = PHPFOX_TIME - (Phpfox::getParam('log.active_session') * 60);
        $this->database()->delete(Phpfox::getT('log_session'), 'last_activity < ' . $time);
        $this->database()->delete(Phpfox::getT('core_session_data'),'(lifetime = 0 AND expired_at < ' . $time . ') OR (lifetime > 0 AND expired_at < ' . PHPFOX_TIME . ')');
        return true;
    }
	/**
	 * If a call is made to an unknown method attempt to connect
	 * it to a specific plug-in with the same name thus allowing 
	 * plug-in developers the ability to extend classes.
	 *
	 * @param string $sMethod is the name of the method
	 * @param array $aArguments is the array of arguments of being passed
	 */
	public function __call($sMethod, $aArguments)
	{
		/**
		 * Check if such a plug-in exists and if it does call it.
		 */
		if ($sPlugin = Phpfox_Plugin::get('log.service_process__call'))
		{
			eval($sPlugin);
            return null;
		}
			
		/**
		 * No method or plug-in found we must throw a error.
		 */
		Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
	}	
}