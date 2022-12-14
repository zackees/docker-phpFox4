<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Block_Status
 */
class User_Component_Block_Status extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{
		if (!Phpfox::isUser())
		{
			return false;
		}
		
		$sImage = Phpfox::getLib('image.helper')->display(array(
				'server_id' => Phpfox::getUserBy('server_id'),
				'title' => Phpfox::getUserBy('full_name'),
				'path' => 'core.url_user',
				'file' => Phpfox::getUserBy('user_image'),
				'suffix' => '_20_square',
				'max_width' => 20,
				'max_height' => 20,
				'no_default' => true,
				'style' => 'vertical-align:middle; padding-right:5px;'
			)
		);		
		
		$this->template()->assign(array(
				'sUserGlobalImage' => $sImage,
				'sUserCurrentStatus' => Phpfox::getUserBy('status'),
				'iCurrentUserId' => Phpfox::getUserId(),
				'sHeader' => _p('status'),
			)
		);
		
		return 'block';
	}
	
	/**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('user.component_block_status_clean')) ? eval($sPlugin) : false);
	}
}
