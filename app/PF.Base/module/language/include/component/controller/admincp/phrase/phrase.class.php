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
 * @package          Module_Language
 * @version          $Id: phrase.class.php 5538 2013-03-25 13:20:22Z phpFox LLC $
 */
class Language_Component_Controller_Admincp_Phrase_Phrase extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        Phpfox::getUserParam('language.can_manage_lang_packs', true);

        $iPage = $this->request()->getInt('page');
        $iLangId = $this->request()->get('lang-id');
        $oPhraseProcess = Phpfox::getService('language.phrase.process');
        $oCache = Phpfox::getLib('cache');

        $search = $this->request()->get('search');
        if (!empty($search)) {
            $search = base64_encode(json_encode($search));
        }
        elseif ($iLangId) {
            $search = base64_encode(json_encode(['language_id' => $iLangId]));
        }

        $searchParams = $this->request()->get('search-params');
        if (!empty($searchParams)) {
            $searchParams = (array) json_decode(base64_decode($searchParams));
            foreach ($searchParams as $key => $val) {
                unset($searchParams[$key]);
                $searchParams['search['. $key .']'] = $val;
            }
        } else {
            $searchParams = [];
        }

        if ($iLangId) {
            $searchParams['search[language_id]'] = $iLangId;
        }

        if ($iPage) {
            $searchParams['page'] = $iPage;
        }

        $url = Phpfox_Url::instance()->makeUrl('admincp.language.phrase', $searchParams);

        if ($this->request()->get('save') && ($aTexts = $this->request()->getArray('text'))) {
            foreach ($aTexts as $iKey => $sText) {
                $oPhraseProcess->update($iKey, $sText);
            }

            $oCache->removeGroup('locale');
            $oCache->remove('apps_header_scripts');
            $oCache->remove('language_phrase_all');
            $this->url()->send($url, null, _p('phrase_s_updated'));
        }

        if ($this->request()->get('save_selected') && ($aTexts = $this->request()->getArray('text')) && ($aIds = $this->request()->getArray('id'))) {
            foreach ($aTexts as $iKey => $sText) {
                if (!in_array($iKey, $aIds)) {
                    continue;
                }
                $oPhraseProcess->update($iKey, $sText);
            }
            $oCache->removeGroup('locale');
            $oCache->remove('apps_header_scripts');
            $oCache->remove('language_phrase_all');
            $this->url()->send($url, null, _p('phrase_s_updated'));
        }

        if ($this->request()->get('revert_selected') && ($aIds = $this->request()->getArray('id'))) {
            if ($oPhraseProcess->revert($aIds)) {
                $oCache->removeGroup('locale');
                $this->url()->send($url, null, _p('selected_phrase_s_successfully_reverted'));
            }
        }

        if ($this->request()->get('delete') && ($aIds = $this->request()->getArray('id'))) {
            foreach ($aIds as $iId) {
                $oPhraseProcess->delete($iId);
            }
            $oCache->removeGroup('locale');
            $oCache->remove('apps_header_scripts');
            $oCache->remove('language_phrase_all');
            $this->url()->send($url, null, _p('selected_phrase_s_successfully_deleted'));
        }

        $aLanguages = Phpfox::getService('language')->get();
        $aLangs = [];
        foreach ($aLanguages as $aLanguage) {
            $aLangs[$aLanguage['language_id']] = $aLanguage['title'];
        }

        $aPages = [20, 40, 60, 80, 100];
        $aDisplays = [];
        foreach ($aPages as $iPageCnt) {
            $aDisplays[$iPageCnt] = _p('per_page', ['total' => $iPageCnt]);
        }

        $aSorts = [
            'added'     => _p('time'),
            'phrase_id' => _p('phrase_id')
        ];

        $aFilters = [
            'display'        => [
                'type'    => 'select',
                'options' => $aDisplays,
                'default' => '20'
            ],
            'sort'           => [
                'type'    => 'select',
                'options' => $aSorts,
                'default' => 'added',
                'alias'   => 'lp'
            ],
            'sort_by'        => [
                'type'    => 'select',
                'options' => [
                    'DESC' => _p('descending'),
                    'ASC'  => _p('ascending')
                ],
                'default' => 'DESC'
            ],
            'language_id'    => [
                'type'       => 'select',
                'options'    => $aLangs,
                'add_select' => true,
                'search'     => "AND lp.language_id = '[VALUE]'",
                'id'         => 'js_language_id'
            ],
            'translate_type' => [
                'type'    => 'select',
                'options' => [
                    '0' => _p('all_phrases'),
                    '1' => _p('not_translated'),
                    '2' => _p('translated_only'),
                ]
            ],
            'search'         => [
                'type' => 'input:text',
            ],
            'search_type'    => [
                'type'    => 'input:radio',
                'options' => [
                    '0' => [_p('phrase_text_only'), "AND lp.text LIKE '%[VALUE]%'"],
                    '1' => [_p('phrase_variable_name_only'), "AND lp.var_name LIKE '%[VALUE]%'"],
                    '2' => [_p('phrase_text_and_phrase_variable_name'), "AND (lp.text LIKE '%[VALUE]%' OR lp.var_name LIKE '%[VALUE]%')"],
                    '3' => [_p('the_exact_phrase_of_text_and_variable_name'), "AND (lp.text = '[VALUE]' OR lp.var_name = '[VALUE]')"]
                ],
                'depend'  => 'search',
                'prefix'  => '<div>',
                'suffix'  => '</div>',
                'default' => '0'
            ]
        ];

        $oSearch = Phpfox_Search::instance()->set([
            'type'    => 'phrases',
            'filters' => $aFilters,
            'field'   => 'lp.phrase_id',
            'search'  => 'search'
        ]);

        $bIsForceLanguagePackage = false;
        if ($iLangId) {
            $bIsForceLanguagePackage = true;
            $oSearch->setCondition('AND lp.language_id = \'' . Phpfox_Database::instance()->escape($iLangId) . '\'');
            $this->template()->setHeader('<script type="text/javascript">$Behavior.language_admincp_phrase = function(){ $(\'#js_language_id\').val(\'' . $iLangId . '\'); };</script>');
        }

        if (($sTranslate = $oSearch->get('translate_type'))) {
            if ($sTranslate == '1') {
                $oSearch->setCondition(' AND lp.text = lp.text_default');
            } else if ($sTranslate == '2') {
                $oSearch->setCondition(' AND lp.text != lp.text_default');
            }
        }

        $iPageSize = $oSearch->getDisplay();

        if (!defined('PHPFOX_SEARCH_MODE_CONVERT')) {
            define('PHPFOX_SEARCH_MODE_CONVERT', true);
        }

        list($iCnt, $aRows) = Phpfox::getService('language.phrase')->get($oSearch->getConditions(), $oSearch->getSort(), $iPage, $iPageSize);

        $cache = [];
        $oSearchOutput = Phpfox::getLib('parse.output');
        $aOut = [];
        foreach ($aRows as $iKey => $aRow) {
            if (!isset($cache[$aRow['language_id']])) {
                $cache[$aRow['language_id']] = [];
            }

            if (isset($cache[$aRow['language_id']][$aRow['var_name']])) {
                \Phpfox_Database::instance()->delete(':language_phrase', ['phrase_id' => $aRow['phrase_id']]);
                continue;
            }

            $aOut[$aRow['phrase_id']] = $aRow;
            $aOut[$aRow['phrase_id']]['sample_text'] = $oSearch->highlight('search', $oSearchOutput->htmlspecialchars($aRow['text_default']));
            $aOut[$aRow['phrase_id']]['is_translated'] = (md5($aRow['text_default']) != md5($aRow['text']) ? true : false);
        }
        $aRows = $aOut;
        Phpfox_Pager::instance()->set(['page' => $iPage, 'size' => $iPageSize, 'count' => $iCnt]);

        //Admin can add new phrase without define PHPFOX_IS_TECHIE
        $this->template()->setActionMenu([
            _p('new_phrase') => [
                'url'   => $this->url()->makeUrl('admincp.language.phrase.add'),
                'class' => 'popup'
            ]
        ]);

        $this->template()
            ->assign([
                'bShowClearCache'         => true,
                'aRows'                   => $aRows,
                'iPage'                   => $iPage,
                'sSearchParams'           => $search,
                'iLangId'                 => $iLangId,
                'bIsForceLanguagePackage' => $bIsForceLanguagePackage
            ])
            ->setBreadCrumb(_p('phrases'))
            ->setTitle(_p('phrase_manager'))
            ->setActiveMenu('admincp.globalize.phrase');

        if ($this->request()->get('q')) {
            $this->template()->assign('q', $this->request()->get('q'));
        }
    }
}