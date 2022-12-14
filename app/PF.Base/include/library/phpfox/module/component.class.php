<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 * Module Component Parent
 * Class is parent with all block/controller components that control each
 * page on the site as well as blocks found on this pages.
 *
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author			phpFox
 * @package 		phpFox
 * @version 		$Id: component.class.php 2721 2011-07-09 09:04:37Z phpFox LLC $
 */
class Phpfox_Component
{
	/**
	 * Params that can be passed to a component other then request methods.
	 *
	 * @static 
	 * @var array
	 */
	private static $_aParams = array();

    /**
     * @var array
     */
	private $aParams = [];

	/**
	 * Holds the current module that component belongs to.
	 *
	 * @var string
	 */
	private $_sModule;
	
	/**
	 * Holds the current component being loaded.
	 *
	 * @var string
	 */
	private $_sComponent;	
	
	/**
	 * Groups params based on unique cache ID for the component.
	 *
	 * @var string
	 */
	private $_sCacheVar = null;
	
	/**
	 * Holds the current menu for the page we are on.
	 *
	 * @var string
	 */
	private static $_sMenuName = null;
	
	/**
	 * Class constructor that sets all the variables to identify what component we are loading,
	 * module it is a part of and any custom params we are passing.
	 *
	 * @param array $aParams ARRAY of params we are passing to the component.
	 */
	public function __construct($aParams)
	{
		$this->_sModule = $aParams['sModule'];
		$this->_sComponent = $aParams['sComponent'];
		$this->setParam($aParams['aParams'], '', false);
	}

	/**
	 * Gets a param that is part of this component group.
	 *
	 * @param string $sData Param name.
	 * @return mixed If param exists we return the param value otherwise we return NULL.
	 */
	public function __get($sData)
	{
		if (isset(self::$_aParams[$this->_sCacheVar][$sData]))
		{
			return self::$_aParams[$this->_sCacheVar][$sData];
		}
		
		Phpfox_Error::trigger('Undefined property: ' . $sData, E_USER_ERROR);
	}	
	
	/**
	 * Set a param that can be used in other component so we don't pass information via get/post requests.
	 *
	 * @param mixed $mParams ARRAY or string of param with values.
	 * @param string $sValue Value of param if argument 1 is not an ARRAY.
	 */	
	public static function setPublicParam($mParams, $sValue = '')
	{
		if (!is_array($mParams))
		{
			$mParams = array($mParams => $sValue);
		}
		
		foreach ($mParams as $sVar => $sValue)
		{			
			self::$_aParams[$sVar] = $sValue;
		}
	}
	
	/**
	 * Set the menu name for the current page we are on.
	 *
	 * @param string $sMenuName Unique menu name.
	 */
	protected function _setMenuName($sMenuName)
	{
		self::$_sMenuName = $sMenuName;
	}
	
	/**
	 * Get the current menu name of the page we are on.
	 *
	 * @return string
	 */
	protected function _getMenuName()
	{
		return self::$_sMenuName;
	}
	
	/**
	 * Set a param that can be used in other component so we don't pass information via get/post requests.
	 *
	 * @param mixed $mParams ARRAY or string of param with values.
	 * @param string $sValue Value of param if argument 1 is not an ARRAY.
     * @param boolean $bGlobal add params to global params
	 */
	protected function setParam($mParams, $sValue = '', $bGlobal = true)
	{
		if (!is_array($mParams))
		{
			$mParams = array($mParams => $sValue);
		}
		
		foreach ($mParams as $sVar => $sValue)
		{
			if ($sVar == 'attachment_share')
			{
				$this->template()->setHeader(array(
						'share.js' => 'module_attachment'
					)
				);
			}

			if ($bGlobal) {
                self::$_aParams[$sVar] = $sValue;
            }
            else {
			    $this->aParams[$sVar] = $sValue;
            }
		}
	}

	public static function __getParam($key) {
        return (isset(self::$_aParams[$key])?self::$_aParams[$key]:null);
	}
	
	/**
	 * Get a param for any component loaded after it was set using the method setParam()
	 *
	 * @see self::setParam()
	 * @param string $sVar Var name of the param.
	 * @param string $mDef If we cannot find the param you can provide a default value.
	 * @return mixed If the param exists we return the value or NULL if nothing was found and a default value was not provided.
	 */
	protected function getParam($sVar, $mDef = null)
	{
	    if (isset($this->aParams[$sVar])) {
	        return $this->aParams[$sVar];
        }
		return (isset(self::$_aParams[$sVar]) ? self::$_aParams[$sVar] : $mDef);
	}	
	
	/**
	 * Clears a param that was set earlier.
	 *
	 * @param string|array $sVar
	 */
	protected function clearParam($sVar)
	{
        if (is_string($sVar)) {
            $sVar = [$sVar];
        }
        foreach ($sVar as $value) {
            unset(self::$_aParams[$value], $this->aParams[$value]);
        }
    }

	/**
	 * Extends the template class and returns its class object.
	 *
	 * @see Phpfox_Template
	 * @return Phpfox_Template
	 */
	protected function template()
	{
		return Phpfox_Template::instance();
	}	
	
	/**
	 * Extends the url class and returns its class object.
	 *
	 * @see Phpfox_Url
	 * @return Phpfox_Url
	 */
	protected function url()
	{
		return Phpfox_Url::instance();
	}	
	
	/**
	 * Extends the request class and returns its class object.
	 *
	 * @see Phpfox_Request
	 * @return Phpfox_Request
	 */
	protected function request()
	{
		return Phpfox_Request::instance();
	}	
	
	/**
	 * Extends the search class and returns its class object.
	 *
	 * @see Phpfox_Search
	 * @return Phpfox_Search
	 */
	protected function search()
	{
		return Phpfox_Search::instance();
	}

    /**
     * @return string
     */
	protected function getHidden(){
	    return implode(' ', $this->getParam('hidden',array()));

    }
	
	/**
	 * Creates a grouping for a param.
	 *
	 * @param string $sVar The name of the group.
	 * @return object This this class.
	 */
	protected function param($sVar)
	{
		if (isset(self::$_aParams[$sVar]))
		{		
			$this->_sCacheVar = $sVar;			
		}
		
		return $this;
	}

    public function clean()
    {

    }
}