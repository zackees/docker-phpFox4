<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Search_Service_Process
 */
class Search_Service_Process extends Phpfox_Service
{
    public function logSearchWord($aWord)
    {
        $iTimestamp = PHPFOX_TIME;
        $iCountWordExisted = 0;

        foreach ($aWord as $sWord) {
            $aWord = $this->database()->select('*')
                ->from(Phpfox::getT('search_word_log'))
                ->where(['search_word' => $sWord])
                ->executeRow();

            if (!empty($aWord)) {
                $iCountWordExisted++;

                if (!Phpfox::getLib('session')->get('search_log_flood') || Phpfox::getLib('session')->get('search_log_flood') != $iTimestamp) {
                    if (!((PHPFOX_TIME - $aWord['time_stamp']) < 60)) {// Seconds
                        $this->database()->updateCounter('search_word_log', 'total', 'search_word_id', $aWord['search_word_id']);
                        $this->database()->update(Phpfox::getT('search_word_log'), ['time_stamp' => $iTimestamp], 'search_word_id =' . $aWord['search_word_id']);
                    }
                }
            } else {
                $this->database()->insert(Phpfox::getT('search_word_log'), ['search_word' => $sWord, 'time_stamp' => PHPFOX_TIME]);
            }
        }

        if ($iCountWordExisted) {
            Phpfox::getLib('session')->set('search_log_flood', $iTimestamp);
        }
    }

    /**
     * @param $sMethod
     * @param $aArguments
     *
     * @return null
     */
    public function __call($sMethod, $aArguments)
    {
        /**
         * Check if such a plug-in exists and if it does call it.
         */
        if ($sPlugin = Phpfox_Plugin::get('search.service_process__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}
