<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Apps\Phpfox_Core\Job;

use Core\Queue\JobAbstract;
use Phpfox;

class TransferStorageFiles extends JobAbstract
{
    /**
     * Perform a job item
     */
    public function perform()
    {
        $params = $this->getParams();
        $files = $params['files'];
        $storageId = $params['storage_id'];
        $uniqId = $params['uniqId'];
        $cache = storage()->get('core_storage_transfer_files_params');
        if ($cache) {
            if ($cache->value->status != 'stopped' && $cache->value->uniq_id == $uniqId) {
                try {
                    Phpfox::getLib('storage.admincp')->transferFiles($files, $storageId, $cache);
                } catch (\Exception $exception) {
                    Phpfox::getLog('storage.log')->error($exception->getMessage());
                }
            }
        }
        $this->delete();
    }
}