<?php

namespace Api\Message;

class Thread extends \Core\Api
{
    public function get($id)
    {
        if(!\Phpfox::isAppActive('Core_Messages')) {
            return [
                'thread'   => null,
                'messages' => [],
            ];
        }
        list($thread, $messages) = \Phpfox::getService('mail')->getThreadedMail($id);

        $objects = [];
        foreach ($messages as $message) {
            $objects[] = new Thread\Objects($message);
        }

        $object = [
            'thread'   => new Objects($thread),
            'messages' => $objects,
        ];

        return $object;
    }

    public function post($id)
    {
        if(!\Phpfox::isAppActive('Core_Messages')) {
            return null;
        }

        $this->auth();
        $this->requires([
            'message',
        ]);

        \Phpfox::getService('mail.process')->add([
            'thread_id' => $id,
            'message'   => $this->request->get('message'),
        ]);

        $thread = new Thread\Objects(\Phpfox::getService('mail')->getThreadedMail($id, 0, true));

        return $thread;
    }
}