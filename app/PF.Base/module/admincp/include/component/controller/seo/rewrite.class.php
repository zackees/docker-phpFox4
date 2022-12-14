<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 * Add a new setting from the Admin CP
 * 
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		phpFox LLC
 * @package  		Module_Admincp
 * @version 		$Id: meta.class.php 5936 2013-05-15 08:16:34Z phpFox LLC $
 */
class Admincp_Component_Controller_Seo_Rewrite extends Phpfox_Component 
{
	/**
	 * Controller
	 */
	public function process()
	{
		$aRewrites = Phpfox::getService('core.redirect')->getRewrites();
		$jRewrites = json_encode($aRewrites);
		$this->template()->setTitle(_p('rewrite_url'))
			->setBreadCrumb(_p('rewrite_url'), $this->url()->makeUrl('admincp.seo.rewrite'))
			->setHeader(array(
				'rewrite.js' => 'module_admincp',
				'rewrite.css' => 'module_admincp'				
			))
			->setPhrase(array(
				'original_url',
				'replacement_url'
			))
			->assign(array(
					'jRewrites' => $jRewrites
			)
		);
	}	
	
	/**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('admincp.component_controller_seo_meta_clean')) ? eval($sPlugin) : false);
	}	
}