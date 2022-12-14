<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 *
 *
 * @copyright        [PHPFOX_COPYRIGHT]
 * @author           phpFox LLC
 * @package          Module_Admincp
 * @version          $Id: process.class.php 2228 2010-12-02 21:02:59Z phpFox LLC $
 */
class Admincp_Service_Block_Process extends Phpfox_Service
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('block');
    }

    /**
     * @param \Core\App\App $App
     */
    public function importFromApp($App)
    {
        //Add component block
        if (isset($App->component_block)) {
            $InsertData = [];
            foreach ($App->component_block as $key => $value) {
                $sModuleId = (isset($value->module_id) ? $value->module_id : $App->alias);
                //Check block is exist
                $iCnt = db()->select('COUNT(*)')
                    ->from(':block')
                    ->where('m_connection="' . $value->m_connection . '" AND component= "' . $value->component . '" AND module_id="' . $sModuleId . '"')
                    ->executeField();
                if ($iCnt) {
                    //this block is exist
                    continue;
                }
                $InsertData[] = [
                    (isset($value->title) ? $value->title : $key), //title
                    $value->type_id,//type_id
                    $value->m_connection,//m_connection
                    $sModuleId,//module_id
                    'phpfox',//product_id
                    $value->component,//component
                    $value->location,//location
                    $value->is_active,//is_active
                    $value->ordering,//ordering
                    (isset($value->disallow_access) ? $value->disallow_access : null),//disallow_access
                    (isset($value->can_move) ? $value->can_move : '0'),//can_move
                    (isset($value->version_id) ? $value->version_id : null),//version_id
                    (isset($value->params) ? json_encode($value->params) : null), //params
                ];
            }
            if (count($InsertData)) {
                db()->multiInsert(Phpfox::getT('block'), [
                    'title',
                    'type_id',
                    'm_connection',
                    'module_id',
                    'product_id',
                    'component',
                    'location',
                    'is_active',
                    'ordering',
                    'disallow_access',
                    'can_move',
                    'version_id',
                    'params'
                ], $InsertData);
            }
        }
        //Remove component block
        if (is_array($App->component_block_remove) && count($App->component_block_remove)) {
            foreach ($App->component_block_remove as $aBlockRemove) {
                if (!isset($aBlockRemove->m_connection) || !isset($aBlockRemove->component)) {
                    continue;
                }
                db()->delete(Phpfox::getT('block'), [
                    'module_id' => $App->alias,
                    'm_connection' => $aBlockRemove->m_connection,
                    'component' => $aBlockRemove->component
                ]);
            }
        }
    }

    /**
     * @param array $aVals
     * @param bool  $bIsUpdate
     *
     * @return bool
     */
    public function add($aVals, $bIsUpdate = false)
    {
        if (!$aVals['type_id'] && empty($aVals['component'])) {
            return Phpfox_Error::set(_p('select_component'));
        }
        
        // Find the user groups we disallowed
        $aDisallow = [];
        $aUserGroups = Phpfox::getService('user.group')->get();
        if (isset($aVals['allow_access'])) {
            foreach ($aUserGroups as $aUserGroup) {
                if (!in_array($aUserGroup['user_group_id'], $aVals['allow_access'])) {
                    $aDisallow[] = $aUserGroup['user_group_id'];
                }
            }
        } else {
            foreach ($aUserGroups as $aUserGroup) {
                $aDisallow[] = $aUserGroup['user_group_id'];
            }
        }
        
        if ($aVals['type_id'] == '5') {
            $aVals['module_id'] = '_app';
            
        } else {
            if (!$aVals['type_id']) {
                $aParts = explode('|', $aVals['component']);
                $aVals['component'] = isset($aParts[1])?$aParts[1]:null;
                $aVals['module_id'] = Phpfox_Module::instance()->getModuleId($aParts[0]);
            } else {
                $aParts = explode('|', $aVals['m_connection']);
                $aVals['component'] = null;
                $aVals['module_id'] = Phpfox_Module::instance()->getModuleId($aParts[0]);
            }
        }
        
        if (empty($aVals['module_id'])) {
            $aVals['module_id'] = 'core';
        }
        
        $aVals['disallow_access'] = (count($aDisallow) ? serialize($aDisallow) : null);
        $aVals['title'] = (empty($aVals['title']) ? null : $this->preParse()->clean($aVals['title']));
        
        if (isset($aVals['style_id']) && is_array($aVals['style_id'])) {
            $aPostInfo = [];
            foreach ($aVals['style_id'] as $iStyleId => $iLocation) {
                if (empty($iLocation)) {
                    continue;
                }
                $aPostInfo[$iStyleId] = $iLocation;
            }
            
            if (count($aPostInfo)) {
                $aVals['location'] = serialize([
                    'g' => $aVals['location'],
                    's' => $aPostInfo
                ]);
            }
        }

        if ($aVals['m_connection'] == 'site_wide') {
            $aVals['m_connection'] = '';
        }
        
        (($sPlugin = Phpfox_Plugin::get('admincp.service_block_process_add')) ? eval($sPlugin) : false);
        
        if ($bIsUpdate) {
            $iId = $aVals['block_id'];
            $this->database()->process([
                'type_id'   => 'int',
                'title',
                'm_connection',
                'module_id',
                'product_id',
                'component',
                'location',
                'is_active' => 'int',
                'disallow_access',
                'can_move'  => 'int'
            ], $aVals)->update($this->_sTable, 'block_id = ' . (int)$aVals['block_id']);
            
            if (!$aVals['can_move']) {
                if ($aVals['m_connection'] == 'core.index-member') {
                    $this->database()->delete(Phpfox::getT('user_dashboard'), 'cache_id = \'js_block_border_' . $aVals['module_id'] . '_' . $aVals['component'] . '\'');
                }
                
                if ($aVals['m_connection'] == 'profile.index') {
                    $this->database()->delete(Phpfox::getT('user_design_order'), 'cache_id = \'js_block_border_' . $aVals['module_id'] . '_' . $aVals['component'] . '\'');
                }
            }
        } else {
            $iCount = $this->database()->select('ordering')
                ->from($this->_sTable)
                ->where("m_connection = '" . $this->database()->escape($aVals['m_connection']) . "'")
                ->order('ordering DESC')
                ->execute('getSlaveField');
            
            $aVals['ordering'] = ($iCount + 1);
            $aVals['version_id'] = Phpfox::getId();
            
            $iId = $this->database()
                ->process([
                    'type_id'    => 'int',
                    'title',
                    'm_connection',
                    'module_id',
                    'product_id',
                    'component',
                    'location'   => 'int',
                    'is_active'  => 'int',
                    'ordering'   => 'int',
                    'disallow_access',
                    'can_move'   => 'int',
                    'version_id' => 'int'
                ], $aVals)->insert($this->_sTable);
        }
        
        $this->cache()->remove();

        if ($aVals['type_id'] > 0 && isset($aVals['source_code'])) {
            $aVals['source_parsed'] = $aVals['source_code'];
            if ($bIsUpdate) {
                Phpfox::getLib('template.cache')->remove('template/' . md5($iId) . '.php');
            }
            $this->database()->delete(Phpfox::getT('block_source'), 'block_id = ' . (int)$iId);
            $this->database()->insert(Phpfox::getT('block_source'), [
                'block_id'      => $iId,
                'source_code'   => (empty($aVals['source_code']) ? null : $aVals['source_code']),
                'source_parsed' => (empty($aVals['source_parsed']) ? null : $aVals['source_parsed'])
            ]);
        }
        
        return true;
    }
    
    /**
     * @param int   $iId
     * @param array $aVals
     *
     * @return bool
     */
    public function update($iId, $aVals)
    {
        $aVals['block_id'] = $iId;
        
        $this->add($aVals, true);
        
        return true;
    }
    
    /**
     * @param array    $aVals
     * @param null|int $iStyleId
     *
     * @return bool
     */
    public function updateOrder($aVals, $iStyleId = null)
    {
        $iCnt = 0;
        foreach ($aVals as $iId => $aValue) {
            $iCnt++;
            
            if ($iStyleId !== null) {
                $iCheck = (int)$this->database()->select('order_id')
                    ->from(Phpfox::getT('block_order'))
                    ->where('style_id = ' . (int)$iStyleId . ' AND block_id = ' . (int)$iId . '')
                    ->execute('getSlaveField');
                
                if ($iCheck) {
                    $this->database()->update(Phpfox::getT('block_order'), [
                            'style_id' => (int)$iStyleId,
                            'block_id' => (int)$iId,
                            'ordering' => (int)$iCnt
                        ], 'order_id =' . $iCheck);
                } else {
                    $this->database()->insert(Phpfox::getT('block_order'), [
                            'style_id' => (int)$iStyleId,
                            'block_id' => (int)$iId,
                            'ordering' => (int)$iCnt
                        ]);
                }
            } else {
                $this->database()->update($this->_sTable, ['ordering' => $iCnt], 'block_id = ' . (int)$iId);
            }
        }
        
        $this->cache()->remove();
        
        return true;
    }
    
    /**
     * @param int $iId
     *
     * @return bool
     */
    public function delete($iId)
    {
        (($sPlugin = Phpfox_Plugin::get('admincp.service_block_process_delete')) ? eval($sPlugin) : false);
        
        $this->database()->delete($this->_sTable, 'block_id = ' . (int)$iId);
        $this->database()->delete(Phpfox::getT('block_source'), 'block_id = ' . (int)$iId);

        $this->cache()->remove();
        
        return true;
    }
    
    /**
     * @param int $iId
     * @param int $iType
     */
    public function updateActivity($iId, $iType)
    {
        Phpfox::isUser(true);
        Phpfox::getUserParam('admincp.has_admin_access', true);
        
        $this->database()->update($this->_sTable, ['is_active' => (int)($iType == '1' ? 1 : 0)], 'block_id = ' . (int)$iId);

        $this->cache()->remove();
    }

    /**
     * Clear caches from module blocks
     * @param array $types
     */
    public function clearBlockCaches($types = [])
    {
        $userGroupId = Phpfox::getUserBy('user_group_id');
        $cacheObject = $this->cache();

        if (empty($types) || in_array('default', $types)) {
            $cacheObject->remove('block_all_' . $userGroupId);
            $cacheObject->remove('block_move_' . $userGroupId);
        }

        if (empty($types) || in_array('source', $types)) {
            $cacheObject->remove('block_source_code_' . $userGroupId);
        }

        if (empty($types) || in_array('app', $types)) {
            $cacheObject->remove('block_app_code_' . $userGroupId);
        }
    }
    
    /**
     * If a call is made to an unknown method attempt to connect
     * it to a specific plug-in with the same name thus allowing
     * plug-in developers the ability to extend classes.
     *
     * @param string $sMethod    is the name of the method
     * @param array  $aArguments is the array of arguments of being passed
     *
     * @return null
     */
    public function __call($sMethod, $aArguments)
    {
        /**
         * Check if such a plug-in exists and if it does call it.
         */
        if ($sPlugin = Phpfox_Plugin::get('admincp.service_block_process___call')) {
            return eval($sPlugin);
        }
        
        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
        return null;
    }
}