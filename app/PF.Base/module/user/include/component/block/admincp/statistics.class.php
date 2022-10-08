<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Block_Admincp_Statistics
 */
class User_Component_Block_Admincp_Statistics extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $iUser = (int)$this->request()->get('iUser');
        $aUser = Phpfox::getService('user')->get($iUser, true);
        if (empty($aUser['user_id'])) {
            return false;
        }

        $aFriends = Phpfox::callback('friend.getUserStatsForAdmin', $aUser['user_id']);

        $aDefaultStats = [
            [
                'name' => $aFriends['total_name'],
                'total' => $aFriends['total_value']
            ],
            [
                'name' => _p('spam_count'),
                'total' => $aUser['total_spam']
            ]
        ];

        $aStats = Phpfox::getService('user')->getUserStatistics($iUser);

        if ($aUser['status_id']) {
            $aVerifyInfo = Phpfox::getService('user.verify')->getVerificationByUser($aUser['user_id'], true);
            if ($aVerifyInfo) {
                if ($aVerifyInfo[2]) {
                    $aUser['unverified_type'] = $aVerifyInfo[0] == 1 ? 'sms' : 'phone';
                } else {
                    $aUser['unverified_type'] = $aVerifyInfo[0] == 1 ? 'email' : 'phone';
                }
            } else {
                $aUser['unverified_type'] = 'email';
            }
        }

        $this->template()->assign([
                'aUser' => $aUser,
                'aStats' => array_merge($aDefaultStats, $aStats)
            ]
        );

        return 'block';
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('user.component_block_filter_clean')) ? eval($sPlugin) : false);
    }
}
