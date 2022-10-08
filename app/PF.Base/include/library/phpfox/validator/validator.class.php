<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 * Validatior
 * Used for site wide validation of POST forms. Includes the feature to validate
 * forms using JavaScript and PHP as a failsafe.
 *
 * @copyright        [PHPFOX_COPYRIGHT]
 * @author            phpFox LLC
 * @package        Phpfox
 * @version        $Id: validator.class.php 6599 2013-09-06 08:18:37Z phpFox LLC $
 */
class Phpfox_Validator
{

    /**
     * Name of the current HTML form.
     *
     * @var string
     */
    private $sFormName;

    /**
     * Name of the validation.
     *
     * @var string
     */
    private $_sName;

    /**
     * List of default regex rules.
     *
     * @var array
     */
    private $_aDefaults;

    /**
     * Check to see if we should load a parent check.
     *
     * @var bool
     */
    private $_bParent = false;

    private $_bAllowZero = false;

    private $_aFields;

    /**
     * @var array
     */
    private $_aInvalidate = [];

    /**
     * Default regex rules.
     *
     * @var array
     */
    private $_aRegex = [
        'user_name' => '/^[a-zA-Z0-9_\-]{5,25}$/',
        'full_name' => '/^[^!@#$%^&*(),.?":{}|<>]{1,25}$/',
        'special_characters' => '/[!@#$%^&*(),.?":{}|<>]/',
        'email' => '/^[0-9a-zA-Z]([+\-.\w]*[0-9a-zA-Z]?)*@([0-9a-zA-Z][\-\w]*[0-9a-zA-Z]\.)+[a-zA-Z]{2,}$/',
        'html' => '/<(.*?)>/',
        'url' => '~(?>[a-z+]{2,}://|www\.)(?:[a-z0-9]+(?:\.[a-z0-9]+)?@)?(?:(?:[a-z](?:[a-z0-9]|(?<!-)-)*[a-z0-9])(?:\.[a-z](?:[a-z0-9]|(?<!-)-)*[a-z0-9])+|(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?))(?:/[^\\/:?*"<>|\n]*[a-z0-9])*/?(?:\?[a-z0-9_.%]+(?:=[a-z0-9_.%:/+-]*)?(?:&[a-z0-9_.%]+(?:=[a-z0-9_.%:/+-]*)?)*)?(?:#[a-z0-9_%.]+)?~is',
        'currency_id' => '/^[A-Z]{3,3}$/'
    ];

    /**
     * Class constructor used to load default validator rules.
     * Found in the file: include/setting/validator.sett.php
     *
     */
    public function __construct()
    {
        // Require validation rule set
        require_once(PHPFOX_DIR_SETTING . 'validator.sett.php');
        if(!empty(Phpfox::getParam('core.username_regex_rule'))) {
            $sUsernameRule = str_replace(['min', 'max'],
                [Phpfox::getParam('user.min_length_for_username'), Phpfox::getParam('user.max_length_for_username')],
                Phpfox::getParam('core.username_regex_rule'));
            $this->_aRegex['user_name'] = $sUsernameRule;
        }
        if(!empty(Phpfox::getParam('core.fullname_regex_rule'))) {
            $this->_aRegex['full_name'] = str_replace('max', Phpfox::getParam('user.maximum_length_for_full_name'), Phpfox::getParam('core.fullname_regex_rule'));
        }
        if(!empty(Phpfox::getParam('core.special_characters_regex_rule'))) {
            $this->_aRegex['special_characters'] = Phpfox::getParam('core.special_characters_regex_rule');
        }
        if(!empty(Phpfox::getParam('core.html_regex_rule'))) {
            $this->_aRegex['html'] = Phpfox::getParam('core.html_regex_rule');
        }
        if(!empty(Phpfox::getParam('core.url_regex_rule'))) {
            $this->_aRegex['url'] = Phpfox::getParam('core.url_regex_rule');
        }
        if(!empty(Phpfox::getParam('core.currency_id_regex_rule'))) {
            $this->_aRegex['currency_id'] = Phpfox::getParam('core.currency_id_regex_rule');
        }

        (($sPlugin = Phpfox_Plugin::get('validator_construct')) ? eval($sPlugin) : false);
    }

    /**
     * @return Phpfox_Validator
     */
    public static function instance()
    {
        return Phpfox::getLib('validator');
    }

    public function allowZero()
    {
        $this->_bAllowZero = true;
        return $this;
    }

    /**
     * Verify a regex pattern against a string value.
     *
     * @param string $sPattern Regex pattern to check.
     * @param string $sValue String value.
     * @return bool TRUE if string passed, FALSE if string failed; comes with error message.
     */
    public function verify($sPattern, $sValue)
    {
        if (!isset($this->_aDefaults[$sPattern])) {
            return Phpfox_Error::trigger('Invalid pattern.', E_USER_ERROR);
        }

        if (!preg_match($this->_aDefaults[$sPattern]['pattern'], $sValue)) {
            return (isset($this->_aDefaults[$sPattern]['pattern']) ? Phpfox_Error::set($this->_aDefaults[$sPattern]['title']) : false);
        }

        return true;
    }

    /**
     * Check a string value against multiple regex checks.
     *
     * @param string $sString Value to check.
     * @param array $aParams ARRAY of regex checks to perform.
     * @return bool FALSE if string failed, TRUE if string passed.
     */
    public function check($sString, $aParams = [])
    {
        if (!is_array($aParams)) {
            $aParams = [$aParams];
        }

        $bFailed = 0;
        foreach ($aParams as $sRegex) {
            if (preg_match($this->_aRegex[$sRegex], $sString)) {
                $bFailed++;
            }
        }

        return ($bFailed > 0 ? false : true);
    }

