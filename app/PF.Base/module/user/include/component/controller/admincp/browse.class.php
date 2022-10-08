<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Controller_Admincp_Browse
 */
class User_Component_Controller_Admincp_Browse extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{
		define('PHPFOX_IS_ADMIN_SEARCH', true);

		$this->template()->setPhrase(['process_message', 'error_message', 'download', 'upload', 'start'])
            ->setHeader('cache',[
                '<script type="text/javascript">window.isInAdmincpUserBrowse = true; window.sExportUsersUrl = "' . $this->url()->makeUrl('admincp.user.exportusers') .'";window.sImportUsersUrl = "' . $this->url()->makeUrl('admincp.user.importusers') .'";</script>'
            ])
            ->setTitle(_p('browse_members'))
            ->setSectionTitle(_p('browse_members'))
            ->setActiveMenu('admincp.member.browse');
		
		return Phpfox_Module::instance()->setController('user.browse');
	}
	
	/**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('user.component_controller_admincp_browse_clean')) ? eval($sPlugin) : false);
	}
}
