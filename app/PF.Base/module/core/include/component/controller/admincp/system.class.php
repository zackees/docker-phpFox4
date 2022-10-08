<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Controller_Admincp_System extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{
		$this->template()->setTitle(_p('system_overview'))
			->setBreadCrumb(_p('tools'))
            ->setActiveMenu('admincp.maintain.system')
			->setSectionTitle(_p('system_overview'))
			->assign(array(
					'aStats' => Phpfox::getService('core.system')->get()
				)
			);
	}
	
	/**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('core.component_controller_system_clean')) ? eval($sPlugin) : false);
	}
}