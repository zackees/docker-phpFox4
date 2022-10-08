<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		phpFox LLC
 * @package 		Phpfox_Ajax
 */
class Admincp_Component_Ajax_Ajax extends Phpfox_Ajax
{
    public function getComponentsForController()
    {
        Phpfox::isAdmin(true);

        $sController = $this->get('controller');
        Phpfox::getLib('template')->assign([
            'aComponents' => Phpfox::getService('admincp.component')->getComponentsByController(!empty($sController) ? $sController : ''),
        ])->getTemplate('admincp.block.block.component-options');

        $this->call('($("#component").selectize())[0].selectize.destroy();');
        $this->html('select#component', $this->getContent(false));
        $this->call('$Behavior.initSelectize();');
    }

    public function updateCronActivity()
    {
        Phpfox::isAdmin(true);

        $cronId = $this->get('id');
        $active = $this->get('active', 1);

        if (empty($cronId)) {
            return false;
        }

        Phpfox::getService('admincp.cron.process')->updateActivity($cronId, $active);
    }

    public function loadLatestAlerts()
    {
        Phpfox::isAdmin(true);
        $badge = Phpfox::getService('admincp.alert')->getAdminMenuBadgeNumber();
        if ($badge > 99) {
            $badge = '99+';
        }
        $aLastestAlerts = Phpfox::getService('admincp.alert')->getItems();
        $sContent = '<div class="js_admincp_alert_information"><span class="item-alert">'._p ('alerts').'('.(!empty($badge) ? $badge : 0).')</span>
                                <span class="item-viewall">
                                    <a href="'.Phpfox_Url::instance()->makeUrl('admincp.alert').'">'._p('view_all').'</a>
                                </span>
                            </div>';
        if(!empty($aLastestAlerts))
        {
            $aLastestAlerts = array_slice($aLastestAlerts,0, 10);
            $sContent.= '<div class="alert-item-container">';
            foreach($aLastestAlerts as $aAlert)
            {
                $sContent.= '<div class="js_alert_item"><a  target="'.(isset($aAlert['target']) ? $aAlert['target'] : '_blank').'" href="'.$aAlert['link'].'">'.$aAlert['message'].'</a></div>';

            }
            $sContent.= '</div>';
        }
        else
        {
            $sContent.= '<div class="js_alert_notice alert-empty-info">'._p('It looks like you have no alert message at this time').'</div>';
        }
        $this->html('#js_admincp_alert_panel', $sContent);
        $this->call('$("#js_admincp_alert_panel").addClass("built");');
    }


    public function deleteMeta()
	{
		Phpfox::isAdmin(true);
		
		foreach ((array) $this->get('id') as $iId)  {
            Phpfox::getService('admincp.seo.process')->deleteMeta($iId);
			$this->remove('#js_id_row_' . $iId);			
		}
		$this->call('$(\'#js_check_box_all\').attr(\'checked\', false);');
	}	
	
	public function addMeta()
	{
		Phpfox::isAdmin(true);
		
		if (($iId = Phpfox::getService('admincp.seo.process')->addMeta($this->get('val'))))
		{
			$aVals = $this->get('val');	
			
			$sHtml = '<tr class="js_nofollow_row is_new_row" id="js_id_row_' . $iId. '">';
			$sHtml .= '<td><input type="checkbox" name="id[]" class="checkbox" value="' . $iId. '" id="js_id_row' . $iId. '" /></td>';
			$sHtml .= '<td>' . ($aVals['type_id'] == '1' ? _p('description') : ($aVals['type_id'] == '2' ? _p('title') : _p('keyword'))) . '</td>';
			$sHtml .= '<td>' . Phpfox::getService('admincp.seo')->getUrl($aVals['url']) . '</td>';
			$sHtml .= '<td><textarea name="val[' . $iId. '][content]" cols="30" rows="4" style="height:30px;">' . $aVals['content'] . '</textarea></td>';
			$sHtml .= '<td>' . Phpfox::getLib('date')->convertTime(PHPFOX_TIME) . '</td>';
			$sHtml .= '</tr>';
			
			$this->call('$(\'#js_meta_form\')[0].reset();');
			$this->show('#js_meta_holder');
			$this->append('#js_meta_holder_table', $sHtml);
			$this->call('var bHasTrClass = false; $(\'.js_nofollow_row\').each(function(){ if ($(this).hasClass(\'is_new_row\')) { $(this).removeClass(\'is_new_row\'); return false; } if ($(this).hasClass(\'tr\')) { bHasTrClass = true; } else { bHasTrClass = false; } }); if (!bHasTrClass) { $(\'#js_id_row_' . $iId. '\').addClass(\'tr\'); }');
			
			$this->alert(_p('successfully_added_a_new_custom_element_dot'));
		}		
	}
	