    /**
     * Process a form and perform regex checks on all the values.
     *
     * @param array $aParams ARRAY of regex checks.
     * @param array $aValues ARRAY of form values.
     * @return mixed Returns all the values cleaned if everything is okay, however if something failed a regex check we return FALSE.
     */
    public function process($aParams, $aValues)
    {
        foreach ($aValues as $sValueKey => $sValue) {
            if (!isset($aParams[$sValueKey])) {
                unset($aValues[$sValueKey]);
            }
        }

        $sDebug = '';
        foreach ($aParams as $sKey => $aParam) {
            if (!is_array($aParam)) {
                $aParam = ['type' => $aParam];
            }

            if (!is_array($aParam['type'])) {
                $aParam['type'] = [$aParam['type']];
            }

            $bFailed = false;
            foreach ($aParam['type'] as $sType) {
                switch ($sType) {
                    case 'array:required':
                    case 'array':

                        if ($sType == 'array:required') {
                            if (empty($aValues[$sKey])) {
                                $bFailed = true;
                                $sDebug = 'Value for array is empty.';
                            }
                        }

                        if ($bFailed === false && isset($aValues[$sKey]) && !is_array($aValues[$sKey])) {
                            $bFailed = true;
                            $sDebug = 'Value is not an array.';
                        }

                        break;
                    case 'phrase':
                    case 'phrase:required':

                        if ($sType == 'phrase:required') {
                            if (empty($aValues[$sKey])) {
                                $bFailed = true;
                                $sDebug = 'Value for phrase is empty.';
                            }
                        }

                        if ($bFailed === false && isset($aValues[$sKey]) && !is_array($aValues[$sKey])) {
                            $bFailed = true;
                            $sDebug = 'Value for phrase is not an array.';
                        }

                        if ($bFailed === false) {
                            $iPhraseCount = 0;
                            foreach ($aValues[$sKey] as $sLanguage => $sPhrase) {
                                if (!empty($sPhrase)) {
                                    $iPhraseCount++;
                                }
                            }

                            if ($iPhraseCount === 0) {
                                $bFailed = true;
                                $sDebug = 'Phrase values are all empty.';
                            }
                        }

                        break;
                    case 'php_code':
                    case 'php_code:required':

                        if ($sType == 'php_code:required') {
                            if (empty($aValues[$sKey])) {
                                $bFailed = true;
                                $sDebug = 'Value for PHP code is empty.';
                            }
                        }

                        if ($bFailed === false) {
                            if (!empty($aValues[$sKey])) {
                                $aValues[$sKey] = Phpfox::getLib('parse.format')->phpCode($aValues[$sKey]);
                            }
                        }

                        break;
                    case 'currency':
                    case 'currency:required':
                        if (!isset($aValues[$sKey])) {
                            $bFailed = true;
                            $sDebug = 'Value not found.';
                        } else {
                            if (!is_array($aValues[$sKey])) {
                                $bFailed = true;
                                $sDebug = 'Value is not an array.';
                            }
                        }

                        if ($bFailed === false) {
                            $aValues[$sKey] = (array)$aValues[$sKey];
                            foreach ($aValues[$sKey] as $sCurrency => $mValue) {
                                if (empty($mValue) && strlen($mValue) < 1) {
                                    $bFailed = true;
                                    $sDebug = 'Value of array is empty.';
                                    break;
                                }
                                if (!is_numeric($mValue) && floatval($mValue) < 0) {
                                    $bFailed = true;
                                    break;
                                }
                                if ($sType == 'currency:required' && floatval($mValue) == 0) {
                                    $bFailed = true;
                                    break;
                                }

                                $sPrice = str_replace([' ', ','], '', $mValue);
                                $aParts = explode('.', $sPrice);
                                if (count($aParts) > 2) {
                                    $iCnt = 0;
                                    $sPrice = '';
                                    foreach ($aParts as $sPart) {
                                        $iCnt++;
                                        $sPrice .= (count($aParts) == $iCnt ? '.' : '') . $sPart;
                                    }
                                }

                                if (!is_numeric($sPrice)) {
                                    unset($aValues[$sKey][$sCurrency]);
                                }
                            }

                            if (!count($aValues[$sKey])) {
                                $bFailed = true;
                                $sDebug = 'Value of array is empty.';
                            }
                        }
                        break;
                    case 'price:required':
                        if (empty($aValues[$sKey])) {
                            $bFailed = true;
                            $sDebug = 'Value not found.';
                        } else {
                            $sPrice = str_replace([' ', ','], '', $aValues[$sKey]);
                            $aParts = explode('.', $sPrice);
                            if (count($aParts) > 2) {
                                $iCnt = 0;
                                $sPrice = '';
                                foreach ($aParts as $sPart) {
                                    $iCnt++;
                                    $sPrice .= (count($aParts) == $iCnt ? '.' : '') . $sPart;
                                }
                            }

                            if (is_numeric($sPrice)) {
                                $aValues[$sKey] = $sPrice;
                            } else {
                                $bFailed = true;
                                $sDebug = 'Value is not a numeric value.';
                            }
                        }

                        break;
                    case 'string:required':
                    case 'string':
                        $bCatchZero = true;
                        if ($this->_bAllowZero == true && $aValues[$sKey] == '0') {
                            $bCatchZero = false;
                        }
                        if ($sType == 'string:required' && empty($aValues[$sKey]) && $bCatchZero) {
                            $bFailed = true;
                            $sDebug = 'Value not found.';
                        }

                        if (!empty($aValues[$sKey]) && !is_string($aValues[$sKey]) && $bCatchZero) {
                            $bFailed = true;
                            $sDebug = 'Value is not a string.';
                        }

                        break;
                    case 'int:required':
                        if (!isset($aValues[$sKey])) {
                            $bFailed = true;
                            $sDebug = 'Value not found.';
                        } else {
                            if (filter_var($aValues[$sKey], FILTER_VALIDATE_INT) === false) {
                                $bFailed = true;
                                $sDebug = 'Value is not a numeric value.';
                            }
                        }

                        if ($bFailed === false) {
                            $aValues[$sKey] = (int)$aValues[$sKey];
                        }
                        break;
                    case 'int':

                        if (isset($aValues[$sKey])) {
                            $aValues[$sKey] = ltrim($aValues[$sKey], 0) == '' ? 0 : $aValues[$sKey];
                            if (filter_var($aValues[$sKey], FILTER_VALIDATE_INT) === false) {
                                $bFailed = true;
                                $sDebug = 'Value is not a numeric value.';
                            } else {
                                $aValues[$sKey] = (int)$aValues[$sKey];
                            }
                        }
                        break;
                    case 'boolean':
                        if (isset($aValues[$sKey])) {
                            if (!is_bool($aValues[$sKey])) {
                                $bFailed = true;
                                $sDebug = 'Value is not a boolean value.';
                            }
                        }
                        break;
                    case 'product_id':
                    case 'product_id:required':

                        if ($sType == 'php_code:required') {
                            if (empty($aValues[$sKey])) {
                                $bFailed = true;
                                $sDebug = 'Product ID is missing.';
                            }
                        }

                        break;
                    case 'module_id':
                    case 'module_id:required':

                        if ($sType == 'module_id:required') {
                            if (empty($aValues[$sKey])) {
                                $bFailed = true;
                                $sDebug = 'Module ID is missing.';
                            }
                        }

                        if (($bFailed === false && $sType == 'module_id:required') || ($sType == 'module_id' && !empty($aValues[$sKey]))) {
                            if (!Phpfox::isModule($aValues[$sKey])) {
                                $bFailed = true;
                                $sDebug = 'Not a valid module.';
                            }
                        }

                        break;
                    case 'email':
                        if (isset($aValues[$sKey]) && $aValues[$sKey] != '') {
                            $bFailed = !filter_var($aValues[$sKey], FILTER_VALIDATE_EMAIL);
                            if ($bFailed) {
                                $sDebug = 'Email is invalid';
                            }
                        }
                        break;
                    case 'email:required':
                        $bFailed = !empty($aValues[$sKey]) ? !filter_var($aValues[$sKey], FILTER_VALIDATE_EMAIL) : true;
                        if ($bFailed) {
                            $sDebug = 'Email is invalid';
                        }
                        break;
                    case 'phone':
                        if (isset($aValues[$sKey])) {
                            $isValid = true;
                            if (!empty($aValues[$sKey])) {
                                $oPhone = Phpfox::getLib('phone');
                                if (!$oPhone->setRawPhone($aValues[$sKey]) || !$oPhone->isValidPhone()) {
                                    $isValid = false;
                                } else {
                                    $aValues[$sKey] = $oPhone->getPhoneNational();
                                    if ($sKey == 'phone_number') {
                                        $aValues['full_phone_number'] = $oPhone->getPhoneE164();
                                    }
                                }
                            }
                            if (!$isValid) {
                                $bFailed = true;
                                $sDebug = 'Phone number is invalid';
                            }
                        }
                        break;
                    case 'phone:required':
                        $isValid = true;
                        if (!empty($aValues[$sKey])) {
                            $oPhone = Phpfox::getLib('phone');
                            if (!$oPhone->setRawPhone($aValues[$sKey]) || !$oPhone->isValidPhone()) {
                                $isValid = false;
                            } else {
                                $aValues[$sKey] = $oPhone->getPhoneNational();
                                if ($sKey == 'phone_number') {
                                    $aValues['full_phone_number'] = $oPhone->getPhoneE164();
                                }
                            }
                        } else {
                            $isValid = false;
                        }
                        if (!$isValid) {
                            $bFailed = true;
                            $sDebug = 'Phone number is invalid';
                        }
                        break;
                    case 'reenter':
                        if (empty($sValue) || empty($aFieldValue['compare_with']) || empty($aVal[$aFieldValue['compare_with']])) {
                            $bFailed = true;
                            $sDebug = 'Re-enter field doesn\'t match';
                        } else {
                            $bSkipped = false;
                            if (!empty($aFieldValue['subdef']) && $aFieldValue['subdef'] == 'phone') {
                                $oPhone = Phpfox::getLib('phone');
                                if ($oPhone->setRawPhone($aVal[$aFieldValue['compare_with']]) && $oPhone->isValidPhone()) {
                                    $sPhone = $oPhone->getPhoneE164();
                                }
                                $oPhone->reset();
                                if ($oPhone->setRawPhone($sValue) && $oPhone->isValidPhone()) {
                                    $sConfirmPhone = $oPhone->getPhoneE164();
                                }
                                if (!empty($sPhone) && !empty($sConfirmPhone)) {
                                    if ($sPhone != $sConfirmPhone) {
                                        $bFailed = true;
                                        $sDebug = 'Re-enter field doesn\'t match';
                                    }
                                    $bSkipped = true;
                                }
                            }
                            if (!$bSkipped && $aVal[$aFieldValue['compare_with']] != $sValue) {
                                $bFailed = true;
                                $sDebug = 'Re-enter field doesn\'t match';
                            }
                        }
                        break;
                    case 'gender:required':
                        if (empty($sValue) || ($sValue == 'custom' && empty($aVal['custom_gender']))) {
                            $bFailed = true;
                            $sDebug = 'Gender is invalid';
                        }
                        break;
                    case 'gender':
                        if (!empty($sValue) && $sValue == 'custom' && empty($aVal['custom_gender'])) {
                            $bFailed = true;
                            $sDebug = 'Gender is invalid';
                        }
                        break;
                    default:
                        if (preg_match('/^regex:(.*?)$/', $sType, $aMatches)) {
                            if (isset($this->_aRegex[$aMatches[1]]) && !preg_match($this->_aRegex[$aMatches[1]], $aValues[$sKey])) {
                                $bFailed = true;
                                $sDebug = 'Regex "' . $aMatches[1] . '" failed.';
                            }
                        } else {
                            return Phpfox_Error::trigger('Not a valid validation type: ' . $sType, E_USER_ERROR);
                        }
                        break;
                }

                if ($bFailed) {
                    Phpfox_Error::set((isset($aParam['message']) ? $aParam['message'] : $sDebug . '(' . $sKey . ')') . ((PHPFOX_DEBUG && isset($aParam['message'])) ? ' DEBUG: ' . $sDebug : ''));
                }
            }

            if ($bFailed === false) {
                if (isset($aParam['convert'])) {
                    $aValues[$sKey] = Phpfox::getLib('parse.input')->convert($aValues[$sKey]);
                }
            }
        }

        return $aValues;
    }

