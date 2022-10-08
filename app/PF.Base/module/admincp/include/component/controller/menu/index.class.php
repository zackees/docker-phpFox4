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
 * @version        $Id: index.class.php 6739 2013-10-07 14:14:51Z Fern $
 */
class Admincp_Component_Controller_Menu_Index extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        if ($iDeleteId = $this->request()->getInt('delete')) {
            $aMenu = Phpfox::getService('admincp.menu')->getForEdit($iDeleteId);
            if (Phpfox::getService('admincp.menu.process')->delete($iDeleteId)) {
                $this->url()->send('admincp.menu', !empty($aMenu['parent_id']) ? ['parent' => $aMenu['parent_id']] : null, _p('menu_successfully_deleted'));
            }
        }

        if ($aVals = $this->request()->getArray('val')) {
            if (Phpfox::getService('admincp.menu.process')->updateOrder($aVals)) {
                return [
                    'updated' => true
                ];
            }
        }

        $iParentId = $this->request()->getInt('parent');
        $aParentMenu = null;
        if ($iParentId > 0) {
            $aParentMenu = Phpfox::getService('admincp.menu')->getForEdit($iParentId);
            if (isset($aParentMenu['menu_id'])) {
                $this->template()->assign('aParentMenu', $aParentMenu);
            } else {
                $iParentId = 0;
            }
        }

        $aRows = Phpfox::getService('admincp.menu')->get(($iParentId > 0 ? ['menu.parent_id = ' . (int)$iParentId] : ['menu.parent_id = 0 AND menu.m_connection IN(\'main\', \'footer\')']));
        $aMenus = [];

        foreach ($aRows as $iKey => $aRow) {
            if (Phpfox::isModule($aRow['module_id'])) {
                $aMenus[$aRow['m_connection']][] = $aRow;
            }
        }
        unset($aRows);

        $this->template()->setBreadCrumb(_p('menu_manager'), $this->url()->makeUrl('admincp.menu'))
            ->setTitle(_p('menu_manager'))
            ->setSectionTitle(_p('menus'))
            ->setActionMenu([
                _p('add_menu') => [
                    'class' => 'popup',
                    'url'   => $this->url()->makeUrl('admincp.menu.add')
                ]
            ])
            ->setPhrase([
                'font_awesome_icon'
            ])
            ->setHeader(array(
                    'drag.js' => 'static_script',
                )
            )
            ->setActiveMenu('admincp.appearance.menu')
            ->assign(array(
                    'aMenus'    => $aMenus,
                    'iParentId' => $iParentId
                )
            );
        if (!empty($aParentMenu)) {
            $this->template()->setBreadCrumb(_p('manage_menu_name', ['name' => _p($aParentMenu['var_name'])]), $this->url()->makeUrl('admincp.menu', ['parent' => $iParentId]));
        }
        foreach ($aMenus as $sKey => $aValue) {
            $this->template()->setHeader('<script type="text/javascript">$Behavior.coreDragInit' . $sKey . ' = function() { Core_drag.init({table: \'#js_drag_drop_' . $sKey . '\', ajax: \'' . $this->url()->makeUrl('admincp.menu') . '\'}); }</script>');
        }
        return null;
    }
}