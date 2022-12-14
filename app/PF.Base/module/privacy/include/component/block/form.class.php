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
 * @package        Phpfox_Component
 * @version        $Id: form.class.php 5428 2013-02-25 15:01:29Z phpFox LLC $
 */
class Privacy_Component_Block_Form extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $bIsListType = $this->getParam('list_type', false);
        $aPrivacyControls = array();
        if (!Phpfox::getParam('core.friends_only_community', false)) {
            $aPrivacyControls[] = array(
                'phrase' => _p('everyone'),
                'value' => '0'
            );
            $aPrivacyControls[] = array(
                'phrase' => _p('community'),
                'value' => '6'
            );
        }
        if (Phpfox::isModule('friend')) {
            $aPrivacyControls[] = array(
                'phrase' => _p('friends'),
                'value' => '1'
            );
            $aPrivacyControls[] = array(
                'phrase' => _p('friends_of_friends'),
                'value' => '2'
            );
        }

        $aPrivacyControls[] = array(
            'phrase' => _p('only_me'),
            'value' => '3'
        );

        if (Phpfox::isModule('friend') && !(bool)$this->getParam('privacy_no_custom', false)) {
            $mCustomPrivacyId = $this->getParam('privacy_custom_id', null);
            $aPrivacyControls[] = array(
                'phrase' => $bIsListType ? _p('custom') : _p('custom_span_click_to_edit_span'),
                'value' => '4',
                'onclick' => '$Core.box(\'privacy.getFriends\', \'\', \'no_page_click=true' . ($mCustomPrivacyId === null ? '' : '&amp;custom-id=' . $mCustomPrivacyId) . '&amp;privacy-array=' . $this->getParam('privacy_array', '') . '\');'
            );
        }

        (($sPlugin = Phpfox_Plugin::get('privacy.component_block_form_process')) ? eval($sPlugin) : '');

        $aVals = (array)$this->template()->getVar('aForms');
        if (($aPostVals = $this->request()->getArray('val'))) {
            $aVals = $aPostVals;
        }

        $bNoActive = true;
        $aSelectedPrivacyControl = array();
        foreach ($aPrivacyControls as $iKey => $aPrivacyControl) {
            if (!empty($aVals) && isset($aVals[$this->getParam('privacy_name')])) {
                if ($aPrivacyControl['value'] == $aVals[$this->getParam('privacy_name')]) {
                    $aPrivacyControl['phrase'] = preg_replace('/<span>(.*)<\/span>/i', '', $aPrivacyControl['phrase']);
                    $aSelectedPrivacyControl = $aPrivacyControl;
                    $aPrivacyControls[$iKey]['is_active'] = true;
                    $bNoActive = false;
                    break;
                }
            } else {
                $aSelectedPrivacyControl = $aPrivacyControl;
                break;
            }
        }

        if ($bNoActive === true && $this->getParam('default_privacy') != '' && ($iDefaultValue = Phpfox::getService('user.privacy')->getValue($this->getParam('default_privacy'))) && $iDefaultValue > 0) {
            foreach ($aPrivacyControls as $iKey => $aPrivacyControl) {
                if ($aPrivacyControl['value'] == $iDefaultValue) {
                    $aPrivacyControl['phrase'] = preg_replace('/<span>(.*)<\/span>/i', '', $aPrivacyControl['phrase']);
                    $aSelectedPrivacyControl = $aPrivacyControl;
                    $aPrivacyControls[$iKey]['is_active'] = true;
                    $bNoActive = false;
                    break;
                }
            }
        }

        $sPrivacyInfo = _p($this->getParam('privacy_info'));

        if (empty($aSelectedPrivacyControl)) {
            $aSelectedPrivacyControl = $aPrivacyControls[0];
        }

        $this->template()->assign(array(
            'sPrivacyFormType' => $this->getParam('privacy_type', ''),
            'sPrivacyFormName' => $this->getParam('privacy_name'),
            'sPrivacyFormInfo' => $sPrivacyInfo,
            'bPrivacyNoCustom' => (bool)$this->getParam('privacy_no_custom', false),
            'bSelectInline' => (bool)$this->getParam('inline_privacy', false),
            'aPrivacyControls' => $aPrivacyControls,
            'aSelectedPrivacyControl' => $aSelectedPrivacyControl,
            'sPrivacyArray' => $this->getParam('privacy_array', null),
            'bNoActive' => $bNoActive,
            'bIsListType' => $bIsListType,
            'sBtnSize' => 'btn-' . $this->getParam('btn_size', ($bIsListType ? 'lg' : 'sm'))
        ));
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('privacy.component_block_form_clean')) ? eval($sPlugin) : false);

        $this->template()->clean(array(
            'sPrivacyFormName',
            'sPrivacyFormInfo',
            'bPrivacyNoCustom',
            'sPrivacyArray'
        ));

        $this->clearParam('privacy_no_custom');
        $this->clearParam('privacy_custom_id');
        $this->clearParam('privacy_array');
        $this->clearParam('default_privacy');
    }
}