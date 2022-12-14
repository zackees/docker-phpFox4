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
 * @package 		Phpfox_Service
 * @version 		$Id: process.class.php 4854 2012-10-09 05:20:40Z phpFox LLC $
 */

use Core\Installation\Manager as InstallationManager;

class Admincp_Service_Process extends Phpfox_Service
{
    /**
     * Class constructor
     */
    public function __construct() {}
    
    /**
     * @param array $aVals
     *
     * @return bool|int
     */
	public function addNewPrivacyRule($aVals)
	{
        if (empty($aVals['url'])) {
            Phpfox_Error::set(_p('provide_a_url'));
        }
        
        if (empty($aVals['user_group'])) {
            Phpfox_Error::set(_p('provide_atleast_one_user_group_for_this_rule'));
        }
        
        if (!Phpfox_Error::isPassed()) {
            return false;
        }
		
		$aFind = array(Phpfox::getParam('core.path'), 'index.php?do=', '/');
		
		$aReplace = array('', '', '.');
				
		$sUrl = $aVals['url'];
		$sUrl = str_replace($aFind, $aReplace, $sUrl);
		$sUrl = trim($sUrl, '.');
		
		$iId = $this->database()->insert(Phpfox::getT('admincp_privacy'), array(
					'url' => $sUrl,
					'time_stamp' => PHPFOX_TIME,
					'user_id' => Phpfox::getUserId(),
					'user_group' => json_encode($aVals['user_group']),
					'wildcard' => (int) $aVals['wildcard']
				)
			);
		
		$this->cache()->remove();
		
		return $iId;
	}
    
    /**
     * @param int $iRuleId
     *
     * @return bool
     */
	public function deletePrivacyRule($iRuleId)
	{
		$this->database()->delete(Phpfox::getT('admincp_privacy'), 'rule_id = ' . (int) $iRuleId);
		
		$this->cache()->remove();
		
		return true;
	}

    /**
     * @param string $cmd
     * @param string $type
     * @param string $appId
     * @param string $appDir
     *
     * @return bool
     * @throws Exception
     */
    public function processUploadedApp($cmd, $type, $appId, $appDir)
    {
        $installer = new InstallationManager([]);

        if ($cmd == 'install' || $cmd == 'upgrade') {
            $installer->dryInstall([
                'type' => $type,
                'productName' => $appId,
                'apps_dir' => base64_encode($appDir),
                'is_upgrade' => $cmd == 'upgrade',
            ]);
        } elseif ($cmd == 're_validate') {
            if ($appInit = \Core\Lib::appInit($appId)) {
                $appInit->processInstall();
                Phpfox::addMessage(_p('the_app_has_been_re_validated'));
            }
        }
        return true;
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
        if ($sPlugin = Phpfox_Plugin::get('admincp.service_process__call')) {
            eval($sPlugin);
            return null;
        }
			
		/**
		 * No method or plug-in found we must throw a error.
		 */
		Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
	}	
}