    /**
     * Build a form validation check.
     *
     * @param array $aParams ARRAY of settings.
     * @return object Return self.
     */
    public function set($aParams)
    {
        $this->sFormName = 'Validation_' . $aParams['sFormName'];
        $this->_sName = $aParams['sFormName'];
        $this->_aFields = $aParams['aParams'];

        if (isset($aParams['bParent'])) {
            $this->_bParent = true;
        }

        return $this;
    }

    /**
     * Create the JS form onsubmit=""
     *
     * @param bool $bReturn TRUE to include the "return" in the JavaScript code.
     * @return    string    Call to JS function
     */
    public function getJsForm($bReturn = true)
    {
        return ($bReturn ? 'return ' : '') . $this->sFormName . '(this)' . ($bReturn ? ';' : '');
    }

    /**
     * Server Side check
     *
     * @param    array $aVal holds all the post arrays
     * @return    boolean    Check if everything is valid or not
     */
    public function isValid($aVal)
    {
        $this->_aInvalidate = [];
        foreach ($this->_aFields as $sFieldKey => $aFieldValue) {
            $this->_checkRoutine($aFieldValue, $sFieldKey, 'php', (isset($aVal[$sFieldKey]) ? $aVal[$sFieldKey] : null), $aVal);
        }

        return Phpfox_Error::isPassed();
    }

