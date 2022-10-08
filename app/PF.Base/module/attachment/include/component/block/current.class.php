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
 * @package  		Module_Attachment
 * @version 		$Id: current.class.php 877 2009-08-20 11:21:32Z phpFox LLC $
 */
class Attachment_Component_Block_Current extends Phpfox_Component 
{
	/**
	 * Controller
	 */
	public function process()
	{
		$sIds = $this->getParam('sIds');
		$sIds = rtrim($sIds, ',');

		list(, $aItems) = Phpfox::getService('attachment')->get('attachment.attachment_id IN(' . $sIds . ')',	'attachment.time_stamp ASC', false);
        
        $this->template()->assign([
            'aItems'           => $aItems,
            'sUrlPath'         => Phpfox::getParam('core.url_attachment'),
            'sThumbPath'       => Phpfox::getParam('core.url_thumb'),
            'bCanUseInline'    => $this->getParam('bCanUseInline'),
            'sAttachmentInput' => $this->getParam('sAttachmentInput')
        ]);
    }
    
    /**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('attachment.component_block_current_clean')) ? eval($sPlugin) : false);
	}
}