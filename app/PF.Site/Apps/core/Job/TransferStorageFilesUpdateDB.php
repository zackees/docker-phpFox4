<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Apps\Phpfox_Core\Job;

use Core\Queue\JobAbstract;
use Phpfox;

class TransferStorageFilesUpdateDB extends JobAbstract
{
    /**
     * Perform a job item
     */
    public function perform()
    {
        $params = $this->getParams();
        $storageId = $params['storage_id'];
        $uniqId = $params['uniqId'];
        $cache = storage()->get('core_storage_transfer_files_params');
        if ($cache) {
            if ($cache->value->status != 'stopped' && $cache->value->uniq_id == $uniqId) {
                try {
                    //Update db
                    Phpfox::getLib('storage.admincp')->transferFileUpdateDatabase($uniqId, $storageId);
                } catch (\Exception $exception) {
                    Phpfox::getLog('storage.log')->error($exception->getMessage());
                }
            }
        }
        $this->delete();
    }
}