    /**
     * @return array
     */
    public function getInvalidate()
    {
        return $this->_aInvalidate;
    }

    /**
     * @param string $key
     * @param string $title
     */
    public function setInvalidate($key, $title)
    {
        $this->_aInvalidate[$key] = $title;
    }


    /**
     * Client side check
     *
     * @return    string    Javascript data needed for a specific form
     */
    public function createJS()
    {
        $sStr = "\n" . '<div id="' . $this->_sName . '_msg"></div>';
        $sStr .= "\n" . '<script type="text/javascript">' . "\n";
        $sStr .= "function " . $this->sFormName . "(form)\n{ \$Core.onSubmitForm(form); return true; }\n</script>";
        return $sStr;
    }

    /**
     * Method is used to check if POST data fields is valid
     * for either PHP or Javascript.
     *
     * @param    array $aFieldValue is the array of field values
     * @param    string $sFieldKey is the KEY or form ID
     * @param    string $sType is the type of form
     * @param    string $sValue is the value, only for PHP
     * @param   array $aVal
     * @return    mixed    PHP returns bool|JS returns JS layout
     */
    private function _checkRoutine($aFieldValue, $sFieldKey, $sType = 'php', $sValue = '', $aVal = [])
    {
        if (!is_array($aFieldValue)) {
            $aFieldValue = [
                'title' => $aFieldValue,
                'def' => 'required'
            ];
        }
        if(is_string($sValue)) {
            $sValue = trim($sValue);
        }

        $sStr = '';
        if ($sFieldKey == 'email' && function_exists('filter_var') && !empty($sValue) && !filter_var($sValue, FILTER_VALIDATE_EMAIL) && !in_array($aFieldValue['def'], ['phone:required', 'phone'])) {
            if ((!isset($aFieldValue['subdef']) || !in_array($aFieldValue['subdef'], ['phone:required', 'phone']))) {
                $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                Phpfox_Error::set($aFieldValue['title']);
            } else {
                return $this->_checkRoutine(['title' => $aFieldValue['title'], 'def' => $aFieldValue['subdef']], $sFieldKey, $sType, $sValue, $aVal);
            }
        } elseif (isset($aFieldValue['pattern'])) {
            if ($sType == 'php' && !preg_match($aFieldValue['pattern'], $sValue)) {
                $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                Phpfox_Error::set($aFieldValue['title']);
            } else {
                $sStr .= $this->_createIfJS('oJs.value(\'' . $sFieldKey . '\').search(' . $aFieldValue['pattern'] . ') == -1',
                    $aFieldValue['title'], $sFieldKey);
            }
        } elseif (isset($aFieldValue['maxlen'])) {
            if ($sType == 'php' && strlen($sValue) > $aFieldValue['maxlen']) {
                $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                Phpfox_Error::set($aFieldValue['title']);
            } else {
                $sStr .= $this->_createIfJS('$(\'#' . $sFieldKey . '\').val().length > ' . $aFieldValue['maxlen'] . '',
                    $aFieldValue['title'], $sFieldKey);
            }
        } elseif (isset($aFieldValue['def'])) {
            $def = $aFieldValue['def'];

            if (isset($aFieldValue['requirements']) && is_array($aFieldValue['requirements'])) {
                if (!$this->_checkRequirements($aFieldValue, $sValue, $aVal)) {
                    $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                    Phpfox_Error::set($aFieldValue['title']);

                    return false;
                }
            }

            switch ($def) {
                case 'array:required':
                case 'array':
                    if ($def == 'array:required' && empty($sValue)) {
                        $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                        Phpfox_Error::set($aFieldValue['title']);
                    }

                    if (!is_array($sValue)) {
                        $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                        Phpfox_Error::set($aFieldValue['title']);
                    }

                    if (isset($aFieldValue['subdef'])) {
                        foreach ($sValue as $subValue) {
                            //Begin
                            switch ($aFieldValue['subdef']) {
                                case 'int':
                                    if (isset($subValue)) {
                                        $isValid = true;
                                        if (!empty($subValue)) {
                                            if (filter_var($subValue, FILTER_VALIDATE_INT) === false) {
                                                $isValid = false;
                                            }

                                            if (isset($aFieldValue['min']) && $subValue < $aFieldValue['min']) {
                                                $isValid = false;
                                            }

                                            if (isset($aFieldValue['max']) && $subValue > $aFieldValue['max']) {
                                                $isValid = false;
                                            }

                                            if ($isValid && isset($aFieldValue['requirements']) && is_array($aFieldValue['requirements'])) {
                                                $isValid = $this->_checkRequirements($aFieldValue, $subValue, $aVal);
                                            }

                                            if (!$isValid) {
                                                $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                                                Phpfox_Error::set($aFieldValue['title']);
                                            }
                                        }
                                    }
                                    break;
                                case 'int:required':
                                    $isValid = true;
                                    if (!isset($subValue) || filter_var(ltrim($subValue, 0) == '' ? 0 : ltrim($subValue, 0), FILTER_VALIDATE_INT) === false) {
                                        $isValid = false;
                                    }

                                    if ($isValid && isset($aFieldValue['min']) && $subValue < $aFieldValue['min']) {
                                        $isValid = false;
                                    }

                                    if ($isValid && isset($aFieldValue['max']) && $aFieldValue['max'] && $subValue > $aFieldValue['max']) {
                                        $isValid = false;
                                    }

                                    if ($isValid && isset($aFieldValue['requirements']) && is_array($aFieldValue['requirements'])) {
                                        $isValid = $this->_checkRequirements($aFieldValue, $subValue, $aVal);
                                    }

                                    if (!$isValid) {
                                        $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                                        Phpfox_Error::set($aFieldValue['title']);
                                    }

                                    break;
                                case 'string':
                                    if (!empty($subValue) && !is_string($subValue)) {
                                        $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                                        Phpfox_Error::set($aFieldValue['title']);
                                    }
                                    break;
                                case 'string:required':
                                    if (empty($subValue) || !is_string($subValue)) {
                                        $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                                        Phpfox_Error::set($aFieldValue['title']);
                                    }
                                    break;
                            }
                        }
                    }

                    break;
                case 'currency':
                case 'currency:required':
                    $bFailed = false;
                    if (!isset($sValue) && $aFieldValue['def']) {
                        $bFailed = true;
                        $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                        Phpfox_Error::set($aFieldValue['title']);
                    } else {
                        if (!is_array($sValue)) {
                            $bFailed = true;
                            $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                            Phpfox_Error::set($aFieldValue['title']);
                        }
                    }

                    if ($bFailed === false) {
                        $sValue = (array)$sValue;
                        foreach ($sValue as $sCurrency => $mValue) {
                            if (empty($mValue) && strlen($mValue) < 1) {
                                $bFailed = true;
                                break;
                            }
                            if (!is_numeric($mValue) || floatval($mValue) < 0) {
                                $bFailed = true;
                                break;
                            }
                            if ($def == 'currency:required' && floatval($mValue) == 0) {
                                $bFailed = true;
                                break;
                            }
                            $sPrice = str_replace([' ', ','], '', $mValue);
                            $aParts = explode('.', $sPrice);
                            if (count($aParts) > 2) {
                                $iCnt = 0;
                                $sPrice = '';
                                foreach ($aParts as $sPart) {
                                    $iCnt++;
                                    $sPrice .= (count($aParts) == $iCnt ? '.' : '') . $sPart;
                                }
                            }

                        }
                    }
                    if ($bFailed) {
                        $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                        Phpfox_Error::set($aFieldValue['title']);
                    }
                    break;
                case 'price:required':
                    if ($sValue == '' || !is_numeric($sValue) || $sValue < 0) {
                        $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                        Phpfox_Error::set($aFieldValue['title']);
                    }
                    break;
                case 'money':
                case 'price':
                    if (!is_numeric($sValue) || $sValue < 0) {
                        $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                        Phpfox_Error::set($aFieldValue['title']);
                    }
                    break;
                case 'required':
                    if ($sType == 'php' && in_array($sFieldKey, ['image_verification', 'package_id'])) {
                        switch ($sFieldKey) {
                            case 'image_verification':
                                $bFailed = false;
                                if (Phpfox::isAppActive('Core_Captcha')) {
                                    if (!empty($aFieldValue['subdef']) && $aFieldValue['subdef'] == 'no_submit'
                                        && Phpfox::getParam('captcha.captcha_type') == 'recaptcha') {
                                        if (empty($_REQUEST['g-recaptcha-response'])) {
                                            $bFailed = true;
                                        }
                                    } elseif (!Phpfox::getService('captcha')->checkHash($sValue)) {
                                        $bFailed = true;
                                    }
                                    if ($bFailed) {
                                        $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                                        Phpfox_Error::set(_p('captcha_failed_please_try_again'));
                                    }
                                }
                                break;
                            case 'package_id':
                                if (Phpfox::isAppActive('Core_Subscriptions')
                                    && Phpfox::getParam('subscribe.enable_subscription_packages')
                                    && Phpfox::getParam('subscribe.subscribe_is_required_on_sign_up') && empty($sValue)) {
                                    $packages = Phpfox::getService('subscribe')->getPackages(true);
                                    if (count($packages)) {
                                        $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                                        Phpfox_Error::set(_p('select_a_membership_package'));
                                    }
                                }
                                break;
                        }
                    } else {
                        if ($sType == 'php') {
                            if (isset($aFieldValue['php_id'])) {
                                if (strpos($aFieldValue['php_id'], ']')) {
                                    $aParts = explode('[', $aFieldValue['php_id']);
                                    $aPostArray = Phpfox_Request::instance()->getArray($aParts[0]);
                                    $aKeyParts = explode('_', $sFieldKey);
                                    if (isset($aPostArray[$aKeyParts[(count($aKeyParts) - 1)]])) {
                                        $sValue = $aPostArray[$aKeyParts[(count($aKeyParts) - 1)]];
                                    }
                                }
                            }
                            if (empty($sValue) && $sValue != '0') {
                                $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                                Phpfox_Error::set($aFieldValue['title']);
                            } elseif (is_string($sValue) && Phpfox::getLib('parse.format')->isEmpty($sValue)) {
                                $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                                Phpfox_Error::set($aFieldValue['title']);
                            } else if (is_array($sValue) && empty($sValue)) {
                                $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                                Phpfox_Error::set($aFieldValue['title']);
                            }
                        } else {
                            (($sPlugin = Phpfox_Plugin::get('validator_check_routine_default')) ? eval($sPlugin) : false);

                            if (!isset($bSkipDefaultCheck)) {
                                if ($this->_bParent === true) {
                                    $sStr .= $this->_createIfJS('$(\'#' . $this->_sName . '\').find(\'.' . $sFieldKey . '\').val() == \'\'', $aFieldValue['title'], $sFieldKey);
                                } else {
                                    $sStr .= $this->_createIfJS('' . (($sFieldKey == 'text') ? '(Editor.sEditor == \'tinymce\' && typeof(tinyMCE) == \'object\' && tinyMCE.activeEditor.getContent().replace(/<\/?[^>]+>/gi, \'\').length == 0) || (typeof(tinyMCE) != \'object\' && $(\'#' . $sFieldKey . '\').val() == \'\') || (Editor.sEditor != \'tinymce\' && typeof(tinyMCE) == \'object\' && $(\'#' . $sFieldKey . '\').val() == \'\')' : '$(\'#' . $sFieldKey . '\').val() == \'\'') . '', $aFieldValue['title'], $sFieldKey);
                                }
                            }
                        }
                    }
                    break;
                case 'checkbox':
                    if ($sType == 'php' && empty($sValue)) {
                        $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                        Phpfox_Error::set($aFieldValue['title']);
                    } else {
                        $sStr .= $this->_createIfJS('!$(\'#' . $sFieldKey . '\').get(0).checked', $aFieldValue['title'], $sFieldKey);
                    }
                    break;
                case 'int':
                    if (isset($sValue)) {
                        $isValid = true;
                        if (!empty($sValue)) {
                            $sValue = ltrim($sValue, 0) == '' ? 0 : ltrim($sValue, 0);
                            if (filter_var($sValue, FILTER_VALIDATE_INT) === false) {
                                $isValid = false;
                            }

                            if ($isValid && isset($aFieldValue['min']) && $sValue < $aFieldValue['min']) {
                                $isValid = false;
                            }

                            if ($isValid && isset($aFieldValue['max']) && $sValue > $aFieldValue['max']) {
                                $isValid = false;
                            }

                            if (!$isValid) {
                                $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                                Phpfox_Error::set($aFieldValue['title']);
                            }
                        }
                    }
                    break;
                case 'string:required':
                    $sValue = trim(trim(strip_tags($sValue)), '&nbsp;');
                    if (empty($sValue) || !is_string($sValue)) {
                        $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                        Phpfox_Error::set($aFieldValue['title']);
                    }
                    break;
                case 'string':
                    if (!empty($sValue) && !is_string($sValue)) {
                        $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                        Phpfox_Error::set($aFieldValue['title']);
                    }
                    break;
                case 'ip:required':
                    $sValue = str_replace('*', '0', $sValue); // support use asterisk (*)
                    if (empty($sValue)) {
                        $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                        Phpfox_Error::set($aFieldValue['title']);
                    } elseif (!filter_var($sValue, FILTER_VALIDATE_IP)) {
                        $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                        Phpfox_Error::set($aFieldValue['title']);
                    }
                    break;
                case 'int:required':
                    $isValid = true;
                    if ($sValue !== null) {
                        $sValue = ltrim($sValue, 0) == '' ? 0 : ltrim($sValue, 0);
                    }
                    if (!isset($sValue) || filter_var($sValue, FILTER_VALIDATE_INT) === false) {
                        $isValid = false;
                    }

                    if ($isValid && isset($aFieldValue['min']) && $sValue < $aFieldValue['min']) {
                        $isValid = false;
                    }

                    if ($isValid && isset($aFieldValue['max']) && $aFieldValue['max'] && $sValue > $aFieldValue['max']) {
                        $isValid = false;
                    }

                    if (!$isValid) {
                        $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                        Phpfox_Error::set($aFieldValue['title']);
                    }

                    break;
                case 'boolean':
                    if (isset($sValue)) {
                        if (!is_bool($sValue)) {
                            $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                            Phpfox_Error::set($aFieldValue['title']);
                        }
                    }
                    break;
                case 'product_id':
                case 'product_id:required':

                    if ($sType == 'php_code:required') {
                        if (empty($sValue)) {
                            $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                            Phpfox_Error::set($aFieldValue['title']);
                        }
                    }

                    break;
                case 'module_id':
                    if ($sValue && !Phpfox::isModule($sValue)) {
                        $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                        Phpfox_Error::set($aFieldValue['title']);
                    }
                    break;
                case 'module_id:required':
                    if (empty($sValue) || !Phpfox::isModule($sValue)) {
                        $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                        Phpfox_Error::set($aFieldValue['title']);
                    }

                    break;
                case 'phone':
                    if (isset($sValue)) {
                        $isValid = true;
                        if (!empty($sValue)) {
                            $oPhone = Phpfox::getLib('phone');
                            if (!$oPhone->setRawPhone($sValue) || !$oPhone->isValidPhone()) {
                                $isValid = false;
                            }
                        }
                        if (!$isValid) {
                            $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                            Phpfox_Error::set($aFieldValue['title']);
                        }
                    }
                    break;
                case 'phone:required':
                    $isValid = true;
                    if (empty($sValue)) {
                        $isValid = false;
                    }
                    if ($isValid) {
                        $oPhone = Phpfox::getLib('phone');
                        if (!$oPhone->setRawPhone($sValue) || !$oPhone->isValidPhone()) {
                            $isValid = false;
                        }
                    }
                    if (!$isValid) {
                        $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                        Phpfox_Error::set($aFieldValue['title']);
                    }
                    break;
                case 'reenter':
                    if (empty($sValue)) {
                        $sMessage = isset($aFieldValue['subtitle']) ? $aFieldValue['subtitle'] : $aFieldValue['title'];
                        $this->setInvalidate($sFieldKey, $sMessage);
                        Phpfox_Error::set($sMessage);
                    } elseif (empty($aFieldValue['compare_with']) || empty($aVal[$aFieldValue['compare_with']])) {
                        $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                        Phpfox_Error::set($aFieldValue['title']);
                    } else {
                        $bSkipped = false;
                        if (!empty($aFieldValue['subdef']) && $aFieldValue['subdef'] == 'phone') {
                            $oPhone = Phpfox::getLib('phone');
                            if ($oPhone->setRawPhone($aVal[$aFieldValue['compare_with']]) && $oPhone->isValidPhone()) {
                                $sPhone = $oPhone->getPhoneE164();
                            }
                            $oPhone->reset();
                            if ($oPhone->setRawPhone($sValue) && $oPhone->isValidPhone()) {
                                $sConfirmPhone = $oPhone->getPhoneE164();
                            }
                            if (!empty($sPhone) && !empty($sConfirmPhone)) {
                                if ($sPhone != $sConfirmPhone) {
                                    $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                                    Phpfox_Error::set($aFieldValue['title']);
                                }
                                $bSkipped = true;
                            }
                        }
                        if (!$bSkipped && $aVal[$aFieldValue['compare_with']] != $sValue) {
                            $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                            Phpfox_Error::set($aFieldValue['title']);
                        }
                    }
                    break;
                case 'gender:required':
                    if (empty($sValue)) {
                        $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                        Phpfox_Error::set($aFieldValue['title']);
                    } elseif ($sValue == 'custom' && empty($aVal['custom_gender'])) {
                        $sMessage = isset($aFieldValue['subtitle']) ? $aFieldValue['subtitle'] : $aFieldValue['title'];
                        $this->setInvalidate($sFieldKey, $sMessage);
                        Phpfox_Error::set($sMessage);
                    }
                    break;
                case 'gender':
                    if (!empty($sValue) && $sValue == 'custom' && empty($aVal['custom_gender'])) {
                        $sMessage = isset($aFieldValue['subtitle']) ? $aFieldValue['subtitle'] : $aFieldValue['title'];
                        $this->setInvalidate($sFieldKey, $sMessage);
                        Phpfox_Error::set($sMessage);
                    }
                    break;
                case 'password':
                    $sDef = $aFieldValue['def'];
                    $bAlreadyError = false;
                    $sFieldTitle = (isset($aFieldValue['title']) ? $aFieldValue['title'] : '');
                    $sExtraPrefixTitle = empty($aFieldValue['subdef']) || $aFieldValue['subdef'] != 'no_newline' ? '<br/>' : ' ';
                    $sExtraFieldTitle = '';
                    if (isset($this->_aDefaults[$sDef])) {
                        $aDefault = $this->_aDefaults[$sDef];
                        $sFieldTitle = empty($sFieldTitle) ? $aDefault['title'] : $sFieldTitle;
                        if (isset($aDefault['minlen'])) {
                            if ($sType == 'php' && strlen($sValue) < $aDefault['minlen']) {
                                $bAlreadyError = true;
                                $sExtraFieldTitle .= $sExtraPrefixTitle . _p('minimum_length_for_password_is_number', ['number' => $aDefault['minlen']]);
                            } else {
                                $sStr .= $this->_createIfJS('$(\'#' . $sFieldKey . '\').val().length < ' . $aDefault['minlen'] . '', $aDefault['title'], $sFieldKey);
                            }
                        }
                        if (isset($aDefault['maxlen'])) {
                            if ($sType == 'php' && strlen($sValue) > $aDefault['maxlen']) {
                                $bAlreadyError = true;
                                $sExtraFieldTitle .= $sExtraPrefixTitle . _p('maximum_length_for_password_is_number', ['number' => $aDefault['maxlen']]);
                            } else {
                                $sStr .= $this->_createIfJS('$(\'#' . $sFieldKey . '\').val().length > ' . $aDefault['maxlen'] . '', $aDefault['title'], $sFieldKey);
                            }
                        }
                    }
                    if (Phpfox::getParam('user.required_strong_password')) {
                        if (!preg_match('/[A-Z]/', $sValue)) {
                            $bAlreadyError = true;
                            $sExtraFieldTitle .= $sExtraPrefixTitle . _p('must_contain_at_least_one_uppercase_character');
                        }
                        if (!preg_match('/[a-z]/', $sValue)) {
                            $bAlreadyError = true;
                            $sExtraFieldTitle .= $sExtraPrefixTitle . _p('must_contain_at_least_one_lowercase_character');
                        }
                        if (!preg_match('/[0-9]/', $sValue)) {
                            $bAlreadyError = true;
                            $sExtraFieldTitle .= $sExtraPrefixTitle . _p('must_contain_at_least_one_number');
                        }
                        if (!preg_match('/[!"#$%&\'()*+,\-.\/:;<=>?@[\]^_`{|}~]/', $sValue)) {
                            $bAlreadyError = true;
                            $sExtraFieldTitle .= $sExtraPrefixTitle . _p('must_contain_at_least_one_special_character');
                        }
                    }
                    if ($bAlreadyError) {
                        $this->setInvalidate($sFieldKey, $sFieldTitle . $sExtraFieldTitle);
                        Phpfox_Error::set($sFieldTitle . $sExtraFieldTitle);
                    }
                    break;
                default:
                    $sDef = $aFieldValue['def'];
                    $bSkipped = false;
                    if ((!isset($sValue) || $sValue == '') && isset($aFieldValue['subdef']) && $aFieldValue['subdef'] == 'no_required') {
                        //Not required
                        break;
                    }
                    if ($sDef == 'username' && (isset($aFieldValue['subdef']) && $aFieldValue['subdef'] == 'no_duplicate')) {
                        $bSkipped = !Phpfox::getService('user.validate')->user($sValue, true);
                    }
                    if (!$bSkipped && isset($this->_aDefaults[$sDef])) {
                        $bAlreadyError = false;
                        $aDefault = $this->_aDefaults[$sDef];
                        $sFieldTitle = (isset($aFieldValue['title']) ? $aFieldValue['title'] : $aDefault['title']);
                        $sFieldGuide = (isset($aDefault['guide']) ? $aDefault['guide'] : '');
                        if (isset($aDefault['pattern'])) {
                            if ($sType == 'php' && !preg_match($aDefault['pattern'], $sValue)) {
                                $bAlreadyError = true;
                                $this->setInvalidate($sFieldKey, $sFieldTitle);
                                Phpfox_Error::set($sFieldTitle);
                            } else {
                                $sStr .= $this->_createIfJS('$(\'#' . $sFieldKey . '\').val().search(' . $aDefault['pattern'] . ') == -1', $sFieldTitle, $sFieldKey, $sFieldGuide);
                            }
                        }

                        if (!$bAlreadyError && isset($aDefault['minlen'])) {
                            if ($sType == 'php' && strlen($sValue) < $aDefault['minlen']) {
                                $bAlreadyError = true;
                                $this->setInvalidate($sFieldKey, $sFieldTitle);
                                Phpfox_Error::set($sFieldTitle);
                            } else {
                                $sStr .= $this->_createIfJS('$(\'#' . $sFieldKey . '\').val().length < ' . $aDefault['minlen'] . '', $aDefault['title'], $sFieldKey);
                            }
                        }

                        if (!$bAlreadyError && isset($aDefault['maxlen'])) {
                            if ($sType == 'php' && strlen($sValue) > $aDefault['maxlen']) {
                                Phpfox_Error::set($sFieldTitle);
                            } else {
                                $sStr .= $this->_createIfJS('$(\'#' . $sFieldKey . '\').val().length > ' . $aDefault['maxlen'] . '', $aDefault['title'], $sFieldKey);
                            }
                        }
                    }
            }
        } else {

            if ($sType == 'php') {
                $this->setInvalidate($sFieldKey, $aFieldValue['title']);
                Phpfox_Error::set($aFieldValue['title']);
            } else {
                $sStr .= $this->_createIfJS('oJs.value(\'' . $sFieldKey . '\') == \'\'', $aFieldValue['title']);
            }
        }
        $sStr .= "\n";

        return ($sType != 'php' ? $sStr : '');
    }

