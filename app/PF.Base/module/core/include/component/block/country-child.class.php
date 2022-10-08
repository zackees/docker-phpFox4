<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Block_Country_Child extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $sCountryChildValue = $this->getParam('country_child_value');
        $mCountryChildFilter = $this->getParam('country_child_filter', $this->request()->get('country_child_filter', null));
        $sCountryChildType = $this->getParam('country_child_type', null);
        $sCountryChildId = null;
        $sessionPrefix = Phpfox::getParam('core.session_prefix');

        if (empty($sCountryChildValue) && Phpfox::isUser() && $mCountryChildFilter === null && !$this->getParam('country_not_user')) {
            $sCountryChildValue = Phpfox::getUserBy('country_iso');
        }

        if ($mCountryChildFilter !== null) {
            $search = $this->request()->get('search');
            if ((Phpfox::isAdminPanel() && isset($search['country']))) {
                $sCountryChildValue = $search['country'];
                $sCountryChildId = (isset($search['country_child_id']) ? $search['country_child_id'] : '');
            } else {
                $iSearchId = $this->request()->get('search-id');
                if (!empty($iSearchId) && isset($_SESSION[$sessionPrefix]['search'][$sCountryChildType][$iSearchId]['country'])) {
                    $sCountryChildValue = $_SESSION[$sessionPrefix]['search'][$sCountryChildType][$iSearchId]['country'];
                }

                if (isset($_SESSION[$sessionPrefix]['search'][$sCountryChildType][$iSearchId]['country_child_id'])) {
                    $sCountryChildId = $_SESSION[$sessionPrefix]['search'][$sCountryChildType][$iSearchId]['country_child_id'];
                }
            }
        }


        /* Last resort, get is a little heavy but controller didnt provide a child country*/

        if ($sCountryChildId == null && $this->getParam('country_child_id') == null) {
            $aUser = Phpfox::getService('user')->get(Phpfox::getUserId());
            $sCountryChildId = $aUser['country_child_id'];
        }


        $this->template()->assign([
                'aCountryChildren' => Phpfox::getService('core.country')->getChildren($sCountryChildValue),
                'iCountryChildId' => (int)$this->getParam('country_child_id', $sCountryChildId),
                'bForceDiv' => $this->getParam('country_force_div', false),
                'bAdminSearch' => $this->getParam('admin_search', false),
                'mCountryChildFilter' => $mCountryChildFilter
            ]
        );
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('core.component_block_country_child_clean')) ? eval($sPlugin) : false);
    }
}