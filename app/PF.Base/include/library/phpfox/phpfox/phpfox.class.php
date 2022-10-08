<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

include 'support.php';

/**
 * phpFox Engine
 * All interactions with anything phpFox related is executed via this class.
 * It is the engine that runs phpFox and all of the other libraries and modules.
 * All methods, variables and constants are static.
 *
 * All libraries are located within the folder: include/library/phpfox/
 * Example of connect to request library:
 * <code>
 * $oObject = Phpfox_Request::instance();
 * </code>
 *
 * @copyright         [PHPFOX_COPYRIGHT]
 * @author            phpFox LLC
 * @package           Phpfox
 * @version           $Id: phpfox.class.php 7299 2014-05-06 15:41:28Z Fern $
 */
class Phpfox
{
    /**
     * Product Version : major.minor.maintenance [alphaX, betaX or rcX]
     */
    const VERSION = '4.8.10';

    /**
     * Product Code Name
     *
     */
    const CODE_NAME = 'Neutron';

    /**
     * Browser agent used with API curl requests.
     *
     */
    const BROWSER_AGENT = 'phpFox';

    /**
     * Product build number.
     *
     */
    const PRODUCT_BUILD = '1';

    /**
     * IS BETA
     * empty mean not beta version
     */
    const PRODUCT_BETA = '';

    /**
     * phpFox API server.
     *
     */
    const PHPFOX_API = 'http://api.phpfox.com/deepspace/';

    /**
     * phpFox package ID.
     *
     */
    const PHPFOX_PACKAGE = '[PHPFOX_PACKAGE_NAME]';

    /**
     * ARRAY of objects initiated. Used to keep a static history
     * so we don't call the same class more then once.
     *
     * @var array
     */
    private static $_aObject = [];

    /**
     * ARRAY of libraries being loaded.
     *
     * @var array
     */
    private static $_aLibs = [];

    /**
     * Used to keep a static variable to see if we are within the AdminCP.
     *
     * @var bool
     */
    private static $_bIsAdminCp = false;

    /**
     * History of any logs we save for debug purposes.
     *
     * @var array
     */
    private static $_aLogs = [];

    /**
     * @var Phpfox_Config_Container
     */
    private static $_config;

    /**
     * @var int
     */
    private static $_pageUserId;

    /**
     * @var
     */
    private static $_libContainer;

    /**
     * @var  \Core\Log\Admincp
     */
    private static $_logManager;

    /* Optimize Reduce Load class */
    private static $dependencyManager = [];
    private static $sessionPrefix;
    private static $tablePrefix;

    /**
     *
     * @var \Core\App
     */
    private static $_coreApp;

    public static function generateSelectDate($aParams)
    {
        $sPrefix = isset($aParams['prefix']) ? $aParams['prefix'] : '';

        $sName = isset($aParams['name']) ? $aParams['name'] : 'val';
        $bForTemplate = empty($aParams['bNotForTemplate']);

        if (!$bForTemplate) {
            $iSelectedMonth = !empty($aParams['selected_values'][$sPrefix . 'month']) ? $aParams['selected_values'][$sPrefix . 'month'] : null;
            $iSelectedDay = !empty($aParams['selected_values'][$sPrefix . 'day']) ? $aParams['selected_values'][$sPrefix . 'day'] : null;
            $iSelectedYear = !empty($aParams['selected_values'][$sPrefix . 'year']) ? $aParams['selected_values'][$sPrefix . 'year'] : null;
        }

        $bSetEmptyAll = (int)(!empty($aParams['set_empty_value']));
        $bUseJquery = Phpfox::getParam('core.use_jquery_datepicker');


        if (isset($aParams['bUseDatepicker']) && $aParams['bUseDatepicker'] == 'false') {
            $bUseJquery = false;
        }
        $sReturn = '<?php $' . $sPrefix . 'default_picker_time = PHPFOX_TIME; ';
        $sDefaultTime = PHPFOX_TIME;
        if (isset($aParams['start_hour'])) {
            if (substr($aParams['start_hour'], 0, 1) == '+') {
                $sReturn .= '$' . $sPrefix . 'default_picker_time += ' . substr_replace($aParams['start_hour'], '', 0, 1) * 3600 . '; ';
                $sDefaultTime += substr_replace($aParams['start_hour'], '', 0, 1) * 3600;
            }
        }
        $sReturn .= '?> ';
        $sMonth = '<select  name="' . $sName . '[' . $sPrefix . 'month]" id="' . $sPrefix . 'month" class="form-control js_datepicker_month">' . "\n";
        if (!isset($aParams['default_all'])) {
            $sMonth .= "\t\t" . '<option value="">' . ($bForTemplate ? '<?php echo _p(\'month\'); ?>' : _p('month')) . ':</option>' . "\n";
        }

        $aMonths = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December'
        ];
        $i = 0;

        foreach ($aMonths as $sNewMonth) {
            $i++;
            $sMonth .= "\t\t\t" . '<option value="' . $i . '" ' . ($bSetEmptyAll ? '' : ($bForTemplate ? Phpfox::getLib('template.cache')->parseFunction('value', '', "type='select' id='{$sPrefix}month' default='{$i}'") . (isset($aParams['default_all']) ? '<?php echo (!isset($this->_aVars[\'aForms\'][\'' . $sPrefix . 'month\']) ? (\'' . $i . '\' == Phpfox::getTime(\'n\', $' . $sPrefix . 'default_picker_time) ? \' selected="selected"\' : \'\') : \'\'); ?>' : '')
                    : (!empty($iSelectedMonth) && $iSelectedMonth == $i ? ' selected="selected"' : '') . (isset($aParams['default_all']) && empty($iSelectedMonth) && $i == Phpfox::getTime('n', $sDefaultTime) ? ' selected="selected"' : ''))) . '>' . ($bForTemplate ? '<?php echo (defined(\'PHPFOX_INSTALLER\') ? \'' . str_replace("'", "\'", $sNewMonth) . '\' : _p(\'' . strtolower($sNewMonth) . '\')); ?>' : (defined('PHPFOX_INSTALLER') ? str_replace("'", "\'", $sNewMonth) : _p(strtolower($sNewMonth)))) . '</option>' . "\n";
        }
        $sMonth .= "\t\t" . '</select>' . "\n";

        $sDay = "\t\t" . '<select name="' . $sName . '[' . $sPrefix . 'day]" id="' . $sPrefix . 'day" class="form-control js_datepicker_day">' . "\n";
        if (!isset($aParams['default_all'])) {
            $sDay .= "\t\t" . '<option value="">' . ($bForTemplate ? '<?php echo _p(\'day\'); ?>' : _p('day')) . ':</option>' . "\n";
        }

        for ($i = 1; $i <= 31; $i++) {
            $sDay .= "\t\t\t" . '<option value="' . $i . '" ' . ($bSetEmptyAll ? '' : ($bForTemplate ? (Phpfox::getLib('template.cache')->parseFunction('value', '', "type='select' id='{$sPrefix}day' default='{$i}'") . (isset($aParams['default_all']) ? '<?php echo (!isset($this->_aVars[\'aForms\'][\'' . $sPrefix . 'day\']) ? (\'' . $i . '\' == Phpfox::getTime(\'j\', $' . $sPrefix . 'default_picker_time) ? \' selected="selected"\' : \'\') : \'\'); ?>' : ''))
                    : (!empty($iSelectedDay) && $i == $iSelectedDay ? ' selected="selected"' : '') . (isset($aParams['default_all']) ? (!isset($iSelectedDay) ? ($i == Phpfox::getTime('j', $sDefaultTime) ? ' selected="selected"' : '') : '') : ''))) . '>' . $i . '</option>' . "\n";
        }

        $sDay .= "\t\t" . '</select>' . "\n";

        if ($aParams['start_year'] == 'current_year') {
            $aParams['start_year'] = date('Y');
        } else if (preg_match('/[a-z]+\.{1}[a-z0-9\_]+/', $aParams['start_year'], $aMatch) > 0) {
            $aParams['start_year'] = Phpfox::getParam($aMatch[0]);
        }

        if (substr($aParams['end_year'], 0, 1) == '+') {
            $aParams['end_year'] = (date('Y') + substr_replace($aParams['end_year'], '', 0, 1));
        } else if (preg_match('/[a-z]+\.{1}[a-z0-9\_]+/', $aParams['end_year'], $aMatch) > 0) {
            $aParams['end_year'] = Phpfox::getParam($aMatch[0]);
        }

        if (isset($aParams['sort_years']) && $aParams['sort_years'] == (empty($bForTemplate) ? 'DESC' : '\'DESC\'')) {
            $sTemp = $aParams['start_year'];
            $aParams['start_year'] = $aParams['end_year'];
            $aParams['end_year'] = $sTemp;
            $sJsSortDesc = '<script>var pf_select_date_sort_desc = true;</script>';
        }
        $aVariableParams = Phpfox::getLib('phpfox.request')->getArray('val');

        if (!$bForTemplate) {
            $aYears = range($aParams['start_year'], $aParams['end_year']);

            $sYear = "\t\t" . '<select name="' . $sName . '[' . $sPrefix . 'year]" id="' . $sPrefix . 'year" class="form-control js_datepicker_year">' . "\n";
            if (!isset($aParams['default_all'])) {
                $sYear .= "\t\t" . '<option value="">' . _p('year') . ':</option>' . "\n";
            }

            foreach ($aYears as $iYear) {
                $sYear .= "\t\t\t" . '<option value="' . $iYear . '"' . ($bSetEmptyAll ? '' : (isset($aVariableParams[$sPrefix . 'year']) && $aVariableParams[$sPrefix . 'year'] == $iYear ? ' selected="selected"' : (!isset($iSelectedYear) ? ($iYear == Phpfox::getTime('Y', $sDefaultTime) ? ' selected="selected"' : '') : ($iSelectedYear == $iYear ? ' selected="selected"' : '')))) . '>' . $iYear . '</option>' . "\n";
            }
        } else {
            $sYear = '<?php $aYears = range(' . $aParams['start_year'] . ', ' . $aParams['end_year'] . ');  ?>';
            $sYear .= '<?php $bSetEmptyAll = ' . $bSetEmptyAll . ';  ?>';
            $sYear .= '<?php $aParams = (isset($aParams) ? $aParams : Phpfox::getLib(\'phpfox.request\')->getArray(\'val\')); ?>';
            $sYear .= "\t\t" . '<select name="' . $sName . '[' . $sPrefix . 'year]" id="' . $sPrefix . 'year" class="form-control js_datepicker_year">' . "\n";
            if (!isset($aArgs['default_all'])) {
                $sYear .= "\t\t" . '<option value=""><?php echo _p(\'year\'); ?>:</option>' . "\n";
            }
            $sYear .= '<?php foreach ($aYears as $iYear): ?>';
            $sYear .= "\t\t\t" . '<option value="<?php echo $iYear; ?>"<?php echo ($bSetEmptyAll ? \' \': ((isset($aParams[\'' . $sPrefix . 'year\']) && $aParams[\'' . $sPrefix . 'year\'] == $iYear) ? \' selected="selected"\' : (!isset($this->_aVars[\'aForms\'][\'' . $sPrefix . 'year\']) ? ($iYear == Phpfox::getTime(\'Y\', ' . '$' . $sPrefix . 'default_picker_time' . ') ? \' selected="selected"\' : \'\') : ($this->_aVars[\'aForms\'][\'' . $sPrefix . 'year\'] == $iYear ? \' selected="selected"\' : \'\')))); ?>><?php echo $iYear; ?></option>' . "\n";
            $sYear .= '<?php endforeach; ?>';
        }

        $sYear .= "\t\t" . '</select>' . "\n";

        $aSep = '<span class="field_separator">' . (isset($aParams['field_separator']) ? $aParams['field_separator'] : '') . '</span>';

        switch (Phpfox::getParam('core.date_field_order')) {
            case 'DMY':
                $sReturn .= $sDay . $aSep . $sMonth . $aSep . $sYear;
                break;
            case 'YMD':
                $sReturn .= $sYear . $aSep . $sMonth . $aSep . $sDay;
                break;
            // MDY
            default:
                $sReturn .= $sMonth . $aSep . $sDay . $aSep . $sYear;
                break;
        }

