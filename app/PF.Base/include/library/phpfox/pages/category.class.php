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
 * @package 		Phpfox_Service
 * @version 		$Id: category.class.php 5099 2013-01-07 19:01:38Z phpFox LLC $
 */
abstract class Phpfox_Pages_Category extends Phpfox_Service
{
	/**
	 * Class constructor
	 */	
	public function __construct()
	{	
		$this->_sTable = Phpfox::getT('pages_category');
	}

	/**
	 * @return Phpfox_Pages_Facade
	 */
	abstract public function getFacade();
	
	public function getCategories()
	{
		return $this->getAllCategories();
	}

    /**
     * Get categories by type id
     *
     * @param $iTypeId
     * @param int $iCacheTime , default 5
     * @return array
     */
    public function getByTypeId($iTypeId, $iCacheTime = 5)
    {
        $aRows = $this->database()->select('*')
            ->from($this->_sTable)
            ->where('type_id = ' . (int)$iTypeId . ' AND is_active = 1')
            ->order('ordering ASC')
            ->execute('getSlaveRows');
        foreach ($aRows as &$aRow) {
            $aRow['link'] = $aRow['url'] = Phpfox::permalink($this->getFacade()->getItemType() . '.sub-category',
                $aRow['category_id'], $aRow['name']);
        }
        return $aRows;
    }
	
	public function getById($iId)
	{
		$aRow = $this->database()->select('pc.*, pt.name AS type_name, pt.type_id')
			->from($this->_sTable, 'pc')
			->join(Phpfox::getT('pages_type'), 'pt', 'pt.type_id = pc.type_id')
			->where('pc.category_id = ' . (int) $iId . ' AND pc.is_active = 1')
			->execute('getSlaveRow');
		
		if (!isset($aRow['category_id']))
		{
			return false;
		}
		
		return $aRow;
	}

	/**
	 *
	 * @return array
	 */
	public function getAllCategories()
	{
        $sCacheId = $this->cache()->set($this->getFacade()->getItemType() . '_categories');
        if (false === ($aAllCategories = $this->cache()->get($sCacheId))) {
            $aAllCategories = [];
			$aRows = $this->database()->select('pc.*, pt.name AS type_name, pt.type_id')
				->from($this->_sTable, 'pc')
				->join(Phpfox::getT('pages_type'), 'pt', 'pt.type_id = pc.type_id')
				->where('pc.is_active = 1')
				->where('pt.item_type = ' . $this->getFacade()->getItemTypeId())
				->execute('getSlaveRows');

			foreach($aRows as $aRow){
                $aAllCategories[$aRow['category_id']] =  $aRow;
			}

            $this->cache()->save($sCacheId, $aAllCategories);
            $this->cache()->group($this->getFacade()->getItemType(), $sCacheId);
		}

		return $aAllCategories;

	}

	public function getForAdmin($iTypeId)
	{
		$aRows = $this->database()->select('*')
			->from($this->_sTable)
			->where('type_id = ' . (int) $iTypeId)
			->order('ordering ASC')
			->execute('getSlaveRows');	
		return $aRows;
	}	
	
	public function getForEdit($iId)
	{
		$aRow = $this->database()->select('*')
			->from(Phpfox::getT('pages_category'))
			->where('category_id = ' . (int) $iId)
			->execute('getSlaveRow');

		if (!isset($aRow['category_id']))
		{
			return false;
		}

        //Support legacy phrases
        if (substr($aRow['name'], 0, 7) == '{phrase' && substr($aRow['name'], -1) == '}') {
            $aRow['name'] = preg_replace('/\s+/', ' ', $aRow['name']);
            $aRow['name'] = str_replace([
                "{phrase var='",
                "{phrase var=\"",
                "'}",
                "\"}"
            ], "", $aRow['name']);
        }//End support legacy
        $aLanguages = Phpfox::getService('language')->getAll();
        foreach ($aLanguages as $aLanguage){
            $sPhraseValue = (Core\Lib::phrase()->isPhrase($aRow['name'])) ? _p($aRow['name'], [], $aLanguage['language_id']) : $aRow['name'];
            $aRow['name_' . $aLanguage['language_id']] = $sPhraseValue;
        }
		
		return $aRow;
	}	
	
	/**
	 * If a call is made to an unknown method attempt to connect
	 * it to a specific plug-in with the same name thus allowing 
	 * plug-in developers the ability to extend classes.
	 *
	 * @param string $sMethod is the name of the method
	 * @param array $aArguments is the array of arguments of being passed
	 */
	public function __call($sMethod, $aArguments)
	{
		/**
		 * Check if such a plug-in exists and if it does call it.
		 */
		if ($sPlugin = Phpfox_Plugin::get($this->getFacade()->getItemType() . '.service_category_category__call'))
		{
			eval($sPlugin);
			return;
		}
			
		/**
		 * No method or plug-in found we must throw a error.
		 */
		Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
	}
}