<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 * 
 * 
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		Miguel Espinoza
 * @package  		Module_Contact
 * @version 		$Id: ajax.class.php 4921 2012-10-22 13:47:30Z phpFox LLC $
 */
class Contact_Component_Ajax_Ajax extends Phpfox_Ajax
{

	public function manageOrdering()
	{
		Phpfox::isAdmin(true);
		$aVals = $this->get('val');
		Phpfox::getService('contact.process')->updateOrdering($aVals['ordering']);
		$this->call('$Core.addNoticeMessage("' . _p('order_updated') . '");');
	}
	
	public function showQuickContact()
	{
		$iUserId = Phpfox::getParam('pages.admin_in_charge_of_page_claims');
		if (empty($iUserId))
		{
			return Phpfox_Error::display(_p('no_admin_has_been_set_to_handle_this_type_of_issues'));
		}
		
		Phpfox::getComponent('mail.compose', array('claim_page' => true, 'page_id' => $this->get('page_id'), 'id' => $iUserId), 'controller');
        return null;
	}
}