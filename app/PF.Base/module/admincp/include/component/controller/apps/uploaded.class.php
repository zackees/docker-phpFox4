<?php

defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Admincp_Component_Controller_Apps_index
 */
class Admincp_Component_Controller_Apps_Uploaded extends Phpfox_Component
{
	public function process()
	{
		$warnings = [];
		$cmd = $this->request()->get('cmd');
		$appId = $this->request()->get('apps_id');
		$appDir = $this->request()->get('apps_dir');
		$showAll = $this->request()->get('show_all', '0');
		$cmdResult = false;

		try {
			if ($cmd && $appId && $appDir) {
				$cmdResult = Phpfox::getService('admincp.process')->processUploadedApp($cmd, 'app', $appId, $appDir);
			}
		} catch (Exception $exception) {
			$warnings[] = $exception->getMessage();
		}

		if ($cmdResult) {
			Phpfox::getLib('cache')->remove();
			$this->url()->send($this->url()->makeUrl('admincp.apps.uploaded', ['show_all' => $showAll]));
		}
		$uploadedApps = Phpfox::getService('admincp.apps')->getUploadedApps($showAll);
		$this->template()
			->setSectionTitle(_p('apps'))
			->setTitle(_p('Uploaded Apps'))
			->setBreadCrumb(_p('Uploaded Apps'))
			->setActiveMenu('admincp.apps.uploaded')
			->assign([
				'showAll' => $showAll,
				'uploadedApps' => $uploadedApps,
				'bShowClearCache' => true,
				'warning' => implode('<br />', $warnings)
			]);
	}
}