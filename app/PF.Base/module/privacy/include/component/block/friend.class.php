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
 * @version 		$Id: friend.class.php 2621 2011-05-22 20:09:22Z phpFox LLC $
 */
class Privacy_Component_Block_Friend extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{
        if (Phpfox::isModule('friend')) {
            $aLists = Phpfox::getService('friend.list')->get();
        } else {
            $aLists = [];
        }

		$this->template()->assign(array(
				'aLists' => $aLists,
				'iNewListId' => (int) $this->getParam('list_id'),
				'sCustomPrivacyId' => $this->request()->get('custom-id'),
				'sPrivacyArray' => $this->request()->get('privacy-array'),
                'sPhraseCustomPrivacy' => html_entity_decode(_p('custom_privacy')),
                'sPhraseCreateFriendsList' => html_entity_decode(_p('create_friends_list'))
			)
		);	
	}
	
	/**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('privacy.component_block_friend_clean')) ? eval($sPlugin) : false);
	}
}