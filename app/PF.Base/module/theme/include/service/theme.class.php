<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 *
 *
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		phpFox LLC
 * @package  		Module_Theme
 * @version 		$Id: theme.class.php 4887 2012-10-11 11:38:15Z phpFox LLC $
 */
class Theme_Service_Theme extends Phpfox_Service
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('theme');
    }

    public function isTheme($sTheme)
    {
        return false;
    }

    public function get($aCond = array())
    {
        return [];
    }

    public function getForEdit($iId)
    {
        return $this->getTheme($iId);
    }

    public function getTheme($iId, $bUseFolder = false)
    {
        return null;
    }

    public function &getDesignValues(&$aAdvanced, $aParams)
    {
        return [];
    }

    public function getCss($aParams)
    {
        return '';
    }

    public function getCssCode($aParams)
    {
        return null;
    }

    public function export($aVals)
    {
        return [];
    }

    public function getNewThemes()
    {
        return [];
    }

    public function isInDnDMode()
    {
        return false;
    }

    public function __call($sMethod, $aArguments)
    {
        /**
         * Check if such a plug-in exists and if it does call it.
         */
        if ($sPlugin = Phpfox_Plugin::get('theme.service_theme__call'))
        {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}