        if ($bUseJquery) {
            $sValue = '';

            if (!$bSetEmptyAll) {
                $sValue = '';
                if ($bForTemplate) {
                    $sValue .= '<?php if (isset($aParams[\'' . $sPrefix . 'month\'])): ?>';

                    $sValue .= '<?php switch(Phpfox::getParam("core.date_field_order")){ ?>';
                    $sValue .= '<?php case "DMY": ?>';
                    $sValue .= '<?php echo $aParams[\'' . $sPrefix . 'day\'] . \'/\'; ?>';
                    $sValue .= '<?php echo $aParams[\'' . $sPrefix . 'month\'] . \'/\'; ?>';
                    $sValue .= '<?php echo $aParams[\'' . $sPrefix . 'year\']; ?>';
                    $sValue .= '<?php break; ?>';
                    $sValue .= '<?php case "MDY": ?>';
                    $sValue .= '<?php echo $aParams[\'' . $sPrefix . 'month\'] . \'/\'; ?>';
                    $sValue .= '<?php echo $aParams[\'' . $sPrefix . 'day\'] . \'/\'; ?>';
                    $sValue .= '<?php echo $aParams[\'' . $sPrefix . 'year\']; ?>';
                    $sValue .= '<?php break; ?>';
                    $sValue .= '<?php case "YMD": ?>';
                    $sValue .= '<?php echo $aParams[\'' . $sPrefix . 'year\'] . \'/\'; ?>';
                    $sValue .= '<?php echo $aParams[\'' . $sPrefix . 'month\'] . \'/\'; ?>';
                    $sValue .= '<?php echo $aParams[\'' . $sPrefix . 'day\']; ?>';
                    $sValue .= '<?php break; ?>';
                    $sValue .= '<?php } ?>';

                    $sValue .= '<?php elseif (isset($this->_aVars[\'aForms\'])): ?>';
                    $sValue .= '<?php if (isset($this->_aVars[\'aForms\'][\'' . $sPrefix . 'month\'])): ?>';

                    $sValue .= '<?php switch(Phpfox::getParam("core.date_field_order")){ ?>';
                    $sValue .= '<?php case "DMY": ?>';
                    $sValue .= '<?php echo $this->_aVars[\'aForms\'][\'' . $sPrefix . 'day\'] . \'/\'; ?>';
                    $sValue .= '<?php echo $this->_aVars[\'aForms\'][\'' . $sPrefix . 'month\'] . \'/\'; ?>';
                    $sValue .= '<?php echo $this->_aVars[\'aForms\'][\'' . $sPrefix . 'year\']; ?>';
                    $sValue .= '<?php break; ?>';
                    $sValue .= '<?php case "MDY": ?>';
                    $sValue .= '<?php echo $this->_aVars[\'aForms\'][\'' . $sPrefix . 'month\'] . \'/\'; ?>';
                    $sValue .= '<?php echo $this->_aVars[\'aForms\'][\'' . $sPrefix . 'day\'] . \'/\'; ?>';
                    $sValue .= '<?php echo $this->_aVars[\'aForms\'][\'' . $sPrefix . 'year\']; ?>';
                    $sValue .= '<?php break; ?>';
                    $sValue .= '<?php case "YMD": ?>';
                    $sValue .= '<?php echo $this->_aVars[\'aForms\'][\'' . $sPrefix . 'year\'] . \'/\'; ?>';
                    $sValue .= '<?php echo $this->_aVars[\'aForms\'][\'' . $sPrefix . 'month\'] . \'/\'; ?>';
                    $sValue .= '<?php echo $this->_aVars[\'aForms\'][\'' . $sPrefix . 'day\']; ?>';
                    $sValue .= '<?php break; ?>';
                    $sValue .= '<?php } ?>';

                    $sValue .= '<?php endif; ?>';
                    $sValue .= '<?php else: ?>';
                    $sValue .= '<?php switch(Phpfox::getParam("core.date_field_order")){';
                    $sValue .= '	case "DMY": echo Phpfox::getTime(\'j\', ' . '$' . $sPrefix . 'default_picker_time' . ') . \'/\' . Phpfox::getTime(\'n\', ' . '$' . $sPrefix . 'default_picker_time' . ') . \'/\' . Phpfox::getTime(\'Y\', ' . '$' . $sPrefix . 'default_picker_time' . '); break;';
                    $sValue .= '	case "MDY": echo Phpfox::getTime(\'n\', ' . '$' . $sPrefix . 'default_picker_time' . ') . \'/\' . Phpfox::getTime(\'j\', ' . '$' . $sPrefix . 'default_picker_time' . ') . \'/\' . Phpfox::getTime(\'Y\', ' . '$' . $sPrefix . 'default_picker_time' . '); break;';
                    $sValue .= '	case "YMD": echo Phpfox::getTime(\'Y\', ' . '$' . $sPrefix . 'default_picker_time' . ') . \'/\' . Phpfox::getTime(\'n\', ' . '$' . $sPrefix . 'default_picker_time' . ') . \'/\' . Phpfox::getTime(\'j\', ' . '$' . $sPrefix . 'default_picker_time' . '); break;';
                    $sValue .= '}?>';
                    $sValue .= '<?php endif; ?>';
                } else {
                    if (isset($aVariableParams[$sPrefix . 'month'])) {
                        switch (Phpfox::getParam("core.date_field_order")) {
                            case 'DMY':
                                $sValue = $aVariableParams[$sPrefix . 'day'] . '/' . $aVariableParams[$sPrefix . 'month'] . '/' . $aVariableParams[$sPrefix . 'year'];
                                break;
                            case 'MDY':
                                $sValue = $aVariableParams[$sPrefix . 'month'] . '/' . $aVariableParams[$sPrefix . 'day'] . '/' . $aVariableParams[$sPrefix . 'year'];
                                break;
                            case 'YMD':
                                $sValue = $aVariableParams[$sPrefix . 'year'] . '/' . $aVariableParams[$sPrefix . 'month'] . '/' . $aVariableParams[$sPrefix . 'day'];
                                break;
                        }
                    } else if (isset($iSelectedMonth)) {
                        switch (Phpfox::getParam("core.date_field_order")) {
                            case 'DMY':
                                $sValue = $iSelectedDay . '/' . $iSelectedMonth . '/' . $iSelectedYear;
                                break;
                            case 'MDY':
                                $sValue = $iSelectedMonth . '/' . $iSelectedDay . '/' . $iSelectedYear;
                                break;
                            case 'YMD':
                                $sValue = $iSelectedYear . '/' . $iSelectedMonth . '/' . $iSelectedDay;
                                break;
                        }
                    } else {
                        switch (Phpfox::getParam("core.date_field_order")) {
                            case "DMY":
                                $sValue = Phpfox::getTime('j', $sDefaultTime) . '/' . Phpfox::getTime('n', $sDefaultTime) . '/' . Phpfox::getTime('Y', $sDefaultTime);
                                break;
                            case "MDY":
                                $sValue = Phpfox::getTime('n', $sDefaultTime) . '/' . Phpfox::getTime('j', $sDefaultTime) . '/' . Phpfox::getTime('Y', $sDefaultTime);
                                break;
                            case "YMD":
                                $sValue = Phpfox::getTime('Y', $sDefaultTime) . '/' . Phpfox::getTime('n', $sDefaultTime) . '/' . Phpfox::getTime('j', $sDefaultTime);
                                break;
                        }
                    }

                }
            }

            $sInput = '<input type="text" name="js_' . $sPrefix . '_datepicker" value="' . $sValue . '" class="form-control js_date_picker" />';

            if (Phpfox::isAdminPanel()) {
                $sInput = '<div class="input-group">' . $sInput . '<span class="input-group-addon js_datepicker_image"><i class="fa fa-calendar" aria-hidden="true"></i></span></div>';
            }

            $sReturn = '<div class="js_datepicker_core' . (isset($aParams['id']) ? str_replace(['"', "'"], '', $aParams['id']) : '') . '"><span class="js_datepicker_holder"><div style="display:none;">' . $sReturn . '</div>' . $sInput . '<div class="js_datepicker_image"></div></span> ';
        }

        if (isset($aParams['add_time'])) {
            $sReturn .= '<span class="form-inline js_datepicker_selects">';
            $sCustomPhrase = !empty($aParams['time_separator']) ? $aParams['time_separator'] : '';
            $aCustomPhraseParts = explode('.', $sCustomPhrase);
            if (Phpfox::isModule($aCustomPhraseParts[0])) {
                $sReturn .= '<span class="select-date-label">' . _p($sCustomPhrase) . '</span>';
            }
            $is24Format = Phpfox::getParam('core.pf_time_format', 2) == 2 || !empty($aParams['ignore_time_format']);
            $defaultMeridiem = null;

            if (!$bForTemplate) {
                $iSelectedHour = !empty($aParams['selected_values'][$sPrefix . 'hour']) ? $aParams['selected_values'][$sPrefix . 'hour'] : null;
                $iSelectedMinute = !empty($aParams['selected_values'][$sPrefix . 'minute']) ? $aParams['selected_values'][$sPrefix . 'minute'] : null;
            }

            $sReturn .= "\t\t" . '<select class="form-control" name="' . $sName . '[' . $sPrefix . 'hour]" id="' . $sPrefix . 'hour">' . "\n";
            if ($is24Format) {
                $aHours = range(0, 23);
                foreach ($aHours as $iHour) {
                    if (isset($aParams['start_hour'])) {
                        if (substr($aParams['start_hour'], 0, 1) == '+') {
                            $aParams['start_hour'] = substr_replace($aParams['start_hour'], '', 0, 1);
                        }
                    }

                    if (strlen($iHour) < 2) {
                        $iHour = '0' . $iHour;
                    }

                    $sReturn .= "\t\t\t" . '<option value="' . $iHour . '"' . ($bSetEmptyAll ? '' : ($bForTemplate ? Phpfox::getLib('template.cache')->parseFunction('value', '', "type='select' id='{$sPrefix}hour' default='{$iHour}'") . (isset($aParams['default_all']) ? '<?php echo (!isset($this->_aVars[\'aForms\'][\'' . $sPrefix . 'hour\']) ? (\'' . $iHour . '\' == ' . (isset($aParams['start_hour']) ? '(Phpfox::getLib(\'date\')->modifyHours(\'+' . $aParams['start_hour'] . '\'))' : 'Phpfox::getTime(\'H\')') . ' ? \' selected="selected"\' : \'\') : \'\'); ?>' : '')
                            : (isset($iSelectedHour) && $iSelectedHour == $iHour ? ' selected="selected"' : '') .  (isset($aParams['default_all']) ? (!isset($iSelectedHour) ? ($iHour == (isset($aParams['start_hour']) ? (Phpfox::getLib('date')->modifyHours('+' . $aParams['start_hour'])) : Phpfox::getTime('H')) ? ' selected="selected"' : '') : '') : ''))) . '>' . $iHour . '</option>' . "\n";
                }
            } else {
                $hourRanges = range(1, 12);
                $aHours = [];

                if (!$bForTemplate) {
                    $defaultHour = !empty($iSelectedHour) ? $iSelectedHour : null;
                } else {
                    $aForms = Phpfox::getLib('template')->getVar('aForms');
                    $defaultHour = isset($aForms[$sPrefix . 'hour']) ? $aForms[$sPrefix . 'hour'] : null;
                }

                if (!isset($defaultHour)) {
                    if (isset($aParams['start_hour'])) {
                        if (substr($aParams['start_hour'], 0, 1) == '+') {
                            $aParams['start_hour'] = substr_replace($aParams['start_hour'], '', 0, 1);
                        }
                        $defaultHour = Phpfox::getLib('date')->modifyHours('+' . $aParams['start_hour']);
                    } else {
                        $defaultHour = Phpfox::getTime('H');
                    }
                }
                $defaultMeridiem = $defaultHour >= 12 ? 'pm' : 'am';
                foreach ($hourRanges as $hourRange) {
                    $aHours = array_merge($aHours, [
                        [
                            'text'  => $hourRange,
                            'value' => $hourRange == 12 ? 0 : $hourRange,
                            'type'  => 'am',
                        ],
                        [
                            'text'  => $hourRange,
                            'value' => $hourRange == 12 ? $hourRange : $hourRange + 12,
                            'type'  => 'pm',
                        ]
                    ]);
                }
                foreach ($aHours as $aHour) {
                    $iHour = $aHour['text'];
                    if (strlen($iHour) < 2) {
                        $iHour = '0' . $iHour;
                    }
                    $sReturn .= "\t\t\t" . '<option value="' . $aHour['value'] . '" ' . ($bSetEmptyAll ? ' ' : ($defaultHour == $aHour['value'] ? ' selected="selected"' : '')) . ' data-value="' . $aHour['text'] . '" data-type="' . $aHour['type'] . '" ' . ($aHour['type'] != $defaultMeridiem ? ' style="display: none;"' : '') . '>' . $iHour . '</option>' . "\n";
                }
            }
            $sReturn .= "\t\t" . '</select><span class="select-date-separator">:</span>' . "\n";

            $aMinutes = range(0, 59);
            $sReturn .= "\t\t" . '<select class="form-control" name="' . $sName . '[' . $sPrefix . 'minute]" id="' . $sPrefix . 'minute">' . "\n";
            foreach ($aMinutes as $iMinute) {
                if (strlen($iMinute) < 2) {
                    $iMinute = '0' . $iMinute;
                }
                $sReturn .= "\t\t\t" . '<option value="' . $iMinute . '"' . ($bSetEmptyAll ? '' : ($bForTemplate ? Phpfox::getLib('template.cache')->parseFunction('value', '', "type='select' id='{$sPrefix}minute' default='{$iMinute}'") . (isset($aArgs['default_all']) ? '<?php echo (!isset($this->_aVars[\'aForms\'][\'' . $sPrefix . 'minute\']) ? (\'' . $iMinute . '\' == Phpfox::getTime(\'i\') ? \' selected="selected"\' : \'\') : \'\'); ?>' : '')
                            : (isset($iSelectedMinute) && $iSelectedMinute == $iMinute ? ' selected="selected"' : '') .  (isset($aParams['default_all']) && !isset($iSelectedMinute) && $iMinute == Phpfox::getTime('i') ? ' selected="selected"' : ''))) . '>' . $iMinute . '</option>' . "\n";
            }
            $sReturn .= "\t\t" . '</select>' . "\n";

            $sReturn .= '</span>';


            if (!$is24Format) {
                $sReturn .= '<span class="form-inline js_datepicker_selects ml-1">';
                $meridiemValues = ['am', 'pm'];
                $sReturn .= "\t\t" . '<select class="form-control js_date_picker_meridiem_select" data-prefix="' . $sPrefix . '">' . "\n";
                foreach ($meridiemValues as $meridiemValue) {
                    $sReturn .= "\t\t\t" . '<option value="' . $meridiemValue . '"' . ($bSetEmptyAll ? ' ' : ($meridiemValue == $defaultMeridiem ? ' selected="selected"' : '')) . '>' . _p($meridiemValue) . '</option>' . "\n";
                }
                $sReturn .= "\t\t" . '</select></span>';
            }
        }

