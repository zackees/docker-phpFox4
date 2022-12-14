<?php
defined('PHPFOX') or exit('No dice!');

/**
 * In the custom_relation_data table the values for status_id are
 * 1: has not been confirmed by `with_user_id`
 * 2: has been confirmed by `with_user_id`
 * 3: has been denied by `with_user_id` // useful to hide it
 *
 * Class Custom_Service_Relation_Process
 */
class Custom_Service_Relation_Process extends Phpfox_Service
{
    /**
     * @var string
     */
    private $_sLangId;

    /**
     * Custom_Service_Relation_Process constructor.
     */
    public function __construct()
    {
        $this->_sLangId = Phpfox::getUserBy('language_id');
        if (empty($this->_sLangId)) {
            $aLang = Phpfox::getService('language')->get(['is_default = 1']);
            $this->_sLangId = $aLang[0]['language_id'];
        }
        $this->_sTable = Phpfox::getT('custom_relation');
    }

    /**
     * This is the auxiliary function to add new relationship statuses
     *
     * @param string $sType
     * @param array $aVals
     * @param string $sStatusName
     *
     * @return mixed|string
     */
    private function _add($sType, $aVals, $sStatusName = '')
    {
        if (empty($sStatusName)) {
            $sStatusName = $aVals[$sType][$this->_sLangId];
        }

        /* Check if user entered a language phrase for the status name */
        if (preg_match('/\{phrase var=\'([a-z\._]+)\'/', $sStatusName, $aMatch)) {
            foreach ($aVals[$sType] as $sLang => $sText) {
                $aVals[$sType][$sLang] = _p($aMatch[1], [], $sLang);
            }
        }
        if (preg_match('/\{_p var=\'([a-z\._]+)\'/', $sStatusName, $aMatch)) {
            foreach ($aVals[$sType] as $sLang => $sText) {
                $aVals[$sType][$sLang] = _p($aMatch[1], [], $sLang);
            }
        }

        /* We need the phrase var to add to this->_sTable */
        $sStatusName = Phpfox::getService('language.phrase.process')->add([
            'var_name' => 'custom_relation_' . $sStatusName,
            'text' => $aVals[$sType]
        ]);
        $this->database()->insert($this->_sTable, [
            'phrase_var_name' => $sStatusName,
            'confirmation' => (isset($aVals['confirmation']) && $aVals['confirmation'] == 'on') ? 1 : 0
        ]);
        return $sStatusName;
    }

    /**
     * Auxiliary function to update relationship statuses
     *
     * @param String $sType can be "new", "feed_with" or "feed_new"
     * @param array $aVals array of values to get info from, passed from a form submit
     *
     * @return null
     */
    private function _update($sType, $aVals)
    {
        $sLanguageVar = null;
        /* get the phrase id */
        $aNew = array_keys($aVals[$sType]);
        foreach ($aVals[$sType][$aNew[0]] as $sLanguage => $sText) {
            $aParts = explode('.', $aNew[0]);
            if (count($aParts) == 1) {
                $aParts[1] = $aParts[0];
            }
            $iPhraseId = $this->database()->select('phrase_id')
                ->from(Phpfox::getT('language_phrase'))
                ->where('var_name = "' . $aParts[1] . '" AND language_id = "' . $sLanguage . '"')
                ->execute('getSlaveField');
            if ((int)$iPhraseId < 1) {
                /* phrase does not exist ? */
                die('Oops no phrase id');
            }
            Phpfox::getService('language.phrase.process')->update($iPhraseId, $sText);
            if (!$sLanguageVar) {
                $sLanguageVar = $aParts[1];
            }
        }
        return $sLanguageVar;
    }

