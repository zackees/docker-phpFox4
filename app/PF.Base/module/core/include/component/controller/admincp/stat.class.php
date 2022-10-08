<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Controller_Admincp_Stat extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{
		$iDayStart = $this->request()->getInt('ds');
		$iMonthStart = $this->request()->getInt('ms');
		$iYearStart = $this->request()->getInt('ys');
		
		$iDayEnd = $this->request()->getInt('de');
		$iMonthEnd = $this->request()->getInt('me');
		$iYearEnd = $this->request()->getInt('ye');		
		
		$sStartTime = '';
		$sEndTime = '';
		$aStats = array();
		if (!empty($iDayStart) && !empty($iDayEnd))
		{
			$iStartTime = mktime(0, 0, 0, $iMonthStart, $iDayStart, $iYearStart);
			$iEndTime = mktime(0, 0, 0, $iMonthEnd, $iDayEnd, $iYearEnd);
			$sStartTime = date('F j, Y', $iStartTime);
			$sEndTime = date('F j, Y', $iEndTime);
			
			$aStats = Phpfox::getService('core.stat')->getSiteStatsForAdmin($iStartTime, $iEndTime);
		}
		
		$this->template()->setTitle(_p('site_statistics'))
			->setBreadCrumb(_p('site_statistics'))
            ->setActiveMenu('admincp.maintain.stat')
			->assign(array(
					'aStats' => $aStats,
					'sStartTime' => $sStartTime,
					'sEndTime' => $sEndTime
				)
			);
	}
	
	/**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('core.component_controller_admincp_stat_clean')) ? eval($sPlugin) : false);
	}
}