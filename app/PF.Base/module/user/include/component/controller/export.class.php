<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * @copyright [PHPFOX_COPYRIGHT]
 * @author phpFox LLC
 *
 * Class User_Component_Controller_Export
 */
class User_Component_Controller_Export extends Phpfox_Component
{
    public function process()
    {
        Phpfox::isUser(true);

        $aCopyUserInfoStatus = Phpfox::getService('user.export')->getUserInfoStatus();
        $aCopyUserDataStatus = Phpfox::getService('user.export')->getUserDataStatus();
        if (($aVals = $this->request()->getArray('val'))) {
            $hash = Phpfox::getService('user.export')->doExport($aVals);
            $this->url()->send('user.export', ['download' => $hash]);
        }

        if ($sDownload = $this->request()->get('download')) {
            Phpfox::getLib('archive.export')->download('my_data', 'zip', $sDownload);
            exit;
        }

        $this->template()->setTitle(_p('download_a_copy_of_your_data'))
            ->setBreadCrumb(_p('download_a_copy_of_your_data'))
            ->assign([
                'aCopyUserInfoStatus' => $aCopyUserInfoStatus,
                'aCopyUserDataStatus' => $aCopyUserDataStatus,
            ]);
    }

    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('user.component_controller_export_clean')) ? eval($sPlugin) : false);
    }
}