    /**
     * This function adds a new relationships status:
     *    - Adds an entry in custom_relation
     *    - Adds language phrases
     *
     * It also updates existing ones:
     *    - if instead of a language_id a phrase is passed
     *    - it updates the phrase instead of creating a new one
     *
     * @param array $aVals
     *
     * @return boolean
     */
    public function add($aVals)
    {
        if (!isset($aVals['new'])) {
            return Phpfox_Error::set(_p('invalid_array'));
        }

        $sLanguageVar = '';
        $bCanClearPhraseCache = false;

        if (isset($aVals['new']) && isset($aVals['new'][$this->_sLangId])) {
            /* This is a new phrase */
            $sLanguageVar = $this->_add('new', $aVals);
        } elseif (isset($aVals['new'])) {
            $sPhrase = array_keys($aVals['new']);
            $sPhrase = array_pop($sPhrase);
            /* User is updating the existing phrase */
            $sLanguageVar = $this->_update('new', $aVals);
            $bCanClearPhraseCache = true;
            $this->database()->update(Phpfox::getT('custom_relation'), [
                'confirmation' => (isset($aVals['confirmation']) && $aVals['confirmation'] == 'on') ? 1 : 0
            ], 'phrase_var_name = "' . Phpfox::getLib('parse.input')->clean($sPhrase) . '"');
        }

        $aParts = explode('.', $sLanguageVar);
        if (count($aParts) == 1) {
            $aParts[1] = $aParts[0];
        }
        $sRelationVarName = isset($aParts[1]) ? $aParts[1] : '';
        if (isset($aVals['feed_with']) && isset($aVals['feed_with'][$this->_sLangId])) {
            /* This is a new phrase but the status already exists, otherwise $aVals['feed_with'] would
             * have a phrase var*/
            Phpfox::getService('language.phrase.process')->add([
                'var_name' => $sRelationVarName . '_with',
                'text' => $aVals['feed_with']
            ]);
        } elseif (isset($aVals['feed_with'])) {
            /* User is updating the existing phrase */
            $this->_update('feed_with', $aVals);
            $bCanClearPhraseCache = true;
        }

        if (isset($aVals['feed_new']) && isset($aVals['feed_new'][$this->_sLangId])) {
            /* This is a new phrase */
            Phpfox::getService('language.phrase.process')->add([
                'var_name' => $sRelationVarName . '_new',
                'text' => $aVals['feed_new']
            ]);
        } elseif (isset($aVals['feed_new'])) {
            /* User is updating the existing phrase */
            $this->_update('feed_new', $aVals);
            $bCanClearPhraseCache = true;
        }

        if ($bCanClearPhraseCache) {
            Core\Lib::phrase()->clearCache();
        }

        return true;
    }

    /**
     * This function deletes a relationship status and also deletes
     * the language phrase associated with it
     *
     * @param int $iId
     *
     * @return bool
     */
    public function delete($iId)
    {
        $aStatus = $this->database()->select('*')
            ->from($this->_sTable)
            ->where('relation_id = ' . (int)$iId)
            ->execute('getSlaveRow');

        if (empty($aStatus)) {
            return Phpfox_Error::set(_p('status_id_does_not_exist'));
        }

        $this->database()->delete($this->_sTable, 'relation_id = ' . (int)$iId);
        Phpfox::getService('language.phrase.process')->delete($aStatus['phrase_var_name'], true);
        return true;
    }

