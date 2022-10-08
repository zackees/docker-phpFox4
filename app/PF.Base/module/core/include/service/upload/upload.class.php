<?php

/**
 * Class Core_Service_Upload_Upload
 * @author  phpFox LLC
 */
class Core_Service_Upload_Upload extends Phpfox_Service
{
    public function upload($aParams = [])
    {
        if (!isset($aParams['destination'])) {
            return false;
        }
        $iId = $this->database()->insert(':upload_temp',[
            'user_id' => Phpfox::getUserId(),
            'destination' => $aParams['destination'],
            'type' => $this->getFileType($aParams['destination']),
            'time_stamp' => PHPFOX_TIME
        ]);
        return $iId;
    }

    /**
     * @param $sDestination
     *
     * @return int 1:image|2:video|3:others
     */
    private function getFileType($sDestination)
    {
        return 1;
    }

    //Remove old temp data if expired
    public function clean()
    {
        return true;
    }

    /**
     * Remove a temporary file
     *
     * @param int $iUploadId
     */
    public function remove($iUploadId)
    {
        return true;
    }

    public function ordering()
    {
        return true;
    }

    public function cleanSession($sSessionName)
    {
        return true;
    }
}