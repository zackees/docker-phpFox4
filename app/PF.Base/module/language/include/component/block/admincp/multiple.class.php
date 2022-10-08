<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 *
 *
 * @copyright        [PHPFOX_COPYRIGHT]
 * @author           phpFox LLC
 * @package          Phpfox_Component
 * @version          $Id: sample.class.php 1297 2009-12-04 23:18:17Z
 *                   phpFox LLC $
 */
class Language_Component_Block_Admincp_Multiple extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $aLanguages = Phpfox::getLib('template')->getVar('aLanguages');

        if(empty($aLanguages)){
            $aLanguages  = Phpfox::getService('language')->getAll(true);
        }

        $sPhraseName = $this->getParam('phrase');
        $sPhraseValue = '';

        $aForms = Phpfox::getLib('template')->getVar('aForms');
        if(is_array($aForms) and array_key_exists($sPhraseName, $aForms)){
            $sPhraseValue =  $aForms[$sPhraseName];
        }
        if(null == $sPhraseValue){
            $sPhraseValue = Phpfox::getLib('template')->getVar($sPhraseName);
        }

        foreach ($aLanguages as $key => $aLanguage) {
            $aLanguages[$key]['phrase_value'] = $sPhraseValue ? _p($sPhraseValue, [], $aLanguage['language_id']) : '';
        }

        $aDefault = array_shift($aLanguages);

        $this->template()->assign([
                'aDefaultLanguage'              => $aDefault,
                'aOtherLanguages'               => $aLanguages,
                'bRequired'                     => $this->getParam('required', false),
                'sLabel'                        => _p($this->getParam('label', 'name')),
                'sField'                        => $this->getParam('field', 'name'),
                'sMaxLength'                    => $this->getParam('maxlength', '200'),
                'sType'                         => $this->getParam('type', 'textarea'),
                'sFormat'                       => $this->getParam('format', 'name_'),
                'bAllowMultiple'                => $this->getParam('allow_multiple', false),
                'sSize'                         => $this->getParam('size', '30'),
                'sRows'                         => $this->getParam('rows', '5'),
                'sCachePhrase'                  => $this->request()->get('phrase'),
                'sHelpPhrase'                   => $this->getParam('help_phrase', 'if_this_value_is_empty_it_will_have_value_the_same_with_the_default_language'),
                'bCloseWarning'                 => $this->getParam('close_warning', false)
            ]
        );
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin
            = Phpfox_Plugin::get('language.component_block_sample_clean'))
            ? eval($sPlugin) : false);
    }
}