    /**
     * Creates a JS IF statment
     *
     * @param    string $sIfStatment is the JS IF statment will plan to execute
     * @param    string $sTitle is the error message if the check has failed
     * @param string $sFieldKey Field ID/Class if using a parent check.
     * @param string $sInfoGuide Info guide to explain the error in more detail.
     * @return    string    JS Content returned
     */
    private function _createIfJS($sIfStatment, $sTitle, $sFieldKey = '', $sInfoGuide = '')
    {
        $sStr = "\t";
        $sStr .= "if (" . $sIfStatment . ")\n\t{\n";
        $sStr .= "\t\t" . "bIsValid = false; \n";
        $sStr .= "\t\t" . '$(\'#' . $this->_sName . '_msg\').message(\'' . str_replace("'", "\'", $sTitle) . '\', \'error\');' . "\n";
        if ($sFieldKey) {
            if ($this->_bParent === true) {
                $sStr .= "\t\t" . '$(\'#' . $this->_sName . '\').find(\'.' . $sFieldKey . '\').addClass(\'alert_input\');' . "\n";
            } else {
                $sStr .= "\t\t" . '$(\'#' . $sFieldKey . '\').addClass(\'alert_input\');' . "\n";
            }
            if (!empty($sInfoGuide)) {
                $sStr .= "\t\t" . 'oJs.className(\'FormInfo_' . $sFieldKey . '\', \'FormInfo\');' . "\n\t\t" . 'oJs.id(\'FormInfo_' . $sFieldKey . '\').innerHTML=\'' . $sInfoGuide . '\';' . "\n";
            }
        }
        $sStr .= "\t" . '}' . "\n";
        return $sStr;
    }

