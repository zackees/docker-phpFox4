<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Apps\Phpfox_Core\Job;

use Core\Queue\JobAbstract;
use Phpfox;

class TransferAssetFiles extends JobAbstract
{
    /**
     * Perform a job item
     */
    public function perform()
    {
        $params = $this->getParams();
        $files = $params['files'];
        $storageId = $params['storage_id'];
        $iRemainFile = $params['remain_file'];
        $uniqid = $params['uniqid'];
        $cache = storage()->get('core_transfer_asset_uniq');
        $transferProcess = !empty($cache) ? (array)$cache->value : null;

        if (defined('PHPFOX_DEBUG') && PHPFOX_DEBUG) {
            Phpfox::getLog('cron.log')->debug('transfer files', ['files' => $files]);
        }

        if (!empty($transferProcess['uniqid']) && $transferProcess['uniqid'] == $uniqid) {
            try {
                Phpfox::getLib('assets')
                    ->transferAssetFiles($files, $storageId);
            } catch (\Exception $exception) {
                Phpfox::getLog('assets.log')->error($exception->getMessage());
            }
            if ($iRemainFile <= 0) {
                storage()->delById($cache->id);
            }
        }
        $this->delete();
    }
}