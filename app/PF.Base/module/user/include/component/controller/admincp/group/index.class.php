<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Controller_Admincp_Group_Index
 */
class User_Component_Controller_Admincp_Group_Index extends Phpfox_Component 
{
	/**
	 * Controller
	 */
	public function process()
	{	
		$this->template()
			->setTitle(_p('manage_user_groups'))
			->setBreadCrumb(_p('manage_user_groups'))
            ->setActiveMenu('admincp.member.group')
			->setActionMenu([
                _p('create_user_group') => [
					'class' => 'popup',
					'url' => $this->url()->makeUrl('admincp.user.group.add')
				]
			])
			->assign(array(
			    'bShowClearCache'=>true,
				'aGroups' => Phpfox::getService('user.group')->getForEdit(),
			)
		);
	}
}
