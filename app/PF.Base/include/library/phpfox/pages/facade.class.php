<?php

/**
 * Class Phpfox_Pages_Facade
 */
class Phpfox_Pages_Facade extends Phpfox_Service
{
    /**
     * @return \Phpfox_Pages_Pages
     */
    public function getItems()
    {
    }

    /**
     * @return \Phpfox_Pages_Category
     */
    public function getCategory()
    {
    }

    /**
     * @return \Phpfox_Pages_Process
     */
    public function getProcess()
    {
    }

    /**
     * @return \Phpfox_Pages_Type
     */
    public function getType()
    {
    }

    /**
     * @return \Phpfox_Pages_Browse
     */
    public function getBrowse()
    {
    }

    public function getItemType()
    {
        return 'pages';
    }

    public function getItemTypeId()
    {
        return 0;
    }

    /**
     * @param $name
     * @param array $params
     * @param string $languageId
     */
    public function getPhrase($name, $params = [], $languageId = '')
    {
    }

    /**
     * @param $name
     * @return string
     */
    public function getPhraseName($name)
    {
        return $name;
    }


    /**
     * @param $name
     * @return string|array|int
     */
    public function getUserParam($name)
    {
    }

    public function getPageItemType($iPageId)
    {
        $aRow = $this->database()->select('p.*')
            ->from(Phpfox::getT('pages'), 'p')
            ->where('p.page_id = ' . (int)$iPageId)
            ->execute('getSlaveRow');
        if (!$aRow || !defined('PHPFOX_PAGE_ITEM_TYPE_' . $aRow['item_type'])) {
            return false;
        }

        return constant('PHPFOX_PAGE_ITEM_TYPE_' . $aRow['item_type']);
    }
}