    /**
     * This function sets the relationship status for Phpfox::getUserId()
     * by inserting into custom_relation_data
     * status
     *        0 => one of the users moved to another relationship, this one is no longer valid
     *        1 => user added the status but has not been confirmed by user_with
     *        2 => user and user_with confirmed the status
     * if the user is only setting its relationship status and not defining
     *        another user then the status_id is set to 2 as its confirmed by
     *        the only user involved
     *
     * @param int $iRelationType Type of relation (married, engaged, single...), it can be zero if its just confirming a previous request
     * @param int $iWith The other other involved in the relation
     * @param int $iUser The current user taking action
     * @param int $iRelationDataId
     *
     * @return bool
     */
    public function updateRelationship($iRelationType, $iWith = null, $iUser = null, $iRelationDataId = null)
    {
        $iUser = ($iUser == null) ? Phpfox::getUserId() : (int)$iUser;

        if ($iRelationDataId) {
            $this->cache()->remove(['relations', "user_" . $iUser . "_relation_" . $iRelationDataId]);
            $this->cache()->remove(['relations', "user_" . $iWith . "_relation_" . $iRelationDataId]);
        }

        $this->cache()->remove(['reluser', $iUser]);
        $this->cache()->remove(['reluser', $iWith]);

        $aAllRelations = Phpfox::getService('custom.relation')->getAll();
        foreach ($aAllRelations as $aRelation) {
            if ($aRelation['relation_id'] == $iRelationType) {
                if ($aRelation['confirmation'] != 1) {
                    $iWith = null;
                }
                break;
            }
        }
        if ($iWith == $iUser) { // funky case where we got engaged to ourselves
            $iWith = null;
        }
        /* check if this user was listed for a relationship with another user */
        $aExisting = $this->database()->select('crd.relation_data_id, crd.status_id, crd.relation_id, crd.user_id as crd_user_id, crd.with_user_id, cr.phrase_var_name, u.full_name as full_name_with')
            ->from(Phpfox::getT('custom_relation_data'), 'crd')
            ->join(Phpfox::getT('custom_relation'), 'cr', 'cr.relation_id = crd.relation_id')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = crd.user_id')
            ->where('crd.with_user_id = ' . (int)$iUser . ' AND crd.user_id = ' . (int)$iWith)
            ->order('relation_data_id DESC')
            ->execute('getSlaveRow');


        /* lets add a plug in here so if anyone wants a different behavior they can still get it */
        if ($sPlugin = Phpfox_Plugin::get('custom.service_relation_process_updaterelationship__1')) {
            eval($sPlugin);
            if (isset($mReturnPlugin)) {
                return $mReturnPlugin;
            }
        }
        if (!empty($aExisting['relation_data_id']) && $aExisting['status_id'] == 1) {
            /* we update because this user is confirming the relationship with the other user */
            $this->database()->update(Phpfox::getT('custom_relation_data'), [
                'status_id' => '2'
            ], 'relation_data_id = ' . $aExisting['relation_data_id']);
            // We add another record (identical) because a feed will point to this one, to separate comments
            $iNewRelationId = $this->database()->insert(Phpfox::getT('custom_relation_data'), [
                'relation_id' => $aExisting['relation_id'],
                'user_id' => $iUser,
                'with_user_id' => $aExisting['crd_user_id'],
                'status_id' => '2',
                'total_like' => '0',
                'total_comment' => '0'
            ]);
            $this->database()->update(Phpfox::getT('user_field'), [
                'relation_data_id' => $aExisting['relation_data_id'],
                'relation_with' => ($aExisting['crd_user_id'] == Phpfox::getUserId() ? 0 : 1)
            ], 'user_id = ' . Phpfox::getUserId());

            /* Send an email and insert in the feed table */
            $aUserLanguages = $this->_getUsersLanguage($iUser, $iWith);
            if ($iUser != Phpfox::getUserId()) {
                Phpfox::getLib('mail')->to($iUser)
                    ->subject('relationship_status_confirmed')
                    ->message([
                        'full_name_and_full_name_with_are_now_phrase_var_name',
                        [
                            'full_name'       => Phpfox::getUserBy('full_name'),
                            'full_name_with'  => $aExisting['full_name_with'],
                            'phrase_var_name' => _p($aExisting['phrase_var_name'], [], $aUserLanguages[$iUser])
                        ]
                    ])
                    ->send();
            }
            if ($iWith != Phpfox::getUserId()) {
                Phpfox::getLib('mail')->to($iWith)
                    ->subject('relationship_status_confirmed')
                    ->message([
                        'full_name_and_full_name_with_are_now_phrase_var_name',
                        [
                            'full_name'       => Phpfox::getUserBy('full_name'),
                            'full_name_with'  => $aExisting['full_name_with'],
                            'phrase_var_name' => _p($aExisting['phrase_var_name'], [], $aUserLanguages[$iWith])
                        ]
                    ])
                    ->send();
            }
            /* Add the feed */
            (Phpfox::isModule('feed') && Phpfox::getParam('user.enable_feed_user_update_relationship', 1) ? Phpfox::getService('feed.process')->add('custom_relation', $iNewRelationId, 0, 0, 0, $aExisting['with_user_id'], $aExisting['crd_user_id']) : null);

            // delete any pending friend requests
            $this->database()->delete(Phpfox::getT('friend_request'),
                'relation_data_id = ' . (int)$aExisting['relation_data_id']);

            return true;
        } else {
            if (isset($aExisting['status_id']) && $aExisting['status_id'] == 2 && $aExisting['relation_id'] == $iRelationType) {
                $aPastRelation = Phpfox::getService('custom.relation')->getLatestForUser($iUser);
                if ($aPastRelation['relation_data_id'] == $aExisting['relation_data_id']) {
                    return false;
                }
            }
        }

        /* Make sure this user is not adding the same type of relationship */
        $aExisting = $this->database()->select('crd.relation_data_id, crd.with_user_id, crd.user_id, crd.relation_id, crd.status_id')
            ->from(Phpfox::getT('custom_relation_data'), 'crd')
            ->where('crd.user_id = ' . (int)$iUser . ' or crd.with_user_id = ' . (int)$iUser)
            ->order('relation_data_id DESC')
            ->execute('getSlaveRow');

        if (isset($aExisting['relation_data_id']) && !empty($aExisting['relation_data_id'])
            && ((isset($aExisting['with_user_id']) && $aExisting['with_user_id'] == (int)$iWith) || ($aExisting['status_id'] == 3 && empty($iWith)))
            && isset($aExisting['relation_id']) && $aExisting['relation_id'] == (int)$iRelationType) {
            return false;
        }

        /* User is starting a relationship */
        // is user "moving away" from a relationship?
        if ((isset($aExisting['user_id']) && $aExisting['user_id'] != $iUser)
            || (isset($aExisting['with_user_id']) && $aExisting['with_user_id'] != $iUser)
        ) {
            // remove the friend request for the relation_data_id (this should not happen but better be safe)
            $this->database()->delete(Phpfox::getT('friend_request'),
                'relation_data_id = ' . $aExisting['relation_data_id'] . ' OR (user_id = ' . $iUser . ' AND friend_user_id = ' . $aExisting['with_user_id'] . ') OR (user_id = ' . $aExisting['with_user_id'] . ' AND friend_user_id = ' . $iUser . ')');
        }
        /* We delete all previous relationships of this same type */
        $this->database()->delete(Phpfox::getT('custom_relation_data'),
            '(user_id = ' . $iUser . ' OR with_user_id = ' . $iUser . ') AND status_id = 1');
        $aAllRelations = Phpfox::getService('custom.relation')->getAll();
        foreach ($aAllRelations as $aRelation) {
            if ($aRelation['relation_id'] == $iRelationType && $aRelation['confirmation'] != 1) {
                $iWith = null;
                break;
            }
        }
        /* add to the relation_data table */
        $aExisting['relation_data_id'] = $this->database()->insert(Phpfox::getT('custom_relation_data'), [
            'relation_id' => (int)$iRelationType,
            'user_id' => (int)$iUser,
            'with_user_id' => (int)$iWith,
            'status_id' => ($iWith === null) ? 2 : 1
        ]);
        // update the user_field table
        $this->database()->update(Phpfox::getT('user_field'), [
            'relation_data_id' => $aExisting['relation_data_id'],
            'relation_with' => 0
        ], 'user_id = ' . $iUser);
        /* it would be zero if user is setting to single or no state */
        if (((int)$iWith) > 0) {
            /* Add the "notification", the matching part will be built later */
            $this->database()->insert(Phpfox::getT('friend_request'), [
                'user_id' => (int)$iWith,
                'friend_user_id' => (int)$iUser,
                'relation_data_id' => $aExisting['relation_data_id'],
                'time_stamp' => PHPFOX_TIME
            ]);
        }

        if (!isset($aExisting['phrase_var_name'])) {
            $aExisting['phrase_var_name'] = $this->database()->select('phrase_var_name')
                ->from(Phpfox::getT('custom_relation'))
                ->where('relation_id = ' . (int)$iRelationType)
                ->execute('getSlaveField');
        }

        /* This happens only when the user is setting the status without giving
         * another, i.e. user only "engaged", not "engaged to..." */
        if ($iWith === null) {
            $iWith = $iUser;
        }

        /* Send an email and insert in the feed table */
        if ($iWith != $iUser) {
            $aUserLanguages = $this->_getUsersLanguage($iUser, $iWith);
            $sLink = Phpfox_Url::instance()->makeUrl('friend.accept');
            if ($iUser != Phpfox::getUserId()) {
                Phpfox::getLib('mail')->to($iUser)
                    ->subject('relationship_status_confirmation')
                    ->message([
                        'full_name_wants_to_list_you_both_as_phrase_var_name',
                        [
                            'full_name' => Phpfox::getUserBy('full_name'),
                            'phrase_var_name' => _p($aExisting['phrase_var_name'], [], $aUserLanguages[$iUser]),
                            'link' => $sLink
                        ]
                    ])
                    ->send();
            }
            if ($iWith != Phpfox::getUserId()) {
                Phpfox::getLib('mail')->to($iWith)
                    ->subject('relationship_status_confirmation')
                    ->message([
                        'full_name_wants_to_list_you_both_as_phrase_var_name',
                        [
                            'full_name'       => Phpfox::getUserBy('full_name'),
                            'phrase_var_name' => _p($aExisting['phrase_var_name'], [], $aUserLanguages[$iWith]),
                            'link'            => $sLink
                        ]
                    ])
                    ->send();
            }
        }

        /* Add the feed */
        (Phpfox::isModule('feed') && Phpfox::getParam('user.enable_feed_user_update_relationship', 1) ? Phpfox::getService('feed.process')->add('custom_relation', $aExisting['relation_data_id'], 0, 0, 0, $iUser) : null);

        return true;
    }

