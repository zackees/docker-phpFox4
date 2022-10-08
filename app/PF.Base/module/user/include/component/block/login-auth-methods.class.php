<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Block_Filter
 */
class User_Component_Block_Login_Auth_Methods extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $iUser = $this->getParam('user_id');
        $aUser = Phpfox::getService('user')->getUser((int)$iUser);
        if (empty($aUser)) {
            return Phpfox_Error::display(_p('invalid_user'));
        }

        $this->template()->assign([
            'iUserId'      => $iUser,
            'sEmail'       => !empty($aUser['email']) ? Phpfox::secureText($aUser['email'], 'email') : '',
            'sPhoneNumber' => Phpfox::getParam('core.enable_register_with_phone_number') && !empty($aUser['full_phone_number']) ?
                Phpfox::secureText($aUser['full_phone_number'], 'phone') : ''
        ]);
        return 'block';
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('user.component_block_login_auth_methods_clean')) ? eval($sPlugin) : false);
    }
}
