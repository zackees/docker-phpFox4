<?php

defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Custom_Service_Group_Group
 */
class Custom_Service_Group_Group extends Phpfox_Service
{
    /**
     * @var string
     */
    protected $_sTable = '';
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('custom_group');
    }

    /**
     * @return array
     */
    public function get()
    {
        $groups = $this->database()->select('cg.*')
            ->from($this->_sTable, 'cg')
            ->join(Phpfox::getT('module'), 'm', 'm.module_id = cg.module_id AND m.is_active = 1')
            ->order('cg.ordering ASC')
            ->execute('getSlaveRows');

        foreach ($groups as $key => $group) {
            $groups[$key]['phrase_var_name_text'] = Phpfox::getLib('parse.output')->clean(_p($group['phrase_var_name']));
        }

        return $groups;
    }

    /**
     * @param int $iId
     *
     * @return array
     */
    public function getGroup($iId)
    {
        $group = $this->database()->select('*')
            ->from($this->_sTable)
            ->where('group_id = ' . (int)$iId)
            ->execute('getSlaveRow');

        if ($group) {
            $group['phrase_var_name_text'] = Phpfox::getLib('parse.output')->clean(_p($group['phrase_var_name']));
        }

        return $group;
    }

    /**
     * @param string $sType
     * @param int $iUserGroup
     *
     * @return array
     */
    public function getGroups($sType, $iUserGroup)
    {
        $iGroup = 0;
        $aWhere = array('type_id = \'' . $this->database()->escape($sType) . '\' AND is_active = 1');
        if (Phpfox::getUserGroupParam($iUserGroup, 'custom.has_special_custom_fields')) {
            $iGroup = $iUserGroup;
            $iInherit = $this->database()->select('inherit_id')->from(Phpfox::getT('user_group'))->where('user_group_id = ' . (int)$iUserGroup)->execute('getSlaveField');
            $aWhere[] = 'AND (user_group_id = 0 OR user_group_id = ' . $iInherit . ' OR user_group_id = ' . (int)$iGroup . ')';
        } else {
            $aWhere[] = 'AND (user_group_id = 0 OR user_group_id = ' . (int)$iGroup . ')';
        }

        $aGroups = $this->database()
            ->select('*')
            ->from($this->_sTable)
            ->where($aWhere)
            ->order('ordering ASC')
            ->execute('getSlaveRows');

        foreach ($aGroups as $key => $aGroup) {
            $aGroups[$key]['phrase_var_name_text'] = Phpfox::getLib('parse.output')->clean(_p($aGroup['phrase_var_name']));
        }

        return $aGroups;
    }

    /**
     * @param int $iId
     *
     * @return array
     */
    public function getForEdit($iId)
    {
        $aGroup = $this->database()
            ->select('*')
            ->from($this->_sTable)
            ->where('group_id = ' . (int)$iId)
            ->execute('getSlaveRow');

        list($sModule, $sVarName) = explode('.', $aGroup['phrase_var_name']);

        $aPhrases = $this->database()
            ->select('language_id, text')
            ->from(Phpfox::getT('language_phrase'))
            ->where('var_name = \'' . $this->database()->escape($sVarName) . '\'')
            ->execute('getSlaveRows');

        foreach ($aPhrases as $aPhrase) {
            $aGroup['group'][$aGroup['phrase_var_name']][$aPhrase['language_id']] = $aPhrase['text'];
        }

        return $aGroup;
    }

    /**
     * @param string $sVarName
     *
     * @return int
     */
    public function getId($sVarName)
    {
        return $this->database()
            ->select('group_id')
            ->from($this->_sTable)
            ->where('phrase_var_name = \'' . $this->database()->escape($sVarName) . '\'')
            ->execute('getSlaveField');
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
        if ($sPlugin = Phpfox_Plugin::get('custom.service_group_group__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}
