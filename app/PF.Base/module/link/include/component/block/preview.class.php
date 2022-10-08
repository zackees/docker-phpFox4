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
 * @package 		Phpfox_Component
 * @version 		$Id: preview.class.php 2294 2011-02-03 18:51:09Z phpFox LLC $
 */
class Link_Component_Block_Preview extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{
	    $value = !empty($this->request()->get('value')) ? $this->request()->get('value') : (!empty($this->getParam('value') ? $this->getParam('value') : ''));
		if (!($aLink = Phpfox::getService('link')->getLink($value)))
		{
			return false;
		}
		$this->template()->assign(array(
				'aLink' => $aLink	
			)
		);
        return null;
	}
	
	/**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('link.component_block_preview_clean')) ? eval($sPlugin) : false);
	}
}