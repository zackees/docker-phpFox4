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
 * @package  		Module_Report
 * @version 		$Id: ajax.class.php 2525 2011-04-13 18:03:20Z phpFox LLC $
 */
class Report_Component_Ajax_Ajax extends Phpfox_Ajax
{	
	public function add()
	{		
		Phpfox::isUser(true);
		
		Phpfox::getBlock('report.add', array(
				'sType' => $this->get('type'),
				'iItemId' => $this->get('id')
			)
		);
	}
	
	public function insert()
	{
		Phpfox::isUser(true);

		if (Phpfox::getService('report.data.process')->add($this->get('report'), $this->get('type'), $this->get('id'), $this->get('feedback')))
		{
			 $this->call('tb_remove();');
		}
	}
	
	public function browse()
	{
		Phpfox::getBlock('report.browse');
	}

    public function deleteCategory()
    {
        Phpfox::getBlock('report.delete-category');
    }

    public function categoryOrdering()
    {
        if (Phpfox::getService('report.process')->updateOrder($this->get('val'))) {
            $this->call('$Core.addNoticeMessage("' . _p('order_updated') . '");');
        }
    }
}