<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 * Run cron jobs and store a log of all the cron jobs being executed. All cron
 * jobs are executed via the file "include/cron/exec.php". The actual PHP code
 * that is being executed with each cron job is stored in the database in the table "cron".
 *
 * @copyright         [PHPFOX_COPYRIGHT]
 * @author            phpFox LLC
 * @package           Phpfox
 * @version           $Id: cron.class.php 1666 2010-07-07 08:17:00Z phpFox LLC $
 */
class Phpfox_Cron
{
    /**
     * Array of all the cron jobs we save into memory
     *
     * @var array
     */
    private $_aCrons;

    /**
     * Cache object
     *
     * @see Phpfox_Cache
     * @var object
     */
    private $_oCache;

    /**
     * Class Constructor
     * Cache all cron jobs and save into memory so we can call
     * them at a later point in time when needed
     *
     */
    public function __construct()
    {
        $this->_oCache = Phpfox::getLib('cache');

        $sCacheId = $this->_oCache->set('cron');

        if (!($this->_aCrons = $this->_oCache->get($sCacheId))) {
            $aRows = Phpfox_Database::instance()->select('cron.*')
                ->from(Phpfox::getT('cron'), 'cron')
                ->join(Phpfox::getT('product'), 'product', 'product.product_id = cron.product_id AND product.is_active = 1')
                ->join(Phpfox::getT('module'), 'm', 'm.module_id = cron.module_id AND m.is_active = 1')
                ->where('cron.is_active = 1')
                ->execute('getSlaveRows');

            foreach ($aRows as $aRow) {
                $this->_aCrons[ $aRow['cron_id'] ] = $aRow;
            }

            // Save cron jobs into cache
            $this->_oCache->save($sCacheId, $this->_aCrons);
            Phpfox::getLib('cache')->group('cron', $sCacheId);
        }

        (($sPlugin = Phpfox_Plugin::get('cron_construct')) ? eval($sPlugin) : false);
    }

    /**
     * @return array
     */
    public function getReadyJobs()
    {
        try {
            Phpfox_Database::instance()->beginTransaction();
            $aRows = Phpfox_Database::instance()->select('cron.*')
                ->from(Phpfox::getT('cron'), 'cron')
                ->join(Phpfox::getT('product'), 'product', 'product.product_id = cron.product_id AND product.is_active = 1')
                ->join(Phpfox::getT('module'), 'm', 'm.module_id = cron.module_id AND m.is_active = 1')
                ->where('cron.is_active = 1 AND next_run < ' . (time() + 55)) // plus 55 seconds to fix miss cron
                ->forUpdate()
                ->execute('getRows');
            foreach ($aRows as $key => $aRow) {
                if($aRow['module_id'] == 'mail' && !Phpfox::isAppActive('Core_Messages')) {
                    unset($aRows[$key]);
                    continue;
                }
                if(!empty($aRow['php_code'])) { // need check app disabled if have apps_alias
                    $isAppDisabled = Phpfox_Database::instance()->select('COUNT(*)')
                        ->from(':apps')
                        ->where('`apps_alias` = \'' . $aRow['module_id'] . '\' AND `is_active` = 0')
                        ->execute('getSlaveField');
                    if ($isAppDisabled) {
                        unset($aRows[$key]);
                        continue;
                    }
                }
                Phpfox_Database::instance()->update(Phpfox::getT('cron'), [
                    'last_run' => time(),
                    'next_run' => $this->_getNextRun($aRow['type_id'], $aRow['every']),
                ], 'cron_id=' . $aRow['cron_id']);
            }
            Phpfox_Database::instance()->commit();
        } catch (\Exception $ex) {
            Phpfox_Database::instance()->rollback();
            echo 'cron errors:  ', $ex->getMessage();
            \Phpfox::getLog('cron.log')->info($ex->getMessage());
            exit;
        }

        return array_map(function ($aRow){
            return $aRow['php_code'];
        }, $aRows);
    }

    /**
     * Run cron jobs
     *
     * @param int $iId Is the optional ID of the cron job
     *
     * @return bool|null
     */
    public function exec($iId = null)
    {
        if (is_bool($this->_aCrons)) {
            return false;
        }

        // Run a specific cron. Used via AdminCP or cli
        if ($iId && isset($this->_aCrons[ $iId ])) {
            // Run the PHP code
            eval($this->_aCrons[ $iId ]['php_code']);

            // Update the cron cache
            $this->_update($this->_aCrons[ $iId ]['cron_id'], $this->_getNextRun($this->_aCrons[ $iId ]['type_id'], $this->_aCrons[ $iId ]['every']));
        } else {
            // Get all the crons
            foreach ($this->_aCrons as $aCron) {
                // Make sure this cron needs to be executed
                if ($aCron['next_run'] < PHPFOX_TIME) {
                    // Get the cron file
                    //require_once(PHPFOX_DIR_CRON . 'source' . PHPFOX_DS . $aCron['file_name']);
                    eval($aCron['php_code']);

                    // Update the cron cache
                    $this->_update($aCron['cron_id'], $this->_getNextRun($aCron['type_id'], $aCron['every']));
                }
            }
        }

        (($sPlugin = Phpfox_Plugin::get('cron_exec')) ? eval($sPlugin) : false);

        return null;
    }

    /**
     * Get the next time a cron job must be executed
     *
     * @param int $iType  Is the type_id of the cron (1 (minute), 2 (hour), 3 (day), 4 (month) or 5 (yea))
     * @param int $iEvery Run every X type's
     *
     * @return int|bool Return the new time stamp so we can store into the db
     */
    private function _getNextRun($iType, $iEvery)
    {
        switch ($iType) {
            case 1: // Minute
                $iAddTime = ($iEvery * CRON_ONE_MINUTE);
                break;
            case 2: // Hour
                $iAddTime = ($iEvery * CRON_ONE_HOUR);
                break;
            case 3: // Day
                $iAddTime = ($iEvery * CRON_ONE_DAY);
                break;
            case 4: // Month
                break;
            case 5: // Year, Doubt we will use this

                break;
        }
        if (isset($iAddTime)) {
            return ($iAddTime + PHPFOX_TIME);
        } else {
            return false;
        }
    }

    /**
     * Update the cron job with the last time it was ran and the next time we need to run the cron.
     * We also add it into the cron log and clear the cron cache so a new cache can be created.
     *
     * @param int $iId   ID for the cron
     * @param int $iTime Time stamp of when the next run must be executed
     */
    private function _update($iId, $iTime)
    {
        // Update the time stamp for the current run and next run
        Phpfox_Database::instance()->update(Phpfox::getT('cron'), [
            'next_run' => $iTime,
            'last_run' => PHPFOX_TIME,
        ], 'cron_id = ' . (int)$iId);

        // Store into the log
        Phpfox_Database::instance()->insert(Phpfox::getT('cron_log'), [
            'cron_id'    => $iId,
            'time_stamp' => PHPFOX_TIME,
        ]);

        // Clear the cache
        $this->_oCache->remove('cron');
    }
}