<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 * 
 * 
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		phpFox LLC
 * @package  		Module_Admincp
 * @version 		$Id: process.class.php 225 2009-02-13 13:24:59Z phpFox LLC $
 */
class Admincp_Service_Cron_Process extends Phpfox_Service 
{
	/**
	 * Class constructor
	 */	
	public function __construct()
	{	
		$this->_sTable = Phpfox::getT('cron');
	}

    /**
     * Active/Deactive cron
     * @param $cronId
     * @param $active
     * @return bool|resource
     */
    public function updateActivity($cronId, $active)
    {
        if ($success = db()->update($this->_sTable, ['is_active' => (int)$active], ['cron_id' => $cronId])) {
            $this->cache()->remove('core_admincp_cron_manager');
        }
        return $success;
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
		if ($sPlugin = Phpfox_Plugin::get('admincp.service_cron_process_call'))
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