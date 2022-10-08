<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Session;

use Phpfox;


/**
 * Class DatabaseAdapter
 * @package Core\Session
 */
class DatabaseAdapter implements SaveHandlerInterface, \SessionHandlerInterface
{
    /**
     * @var int
     */
    private $found = 0;

    private $lifetime;

    /**
     * @var string
     */
    private $sessionTable;


    /**
     * DatabaseAdapter constructor.
     * @param array $params
     */
    public function __construct($params = [])
    {
        extract($params, EXTR_OVERWRITE);

        $this->sessionTable = Phpfox::getT('core_session_data');

        $config = session_get_cookie_params();
        $this->lifetime = $config['lifetime'];
    }

    public function registerSaveHandler()
    {
        session_set_save_handler(
            array($this, 'open'),
            array($this, 'close'),
            array($this, 'read'),
            array($this, 'write'),
            array($this, 'destroy'),
            array($this, 'gc')
        );
    }

    public function close()
    {
        return true;
    }

    public function destroy($session_id)
    {
        Phpfox::getLib('database')
            ->delete($this->sessionTable, "session_id='{$session_id}'");
    }

    public function gc($maxlifetime)
    {
        Phpfox::getLib('database')
            ->delete($this->sessionTable, 'expired_at <' . time());
        return true;
    }

    public function open($save_path, $name)
    {
        return true;
    }

    public function read($session_id)
    {
        $row = Phpfox::getLib('database')
            ->select('s.*')
            ->from($this->sessionTable, 's')
            ->where("s.session_id='{$session_id}'")
            ->execute('getSlaveRow');

        if ($row && $row['session_id']) {
            $this->found = true;
            $this->lifetime = (int)$row['lifetime'];
            return $row['session_data'];
        }

        return '';
    }

    public function write($session_id, $session_data)
    {
        $databaseObject = Phpfox::getLib('database');
        //We won't store empty session, it's useless
        if (empty($session_data)) {
            $databaseObject->delete($this->sessionTable, ['session_id' => $session_id]);
            return true;
        }
        $isExists = $this->found || (!empty($existedSessionId = $databaseObject->select('session_id')
                    ->from($this->sessionTable)
                    ->where(['session_id' => $session_id])
                    ->executeField()) && $existedSessionId == $session_id);

        if ($isExists) {
            $success = $databaseObject->update($this->sessionTable, [
                'session_data' => $session_data,
                'lifetime' => $this->lifetime,
                'expired_at' => $this->lifetime + time(),
            ], "session_id='{$session_id}'");
        } else {
            $success = $databaseObject->insert($this->sessionTable, [
                'session_id' => $session_id,
                'session_data' => $session_data,
                'lifetime' => $this->lifetime,
                'expired_at' => $this->lifetime + time(),
            ]);
        }

        return $success;
    }

    public function isValid()
    {
        return $this->write('test-session-id', '');
    }
}