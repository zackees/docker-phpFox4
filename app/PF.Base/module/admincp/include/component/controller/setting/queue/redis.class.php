<?php

/**
 * Class Admincp_Component_Controller_Setting_Queue_Redis
 * @since 4.8.0
 * @author phpfox
 */
class Admincp_Component_Controller_Setting_Queue_Redis extends Phpfox_Component
{
    const SERVICE_ID = 'redis';
    const DEFAULT_PORT = 6379;
    const DEFAULT_DATABASE_INDEX = 3;

    public function process()
    {
        $sError = null;
        $manager = Phpfox::getLib('job.admincp');
        $queue_id = $this->request()->get('queue_id');

        $passDependencies = extension_loaded('redis') && version_compare(phpversion(), '7.0', '>');

        if ($this->request()->method() === 'POST') {
            $aVals = $this->request()->get('val');
            $bIsActive = !!$aVals['is_active'];

            $bIsValid = true;
            $config = [
                'password' => $aVals['password'],
                'host' => $aVals['host'],
                'port' => isset($aVals['port']) && is_numeric($aVals['port']) && (int)$aVals['port'] >= 0 ? (int)$aVals['port'] : self::DEFAULT_PORT,
                'database' => isset($aVals['database']) && is_numeric($aVals['database']) && (int)$aVals['database'] >= 0 && (int)$aVals['database'] <= 15 ? $aVals['database'] : self::DEFAULT_DATABASE_INDEX,
            ];

            if ($bIsActive) {
                try {
                    /*$bIsValid = $manager->verifyQueueConfig(self::SERVICE_ID, $config);
                    if (!$bIsValid) {
                        $sError = _p('invalid_configuration');
                    }*/
                    $bIsValid = true;
                } catch (Exception $exception) {
                    $bIsValid = false;
                    $sError = $exception->getMessage();
                }
            }

            if ($bIsValid) {
                $manager->updateQueueConfig($queue_id, self::SERVICE_ID, $bIsActive, $config);
                Phpfox::addMessage(_p('Your changes have been saved!'));
            }
        } else {
            $aVals = $manager->getQueueConfig($queue_id, self::SERVICE_ID);
            if (!isset($aVals['port'])) {
                $aVals['port'] = (string)self::DEFAULT_PORT;
            }
            $aVals['port'] = (string)$aVals['port'];
            if (!isset($aVals['database'])) {
                $aVals['database'] = self::DEFAULT_DATABASE_INDEX;
            }
        }


        $this->template()->clearBreadCrumb()
            ->setTitle(_p('message_queue'))
            ->setBreadCrumb(_p('message_queue'))
            ->setActiveMenu('admincp.setting.queue')
            ->assign([
                'aForms' => $aVals,
                'sError' => $sError,
                'passDependencies' => $passDependencies
            ]);
    }
}