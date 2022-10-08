<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 * 
 * 
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		phpFox LLC
 * @package  		Module_Admincp
 * @version 		$Id: cron.class.php 1496 2010-03-05 17:15:05Z phpFox LLC $
 */
class Admincp_Service_Cron_Cron extends Phpfox_Service 
{
	/**
	 * Class constructor
	 */	
	public function __construct()
	{	
		$this->_sTable = Phpfox::getT('cron');
	}

    /**
     * Get cron listing for AdminCP
     * @return array|bool|int|resource|string
     * @throws Exception
     */
    public function getAll()
    {
        $cacheObject = $this->cache();
        $cacheId = $cacheObject->set('core_admincp_cron_manager');

        if (($items = $cacheObject->get($cacheId)) === false) {
            $items = db()->select('cron_id, name, type_id, every, php_code, is_active, next_run, module_id')
                ->from($this->_sTable)
                ->executeRows();

            foreach ($items as $key => $item) {
                if ((!isset($item['name']) || $item['name'] == '') && preg_match('/->([^\(]+)\(/', $item['php_code'], $match) && strlen($match[1])) {
                    $tempName = $match[1];
                    $tempName[0] = strtoupper($tempName[0]);
                    $characters = [];
                    $tempString = '';
                    for($i = 0; $i < strlen($tempName); $i++) {
                        if ($tempName[$i] == '') {
                            continue;
                        }
                        if (strtoupper($tempName[$i]) == $tempName[$i] && $i > 0) {
                            $characters[] = $tempString;
                            $tempString = '';
                        }

                        $tempString .= $tempName[$i];
                    }
                    if ($tempString != '') {
                        $characters[] = $tempString;
                    }
                    if (!empty($characters)) {
                        $item['name'] = Phpfox::getLib('parse.output')->clean(implode(' ', $characters));
                    }
                } elseif (\Core\Lib::phrase()->isPhrase($item['name'])) {
                    $item['name'] = _p($item['name']);
                }
                if (!isset($item['name']) || $item['name'] == '') {
                    $item['name'] = ucfirst($item['module_id']);
                }

                switch ($item['type_id']) {
                    case 1:
                        $frequencyPhrase = $item['every'] == 1 ? 'cron_frequency_minute_number' : 'cron_frequency_minutes_number';
                        break;
                    case 2:
                        $frequencyPhrase = $item['every'] == 1 ? 'cron_frequency_hour_number' : 'cron_frequency_hours_number';
                        break;
                    case 3:
                        $frequencyPhrase = $item['every'] == 1 ? 'cron_frequency_day_number' : 'cron_frequency_days_number';
                        break;
                    default:
                        $frequencyPhrase = null;
                        break;
                }

                if (!empty($frequencyPhrase)) {
                    $item['frequency_phrase'] = $frequencyPhrase;
                }

                $items[$key] = $item;
            }

            $cacheObject->save($cacheId, $items);
        }

        if (!empty($items)) {
            $nextRuns = db()->select('cron_id, next_run')
                ->from($this->_sTable)
                ->where([
                    'cron_id' => ['in' => implode(',', array_column($items, 'cron_id'))],
                    'is_active' => 1,
                ])->executeRows();
            if (!empty($nextRuns)) {
                $nextRuns = array_combine(array_column($nextRuns, 'cron_id'), array_column($nextRuns, 'next_run'));
            }

            $corePhrases = [
                'mobile' => 'mobile_api'
            ];

            foreach ($items as $key => $item) {
                if (isset($nextRuns[$item['cron_id']])) {
                    $item['next_run'] = $nextRuns[$item['cron_id']];
                }
                if ($item['next_run'] == 0) {
                    $item = array_merge($item, [
                        'next_run_text' => _p('core_never_run_before'),
                        'is_error' => true,
                    ]);
                } else {
                    if ($item['next_run'] == PHPFOX_TIME) {
                        $item['next_run_text'] = _p('core_running');
                    } else {
                        $item['next_run_text'] = Phpfox::getTime('F j, Y H:i', $item['next_run']);
                    }
                    if ($item['next_run'] < PHPFOX_TIME) {
                        $item['is_error'] = true;
                    }
                }

                $item['app_name'] = isset($corePhrases[$item['module_id']]) ? _p($corePhrases[$item['module_id']]) : (\Core\Lib::phrase()->isPhrase('module_' . $item['module_id']) ? _p('module_' . $item['module_id']) : (\Core\Lib::phrase()->isPhrase($item['module_id']) ? _p($item['module_id']) : $item['module_id']));

                $items[$key] = $item;
            }
        }

        return $items;
    }

    /**
     * @param string      $sProductId
     * @param null|string $sModuleId
     *
     * @return bool
     */
	public function export($sProductId, $sModuleId = null)
	{
		$aSql = array();
		if ($sModuleId !== null)
		{
			$aSql[] = "cron.module_id = '" . $sModuleId . "' AND";
		}
		$aSql[] = "cron.product_id = '" . $sProductId . "'";
		
		$aRows = $this->database()->select('cron.*, product.title AS product_name')
			->from($this->_sTable, 'cron')
			->leftJoin(Phpfox::getT('product'), 'product', 'product.product_id = cron.product_id')
			->where($aSql)
			->execute('getSlaveRows');
        
        if (!isset($aRows[0]['product_name'])) {
            return Phpfox_Error::set(_p('product_does_not_have_any_settings'));
        }
        
        if (!count($aRows)) {
            return false;
        }
		
		$oXmlBuilder = Phpfox::getLib('xml.builder');
		$oXmlBuilder->addGroup('crons');
        
        foreach ($aRows as $aRow) {
            $oXmlBuilder->addTag('cron', $aRow['php_code'], [
                    'module_id' => $aRow['module_id'],
                    'type_id'   => $aRow['type_id'],
                    'every'     => $aRow['every']
                ]);
        }
        $oXmlBuilder->closeGroup();
				
		return true;
	}
    
    /**
     * If a call is made to an unknown method attempt to connect
     * it to a specific plug-in with the same name thus allowing
     * plug-in developers the ability to extend classes.
     *
     * @param string $sMethod    is the name of the method
     * @param array  $aArguments is the array of arguments of being passed
     *
     * @return null
     */
	public function __call($sMethod, $aArguments)
	{
		/**
		 * Check if such a plug-in exists and if it does call it.
		 */
		if ($sPlugin = Phpfox_Plugin::get('admincp.service_cron_cron__call'))
		{
			return eval($sPlugin);
		}
			
		/**
		 * No method or plug-in found we must throw a error.
		 */
		Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
        return null;
	}
}