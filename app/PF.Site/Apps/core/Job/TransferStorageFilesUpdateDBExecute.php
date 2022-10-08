<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Apps\Phpfox_Core\Job;

use Core\Queue\JobAbstract;
use Phpfox;

class TransferStorageFilesUpdateDBExecute extends JobAbstract
{
    /**
     * Perform a job item
     */
    public function perform()
    {
        $params = $this->getParams();
        $sqlList = $params['sql_list'];
        $uniqId = $params['uniqId'];
        $cache = storage()->get('core_storage_transfer_files_params');
        if ($cache) {
            if ($cache->value->status != 'stopped' && $cache->value->uniq_id == $uniqId) {
                try {
                    foreach ($sqlList as $sql) {
                        \Phpfox_Database::instance()->query($sql);
                    }
                } catch (\Exception $exception) {
                    Phpfox::getLog('storage.log')->error($exception->getMessage());
                }
            }
        }
        $this->delete();
    }
}