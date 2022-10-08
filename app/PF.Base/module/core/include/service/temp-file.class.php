<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 *
 * @copyright        [PHPFOX_COPYRIGHT]
 */
class Core_Service_Temp_File extends Phpfox_Service
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_sTable = Phpfox::getT('temp_file');
    }

    /**
     * Get info of temp file
     * @param $id
     * @param null $fields
     * @return array|false
     */
    public function getByFields($id, $fields = null)
    {
        if (empty($id)) {
            return false;
        }

        if (empty($fields)) {
            $fields = '*';
        }

        $file = db()->select($fields)
            ->from($this->_sTable)
            ->where([
                'file_id' => $id,
            ])->executeRow();

        return $file;
    }

    /**
     * @param array $aParams
     *
     * @return int
     */
    public function add($aParams = [])
    {
        $aParams = array_merge([
            'user_id'    => !empty($aParams['user_id']) ? (int)$aParams['user_id'] : (int)Phpfox::getUserId(),
            'time_stamp' => PHPFOX_TIME
        ], $aParams);

        return db()->insert($this->_sTable, $aParams);
    }

    public function updateProfile($aParams = [])
    {
        $aParams = array_merge([
            'time_stamp' => PHPFOX_TIME
        ], $aParams);

        $aConds = [
            'user_id' => !empty($aParams['user_id']) ? (int)$aParams['user_id'] : (int)Phpfox::getUserId(),
            'type'    => 'profile'
        ];

        return db()->update($this->_sTable, $aParams, $aConds);
    }

    public function getProfile($iUserId = null)
    {
        $aParams = [
            'user_id' => $iUserId ? (int)$iUserId : (int)Phpfox::getUserId(),
            'type'    => 'profile'
        ];

        return db()->select('path')->from($this->_sTable)->where($aParams)->order('time_stamp DESC')->execute('getSlaveField');
    }

    /**
     * @param int $iId
     *
     * @return array|int|string
     */
    public function get($iId = 0)
    {
        if (empty($iId)) {
            return [];
        }

        return db()->select('*')->from($this->_sTable)->where(['file_id' => (int)$iId])->execute('getSlaveRow');
    }

    /**
     * @param int  $iId
     * @param bool $bDeleteFile
     *
     * @return bool
     */
    public function delete($iId = 0, $bDeleteFile = false)
    {
        if (empty($iId)) {
            return false;
        }

        if ($bDeleteFile) {
            $aFile = $this->get($iId);
            if (empty($aFile)) {
                return false;
            }

            if (!Phpfox::hasCallback($aFile['type'], 'getUploadParams')) {
                return false;
            }

            $aParams = [
                'upload_dir'      => Phpfox::getParam('core.dir_pic'),
                'upload_url'      => Phpfox::getParam('core.url_pic'),
                'thumbnail_sizes' => []

            ];

            $aParams = array_merge($aParams, Phpfox::callback($aFile['type'] . '.getUploadParams'), $aFile);
            $aParams['update_space'] = false;
            Phpfox::getService('user.file')->remove($aParams);

        }

        return db()->delete($this->_sTable, 'file_id = ' . $iId);
    }

    /**
     * @description: clean unused temp files
     * @return bool
     */
    public function clean()
    {
        $iTime = PHPFOX_TIME - 60 * 60;
        $aRows = db()->select('file_id')->from($this->_sTable)->where('time_stamp < ' . $iTime)->order('file_id ASC')->limit(100)->execute('getSlaveRows');
        foreach ($aRows as $aRow) {
            $this->delete($aRow['file_id'], true);
        }
        return true;
    }
}
