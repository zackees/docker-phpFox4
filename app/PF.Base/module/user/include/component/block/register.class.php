<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Block_Register
 */
class User_Component_Block_Register extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        if (Phpfox::isUser()) {
            return false;
        }
        $bAllowRegistration = Phpfox::getParam('user.allow_user_registration');
        $this->template()->assign(array(
                'sHeader' => $bAllowRegistration ? _p('sign_up') : _p('sign_in'),
                'sSiteUrl' => Phpfox::getParam('core.path'),
                'aTimeZones' => Phpfox::getService('core')->getTimeZones(),
                'aPackages' => (Phpfox::isAppActive('Core_Subscriptions') ? Phpfox::getService('subscribe')->getPackages(true) : null),
                'sDobStart' => Phpfox::getParam('user.date_of_birth_start'),
                'sDobEnd' => Phpfox::getParam('user.date_of_birth_end'),
                'sSiteTitle' => Phpfox::getParam('core.site_title'),
                'bAllowRegistration' => $bAllowRegistration,
                'sEmailClass' => 'email' . PHPFOX_TIME,
                'sConfirmEmailClass' => 'confirm_email' . PHPFOX_TIME
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
        (($sPlugin = Phpfox_Plugin::get('user.component_block_register_clean')) ? eval($sPlugin) : false);
    }
}
