<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Block_Login_Block
 */
class User_Component_Block_Users_You_May_Know extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        if (!Phpfox::isModule('friend')
            || !Phpfox::isUser()
            || Phpfox::getParam('user.hide_recommended_user_block', false)
            || empty($users = Phpfox::getService('friend.suggestion')->get(false, true))) {
            return false;
        }

        $this->template()->assign([
                'aUsers' => $users,
                'sHeader' => _p('users_you_may_know'),
            ]
        );

        if (!$this->request()->get('s')) {
            $sViewParam = $this->request()->get('view');
            $aSpecialPages = [
                'online',
                'featured',
            ];
            if (!in_array($sViewParam, $aSpecialPages)) {
                return 'block';
            }
        }

        return false;
    }
}