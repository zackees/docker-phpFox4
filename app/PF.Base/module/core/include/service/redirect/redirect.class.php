<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 *
 *
 * @copyright        [PHPFOX_COPYRIGHT]
 * @author        Miguel Espinoza
 * @package        Phpfox_Service
 * @version        $Id: core.class.php 2209 2010-11-26 12:24:28Z phpFox LLC $
 */
class Core_Service_Redirect_Redirect extends Phpfox_Service
{
    /**
     * If a url is requested and it does not exist this function checks if it ever existed
     * if so then it provides the old title for the item
     *
     * @example http://site.com/index.php?do=/user1/blog/blog-1fds/ vs http://site.com/index.php?do=/user1/blog/blog-1/
     *
     * @param string $sModule
     * @param string $sOldTitle
     *
     * @return string|false
     */
    public function getRedirection($sModule, $sOldTitle)
    {

        if (Phpfox::isModule($sModule) && Phpfox::hasCallback($sModule, 'getRedirectionTable')) {
            $sNewTitle = $this->database()->select('new_title')
                ->from(Phpfox::callback($sModule . '.getRedirectionTable'))
                ->where('old_title = "' . $sOldTitle . '"')
                ->execute('getSlaveField');

            if (!empty($sNewTitle)) {
                return $sNewTitle;
            }
        }
        return false;
    }

    /**
     * This function checks if a user is allowed to update the URL of a specific blog
     *
     * @param int $sModule
     * @param int $iUser
     * @param int $iItemId
     *
     * @return bool
     */
    public function canUpdateURL($sModule, $iUser, $iItemId)
    {
        // first the general permission
        if (!Phpfox::isModule($sModule)
            || (Phpfox::getUserParam($sModule . '.can_update_url') == false)
            || !Phpfox::hasCallback($sModule, 'getRedirectionTable')) {
            return false;
        }
        $iCnt = $this->database()->select('COUNT(*)')
            ->from(Phpfox::callback($sModule . '.getRedirectionTable'))
            ->where('item_id = ' . (int)$iItemId)
            ->execute('getSlaveField');
        if ($iCnt >= Phpfox::getUserParam($sModule . '.how_many_url_updates') && $iCnt > 0 &&
            Phpfox::getUserParam($sModule . '.how_many_url_updates') > 0) {
            return false;
        }

        return true;
    }

    /**
     * This function gets the ReWrites from cache or database, this is not related to a specific section, but to the
     * entire site. This function is used in the AdminCP -> Tools -> SEO -> Rewrite URL, but not in the URL Library.
     * There is very little benefit in caching this query because it is only used in the AdminCP, and the cache object
     * created in the URL library includes the reverse rewrites which makes it too complex for just displaying in the
     * AdminCP.
     *
     * @return array
     */
    public function getRewrites()
    {

        $aRows = Phpfox_Database::instance()->select('r.url, r.replacement, r.rewrite_id')
            ->from(Phpfox::getT('rewrite'), 'r')
            ->order('rewrite_id DESC')
            ->execute('getSlaveRows');

        return $aRows;
    }
}