<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Search_Component_Controller_Index
 */
class Search_Component_Controller_Index extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        Phpfox::getUserParam('search.can_use_global_search', true);

        $sView = $this->request()->get('view', null);
        $sGetHistory = $this->request()->get('history');
        $sQuery = $this->request()->get('q', null, false);
        $iTotalShow = 10;
        $iPage = $this->request()->getInt('page', 1);
        $minCharacter = (int)Phpfox::getParam('core.min_character_to_search');
        if ($minCharacter <= 0) {
            $minCharacter = 2;
        }

        if (!empty($sQuery) && mb_strlen($sQuery) >= $minCharacter) {
            if (empty($this->request()->get('encode'))) {
                preg_match_all('/(\w+){2,30}/', $sQuery, $aMatches);

                if (!empty($aMatches)) {
                    $aWord = array_intersect_key($aMatches[0], array_unique(array_map('strtolower', $aMatches[0])));

                    if (!empty($aWord)) {
                        Phpfox::getService('search.process')->logSearchWord($aWord);
                    }
                }
            }

            $sTrimQuery = trim($sQuery);
            if (strpos($sTrimQuery, '#') === 0 && substr_count($sTrimQuery, '#') == 1 && !preg_match('/\s/', $sTrimQuery)) {
                $this->url()->send('hashtag.' . preg_replace('/^#/', '', $sTrimQuery));
            }

            $iRootTotalPerPage = 0;
            $aSearchResults = Phpfox::getService('search')->query($sQuery, $iPage, $iTotalShow, $sView, $iRootTotalPerPage);
            $aFilterMenu = [
                _p('all_results') => $this->url()->makeUrl('search',
                    ['q' => urlencode($sQuery), 'encode' => '1'])
            ];

            if (empty($sGetHistory)) {
                $sHistory = '';
                foreach ($aSearchResults as $aSearchResult) {
                    if (isset($aSearchTypes[$aSearchResult['item_type_id']])) {
                        continue;
                    }

                    $aSearchTypes[$aSearchResult['item_type_id']] = true;
                    $sHistory .= $aSearchResult['item_type_id'] . ',';
                }
                $sHistory = rtrim($sHistory, ',');
            } else {
                $sHistory = $sGetHistory;
            }

            $aMenus = Phpfox::massCallback('getSearchTitleInfo');
            foreach ($aMenus as $sKey => $aMenu) {
                if ($aMenu) {
                    $aFilterMenu[$aMenu['name']] = $this->url()->makeUrl('search',
                        ['q' => urlencode($sQuery), 'view' => $sKey, 'encode' => '1', 'history' => $sHistory]);
                }
            }

            $this->template()->buildSectionMenu('search', $aFilterMenu);
            $sQuery = htmlspecialchars($sQuery);


            $this->template()->clearBreadCrumb()
                ->assign([
                    'bCanLoadMore' => $iRootTotalPerPage >= $iTotalShow,
                    'iTotalShow' => $iTotalShow,
                    'aSearchResults' => $aSearchResults,
                    'sQuery' => $sQuery,
                    'sNextPage' => 'q=' . urlencode($sQuery) . '&amp;encode=1&amp;view=' . $sView . '&amp;history=' . $sHistory . '&amp;page=' . ($iPage + 1),
                    'sMenuBlockTitle' => _p('filter_results_by')
                ])
                ->setTitle(_p('results'));
        } else {
            $this->template()->clearBreadCrumb()
                ->assign([
                    'sQuery' => $sQuery,
                    'minCharacter' => $minCharacter
                ]);
        }

        (($sPlugin = Phpfox_Plugin::get('search.component_controller_index_process_end')) ? eval($sPlugin) : false);
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('search.component_controller_index_clean')) ? eval($sPlugin) : false);
    }
}