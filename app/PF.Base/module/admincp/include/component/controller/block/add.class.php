<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 *
 *
 * @copyright       [PHPFOX_COPYRIGHT]
 * @author          phpFox LLC
 * @package         Module_Admincp
 */
class Admincp_Component_Controller_Block_Add extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        Phpfox::getUserParam('admincp.can_add_new_block', true);
        $bIsEdit = false;

        if (($iEditId = $this->request()->getInt('id')) || ($iEditId = $this->request()->getInt('block_id'))) {
            $aRow = Phpfox::getService('admincp.block')->getForEdit($iEditId);
            $bIsEdit = true;
            $this->template()->assign(array(
                    'aAccess' => (empty($aRow['disallow_access']) ? null : unserialize($aRow['disallow_access']))
                )
            );
        } else {
            $aRow['m_connection'] = $this->request()->get('m_connection');
        }

        if (empty($aRow['m_connection'])) {
            $aRow['m_connection'] = 'site_wide';
        }

        $bIsSiteWide = $aRow['m_connection'] == 'site_wide';

        $aValidation = array(
            'title' => [
                'def' => 'required',
                'title' => _p('block_title_is_required')
            ],
            'type_id' => [
                'def' => 'required',
                'title' => _p('block_type_is_required')
            ],
        );

        $oValid = Phpfox_Validator::instance()->set(array('sFormName' => 'js_form', 'aParams' => $aValidation));

        if ($aVals = $this->request()->getArray('val')) {
            if ($aVals['type_id'] === '0') {
                $aValidation['component'] = [
                    'def' => 'required',
                    'title' => _p('component_is_required')
                ];
                $oValid = Phpfox_Validator::instance()->set(array('sFormName' => 'js_form', 'aParams' => $aValidation));
            }
            if ($oValid->isValid($aVals)) {
                if ($bIsEdit) {
                    $sMessage = _p('successfully_updated');
                    Phpfox::getService('admincp.block.process')->update($aRow['block_id'], $aVals);
                } else {
                    $sMessage = _p('block_successfully_added');
                    Phpfox::getService('admincp.block.process')->add($aVals);
                }

                $aUrl = array(
                    'block',
                    'm_connection' => empty($aVals['m_connection']) ? 'site_wide' : $aVals['m_connection']
                );

                $this->url()->send('admincp', $aUrl, $sMessage);
            }
        }

        $aController = Phpfox::getService('admincp.component')->get(true);
        if (isset($aController['page']) && count($aController['page']) > 1) {
            $activeStaticPages = Phpfox::getService('page')->getActive('p.title_url AS url, p.title');
            if (!empty($activeStaticPages)) {
                $activeStaticPages = array_combine(array_column($activeStaticPages, 'url'), array_column($activeStaticPages, 'title'));
            }
            $parsedPageControllers = [];
            foreach ($aController['page'] as $key => $controllerItem) {
                if ($controllerItem['component'] == 'view') {
                    $parsedPageControllers = array_merge([$controllerItem], $parsedPageControllers);
                } else {
                    if (!isset($activeStaticPages[$controllerItem['component']])) {
                        unset($aController['page'][$key]);
                        continue;
                    }
                    $parsedPageControllers[] = array_merge($controllerItem, [
                        'title' => Phpfox::getLib('parse.output')->clean($activeStaticPages[$controllerItem['component']]),
                    ]);
                }
            }
            $aController['page'] = $parsedPageControllers;
        }

        $aComponents = Phpfox::getService('admincp.component')->getComponentsByController(!empty($aRow['m_connection']) ? $aRow['m_connection'] : '');

        $this->template()->assign(array(
            'aProducts' => Phpfox::getService('admincp.product')->get(),
            'aControllers' => $aController,
            'aComponents' => $aComponents,
            'aUserGroups' => Phpfox::getService('user.group')->get(),
            'sCreateJs' => $oValid->createJS(),
            'sGetJsForm' => $oValid->getJsForm(),
            'bIsEdit' => $bIsEdit,
            'aForms' => $aRow,
            'bIsSiteWide' => $bIsSiteWide,
        ))
            ->setTitle(_p('block_manager'))
            ->setBreadCrumb(_p('block_manager'), $this->url()->makeUrl('admincp.block'))
            ->setBreadCrumb(($bIsEdit ? _p('editing') . ': ' . ($bIsSiteWide ? _p('site_wide') : $aRow['m_connection']) . (empty($aRow['component']) ? '' : '::' . rtrim(str_replace('|',
                        '::', $aRow['component']),
                        '::')) . (empty($aRow['title']) ? '' : ' (' . Phpfox_Locale::instance()->convert($aRow['title']) . ')') : _p('add_new_block')),
                $this->url()->makeUrl('admincp.block.add'), true)
            ->setActiveMenu('admincp.appearance.block')
            ->setTitle(_p('add_new_block'));
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('admincp.component_controller_block_add_clean')) ? eval($sPlugin) : false);
    }
}
