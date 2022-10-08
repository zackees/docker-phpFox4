<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Block_Giftpoints extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{
		Phpfox::isUser(true);
        if(!Phpfox::isAppActive('Core_Activity_Points')) {
            return false;
        }
        Phpfox::getUserParam('activitypoint.can_gift_activity_points', true);
		
		$aUser = Phpfox::getService('user')->get($this->getParam('user_id'), true);
			
		$this->template()->assign(array(
				'aUser' => $aUser,
				'iCurrentAvailable' => Phpfox::getUserBy('activity_points'),
				'iTrgUserId' => $this->getParam('user_id')
			)
		);
	}
	
	/**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('core.component_block_activity_clean')) ? eval($sPlugin) : false);
	}
}