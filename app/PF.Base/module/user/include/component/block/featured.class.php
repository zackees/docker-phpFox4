<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Block_Featured
 */
class User_Component_Block_Featured extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $iLimit = $this->getParam('limit', 4);

        if (!(int)$iLimit) {
            return false;
        }
        $iCacheTime = $this->getParam('cache_time', 5);
        list($aUsers, $iTotal) = Phpfox::getService('user.featured')->get($iLimit, $iCacheTime);

        if (empty($aUsers) || $aUsers === false) {
            return false;
        }
        if (count($aUsers) < $iTotal) {
            $this->template()->assign(array(
                'aFooter' => array(
                    _p('view_all') => $this->url()->makeUrl('user.browse', array('view' => 'featured'))
                )
            ));
        }
        $this->template()->assign(array(
                'aFeaturedUsers' => $aUsers,
                'sHeader' => _p('featured_members'),
            )
        );

        return 'block';
    }

    public function getSettings()
    {
        return [
            [
                'info' => _p('featured_members_limit'),
                'description' => _p('define_the_limit_of_how_many_featured_members_display_set_zero_will_hide_this_block'),
                'value' => 6,
                'type' => 'integer',
                'var_name' => 'limit',
            ],
            [
                'info' => _p('featured_members_cache_ime'),
                'description' => _p('define_how_long_we_should_the_cache_fore_featured_members_by_minutes'),
                'value' => Phpfox::getParam('core.cache_time_default'),
                'options' => Phpfox::getParam('core.cache_time'),
                'type' => 'select',
                'var_name' => 'cache_time',
            ]
        ];
    }

    public function getValidation()
    {
        return [
            'limit' => [
                'def' => 'int',
                'min' => 0,
                'title' => _p('setting_name_must_be_greater_than_or_equal_to_zero',
                    ['setting_name' => _p('featured_members_limit')])
            ],
        ];
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('user.component_block_featured_clean')) ? eval($sPlugin) : false);
    }
}
