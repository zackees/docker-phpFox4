<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Block_Template_Menufooter extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{
        $footerMenus = $this->template()->getMenu('footer');
        foreach ($footerMenus as $key => $footerMenu) {
            if ($footerMenu['module'] == 'invite' &&
                (!Phpfox::isModule('invite') || !Phpfox::isUser() || !Phpfox::getService('invite')->canShowInviteMenu())) {
                unset($footerMenus[$key]);
            }
        }

        $this->template()->assign([
            'aFooterMenu' => $footerMenus,
        ]);
	}

	/**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('core.component_block_template-menufooter_clean')) ? eval($sPlugin) : false);
	}
}