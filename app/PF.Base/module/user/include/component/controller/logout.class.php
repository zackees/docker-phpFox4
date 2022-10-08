<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Controller_Logout
 */
class User_Component_Controller_Logout extends Phpfox_Component 
{
	/**
	 * Process the controller
	 *
	 */
	public function process()
	{
		if ($this->request()->get('req3') != 'done')
		{
			Phpfox::getService('user.auth')->logout();

            if (Phpfox::getParam('user.redirect_after_logout')) {
                $this->url()->send(Phpfox::getParam('user.redirect_after_logout'));
            } else {
                $this->url()->send('');
            }

		}
		
		$this->template()->setTitle(_p('logout'));
	}
}
