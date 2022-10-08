<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Service_Callback extends Phpfox_Service
{
    /**
     * Class constructor
     */
    public function __construct()
    {
    }

    /**
     * @return array
     */
    public function getBlocksIndexMember()
    {
        return [
            'table' => 'user_dashboard',
            'field' => 'user_id'
        ];
    }

    /**
     * @return array
     */
    public function hideBlockNew()
    {
        return [
            'table' => 'user_dashboard'
        ];
    }

    /**
     * @return array
     */
    public function getBlockDetailsNew()
    {
        return [
            'title' => _p('what_s_new')
        ];
    }

    /**
     * @param string $sProduct
     * @param string $sModule
     * @param bool $bCore
     *
     * @return bool
     */
    public function exportModule($sProduct, $sModule, $bCore)
    {
        $iCnt = 0;
        (Phpfox::getService('admincp.menu')->export($sProduct, $sModule) ? $iCnt++ : null);
        (Phpfox::getService('admincp.setting')->exportGroup($sProduct, $sModule) ? $iCnt++ : null);
        (Phpfox::getService('admincp.setting')->export($sProduct, $sModule, $bCore) ? $iCnt++ : null);
        (Phpfox::getService('admincp.module.block')->export($sProduct, $sModule) ? $iCnt++ : null);
        (Phpfox::getService('admincp.plugin')->exportHooks($sProduct, $sModule) ? $iCnt++ : null);
        (Phpfox::getService('admincp.plugin')->export($sProduct, $sModule) ? $iCnt++ : null);
        (Phpfox::getService('admincp.component')->export($sProduct, $sModule) ? $iCnt++ : null);
        (Phpfox::getService('admincp.cron')->export($sProduct, $sModule) ? $iCnt++ : null);
        (Phpfox::getService('core.stat')->export($sProduct, $sModule) ? $iCnt++ : null);

        return ($iCnt ? true : false);
    }

    public function getAdmincpAlertItems()
    {
        $iCnt = 0;
        // TODO: Will check and document again in 4.8.0
//        if (is_writable(PHPFOX_DIR_SITE)) {
//            $iCnt++;
//        }
//        if (is_writable(PHPFOX_DIR)) {
//            $iCnt++;
//        }
        if ($iCnt == 2) {
            $message = _p("dir_site_and_dir_base_are_writable_please_follow_our_document_to_resolve_this_warning", [
                    'dir_site' => 'PF.Site/',
                    'dir_base' => 'PF.Base/'
                ]);
        } elseif ($iCnt) {
            if (is_writable(PHPFOX_DIR_SITE)) {
                $message = _p("dir_is_writable_please_follow_our_document_to_resolve_this_warning", [
                    'dir' => 'PF.Site/',
                ]);
            } else {
                $message = _p("dir_is_writable_please_follow_our_document_to_resolve_this_warning", [
                    'dir' => 'PF.Base/',
                ]);
            }
        } else {
            return [];
        }
        return [
            'message' => $message,
            'value' => $iCnt,
            'link' => 'https://docs.phpfox.com/display/FOX4MAN/Installing+phpFox'
        ];
    }

    /**
     * If a call is made to an unknown method attempt to connect
     * it to a specific plug-in with the same name thus allowing
     * plug-in developers the ability to extend classes.
     *
     * @param string $sMethod is the name of the method
     * @param array $aArguments is the array of arguments of being passed
     *
     * @return null
     */
    public function __call($sMethod, $aArguments)
    {
        /**
         * Check if such a plug-in exists and if it does call it.
         */
        if ($sPlugin = Phpfox_Plugin::get('core.service_callback__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}