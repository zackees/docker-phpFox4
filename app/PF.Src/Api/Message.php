<?php

namespace Api;

class Message extends \Core\Api
{
    public function get($id = null)
    {
        if ($id) {
            $message = $this->db->select('t.*, ' . \Phpfox::getUserField())
                ->from(':mail_thread_text', 't')
                ->join(':user', 'u', 'u.user_id = t.user_id')
                ->where(['t.message_id' => $id])->execute('getRow');

            $object = new Message\Thread\Objects($message);
        } else {
            $this->auth();
            $object = [];
            if(\Phpfox::isAppActive('Core_Messages')) {
                list(, $messages, ) = \Phpfox::getService('mail')->get();
                foreach ($messages as $message) {
                    $object[] = new Message\Objects($message);
                }
            }
        }

        return $object;
    }
}