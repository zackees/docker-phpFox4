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
 * @package  		Module_Theme
 * @version 		$Id: index.class.php 1179 2009-10-12 13:56:40Z phpFox LLC $
 */
class Theme_Component_Controller_Admincp_Index extends Phpfox_Component {
	public function process()
	{
		$themes = [];
		$default = [];
		$rows = $this->template()->theme()->all();
		foreach ($rows as $row) {
			if ($row->is_default) {
				$default = $row;

				continue;
			}
			$themes[] = $row;
		}

		if ($default) {
			$themes = array_merge([$default], $themes);
		}

		(($sPlugin = Phpfox_Plugin::get('theme.component_controller_admincp_index')) ? eval($sPlugin) : false);

		$this->template()->setTitle(_p('themes'))
			->setSectionTitle(_p('themes'))
            ->setActiveMenu('admincp.appearance.theme')
			->setActionMenu([
                _p('create_new_theme') => [
                    'url' => $this->url()->makeUrl('admincp.theme.add'),
                    'class' => 'popup light'
                ],
                _p('find_more_themes') => [
                    'url' => $this->url()->makeUrl('admincp.store', ['load' => 'themes']),
                    'class' => ''
                ]
			])
			->setBreadCrumb(_p('themes'), $this->url()->makeUrl('admincp.theme'))
			->assign(array(
					'themes' => $themes
				)
			);
	}
}