<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Controller_Index_Member extends Phpfox_Component 
{
	/**
	 * Controller
	 */
	public function process()
	{
		if ($sPlugin = Phpfox_Plugin::get('core.component_controller_index_member_start'))
		{
		    eval($sPlugin);
		}

		if ($this->request()->segment(1) != 'hashtag') {
			Phpfox::isUser(true);
		}
		
		$this->template()->setHeader('cache', array(
					'jquery/plugin/jquery.highlightFade.js' => 'static_script',
					'jquery/plugin/jquery.scrollTo.js' => 'static_script'
				)
			)
			->setEditor(array(
					'load' => 'simple'					
			)
		);
	}
}