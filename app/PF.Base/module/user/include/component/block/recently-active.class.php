<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Block_Login_Block
 */
class User_Component_Block_Recently_Active extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $title = _p('recently_active');
        $users = Phpfox::getService('user.featured')->getRecentActiveUsers();

        if (empty($users)) {
            return false;
        }

        $this->template()->assign(array(
                'aUsers' => $users,
                'sHeader' => $title,
            )
        );
        if (!$this->request()->get('s')) {
            $sViewParam = $this->request()->get('view');
            $aSpecialPages = [
                'online',
                'featured'
            ];
            if (!in_array($sViewParam, $aSpecialPages)) {
                return 'block';
            }
        }
        return false;
    }
}