<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 *
 *
 * @copyright        [PHPFOX_COPYRIGHT]
 * @author        phpFox LLC
 * @package        Module_Admincp
 * @version        $Id: index.class.php 7202 2014-03-18 13:38:56Z phpFox LLC $
 */
class Admincp_Component_Controller_Environment extends Phpfox_Component
{
	public function process()
	{
		$sEnvironmentVariables = var_export(Phpfox::getLib('setting')->getFromServerConfigFile(null),true);
		$this->template()
			->setTitle('Environment Variables')
			->setBreadCrumb('Environment Variables')
			->assign([
				'sEnvironmentVariables'=> $sEnvironmentVariables
			]);
	}
}