<?php

/**
 * Class Admincp_Component_Controller_Setting_Logger_Local
 * @since 4.8.0
 * @author phpfox
 */
class Admincp_Component_Controller_Setting_Logger_Local extends Phpfox_Component
{
    const SERVICE_ID = 'local';

    public function process()
    {
        $sError = null;
        $sLogDirectory = PHPFOX_DIR_FILE . 'log';

        /**
         * @var \Core\Log\Admincp
         */
        $logManager = Phpfox::getLib('log.admincp');

        if ($this->request()->method() === 'POST') {
            $aVals = $this->request()->get('val');
            $config = [
                'level' => $aVals['level'],
            ];

            try {
                $bIsValid = $logManager->verifyServiceConfig(self::SERVICE_ID, $config);
                if (!$bIsValid) {
                    $sError = _p('invalid_configuration');
                }
            } catch (Exception $exception) {
                $bIsValid = false;
                $sError = $exception->getMessage();
            }

            if ($bIsValid) {
                $logManager->updateServiceConfig(self::SERVICE_ID, !!$aVals['is_active'], $config);
                Phpfox::addMessage(_p('Your changes have been saved!'));
            }
        } else {
            $aVals = $logManager->getServiceConfig(self::SERVICE_ID);
        }


        $this->template()->clearBreadCrumb()
            ->setTitle(_p('log_handling'))
            ->setBreadCrumb(_p('log_handling'))
            ->setActiveMenu('admincp.setting.logger')
            ->assign([
                'aForms' => $aVals,
                'sLogDirectory' => $sLogDirectory,
                'sError' => $sError,
            ]);
    }
}