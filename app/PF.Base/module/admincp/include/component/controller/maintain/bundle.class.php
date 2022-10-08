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
 * @version        $Id: cache.class.php 6584 2013-09-05 09:59:17Z phpFox LLC $
 */
class Admincp_Component_Controller_Maintain_Bundle extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{
		Phpfox::getUserParam('admincp.can_clear_site_cache', true);


		if ($this->request()->get('unlock')) {
			Phpfox::getLib('cache')->unlock();
			$this->url()->send('admincp.maintain.cache', null, _p('cache_system_unlocked'));
		}

		if ($this->request()->get('all')) {
			$oAssets = Phpfox::getLib('assets');
			$oAssets->bundleCssFile(PHPFOX_DIR_FILE . 'static' . PHPFOX_DS . 'autoload-' . Phpfox::getFullVersion() . '.css');
			$oAssets->bundleJsFile(PHPFOX_DIR_FILE . 'static' . PHPFOX_DS . 'autoload-' . Phpfox::getFullVersion() . '.js');
			if($returnUrl = $this->request()->get('return')){
				Phpfox::addMessage(_p('bundle_js_css_successful'));
				$this->url()->send(base64_decode($returnUrl));
			}
		}


		$this->template()
			->setActiveMenu('admincp.maintain.cache')
			->setTitle(_p('bundle_js_css'))
			->setBreadCrumb(_p('bundle_js_css'))
			->setSectionTitle(_p('bundle_js_css'))
			->setActionMenu([
				_p('clear_cache') => [
					'url' => $this->url()->makeUrl('admincp.maintain.cache', ['all' => true]),
					'class' => 'btn-danger',
					'custom' => 'data-caption="' . _p('clear_cache') . '"'
				]
			])
			->assign([
					'bShowClearCache' => true,
					'sMessage' => $sMessage,
					'bCacheLocked' => (file_exists(PHPFOX_DIR_CACHE . 'cache.lock') ? true : false),
					'sUnlockCache' => $this->url()->makeUrl('admincp.maintain.cache', ['unlock' => 'true'])
				]
			);
		return null;
	}
}