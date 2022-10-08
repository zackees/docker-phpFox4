<?php

defined('PHPFOX') or exit('NO DICE!');

class Language_Component_Controller_Admincp_Confirm extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $this->template()->setTitle(_p('manage_language_packages'))
            ->setBreadCrumb(_p('manage_language_packages'))
            ->assign([
                    'dir'  => $this->request()->get('dir'),
                    'page' => $this->request()->get('page')
                ]
            );
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('language.component_controller_admincp_confirm_clean')) ? eval($sPlugin) : false);
    }
}