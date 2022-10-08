<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Block_Images
 */
class User_Component_Block_Google_Login_Button extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        if (!Phpfox::getParam('core.enable_register_with_google') || empty(Phpfox::getParam('core.google_oauth_client_id'))) {
            return false;
        }
        $this->template()->assign([
            'bSmallSize' => $this->getParam('small_size',false),
            'sPhrase' => $this->getParam('phrase', 'sign_in_with_google'),
            'sId' => 'js_google_signin_' . PHPFOX_TIME
        ]);
        return 'block';
    }
}