    /**
     * This function checks that a friend request matches a relation,
     * if it does'nt, then it deletes the friend request
     *
     * @param int $iId
     *
     * @return void
     */
    public function checkRequest($iId)
    {
        $iRelationId = $this->database()
            ->select('relation_data_id')
            ->from(Phpfox::getT('custom_relation_data'))
            ->where('relation_data_id = ' . (int)$iId)
            ->execute('getSlaveField');
        if ($iRelationId != $iId) {
            $this->database()->delete(Phpfox::getT('friend_request'), 'relation_data_id = ' . (int)$iId);
        }
    }

    /**
     * PHP version of http://bit.ly/4DdLtW
     * This function updates the custom_relation_data table setting status_id = 3
     *
     * @param int $iStatusId
     * @param mixed $iUser if this param is available and is an integer it is
     *                     treated as a condition so only deny $iStatusId if $iUser is the
     *                     receiver of the request
     *
     * @return boolean
     */
    public function denyStatus($iStatusId, $iUser = null)
    {
        /* Make sure the status id exists (we may send a notification so this
         * has to exist) */
        $aRequest = Phpfox::getService('custom.relation')->getDataById($iStatusId);
        if (empty($aRequest)) {
            return false;
        }

        $this->cache()->remove(['relations', "user_" . $aRequest['user_id'] . "_relation_" . $iStatusId]);
        $this->cache()->remove(['relations', "user_" . $aRequest['with_user_id'] . "_relation_" . $iStatusId]);

        $this->cache()->remove(['reluser', $aRequest['user_id']]);
        $this->cache()->remove(['reluser', $aRequest['with_user_id']]);

        if ($iUser === null || (((int)$iUser) > 0 && $aRequest['with_user_id'] == $iUser)) {
            $this->database()->update(Phpfox::getT('custom_relation_data'), [
                'status_id' => 3
            ], 'relation_data_id = ' . (int)$iStatusId);
            return true;
        }

        return false;
    }

    private function _getUsersLanguage($iUser, $iWith)
    {
        $aUsers = [
            $iUser => Phpfox::getLanguageId(),
            $iWith => Phpfox::getLanguageId()
        ];
        $aLanguages = db()->select('user_id, language_id')
            ->from(':user')
            ->where([
                'user_id' => [
                    'in' => implode(',', [(int)$iUser, (int)$iWith])
                ]
            ])
            ->executeRows();

        foreach ($aLanguages as $aLang) {
            $aUsers[$aLang['user_id']] = $aLang['language_id'];
        }

        return $aUsers;
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
        if ($sPlugin = Phpfox_Plugin::get('custom.service_relation_process__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}
