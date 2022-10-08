<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Ban_Component_Controller_Admincp_Default
 */
class Ban_Component_Controller_Admincp_Default extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $aBanFilter = $this->getParam('aBanFilter');
        $sFindValue = '';
        $sType = $this->request()->get('req3');

        if (($iDeleteId = $this->request()->getInt('delete'))) {
            if (Phpfox::getService('ban.process')->delete($iDeleteId)) {
                $this->url()->send($aBanFilter['url'], null, _p('filter_successfully_deleted'));
            }
        }

        $aValidation = $this->getParam('aValidation');
        $oValidator = Phpfox::getLib('validator');
        if ($aValidation) {
            $oValidator->set(['sFormName' => 'js_form', 'aParams' => $aValidation]);
        }


        if (($aBanValue = $this->request()->getArray('val'))) {
            $aBan = $this->request()->getArray('aBan');
            $aVals = array_merge([
                'type_id' => $aBanFilter['type'],
                'find_value' => $sFindValue = $aBanValue['find_value'],
                'replacement' => $this->request()->get('replacement', null)
            ], $aBan);
            $isValid = true;

            if ($aValidation and !$oValidator->isValid($aVals)) {
                $isValid = false;
            }
            if ($isValid and Phpfox::getService('ban.process')->add($aVals, $aBanFilter)) {
                $this->url()->send($aBanFilter['url'], null, _p('filter_successfully_added'));
            }
        }
        $iPageSize = 10;
        $iPage = $this->request()->get('page', 1);
        $oSearch = Phpfox_Search::instance()->set([
            'type'    => 'ban_filters',
            'filters' => [
                'keyword' => [
                    'type' => 'input:text',
                    'placeholder' => _p('search_ban_filter_' . $sType)
                ]
            ],
            'field'   => 'b.find_value',
            'search'  => 'search'
        ]);
        $aFilters = Phpfox::getService('ban')->getFilters($aBanFilter['type'], $iPage, $iPageSize, $iCnt, $oSearch->get('keyword'));
        Phpfox_Pager::instance()->set(['page' => $iPage, 'size' => $iPageSize, 'count' => $iCnt]);

        foreach ($aFilters as $iKey => $aFilter) {
            $aFilters[$iKey]['s_user_groups_affected'] = '';
            if (is_array($aFilter['user_groups_affected'])) {
                foreach ($aFilter['user_groups_affected'] as $aGroup) {
                    $aFilters[$iKey]['s_user_groups_affected'] .= Phpfox_Locale::instance()->convert($aGroup['title']) . ', ';
                }
                $aFilters[$iKey]['s_user_groups_affected'] = rtrim($aFilters[$iKey]['s_user_groups_affected'], ', ');
            }
        }
        $this->template()->setTitle(_p('ban') . ': ' . $aBanFilter['title'])
            ->setBreadCrumb(_p('ban_filters'))
            ->setSectionTitle(_p('ban') . ': ' . $aBanFilter['title'])
            ->setActiveMenu('admincp.maintain.ban')
            ->assign([
                'sFindValue' => $sFindValue,
                'aBanFilters' => $aFilters,
                'aBanFilter' => $aBanFilter,
                'aSectionAppMenus' => [
                    _p('usernames') => [
                        'url' => $this->url()->makeUrl('admincp.ban.username'),
                        'is_active' => $sType == 'username'
                    ],
                    _p(!Phpfox::getParam('core.enable_register_with_phone_number') ?
                        'email' : 'email_or_phone_number') => [
                        'url' => $this->url()->makeUrl('admincp.ban.email'),
                        'is_active' => $sType == 'email'
                    ],
                    _p('ip_address') => [
                        'url' => $this->url()->makeUrl('admincp.ban.ip'),
                        'is_active' => $sType == 'ip'
                    ],
                    _p('display') => [
                        'url' => $this->url()->makeUrl('admincp.ban.display'),
                        'is_active' => $sType == 'display'
                    ],
                    _p('words') => [
                        'url' => $this->url()->makeUrl('admincp.ban.word'),
                        'is_active' => $sType == 'word'
                    ]
                ]
            ]);
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('ban.component_controller_admincp_default_clean')) ? eval($sPlugin) : false);
    }
}
