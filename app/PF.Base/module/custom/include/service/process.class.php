<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Custom_Service_Process
 */
class Custom_Service_Process extends Phpfox_Service
{
    /**
     * @var string
     */
    protected $_sTable = '';

    /**
     * @var array
     */
    private $_aOptions = array();

    /**
     * Used to control when to add another feed
     * @var array of boolean
     */
    private $_aFeedsAdded = array();

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('custom_field');
    }

    /**
     * Adds a new custom field, the options must come in this structure
     *  array(
     *    option = array(
     *        # => array(
     *        <language_id> => array(
     *            text => option text
     *            feed =>
     *
     * @param $aVals
     *
     * @return array|bool
     * @throws Exception
     */
    public function add($aVals)
    {
        $this->_aOptions = array();
        Phpfox::getUserParam('custom.can_add_custom_fields', true);
        $aVals['module_id'] = 'custom';
        // Prepare the name of the custom field
        $sVarName = '';
        $bMissingName = false;
        foreach ($aVals['name'] as $aText) {
            if (empty($aText['text'])) {
                $bMissingName = true;
                continue;
            }
            if (empty($sVarName)) {
                $sVarName = Phpfox::getService('language.phrase.process')->prepare($aText['text']);
            }
        }
        if ($bMissingName) {
            return Phpfox_Error::set(_p('provide_a_name_for_the_custom_field'));
        }

        $sVarName = $sFieldName = substr($sVarName, 0, 20);
        $sVarName = trim($sVarName, '_');
        $sFieldName = trim($sFieldName, '_');

        $sCustomTable = Phpfox::getT('user_custom');
        $sCustomValueTable = Phpfox::getT('user_custom_value');

        if (!empty($aVals['user_group_id'])) {
            $sCustomTable = Phpfox::getUserGroupParam($aVals['user_group_id'], 'custom.custom_table_name');
            $sCustomValueTable = $sCustomTable . '_value';
        }

        // check if table exists
        if (!$this->database()->tableExists($sCustomTable)) {
            return Phpfox_Error::set(_p('table_does_not_exist', ['sTableName' => $sCustomTable]));
        }

        if ($this->database()->isField(($sCustomTable), Phpfox::getService('custom')->getAlias() . $sFieldName)) {
            if (!defined('PHPFOX_INSTALLER')) {
                return Phpfox_Error::set(_p('name_of_this_custom_field_is_already_in_use'));
            }
            $sFieldName = $sFieldName . rand(1, 20);
        }

        $sVarName = 'custom_' . $sVarName;

        $bAddToOptions = false;
        switch ($aVals['var_type']) {
            case 'select':
            case 'radio':
                $sTypeName = 'VARCHAR(150)';
                $sValueTypeName = 'SMALLINT(5)';
                $bAddToOptions = true;
                break;
            case 'multiselect':
            case 'checkbox':
                $sTypeName = 'MEDIUMTEXT';
                $sValueTypeName = 'MEDIUMTEXT';
                $bAddToOptions = true;
                break;
            case 'text':
                $sTypeName = 'VARCHAR(255)';
                $sValueTypeName = 'VARCHAR(255)';
                break;
            case 'date':
                $sTypeName = 'INT(10)';
                $sValueTypeName = 'INT(10)';
                break;
            case 'textarea':
                $sTypeName = 'MEDIUMTEXT';
                $sValueTypeName = 'MEDIUMTEXT';
                break;
            default:
                return Phpfox_Error::set(_p('not_a_valid_type_of_custom_field'));
        }

        if ($bAddToOptions && !empty($aVals['option']) && is_array($aVals['option'])) {
            $iTotalOptions = 0;
            foreach ($aVals['option'] as $aOption) {
                foreach ($aOption as $aLanguage) {
                    if (isset($aLanguage['text']) && !empty($aLanguage['text'])) {
                        $iTotalOptions++;
                        // there may be more languages, counting them would give an incorrect number of options
                        break;
                    }
                }
            }

            if (!$iTotalOptions) {
                return Phpfox_Error::set(_p('you_have_selected_that_this_field_is_a_select_custom_field_which_requires_at_least_one_option'));
            }
        }

        $iCustomFieldCount = $this->database()->select('COUNT(*)')
            ->from(Phpfox::getT('custom_field'))
            ->where('phrase_var_name = \'' . $this->database()->escape($aVals['module_id'] . '.' . $sVarName) . '\'')
            ->execute('getSlaveField');

        if ($iCustomFieldCount > 0) {
            $sVarName = $sVarName . '_' . ($iCustomFieldCount + 1);
            $sFieldName = $sFieldName . '_' . ($iCustomFieldCount + 1);
        }

        $aSql = [
            'field_name' => $sFieldName,
            'module_id' => $aVals['module_id'],
            'product_id' => $aVals['product_id'],
            'user_group_id' => (isset($aVals['user_group_id']) ? (int)$aVals['user_group_id'] : 0),
            'type_id' => $aVals['type_id'],
            'group_id' => (isset($aVals['group_id']) ? (int)$aVals['group_id'] : 0),
            'phrase_var_name' => $aVals['module_id'] . '.' . $sVarName,
            'type_name' => $sTypeName,
            'var_type' => $aVals['var_type'],
            'is_required' => (isset($aVals['is_required']) ? (int)$aVals['is_required'] : 0),
            'on_signup' => (isset($aVals['on_signup']) ? (int)$aVals['on_signup'] : 0),
            'has_feed' => (isset($aVals['add_feed']) && !empty($aVals['add_feed'])) ? $aVals['add_feed'] : '0',
            'is_search' => (isset($aVals['is_search']) && !empty($aVals['is_search'])) ? $aVals['is_search'] : '0'
        ];
        switch ($aVals['var_type']) {
            case 'select':
            case 'multiselect':
            case 'checkbox':
            case 'radio':
                $bAddField = false;
                break;
            default:
                $bAddField = true;
        }

        if ($bAddField &&
            !$this->database()->isField(($sCustomValueTable), Phpfox::getService('custom')->getAlias() . $sFieldName)) {
            $this->database()->addField(array(
                    'table' => ($sCustomValueTable),
                    'field' => Phpfox::getService('custom')->getAlias() . $sFieldName,
                    'type' => $sValueTypeName
                )
            );

            if ($sCustomValueTable != Phpfox::getT('user_custom_value')) {
                $this->database()->addField(array(
                        'table' => (Phpfox::getT('user_custom_value')),
                        'field' => Phpfox::getService('custom')->getAlias() . $sFieldName,
                        'type' => $sValueTypeName
                    )
                );
            }
        }
        if (!$this->database()->isField((Phpfox::getT('user_custom')),
            Phpfox::getService('custom')->getAlias() . $sFieldName)) {
            $this->database()->addField(array(
                    'table' => (Phpfox::getT('user_custom')),
                    'field' => Phpfox::getService('custom')->getAlias() . $sFieldName,
                    'type' => $sValueTypeName
                )
            );
        }
        // Insert into DB
        $iFieldId = $this->database()->insert($this->_sTable, $aSql);
        if ($bAddToOptions && !empty($aVals['option']) && is_array($aVals['option'])) {
            $this->_addOptions($iFieldId, $aVals);
        }
        // Add the new phrase
        if (!Phpfox::getService('language.phrase')->isValid($sVarName)) {
            foreach ($aVals['name'] as $sLang => $aName) {
                Phpfox::getService('language.phrase.process')->add([
                    'var_name' => $sVarName,
                    'text' => [$sLang => $aName['text']]
                ], true);
            }

        }

        // create component
        $this->database()->insert(Phpfox::getT('component'), array(
            'component' => 'cf_' . $sFieldName,
            'm_connection' => null,
            'module_id' => Phpfox::getLib('parse.input')->clean($aVals['module_id']),
            'product_id' => Phpfox::getLib('parse.input')->clean($aVals['product_id']),
            'is_controller' => '0',
            'is_block' => '1',
            'is_active' => '1'
        ));

        $sBlockTitle = array_values(array_pop($aVals['name']));
        $sBlockTitle = array_pop($sBlockTitle);

        // in order to display the field in profile.info we need to add a component
        $this->database()->insert(Phpfox::getT('block'), array(
            'title' => Phpfox::getLib('parse.input')->clean($sBlockTitle),
            'type_id' => '0',
            'm_connection' => 'profile.info',
            'module_id' => Phpfox::getLib('parse.input')->clean($aVals['module_id']),
            'product_id' => Phpfox::getLib('parse.input')->clean($aVals['product_id']),
            'component' => 'cf_' . $sFieldName,
            'location' => '2',
            'is_active' => 1,
            'ordering' => '10',
            'disallow_access' => null,
            'can_move' => '1',
            'version_id' => '1'
        ));

        $this->cache()->remove();

        return [
            $iFieldId,
            $this->_aOptions
        ];
    }

    /**
     * @param int $iId
     * @param array $aVals
     *
     * @return bool
     */
    public function update($iId, $aVals)
    {
        Phpfox::getUserParam('custom.can_manage_custom_fields', true);

        if ($bUsingOption = in_array($aVals['var_type'], ['select', 'radio', 'multiselect', 'checkbox'])) {
            $invalid = false;

            if (empty($aVals['current'])) {
                if (empty($aVals['option'])) {
                    $invalid = true;
                } else {
                    $emptyCount = 0;

                    foreach ($aVals['option'] as $optionTexts) {
                        if (empty($optionTexts)) {
                            $emptyCount++;
                        } else {
                            foreach ($optionTexts as $optionText) {
                                if (!isset($optionText['text']) || $optionText['text'] == '') {
                                    $emptyCount++;
                                    break;
                                }
                            }
                        }
                    }

                    if ($emptyCount == count($aVals['option'])) {
                        $invalid = true;
                    }
                }
            }

            if ($invalid) {
                return Phpfox_Error::set(_p('you_have_selected_that_this_field_is_a_select_custom_field_which_requires_at_least_one_option'));
            }
        }

        $aVals['field_id'] = $iId; // used in addOptions

        // $sKey == the language phrase
        foreach ($aVals['name'] as $sKey => $aPhrases) {
            foreach ($aPhrases as $sLang => $aValue) {
                if (empty($aValue['text'])) {
                    return Phpfox_Error::set(_p('provide_a_name_for_the_custom_field'));
                }
                if (Phpfox::getService('language.phrase')->isValid($sKey, $sLang)) {
                    Phpfox::getService('language.phrase.process')->updateVarName($sLang, $sKey, $aValue['text']);
                }
                else {
                    Phpfox::getService('language.phrase.process')->add([
                        'var_name' => Core\Lib::phrase()->correctLegacyPhrase($sKey),
                        'text' => [$sLang => $aValue['text']]
                    ], true);
                }
            }
        }

        if ($bUsingOption) {
            $aCurrentOptions = db()->select('option_id, phrase_var_name')
                ->from(Phpfox::getT('custom_option'))
                ->where([
                    'field_id' => $iId
                ])
                ->execute('getSlaveRows');

            if (!empty($aCurrentOptions)) {
                $aCurrentOptions = array_combine(array_column($aCurrentOptions, 'phrase_var_name'), array_column($aCurrentOptions, 'option_id'));
            }

            $aDeletedOptions = [];
            $aUpdatedOptions = [];

            if (!empty($aVals['current'])) {
                $aDeletedOptions = array_diff(array_keys($aCurrentOptions), array_keys($aVals['current']));
                $aUpdatedOptions = array_intersect(array_keys($aCurrentOptions), array_keys($aVals['current']));
            } elseif ($aCurrentOptions) {
                $aDeletedOptions = array_keys($aCurrentOptions);
            }

            if ($aUpdatedOptions) {
                // $sKey == the language phrase
                foreach ($aVals['current'] as $sKey => $aPhrases) {
                    if (strpos($sKey, '.') === false || !in_array($sKey, $aUpdatedOptions)) {
                        continue;
                    }
                    foreach ($aPhrases as $sLang => $aValue) {
                        if (Phpfox::getService('language.phrase')->isValid($sKey, $sLang)) {
                            Phpfox::getService('language.phrase.process')->updateVarName($sLang, $sKey, $aValue['text']);
                        }
                        else {
                            Phpfox::getService('language.phrase.process')->add([
                                'var_name' => Core\Lib::phrase()->correctLegacyPhrase($sKey),
                                'text' => [$sLang => $aValue['text']]
                            ], true);
                        }
                        if(isset($aValue['feed'])) {
                            if (Phpfox::getService('language.phrase')->isValid($sKey . '_feed', $sLang)) {
                                Phpfox::getService('language.phrase.process')->updateVarName($sLang, $sKey . '_feed', $aValue['feed']);
                            }
                            else {
                                Phpfox::getService('language.phrase.process')->add([
                                    'var_name' => Core\Lib::phrase()->correctLegacyPhrase($sKey) . '_feed',
                                    'text' => [$sLang => $aValue['feed']]
                                ]);
                            }
                        }
                    }
                }
            }

            if ($aDeletedOptions) {
                foreach ($aDeletedOptions as $iDeletedOption) {
                    if (isset($aCurrentOptions[$iDeletedOption])) {
                        $this->deleteOption($aCurrentOptions[$iDeletedOption]);
                    }
                }
            }

            if (!empty($aVals['option']) && is_array($aVals['option'])) {
                $this->_addOptions($iId, $aVals);
            }
        }

        $aInsertDb = [
            'group_id' => empty($aVals['group_id']) ? 0 : $aVals['group_id'],
            'type_id' => $aVals['type_id'],
            'is_required' => (int)$aVals['is_required'],
            'on_signup' => (int)$aVals['on_signup'],
            'is_search' => (int)$aVals['is_search'],
        ];

        if (empty($aVals['user_group_id']) || (!empty($aVals['user_group_id']) && Phpfox::getUserGroupParam($aVals['user_group_id'], 'custom.has_special_custom_fields'))) {
            $aInsertDb['user_group_id'] = $aVals['user_group_id'];
        }
        if ($this->database()->update($this->_sTable, $aInsertDb, 'field_id = ' . (int)$iId)) {
            if ($aVals['type_id'] == 'user_panel') {
                $fieldName = db()->select('field_name')
                    ->from(':custom_field')
                    ->where(['field_id' => (int)$iId])
                    ->executeField(false);
                if (!empty($fieldName)) {
                    $existedBlock = db()->select('block_id, m_connection, location')
                        ->from(':block')
                        ->where([
                            'module_id' => 'custom',
                            'component' => Phpfox::getService('custom')->getAlias() . $fieldName,
                        ])->executeRow(false);
                    if (!empty($existedBlock['block_id'])) {
                        $update = [];
                        if ($existedBlock['m_connection'] != 'profile.info') {
                            $update['m_connection'] = 'profile.info';
                        }
                        if ($existedBlock['location'] != 2) {
                            $update['location'] = 2;
                        }

                        if (!empty($update)) {
                            db()->update(':block', $update, ['block_id' => $existedBlock['block_id']]);
                        }
                    }
                }
            }

            $this->cache()->remove();
        }

        return true;
    }

    /**
     * @param int $iItemId The user whose custom field will be updated
     * @param int $iEditUserId The user that is updating the custom field (can be an admin)
     * @param array $aVals
     * @param bool $bForce
     *
     * @return bool
     */
    public function updateFields($iItemId, $iEditUserId, $aVals, $bForce = false)
    {
        if (empty($aVals)) {
            /* This happens when the Admin edits a user from the AdminCP but did not set any value for the custom fields*/
            return true;
        }

        $aFields = $this->database()->select('*')
            ->from($this->_sTable)
            ->where('field_id IN(' . implode(',', array_map('intval', array_keys($aVals))) . ')')
            ->execute('getSlaveRows');

        /* Get all the options by this user and these fields */
        $aChosen = $this->database()->select('*')
            ->from(Phpfox::getT('user_custom_multiple_value'))
            ->where('user_id = ' . $iItemId . ' AND field_id IN (' . implode(',',
                    array_map('intval', array_keys($aVals))) . ')')
            ->execute('getSlaveRows');

        $aUserValues = $this->database()->select('*')
            ->from(Phpfox::getT('user_custom_value'))
            ->where('user_id = ' . $iItemId)
            ->execute('getSlaveRow');

        /* Looping through the fields to check if the type needs to check for one table
         * or the other */
        foreach ($aFields as $aField) {
            foreach ($aVals as $iFieldId => $aVal) {
                if ($aField['field_id'] != $iFieldId) {
                    continue;
                }

                if (in_array($aField['var_type'], ['text', 'textarea', 'date'])) {

                    /* We check the user_custom_value table */
                    if ((
                        !is_array($aVal) && isset($aUserValues[Phpfox::getService('custom')->getAlias() . $aField['field_name']])
                        && md5($aVal) == md5($aUserValues[Phpfox::getService('custom')->getAlias() . $aField['field_name']])
                    )) {
                        continue;
                    }

                    if ($aField['var_type'] == 'date') {
                        $sDateValue = null;
                        if (!empty($aVal['custom_' . $iFieldId . '_day']) && !empty($aVal['custom_' . $iFieldId . '_month']) && !empty($aVal['custom_' . $iFieldId . '_year'])) {
                            $sDateValue = Phpfox::getLib('date')->mktime(0, 0, 0, $aVal['custom_' . $iFieldId . '_month'], $aVal['custom_' . $iFieldId . '_day'], $aVal['custom_' . $iFieldId . '_year']);
                        }
                        $aVal = $sDateValue ? $sDateValue : null;
                    }

                    if ($this->updateField($iFieldId, $iItemId, $iEditUserId, $aVal, $bForce) === false) {
                        return false;
                    }
                } // if no value has ever been set
                elseif (empty($aChosen)) {
                    if ($this->updateField($iFieldId, $iItemId, $iEditUserId, $aVal, $bForce) === false) {
                        return false;
                    }
                }
                // there is a third case when some options have been set but this is the first time
                // that we set this specific value
                else {
                    if ($this->updateField($iFieldId, $iItemId, $iEditUserId, $aVal, $bForce) === false) {
                        return false;
                    }
                    continue;
                }
            }
        }
        // Only add a feed if the user updated a custom field
        // (Avoids a feed when the user only updated Relationship status)
        if (Phpfox::isModule('feed') && count($this->_aFeedsAdded) && $iItemId == Phpfox::getUserId() && Phpfox::getParam('user.enable_feed_user_update_profile')) {
            Phpfox::getService('feed.process')->delete('custom', $iItemId);
            Phpfox::getService('feed.process')->add('custom', $iItemId);
        }

        (($sPlugin = Phpfox_Plugin::get('custom.service_process_updatefields')) ? eval($sPlugin) : false);

        return true;
    }

    /**
     * Updates the value(s) of a custom field.
     * An important change from 2.1.0 is that we stop adding columns to instead insert     *
     * @version 3.0.0
     * @uses    $this->_aFeedsAdded to control when to add a new feed (we only add one feed per
     *            custom field, in the callback it picks up all the options chosen)     *
     * @static  var array $aUser
     * @static  var array $aUserValue
     * @static  var string $sTable
     * @static  var string $sValueTable
     * @static  var boolean $bUpdated
     *
     * @param array|string $aField
     * @param int $iItemId
     * @param int $iEditUserId
     * @param string $sValue
     * @param bool $bForce
     *
     * @return string
     */
    public function updateField($aField, $iItemId, $iEditUserId, $sValue, $bForce = false)
    {
        if (!is_array($aField)) {
            $aField = $this->database()->select('*')
                ->from($this->_sTable)
                ->where('field_id = ' . (int)$aField)
                ->execute('getSlaveRow');
        }

        if (!isset($aField['field_id'])) {
            return Phpfox_Error::set(_p('not_a_valid_custom_field_to_edit'));
        }
        if ($bForce === false) {
            $bCanEdit = false;
            if ($iEditUserId == Phpfox::getUserId() || (Phpfox::getUserParam('custom.can_edit_other_custom_fields'))) {
                $bCanEdit = true;
            }

            if ($bCanEdit === false) {
                return Phpfox_Error::set(_p('you_do_not_have_permission_to_edit_this_field'));
            }
        }
        if ($aField['is_required'] && (!isset($sValue) || $sValue == '')) {
            return Phpfox_Error::set(_p('the_field_field_is_required', ['field' => _p($aField['phrase_var_name'])]));
        }
        /* Delete all the options this user has chosen*/
        $this->database()->delete(Phpfox::getT('user_custom_multiple_value'),
            'user_id = ' . $iItemId . ' AND field_id = ' . $aField['field_id']);
        /* We use this array to store the phrase vars of the options chosen by the user
         * Later, the values in this array are checked for feed language variables.
         */

        $parseOutput = Phpfox::getLib('parse.output');

        switch ($aField['var_type']) {
            case 'radio':
                $sReturnPhrase = $this->database()->select('phrase_var_name')
                    ->from(Phpfox::getT('custom_option'))
                    ->where('option_id = ' . (int)$sValue)
                    ->execute('getSlaveField');

                $sReturn = $parseOutput->clean(_p($sReturnPhrase));
                /* Add the feed */
                $this->_aFeedsAdded[$aField['field_id']] = $aField['field_id'];

                /* Add the option this user chose */
                $this->database()->insert(Phpfox::getT('user_custom_multiple_value'), array(
                    'user_id' => (int)$iItemId,
                    'field_id' => (int)$aField['field_id'],
                    'option_id' => (int)$sValue
                ));
                break;
            case 'multiselect':
            case 'checkbox':
                $sWhere = '';
                if (!is_array($sValue) || empty($sValue)) {
                    $sReturn = '';
                    $sValue = array();
                    break;
                }
                foreach ($sValue as $iVal) {
                    $sWhere .= 'option_id = ' . $iVal . ' OR ';
                }
                $sWhere = rtrim($sWhere, ' OR ');
                $sOptionValue = $this->database()->select('phrase_var_name, option_id')
                    ->from(Phpfox::getT('custom_option'))
                    ->where($sWhere)
                    ->execute('getSlaveRows');

                $sReturn = '';
                if (isset($sOptionValue[0]['phrase_var_name'])) {
                    foreach ($sOptionValue as $iKey => $aVal) {
                        $sReturn .= $parseOutput->clean(_p($aVal['phrase_var_name'])) . ', ';
                        /* Add the feed */
                        $this->_aFeedsAdded[$aField['field_id']] = $aField['field_id'];

                        /* Add the option this user chose */
                        $this->database()->insert(Phpfox::getT('user_custom_multiple_value'), array(
                            'user_id' => $iItemId,
                            'field_id' => $aField['field_id'],
                            'option_id' => $aVal['option_id']
                        ));
                    }
                }
                $sReturn = rtrim($sReturn, ', ');

                break;
            case 'select':
                $sOptionValue = $this->database()->select('phrase_var_name, option_id')
                    ->from(Phpfox::getT('custom_option'))
                    ->where('option_id = ' . (int)$sValue)
                    ->execute('getSlaveRow');

                if (!isset($sOptionValue['phrase_var_name'])) {
                    $sOptionValue['phrase_var_name'] = null;
                }
                /* Add the option this user chose */
                if (isset($sOptionValue['option_id'])) {
                    $this->database()->insert(Phpfox::getT('user_custom_multiple_value'),
                        array(
                            'user_id' => $iItemId,
                            'field_id' => $aField['field_id'],
                            'option_id' => $sOptionValue['option_id']
                        ));
                    /* Add the feed */
                    $this->_aFeedsAdded[$aField['field_id']] = $aField['field_id'];
                }

                $sReturn = Phpfox::getLib('parse.output')->clean(_p($sOptionValue['phrase_var_name']));
                break;
            case 'text':
            case 'date':
            case 'textarea':
                if ($aField['type_id'] == 'profile_panel') {
                    Phpfox::getLib('parse.output')->setImageParser(array(
                            'width' => 300,
                            'height' => 260
                        )
                    );
                }
                $sInsertValue = Phpfox::getLib('parse.input')->prepare($sValue);
                $sCleanValue = Phpfox::getLib('parse.input')->clean($sValue);
                $sReturn = Phpfox::getLib('parse.output')->parse($sInsertValue);
                $sValueTable = 'user_custom_value';
                $sTable = 'user_custom';

                switch ($aField['var_type']) {
                    case 'text':
                        $sTypeName = 'VARCHAR(255)';
                        $sValueTypeName = 'VARCHAR(255)';
                        break;
                    case 'textarea':
                        $sTypeName = 'MEDIUMTEXT';
                        $sValueTypeName = 'MEDIUMTEXT';
                        break;
                    case 'date':
                        $sTypeName = 'INT(10)';
                        $sValueTypeName = 'INT(10)';
                        break;
                    default:
                        return Phpfox_Error::set(_p('wrong_type'));
                }

                if (!$this->database()->isField(Phpfox::getT($sTable),
                    Phpfox::getService('custom')->getAlias() . $aField['field_name'])) {
                    $this->database()->addField(array(
                            'table' => Phpfox::getT($sTable),
                            'field' => Phpfox::getService('custom')->getAlias() . $aField['field_name'],
                            'type' => $sTypeName
                        )
                    );
                }


                if (!$this->database()->isField(Phpfox::getT($sValueTable),
                    Phpfox::getService('custom')->getAlias() . $aField['field_name'])) {
                    $this->database()->addField(array(
                            'table' => Phpfox::getT($sValueTable),
                            'field' => Phpfox::getService('custom')->getAlias() . $aField['field_name'],
                            'type' => $sValueTypeName
                        )
                    );
                }

                /* Check if we need to insert a new record or update an existing one*/
                $aExisting = $this->database()->select('user_id as userCustom, ' . Phpfox::getService('custom')->getAlias() . $aField['field_name'] . ' as currentValue')
                    ->from(Phpfox::getT($sTable))
                    ->where('user_id = ' . (int)$iItemId)
                    ->execute('getSlaveRow');

                if ($aField['var_type'] == 'date' && $sReturn) {
                    $sReturn = $this->formatDateFieldOrder($sReturn);
                }

                if (isset($aExisting['currentValue']) && $aExisting['currentValue'] == $sInsertValue) {
                    break;
                }

                if (isset($aExisting['userCustom']) && !empty($aExisting['userCustom'])) {
                    $this->database()->update(Phpfox::getT($sTable), array(
                        Phpfox::getService('custom')->getAlias() . $aField['field_name'] => $sInsertValue
                    ), 'user_id = ' . (int)$iItemId);
                } else {
                    $this->database()->insert(Phpfox::getT($sTable), array(
                        'user_id' => (int)$iItemId,
                        Phpfox::getService('custom')->getAlias() . $aField['field_name'] => $sInsertValue
                    ));
                }

                $aExisting = $this->database()->select('user_id as userCustomValue')
                    ->from(Phpfox::getT($sValueTable))
                    ->where('user_id = ' . (int)$iItemId)
                    ->execute('getSlaveRow');

                if (isset($aExisting['userCustomValue']) && !empty($aExisting['userCustomValue'])) {
                    $this->database()->update(Phpfox::getT($sValueTable), array(
                        Phpfox::getService('custom')->getAlias() . $aField['field_name'] => $sInsertValue
                    ), 'user_id = ' . (int)$iItemId);
                } else {
                    $this->database()->insert(Phpfox::getT($sValueTable), array(
                        'user_id' => (int)$iItemId,
                        Phpfox::getService('custom')->getAlias() . $aField['field_name'] => $sCleanValue
                    ));
                }
                $this->_aFeedsAdded[$iItemId] = $iItemId;
                break;

            default:
                break;
        }

        return isset($sReturn) ? $sReturn : ((!$aField['is_required'] && empty($sValue)) ? '' : false);
    }

    /**
     * @param        $sTime
     * @param string $sFieldSeparator
     *
     * @return float|int|string
     * @throws Exception
     */
    public function formatDateFieldOrder($sTime, $sFieldSeparator = '/')
    {
        $sReturn = '';
        if (!empty($sTime)) {
            $sValueMonth = Phpfox::getTime('n', $sTime, false);
            $sValueDay = Phpfox::getTime('j', $sTime, false);
            $sValueYear = Phpfox::getTime('Y', $sTime, false);
            switch (Phpfox::getParam("core.date_field_order")) {
                case 'DMY':
                    $sReturn = $sValueDay . $sFieldSeparator . $sValueMonth . $sFieldSeparator . $sValueYear;
                    break;
                case 'MDY':
                    $sReturn = $sValueMonth . $sFieldSeparator . $sValueDay . $sFieldSeparator . $sValueYear;
                    break;
                case 'YMD':
                    $sReturn = $sValueYear . $sFieldSeparator . $sValueMonth . $sFieldSeparator . $sValueDay;
                    break;
            }
        }
        return $sReturn;
    }

    /**
     * @param int $iId
     *
     * @return bool
     */
    public function toggleActivity($iId)
    {
        Phpfox::getUserParam('custom.can_manage_custom_fields', true);

        $aField = $this->database()->select('field_id, is_active')
            ->from($this->_sTable)
            ->where('field_id = ' . (int)$iId)
            ->execute('getSlaveRow');

        if (!isset($aField['field_id'])) {
            return Phpfox_Error::set(_p('unable_to_find_the_custom_field'));
        }

        $this->database()->update($this->_sTable, array('is_active' => ($aField['is_active'] ? 0 : 1)),
            'field_id = ' . $aField['field_id']);

        $this->cache()->removeGroup('custom');

        return true;
    }

    /**
     * @param array $aVals
     *
     * @return bool
     */
    public function updateOrder($aVals)
    {
        Phpfox::getUserParam('custom.can_manage_custom_fields', true);
        $aFields = $this->database()->select('field_id, field_name, type_id')
            ->from($this->_sTable)
            ->where('field_id IN (' . implode(',', array_keys($aVals)) . ')')
            ->execute('getSlaveRows');

        $this->database()->update(Phpfox::getT('block'), array('ordering' => 1),
            'component = "info" AND m_connection="profile.info" AND module_id = "profile"');
        foreach ($aVals as $iId => $iOrder) {
            $this->database()->update($this->_sTable, array('ordering' => (int)$iOrder), 'field_id = ' . (int)$iId);
            foreach ($aFields as $aField) {
                if ($aField['field_id'] == $iId) {
                    $this->database()->update(Phpfox::getT('block'), array('ordering' => (1 + (int)$iOrder)),
                        'component = "cf_' . $aField['field_name'] . '" AND m_connection="profile.info"');
                }
            }
        }

        $this->cache()->removeGroup('custom');

        if (in_array('user_panel', array_unique(array_column($aFields, 'type_id')))) {
            Phpfox::getService('admincp.block.process')->clearBlockCaches(['default']);
        }

        return true;
    }

    /**
     * @param int $iId
     *
     * @return bool
     */
    public function delete($iId)
    {
        Phpfox::getUserParam('custom.can_manage_custom_fields', true);

        $aField = $this->database()
            ->select('*')
            ->from($this->_sTable)
            ->where('field_id = ' . (int)$iId)
            ->execute('getSlaveRow');

        if (!isset($aField['field_id'])) {
            return Phpfox_Error::set(_p('unable_to_find_the_custom_field_you_want_to_delete'));
        }

        $sTable = 'user_custom';
        $sValueTable = 'user_custom_value';
        if ($aField['user_group_id'] > 0) {
            $aCustomFields = $this->database()->select('name, default_value')
                ->from(Phpfox::getT('user_group_custom'))
                ->where('module_id = "custom" AND (name = "has_custom_fields" OR name = "custom_table_name")')
                ->execute('getSlaveRows');
            $bHasCustom = false;
            $sTableName = '';
            foreach ($aCustomFields as $aTField) {
                if ($aTField['name'] == 'has_custom_fields' && $aTField['default_value'] == 1) {
                    $bHasCustom = true;
                }
                if ($aTField['name'] == 'custom_table_name') {
                    $sTableName = $aTField['default_value'];
                }
            }
            if ($bHasCustom && !empty($sTableName)) {
                $sTable = $sTableName;
                $sValueTable = $sTable . '_value';
            }
        }

        list($sModule, $sPhrase) = explode('.', $aField['phrase_var_name']);

        $this->database()->delete(Phpfox::getT('language_phrase'), 'var_name = \'' . $sPhrase . '\'');

        if ($this->database()->isField(Phpfox::getT($sTable),
            Phpfox::getService('custom')->getAlias() . $aField['field_name'])) {
            $this->database()->dropField(Phpfox::getT($sTable),
                Phpfox::getService('custom')->getAlias() . $aField['field_name']);
        }

        if ($this->database()->isField(Phpfox::getT($sValueTable),
            Phpfox::getService('custom')->getAlias() . $aField['field_name'])) {
            $this->database()->dropField(Phpfox::getT($sValueTable),
                Phpfox::getService('custom')->getAlias() . $aField['field_name']);
        }

        $aOptions = $this->database()->select('*')
            ->from(Phpfox::getT('custom_option'))
            ->where('field_id = ' . $aField['field_id'])
            ->execute('getSlaveRows');

        foreach ($aOptions as $aOption) {
            list($sModule, $sPhrase) = explode('.', $aOption['phrase_var_name']);
            $this->database()->delete(Phpfox::getT('language_phrase'), 'var_name = \'' . $sPhrase . '\'');
        }

        $this->database()->delete(Phpfox::getT('custom_option'), 'field_id = ' . $aField['field_id']);
        // Also delete the options this user chose, if the field is being deleted there is no reason to keep them
        if ($aField['var_type'] != 'textarea') {
            $this->database()->delete(Phpfox::getT('user_custom_multiple_value'), 'field_id = ' . $aField['field_id']);
        }
        $this->database()->delete($this->_sTable, 'field_id = ' . $aField['field_id']);
        // remove the component and the block that were possibly created along with this custom field
        $this->database()->delete(Phpfox::getT('component'),
            'component = "cf_' . $aField['field_name'] .
            '" AND m_connection is null AND module_id = "' . $aField['module_id'] . '" AND
			    product_id = "' . $aField['product_id'] . '" AND is_controller = 0 AND is_block = 1');

        $this->database()->delete(Phpfox::getT('block'),
            'component = "cf_' . $aField['field_name'] . '" AND module_id = "' . $aField['module_id'] . '"'
            . ' AND product_id = "' . $aField['product_id'] . '" AND m_connection ="profile.info" AND type_id = 0');
        $this->cache()->remove();

        return true;
    }

    /**
     * @param int $iId
     *
     * @return bool
     */
    public function deleteOption($iId)
    {
        Phpfox::getUserParam('custom.can_manage_custom_fields', true);

        $aOption = $this->database()->select('co.*, cf.field_name, cf.user_group_id, cf.var_type, cf.field_id')
            ->from(Phpfox::getT('custom_option'), 'co')
            ->join(Phpfox::getT('custom_field'), 'cf', 'cf.field_id = co.field_id')
            ->where('co.option_id = ' . (int)$iId)
            ->execute('getSlaveRow');

        if (!isset($aOption['option_id'])) {
            return Phpfox_Error::set(_p('unable_to_find_the_custom_option_you_plan_on_deleting'));
        }
        if ($aOption['var_type'] == 'select' || $aOption['var_type'] == 'multiselect'
            || $aOption['var_type'] == 'checkbox' || $aOption['var_type'] == 'radio') {
            Phpfox::getService('language.phrase.process')->delete($aOption['phrase_var_name']);
            $this->database()->delete(Phpfox::getT('custom_option'), 'option_id = ' . $aOption['option_id']);
            $this->database()->delete(Phpfox::getT('user_custom_multiple_value'),
                'option_id = ' . $aOption['option_id'] . ' AND field_id = ' . $aOption['field_id']);
            return true;
        }
        $sTable = 'user_custom';
        $sValueTable = 'user_custom_value';
        if ($aOption['user_group_id'] > 0) {
            if (Phpfox::getUserGroupParam($aOption['user_group_id'], 'custom.has_special_custom_fields')) {
                $sTable = Phpfox::getUserGroupParam($aOption['user_group_id'], 'custom.custom_table_name');
                $sValueTable = $sTable . '_value';
            }
        }

        $sFieldName = Phpfox::getService('custom')->getAlias() . $aOption['field_name'];

        $this->database()->update(Phpfox::getT($sTable), array($sFieldName => null),
            $sFieldName . ' = \'' . $this->database()->escape($aOption['phrase_var_name']) . '\'');
        $this->database()->update(Phpfox::getT($sValueTable), array($sFieldName => 0),
            $sFieldName . ' = \'' . $aOption['option_id'] . '\'');

        list($sModule, $sPhrase) = explode('.', $aOption['phrase_var_name']);
        $this->database()->delete(Phpfox::getT('language_phrase'), 'var_name = \'' . $sPhrase . '\'');

        $this->database()->delete(Phpfox::getT('custom_option'), 'option_id = ' . $aOption['option_id']);

        return true;
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
        if ($sPlugin = Phpfox_Plugin::get('custom.service_process__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }

    /**
     * @param int $iFieldId
     * @param array $aVals
     *
     * @return bool
     */
    private function _addOptions($iFieldId, &$aVals)
    {
        // it 	adds a new language phrase and the var_name is in the form "cf_option_" + <field_id> + <seq_number>
        // but the sequence number may overlap an existing option, so we need to make sure this value is unique
        $aExisting = array();
        if (isset($aVals['current'])) {
            foreach ($aVals['current'] as $sVarName => $aVal) {
                $aExisting[] = str_replace('custom.cf_option_' . $aVals['field_id'] . '_', '', $sVarName);
            }
        }

        foreach ($aVals['option'] as $iKey => $aOptions) {
            if (isset($aVals['option'][$iKey]['added']) && $aVals['option'][$iKey]['added'] == true) {
                continue;
            }
            $aOptionsAdded = array();
            $iSeqNumber = in_array($iKey, $aExisting) ? (max($aExisting) + 1) : $iKey;
            $aExisting[] = $iSeqNumber;
            foreach ($aOptions as $sLang => $aOption) {
                if (empty($aOption['text'])) {
                    continue;
                }

                $sPhraseVar = 'cf_option_' . $iFieldId . '_' . $iSeqNumber;

                Phpfox::getService('language.phrase.process')->add([
                    'var_name' => $sPhraseVar,
                    'text' => [$sLang => $aOption['text']]
                ]);

                // Only add one option per language
                if (!in_array($iKey, $aOptionsAdded)) {
                    $this->_aOptions[$iKey . $sLang] = $this->database()->insert(Phpfox::getT('custom_option'), array(
                            'field_id' => $iFieldId,
                            'phrase_var_name' => $aVals['module_id'] . '.' . $sPhraseVar
                        )
                    );
                    $aOptionsAdded[] = $iKey;
                }

                if (isset($aOption['feed'])) {
                    Phpfox::getService('language.phrase.process')->add([
                        'var_name' => 'cf_option_' . Phpfox::getService('language.phrase.process')->prepare($aOption['text']) . '_feed',
                        'text' => [$sLang => $aOption['feed']]
                    ]);
                }

            }
            $aVals['option'][$iKey]['added'] = true;
        }

        return true;
    }
}