    /**
     * @param $aFieldValue
     * @param $sValue
     * @param $aVal
     *
     * @return bool
     */
    private function _checkRequirements($aFieldValue, $sValue, $aVal)
    {
        $isValid = true;

        if (is_array($aFieldValue['requirements'])) {
            foreach ($aFieldValue['requirements'] as $sSubKey => $sSubDef) {
                if (substr($sSubDef, 0, 1) == '$') {
                    $sSubValue = $aVal[substr($sSubDef, 1)];
                } else {
                    $sSubValue = isset($aVal[$sSubDef]) ? $aVal[$sSubDef] : '';
                }
                switch ($sSubKey) {
                    case 'callback':
                        if (is_callable($sSubDef)) {
                            return $sSubDef($sValue);
                        } elseif (strpos($sSubDef, ':' > 0)) {
                            list($serviceName, $methodName) = explode(':', $sSubDef, 2);
                            try {
                                $service = Phpfox::getService($serviceName);
                                if (is_object($service) and method_exists($service, $methodName)) {
                                    return call_user_func([$service, $methodName], $sValue);
                                }
                            } catch (\Exception $exception) {

                            }
                            return true;
                        }
                        break;
                    case 'min':
                        if ($sValue < $sSubValue) {
                            $isValid = false;
                            break;
                        }
                        break;
                    case 'max':
                        if ($sValue > $sSubValue) {
                            $isValid = false;
                        }
                        break;
                }
            }
        }

        return $isValid;
    }

    public function parseIntValues($aParams, &$aValues)
    {
        if (Phpfox_Error::isPassed() && !empty($aParams)) {
            $aIntValidate = array_filter($aParams, function ($aValidate) {
                return isset($aValidate['def']) && in_array($aValidate['def'], ['int', 'int:required']);
            });
            if (count($aIntValidate)) {
                foreach ($aIntValidate as $sKey => $aValidate) {
                    if (isset($aValues[$sKey]) && trim($aValues[$sKey]) != '') {
                        $aValues[$sKey] = (int)$aValues[$sKey];
                    }
                }
            }
        }
    }
}