	public function nofollow()
	{
		Phpfox::isAdmin(true);
		
		if (($iId = Phpfox::getService('admincp.seo.process')->addNoFollow($this->get('val'))))
		{
			$aVals = $this->get('val');
			
			$sHtml = '<tr class="js_nofollow_row is_new_row" id="js_id_row_' . $iId. '">';
			$sHtml .= '<td><input type="checkbox" name="id[]" class="checkbox" value="' . $iId. '" id="js_id_row' . $iId. '" /></td>';
			$sHtml .= '<td>' . Phpfox::getService('admincp.seo')->getUrl($aVals['url']) . '</td>';
			$sHtml .= '<td>' . Phpfox::getLib('date')->convertTime(PHPFOX_TIME) . '</td>';
			$sHtml .= '</tr>';
			
			$this->val('#js_nofollow_url', '');
			$this->show('#js_nofollow_holder');
			$this->append('#js_nofollow_holder_table', $sHtml);
			$this->call('var bHasTrClass = false; $(\'.js_nofollow_row\').each(function(){ if ($(this).hasClass(\'is_new_row\')) { $(this).removeClass(\'is_new_row\'); return false; } if ($(this).hasClass(\'tr\')) { bHasTrClass = true; } else { bHasTrClass = false; } }); if (!bHasTrClass) { $(\'#js_id_row_' . $iId. '\').addClass(\'tr\'); }');
			
			$this->alert(_p('successfully_added_a_new_url'));
		}
	}
	
	public function deleteNoFollow()
	{
		Phpfox::isAdmin(true);
		
		foreach ((array) $this->get('id') as $iId)
		{
            Phpfox::getService('admincp.seo.process')->deleteNoFollow($iId);
			$this->remove('#js_id_row_' . $iId);			
		}
		$this->call('$(\'#js_check_box_all\').attr(\'checked\', false);');
	}
	
	public function buildSearchValues()
	{
		Phpfox::isUser(true);
		Phpfox::getUserParam('admincp.has_admin_access', true);		
		
		$this->call('aAdminCPSearchValues = ' . json_encode(Phpfox::getService('admincp.setting')->getForSearch()) . ';');
		$this->call('$("#admincp_search_input").keyup();');
	}
	
	public function updateBlockActivity()
	{		
		if (Phpfox::getService('admincp.block.process')->updateActivity($this->get('id'), $this->get('active')))
		{
			
		}
	}	
	
	public function blockOrdering()
	{
		Phpfox::isUser(true);
		Phpfox::getUserParam('admincp.has_admin_access', true);
		if ($aVals = $this->get('val'))
		{
			if (Phpfox::getService('admincp.block.process')->updateOrder($aVals['ordering'], (isset($aVals['style_id']) ? (int) $aVals['style_id'] : null)))
			{

			}			
		}		
	}
	
	public function getBlocks()
	{
		Phpfox::isUser(true);
		Phpfox::getUserParam('admincp.has_admin_access', true);
		Phpfox::getBlock('admincp.block.setting');		

		$this->html('#js_setting_block', $this->getContent(false));
		$this->show('#content_editor_text');
		$this->show('#js_editing_block');
		$this->html('#js_editing_block_text', ($this->get('m_connection') == '' ? _p('site_wide') : $this->get('m_connection')));
		$this->call('$.scrollTo(0);');		
		$this->call('$Core.loadInit();');
		$this->call('Core_drag.init({table: \'.js_drag_drop\', ajax: \'admincp.blockOrdering\'});');
	}
	
	public function removeSettingFromArray()
	{
		Phpfox::isUser(true);
		Phpfox::getUserParam('admincp.has_admin_access', true);
        Phpfox::getService('admincp.setting.process')->removeSettingFromArray($this->get('setting'), $this->get('value'));
	}
	
	public function checkProductVersions()
	{
		Phpfox::getService('admincp.product.process')->checkProductVersions();
	}
	
	public function updateModuleActivity()
	{
		Phpfox::getService('admincp.module.process')->updateActivity($this->get('id'), $this->get('active'));
	}

	public function updateMenuActivity()
	{
		Phpfox::getService('admincp.menu.process')->updateActivity($this->get('id'), $this->get('active'));
	}
	
	public function componentFeedActivity()
	{
        Phpfox::getService('admincp.component.process')->updateActivity($this->get('id'), $this->get('active'));
	}

}