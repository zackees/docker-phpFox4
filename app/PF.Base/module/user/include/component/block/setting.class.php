<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Block_Setting
 */
class User_Component_Block_Setting extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{
		$aUser = Phpfox::getService('user')->get(Phpfox::getUserId(), true);
		
		if (!empty($aUser['birthday']))
		{
			$aUser = array_merge($aUser, Phpfox::getService('user')->getAgeArray($aUser['birthday']));
		}

        if (Phpfox::getUserParam('user.can_add_custom_gender')) {
            $aCustomGenders = Phpfox::getService('user')->getCustomGenders($aUser);
            if($aCustomGenders) {
                $this->template()->setHeader('cache', [
                    '<script>aUserGenderCustom = ' . json_encode($aCustomGenders) . '; bIsCustomGender = true;</script>'
                ]);
            }
            else {
                $this->template()->setHeader('cache', [
                    '<script>aUserGenderCustom = {}; bIsCustomGender = false;</script>'
                ]);
            }
        }

		$this->template()->assign(array(
				'aForms' => $aUser,
				'aSettings' => Phpfox::getService('custom')->getForEdit(array('user_panel'), $aUser['user_id'], $aUser['user_group_id']),
			)
		);
	}
	
	/**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('user.component_block_setting_clean')) ? eval($sPlugin) : false);
	}
}