        if ($bUseJquery) {
            $sReturn .= '</div>';
        }

        return '<div class="form-inline select_date">' . $sReturn . (isset($sJsSortDesc) ? $sJsSortDesc : '') . '</div>';
    }

    public static function getCoreApp($refresh = false)
    {
        if (empty(self::$_coreApp) || $refresh) {
            self::$_coreApp = new \Core\App($refresh);
        }
        return self::$_coreApp;
    }

    public static function dependencyInjection()
    {
        self::$tablePrefix = Phpfox::getParam(['db', 'prefix']);
        // set settings to cache
        Phpfox::getLib('setting')->set();
        self::$dependencyManager['l_setting'] = Phpfox::getLib('setting');
        self::$sessionPrefix = Phpfox::getParam('core.session_prefix');
    }

    public static function getDependencyManager()
    {
        $s = array_keys(self::$dependencyManager);
        asort($s);
        return $s;
    }

    /**
     * Get the phpFox version.
     *
     * @return string
     */
    public static function getVersion()
    {
        return self::VERSION;
    }

    /**
     * Get the current phpFox version.
     *
     * @return array|int|resource|string
     */
    public static function getCurrentVersion()
    {
        $sCacheId = Phpfox::getLib('cache')->set('core_current_version');
        if (($version = Phpfox::getLib('cache')->get($sCacheId)) === false) {
            $version = db()->select('value_actual')
                ->from(Phpfox::getT('setting'))
                ->where(['var_name' => 'phpfox_version'])->executeField(false);
            if (!$version) { // get old setting from phpFox <= 4.7.10
                $versionFile = PHPFOX_DIR_SETTINGS . 'version.sett.php';
                if (file_exists($versionFile)) {
                    $object = (object)require($versionFile);
                    if (isset($object->version)) {
                        $version = $object->version;
                    }
                }
            }
            Phpfox::getLib('cache')->save($sCacheId, ($version ? $version : '4.8.0'));
        }
        return $version;
    }

    /**
     * NOTE: Do not use this function to version_compare.
     * @return string
     */
    public static function getDisplayVersion()
    {
        $sVersion = self::VERSION;
        if (self::PRODUCT_BUILD > 1) {
            $sVersion .= ' (' . _p('build') . ' ' . self::PRODUCT_BUILD . ')';
        }

        return $sVersion;
    }

    public static function isTrial()
    {
        return function_exists('ioncube_file_info') && is_array(ioncube_file_info());
    }


    /**
     * @return Phpfox_Config_Container
     */
    public static function configs()
    {
        if (null == self::$_config) {
            self::$_config = new Phpfox_Config_Container();
        }
        return self::$_config;
    }

    /**
     * @param      $section
     * @param null $item
     *
     * @return mixed|null
     * @see Phpfox_Config_Container::get()
     *
     */
    public static function getConfig($section, $item = null)
    {
        if (null == self::$_config) {
            self::$_config = new Phpfox_Config_Container();
        }

        return self::$_config->get($section, $item);
    }

    /**
     * Get the current phpFox version ID.
     *
     * @return int
     */
    public static function getId()
    {
        return self::getVersion();
    }

    /**
     * Get the products code name.
     *
     * @return string
     */
    public static function getCodeName()
    {
        return self::CODE_NAME;
    }

    /**
     * Get the products build number.
     *
     * @return int
     */
    public static function getBuild()
    {
        return self::PRODUCT_BUILD;
    }

    public static function getFullVersion()
    {
        return self::getCleanVersion();
    }

    /**
     * Get the clean numerical value of the phpFox version.
     *
     * @return int
     */
    public static function getCleanVersion()
    {
        return str_replace('.', '', self::VERSION);
    }

    protected static $internalVersion;

    public static function internalVersion()
    {
        if (self::$internalVersion) {
            return self::$internalVersion;
        }
        $version = self::getCleanVersion();
        $version .= Phpfox::getParam('core.css_edit_id');
        if (defined('PHPFOX_NO_CSS_CACHE')) {
            return Phpfox::getTime();
        }
        self::$internalVersion = $version;
        return $version;
    }

    /**
     * Check if a feature can be used based on the package the client
     * has installed.
     *
     * Example (STRING):
     * <code>
     * if (Phpfox::isPackage('1') { }
     * </code>
     *
     * Example (ARRAY):
     * <code>
     * if (Phpfox::isPackage(array('1', '2')) { }
     * </code>
     *
     * @param mixed $mPackage STRING can be used to pass the package ID, or an ARRAY to pass multiple packages.
     *
     * @return bool
     */
    public static function isPackage($mPackage)
    {
        $iPackageId = 3;

        if (!is_array($mPackage)) {
            $mPackage = [$mPackage];
        }

        if (!defined('PHPFOX_INSTALLER') && PHPFOX_LICENSE_ID != 'techie') {
            $iPackageId = PHPFOX_PACKAGE_ID;
        }

        return (in_array($iPackageId, $mPackage) ? true : false);
    }

    /**
     * Provide "powered by" link.
     *
     * @param bool $bLink    TRUE to include a link to phpFox.
     * @param bool $bVersion TRUE to include the version being used.
     *
     * @return string Powered by phpFox string returned.
     */
    public static function link($bLink = true, $bVersion = true)
    {
        if (Phpfox::getParam('core.branding')) {
            return '';
        }

        $sVersion = Phpfox::getVersion();
        if (Phpfox::PRODUCT_BETA) {
            $sVersion .= '-' . Phpfox::PRODUCT_BETA;
        } else if (Phpfox::PRODUCT_BUILD > 1) {
            $sVersion .= 'p' . Phpfox::PRODUCT_BUILD;
        }
        return '' . ($bLink ? '<a href="http://www.phpfox.com/">' : '') . 'Powered By PHPFox' . ($bVersion ? ' Version ' . $sVersion : '') . ($bLink ? '</a>' : '');
    }

    /**
     * Gets and creates an object for a class.
     *
     * @param string $sClass  Class name.
     * @param array  $aParams Params to pass to the class.
     *
     * @return object Object created will be returned.
     */
    public static function &getObject($sClass, $aParams = [])
    {
        $sHash = md5($sClass . serialize($aParams));

        if (isset(self::$_aObject[$sHash])) {
            return self::$_aObject[$sHash];
        }

        (PHPFOX_DEBUG ? Phpfox_Debug::start('object') : false);

        $sClass = str_replace(['.', '-'], '_', $sClass);

        if (!class_exists($sClass)) {
            Phpfox_Error::trigger('Unable to call class: ' . $sClass, E_USER_ERROR);
        }

        if ($aParams) {
            self::$_aObject[$sHash] = new $sClass($aParams);
        } else {
            self::$_aObject[$sHash] = new $sClass();
        }

        (PHPFOX_DEBUG ? Phpfox_Debug::end('object', ['name' => $sClass]) : false);

        if (method_exists(self::$_aObject[$sHash], 'getInstance')) {
            return self::$_aObject[$sHash]->getInstance();
        }

        return self::$_aObject[$sHash];
    }

    /**
     * @param mixed $sVar
     * @param mixed $mVarDefault
     *
     * @return mixed
     * @see Phpfox_Setting::getParam()
     *
     * You can override setting by using env or ini configure with follow pattern
     * "phpfox.{$sVar}"
     *
     */
    public static function getParam($sVar, $mVarDefault = null)
    {
        return Phpfox::getLib('setting')->getParam($sVar, $mVarDefault);
    }

    /**
     * @param string $sVar
     *
     * @return bool
     */
    public static function hasEnvParam($sVar)
    {
        return Phpfox::getLib('setting')->hasEnvParam($sVar);
    }


    public static function demoModeActive()
    {
        if (defined('PHPFOX_DEMO_MODE')) {
            return true;
        }

        return false;
    }

    public static function demoMode($module = '', $method = '')
    {
        if (self::demoModeActive()) {
            $message = 'AdminCP is set to "Demo Mode". This action is not permitted when the site is in this mode.';
            $req = Phpfox_Request::instance();
            $val = Phpfox_Request::instance()->get('val');
            if (Phpfox_Request::instance()->method() == 'POST') {
                if ($module) {
                    $ajax = Phpfox_Ajax::instance();
                    $ajax->call('Admin_Demo_Message(\'' . $message . '\');');
                } else {
                    if (Phpfox_Request::instance()->get('is_ajax_post')
                        || !empty($_FILES['ajax_upload'])
                        || ($req->segment(2) == 'menu' && count($val) && !$req->get('id') && !$req->segment(3))
                    ) {
                        // else {
                        header('Content-Type: application/json');
                        echo json_encode(['run' => 'Admin_Demo_Message(\'' . $message . '\');']);
                        exit;
                    } else {
                        if (Phpfox_Request::instance()->segment(2) == 'setting'
                            || (Phpfox_Request::instance()->segment(2) == 'page' && Phpfox_Request::instance()->segment(3) == 'add')
                        ) {
                            header('Content-Type: application/json');
                            echo json_encode(['error' => $message]);
                            exit;
                        }
                    }
                }

                return true;
            } else {
                if (Phpfox_Request::instance()->method() == 'GET') {
                    $route = trim(Phpfox_Url::instance()->getUri(), '/');
                    $sections = [
                        'admincp/store',
                        'admincp/store/orders',
                        'admincp/core/system',
                        'admincp/checksum/modified',
                        'admincp/checksum/unknown',
                        'admincp/product/file',
                        'admincp/product'
                    ];
                    if (in_array($route, $sections)) {
                        return true;
                    } else {
                        if (($req->segment(2) == 'menu' || $req->segment(2) == 'block') && $req->get('delete')) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get a phpFox library. This includes the class file and creates the object for you.
     *
     * Example usage:
     * <code>
     * Phpfox_Url::instance()->makeUrl('test');
     * </code>
     * In the example we called the URL library found in the folder: include/library/phpfox/url/url.class.php
     * then created an object for it so we could directly call the method "makeUrl".
     *
     * @param string $sClass  Library class name.
     * @param array  $aParams ARRAY of params you can pass to the library.
     *
     * @return object Object of the library class is returned.
     * `aParams` deprecated since 4.7.
     */
    public static function getLib($sClass, $aParams = [])
    {
        $cache = 'l_' . $sClass;
        /* Optimize: Cache Object */
        if (isset(self::$dependencyManager[$cache])) {
            return self::$dependencyManager[$cache];
        }
        return (self::$dependencyManager[$cache] = self::getLibContainer()->get($sClass));
    }

    /**
     * @return Phpfox_Service_Container
     */
    public static function getLibContainer()
    {
        if (null == self::$_libContainer) {
            self::$_libContainer = new Phpfox_Service_Container();
        }

        return self::$_libContainer;
    }

    /**
     * @param string $sModule
     *
     * @return bool
     * @see Phpfox_Module::isModule()
     *
     */
    public static function isModule($sModule)
    {
        return Phpfox_Module::instance()->isModule($sModule);
    }

    /**
     * Check is module or apps
     *
     * @param string $sName
     * @param bool   $bNoCheckModule
     *
     * @return bool
     */
    public static function isApps($sName, $bNoCheckModule = true)
    {
        return Phpfox_Module::instance()->isApps($sName, false, $bNoCheckModule);
    }


    /**
     * Check an app is active
     *
     * @param $sAppId
     *
     * @return bool
     */
    public static function isAppActive($sAppId)
    {
        return Core\App::isAppActive($sAppId);
    }

    /**
     * Check a name is a alias of app or not
     *
     * @param string $sName
     * @param bool   $bReturnId
     *
     * @return bool|string
     */
    public static function isAppAlias($sName = '', $bReturnId = false)
    {
        if (empty($sName)) {
            return false;
        }
        $sAppId = db()->select('apps_id')
            ->from(':apps')
            ->where('apps_alias=\'' . db()->escape($sName) . '\'')
            ->executeField();
        if ($bReturnId) {
            return (!empty($sAppId)) ? $sAppId : false;
        } else {
            return !empty($sAppId);
        }
    }

    /**
     * Use to check is app alias multiple times.
     * @return array
     */
    public static function getAppIds()
    {
        return array_map(function ($item) {
            return $item['apps_id'];
        }, db()->select('apps_id')
            ->from(':apps')
            ->executeRows());
    }

    /**
     * Use this method to check duplicate settings when app & module has same alias
     * 1. if apps is enable => avoid settings from modules
     * 2. if apps is disable => avoid settings from apps
     *
     * Example result when enable apps Core_Photos and disable apps Core_Groups
     *
     *  array (
     *   'photo' => 'phpfox'
     * ),
     *
     * @return array
     */
    public static function getExcludeSettingsConditions()
    {
        $activeApps = [];
        $activeModules = [];
        $disableApps = [];
        $excludes = [];

        $items = db()->select('*')
            ->from(':module')
            ->where('is_active=1')
            ->executeRows();
        array_walk($items, function ($row) use (&$activeModules) {
            $activeModules[$row['module_id']] = $row['product_id'];
        });

        $items = db()->select('*')
            ->from(':apps')
            ->where('apps_alias <> \'\' AND apps_alias IS NOT NULL')
            ->executeRows();
        array_walk($items, function ($row) use (&$activeApps, &$disableApps) {
            if ($row['is_active']) {
                $activeApps[$row['apps_id']] = $row['apps_alias'];
            } else {
                $disableApps[$row['apps_id']] = $row['apps_alias'];
            }
        });

        // active app will disable module setting
        foreach ($activeApps as $appId => $moduleId) {
            if (array_key_exists($moduleId, $activeModules)) {
                $excludes[$moduleId] = $activeModules[$moduleId];
            }
        }

        // disable app, don't check module settings.
        foreach ($disableApps as $appId => $moduleId) {
            $excludes[$moduleId] = $appId;
        }
        return $excludes;
    }

    /**
     * @param string|array $sClass
     * @param array        $aParams
     * @param bool         $bTemplateParams
     *
     * @return object
     * @see Phpfox_Module::getComponent()
     *
     */
    public static function getBlock($sClass, $aParams = [], $bTemplateParams = false)
    {
        if (is_array($sClass)) {
            if (isset($sClass['type_id']) and isset($sClass['component'])) {
                if ($sClass['type_id'] == 0) {
                    $sClass = $sClass['component'];
                } else {
                    if ($sClass['type_id'] == 1) {
                        $sClass = [$sClass['component']];
                    } else {
                        if ($sClass['type_id'] == 2) {
                            $sClass = [$sClass['component']];
                        }
                    }
                }
            }
            if (isset($sClass['params'])) {
                $aParams = $sClass['params'];
            }
        }

        $sHidden = '';
        if (isset($aParams['hidden']) && is_array($aParams['hidden'])) {
            $sHidden = implode(' ', $aParams['hidden']);
        }

        if ($sClass instanceof \Closure) {
            $content = call_user_func($sClass);

            echo '<div class="', $sHidden, '">', $content, '</div>';

            return null;
        }

        if (is_array($sClass)) {
            if (isset($sClass['callback'])) {
                $content = call_user_func($sClass['callback'], $sClass['object']);
            } else {
                $content = $sClass[0];
            }

            if (empty($content)) {
                $obj = $sClass['object'];
                if ($obj instanceof \Core\Block) {
                    if (empty($html)) {
                        $content = '
						<div class="block">
							' . ($obj->get('title') ? '<div class="title">' . $obj->get('title') . '</div>' : '') . '
							<div class="content">
								' . $obj->get('content') . '
							</div>
						</div>
						';
                    }
                }
            }
            if (file_exists($content)) {
                echo '<div class="', $sHidden, '">';
                require_once $content;
                echo '</div>';
            } else {
                echo '<div class="', $sHidden, '">', $content, '</div>';
            }

            return null;
        }

        return Phpfox_Module::instance()->getComponent($sClass, $aParams, 'block', $bTemplateParams);
    }

    /**
     * @param string $sCall
     *
     * @return mixed
     * @see Phpfox_Module::callback()
     *
     */
    public static function callback($sCall)
    {
        if (func_num_args() > 1) {
            $aParams = func_get_args();
            return Phpfox_Module::instance()->callback($sCall, $aParams);
        }

        return Phpfox_Module::instance()->callback($sCall);
    }

    /**
     * @param string $sMethod
     *
     * @return mixed
     * @see Phpfox_Module::massCallback()
     *
     */
    public static function massCallback($sMethod)
    {
        if (func_num_args() > 1) {
            $aParams = func_get_args();

            return Phpfox_Module::instance()->massCallback($sMethod, $aParams);
        }

        return Phpfox_Module::instance()->massCallback($sMethod);
    }

    /**
     * @param string $sModule
     * @param string $sMethod
     *
     * @return bool
     * @see Phpfox_Module::hasCallback()
     *
     */
    public static function hasCallback($sModule, $sMethod)
    {
        return Phpfox_Module::instance()->hasCallback($sModule, $sMethod);
    }

    /**
     * @param string $sClass  Class name.
     * @param array  $aParams ARRAY of params you can pass to the component.
     * @param string $sType   Type of component (block or controller).
     *
     * @return object We return the object of the component class.
     * @see Phpfox_Module::getComponent()
     *
     */
    public static function getComponent($sClass, $aParams = [], $sType = 'block', $bTemplateParams = false)
    {
        return Phpfox_Module::instance()->getComponent($sClass, $aParams, $sType, $bTemplateParams);
    }

    /**
     * @param int    $iUserId
     * @param string $sVarName
     * @param mixed  $mDefaultValue
     *
     * @return mixed
     * @see Phpfox_Module::getComponentSetting()
     *
     */
    public static function getComponentSetting($iUserId, $sVarName, $mDefaultValue)
    {
        return Phpfox_Module::instance()->getComponentSetting($iUserId, $sVarName, $mDefaultValue);
    }

    /**
     * Returns the token name for forms
     */
    public static function getTokenName()
    {
        return 'core';
    }

    /**
     * @param string $sClass
     * @param array  $aParams
     *
     * @return object
     * @see Phpfox_Module::getService()
     *
     */
    public static function getService($sClass, $aParams = [])
    {
        $cache = 's_' . $sClass;
        if (isset(self::$dependencyManager[$cache])) {
            return self::$dependencyManager[$cache];
        }
        return (self::$dependencyManager[$cache] = Phpfox::getLib('module')->getService($sClass, null));
    }

    /**
     * Builds a database table prefix.
     *
     * @param string $sTable Database table name.
     *
     * @return string Returns the table name with the clients prefix.
     */
    public static function getT($sTable)
    {
        if ($sTable == 'ad_sponsor' && !Phpfox::isAdminPanel()) {
            $sTable = 'better_ads_sponsor';
        }
        return self::$tablePrefix . $sTable;
    }

    public static function getPageUserId()
    {
        if (self::$_pageUserId) {
            return self::$_pageUserId;
        }

        $aPage = Phpfox_Database::instance()->getRow('
				SELECT p.page_id, p.user_id AS owner_user_id, u.user_id
				FROM ' . Phpfox::getT('pages') . ' AS p
				JOIN ' . Phpfox::getT('user') . ' AS u ON(u.profile_page_id = p.page_id)
				WHERE p.item_type = 0 AND p.page_id = ' . (int)$_REQUEST['custom_pages_post_as_page'] . '
			');

        $iActualUserId = Phpfox::getService('user.auth')->getUserId();

        if (!defined('PHPFOX_POSTING_AS_PAGE')) {
            define('PHPFOX_POSTING_AS_PAGE', true);
        }

        if (isset($aPage['page_id'])) {
            $bPass = false;

            //check isAdmin
            if (Phpfox::getService('user')->isAdminUser($iActualUserId, true)) {
                $bPass = true;
            }

            if (!$bPass && $aPage['owner_user_id'] == $iActualUserId) {
                $bPass = true;
            }

            if (!$bPass) {
                $aAdmin = Phpfox_Database::instance()->getRow('
						SELECT page_id
						FROM ' . Phpfox::getT('pages_admin') . '
						WHERE page_id = ' . (int)$aPage['page_id'] . ' AND user_id = ' . (int)$iActualUserId . '
					');

                if (isset($aAdmin['page_id'])) {
                    $bPass = true;
                }
            }

            if ($bPass) {
                return self::$_pageUserId = $aPage['user_id'];
            }
        }
        return 0;
    }

    /**
     * @return int
     * @see User_Service_Auth::getUserId()
     */
    public static function getUserId()
    {
        static $sPlugin;

        if (isset($_REQUEST['custom_pages_post_as_page']) && (int)$_REQUEST['custom_pages_post_as_page'] > 0
        ) {
            return self::getPageUserId();
        }

        if (null === $sPlugin) {
            $sPlugin = Phpfox_Plugin::get('library_phpfox_phpfox_getuserid__1');
        }
        if ($sPlugin) {
            eval($sPlugin);
        }

        if (defined('PHPFOX_APP_USER_ID')) {
            return PHPFOX_APP_USER_ID;
        }

        return Phpfox::getService('user.auth')->getUserId();
    }

    /**
     * @return string|array
     * @see User_Service_Auth::getUserBy()
     */
    public static function getUserBy($sVar = null)
    {
        return Phpfox::getService('user.auth')->getUserBy($sVar);
    }

    /**
     * @return string
     * @see Phpfox_Request::getIp()
     */
    public static function getIp($bReturnNum = false)
    {
        return Phpfox_Request::instance()->getIp($bReturnNum);
    }

    /**
     * Checks to see if the user that is logged in has been marked as a spammer.
     *
     * @return bool TRUE is a spammer, FALSE if not a spammer.
     */
    public static function isSpammer()
    {
        if (Phpfox::getUserParam('core.is_spam_free')) {
            return false;
        }

        if (!Phpfox::getParam('core.enable_spam_check')) {
            return false;
        }

        if (Phpfox::isUser() && Phpfox::getUserBy('total_spam') > Phpfox::getParam('core.auto_deny_items')) {
            return true;
        }

        return false;
    }

    /**
     * Get all the user fields when joining with the user database table.
     *
     * @param string $sAlias  Table alias. User table alias by default is "u".
     * @param string $sPrefix Prefix for each of the fields.
     *
     * @return string Returns SQL SELECT for user fields.
     */
    public static function getUserField($sAlias = 'u', $sPrefix = '')
    {
        static $aValues = [];

        // Create hash
        $sHash = md5($sAlias . $sPrefix);

        // Have we already cached it? We do not want to run an extra foreach() for nothing.
        if (isset($aValues[$sHash])) {
            return $aValues[$sHash];
        }

        $aFields = Phpfox::getService('user')->getUserFields();

        $aValues[$sHash] = '';
        foreach ($aFields as $sField) {
            $aValues[$sHash] .= ", {$sAlias}.{$sField}";

            if ($sAlias == 'u' && $sField == 'server_id') {
                $aValues[$sHash] .= " AS user_{$sPrefix}{$sField}";
                continue;
            }

            if (!empty($sPrefix)) {
                $aValues[$sHash] .= " AS {$sPrefix}{$sField}";
            }
        }
        $aValues[$sHash] = ltrim($aValues[$sHash], ',');

        return $aValues[$sHash];
    }

    /**
     * @param bool $bDst
     * @param null $iTime
     *
     * @return float|int|mixed|string|null
     * @throws Exception
     * @see Phpfox_Date::getTimeZone()
     */
    public static function getTimeZone($bDst = true, $iTime = null)
    {
        return Phpfox::getLib('date')->getTimeZone($bDst, $iTime);
    }

    /**
     * Gets a time stamp, Works similar to PHP date() function.
     * We also take into account locale and time zone settings.
     *
     * @param null $sStamp       Time stamp format.
     * @param int  $iTime        UNIX epoch time stamp.
     * @param bool $bTimeZone    $bTimeZone
     * @param bool $bIsShortType Short type of date
     *
     * @return false|float|int|mixed|string Time stamp value based on locale.
     * @throws Exception
     */
    public static function getTime($sStamp = null, $iTime = PHPFOX_TIME, $bTimeZone = true, $bIsShortType = false)
    {
        static $aReplaceDay, $aReplaceMonth, $aReplaceDayShort, $aReplaceMonthShort;
        $iNewTime = '';

        if ($bTimeZone) {
            $sUserOffSet = Phpfox::getTimeZone(true, $iTime);

            if ($sStamp === null) {
                return (!empty($sUserOffSet) ? (substr($sUserOffSet, 0, 1) == '-' ? ($iTime - (substr($sUserOffSet,
                            1) * 3600)) : (((int)$sUserOffSet) * 3600) + $iTime) : $iTime);
            } else if (!isset($bSet)) {
                $iNewTime = (!empty($sUserOffSet) ? date($sStamp,
                    (substr($sUserOffSet, 0, 1) == '-' ? ($iTime - (substr($sUserOffSet,
                                1) * 3600)) : ($sUserOffSet * 3600) + $iTime)) : date($sStamp, $iTime));

            }
        } else {
            $iNewTime = date($sStamp, $iTime);
        }

        $aDay = [
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
            'Sunday'
        ];

        $aMonth = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December',
        ];

        $aDayShort = [
            'Mon',
            'Tue',
            'Wed',
            'Thu',
            'Fri',
            'Sat',
            'Sun'
        ];

        $aMonthShort = [
            'Jan',
            'Feb',
            'Mar',
            'Apr',
            'May',
            'Jun',
            'Jul',
            'Aug',
            'Sep',
            'Oct',
            'Nov',
            'Dec',
        ];

        // check short type
        if (!$bIsShortType) {
            $iNewTime = str_replace($aDayShort, $aDay, $iNewTime);
            $iNewTime = str_replace($aMonthShort, $aMonth, $iNewTime);
        } else {
            $iNewTime = str_replace($aDay, $aDayShort, $iNewTime);
            $iNewTime = str_replace($aMonth, $aMonthShort, $iNewTime);
        }

        $aDayDuplicated = [
            'Mondayday',
            'Tuesdaysday',
            'Wednesdaynesday',
            'Thursdayrsday',
            'Fridayday',
            'Saturdayurday',
            'Sundayday'
        ];

        $aMonthDuplcated = [
            'Januaryuary',
            'Februaryruary',
            'Marchch',
            'Aprilil',
            'May',
            'Junee',
            'Julyy',
            'Augustust',
            'Septembertember',
            'Octoberober',
            'Novemberember',
            'Decemberember',
        ];

        // replace duplicated to correctly
        $iNewTime = str_replace($aDayDuplicated, $aDay, $iNewTime);
        $iNewTime = str_replace($aMonthDuplcated, $aMonth, $iNewTime);

        if (!$aReplaceDay) {
            $aReplaceDay = [
                _p('monday'),
                _p('tuesday'),
                _p('wednesday'),
                _p('thursday'),
                _p('friday'),
                _p('saturday'),
                _p('sunday')
            ];
        }

        if (!$aReplaceDayShort) {
            $aReplaceDayShort = [
                _p('mon'),
                _p('tue'),
                _p('wed'),
                _p('thu'),
                _p('fri'),
                _p('sat'),
                _p('sun')
            ];
        }

        if (!$aReplaceMonth) {
            $aReplaceMonth = [
                _p('january'),
                _p('february'),
                _p('march'),
                _p('april'),
                _p('may'),
                _p('june'),
                _p('july'),
                _p('august'),
                _p('september'),
                _p('october'),
                _p('november'),
                _p('december')
            ];
        }

        if (!$aReplaceMonthShort) {
            $aReplaceMonthShort = [
                _p('jan'),
                _p('feb'),
                _p('mar'),
                _p('apr'),
                _p('may'),
                _p('jun'),
                _p('jul'),
                _p('aug'),
                _p('sep'),
                _p('oct'),
                _p('nov'),
                _p('dec')
            ];
        }

        // replace with phrase translated
        $iNewTime = str_replace($aDay, $aReplaceDay, $iNewTime, $iCount);
        if (!$iCount) {
            $iNewTime = str_replace($aDayShort, $aReplaceDayShort, $iNewTime, $iCount);
        }
        $iNewTime = str_replace($aMonth, $aReplaceMonth, $iNewTime, $iCount);
        if (!$iCount) {
            $iNewTime = str_replace($aMonthShort, $aReplaceMonthShort, $iNewTime, $iCount);
        }
        $iNewTime = str_replace(['PM', 'pm'], _p('pm'), $iNewTime);
        $iNewTime = str_replace(['AM', 'am'], _p('am'), $iNewTime);

        return $iNewTime;
    }

    /**
     * Used to see if a user is logged in or not. By passing the first argument as TRUE
     * we can also do an auto redirect to guide the user to login first before using a
     * feature.
     *
     * @param bool $bRedirect User will be redirected to the login page if they are not logged int.
     *
     * @return bool If the 1st argument is FALSE, it will return a BOOL TRUE if the user is logged in, otherwise FALSE.
     */
    public static function isUser($bRedirect = false)
    {
        if (defined('PHPFOX_APP_USER_ID')) {
            return true;
        }
        $bIsUser = Phpfox::getService('user.auth')->isUser();

        if ($bRedirect && !$bIsUser) {
            if (PHPFOX_IS_AJAX || PHPFOX_IS_AJAX_PAGE) {
                return Phpfox_Ajax::instance()->isUser();
            } else {
                // Create a session so we know where we plan to redirect the user after they login
                $url = Phpfox_Url::instance()->getFullUrl();
                Phpfox::getLib('session')->set('redirect', $url);
                Phpfox_Url::instance()->send('user.login');
            }
        }

        return $bIsUser;
    }

    /**
     * Used to see if a user is an Admin. By passing the first argument as TRUE
     * we can also do an auto redirect to guide the user to login first before using a
     * feature in the AdminCP.
     *
     * @param bool $bRedirect User will be redirected to the AdminCP login page if they are not logged int.
     *
     * @return bool If the 1st argument is FALSE, it will return a BOOL TRUE if the user is logged in, otherwise FALSE.
     */
    public static function isAdmin($bRedirect = false)
    {
        if (!Phpfox::isUser($bRedirect)) {
            return false;
        }

        if (!Phpfox::getUserParam('admincp.has_admin_access', $bRedirect)) {
            return false;
        }

        return true;
    }

    public static function isTechie()
    {
        return (defined('PHPFOX_IS_TECHIE') && PHPFOX_IS_TECHIE);
    }

    /**
     * @param int    $iGroupId
     * @param string $sName
     *
     * @return mixed
     * @see User_Service_Group_Setting_Setting::getGroupParam()
     *
     */
    public static function getUserGroupParam($iGroupId, $sName)
    {
        return Phpfox::getService('user.group.setting')->getGroupParam($iGroupId, $sName);
    }

    /**
     * Get a user group setting.
     *
     * @param string|array $sName     User group param name.
     * @param bool         $bRedirect TRUE will redirect the user to a subscribtion page if they do not have access to the param.
     * @param mixed        $sJsCall   NULL will do nothing, however a STRING JavaScript code will run the code instead of a redirection.
     *
     * @return bool
     * @see User_Service_Group_Setting_Setting::getParam()
     *
     */
    public static function getUserParam($sName, $bRedirect = false, $sJsCall = null)
    {
        if (defined('PHPFOX_INSTALLER') and !defined('PHPFOX_IS_UPGRADE')) {
            return true;
        }

        $bPass = false;
        // Is this an array
        if (is_array($sName)) {
            // Get the array key
            $sKey = array_keys($sName);

            // Get the setting value
            $sValue = Phpfox::getService('user.group.setting')->getParam($sKey[0]);

            // Do the evil eval to get our new value
            eval('$bPass = (' . $sValue . ' ' . $sName[$sKey[0]][0] . ' ' . $sName[$sKey[0]][1] . ');');
        } else {
            $bPass = (Phpfox::getService('user.group.setting')->getParam($sName) ? true : false);
            if ($sName == 'admincp.has_admin_access' && Phpfox::getParam('core.protect_admincp_with_ips') != '') {
                $bPass = false;
                $aIps = explode(',', Phpfox::getParam('core.protect_admincp_with_ips'));
                foreach ($aIps as $sIp) {
                    $sIp = trim($sIp);
                    if (empty($sIp)) {
                        continue;
                    }

                    if ($sIp == Phpfox::getIp()) {
                        $bPass = true;
                        break;
                    }
                }
            }
        }

        if (!$bPass && $bRedirect) {
            self::redirectByPermissionDenied();
            return true;
        } else {
            if (is_array($sName)) {
                return $bPass;
            } else {
                return Phpfox::getService('user.group.setting')->getParam($sName);
            }
        }
    }

    public static function redirectByPermissionDenied()
    {
        $sJsCall = null;
        if (PHPFOX_IS_AJAX) {
            if (!Phpfox::isUser()) {
                // Are we using thickbox?
                if (Phpfox_Request::instance()->get('tb')) {
                    Phpfox::getBlock('user.login-ajax');
                } else {
                    // If we passed an AJAX call we execute it
                    if ($sJsCall !== null) {
                        echo $sJsCall;
                    }
                    echo "tb_show('" . _p('sign_in') . "', \$.ajaxBox('user.login', 'height=250&width=400'));";
                }
            } else {
                // Are we using thickbox?
                if (Phpfox_Request::instance()->get('tb')) {
                    Phpfox::getBlock('subscribe.message');
                } else {
                    // If we passed an AJAX call we execute it
                    if ($sJsCall !== null) {
                        // echo $sJsCall;
                    }
                    echo "/*<script type='text/javascript'>*/window.location.href = '" . Phpfox_Url::instance()->makeUrl('privacy.invalid') . "';/*</script>*/";
                }
            }
            exit;
        } else {
            if (!Phpfox::isUser()) {
                // Create a session so we know where we plan to redirect the user after they login
                Phpfox::getLib('session')->set('redirect', Phpfox_Url::instance()->getFullUrl(true));

                // Okay thats it lets send them away so they can login
                Phpfox_Url::instance()->send('user.login');
            } else {
                Phpfox_Url::instance()->send('subscribe');
            }
        }
    }

    /**
     * Check to see if we are in the AdminCP or not.
     *
     * @return bool if we are, FALSE if we are not.
     */
    public static function isAdminPanel()
    {
        return (self::$_bIsAdminCp ? true : false);
    }

    /**
     * Set to AdminCP.
     *
     * @return TRUE if we can, FALSE if we can not.
     */
    public static function setAdminPanel()
    {
        if (Phpfox::isAdmin()) {
            self::$_bIsAdminCp = true;
            return true;
        }
        return false;
    }

    /**
     * Returns an array with the css and js files to be loaded in every controller
     */
    public static function getMasterFiles()
    {
        $aOut = [
            'font-awesome/css/font-awesome.min.css'               => 'style_css',
            'icofont.css'                                         => 'style_css',
            'jquery/jquery.js'                                    => 'static_script',
            'jquery/ui.js'                                        => 'static_script',
            'jquery/plugin/jquery.nanoscroller.min.js'            => 'static_script',
            'common.js'                                           => 'static_script',
            'main.js'                                             => 'static_script',
            'ajax.js'                                             => 'static_script',
            'thickbox/thickbox.js'                                => 'static_script',
            'search.js'                                           => 'module_friend',
            'search-members.js'                                   => 'module_user',
            'progress.js'                                         => 'static_script',
            'nprogress.js'                                        => 'static_script',
            'quick_edit.js'                                       => 'static_script',
            'feed.js'                                             => 'module_feed',
            'exif.js'                                             => 'static_script',
            'dropzone.js'                                         => 'static_script',
            'jquery/plugin/bootstrap-tokenfield.min.js'           => 'static_script',
            'bootstrap-tokenfield.min.css'                        => 'style_css',
            'register.js'                                         => 'module_user',
            'gender.js'                                           => 'module_user',
            'gmap.js'                                             => 'module_core',
            'user_info.js'                                        => 'static_script',
            'jquery/plugin/jquery.mCustomScrollbar.concat.min.js' => 'static_script',
            'jquery.mCustomScrollbar.min.css'                     => 'style_css',
            'owl_carousel/owl.carousel.min.js'                    => 'static_script',
            'owl_carousel/owl.carousel.min.css'                   => 'style_css',
            'owl_carousel/owl.theme.default.min.css'              => 'style_css',
            'asBreadcrumbs.min.css'                               => 'style_css',
            'jquery-asBreadcrumbs.js'                             => 'static_script',
            'masonry/masonry.min.js'                              => 'static_script',
            'selectize/selectize.min.js'                          => 'static_script',
            'selectize.css'                                       => 'style_css',
            'schedule-form.js'                                    => 'module_core',
        ];

        if (Phpfox::isModule('feed')) {
            $aOut['places.js'] = 'module_feed';
        }

        if (Phpfox::isAdminPanel()) {
            $aOut = array_merge([
                'bootstrap.min.css' => 'style_css',
                'layout.css'        => 'style_css',
                'common.css'        => 'style_css',
                'thickbox.css'      => 'style_css',
                'jquery.css'        => 'style_css',
                'comment.css'       => 'style_css',
                'pager.css'         => 'style_css'
            ], $aOut);
        }

        (($sPlugin = Phpfox_Plugin::get('get_master_files')) ? eval($sPlugin) : false);

        return $aOut;
    }

    /**
     * @return array
     */
    public static function getMasterPhrase()
    {
        $aOut = [
            'search_for_your_friends_dot',
            'save',
            'changes_you_made_may_not_be_saved',
            'search_friends_dot_dot_dot',
            'write_a_reply',
            'b',
            'kb',
            'mb',
            'gb',
            'tb'

        ];
        if (Phpfox::isModule('comment')) {
            $aOut[] = 'view_previous_comments';
        }
        if (Phpfox::isModule('feed')) {
            $aOut[] = 'show_more';
            $aOut[] = 'hide_all_from_full_name';
            $aOut[] = 'unhide_number_items';
            $aOut[] = 'unhide_one_item';
            $aOut[] = 'you_wont_see_this_post_in_news_feed_undo';
            $aOut[] = 'undo';
            $aOut[] = 'you_wont_see_posts_from_full_name_undo';
            $aOut[] = 'one_item_selected';
            $aOut[] = 'number_items_selected';
            $aOut[] = 'pages';
            $aOut[] = 'groups';
            $aOut[] = 'you';
            $aOut[] = 'you_wont_be_tagged_in_this_post_anymore';
            $aOut[] = 'tag_removed';
        }

        (($sPlugin = Phpfox_Plugin::get('get_master_phrases')) ? eval($sPlugin) : false);

        return $aOut;
    }

    public static function getPagesType($iId)
    {
        return Phpfox::getLib('pages.facade')->getPageItemType($iId);
    }

    /**
     * Starts the phpFox engine. Used to get and display the pages controller.
     *
     */
    public static function run()
    {
        $oTpl = Phpfox_Template::instance();
        $aLocale = Phpfox_Locale::instance()->getLang();
        $oReq = Phpfox_Request::instance();
        $oModule = Phpfox_Module::instance();

        if ($oReq->segment(1) == 'favicon.ico') {
            header('Content-type: image/x-icon');
            if (file_exists(PHPFOX_DIR . '../favicon.ico')) {
                echo file_get_contents(PHPFOX_DIR . '../favicon.ico');
            } else {
                echo file_get_contents(PHPFOX_PARENT_DIR . 'PF.Base/theme/frontend/default/style/default/image/favicon.ico');
            }
            exit;
        } else if ($sImage = $oReq->get('external')) {
            self::fetchExternalThenExit($sImage);
        } else if (Phpfox::getParam('core.url_rewrite') == 1 && $oReq->segment(1) == 'index.php') {
            $sUrl = Phpfox_Url::instance()->getFullUrl();
            $sUrl = str_replace('/index.php', '', $sUrl);
            $sUrl = str_replace('/index/php', '', $sUrl);
            Phpfox_Url::instance()->send($sUrl);
        }

        $aStaticFolders = ['_ajax', 'file', 'static', 'module', 'apps', 'Apps', 'themes'];
        if (in_array($oReq->segment(1), $aStaticFolders) ||
            (
                $oReq->segment(1) == 'theme' && $oReq->segment(2) != 'demo'
                && $oReq->segment(1) == 'theme' && $oReq->segment(2) != 'sample'
            )
        ) {
            $sUri = Phpfox_Url::instance()->getUri();
            if ($sUri == '/_ajax/') {
                $oAjax = Phpfox_Ajax::instance();
                $oAjax->process();
                echo $oAjax->getData();
                exit;
            }

            $sDir = PHPFOX_DIR;
            if ($oReq->segment(1) == 'Apps' || $oReq->segment(1) == 'apps' || $oReq->segment(1) == 'themes') {
                $sDir = PHPFOX_DIR_SITE;
            }
            $sPath = $sDir . trim($sUri, '/');

            if ($oReq->segment(1) == 'themes' && $oReq->segment(2) == 'default') {
                $sPath = PHPFOX_DIR . str_replace('themes/default', 'theme/default', $sUri);
            }

            $sType = Phpfox_File::instance()->mime($sUri);
            if (in_array(strtolower(substr($sUri, -4)), ['.php', 'php\\', 'php/'])) {
                header("HTTP/1.0 404 Not Found");
                header('Content-type: application/json');
                echo json_encode([
                    'error' => 404
                ]);
                exit;
            }

            if (!file_exists($sPath)) {
                $sPath = str_replace('PF.Base', 'PF.Base/..', $sPath);
                if (!file_exists($sPath)) {
                    header("HTTP/1.0 404 Not Found");
                    header('Content-type: application/json');
                    echo json_encode([
                        'error' => 404
                    ]);
                    exit;
                }
            }

            if ($oReq->segment(1) == 'themes') {
                $Theme = $oTpl->theme()->get();
                $Service = new Core\Theme\Service($Theme);
                if ($sType == 'text/css') {
                    if (file_exists($sPath)) {
                        echo @file_get_contents($sPath);
                    } else {
                        echo $Service->css()->getParsed();
                    }
                } else {
                    echo $Service->js()->get();
                }
            } else {
                echo @fox_get_contents($sPath);
            }
            exit;
        }


        (($sPlugin = Phpfox_Plugin::get('run_start')) ? eval($sPlugin) : false);

        if (strtolower($oReq->get('req1')) == 'admincp' || $oReq->get('bIsAdminCp', false)) {
            if (self::setAdminPanel() && !defined('PHPFOX_ADMIN_PANEL')) {
                define('PHPFOX_ADMIN_PANEL', true);
                header('Cache-Control: no-store, no-cache, must-revalidate');
            }
        } else if (self::demoModeActive() && Phpfox::isAdmin() && strtolower($oReq->get('req1')) !== 'flavors') {
            Phpfox::getLib('url')->send('admincp');
        }


        // Load module blocks
        $oModule->loadBlocks();

        if (!Phpfox::getParam('core.branding')) {
            $oTpl->setHeader(['<meta name="author" content="phpFox" />']);
        }

        $View = $oModule->setController();

        if ($View instanceof Core\View) {

        } else {
            if (!self::$_bIsAdminCp) {
                $View = new Core\View();
            }
        }

        (($sPlugin = Phpfox_Plugin::get('run_set_controller')) ? eval($sPlugin) : false);

        if (!PHPFOX_IS_AJAX_PAGE) {
            $oTpl->setImage([
                'ajax_small'        => 'ajax/small.gif',
                'ajax_large'        => 'ajax/large.gif',
                'loading_animation' => 'misc/loading_animation.gif',
                'close'             => 'misc/close.gif',
                'move'              => 'misc/move.png',
                'calendar'          => 'jquery/calendar.gif'
            ]);

            $favicon = Phpfox::getParam('core.path') . 'favicon.ico?v=' . $oTpl->getStaticVersion();

            (($sPlugin = Phpfox_Plugin::get('favicon')) ? eval($sPlugin) : false);

            $oTpl->setHeader([
                    '<meta http-equiv="X-UA-Compatible" content="IE=edge">',
                    '<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">',
                    '<meta http-equiv="Content-Type" content="text/html;charset=' . $aLocale['charset'] . '" />',
                    '<link rel="shortcut icon" type="image/x-icon" href="' . $favicon . '" />'
                ]
            )
                ->setMeta('keywords', Phpfox_Locale::instance()->convert(Phpfox::getParam('core.keywords')))
                ->setMeta('robots', 'index,follow');

            $oTpl->setPhrase(Phpfox::getMasterPhrase());

            if (Phpfox::isModule('friend')) {
                $oTpl->setPhrase(['show_more_results_for_search_term']);
            }

            if (Phpfox::isAppActive('Core_Messages')) {
                $oTpl->setPhrase(['can_not_send_empty_message']);
            }

            if (PHPFOX_DEBUG && self::isAdminPanel()) {
                $oTpl->setHeader('cache', ['debug.css' => 'style_css']);
            }
        }

        if ($sPlugin = Phpfox_Plugin::get('get_controller')) {
            eval($sPlugin);
        }


        $oTpl->assign([
            'aGlobalUser'   => (Phpfox::isUser() ? Phpfox::getUserBy(null) : []),
            'bIsDetailPage' => false
        ]);

        $oModule->getController();

        Phpfox::getService('admincp.seo')->setHeaders();

        if (!defined('PHPFOX_DONT_SAVE_PAGE')) {
            Phpfox::getLib('session')->set('redirect', Phpfox_Url::instance()->getFullUrl(true));
        }

        if (!defined('PHPFOX_NO_CSRF')) {
            Phpfox::getService('log.session')->verifyToken();
        }

        (($sPlugin = Phpfox_Plugin::get('run')) ? eval($sPlugin) : false);

        $sMenuSelected = '';
        $bIsPopup = $oReq->get('is_ajax_popup', false);
        if (!self::isAdminPanel()) {
            if (!PHPFOX_IS_AJAX_PAGE && Phpfox::isAppActive('Core_RSS') && !defined('PHPFOX_IS_USER_PROFILE')) {
                $aFeeds = Phpfox::getService('rss')->getLinks();
                if (is_array($aFeeds) && count($aFeeds)) {
                    foreach ($aFeeds as $sLink => $sPhrase) {
                        $oTpl->setHeader('<link rel="alternate" type="application/rss+xml" title="' . $sPhrase . '" href="' . $sLink . '" />');
                    }
                }
            }

            $aPageLastLogin = ((Phpfox::isAppActive('Core_Pages') && Phpfox::getUserBy('profile_page_id')) ? Phpfox::getService('pages')->getLastLogin() : false);
            $aSubMenus = $oTpl->getMenu();

            if (defined('PHPFOX_IS_PAGES_VIEW') && defined('PHPFOX_PAGES_ITEM_TYPE')) {
                $sSection = PHPFOX_PAGES_ITEM_TYPE;
                $sRewrittenSection = Phpfox::getNonRewritten($sSection);
                $sPageUrl = $oReq->get('req1');
                $sModule = (in_array($sPageUrl, [$sSection, $sRewrittenSection]) ? $oReq->get('req3') : $oReq->get('req2'));
                $aPage = $oTpl->getVar('aPage');
                if (empty($sModule) && !empty($aPage['landing_page'])) {
                    $sModule = $aPage['landing_page'];
                }
                $sModule = Phpfox_Url::instance()->reverseRewrite($sModule);
                $sSubMenuCallback = 'get' . $sSection . 'SubMenu';
                switch ($sSection) {
                    case 'pages':
                        $sSubMenuCallback = 'getPageSubMenu';
                        break;
                    case 'groups':
                        $sSubMenuCallback = 'getGroupSubMenu';
                        break;

                }
                if (Phpfox::isModule($sModule) && Phpfox::hasCallback($sModule, $sSubMenuCallback)) {
                    $aMenu = Phpfox::callback($sModule . '.' . $sSubMenuCallback, $aPage);
                    if (is_array($aMenu)) {
                        foreach ($aMenu as $iKey => $aSubMenu) {
                            $aMenu[$iKey]['module'] = $sModule;
                            if (isset($aSubMenu['phrase'])) {
                                if (Core\Lib::phrase()->isPhrase($sModule . '.' . $aSubMenu['phrase'])) {
                                    $aMenu[$iKey]['var_name'] = $aSubMenu['phrase'];
                                } else {
                                    $aMenu[$iKey]['text'] = $aSubMenu['phrase'];
                                }
                                continue;
                            }
                            switch ($sModule) {
                                case 'event':
                                    $aMenu[$iKey]['var_name'] = 'menu_create_new_' . $sModule;
                                    break;
                                case 'forum':
                                    $aMenu[$iKey]['var_name'] = 'post_a_new_thread';
                                    break;
                                case 'music':
                                    $aMenu[$iKey]['var_name'] = 'menu_upload_a_song';
                                    break;
                                case 'photo':
                                    $aMenu[$iKey]['var_name'] = 'upload_a_new_image';
                                    break;
                                default:
                                    $aMenu[$iKey]['var_name'] = 'menu_add_new_' . $sModule;
                            }
                        }
                    }
                    $aSubMenus = $aMenu;
                }
            }
            $oTpl->assign([
                    'aMainMenus'              => $oTpl->getMenu('main'),
                    'aSubMenus'               => $aSubMenus,
                    'bIsUsersProfilePage'     => defined('PHPFOX_IS_USER_PROFILE'),
                    'sGlobalUserFullName'     => (Phpfox::isUser() ? Phpfox::getUserBy('full_name') : null),
                    'sFullControllerName'     => str_replace(['.', '/'], '_', Phpfox_Module::instance()->getFullControllerName()),
                    'iGlobalProfilePageId'    => Phpfox::getUserBy('profile_page_id'),
                    'aGlobalProfilePageLogin' => $aPageLastLogin,
                ]
            );

            foreach ($oTpl->getMenu('main') as $aMenu) {
                if (isset($aMenu['is_selected']) && $aMenu['is_selected']) {
                    $sMenuSelected .= $aMenu['menu_id'] . ',';
                }
                if (!empty($aMenu['children'])) {
                    foreach ($aMenu['children'] as $aSubMenu) {
                        if (isset($aSubMenu['is_selected']) && $aSubMenu['is_selected']) {
                            $sMenuSelected .= $aSubMenu['menu_id'] . ',';
                        }
                    }
                }
            }

            $oTpl->setEditor();

            if (Phpfox::isModule('notification') && Phpfox::isUser() && Phpfox::getParam('notification.notify_on_new_request')) {
                $oTpl->setHeader('cache', ['update.js' => 'module_notification']);
            }
        }

        if (!PHPFOX_IS_AJAX_PAGE && ($sHeaderFile = $oTpl->getHeaderFile())) {
            (($sPlugin = Phpfox_Plugin::get('run_get_header_file_1')) ? eval($sPlugin) : false);
            require_once($sHeaderFile);
        }

        list($aBreadCrumbs, $aBreadCrumbTitle) = $oTpl->getBreadCrumb();
        $bIsDetailPage = false;
        $fullControllerName = $oModule->getFullControllerName();
        foreach ([
                     '.view',
                     '.detail',
                     '.edit',
                     '.delete',
                     '.add',
                     '.thread',
                     '.create',
                     '.post',
                     '.upload',
                     '.album'
                 ] as $name) {
            if (strpos($fullControllerName, $name)) {
                $bIsDetailPage = true;
            }
        }

        (($sPlugin = \Phpfox_Plugin::get('phpfox_assign_ajax_browsing')) ? eval($sPlugin) : false);

        $oTpl->assign([
            'bIsDetailPage'           => $bIsDetailPage,
            'aErrors'                 => (Phpfox_Error::getDisplay() ? Phpfox_Error::get() : []),
            'sPublicMessage'          => Phpfox::getMessage(),
            'sPublicMessageType'      => Phpfox::getMessageType(),
            'sPublicMessageAutoClose' => Phpfox::getMessageMode(),
            'sLocaleDirection'        => $aLocale['direction'],
            'sLocaleCode'             => $aLocale['language_code'],
            'sLocaleFlagId'           => $aLocale['image'],
            'sLocaleName'             => $aLocale['title'],
            'aBreadCrumbs'            => $aBreadCrumbs,
            'aBreadCrumbTitle'        => $aBreadCrumbTitle,
            'sCopyright'              => '&copy; ' . _p('copyright') . ' ' . Phpfox::getParam('core.site_copyright')
        ]);

        if (self::isAdminPanel()) {
            $sBodyClass = str_replace(['.', '/'], '-', Phpfox_Module::instance()->getFullControllerName());
            if (Phpfox::getParam('core.site_is_offline')) {
                $sBodyClass .= ' is-site-offline';
            }
            if (defined('PHPFOX_TRIAL_MODE')) {
                $sBodyClass .= ' is-site-trial';
            }
            if (Phpfox::getCookie('admincp-toggle-nav-cookie')) {
                $sBodyClass .= ' collapse-nav-active';
            }
            $oTpl->assign('sBodyClass', $sBodyClass);
        }

        Phpfox::clearMessage();

        unset($_SESSION['phpfox']['image']);

        if ($oReq->isPost()) {
            header('X-Is-Posted: true');
            exit;
        }

        if ($oReq->get('is_ajax_get')) {
            header('X-Is-Get: true');
            exit;
        }

        if (defined('PHPFOX_SITE_IS_OFFLINE')) {
            $oTpl->sDisplayLayout = 'blank';
            unset($View);
        }

        if ((!PHPFOX_IS_AJAX_PAGE && $oTpl->sDisplayLayout && !isset($View))
            || (!PHPFOX_IS_AJAX_PAGE && self::isAdminPanel())
        ) {
            $oTpl->getLayout($oTpl->sDisplayLayout);
        }

        if (PHPFOX_IS_AJAX_PAGE) {
            if ($bIsPopup) {
                //hide all blocks
                Phpfox_Module::instance()->resetBlocks();
            }
            header('Content-type: application/json; charset=utf-8');
            if ($View instanceof \Core\View) {
                $content = $View->getContent();
            } else {
                Phpfox_Module::instance()->getControllerTemplate();
                $content = ob_get_contents();
                ob_clean();
            }

            $breadcrumb = $breadcrumb_menu = $search = $menuSub = $h1 = $error = '';
            $blocks = $aCss = $aLoadFiles = $meta = $phrases = [];
            $iNumberRequest = $iNumberNotification = $iNumberMessage = 0;
            $sPageId = Phpfox_Module::instance()->getPageId();
            $title = $actualTitle = $class = $controller_e = '';
            $keepBody = $isSample = false;

            if (!PHPFOX_IS_AJAX) {
                $oTpl->getLayout('breadcrumb');
                $breadcrumb = ob_get_contents();
                ob_clean();

                if (strpos($sPageId, '_admincp_')) {
                    Phpfox::getBlock('admincp.template-breadcrumbmenu');
                    $breadcrumb_menu = ob_get_contents();
                    ob_clean();
                } else {
                    Phpfox::getBlock('core.template-breadcrumbmenu');
                    $breadcrumb_menu = ob_get_contents();
                    ob_clean();
                }

                foreach (range(1, 12) as $location) {
                    if ($location == 3) {
                        echo \Phpfox_Template::instance()->getSubMenu();
                    }
                    $aBlocks = Phpfox_Module::instance()->getModuleBlocks($location);

                    $blocks[$location] = [];

                    foreach ($aBlocks as $aBlock) {
                        $mClass = $aBlock;
                        $aParams = [];
                        if (is_array($aBlock) && isset($aBlock['type_id'])) {
                            if ($aBlock['type_id'] == 0) {
                                $mClass = $aBlock['component'];
                                $aParams = $aBlock['params'];
                            } else if (in_array((int)$aBlock['type_id'], [1, 2])) {
                                $mClass = [$aBlock['component']];
                                $aParams = $aBlock['params'];
                            }
                        }
                        \Phpfox::getBlock($mClass, $aParams);
                        $blocks[$location][] = ob_get_contents();
                        ob_clean();
                    }
                }

                // check and add block 2, 4 to content again.
                if (isset($blocks[2]) && strpos($content, 'data-location="2"') === false) {
                    $content = '<div class="_block location_2" data-location="2">' . implode('',
                            $blocks[2]) . '</div>' . $content;
                }
                if (isset($blocks[4]) && strpos($content, 'data-location="4"') === false) {
                    $content = $content . '<div class="_block location_4" data-location="4">' . implode('',
                            $blocks[4]) . '</div>';
                }

                $oTpl->getLayout('search');
                $search = ob_get_contents();
                ob_clean();

                Phpfox::getBlock('core.template-menusub');
                $menuSub = ob_get_contents();
                ob_clean();

                if (isset($aBreadCrumbTitle[1])) {
                    $h1 .= '<h1><a href="' . $aBreadCrumbTitle[1] . '">' . Phpfox_Parse_Output::instance()->clean($aBreadCrumbTitle[0]) . '</a></h1>';
                }

                $oTpl->getLayout('error');
                $error = ob_get_contents();
                ob_clean();

                $aBundleFiles = Phpfox_Template::instance()->getBundleFiles();
                $calculateLoadFiles = function ($aHeaderFiles) use (&$aLoadFiles, &$aCss) {
                    foreach ($aHeaderFiles as $sHeaderFile) {
                        if (!is_string($sHeaderFile)) {
                            continue;
                        }

                        if (preg_match('/<style(.*)>(.*)<\/style>/i', $sHeaderFile)) {
                            $aCss[] = strip_tags($sHeaderFile);

                            continue;
                        }

                        if (preg_match_all('/href=(["\']?([^"\'>]+)["\']?)/m', $sHeaderFile, $aMatches) > 0) {
                            foreach ($aMatches[1] as $aMatch) {
                                if (strpos($aMatch, '.css') !== false) {
                                    $sHeaderFile = str_replace(['"', "'"], ['', ''], $aMatch);
                                    $sHeaderFile = substr($sHeaderFile, 0, strpos($sHeaderFile, '?'));
                                    $sNew = preg_replace('/\s+/', '', $sHeaderFile);
                                    if (empty($sNew)) {
                                        continue;
                                    }

                                    $aLoadFiles[] = $sHeaderFile;
                                }
                            }
                            continue;
                        }

                        $sHeaderFile = strip_tags($sHeaderFile);

                        $sNew = preg_replace('/\s+/', '', $sHeaderFile);
                        if (empty($sNew)) {
                            continue;
                        }

                        $aLoadFiles[] = $sHeaderFile;
                    }
                };
                $calculateLoadFiles(Phpfox_Template::instance()->getHeader(true));
                $calculateLoadFiles(Phpfox_Template::instance()->getFooter(true));


                $aLoadFiles = array_values(array_diff($aLoadFiles, $aBundleFiles));
                if ($bIsPopup) {
                    $aLoadFiles = array_values(array_filter($aLoadFiles, function ($file) {
                        return strpos($file, '.css') == false;
                    }));
                }

                $title = html_entity_decode($oTpl->instance()->getTitle());
                $actualTitle = $oTpl->instance()->getActualTitle();
                $phrases = Phpfox_Template::instance()->getPhrases();
                $class = Phpfox_Module::instance()->getPageClass();
                $controller_e = (Phpfox::isAdmin() ? Phpfox_Url::instance()->makeUrl('admincp.element.edit',
                    ['controller' => base64_encode(Phpfox_Module::instance()->getFullControllerName())]) : null);
                $meta = Phpfox_Template::instance()->getPageMeta();
                $keepBody = Phpfox_Template::instance()->keepBody();
                $isSample = Phpfox_Template::instance()->bIsSample;

                $iNumberRequest = Phpfox::getService('core.helper')->shortNumberOver100((Phpfox::isUser() && Phpfox::isModule('friend')) ? Phpfox::getService('friend.request')->getUnseenTotal() : -1);
                $iNumberNotification = Phpfox::getService('core.helper')->shortNumberOver100((Phpfox::isUser() && Phpfox::isModule('notification')) ? Phpfox::getService('notification')->getUnseenTotal() : -1);
                $iNumberMessage = Phpfox::getService('core.helper')->shortNumberOver100((Phpfox::isUser() && Phpfox::isAppActive('Core_Messages')) ? Phpfox::getService('mail')->getUnseenTotal() : -1);
            }

            $oAssets = Phpfox::getLib('assets');
            $aLoadFiles = array_values(array_map(function ($file) use ($oAssets) {
                if (preg_match("#\.(css|js)$#", $file)) {
                    return $oAssets->getAssetUrl($file, false);
                } else {
                    return $file;
                }
            }, Phpfox::getLib('template')->verifyBundleFiles($aLoadFiles)));


            $data = json_encode([
                'content'             => $content,
                'title'               => $title,
                'actual_title'        => $actualTitle,
                'phrases'             => $phrases,
                'files'               => $aLoadFiles,
                'css'                 => $aCss,
                'breadcrumb'          => $breadcrumb,
                'breadcrumb_menu'     => $breadcrumb_menu,
                'blocks'              => $blocks,
                'search'              => $search,
                'menuSub'             => $menuSub,
                'id'                  => $sPageId,
                'class'               => $class,
                'h1'                  => $h1,
                'h1_clean'            => strip_tags($h1),
                'error'               => $error,
                'has_left'            => !(empty($blocks['1']) && empty($blocks['9'])),
                'has_right'           => !(empty($blocks['3']) && empty($blocks['10'])),
                'controller_e'        => $controller_e,
                'meta'                => $meta,
                'keep_body'           => $keepBody,
                'selected_menu'       => trim($sMenuSelected, ','),
                'is_sample'           => $isSample,
                'iNumberRequest'      => $iNumberRequest,
                'iNumberNotification' => $iNumberNotification,
                'iNumberMessage'      => $iNumberMessage,
                'profile_user_id'     => (defined('PHPFOX_IS_USER_PROFILE') && defined('PHPFOX_CURRENT_TIMELINE_PROFILE')) ? PHPFOX_CURRENT_TIMELINE_PROFILE : 0
            ]);
            echo $data;
        } else {
            if (isset($View) && $View instanceof Core\View) {
                echo $View->getContent();
            }
        }

        if (PHPFOX_DEBUG && !PHPFOX_IS_AJAX && !PHPFOX_IS_AJAX_PAGE) {
            echo Phpfox_Debug::getDetails();
        }
    }

    /**
     * @param string $sParam
     * @param array  $aParams
     * @param bool   $bNoDebug
     * @param string $sDefault
     * @param string $sLang
     *
     * @return string
     * @deprecated from 4.7.0
     * @see        _p()
     *
     */
    public static function getPhrase($sParam, $aParams = [], $bNoDebug = false, $sDefault = null, $sLang = '')
    {
        return _p($sParam, $aParams, $sLang);
    }

    /**
     * @param string $sParam
     * @param array  $aParams
     * @param string $sLang
     *
     * @return string
     * @deprecated from 4.7.0
     * @see        _p()
     *
     */
    public static function getSoftPhrase($sParam, $aParams = [], $sLang = '')
    {
        return _p($sParam, $aParams, $sLang);
    }

    public static function getLanguageId()
    {
        return Phpfox_Locale::instance()->getLangId();
    }

    /**
     * @param string $sParam
     *
     * @return bool
     * @see Phrase::isPhrase()
     *
     */
    public static function isPhrase($sParam)
    {
        return Core\Lib::phrase()->isPhrase($sParam);
    }

    /**
     * @param string $sParam
     * @param string $sPrefix
     *
     * @return string
     * @see Phpfox_Locale::translate()
     *
     */
    public static function getPhraseT($sParam, $sPrefix)
    {
        return Phpfox_Locale::instance()->translate($sParam, $sPrefix);
    }

    /**
     * Add a public message which can be used later on to display information to a user.
     * Message gets stored in a $_SESSION so the message can be viewed after page reload in case
     * it is used with a HTML form.
     *
     * @param string $sMsg       Message we plan to display to the user
     * @param string $sType      We support 5 types: success, primary, info, warning, danger
     * @param bool   $bAutoClose Auto close after a certain time
     *
     * @see Phpfox_Session::set()
     *
     */
    public static function addMessage($sMsg, $sType = 'success', $bAutoClose = true)
    {
        Phpfox::getLib('session')->set('message', $sMsg);
        Phpfox::getLib('session')->set('message_type', $sType);
        Phpfox::getLib('session')->set('message_auto_close', var_export($bAutoClose, true));
    }

    /**
     * Get the public message we setup earlier
     *
     * @return mixed Return the public message, or return nothing if no public message is set
     * @see Phpfox_Session::get()
     */
    public static function getMessage()
    {
        return Phpfox::getLib('session')->get('message');
    }

    /**
     * Get the public message type we setup earlier
     * @return mixed
     */
    public static function getMessageType()
    {
        return Phpfox::getLib('session')->get('message_type');
    }

    /**
     * Get the public message mode (auto close or not)
     * @return boolean
     */
    public static function getMessageMode()
    {
        return Phpfox::getLib('session')->get('message_auto_close');
    }

    /**
     * Clear the public message we set earlier
     *
     * @see Phpfox_Session::remove()
     */
    public static function clearMessage()
    {
        Phpfox::getLib('session')->remove('message');
        Phpfox::getLib('session')->remove('message_type');
        Phpfox::getLib('session')->remove('message_auto_close');
    }

    /**
     * Set a cookie with PHP setcookie()
     *
     * @param string $sName   The name of the cookie.
     * @param string $sValue  The value of the cookie.
     * @param int    $iExpire The time the cookie expires. This is a Unix timestamp so is in number of seconds since the epoch.
     * @param bool   $bSecure
     * @param bool   $bHttpOnly
     *
     * @see setcookie()
     *
     */
    public static function setCookie($sName, $sValue, $iExpire = 0, $bSecure = false, $bHttpOnly = true)
    {
        $sName = self::$sessionPrefix . $sName;
        if (($iExpire - PHPFOX_TIME) > 0) {
            $iRealExpire = $iExpire;
        } else {
            $iRealExpire = (($iExpire <= 0) ? 0 : (PHPFOX_TIME + (60 * 60 * 24 * $iExpire)));
        }
        if (version_compare(PHP_VERSION, '7.3.0', '<')) {
            setcookie($sName, $sValue, $iRealExpire, Phpfox::getParam('core.cookie_path') . (PHPFOX_IS_HTTPS ? '; SameSite=None; Secure' : ''),
                Phpfox::getParam('core.cookie_domain'), $bSecure, $bHttpOnly);
        } else {
            $aParams = [
                'expires' => $iRealExpire,
                'path' => Phpfox::getParam('core.cookie_path'),
                'domain' => Phpfox::getParam('core.cookie_domain'),
                'secure' => $bSecure,
                'httponly' => $bHttpOnly,
            ];
            if (PHPFOX_IS_HTTPS) {
                $aParams['samesite'] = 'none';
                $aParams['secure'] = true;
            }
            setcookie($sName, $sValue, $aParams);
        }

    }

    /**
     * Gets a cookie set by the method self::setCookie().
     *
     * @param string $sName Name of the cookie.
     *
     * @return string Value of the cookie.
     */
    public static function getCookie($sName)
    {
        $sName = self::$sessionPrefix . $sName;
        return (isset($_COOKIE[$sName]) ? $_COOKIE[$sName] : '');
    }

    public static function removeCookie($sName)
    {
        $sName = self::$sessionPrefix . $sName;
        if (isset($_COOKIE[$sName])) {
            unset($_COOKIE[$sName]);
        }
    }

    /**
     * Start a new log.
     *
     * @param string $sLog Message to the log.
     */
    public static function startLog($sLog = null)
    {
        self::$_aLogs[] = [];

        if ($sLog !== null) {
            self::log($sLog);
        }
    }

    /**
     * Log a message.
     *
     * @param string $sLog Message to the log.
     */
    public static function log($sLog)
    {
        self::$_aLogs[] = $sLog;
    }

    /**
     * End the log and get it.
     *
     * @return array Returns the log.
     */
    public static function endLog()
    {
        return self::$_aLogs;
    }

    /**
     * Permalink for items.
     *
     * @return    string    Returns the full URL of the link.
     */
    public static function permalink($sLink, $iId, $sTitle = null, $bRedirect = false, $sMessage = null, $aExtra = [])
    {
        return Phpfox_Url::instance()->permalink($sLink, $iId, $sTitle, $bRedirect, $sMessage, $aExtra);
    }

    /**
     * Get CDN path
     *
     * @return string Returns CDN full URL
     */
    public static function getCdnPath()
    {
        return 'http://cdn.oncloud.ly/' . self::getVersion() . '/';
    }

    /**
     * Since we allow urls to be rewritten we use this function to get the original value no matter what
     *
     * @param $sSection <string>
     *
     * @return <string>
     */
    public static function getNonRewritten($sSection)
    {
        $aRewrites = Phpfox::getService('core.redirect')->getRewrites();
        foreach ($aRewrites as $aRewrite) {
            if ($aRewrite['url'] == $sSection) {
                return $aRewrite['replacement'];
            }
        }

        return $sSection;
    }

    /**
     * Get base url
     * strip "index.php" from core.path
     *
     * @return string
     */
    public static function getBaseUrl()
    {
        return str_replace('/index.php/', '/', Phpfox::getParam('core.path'));
    }

    /**
     * check that can use ajax to get a link
     *
     * @param string $url
     *
     * @return bool
     */

    public static function canOpenPopup($url = '')
    {
        if (in_array($url, ['login', 'user.register']) && !Phpfox::getParam('core.use_popup_on_signup_login_button')) {
            return false;
        }
        $sUrl = Phpfox_Url::instance()->makeUrl($url);
        $sLinkProtocol = strpos($sUrl, 'https') === false ? 'http' : 'https';
        $sCurrentProtocol = PHPFOX_IS_HTTPS ? 'https' : 'http';

        return ($sLinkProtocol === $sCurrentProtocol);
    }

    /**
     * @param $sImage
     */
    private static function fetchExternalThenExit($sImage)
    {
        // fix issue with facebook image can not get image link contain '&amp;'
        $sImageLink = str_replace('&amp;', '&', base64_decode(str_replace([' '], ['+'], $sImage)));
        if (filter_var($sImageLink, FILTER_VALIDATE_URL) !== false) {
            if (!$content = @file_get_contents($sImageLink)) {
                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, $sImageLink);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
                curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
                curl_setopt($ch, CURLOPT_TIMEOUT, 300);

                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/webp,*/*;q=0.8',
                    'User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36',
                ]);

                $content = curl_exec($ch);

                if ($error = curl_errno($ch)) {
                    exit(curl_error($ch));
                }

                curl_close($ch);
                ob_clean();
            }
            $imageInfo = getimagesize($sImageLink);
            if (!empty($imageInfo['mime'])) {
                header('Content-type: ' . $imageInfo['mime']);
            } else {
                header('Content-type: image/jpg');
            }
            echo $content;
        }

        exit;
    }

    /**
     * @return \Core\Log\Admincp
     */
    public static function getLogManager()
    {
        if (null == self::$_logManager) {
            self::$_logManager = new \Core\Log\Manager();
        }
        return self::$_logManager;
    }


    /**
     * @param string $channel
     *
     * @return \Monolog\Logger
     */
    public static function getLog($channel)
    {
        return self::getLogManager()->get($channel);
    }

    /**
     * Get app id using alias.
     *
     * @param string $sModuleId
     *
     * @return string|null
     */
    public static function getAppId($sModuleId)
    {
        return Phpfox::getLib('database')
            ->select('apps_id')
            ->from(':apps')
            ->where(['apps_alias' => $sModuleId])
            ->execute('getSlaveField');

    }

    /**
     * @return bool
     * @deprecated from 4.0.0 (keep it forever to support 3rd party)
     */
    public static function isMobile($bRedirect = true)
    {
        return false;
    }

    /**
     * Replace some characters into a secure symbol
     * @param $sText
     * @param string $sSymbol
     * @param null $sType
     * @return mixed|string
     */
    public static function secureText($sText, $sType = null, $sSymbol = '*')
    {
        if (is_string($sText)) {
            switch ($sType) {
                case 'email':
                    $aPart = explode('@', $sText);
                    if (count($aPart) < 2) {
                        $sText = self::secureText($sText, null, $sSymbol);
                    } else {
                        $iLength = mb_strlen($aPart[0]);
                        $sAsterisk = str_repeat($sSymbol, round($iLength / 2));
                        $sText = substr($aPart[0], 0, $iLength - mb_strlen($sAsterisk)) . $sAsterisk . '@' . $aPart[1];
                    }
                    break;
                case 'phone':
                    $iLength = mb_strlen($sText);
                    $sAsterisk = str_repeat($sSymbol, round($iLength / 1.5));
                    $sText = $sAsterisk . substr($sText, mb_strlen($sAsterisk));
                    break;
                default:
                    $iLength = mb_strlen($sText);
                    $sAsterisk = str_repeat($sSymbol, round($iLength / 2));
                    $sText = substr($sText, 0, $iLength - mb_strlen($sAsterisk)) . $sAsterisk;
                    break;
            }
        }
        return $sText;
    }
}