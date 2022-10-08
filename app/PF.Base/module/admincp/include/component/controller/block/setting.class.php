<?php

class Admincp_Component_Controller_Block_Setting extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        Phpfox::getUserParam('admincp.can_add_new_block', true);
        $iBlockId = $this->request()->getInt('id');
        $sConnection = $this->request()->get('m_connection');
        $aBlock = db()->select('*')
            ->from(':block')
            ->where(['block_id' => $iBlockId])
            ->execute('getRow');

        if (empty($aBlock)) {
            return Phpfox_Error::set('Invalid Request');
        }

        $aSettings = [];
        $aValidation = [];
        $aInvalid = [];
        $oValidator = Phpfox_Validator::instance();

        if (!empty($aBlock['component'])) {
            $sBlockComponent = sprintf('%s.%s', $aBlock['module_id'],
                $aBlock['component']);

            $oBlockObject = Phpfox_Module::instance()
                ->getBlockObject($sBlockComponent);

            if (is_object($oBlockObject) and method_exists($oBlockObject, 'getSettings')) {
                $aSettings = $oBlockObject->getSettings();
            }

            if (is_object($oBlockObject) and method_exists($oBlockObject, 'getValidation')) {
                $aValidation = $oBlockObject->getValidation();
            }
        }

        if (!empty($aValidation)) {
            $oValidator->set(['sFormName' => 'js_form', 'aParams' => $aValidation]);
        }

        $aGlobalSettings = [
            [
                'info' => _p('visibility_based_on_device'),
                'description' => '',
                'value' => [],
                'var_name' => 'hidden_device',
                'type' => 'multi_checkbox',
                'options' => [
                    'hidden-desktop' => _p('hide_on_desktop'),
                    'hidden-tablet' => _p('hide_on_tablet'),
                    'hidden-mobile' => _p('hide_on_mobile'),
                ],
            ],
            [
                'info' => _p('visibility_based_on_screen_size'),
                'description' => '',
                'value' => [],
                'var_name' => 'hidden',
                'type' => 'multi_checkbox',
                'options' => [
                    'hidden-xs' => _p('hide_on_xs_screen_size'),
                    'hidden-sm' => _p('hide_on_sm_screen_size'),
                    'hidden-md' => _p('hide_on_md_screen_size'),
                    'hidden-lg' => _p('hide_on_lg_screen_size'),
                ],
            ],
            [
                'info' => _p('collapse_block'),
                'description' => '',
                'value' => [480, 767, 992],
                'var_name' => 'toggle',
                'type' => 'multi_checkbox',
                'options' => [
                    '480' => _p('collapse_on_screen_smaller_than_480px'),
                    '767' => _p('collapse_on_screen_between_480_and_767'),
                    '992' => _p('collapse_on_screen_between_767_and_992')
                ],
            ]
        ];

        (($sPlugin = Phpfox_Plugin::get('admincp.component_controller_block_setting')) ? eval($sPlugin) : false);

        $aSettings = array_merge($aGlobalSettings, $aSettings);
        // submit form
        if ($this->request()->get('cmd') == '_save') {
            $aVals = $this->request()->get('val');
            $aValues = !empty($aVals['value']) ? $aVals['value'] : [];
            $aDefaults = array_fill_keys(array_column($aSettings, 'var_name'), '');
            $aValues = array_merge($aDefaults, $aValues);

            if ($aValidation && !$oValidator->isValid($aVals['value'])) {
                $aInvalid = $oValidator->getInvalidate();
            } else {
                // save data to block params
                Phpfox::getLib('database')->update(':block', [
                    'params' => json_encode($aValues),
                ], ['block_id' => $iBlockId]);

                //clear cache for blocks
                $aUserGroups = Phpfox::getService('user.group')->getAll();
                foreach ($aUserGroups as $aUserGroup) {
                    Phpfox::getLib('cache')->remove([
                        'block',
                        'all_' . $aUserGroup['user_group_id'],
                    ]);
                }

                $aUrl = array(
                    'block',
                    'm_connection' => empty($aBlock['m_connection']) ? 'site_wide' : $aBlock['m_connection']
                );

                $this->url()->send('admincp', $aUrl, _p('successfully_updated'));
            }

            // keep value show
            foreach ($aSettings as $index => $row) {
                $type = isset($row['type']) ? $row['type'] : 'string';
                $name = $row['var_name'];
                if (isset($aValues[$name])) {
                    $aSettings[$index]['value'] = $aValues[$name];
                } elseif (in_array($type, ['multi_checkbox', 'array', 'currency', 'multi_text'])) {
                    $aSettings[$index]['value'] = [];
                } else {
                    $aSettings[$index]['value'] = '';
                }

                if (isset($aInvalid[$name])) {
                    $aSettings[$index]['error'] = $aInvalid[$name];
                }
            }
        } elseif (!empty($aBlock['params'])) {
            $aValues = json_decode($aBlock['params'], true);
            foreach ($aSettings as $index => $row) {
                $type = isset($row['type']) ? $row['type'] : 'string';
                $name = $row['var_name'];
                if (isset($aValues[$name])) {
                    $aSettings[$index]['value'] = $aValues[$name];
                } elseif (in_array($type, ['multi_checkbox', 'array', 'currency', 'multi_text'])) {
                    $aSettings[$index]['value'] = [];
                } else {
                    $aSettings[$index]['value'] = '';
                }
            }
        }

        $this->template()->assign([
            'bShowSaveChanges' => true,
            'bShowClearCache' => true,
            'sConnection' => $sConnection,
            'aSettings' => $aSettings,
        ])->setTitle(_p('block_manager'))
            ->setBreadCrumb(_p('editing') . ': ' . (empty($aBlock['m_connection']) ? _p('site_wide') : $aBlock['m_connection']) . (empty($aBlock['component']) ? '' : '::' . rtrim(str_replace('|', '::', $aBlock['component']), '::')) . (empty($aBlock['title']) ? '' : ' (' . Phpfox_Locale::instance()->convert($aBlock['title']) . ')'), $this->url()->makeUrl('admincp.block.add'), true)
            ->setActiveMenu('admincp.appearance.block')
            ->setTitle(_p('settings'));
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin
            = Phpfox_Plugin::get('admincp.component_controller_block_add_clean'))
            ? eval($sPlugin) : false);
    }
}